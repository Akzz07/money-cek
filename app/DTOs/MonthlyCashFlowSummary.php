<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;

final readonly class MonthlyCashFlowSummary
{
    public function __construct(
        public string $periodKey,
        public string $periodName,
        public Money $income,
        public Money $expense,
        public Money $net,
        public float $incomeBarHeightPercentage,
        public float $expenseBarHeightPercentage,
    ) {}
}
