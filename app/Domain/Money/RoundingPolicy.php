<?php

declare(strict_types=1);

namespace App\Domain\Money;

use Brick\Math\RoundingMode;

final class RoundingPolicy
{
    /**
     * The standard decimal scale for currency in Monetra.
     */
    public const int SCALE = 2;

    /**
     * The standard financial rounding mode (Banker's standard / commercial: HalfUp).
     */
    public const RoundingMode MODE = RoundingMode::HalfUp;
}
