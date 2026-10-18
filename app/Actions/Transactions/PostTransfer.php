<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\DTOs\TransferData;
use App\Enums\AccountStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionPosted;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PostTransfer
{
    /**
     * Post an atomic transfer between two active accounts owned by the user.
     */
    public function handle(User $user, TransferData $data): Transaction
    {
        if (! $data->amount->isPositive()) {
            throw new InvalidArgumentException('Nominal transfer harus bernilai lebih dari 0.');
        }

        if ($data->sourceAccountId === $data->destinationAccountId) {
            throw new DomainException('Akun sumber dan akun tujuan transfer tidak boleh sama.');
        }

        $sourceAccount = $user->accounts()->where('id', $data->sourceAccountId)->first();
        if ($sourceAccount === null) {
            throw new DomainException('Akun sumber tidak ditemukan atau bukan milik pengguna.');
        }

        if ($sourceAccount->status !== AccountStatus::Active) {
            throw new DomainException('Akun sumber tidak aktif atau telah diarsipkan.');
        }

        $destinationAccount = $user->accounts()->where('id', $data->destinationAccountId)->first();
        if ($destinationAccount === null) {
            throw new DomainException('Akun tujuan tidak ditemukan atau bukan milik pengguna.');
        }

        if ($destinationAccount->status !== AccountStatus::Active) {
            throw new DomainException('Akun tujuan tidak aktif atau telah diarsipkan.');
        }

        if ($data->clientRequestId !== null) {
            $existing = Transaction::where('user_id', $user->id)
                ->where('client_request_id', $data->clientRequestId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($user, $sourceAccount, $destinationAccount, $data) {
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'source_account_id' => $sourceAccount->id,
                    'destination_account_id' => $destinationAccount->id,
                    'category_id' => null,
                    'type' => TransactionType::Transfer,
                    'amount' => $data->amount,
                    'transaction_date' => $data->transactionDate->toDateString(),
                    'description' => $data->description ?? "Transfer ke {$destinationAccount->name}",
                    'notes' => $data->notes,
                    'status' => TransactionStatus::Posted,
                    'client_request_id' => $data->clientRequestId,
                ]);

                TransactionPosted::dispatch($transaction, $user->id);

                return $transaction;
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($data->clientRequestId !== null) {
                $existing = Transaction::where('user_id', $user->id)
                    ->where('client_request_id', $data->clientRequestId)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            throw $e;
        }
    }
}
