<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\DTOs\CategoryData;
use App\Models\Category;
use DomainException;
use Illuminate\Support\Facades\DB;

final class UpdateCategory
{
    /**
     * Update an existing user-defined category.
     */
    public function handle(Category $category, CategoryData $data): Category
    {
        if ($category->is_system) {
            throw new DomainException('Kategori sistem bawaan tidak dapat diubah.');
        }

        return DB::transaction(function () use ($category, $data) {
            $category->update([
                'name' => $data->name,
                'type' => $data->type,
                'icon' => $data->icon,
                'color' => $data->color,
            ]);

            return $category->fresh();
        });
    }
}
