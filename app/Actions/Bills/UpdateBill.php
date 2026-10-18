<?php

declare(strict_types=1);

namespace App\Actions\Bills;

use App\DTOs\BillData;
use App\Enums\BillStatus;
use App\Enums\CategoryType;
use App\Models\Bill;
use App\Models\Category;
use DomainException;
use InvalidArgumentException;

final class UpdateBill
{
    /**
     * Update bill details.
     */
    public function handle(Bill $bill, BillData $data): Bill
    {
        if ($bill->status === BillStatus::Paid) {
            throw new DomainException('Tagihan yang sudah lunas tidak dapat diubah. Batalkan pembayaran terlebih dahulu.');
        }

        if (! $data->amount->isPositive()) {
            throw new InvalidArgumentException('Nominal tagihan harus bernilai lebih dari 0.');
        }

        if ($data->categoryId !== null) {
            $category = Category::forUser($bill->user)->where('id', $data->categoryId)->first();
            if ($category === null) {
                throw new DomainException('Kategori tidak ditemukan atau bukan milik pengguna.');
            }

            if (! $category->isActive()) {
                throw new DomainException('Kategori yang dipilih telah diarsipkan.');
            }

            if ($category->type !== CategoryType::Expense) {
                throw new DomainException('Tagihan hanya dapat dikaitkan dengan kategori pengeluaran.');
            }
        }

        $bill->update([
            'name' => trim($data->name),
            'amount' => $data->amount,
            'due_date' => $data->dueDate->toDateString(),
            'category_id' => $data->categoryId,
            'notes' => $data->notes,
        ]);

        return $bill;
    }
}
