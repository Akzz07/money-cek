<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\DTOs\TransferData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTransferRequest extends FormRequest
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
            'source_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('status', 'active'),
            ],
            'destination_account_id' => [
                'required',
                'integer',
                'different:source_account_id',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('status', 'active'),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'client_request_id' => ['nullable', 'string', 'max:36'],
        ];
    }

    /**
     * Convert validated input to TransferData DTO.
     */
    public function toTransferData(): TransferData
    {
        return new TransferData(
            sourceAccountId: (int) $this->input('source_account_id'),
            destinationAccountId: (int) $this->input('destination_account_id'),
            amount: Money::of((string) $this->input('amount')),
            transactionDate: CarbonImmutable::parse((string) $this->input('transaction_date'), $this->user()?->timezone ?? 'Asia/Jakarta'),
            description: $this->filled('description') ? (string) $this->input('description') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            clientRequestId: $this->filled('client_request_id') ? (string) $this->input('client_request_id') : null,
        );
    }
}
