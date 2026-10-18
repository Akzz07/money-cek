<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\DTOs\BillData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'due_date' => ['required', 'date'],
            'category_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toBillData(): BillData
    {
        $timezone = $this->user()?->timezone ?? 'Asia/Jakarta';

        return new BillData(
            name: (string) $this->input('name'),
            amount: Money::of((string) $this->input('amount')),
            dueDate: CarbonImmutable::parse((string) $this->input('due_date'), $timezone)->startOfDay(),
            categoryId: $this->filled('category_id') ? (int) $this->input('category_id') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
