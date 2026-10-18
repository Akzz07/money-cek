<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\DTOs\CategoryData;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateCategory
{
    /**
     * Create a user-defined category.
     */
    public function handle(User $user, CategoryData $data): Category
    {
        return DB::transaction(function () use ($user, $data) {
            return Category::create([
                'user_id' => $user->id,
                'name' => $data->name,
                'type' => $data->type,
                'icon' => $data->icon,
                'color' => $data->color,
                'is_system' => false,
                'status' => CategoryStatus::Active,
            ]);
        });
    }
}
