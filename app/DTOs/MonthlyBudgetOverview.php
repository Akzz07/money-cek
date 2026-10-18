<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class MonthlyBudgetOverview
{
    /**
     * @param  Collection<int, BudgetUsage>  $budgetsUsage
     */
    public function __construct(
        public Money $totalLimit,
        public Money $totalSpent,
        public Money $totalRemaining,
        public float $overallPercentage,
        public Collection $budgetsUsage,
        public string $periodName,
        public string $periodKey,
        public CarbonImmutable $startDate,
    ) {}
}
