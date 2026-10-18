<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\BudgetStatus;
use App\Enums\CategoryType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
final class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::now()->startOfMonth();
        $end = $start->addMonthNoOverflow();

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => CategoryType::Expense]),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'limit_amount' => Money::of('1000000.00'),
            'warning_threshold' => 80,
            'status' => BudgetStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => BudgetStatus::Archived,
        ]);
    }
}
