<?php

declare(strict_types=1);

namespace App\Actions\Transactions;

use App\DTOs\TransactionData;
use App\Enums\AccountStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionPosted;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PostTransaction
{
    /**
     * Record a new income or expense transaction with idempotency and audit trail.
     */
    public function handle(User $user, TransactionData $data): Transaction
    {
        // Invariant 1: Amount must be strictly greater than 0
        if (! $data->amount->isPositive()) {
            throw new InvalidArgumentException('Nominal transaksi harus bernilai lebih dari 0.');
        }

        // Invariant 2: Transaction type must be income or expense in this action
        if ($data->type !== TransactionType::Income && $data->type !== TransactionType::Expense) {
            throw new InvalidArgumentException('Aksi ini hanya menangani transaksi pemasukan atau pengeluaran.');
        }

        // Invariant 3: Source account must belong to user and be active
        $sourceAccount = $user->accounts()->where('id', $data->sourceAccountId)->first();
        if ($sourceAccount === null) {
            throw new DomainException('Akun keuangan tidak ditemukan atau bukan milik pengguna.');
        }

        if ($sourceAccount->status !== AccountStatus::Active) {
            throw new DomainException('Transaksi tidak dapat dicatat pada akun yang telah diarsipkan.');
        }

        // Invariant 4: Category is required and must match transaction type
        if ($data->categoryId === null) {
            throw new InvalidArgumentException('Kategori transaksi wajib dipilih.');
        }

        $category = Category::forUser($user)->where('id', $data->categoryId)->first();
        if ($category === null) {
            throw new DomainException('Kategori transaksi tidak ditemukan.');
        }

        if (! $category->isActive()) {
            throw new DomainException('Kategori yang dipilih telah diarsipkan.');
        }

        if ($category->type->value !== $data->type->value) {
            throw new DomainException('Kategori tidak sesuai dengan jenis transaksi.');
        }

        // Invariant 5: Anti double submit / idempotency via client_request_id
        if ($data->clientRequestId !== null) {
            $existing = Transaction::where('user_id', $user->id)
                ->where('client_request_id', $data->clientRequestId)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($user, $sourceAccount, $category, $data) {
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'source_account_id' => $sourceAccount->id,
                    'destination_account_id' => null,
                    'category_id' => $category->id,
                    'type' => $data->type,
                    'amount' => $data->amount,
                    'transaction_date' => $data->transactionDate->toDateString(),
                    'description' => $data->description,
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
