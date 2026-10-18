<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

final class ReconcileAccountRequest extends FormRequest
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
            'real_balance' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function realBalance(): Money
    {
        return Money::of((string) $this->input('real_balance'));
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}
