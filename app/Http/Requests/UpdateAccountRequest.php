<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\AccountData;
use App\Enums\AccountType;
use App\Models\Account;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account instanceof Account && $this->user()?->can('update', $account);
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
            'include_in_total_assets' => ['boolean'],
            'include_in_safe_to_spend' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Convert validated input to AccountData DTO preserving original opening balance and date.
     */
    public function toAccountData(Account $account): AccountData
    {
        return new AccountData(
            name: (string) $this->input('name'),
            type: AccountType::from((string) $this->input('type')),
            accountNumber: $this->filled('account_number') ? (string) $this->input('account_number') : null,
            institutionName: $this->filled('institution_name') ? (string) $this->input('institution_name') : null,
            openingBalance: $account->opening_balance,
            openingDate: CarbonImmutable::parse($account->opening_date),
            includeInTotalAssets: (bool) $this->boolean('include_in_total_assets', $account->include_in_total_assets),
            includeInSafeToSpend: (bool) $this->boolean('include_in_safe_to_spend', $account->include_in_safe_to_spend),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
