<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBudgetRequest extends FormRequest
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
            'limit_amount' => ['required', 'numeric', 'gt:0'],
            'warning_threshold' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function limitAmount(): Money
    {
        return Money::of((string) $this->input('limit_amount'));
    }

    public function warningThreshold(): int
    {
        return (int) $this->input('warning_threshold', 80);
    }
}
