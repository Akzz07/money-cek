<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\Bill;
use Illuminate\Support\Collection;

final readonly class BillsOverview
{
    /**
     * @param  Collection<int, Bill>  $bills
     */
    public function __construct(
        public Money $totalUpcoming,
        public Money $totalOverdue,
        public Money $totalPaid,
        public Collection $bills,
        public int $upcomingCount,
        public int $overdueCount,
        public int $paidCount,
    ) {}
}
