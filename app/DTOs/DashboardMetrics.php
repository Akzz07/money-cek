<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class DashboardMetrics
{
    /**
     * @param  Collection<int, Transaction>  $recentTransactions
     * @param  array<string, mixed>  $upcomingBillsPlaceholder
     * @param  array<string, mixed>  $safeToSpendPlaceholder
     */
    public function __construct(
        public Money $totalBalance,
        public Money $monthlyIncome,
        public Money $monthlyExpense,
        public Money $netCashFlow,
        public Money $spendableBalance,
        public Collection $recentTransactions,
        public string $periodName,
        public string $periodKey,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public bool $hasAccounts,
        public bool $hasTransactions,
        public array $upcomingBillsPlaceholder = [],
        public array $safeToSpendPlaceholder = [],
    ) {}
}
