<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\SavingsGoalEntryDirection;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoalEntry>
 */
final class SavingsGoalEntryFactory extends Factory
{
    protected $model = SavingsGoalEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'savings_goal_id' => SavingsGoal::factory(),
            'user_id' => User::factory(),
            'direction' => SavingsGoalEntryDirection::Contribution,
            'amount' => Money::of('500000.00'),
            'entry_date' => CarbonImmutable::now()->toDateString(),
            'notes' => null,
            'linked_transaction_id' => null,
        ];
    }

    public function withdrawal(): static
    {
        return $this->state(fn () => [
            'direction' => SavingsGoalEntryDirection::Withdrawal,
        ]);
    }
}
