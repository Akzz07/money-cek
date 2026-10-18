<?php

declare(strict_types=1);

namespace App\Services\SavingsGoals;

use App\Domain\Money\Money;
use App\DTOs\SavingsGoalProgress;
use App\DTOs\SavingsGoalsOverview;
use App\Enums\SavingsGoalStatus;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use App\Models\User;

final class SavingsGoalProgressService
{
    /**
     * Get comprehensive savings goals overview for a user.
     */
    public function getOverview(User $user, ?SavingsGoalStatus $statusFilter = null): SavingsGoalsOverview
    {
        $query = SavingsGoal::query()
            ->where('user_id', $user->id)
            ->with('targetAccount')
            ->orderByRaw("CASE WHEN status = 'active' THEN 1 WHEN status = 'paused' THEN 2 WHEN status = 'completed' THEN 3 ELSE 4 END")
            ->orderBy('target_date');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter->value);
        }

        $goals = $query->get();

        if ($goals->isEmpty()) {
            return new SavingsGoalsOverview(
                totalTarget: Money::zero(),
                totalSaved: Money::zero(),
                totalRemaining: Money::zero(),
                overallPercentage: 0.0,
                activeGoalsCount: 0,
                completedGoalsCount: 0,
                goals: collect(),
            );
        }

        // Single batch query for net saved amounts across all goals (0 N+1)
        $entrySums = SavingsGoalEntry::query()
            ->whereIn('savings_goal_id', $goals->pluck('id'))
            ->selectRaw("
                savings_goal_id,
                SUM(CASE WHEN direction = 'contribution' THEN amount ELSE 0 END) as total_contributions,
                SUM(CASE WHEN direction = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals
            ")
            ->groupBy('savings_goal_id')
            ->get()
            ->keyBy('savings_goal_id');

        $progressList = collect();
        $totalTarget = Money::zero();
        $totalSaved = Money::zero();
        $totalRemaining = Money::zero();
        $activeCount = 0;
        $completedCount = 0;

        foreach ($goals as $goal) {
            $goalId = $goal->id;
            $sumRow = $entrySums->get($goalId);

            $contributions = (string) ($sumRow->total_contributions ?? '0.00');
            $withdrawals = (string) ($sumRow->total_withdrawals ?? '0.00');
            $netDecimal = bcsub($contributions, $withdrawals, 2);

            $currentAmount = Money::of($netDecimal);
            $targetAmount = $goal->target_amount;

            $remainingAmount = $currentAmount->isGreaterThanOrEqual($targetAmount)
                ? Money::zero()
                : $targetAmount->minus($currentAmount);

            $surplusAmount = $currentAmount->isGreaterThan($targetAmount)
                ? $currentAmount->minus($targetAmount)
                : null;

            $targetFloat = (float) $targetAmount->toDecimalString();
            $currentFloat = (float) $currentAmount->toDecimalString();
            $percentage = $targetFloat > 0.0 ? round(($currentFloat / $targetFloat) * 100.0, 1) : 0.0;
            $isAchieved = $currentAmount->isGreaterThanOrEqual($targetAmount);

            $progressList->push(new SavingsGoalProgress(
                goal: $goal,
                targetAmount: $targetAmount,
                currentAmount: $currentAmount,
                remainingAmount: $remainingAmount,
                percentage: $percentage,
                isAchieved: $isAchieved,
                surplusAmount: $surplusAmount,
            ));

            if ($goal->status === SavingsGoalStatus::Active) {
                $activeCount++;
                $totalTarget = $totalTarget->plus($targetAmount);
                $totalSaved = $totalSaved->plus($currentAmount);
                $totalRemaining = $totalRemaining->plus($remainingAmount);
            } elseif ($goal->status === SavingsGoalStatus::Completed) {
                $completedCount++;
                $totalSaved = $totalSaved->plus($currentAmount);
            }
        }

        $totalTargetFloat = (float) $totalTarget->toDecimalString();
        $totalSavedFloat = (float) $totalSaved->toDecimalString();
        $overallPercentage = $totalTargetFloat > 0.0
            ? round(($totalSavedFloat / $totalTargetFloat) * 100.0, 1)
            : 0.0;

        return new SavingsGoalsOverview(
            totalTarget: $totalTarget,
            totalSaved: $totalSaved,
            totalRemaining: $totalRemaining,
            overallPercentage: $overallPercentage,
            activeGoalsCount: $activeCount,
            completedGoalsCount: $completedCount,
            goals: $progressList,
        );
    }

    /**
     * Get progress for a single goal.
     */
    public function getProgress(SavingsGoal $goal): SavingsGoalProgress
    {
        $currentAmount = $goal->currentAmount();
        $targetAmount = $goal->target_amount;

        $remainingAmount = $currentAmount->isGreaterThanOrEqual($targetAmount)
            ? Money::zero()
            : $targetAmount->minus($currentAmount);

        $surplusAmount = $currentAmount->isGreaterThan($targetAmount)
            ? $currentAmount->minus($targetAmount)
            : null;

        $targetFloat = (float) $targetAmount->toDecimalString();
        $currentFloat = (float) $currentAmount->toDecimalString();
        $percentage = $targetFloat > 0.0 ? round(($currentFloat / $targetFloat) * 100.0, 1) : 0.0;
        $isAchieved = $currentAmount->isGreaterThanOrEqual($targetAmount);

        return new SavingsGoalProgress(
            goal: $goal,
            targetAmount: $targetAmount,
            currentAmount: $currentAmount,
            remainingAmount: $remainingAmount,
            percentage: $percentage,
            isAchieved: $isAchieved,
            surplusAmount: $surplusAmount,
        );
    }
}
