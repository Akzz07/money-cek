<?php

declare(strict_types=1);

namespace App\Actions\Bills;

use App\DTOs\BillData;
use App\Enums\BillStatus;
use App\Enums\CategoryType;
use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use DomainException;
use InvalidArgumentException;

final class CreateBill
{
    /**
     * Create a new upcoming bill.
     */
    public function handle(User $user, BillData $data): Bill
    {
        if (! $data->amount->isPositive()) {
            throw new InvalidArgumentException('Nominal tagihan harus bernilai lebih dari 0.');
        }

        if ($data->categoryId !== null) {
            $category = Category::forUser($user)->where('id', $data->categoryId)->first();
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

        return Bill::create([
            'user_id' => $user->id,
            'name' => trim($data->name),
            'amount' => $data->amount,
            'due_date' => $data->dueDate->toDateString(),
            'category_id' => $data->categoryId,
            'status' => BillStatus::Upcoming,
            'notes' => $data->notes,
        ]);
    }
}
