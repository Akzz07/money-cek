<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'type' => CategoryType::Expense,
            'icon' => 'tag',
            'color' => 'blue',
            'is_system' => false,
            'status' => CategoryStatus::Active,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CategoryType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CategoryType::Expense,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_system' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CategoryStatus::Archived,
        ]);
    }
}
