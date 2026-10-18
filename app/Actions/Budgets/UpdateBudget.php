<?php

declare(strict_types=1);

namespace App\Actions\Budgets;

use App\Domain\Money\Money;
use App\Models\Budget;
use InvalidArgumentException;

final class UpdateBudget
{
    /**
     * Update budget limit amount and warning threshold.
     */
    public function handle(Budget $budget, Money $limitAmount, int $warningThreshold = 80): Budget
    {
        if (! $limitAmount->isPositive()) {
            throw new InvalidArgumentException('Batas anggaran harus bernilai lebih dari 0.');
        }

        if ($warningThreshold < 1 || $warningThreshold > 100) {
            throw new InvalidArgumentException('Ambang peringatan harus berada antara 1% dan 100%.');
        }

        $budget->update([
            'limit_amount' => $limitAmount,
            'warning_threshold' => $warningThreshold,
        ]);

        return $budget;
    }
}
