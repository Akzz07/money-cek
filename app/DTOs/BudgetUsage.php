<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\Budget;

final readonly class BudgetUsage
{
    public Money $limit;

    public Money $spent;

    public Money $remaining;

    public float $percentage;

    public bool $isExceeded;

    public bool $isWarning;

    public string $statusLabel;

    public string $colorClass;

    public function __construct(
        public Budget $budget,
        Money $spent,
    ) {
        $this->limit = $budget->limit_amount;
        $this->spent = $spent;
        $this->remaining = $this->limit->minus($spent);

        $limitValue = (float) $this->limit->amount();
        $spentValue = (float) $spent->amount();

        if ($limitValue > 0) {
            $this->percentage = round(($spentValue / $limitValue) * 100, 1);
        } else {
            $this->percentage = 0.0;
        }

        $this->isExceeded = $this->remaining->isNegative();
        $this->isWarning = ! $this->isExceeded && $this->percentage >= (float) $budget->warning_threshold;

        if ($this->isExceeded) {
            $this->statusLabel = 'Melebihi Batas';
            $this->colorClass = 'rose';
        } elseif ($this->isWarning) {
            $this->statusLabel = "Mendekati Batas ({$this->percentage}%)";
            $this->colorClass = 'amber';
        } else {
            $this->statusLabel = 'Aman';
            $this->colorClass = 'emerald';
        }
    }
}
