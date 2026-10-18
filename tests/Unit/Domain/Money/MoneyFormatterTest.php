<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Money;

use App\Domain\Money\Money;
use App\Domain\Money\MoneyFormatter;
use Tests\TestCase;

final class MoneyFormatterTest extends TestCase
{
    public function test_formats_standard_idr_without_decimals_by_default(): void
    {
        $m = Money::of('12450000.00');

        $formatted = MoneyFormatter::format($m);

        $this->assertEquals('Rp 12.450.000', $formatted);
    }

    public function test_formats_with_decimals_when_requested(): void
    {
        $m = Money::of('12450000.50');

        $formatted = MoneyFormatter::format($m, withDecimals: true);

        $this->assertEquals('Rp 12.450.000,50', $formatted);
    }

    public function test_formats_negative_amounts(): void
    {
        $m = Money::of('-35000.00');

        $formatted = MoneyFormatter::format($m);

        $this->assertEquals('-Rp 35.000', $formatted);
    }

    public function test_formats_zero(): void
    {
        $zero = Money::zero();

        $this->assertEquals('Rp 0', MoneyFormatter::format($zero));
        $this->assertEquals('Rp 0,00', MoneyFormatter::format($zero, withDecimals: true));
    }
}
