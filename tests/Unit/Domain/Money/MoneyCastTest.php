<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Money;

use App\Casts\MoneyCast;
use App\Domain\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

final class MoneyCastTest extends TestCase
{
    private MoneyCast $cast;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new MoneyCast;
    }

    public function test_get_converts_db_decimal_string_to_money_instance(): void
    {
        $model = new class extends Model {};

        $money = $this->cast->get($model, 'balance', '150000.75', []);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertEquals('150000.75', $money->toDecimalString());
    }

    public function test_get_returns_null_when_value_is_null(): void
    {
        $model = new class extends Model {};

        $result = $this->cast->get($model, 'balance', null, []);

        $this->assertNull($result);
    }

    public function test_set_converts_money_instance_to_decimal_string(): void
    {
        $model = new class extends Model {};
        $money = Money::of('250000.00');

        $result = $this->cast->set($model, 'balance', $money, []);

        $this->assertEquals('250000.00', $result);
    }

    public function test_set_converts_numeric_string_to_decimal_string(): void
    {
        $model = new class extends Model {};

        $result = $this->cast->set($model, 'balance', '50000', []);

        $this->assertEquals('50000.00', $result);
    }

    public function test_set_returns_null_when_value_is_null(): void
    {
        $model = new class extends Model {};

        $result = $this->cast->set($model, 'balance', null, []);

        $this->assertNull($result);
    }
}
