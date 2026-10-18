<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use Illuminate\Support\Collection;

final readonly class SavingsGoalsOverview
{
    /**
     * @param  Collection<int, SavingsGoalProgress>  $goals
     */
    public function __construct(
        public Money $totalTarget,
        public Money $totalSaved,
        public Money $totalRemaining,
        public float $overallPercentage,
        public int $activeGoalsCount,
        public int $completedGoalsCount,
        public Collection $goals,
    ) {}
}
