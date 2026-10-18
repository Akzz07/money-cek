<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\SavingsGoalStatus;
use App\Models\SavingsGoal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
final class SavingsGoalFactory extends Factory
{
    protected $model = SavingsGoal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Dana Darurat', 'Liburan Akhir Tahun', 'Beli Laptop Baru', 'DP Rumah', 'Modal Usaha']),
            'target_amount' => Money::of('10000000.00'),
            'target_date' => CarbonImmutable::now()->addMonths(6)->toDateString(),
            'status' => SavingsGoalStatus::Active,
            'color' => '#2563EB',
            'icon' => 'target',
            'target_account_id' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => SavingsGoalStatus::Completed,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => [
            'status' => SavingsGoalStatus::Paused,
        ]);
    }
}
