<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Money;

use App\Domain\Money\Money;
use InvalidArgumentException;
use Tests\TestCase;

final class MoneyTest extends TestCase
{
    public function test_can_create_from_int_and_string(): void
    {
        $m1 = Money::of(100000);
        $m2 = Money::of('100000.50');
        $m3 = Money::of('Rp 12.450.000');
        $m4 = Money::of('12.450.000,50');

        $this->assertEquals('100000.00', $m1->toDecimalString());
        $this->assertEquals('100000.50', $m2->toDecimalString());
        $this->assertEquals('12450000.00', $m3->toDecimalString());
        $this->assertEquals('12450000.50', $m4->toDecimalString());
    }

    public function test_throws_exception_on_invalid_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of('invalid-amount-xyz');
    }

    public function test_zero_instance(): void
    {
        $zero = Money::zero();

        $this->assertTrue($zero->isZero());
        $this->assertEquals('0.00', $zero->toDecimalString());
    }

    public function test_immutable_arithmetic_addition_and_subtraction(): void
    {
        $a = Money::of('10000.00');
        $b = Money::of('2500.50');

        $sum = $a->add($b);
        $diff = $a->subtract($b);

        // Original objects must remain unchanged
        $this->assertEquals('10000.00', $a->toDecimalString());
        $this->assertEquals('2500.50', $b->toDecimalString());

        $this->assertEquals('12500.50', $sum->toDecimalString());
        $this->assertEquals('7499.50', $diff->toDecimalString());
    }

    public function test_multiplication_and_division(): void
    {
        $amount = Money::of('100.00');

        $product = $amount->multiply(3);
        $quotient = $amount->divide(3); // 100 / 3 = 33.3333... -> 33.33

        $this->assertEquals('300.00', $product->toDecimalString());
        $this->assertEquals('33.33', $quotient->toDecimalString());
    }

    public function test_division_by_zero_throws_exception(): void
    {
        $amount = Money::of('100.00');

        $this->expectException(InvalidArgumentException::class);
        $amount->divide(0);
    }

    public function test_rounding_half_up_behavior(): void
    {
        $m1 = Money::of('10.005'); // 10.005 -> 10.01 (half up)
        $m2 = Money::of('10.004'); // 10.004 -> 10.00

        $this->assertEquals('10.01', $m1->toDecimalString());
        $this->assertEquals('10.00', $m2->toDecimalString());
    }

    public function test_comparison_methods(): void
    {
        $small = Money::of('5000.00');
        $large = Money::of('10000.00');
        $same = Money::of('5000.00');

        $this->assertTrue($small->lessThan($large));
        $this->assertTrue($small->lessThanOrEqual($same));
        $this->assertTrue($large->greaterThan($small));
        $this->assertTrue($large->greaterThanOrEqual($same));
        $this->assertTrue($small->equals($same));
        $this->assertFalse($small->equals($large));
    }

    public function test_predicate_and_sign_methods(): void
    {
        $pos = Money::of('1000.00');
        $neg = Money::of('-1000.00');
        $zero = Money::zero();

        $this->assertTrue($pos->isPositive());
        $this->assertFalse($pos->isNegative());
        $this->assertFalse($pos->isZero());

        $this->assertTrue($neg->isNegative());
        $this->assertFalse($neg->isPositive());

        $this->assertTrue($zero->isZero());

        $this->assertEquals('1000.00', $neg->abs()->toDecimalString());
        $this->assertEquals('-1000.00', $pos->negated()->toDecimalString());
    }

    public function test_high_precision_nineteen_digit_numbers(): void
    {
        // 17 integer digits + 2 decimal digits = 19 digits (max DECIMAL(19,2))
        $huge = Money::of('99999999999999999.99');
        $small = Money::of('0.01');

        $this->assertEquals('99999999999999999.99', $huge->toDecimalString());
        $sub = $huge->subtract($small);
        $this->assertEquals('99999999999999999.98', $sub->toDecimalString());
    }

    public function test_json_and_string_representation(): void
    {
        $m = Money::of('25000.00');

        $this->assertEquals('25000.00', (string) $m);
        $this->assertEquals('"25000.00"', json_encode($m));
    }
}
