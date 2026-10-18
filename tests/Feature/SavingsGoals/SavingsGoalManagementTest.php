<?php

declare(strict_types=1);

namespace Tests\Feature\SavingsGoals;

use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\SavingsGoalStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SavingsGoalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_savings_goals(): void
    {
        $response = $this->get(route('savings-goals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_savings_goals_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('savings-goals.index'));
        $response->assertOk();
        $response->assertSee('Target Tabungan');
        $response->assertSee('Belum Ada Target Tabungan');
    }

    public function test_user_can_create_a_savings_goal(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Simpanan',
            'type' => AccountType::Savings,
            'status' => AccountStatus::Active,
        ]);

        $targetDate = CarbonImmutable::now()->addMonths(6)->toDateString();

        $response = $this->actingAs($user)->post(route('savings-goals.store'), [
            'name' => 'Liburan ke Jepang',
            'target_amount' => '25000000.00',
            'target_date' => $targetDate,
            'target_account_id' => $account->id,
            'color' => '#10B981',
            'notes' => 'Tiket dan hotel Tokyo',
        ]);

        $response->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseHas('savings_goals', [
            'user_id' => $user->id,
            'name' => 'Liburan ke Jepang',
            'target_account_id' => $account->id,
            'color' => '#10B981',
            'status' => 'active',
        ]);

        $saved = SavingsGoal::firstOrFail();
        $this->assertSame('25000000.00', $saved->target_amount->toDecimalString());
    }

    public function test_user_can_contribute_to_savings_goal_and_ledger_updates_accurately(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Dana Darurat',
            'target_amount' => Money::of('10000000.00'),
            'status' => SavingsGoalStatus::Active,
        ]);

        // 1. First contribution
        $response1 = $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'contribution',
            'amount' => '3000000.00',
            'entry_date' => '2026-10-18',
            'notes' => 'Alokasi Gaji Oktober',
        ]);
        $response1->assertRedirect();

        // 2. Second contribution
        $response2 = $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'contribution',
            'amount' => '2000000.00',
            'entry_date' => '2026-10-19',
            'notes' => 'Bonus freelance',
        ]);
        $response2->assertRedirect();

        $this->assertCount(2, $goal->entries);
        $this->assertSame('5000000.00', $goal->currentAmount()->toDecimalString());
        $this->assertSame('5000000.00', $goal->remainingAmount()->toDecimalString());
        $this->assertSame(50.0, $goal->percentage());
        $this->assertFalse($goal->isAchieved());
    }

    public function test_atomic_transfer_is_created_when_contributing_with_source_and_target_account(): void
    {
        $user = User::factory()->create();
        $sourceAccount = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Operasional',
            'opening_balance' => Money::of('5000000.00'),
            'status' => AccountStatus::Active,
        ]);

        $targetAccount = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Mandiri Tabungan',
            'opening_balance' => Money::of('1000000.00'),
            'status' => AccountStatus::Active,
        ]);

        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Beli Mac Studio',
            'target_amount' => Money::of('30000000.00'),
            'target_account_id' => $targetAccount->id,
            'status' => SavingsGoalStatus::Active,
        ]);

        $response = $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'contribution',
            'amount' => '2500000.00',
            'entry_date' => '2026-10-18',
            'account_id' => $sourceAccount->id,
            'notes' => 'Setor dari BCA ke Mandiri',
        ]);

        $response->assertRedirect();

        // Verify ledger entry
        $entry = SavingsGoalEntry::where('savings_goal_id', $goal->id)->firstOrFail();
        $this->assertSame('2500000.00', $entry->amount->toDecimalString());
        $this->assertNotNull($entry->linked_transaction_id);

        // Verify linked atomic transfer transaction
        $transaction = Transaction::findOrFail($entry->linked_transaction_id);
        $this->assertSame(TransactionType::Transfer, $transaction->type);
        $this->assertSame($sourceAccount->id, $transaction->source_account_id);
        $this->assertSame($targetAccount->id, $transaction->destination_account_id);
        $this->assertSame('2500000.00', $transaction->amount->toDecimalString());
        $this->assertSame(TransactionStatus::Posted, $transaction->status);

        // Verify physical account balances
        $balanceService = app(AccountBalanceService::class);
        $this->assertSame('2500000.00', $balanceService->getBalance($sourceAccount)->toDecimalString());
        $this->assertSame('3500000.00', $balanceService->getBalance($targetAccount)->toDecimalString());
    }

    public function test_savings_goal_automatically_marks_as_completed_when_target_is_reached(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kursus Golang',
            'target_amount' => Money::of('2000000.00'),
            'status' => SavingsGoalStatus::Active,
        ]);

        $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'contribution',
            'amount' => '2000000.00',
            'entry_date' => '2026-10-18',
        ]);

        $goal->refresh();
        $this->assertSame(SavingsGoalStatus::Completed, $goal->status);
        $this->assertTrue($goal->isAchieved());
        $this->assertSame('0.00', $goal->remainingAmount()->toDecimalString());
        $this->assertNull($goal->surplusAmount());
    }

    public function test_surplus_over_100_percent_is_calculated_cleanly_without_errors(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Gadget Baru',
            'target_amount' => Money::of('5000000.00'),
            'status' => SavingsGoalStatus::Active,
        ]);

        // Contribute 6.500.000 (130%)
        $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'contribution',
            'amount' => '6500000.00',
            'entry_date' => '2026-10-18',
        ]);

        $goal->refresh();
        $this->assertSame(SavingsGoalStatus::Completed, $goal->status);
        $this->assertSame(130.0, $goal->percentage());
        $this->assertNotNull($goal->surplusAmount());
        $this->assertSame('1500000.00', $goal->surplusAmount()->toDecimalString());
        $this->assertSame('0.00', $goal->remainingAmount()->toDecimalString());

        // Check index view renders cleanly with surplus
        $response = $this->actingAs($user)->get(route('savings-goals.index'));
        $response->assertOk();
        $response->assertSee('Surplus: +Rp 1.500.000', false);
    }

    public function test_user_can_withdraw_from_savings_goal_and_reverts_completed_status_if_below_target(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Renovasi Kamar',
            'target_amount' => Money::of('10000000.00'),
            'status' => SavingsGoalStatus::Active,
        ]);

        // Reach target
        $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'contribution',
            'amount' => '10000000.00',
            'entry_date' => '2026-10-18',
        ]);

        $goal->refresh();
        $this->assertSame(SavingsGoalStatus::Completed, $goal->status);

        // Withdraw 3.000.000
        $this->actingAs($user)->post(route('savings-goals.entries.store', $goal), [
            'direction' => 'withdrawal',
            'amount' => '3000000.00',
            'entry_date' => '2026-10-19',
            'notes' => 'Beli semen dan cat',
        ]);

        $goal->refresh();
        $this->assertSame(SavingsGoalStatus::Active, $goal->status);
        $this->assertSame('7000000.00', $goal->currentAmount()->toDecimalString());
        $this->assertSame('3000000.00', $goal->remainingAmount()->toDecimalString());
        $this->assertSame(70.0, $goal->percentage());
    }

    public function test_user_can_update_and_delete_savings_goal(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Target Lama',
            'target_amount' => Money::of('5000000.00'),
        ]);

        // Update
        $editResponse = $this->actingAs($user)->put(route('savings-goals.update', $goal), [
            'name' => 'Target Diperbarui',
            'target_amount' => '8000000.00',
            'status' => 'paused',
            'color' => '#F59E0B',
        ]);

        $editResponse->assertRedirect(route('savings-goals.show', $goal));
        $goal->refresh();
        $this->assertSame('Target Diperbarui', $goal->name);
        $this->assertSame('8000000.00', $goal->target_amount->toDecimalString());
        $this->assertSame(SavingsGoalStatus::Paused, $goal->status);

        // Delete
        $deleteResponse = $this->actingAs($user)->delete(route('savings-goals.destroy', $goal));
        $deleteResponse->assertRedirect(route('savings-goals.index'));
        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
    }

    public function test_user_cannot_access_or_modify_another_users_savings_goal(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $goalA = SavingsGoal::factory()->create(['user_id' => $userA->id]);

        $this->actingAs($userB)->get(route('savings-goals.show', $goalA))->assertForbidden();
        $this->actingAs($userB)->put(route('savings-goals.update', $goalA), [
            'name' => 'Hacked',
            'target_amount' => '1000.00',
            'status' => 'active',
        ])->assertForbidden();
        $this->actingAs($userB)->delete(route('savings-goals.destroy', $goalA))->assertForbidden();
        $this->actingAs($userB)->post(route('savings-goals.entries.store', $goalA), [
            'direction' => 'contribution',
            'amount' => '1000.00',
            'entry_date' => '2026-10-18',
        ])->assertForbidden();
    }
}
