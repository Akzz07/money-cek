<?php

declare(strict_types=1);

namespace App\Enums;

enum BillStatus: string
{
    case Upcoming = 'upcoming';
    case Paid = 'paid';
    case Skipped = 'skipped';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Mendatang',
            self::Paid => 'Lunas',
            self::Skipped => 'Dilewati',
        };
    }
}
