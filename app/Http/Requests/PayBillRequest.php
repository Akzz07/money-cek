<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PayBillRequest extends FormRequest
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
            'source_account_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function sourceAccountId(): int
    {
        return (int) $this->input('source_account_id');
    }

    public function notes(): ?string
    {
        return $this->filled('notes') ? (string) $this->input('notes') : null;
    }
}
