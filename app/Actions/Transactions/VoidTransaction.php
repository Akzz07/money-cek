<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class VoidTransaction
{
    /**
     * Void an existing posted transaction with transparent audit trail.
     */
    public function handle(User $user, Transaction $transaction, string $reason): Transaction
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan pembatalan (void) transaksi wajib diisi.');
        }

        if ($transaction->user_id !== $user->id) {
            throw new DomainException('Transaksi ini bukan milik pengguna.');
        }

        // Idempotent: If already voided, return immediately without re-voiding
        if ($transaction->status === TransactionStatus::Voided) {
            return $transaction;
        }

        return DB::transaction(function () use ($user, $transaction, $reason) {
            $transaction->update([
                'status' => TransactionStatus::Voided,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'transaction.voided',
                'entity_type' => 'Transaction',
                'entity_id' => $transaction->id,
                'request_id' => $transaction->client_request_id,
                'metadata' => [
                    'amount' => $transaction->amount->toDecimalString(),
                    'void_reason' => $reason,
                    'voided_at' => now()->toIso8601String(),
                ],
                'created_at' => now(),
            ]);

            return $transaction->fresh();
        });
    }
}
