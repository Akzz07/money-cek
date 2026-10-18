<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
        };
    }
}
