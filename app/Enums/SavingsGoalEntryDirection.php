<?php

declare(strict_types=1);

namespace App\Enums;

enum SavingsGoalEntryDirection: string
{
    case Contribution = 'contribution';
    case Withdrawal = 'withdrawal';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Contribution => 'Setoran Tabungan',
            self::Withdrawal => 'Penarikan Dana',
        };
    }
}
