<?php

declare(strict_types=1);

namespace App\Services\SafeToSpend;

use App\Domain\Money\Money;
use App\DTOs\SafeToSpendResult;
use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\SavingsGoalStatus;
use App\Models\Bill;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use Carbon\CarbonImmutable;

final class SafeToSpendService
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
    ) {}

    /**
     * Compute transparent Safe-to-Spend projection for the user.
     */
    public function calculate(User $user, ?CarbonImmutable $customPlanningDate = null): SafeToSpendResult
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $planningDate = $this->resolvePlanningDate($user, $customPlanningDate, $today, $timezone);
        $daysRemaining = max(0, (int) $today->diffInDays($planningDate, false));

        // 1. Spendable Account Balances
        $spendableAccountsQuery = $user->accounts()
            ->where('status', AccountStatus::Active->value)
            ->where('include_in_safe_to_spend', true)
            ->orderBy('name')
            ->get();

        $spendableAccounts = collect();
        $totalSpendable = Money::zero();

        foreach ($spendableAccountsQuery as $account) {
            $balance = $this->balanceService->getBalance($account);
            $spendableAccounts->push([
                'account' => $account,
                'balance' => $balance,
            ]);
            $totalSpendable = $totalSpendable->plus($balance);
        }

        // 2. Unpaid Obligations due on or before planning date
        $upcomingBills = Bill::query()
            ->where('user_id', $user->id)
            ->where('status', BillStatus::Upcoming->value)
            ->whereNull('paid_transaction_id')
            ->whereDate('due_date', '<=', $planningDate->toDateString())
            ->orderBy('due_date')
            ->get();

        $totalBills = Money::zero();
        foreach ($upcomingBills as $bill) {
            $totalBills = $totalBills->plus($bill->amount);
        }

        // 3. Pending Allocations with Deduction Isolation (ADR-006 Anti Double-Counting)
        $activeGoals = SavingsGoal::query()
            ->where('user_id', $user->id)
            ->where('status', SavingsGoalStatus::Active->value)
            ->with(['targetAccount', 'entries'])
            ->get();

        $allocations = collect();
        $totalAllocations = Money::zero();

        foreach ($activeGoals as $goal) {
            $targetAccount = $goal->targetAccount;
            $hasDedicatedNonSpendableAccount = $targetAccount !== null && ! $targetAccount->include_in_safe_to_spend;

            $deductAmount = Money::zero();
            $reason = '';

            if ($hasDedicatedNonSpendableAccount) {
                // Funds already transferred to the dedicated account are already outside the spendable balance pool.
                // We do NOT deduct them again. Only deduct remaining amount if due on/before planning date.
                if ($goal->target_date !== null && $goal->target_date->lessThanOrEqualTo($planningDate)) {
                    $deductAmount = $goal->remainingAmount();
                    $reason = "Kebutuhan target jatuh tempo ({$goal->target_date->translatedFormat('d M Y')}) pada rekening khusus";
                }
            } else {
                // Virtual goal: accumulated savings are still sitting inside daily spendable accounts.
                // We must reserve the current saved balance so the user does not spend it.
                $savedSoFar = $goal->currentAmount();
                if ($savedSoFar->isPositive()) {
                    $deductAmount = $savedSoFar;
                    $reason = 'Cadangan tabungan yang tersimpan di rekening operasional harian';
                }

                // Plus remaining amount if due before or on planning date
                if ($goal->target_date !== null && $goal->target_date->lessThanOrEqualTo($planningDate)) {
                    $remaining = $goal->remainingAmount();
                    if ($remaining->isPositive()) {
                        $deductAmount = $deductAmount->plus($remaining);
                        $reason .= ' + sisa target jatuh tempo sebelum gajian';
                    }
                }
            }

            if ($deductAmount->isPositive()) {
                $totalAllocations = $totalAllocations->plus($deductAmount);
                $allocations->push([
                    'goal' => $goal,
                    'amount' => $deductAmount,
                    'reason' => $reason,
                ]);
            }
        }

        // 4. Compute Final Safe-to-Spend
        $safeToSpend = $totalSpendable->minus($totalBills)->minus($totalAllocations);

        // 5. Construct Explicit Assumptions
        $assumptions = [
            "Hanya memperhitungkan rekening operasional harian aktif (total {$spendableAccounts->count()} akun).",
            "Tagihan diperhitungkan s/d tanggal perencanaan {$planningDate->translatedFormat('d F Y')}.",
            'Tagihan yang sudah lunas atau dilewati tidak mengurangi saldo.',
            'Tabungan yang telah ditransfer ke rekening investasi/khusus tidak dikurangkan ganda (ADR-006).',
            'Estimasi perencanaan, bukan jaminan atau nasihat keuangan formal.',
        ];

        return new SafeToSpendResult(
            spendableBalance: $totalSpendable,
            unpaidBills: $totalBills,
            pendingAllocations: $totalAllocations,
            safeToSpend: $safeToSpend,
            planningDate: $planningDate,
            daysRemaining: $daysRemaining,
            spendableAccounts: $spendableAccounts,
            upcomingBills: $upcomingBills,
            allocations: $allocations,
            assumptions: $assumptions,
            isEstimate: true,
        );
    }

    /**
     * Resolve the target planning date (e.g. next payday or user choice).
     */
    public function resolvePlanningDate(
        User $user,
        ?CarbonImmutable $customPlanningDate,
        CarbonImmutable $today,
        string $timezone,
    ): CarbonImmutable {
        if ($customPlanningDate !== null) {
            return $customPlanningDate->startOfDay();
        }

        if ($user->next_payday_date !== null) {
            $explicitDate = CarbonImmutable::parse($user->next_payday_date, $timezone)->startOfDay();
            if ($explicitDate->greaterThanOrEqual($today)) {
                return $explicitDate;
            }
        }

        if ($user->payday_day !== null) {
            $payday = (int) $user->payday_day;

            if ($today->day < $payday) {
                $maxDay = (int) $today->endOfMonth()->day;

                return $today->setDay(min($payday, $maxDay))->startOfDay();
            }

            $nextMonth = $today->addMonthNoOverflow();
            $maxDay = (int) $nextMonth->endOfMonth()->day;

            return $nextMonth->setDay(min($payday, $maxDay))->startOfDay();
        }

        return $today->endOfMonth()->startOfDay();
    }
}
