<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Finance;

use App\Domain\Finance\PaydaySchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class PaydayScheduleTest extends TestCase
{
    private PaydaySchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedule = new PaydaySchedule;
    }

    public function test_normal_payday_in_current_month(): void
    {
        $user = new User([
            'payday_day' => 25,
            'timezone' => 'Asia/Jakarta',
        ]);

        $from = CarbonImmutable::create(2026, 4, 10, 0, 0, 0, 'Asia/Jakarta');
        $next = $this->schedule->nextPayday($user, $from);

        $this->assertEquals('2026-04-25', $next->format('Y-m-d'));
    }

    public function test_payday_moves_to_next_month_if_already_passed(): void
    {
        $user = new User([
            'payday_day' => 25,
            'timezone' => 'Asia/Jakarta',
        ]);

        $from = CarbonImmutable::create(2026, 4, 26, 0, 0, 0, 'Asia/Jakarta');
        $next = $this->schedule->nextPayday($user, $from);

        $this->assertEquals('2026-05-25', $next->format('Y-m-d'));
    }

    public function test_clamps_to_last_day_for_31st_on_30_day_month(): void
    {
        $user = new User([
            'payday_day' => 31,
            'timezone' => 'Asia/Jakarta',
        ]);

        // April has 30 days
        $from = CarbonImmutable::create(2026, 4, 5, 0, 0, 0, 'Asia/Jakarta');
        $next = $this->schedule->nextPayday($user, $from);

        $this->assertEquals('2026-04-30', $next->format('Y-m-d'));
    }

    public function test_clamps_to_february_28_in_non_leap_year(): void
    {
        $user = new User([
            'payday_day' => 31,
            'timezone' => 'Asia/Jakarta',
        ]);

        // 2025 is not a leap year (Feb has 28 days)
        $from = CarbonImmutable::create(2025, 2, 1, 0, 0, 0, 'Asia/Jakarta');
        $next = $this->schedule->nextPayday($user, $from);

        $this->assertEquals('2025-02-28', $next->format('Y-m-d'));
    }

    public function test_clamps_to_february_29_in_leap_year(): void
    {
        $user = new User([
            'payday_day' => 31,
            'timezone' => 'Asia/Jakarta',
        ]);

        // 2028 is a leap year (Feb has 29 days)
        $from = CarbonImmutable::create(2028, 2, 1, 0, 0, 0, 'Asia/Jakarta');
        $next = $this->schedule->nextPayday($user, $from);

        $this->assertEquals('2028-02-29', $next->format('Y-m-d'));
    }

    public function test_explicit_next_payday_date_takes_precedence(): void
    {
        $user = new User([
            'payday_day' => 25,
            'next_payday_date' => '2026-04-20',
            'timezone' => 'Asia/Jakarta',
        ]);

        $from = CarbonImmutable::create(2026, 4, 10, 0, 0, 0, 'Asia/Jakarta');
        $next = $this->schedule->nextPayday($user, $from);

        $this->assertEquals('2026-04-20', $next->format('Y-m-d'));
    }
}
