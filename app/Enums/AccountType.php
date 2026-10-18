<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case Investment = 'investment';
    case Savings = 'savings';
    case Other = 'other';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Bank => 'Bank',
            self::EWallet => 'Dompet Digital',
            self::Investment => 'Investasi',
            self::Savings => 'Tabungan',
            self::Other => 'Lainnya',
        };
    }

    /**
     * Rule OD-06 & FR-006:
     * Investment accounts do not count towards discretionary Safe-to-Spend by default.
     */
    public function defaultIncludeInSafeToSpend(): bool
    {
        return match ($this) {
            self::Investment => false,
            default => true,
        };
    }
}
