<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Accounts\ReconcileAccount;
use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\AdjustmentDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountBalanceAndReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_balance_formula_calculates_all_components(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('100000.00'), // Saldo awal: 100.000
        ]);
        $otherAccount = Account::factory()->create(['user_id' => $user->id]);

        $catIncome = Category::factory()->income()->create(['user_id' => $user->id]);
        $catExpense = Category::factory()->expense()->create(['user_id' => $user->id]);

        // 1. Income: +50.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'category_id' => $catIncome->id,
            'amount' => Money::of('50000.00'),
            'status' => TransactionStatus::Posted,
        ]);

        // 2. Expense: -20.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Expense,
            'category_id' => $catExpense->id,
            'amount' => Money::of('20000.00'),
            'status' => TransactionStatus::Posted,
        ]);

        // 3. Transfer In: +30.000 (from otherAccount to account)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $otherAccount->id,
            'destination_account_id' => $account->id,
            'type' => TransactionType::Transfer,
            'category_id' => null,
            'amount' => Money::of('30000.00'),
            'status' => TransactionStatus::Posted,
        ]);

        // 4. Transfer Out: -15.000 (from account to otherAccount)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'destination_account_id' => $otherAccount->id,
            'type' => TransactionType::Transfer,
            'category_id' => null,
            'amount' => Money::of('15000.00'),
            'status' => TransactionStatus::Posted,
        ]);

        // Expected = 100.000 + 50.000 - 20.000 + 30.000 - 15.000 = 145.000
        $service = app(AccountBalanceService::class);
        $balance = $service->getBalance($account);

        $this->assertEquals('145000.00', $balance->toDecimalString());
    }

    public function test_voided_transactions_are_excluded_from_balance_calculation(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('100000.00'),
        ]);

        $catExpense = Category::factory()->expense()->create(['user_id' => $user->id]);

        // Voided expense: Should NOT deduct from balance
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Expense,
            'category_id' => $catExpense->id,
            'amount' => Money::of('90000.00'),
            'status' => TransactionStatus::Voided,
            'voided_at' => now(),
            'void_reason' => 'Tes void',
        ]);

        $service = app(AccountBalanceService::class);
        $balance = $service->getBalance($account);

        $this->assertEquals('100000.00', $balance->toDecimalString());
    }

    public function test_net_worth_and_spendable_aggregates(): void
    {
        $user = User::factory()->create();

        // Account 1: Bank (Asset + Spendable) = 200.000
        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('200000.00'),
            'include_in_total_assets' => true,
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // Account 2: Investment (Asset, NOT Spendable) = 500.000
        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('500000.00'),
            'include_in_total_assets' => true,
            'include_in_safe_to_spend' => false,
            'status' => AccountStatus::Active,
        ]);

        $service = app(AccountBalanceService::class);

        // Net worth = 200.000 + 500.000 = 700.000
        $this->assertEquals('700000.00', $service->getTotalNetWorth($user)->toDecimalString());

        // Spendable = 200.000
        $this->assertEquals('200000.00', $service->getTotalSpendableBalance($user)->toDecimalString());
    }

    public function test_reconciliation_increases_balance_to_match_real_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('50000.00'),
        ]);

        $service = app(AccountBalanceService::class);
        $this->assertEquals('50000.00', $service->getBalance($account)->toDecimalString());

        // User reconciles to 75.000 (+25.000 increase)
        $targetBalance = Money::of('75000.00');
        $action = app(ReconcileAccount::class);
        $tx = $action->handle($user, $account, $targetBalance, 'Penyesuaian temuan uang tunai');

        $this->assertNotNull($tx);
        $this->assertEquals(TransactionType::Adjustment, $tx->type);
        $this->assertEquals(AdjustmentDirection::Increase, $tx->adjustment_direction);
        $this->assertEquals('25000.00', $tx->amount->toDecimalString());

        // Post-reconciliation balance must match target exactly
        $this->assertEquals('75000.00', $service->getBalance($account)->toDecimalString());
    }

    public function test_reconciliation_decreases_balance_to_match_real_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('100000.00'),
        ]);

        $service = app(AccountBalanceService::class);

        // User reconciles to 80.000 (-20.000 decrease)
        $targetBalance = Money::of('80000.00');
        $action = app(ReconcileAccount::class);
        $tx = $action->handle($user, $account, $targetBalance, 'Biaya admin terlewat');

        $this->assertNotNull($tx);
        $this->assertEquals(TransactionType::Adjustment, $tx->type);
        $this->assertEquals(AdjustmentDirection::Decrease, $tx->adjustment_direction);
        $this->assertEquals('20000.00', $tx->amount->toDecimalString());

        // Post-reconciliation balance must match target exactly
        $this->assertEquals('80000.00', $service->getBalance($account)->toDecimalString());
    }

    public function test_reconciliation_records_audit_log(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('50000.00'),
        ]);

        $action = app(ReconcileAccount::class);
        $action->handle($user, $account, Money::of('60000.00'), 'Cek rekonsiliasi audit');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'account.reconciled',
            'entity_type' => 'Account',
            'entity_id' => $account->id,
        ]);
    }

    public function test_user_cannot_reconcile_other_users_account(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountB = Account::factory()->create([
            'user_id' => $userB->id,
            'opening_balance' => Money::of('100000.00'),
        ]);

        $response = $this->actingAs($userA)->post(route('accounts.reconcile', $accountB), [
            'real_balance' => '50000.00',
            'reason' => 'Usaha meretas saldo orang',
        ]);

        $response->assertForbidden();
    }
}
