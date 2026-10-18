<?php

declare(strict_types=1);

namespace App\Actions\Budgets;

use App\Enums\BudgetStatus;
use App\Models\Budget;

final class ArchiveBudget
{
    /**
     * Archive an active budget.
     */
    public function handle(Budget $budget): Budget
    {
        $budget->update([
            'status' => BudgetStatus::Archived,
        ]);

        return $budget;
    }
}
