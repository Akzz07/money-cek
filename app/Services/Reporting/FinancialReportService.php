<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Domain\Money\Money;
use App\DTOs\CategoryExpenseItem;
use App\DTOs\FinancialReportOverview;
use App\DTOs\MonthlyCashFlowSummary;
use App\Enums\AccountStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class FinancialReportService
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
    ) {}

    /**
     * Generate financial report overview for the given user and period.
     */
    public function getReport(User $user, ?string $period = 'this_month'): FinancialReportOverview
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $now = CarbonImmutable::now($timezone);

        [$startDate, $endDate, $periodLabel, $periodKey, $priorStartDate, $priorEndDate] = $this->resolveDateRange($now, $period);

        // 1. Total Income and Total Expense in period
        $incomeSum = (string) (Transaction::query()
            ->where('user_id', $user->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('type', TransactionType::Income->value)
            ->where('transaction_date', '>=', $startDate->toDateString())
            ->where('transaction_date', '<', $endDate->toDateString())
            ->sum('amount') ?? '0.00');

        $expenseSum = (string) (Transaction::query()
            ->where('user_id', $user->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('type', TransactionType::Expense->value)
            ->where('transaction_date', '>=', $startDate->toDateString())
            ->where('transaction_date', '<', $endDate->toDateString())
            ->sum('amount') ?? '0.00');

        $totalIncome = Money::of($incomeSum);
        $totalExpense = Money::of($expenseSum);
        $netCashFlow = $totalIncome->minus($totalExpense);

        $incomeFloat = (float) $totalIncome->toDecimalString();
        $expenseFloat = (float) $totalExpense->toDecimalString();
        $savingsRate = $incomeFloat > 0.0
            ? round((($incomeFloat - $expenseFloat) / $incomeFloat) * 100.0, 1)
            : 0.0;

        // 2. Category Expense Breakdown
        $categoryBreakdown = $this->getCategoryBreakdown(
            $user,
            $startDate,
            $endDate,
            $priorStartDate,
            $priorEndDate,
            $totalExpense,
        );

        // 3. Monthly Trends (Last 6 months)
        $monthlyTrends = $this->getMonthlyTrends($user, $now);

        // 4. Accounts & Net Worth Summary
        $accountsSummary = collect();
        $typeSummary = [];
        $totalNetWorth = Money::zero();

        $accounts = $user->accounts()
            ->where('status', AccountStatus::Active->value)
            ->orderBy('name')
            ->get();

        foreach ($accounts as $account) {
            $balance = $this->balanceService->getBalance($account);
            $accountsSummary->push([
                'account' => $account,
                'balance' => $balance,
            ]);

            $typeKey = $account->type->value;
            $typeSummary[$typeKey] = ($typeSummary[$typeKey] ?? Money::zero())->plus($balance);

            if ($account->include_in_total_assets) {
                $totalNetWorth = $totalNetWorth->plus($balance);
            }
        }

        return new FinancialReportOverview(
            startDate: $startDate,
            endDate: $endDate,
            periodKey: $periodKey,
            periodLabel: $periodLabel,
            totalIncome: $totalIncome,
            totalExpense: $totalExpense,
            netCashFlow: $netCashFlow,
            savingsRate: $savingsRate,
            monthlyTrends: $monthlyTrends,
            categoryBreakdown: $categoryBreakdown,
            accountsSummary: $accountsSummary,
            typeSummary: $typeSummary,
            totalNetWorth: $totalNetWorth,
        );
    }

    /**
     * @return Collection<int, CategoryExpenseItem>
     */
    private function getCategoryBreakdown(
        User $user,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        CarbonImmutable $priorStartDate,
        CarbonImmutable $priorEndDate,
        Money $totalExpense,
    ): Collection {
        // Current period expense group by category
        $currentRows = Transaction::query()
            ->where('user_id', $user->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('type', TransactionType::Expense->value)
            ->where('transaction_date', '>=', $startDate->toDateString())
            ->where('transaction_date', '<', $endDate->toDateString())
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->get()
            ->keyBy('category_id');

        // Prior period expense group by category
        $priorRows = Transaction::query()
            ->where('user_id', $user->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('type', TransactionType::Expense->value)
            ->where('transaction_date', '>=', $priorStartDate->toDateString())
            ->where('transaction_date', '<', $priorEndDate->toDateString())
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $categoryIds = $currentRows->keys()->filter()->all();
        $categories = Category::query()->whereIn('id', $categoryIds)->get()->keyBy('id');

        $totalExpenseFloat = (float) $totalExpense->toDecimalString();
        $items = collect();

        foreach ($currentRows as $catId => $row) {
            $cat = $catId ? $categories->get((int) $catId) : null;
            $amount = Money::of((string) $row->total_amount);
            $amountFloat = (float) $amount->toDecimalString();

            $percentage = $totalExpenseFloat > 0.0
                ? round(($amountFloat / $totalExpenseFloat) * 100.0, 1)
                : 0.0;

            $priorRow = $priorRows->get($catId);
            $priorAmount = $priorRow ? Money::of((string) $priorRow->total_amount) : null;
            $changePercentage = null;

            if ($priorAmount !== null) {
                $priorFloat = (float) $priorAmount->toDecimalString();
                if ($priorFloat > 0.0) {
                    $changePercentage = round((($amountFloat - $priorFloat) / $priorFloat) * 100.0, 1);
                }
            }

            $items->push(new CategoryExpenseItem(
                category: $cat,
                categoryName: $cat ? $cat->name : 'Tanpa Kategori',
                color: $cat?->color ?? '#64748B',
                icon: $cat?->icon ?? 'tag',
                amount: $amount,
                percentage: $percentage,
                previousAmount: $priorAmount,
                changePercentage: $changePercentage,
            ));
        }

        return $items;
    }

    /**
     * Get monthly trends for the last 6 months.
     *
     * @return Collection<int, MonthlyCashFlowSummary>
     */
    private function getMonthlyTrends(User $user, CarbonImmutable $now): Collection
    {
        $months = collect();
        $rawSums = [];
        $maxAmount = 1.0;

        // Generate 6 months backwards up to current month
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->subMonthsNoOverflow($i);
            $start = $monthDate->startOfMonth();
            $end = $monthDate->addMonthNoOverflow()->startOfMonth();
            $key = $start->format('Y-m');

            $inc = (string) (Transaction::query()
                ->where('user_id', $user->id)
                ->where('status', TransactionStatus::Posted->value)
                ->where('type', TransactionType::Income->value)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<', $end->toDateString())
                ->sum('amount') ?? '0.00');

            $exp = (string) (Transaction::query()
                ->where('user_id', $user->id)
                ->where('status', TransactionStatus::Posted->value)
                ->where('type', TransactionType::Expense->value)
                ->where('transaction_date', '>=', $start->toDateString())
                ->where('transaction_date', '<', $end->toDateString())
                ->sum('amount') ?? '0.00');

            $incMoney = Money::of($inc);
            $expMoney = Money::of($exp);
            $netMoney = $incMoney->minus($expMoney);

            $incFloat = (float) $incMoney->toDecimalString();
            $expFloat = (float) $expMoney->toDecimalString();
            $maxAmount = max($maxAmount, $incFloat, $expFloat);

            $rawSums[] = [
                'key' => $key,
                'name' => $start->translatedFormat('M y'),
                'income' => $incMoney,
                'expense' => $expMoney,
                'net' => $netMoney,
                'incomeFloat' => $incFloat,
                'expenseFloat' => $expFloat,
            ];
        }

        foreach ($rawSums as $row) {
            $incHeight = round(($row['incomeFloat'] / $maxAmount) * 100.0, 1);
            $expHeight = round(($row['expenseFloat'] / $maxAmount) * 100.0, 1);

            $months->push(new MonthlyCashFlowSummary(
                periodKey: $row['key'],
                periodName: $row['name'],
                income: $row['income'],
                expense: $row['expense'],
                net: $row['net'],
                incomeBarHeightPercentage: max(4.0, $incHeight),
                expenseBarHeightPercentage: max(4.0, $expHeight),
            ));
        }

        return $months;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string, 3: string, 4: CarbonImmutable, 5: CarbonImmutable}
     */
    private function resolveDateRange(CarbonImmutable $now, ?string $period): array
    {
        return match ($period) {
            'last_month' => [
                $now->subMonthNoOverflow()->startOfMonth(),
                $now->startOfMonth(),
                'Bulan Lalu ('.$now->subMonthNoOverflow()->translatedFormat('F Y').')',
                'last_month',
                $now->subMonthsNoOverflow(2)->startOfMonth(),
                $now->subMonthNoOverflow()->startOfMonth(),
            ],
            'last_3_months' => [
                $now->subMonthsNoOverflow(2)->startOfMonth(),
                $now->addMonthNoOverflow()->startOfMonth(),
                '3 Bulan Terakhir',
                'last_3_months',
                $now->subMonthsNoOverflow(5)->startOfMonth(),
                $now->subMonthsNoOverflow(2)->startOfMonth(),
            ],
            'this_year' => [
                $now->startOfYear(),
                $now->addYear()->startOfYear(),
                'Tahun '.$now->year,
                'this_year',
                $now->subYear()->startOfYear(),
                $now->startOfYear(),
            ],
            default => [
                $now->startOfMonth(),
                $now->addMonthNoOverflow()->startOfMonth(),
                'Bulan Ini ('.$now->translatedFormat('F Y').')',
                'this_month',
                $now->subMonthNoOverflow()->startOfMonth(),
                $now->startOfMonth(),
            ],
        };
    }
}
