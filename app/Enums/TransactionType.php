<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
            self::Transfer => 'Transfer Antar Akun',
            self::Adjustment => 'Penyesuaian Saldo',
        };
    }
}
