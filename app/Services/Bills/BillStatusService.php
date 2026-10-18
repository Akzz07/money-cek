<?php

declare(strict_types=1);

namespace App\Services\Bills;

use App\Domain\Money\Money;
use App\DTOs\BillsOverview;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\User;
use Carbon\CarbonImmutable;

final class BillStatusService
{
    /**
     * Get aggregate overview and list of bills for a user.
     */
    public function getOverview(User $user, ?string $tab = null): BillsOverview
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $todayStr = $today->toDateString();

        $allBills = $user->bills()
            ->with(['category', 'paidTransaction'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $totalUpcoming = Money::zero();
        $totalOverdue = Money::zero();
        $totalPaid = Money::zero();

        $upcomingCount = 0;
        $overdueCount = 0;
        $paidCount = 0;

        foreach ($allBills as $bill) {
            if ($bill->status === BillStatus::Paid) {
                $totalPaid = $totalPaid->plus($bill->amount);
                $paidCount++;
            } elseif ($bill->status === BillStatus::Upcoming) {
                if ($bill->due_date->startOfDay()->lessThan($today)) {
                    $totalOverdue = $totalOverdue->plus($bill->amount);
                    $overdueCount++;
                } else {
                    $totalUpcoming = $totalUpcoming->plus($bill->amount);
                    $upcomingCount++;
                }
            }
        }

        $filteredBills = match ($tab) {
            'upcoming' => $allBills->filter(fn (Bill $b) => $b->status === BillStatus::Upcoming && $b->due_date->startOfDay()->greaterThanOrEqualTo($today)),
            'overdue' => $allBills->filter(fn (Bill $b) => $b->status === BillStatus::Upcoming && $b->due_date->startOfDay()->lessThan($today)),
            'paid' => $allBills->filter(fn (Bill $b) => $b->status === BillStatus::Paid),
            'skipped' => $allBills->filter(fn (Bill $b) => $b->status === BillStatus::Skipped),
            default => $allBills,
        };

        return new BillsOverview(
            totalUpcoming: $totalUpcoming,
            totalOverdue: $totalOverdue,
            totalPaid: $totalPaid,
            bills: $filteredBills->values(),
            upcomingCount: $upcomingCount,
            overdueCount: $overdueCount,
            paidCount: $paidCount,
        );
    }
}
