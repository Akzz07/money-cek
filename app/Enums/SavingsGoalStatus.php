<?php

declare(strict_types=1);

namespace App\Enums;

enum SavingsGoalStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Paused => 'Dijeda',
            self::Completed => 'Tercapai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Get badge styling classes for UI.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Paused => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Cancelled => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }
}
