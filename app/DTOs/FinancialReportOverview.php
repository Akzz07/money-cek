<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\Account;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class FinancialReportOverview
{
    /**
     * @param  Collection<int, MonthlyCashFlowSummary>  $monthlyTrends
     * @param  Collection<int, CategoryExpenseItem>  $categoryBreakdown
     * @param  Collection<int, array{account: Account, balance: Money}>  $accountsSummary
     * @param  array<string, Money>  $typeSummary
     */
    public function __construct(
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public string $periodKey,
        public string $periodLabel,
        public Money $totalIncome,
        public Money $totalExpense,
        public Money $netCashFlow,
        public float $savingsRate,
        public Collection $monthlyTrends,
        public Collection $categoryBreakdown,
        public Collection $accountsSummary,
        public array $typeSummary,
        public Money $totalNetWorth,
    ) {}
}
