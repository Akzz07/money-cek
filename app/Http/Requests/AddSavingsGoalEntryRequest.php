<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\Enums\SavingsGoalEntryDirection;
use App\Models\SavingsGoal;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddSavingsGoalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $goal = $this->route('savings_goal');

        return $goal instanceof SavingsGoal && $this->user()?->can('update', $goal);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::enum(SavingsGoalEntryDirection::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'entry_date' => ['required', 'date'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('user_id', $this->user()?->id)),
            ],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function entryDirection(): SavingsGoalEntryDirection
    {
        return SavingsGoalEntryDirection::from((string) $this->input('direction'));
    }

    public function entryAmount(): Money
    {
        return Money::of((string) $this->input('amount'));
    }

    public function entryDate(): CarbonImmutable
    {
        $timezone = $this->user()?->timezone ?? 'Asia/Jakarta';

        return CarbonImmutable::parse((string) $this->input('entry_date'), $timezone)->startOfDay();
    }

    public function accountId(): ?int
    {
        return $this->filled('account_id') ? (int) $this->input('account_id') : null;
    }

    public function notes(): ?string
    {
        return $this->filled('notes') ? (string) $this->input('notes') : null;
    }
}
