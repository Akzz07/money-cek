<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\DTOs\AccountData;
use App\Enums\AccountType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'account_number' => ['nullable', 'string', 'max:50'],
            'institution_name' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_date' => ['required', 'date', 'before_or_equal:today'],
            'include_in_total_assets' => ['boolean'],
            'include_in_safe_to_spend' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Convert validated input to AccountData DTO.
     */
    public function toAccountData(): AccountData
    {
        $type = AccountType::from((string) $this->input('type'));

        return new AccountData(
            name: (string) $this->input('name'),
            type: $type,
            accountNumber: $this->filled('account_number') ? (string) $this->input('account_number') : null,
            institutionName: $this->filled('institution_name') ? (string) $this->input('institution_name') : null,
            openingBalance: Money::of((string) $this->input('opening_balance')),
            openingDate: CarbonImmutable::parse((string) $this->input('opening_date'), $this->user()->timezone ?? 'Asia/Jakarta'),
            includeInTotalAssets: (bool) $this->boolean('include_in_total_assets', true),
            includeInSafeToSpend: (bool) $this->boolean('include_in_safe_to_spend', $type->defaultIncludeInSafeToSpend()),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
