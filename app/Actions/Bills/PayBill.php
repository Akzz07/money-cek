<?php

declare(strict_types=1);

namespace App\Actions\Bills;

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionPosted;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class PayBill
{
    /**
     * Mark a bill as paid by atomically creating an expense transaction on the selected account.
     */
    public function handle(User $user, Bill $bill, Account $sourceAccount, ?string $notes = null): Bill
    {
        if ($bill->user_id !== $user->id) {
            throw new DomainException('Tagihan tidak ditemukan atau bukan milik pengguna.');
        }

        if ($bill->status === BillStatus::Paid || $bill->paid_transaction_id !== null) {
            throw new DomainException('Tagihan ini sudah berstatus lunas dan tidak dapat dibayar kembali.');
        }

        if ($sourceAccount->user_id !== $user->id) {
            throw new DomainException('Akun pembayaran tidak ditemukan atau bukan milik pengguna.');
        }

        if ($sourceAccount->status !== AccountStatus::Active) {
            throw new DomainException('Pembayaran tagihan tidak dapat menggunakan akun yang tidak aktif atau telah diarsipkan.');
        }

        return DB::transaction(function () use ($user, $bill, $sourceAccount, $notes) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'source_account_id' => $sourceAccount->id,
                'destination_account_id' => null,
                'category_id' => $bill->category_id,
                'type' => TransactionType::Expense,
                'amount' => $bill->amount,
                'transaction_date' => CarbonImmutable::now()->toDateString(),
                'description' => "Pembayaran Tagihan: {$bill->name}",
                'notes' => $notes ?? $bill->notes,
                'status' => TransactionStatus::Posted,
            ]);

            TransactionPosted::dispatch($transaction, $user->id);

            $bill->update([
                'status' => BillStatus::Paid,
                'paid_transaction_id' => $transaction->id,
            ]);

            return $bill;
        });
    }
}
