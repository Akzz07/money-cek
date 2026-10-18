<?php

declare(strict_types=1);

namespace App\Services\Budgets;

use App\Domain\Money\Money;
use App\DTOs\BudgetUsage;
use App\DTOs\MonthlyBudgetOverview;
use App\Models\Budget;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BudgetUsageService
{
    private const array MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Compute usage for a single budget.
     */
    public function usageFor(Budget $budget): BudgetUsage
    {
        $spentRaw = DB::table('transactions')
            ->where('user_id', $budget->user_id)
            ->where('category_id', $budget->category_id)
            ->where('type', 'expense')
            ->where('status', 'posted')
            ->where('transaction_date', '>=', $budget->period_start->toDateString())
            ->where('transaction_date', '<', $budget->period_end->toDateString())
            ->sum('amount');

        $spent = Money::of((string) ($spentRaw ?? '0.00'));

        return new BudgetUsage($budget, $spent);
    }

    /**
     * Compute full monthly overview for user in a given month.
     */
    public function getMonthlyBudgetOverview(User $user, ?string $month = null): MonthlyBudgetOverview
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $periodDate = $this->resolvePeriodDate($month, $timezone);

        $startDate = $periodDate->startOfMonth()->startOfDay();
        $endDate = $startDate->addMonthNoOverflow();

        $periodName = self::MONTH_NAMES[$startDate->month].' '.$startDate->year;
        $periodKey = $startDate->format('Y-m');

        $budgets = $user->budgets()
            ->active()
            ->whereDate('period_start', $startDate->toDateString())
            ->with('category')
            ->get();

        if ($budgets->isEmpty()) {
            return new MonthlyBudgetOverview(
                totalLimit: Money::zero(),
                totalSpent: Money::zero(),
                totalRemaining: Money::zero(),
                overallPercentage: 0.0,
                budgetsUsage: collect(),
                periodName: $periodName,
                periodKey: $periodKey,
                startDate: $startDate,
            );
        }

        // Single batch aggregation query to prevent N+1 queries
        $spentByCategory = DB::table('transactions')
            ->select('category_id', DB::raw('SUM(amount) as total_spent'))
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('status', 'posted')
            ->where('transaction_date', '>=', $startDate->toDateString())
            ->where('transaction_date', '<', $endDate->toDateString())
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->groupBy('category_id')
            ->pluck('total_spent', 'category_id');

        $totalLimit = Money::zero();
        $totalSpent = Money::zero();

        $budgetsUsage = $budgets->map(function (Budget $budget) use ($spentByCategory, &$totalLimit, &$totalSpent) {
            $spentVal = (string) ($spentByCategory[$budget->category_id] ?? '0.00');
            $usage = new BudgetUsage($budget, Money::of($spentVal));

            $totalLimit = $totalLimit->plus($usage->limit);
            $totalSpent = $totalSpent->plus($usage->spent);

            return $usage;
        });

        $totalRemaining = $totalLimit->minus($totalSpent);

        $totalLimitFloat = (float) $totalLimit->amount();
        $totalSpentFloat = (float) $totalSpent->amount();
        $overallPercentage = $totalLimitFloat > 0 ? round(($totalSpentFloat / $totalLimitFloat) * 100, 1) : 0.0;

        return new MonthlyBudgetOverview(
            totalLimit: $totalLimit,
            totalSpent: $totalSpent,
            totalRemaining: $totalRemaining,
            overallPercentage: $overallPercentage,
            budgetsUsage: $budgetsUsage,
            periodName: $periodName,
            periodKey: $periodKey,
            startDate: $startDate,
        );
    }

    private function resolvePeriodDate(?string $month, string $timezone): CarbonImmutable
    {
        if ($month !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $month.'-01', $timezone);
            } catch (\Throwable) {
            }
        }

        return CarbonImmutable::now($timezone);
    }
}
