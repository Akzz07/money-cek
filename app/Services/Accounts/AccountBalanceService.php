<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AccountBalanceService
{
    /**
     * Calculate current posted balance for a specific account.
     * Formula: opening_balance + income - expense + transfer_in - transfer_out + adj_increase - adj_decrease.
     */
    public function getBalance(Account $account): Money
    {
        $aggregates = DB::table('transactions')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN source_account_id = ? AND type = 'income' AND status = 'posted' THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN source_account_id = ? AND type = 'expense' AND status = 'posted' THEN amount ELSE 0 END), 0) AS total_expense,
                COALESCE(SUM(CASE WHEN destination_account_id = ? AND type = 'transfer' AND status = 'posted' THEN amount ELSE 0 END), 0) AS total_transfer_in,
                COALESCE(SUM(CASE WHEN source_account_id = ? AND type = 'transfer' AND status = 'posted' THEN amount ELSE 0 END), 0) AS total_transfer_out,
                COALESCE(SUM(CASE WHEN source_account_id = ? AND type = 'adjustment' AND adjustment_direction = 'increase' AND status = 'posted' THEN amount ELSE 0 END), 0) AS total_adj_increase,
                COALESCE(SUM(CASE WHEN source_account_id = ? AND type = 'adjustment' AND adjustment_direction = 'decrease' AND status = 'posted' THEN amount ELSE 0 END), 0) AS total_adj_decrease
            ", [
                $account->id,
                $account->id,
                $account->id,
                $account->id,
                $account->id,
                $account->id,
            ])
            ->where('user_id', $account->user_id)
            ->where('status', 'posted')
            ->where(function ($q) use ($account) {
                $q->where('source_account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id);
            })
            ->first();

        $balance = $account->opening_balance
            ->plus(Money::of((string) ($aggregates->total_income ?? '0.00')))
            ->minus(Money::of((string) ($aggregates->total_expense ?? '0.00')))
            ->plus(Money::of((string) ($aggregates->total_transfer_in ?? '0.00')))
            ->minus(Money::of((string) ($aggregates->total_transfer_out ?? '0.00')))
            ->plus(Money::of((string) ($aggregates->total_adj_increase ?? '0.00')))
            ->minus(Money::of((string) ($aggregates->total_adj_decrease ?? '0.00')));

        return $balance;
    }

    /**
     * Get mapped balances for a collection of accounts: [account_id => Money].
     *
     * @param  Collection<int, Account>  $accounts
     * @return Collection<int, Money>
     */
    public function getBalancesForAccounts(Collection $accounts): Collection
    {
        return $accounts->mapWithKeys(function (Account $account) {
            return [$account->id => $this->getBalance($account)];
        });
    }

    /**
     * Get total net worth across active asset accounts (include_in_total_assets = true).
     */
    public function getTotalNetWorth(User $user): Money
    {
        $assetAccounts = $user->accounts()->active()->where('include_in_total_assets', true)->get();

        $total = Money::zero();
        foreach ($assetAccounts as $account) {
            $total = $total->plus($this->getBalance($account));
        }

        return $total;
    }

    /**
     * Get total spendable balance (include_in_safe_to_spend = true).
     */
    public function getTotalSpendableBalance(User $user): Money
    {
        $spendableAccounts = $user->accounts()->active()->where('include_in_safe_to_spend', true)->get();

        $total = Money::zero();
        foreach ($spendableAccounts as $account) {
            $total = $total->plus($this->getBalance($account));
        }

        return $total;
    }
}
