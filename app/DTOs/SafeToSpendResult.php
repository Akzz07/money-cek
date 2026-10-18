<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\Bill;
use App\Models\SavingsGoal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class SafeToSpendResult
{
    /**
     * @param  Collection<int, array{account: Account, balance: Money}>  $spendableAccounts
     * @param  Collection<int, Bill>  $upcomingBills
     * @param  Collection<int, array{goal: SavingsGoal, amount: Money, reason: string}>  $allocations
     * @param  array<int, string>  $assumptions
     */
    public function __construct(
        public Money $spendableBalance,
        public Money $unpaidBills,
        public Money $pendingAllocations,
        public Money $safeToSpend,
        public CarbonImmutable $planningDate,
        public int $daysRemaining,
        public Collection $spendableAccounts,
        public Collection $upcomingBills,
        public Collection $allocations,
        public array $assumptions,
        public bool $isEstimate = true,
    ) {}

    public function isPositive(): bool
    {
        return $this->safeToSpend->isPositive();
    }

    public function isZero(): bool
    {
        return $this->safeToSpend->isZero();
    }

    public function isNegative(): bool
    {
        return $this->safeToSpend->isNegative();
    }
}
