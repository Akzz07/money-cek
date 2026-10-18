<?php

declare(strict_types=1);

namespace App\Domain\Money;

use NumberFormatter;

final class MoneyFormatter
{
    private static ?NumberFormatter $formatter = null;

    /**
     * Format a Money instance into IDR currency representation.
     * Example: "Rp 12.450.000" or with decimals "Rp 12.450.000,00"
     */
    public static function format(Money $money, bool $withDecimals = false): string
    {
        $isNegative = $money->isNegative();
        $abs = $money->abs();
        $decimalStr = $abs->toDecimalString();

        [$integerPart, $fractionPart] = explode('.', $decimalStr);

        // Group thousands with '.' for Indonesian format
        $formattedInteger = number_format((float) $integerPart, 0, ',', '.');

        if ($withDecimals) {
            $result = "Rp {$formattedInteger},{$fractionPart}";
        } else {
            $result = "Rp {$formattedInteger}";
        }

        return $isNegative ? "-{$result}" : $result;
    }
}
