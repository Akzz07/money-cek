<?php

declare(strict_types=1);

namespace App\Actions\SavingsGoals;

use App\DTOs\SavingsGoalData;
use App\Enums\AccountStatus;
use App\Models\SavingsGoal;
use App\Models\User;
use DomainException;
use InvalidArgumentException;

final class CreateSavingsGoal
{
    /**
     * Create a new savings goal for the user.
     */
    public function handle(User $user, SavingsGoalData $data): SavingsGoal
    {
        if (! $data->targetAmount->isPositive()) {
            throw new InvalidArgumentException('Nominal target tabungan harus bernilai lebih dari 0.');
        }

        if ($data->targetAccountId !== null) {
            $account = $user->accounts()->where('id', $data->targetAccountId)->first();
            if ($account === null) {
                throw new DomainException('Akun tabungan yang dipilih tidak ditemukan.');
            }

            if ($account->status !== AccountStatus::Active) {
                throw new DomainException('Akun tabungan yang dipilih tidak aktif.');
            }
        }

        return SavingsGoal::create([
            'user_id' => $user->id,
            'name' => $data->name,
            'target_amount' => $data->targetAmount,
            'target_date' => $data->targetDate?->toDateString(),
            'status' => $data->status,
            'color' => $data->color,
            'icon' => $data->icon,
            'target_account_id' => $data->targetAccountId,
            'notes' => $data->notes,
        ]);
    }
}
