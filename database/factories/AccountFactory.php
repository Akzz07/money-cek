<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
final class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AccountType::cases());

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['BCA Utama', 'Dompet Tunai', 'GoPay', 'Bibit Reksadana', 'Mandiri Tabungan']),
            'type' => $type,
            'account_number' => fake()->numerify('##########'),
            'institution_name' => fake()->randomElement(['Bank Central Asia', 'Bank Mandiri', 'GoTo', 'PT Bibit Tumbuh Bersama', null]),
            'opening_balance' => '1000000.00',
            'opening_date' => now()->toDateString(),
            'include_in_total_assets' => true,
            'include_in_safe_to_spend' => $type->defaultIncludeInSafeToSpend(),
            'status' => AccountStatus::Active,
            'notes' => fake()->sentence(),
        ];
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'name' => 'Bank BCA',
            'type' => AccountType::Bank,
            'institution_name' => 'Bank Central Asia',
            'include_in_safe_to_spend' => true,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'name' => 'Dompet Tunai',
            'type' => AccountType::Cash,
            'institution_name' => null,
            'account_number' => null,
            'include_in_safe_to_spend' => true,
        ]);
    }

    public function eWallet(): static
    {
        return $this->state(fn () => [
            'name' => 'GoPay',
            'type' => AccountType::EWallet,
            'institution_name' => 'GoTo Financial',
            'include_in_safe_to_spend' => true,
        ]);
    }

    public function investment(): static
    {
        return $this->state(fn () => [
            'name' => 'Portofolio Saham',
            'type' => AccountType::Investment,
            'institution_name' => 'Stockbit Sekuritas',
            'include_in_safe_to_spend' => false,
            'include_in_total_assets' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => AccountStatus::Archived,
        ]);
    }
}
