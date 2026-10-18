<?php

declare(strict_types=1);

namespace App\Actions\Budgets;

use App\DTOs\BudgetData;
use App\Enums\BudgetStatus;
use App\Enums\CategoryType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use DomainException;
use InvalidArgumentException;

final class CreateBudget
{
    /**
     * Create a new monthly budget for a specific category.
     */
    public function handle(User $user, BudgetData $data): Budget
    {
        if (! $data->limitAmount->isPositive()) {
            throw new InvalidArgumentException('Batas anggaran harus bernilai lebih dari 0.');
        }

        if ($data->warningThreshold < 1 || $data->warningThreshold > 100) {
            throw new InvalidArgumentException('Ambang peringatan harus berada antara 1% dan 100%.');
        }

        $category = Category::forUser($user)->where('id', $data->categoryId)->first();
        if ($category === null) {
            throw new DomainException('Kategori tidak ditemukan atau bukan milik pengguna.');
        }

        if (! $category->isActive()) {
            throw new DomainException('Kategori yang dipilih telah diarsipkan.');
        }

        if ($category->type !== CategoryType::Expense) {
            throw new DomainException('Anggaran hanya dapat dibuat untuk kategori bertipe pengeluaran.');
        }

        $periodStart = $data->periodStart->startOfMonth()->startOfDay();
        $periodEnd = $periodStart->addMonthNoOverflow();

        // Check if active budget already exists for this category and month
        $exists = Budget::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->whereDate('period_start', $periodStart->toDateString())
            ->where('status', BudgetStatus::Active)
            ->exists();

        if ($exists) {
            throw new DomainException('Anggaran untuk kategori ini pada periode tersebut sudah ada.');
        }

        return Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'limit_amount' => $data->limitAmount,
            'warning_threshold' => $data->warningThreshold,
            'status' => BudgetStatus::Active,
        ]);
    }
}
