<?php

declare(strict_types=1);

namespace App\Actions\Bills;

use App\Enums\BillStatus;
use App\Models\Bill;
use DomainException;

final class SkipBill
{
    /**
     * Skip an upcoming bill without generating an expense transaction.
     */
    public function handle(Bill $bill): Bill
    {
        if ($bill->status === BillStatus::Paid) {
            throw new DomainException('Tagihan yang sudah lunas tidak dapat dilewati.');
        }

        $bill->update([
            'status' => BillStatus::Skipped,
        ]);

        return $bill;
    }
}
