<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Domain\Money\Money;
use App\DTOs\DashboardMetrics;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DashboardMetricsService
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

    public function __construct(
        private readonly AccountBalanceService $balanceService,
    ) {}

    public function getMetrics(User $user, ?string $month = null): DashboardMetrics
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';

        // 1. Resolve Planning Period
        $periodDate = $this->resolvePeriodDate($month, $timezone);
        $startDate = $periodDate->startOfMonth()->startOfDay();
        $endDate = $periodDate->endOfMonth()->endOfDay();

        $periodName = self::MONTH_NAMES[$startDate->month].' '.$startDate->year;
        $periodKey = $startDate->format('Y-m');

        // 2. Total Net Worth & Spendable Balance (Authoritative from AccountBalanceService)
        $totalBalance = $this->balanceService->getTotalNetWorth($user);
        $spendableBalance = $this->balanceService->getTotalSpendableBalance($user);

        // 3. Monthly Income & Expense (Filtered by transaction_date within the half-open or closed monthly boundary)
        $monthlyAggregates = DB::table('transactions')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense
            ")
            ->where('user_id', $user->id)
            ->where('status', 'posted')
            ->whereBetween('transaction_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->first();

        $monthlyIncome = Money::of((string) ($monthlyAggregates->total_income ?? '0.00'));
        $monthlyExpense = Money::of((string) ($monthlyAggregates->total_expense ?? '0.00'));
        $netCashFlow = $monthlyIncome->minus($monthlyExpense);

        // 4. Recent Transactions for the selected period (Latest 5 posted, eager loaded)
        $recentTransactions = $user->transactions()
            ->posted()
            ->whereBetween('transaction_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // 5. Account & Transaction existence flags
        $hasAccounts = $user->accounts()->exists();
        $hasTransactions = $user->transactions()->posted()->exists();

        // 6. Honest Placeholders for Phase 3 features (FR-008 & FR-010)
        $upcomingBillsPlaceholder = [
            'phase' => 3,
            'status' => 'pending_phase_3',
            'message' => 'Tagihan otomatis dan pengingat jatuh tempo aktif pada Fase 3.',
        ];

        $safeToSpendPlaceholder = [
            'phase' => 3,
            'is_basic_estimate' => true,
            'spendable_balance' => $spendableBalance,
            'message' => 'Estimasi berdasarkan total akun pengeluaran aktif.',
        ];

        return new DashboardMetrics(
            totalBalance: $totalBalance,
            monthlyIncome: $monthlyIncome,
            monthlyExpense: $monthlyExpense,
            netCashFlow: $netCashFlow,
            spendableBalance: $spendableBalance,
            recentTransactions: $recentTransactions,
            periodName: $periodName,
            periodKey: $periodKey,
            startDate: $startDate,
            endDate: $endDate,
            hasAccounts: $hasAccounts,
            hasTransactions: $hasTransactions,
            upcomingBillsPlaceholder: $upcomingBillsPlaceholder,
            safeToSpendPlaceholder: $safeToSpendPlaceholder,
        );
    }

    private function resolvePeriodDate(?string $month, string $timezone): CarbonImmutable
    {
        if ($month !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $month.'-01', $timezone);
            } catch (\Throwable) {
                // fallback to current time
            }
        }

        return CarbonImmutable::now($timezone);
    }
}
