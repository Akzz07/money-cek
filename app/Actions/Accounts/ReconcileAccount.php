<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\AdjustmentDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionPosted;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReconcileAccount
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
    ) {}

    /**
     * Reconcile account balance to match the user's real balance via an adjustment transaction.
     */
    public function handle(User $user, Account $account, Money $realBalance, string $reason): ?Transaction
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan rekonsiliasi wajib diisi.');
        }

        if ($account->user_id !== $user->id) {
            throw new DomainException('Akun keuangan bukan milik pengguna.');
        }

        if ($account->status !== AccountStatus::Active) {
            throw new DomainException('Tidak dapat melakukan rekonsiliasi pada akun yang telah diarsipkan.');
        }

        $currentBalance = $this->balanceService->getBalance($account);
        $diff = $realBalance->subtract($currentBalance);

        if ($diff->isZero()) {
            return null;
        }

        $isIncrease = $diff->isPositive();
        $adjustmentAmount = $diff->abs();

        return DB::transaction(function () use ($user, $account, $isIncrease, $adjustmentAmount, $currentBalance, $realBalance, $reason) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'source_account_id' => $account->id,
                'destination_account_id' => null,
                'category_id' => null,
                'type' => TransactionType::Adjustment,
                'amount' => $adjustmentAmount,
                'transaction_date' => now()->toDateString(),
                'description' => 'Penyesuaian Saldo (Rekonsiliasi)',
                'notes' => $reason,
                'status' => TransactionStatus::Posted,
                'adjustment_direction' => $isIncrease ? AdjustmentDirection::Increase : AdjustmentDirection::Decrease,
            ]);

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'account.reconciled',
                'entity_type' => 'Account',
                'entity_id' => $account->id,
                'metadata' => [
                    'previous_balance' => $currentBalance->toDecimalString(),
                    'reconciled_balance' => $realBalance->toDecimalString(),
                    'adjustment_amount' => $adjustmentAmount->toDecimalString(),
                    'direction' => $isIncrease ? 'increase' : 'decrease',
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);

            TransactionPosted::dispatch($transaction, $user->id);

            return $transaction;
        });
    }
}
