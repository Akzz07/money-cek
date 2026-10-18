<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\BillStatus;
use App\Enums\CategoryType;
use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
final class BillFactory extends Factory
{
    protected $model = Bill::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Listrik PLN', 'Wifi Internet', 'Air PDAM', 'BPJS Kesehatan', 'Langganan Netflix']),
            'amount' => Money::of('350000.00'),
            'due_date' => CarbonImmutable::now()->addDays(5)->toDateString(),
            'category_id' => Category::factory()->state(['type' => CategoryType::Expense]),
            'paid_transaction_id' => null,
            'status' => BillStatus::Upcoming,
            'notes' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => CarbonImmutable::now()->subDays(3)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => BillStatus::Paid,
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn () => [
            'status' => BillStatus::Skipped,
        ]);
    }
}
