<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

final class VoidTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction && $this->user()?->can('void', $transaction);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }
}
