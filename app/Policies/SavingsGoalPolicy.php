<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;

final class SavingsGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SavingsGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SavingsGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function delete(User $user, SavingsGoal $goal): bool
    {
        return $user->id === $goal->user_id;
    }
}
