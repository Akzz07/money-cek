<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Money\Money;
use App\DTOs\BudgetData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBudgetRequest extends FormRequest
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
            'category_id' => ['required', 'integer'],
            'period' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'limit_amount' => ['required', 'numeric', 'gt:0'],
            'warning_threshold' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function toBudgetData(): BudgetData
    {
        $timezone = $this->user()?->timezone ?? 'Asia/Jakarta';

        $periodRaw = (string) $this->input('period', '');
        if ($periodRaw !== '' && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodRaw) === 1) {
            $periodStart = CarbonImmutable::createFromFormat('Y-m-d', $periodRaw.'-01', $timezone)->startOfMonth();
        } else {
            $periodStart = CarbonImmutable::now($timezone)->startOfMonth();
        }

        return new BudgetData(
            categoryId: (int) $this->input('category_id'),
            periodStart: $periodStart,
            limitAmount: Money::of((string) $this->input('limit_amount')),
            warningThreshold: (int) $this->input('warning_threshold', 80),
        );
    }
}
