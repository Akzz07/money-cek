<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Archived => 'Diarsipkan',
        };
    }
}
