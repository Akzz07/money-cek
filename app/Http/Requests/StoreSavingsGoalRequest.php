<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\DTOs\SavingsGoalData;
use App\Enums\SavingsGoalStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSavingsGoalRequest extends FormRequest
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
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'target_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(SavingsGoalStatus::class)],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:50'],
            'target_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('user_id', $this->user()?->id)),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toSavingsGoalData(): SavingsGoalData
    {
        $timezone = $this->user()?->timezone ?? 'Asia/Jakarta';

        return new SavingsGoalData(
            name: (string) $this->input('name'),
            targetAmount: Money::of((string) $this->input('target_amount')),
            targetDate: $this->filled('target_date')
                ? CarbonImmutable::parse((string) $this->input('target_date'), $timezone)->startOfDay()
                : null,
            status: $this->filled('status')
                ? SavingsGoalStatus::from((string) $this->input('status'))
                : SavingsGoalStatus::Active,
            color: (string) ($this->input('color') ?? '#2563EB'),
            icon: (string) ($this->input('icon') ?? 'target'),
            targetAccountId: $this->filled('target_account_id') ? (int) $this->input('target_account_id') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
