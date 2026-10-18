<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Enums\CategoryStatus;
use App\Models\Category;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ArchiveCategory
{
    /**
     * Archive an existing user-defined category.
     */
    public function handle(Category $category): Category
    {
        if ($category->is_system) {
            throw new DomainException('Kategori sistem bawaan tidak dapat diarsipkan.');
        }

        return DB::transaction(function () use ($category) {
            $category->update([
                'status' => CategoryStatus::Archived,
            ]);

            return $category->fresh();
        });
    }
}
