<?php

declare(strict_types=1);

namespace App\Actions\Bills;

use App\Actions\Transactions\VoidTransaction;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CancelBillPayment
{
    public function __construct(
        private readonly VoidTransaction $voidTransaction,
    ) {}

    /**
     * Cancel a paid bill, voiding the associated expense transaction and restoring account balance.
     */
    public function handle(User $user, Bill $bill): Bill
    {
        if ($bill->user_id !== $user->id) {
            throw new DomainException('Tagihan tidak ditemukan atau bukan milik pengguna.');
        }

        if ($bill->status !== BillStatus::Paid) {
            throw new DomainException('Hanya tagihan berstatus lunas yang dapat dibatalkan pembayarannya.');
        }

        return DB::transaction(function () use ($user, $bill) {
            $transaction = $bill->paidTransaction;

            if ($transaction !== null && ! $transaction->isVoided()) {
                $this->voidTransaction->handle($user, $transaction, "Pembatalan pembayaran tagihan: {$bill->name}");
            }

            $bill->update([
                'status' => BillStatus::Upcoming,
                'paid_transaction_id' => null,
            ]);

            return $bill;
        });
    }
}
