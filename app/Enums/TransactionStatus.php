<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Posted = 'posted';
    case Voided = 'voided';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Posted => 'Tercatat',
            self::Voided => 'Dibatalkan (Void)',
        };
    }
}
