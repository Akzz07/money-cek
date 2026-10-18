<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Models\User;
use Carbon\CarbonImmutable;

final class PaydaySchedule
{
    /**
     * Compute the next upcoming payday for the given user.
     * Rule OD-11:
     * 1. If explicit next_payday_date is set and >= $from, return it.
     * 2. Otherwise calculate based on monthly payday_day (1-31).
     * 3. If payday_day exceeds the number of days in the month, clamp to the last day of that month.
     */
    public function nextPayday(User $user, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $today = ($from ?? CarbonImmutable::now($timezone))->setTimezone($timezone)->startOfDay();

        // Check explicit next_payday_date override first
        if ($user->next_payday_date !== null) {
            $explicit = CarbonImmutable::parse($user->next_payday_date, $timezone)->startOfDay();
            if ($explicit->greaterThanOrEqualTo($today)) {
                return $explicit;
            }
        }

        // Default to day 25 if user has not configured payday_day
        $targetDay = $user->payday_day ?? 25;

        // Try candidate in current month
        $candidateThisMonth = $this->resolvePaydayInMonth($today->year, $today->month, $targetDay, $timezone);

        if ($candidateThisMonth->greaterThanOrEqualTo($today)) {
            return $candidateThisMonth;
        }

        // If today has already passed this month's payday, move to next month
        $nextMonth = $today->addMonthNoOverflow();

        return $this->resolvePaydayInMonth($nextMonth->year, $nextMonth->month, $targetDay, $timezone);
    }

    /**
     * Resolve the payday date in a given year and month, clamping to the last day if targetDay > daysInMonth.
     */
    private function resolvePaydayInMonth(int $year, int $month, int $targetDay, string $timezone): CarbonImmutable
    {
        $firstOfMonth = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $timezone);
        $daysInMonth = $firstOfMonth->daysInMonth;

        $clampedDay = min($targetDay, $daysInMonth);

        return $firstOfMonth->setDay($clampedDay);
    }
}
