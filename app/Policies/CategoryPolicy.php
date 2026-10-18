<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

final class CategoryPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return $category->is_system || $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the category.
     * System categories can never be updated.
     */
    public function update(User $user, Category $category): bool
    {
        return ! $category->is_system && $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can delete/archive the category.
     * System categories can never be deleted or archived.
     */
    public function delete(User $user, Category $category): bool
    {
        return ! $category->is_system && $user->id === $category->user_id;
    }
}
