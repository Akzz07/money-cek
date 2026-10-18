<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Money\Money;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
final class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_account_id' => Account::factory(),
            'destination_account_id' => null,
            'category_id' => Category::factory()->expense(),
            'type' => TransactionType::Expense,
            'amount' => Money::of('50000.00'),
            'transaction_date' => now()->toDateString(),
            'description' => fake()->sentence(3),
            'notes' => null,
            'status' => TransactionStatus::Posted,
            'adjustment_direction' => null,
            'client_request_id' => (string) Str::uuid(),
            'voided_at' => null,
            'void_reason' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
            'category_id' => Category::factory()->income(),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
            'category_id' => Category::factory()->expense(),
        ]);
    }

    public function transfer(Account $destinationAccount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Transfer,
            'destination_account_id' => $destinationAccount->id,
            'category_id' => null,
        ]);
    }

    public function voided(string $reason = 'Salah input nominal'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionStatus::Voided,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }
}
