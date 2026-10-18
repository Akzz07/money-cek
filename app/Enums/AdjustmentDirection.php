<?php

declare(strict_types=1);

namespace App\Enums;

enum AdjustmentDirection: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Increase => 'Penambahan (+)',
            self::Decrease => 'Pengurangan (-)',
        };
    }
}
