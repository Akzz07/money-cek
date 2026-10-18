<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

final class TransactionPolicy
{
    /**
     * Determine whether the user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id;
    }

    /**
     * Determine whether the user can create transactions.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can void the transaction.
     */
    public function void(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id && $transaction->isPosted();
    }
}
