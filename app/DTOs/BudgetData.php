<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use Carbon\CarbonImmutable;

final readonly class BudgetData
{
    public function __construct(
        public int $categoryId,
        public CarbonImmutable $periodStart,
        public Money $limitAmount,
        public int $warningThreshold = 80,
    ) {}
}
