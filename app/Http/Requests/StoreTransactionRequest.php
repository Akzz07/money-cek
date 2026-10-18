<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\DTOs\TransactionData;
use App\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'source_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('status', 'active'),
            ],
            'category_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'client_request_id' => ['nullable', 'string', 'max:36'],
        ];
    }

    /**
     * Convert validated input to TransactionData DTO.
     */
    public function toTransactionData(): TransactionData
    {
        return new TransactionData(
            type: TransactionType::from((string) $this->input('type')),
            sourceAccountId: (int) $this->input('source_account_id'),
            amount: Money::of((string) $this->input('amount')),
            transactionDate: CarbonImmutable::parse((string) $this->input('transaction_date'), $this->user()?->timezone ?? 'Asia/Jakarta'),
            destinationAccountId: null,
            categoryId: (int) $this->input('category_id'),
            description: $this->filled('description') ? (string) $this->input('description') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            clientRequestId: $this->filled('client_request_id') ? (string) $this->input('client_request_id') : null,
        );
    }
}
