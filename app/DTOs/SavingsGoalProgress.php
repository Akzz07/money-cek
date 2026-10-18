<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\SavingsGoal;

final readonly class SavingsGoalProgress
{
    public function __construct(
        public SavingsGoal $goal,
        public Money $targetAmount,
        public Money $currentAmount,
        public Money $remainingAmount,
        public float $percentage,
        public bool $isAchieved,
        public ?Money $surplusAmount = null,
    ) {}
}
