<?php

declare(strict_types=1);

namespace App\Actions\SavingsGoals;

use App\Models\SavingsGoal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DeleteSavingsGoal
{
    /**
     * Delete a savings goal and its ledger entries.
     */
    public function handle(User $user, SavingsGoal $goal): void
    {
        if ($goal->user_id !== $user->id) {
            throw new DomainException('Target tabungan tidak ditemukan atau bukan milik pengguna.');
        }

        DB::transaction(function () use ($goal) {
            $goal->delete();
        });
    }
}
