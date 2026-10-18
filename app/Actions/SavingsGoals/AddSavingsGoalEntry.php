<?php

declare(strict_types=1);

namespace App\Actions\SavingsGoals;

use App\Actions\Transactions\PostTransfer;
use App\Domain\Money\Money;
use App\DTOs\TransferData;
use App\Enums\SavingsGoalEntryDirection;
use App\Enums\SavingsGoalStatus;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AddSavingsGoalEntry
{
    public function __construct(
        private readonly PostTransfer $postTransfer,
    ) {}

    /**
     * Record a contribution or withdrawal into a savings goal.
     */
    public function handle(
        User $user,
        SavingsGoal $goal,
        SavingsGoalEntryDirection $direction,
        Money $amount,
        CarbonImmutable $entryDate,
        ?string $notes = null,
        ?int $accountId = null,
    ): SavingsGoalEntry {
        if ($goal->user_id !== $user->id) {
            throw new DomainException('Target tabungan tidak ditemukan atau bukan milik pengguna.');
        }

        if (! $amount->isPositive()) {
            throw new InvalidArgumentException('Nominal mutasi tabungan harus lebih dari 0.');
        }

        return DB::transaction(function () use ($user, $goal, $direction, $amount, $entryDate, $notes, $accountId) {
            $linkedTransactionId = null;

            // Optional atomic transfer between physical accounts
            if ($accountId !== null && $goal->target_account_id !== null) {
                if ($direction === SavingsGoalEntryDirection::Contribution && $accountId !== $goal->target_account_id) {
                    $transfer = $this->postTransfer->handle($user, new TransferData(
                        sourceAccountId: $accountId,
                        destinationAccountId: $goal->target_account_id,
                        amount: $amount,
                        transactionDate: $entryDate,
                        description: "Setoran Tabungan: {$goal->name}",
                        notes: $notes,
                    ));
                    $linkedTransactionId = $transfer->id;
                } elseif ($direction === SavingsGoalEntryDirection::Withdrawal && $accountId !== $goal->target_account_id) {
                    $transfer = $this->postTransfer->handle($user, new TransferData(
                        sourceAccountId: $goal->target_account_id,
                        destinationAccountId: $accountId,
                        amount: $amount,
                        transactionDate: $entryDate,
                        description: "Penarikan Tabungan: {$goal->name}",
                        notes: $notes,
                    ));
                    $linkedTransactionId = $transfer->id;
                }
            }

            $entry = SavingsGoalEntry::create([
                'savings_goal_id' => $goal->id,
                'user_id' => $user->id,
                'direction' => $direction,
                'amount' => $amount,
                'entry_date' => $entryDate->toDateString(),
                'notes' => $notes,
                'linked_transaction_id' => $linkedTransactionId,
            ]);

            // Re-evaluate goal status
            $currentNet = $goal->unsetRelation('entries')->currentAmount();
            if ($currentNet->isGreaterThanOrEqual($goal->target_amount) && $goal->status === SavingsGoalStatus::Active) {
                $goal->update(['status' => SavingsGoalStatus::Completed]);
                $goal->status = SavingsGoalStatus::Completed;
            } elseif ($direction === SavingsGoalEntryDirection::Withdrawal && ! $currentNet->isGreaterThanOrEqual($goal->target_amount) && $goal->status === SavingsGoalStatus::Completed) {
                $goal->update(['status' => SavingsGoalStatus::Active]);
                $goal->status = SavingsGoalStatus::Active;
            }

            return $entry;
        });
    }
}
