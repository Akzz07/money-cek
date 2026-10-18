<?php

declare(strict_types=1);

namespace App\Casts;

use App\Domain\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Eloquent cast for mapping DECIMAL(19,2) columns to the Money Value Object.
 *
 * @implements CastsAttributes<Money, Money|string|int>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value from the database to a Money instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::of((string) $value);
    }

    /**
     * Prepare the given value for storage in the database as DECIMAL(19,2).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->toDecimalString();
        }

        return Money::of($value)->toDecimalString();
    }
}
