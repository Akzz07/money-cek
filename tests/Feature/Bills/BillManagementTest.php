<?php

declare(strict_types=1);

namespace Tests\Feature\Bills;

use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BillManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_bills_module(): void
    {
        $response = $this->get(route('bills.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_bills_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('bills.index'));
        $response->assertOk();
        $response->assertSee('Tagihan & Kewajiban', false);
        $response->assertSee('Belum Ada Tagihan');
    }

    public function test_user_can_create_a_bill(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Utilitas',
            'type' => CategoryType::Expense,
        ]);

        $response = $this->actingAs($user)->post(route('bills.store'), [
            'name' => 'Listrik PLN Pasca',
            'amount' => '450000.00',
            'due_date' => CarbonImmutable::now()->addDays(7)->toDateString(),
            'category_id' => $category->id,
            'notes' => 'ID Pelanggan: 5123456789',
        ]);

        $response->assertRedirect(route('bills.index'));
        $this->assertDatabaseHas('bills', [
            'user_id' => $user->id,
            'name' => 'Listrik PLN Pasca',
            'category_id' => $category->id,
            'status' => 'upcoming',
        ]);

        $saved = Bill::firstOrFail();
        $this->assertSame('450000.00', $saved->amount->toDecimalString());
    }

    public function test_overdue_status_is_derived_dynamically_based_on_due_date(): void
    {
        $user = User::factory()->create();

        // 1. Bill past due
        $overdueBill = Bill::factory()->create([
            'user_id' => $user->id,
            'name' => 'Wifi Kantor',
            'amount' => Money::of('300000.00'),
            'due_date' => CarbonImmutable::now()->subDays(2)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);

        // 2. Bill future due
        $upcomingBill = Bill::factory()->create([
            'user_id' => $user->id,
            'name' => 'Air PDAM',
            'amount' => Money::of('100000.00'),
            'due_date' => CarbonImmutable::now()->addDays(5)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);

        $this->assertTrue($overdueBill->isOverdue());
        $this->assertSame('Terlambat', $overdueBill->displayStatusLabel());

        $this->assertFalse($upcomingBill->isOverdue());
        $this->assertSame('Mendatang', $upcomingBill->displayStatusLabel());

        $response = $this->actingAs($user)->get(route('bills.index'));
        $response->assertOk();
        $response->assertSee('Wifi Kantor');
        $response->assertSee('Terlambat');
    }

    public function test_user_can_pay_a_bill_atomically_creating_expense_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'opening_balance' => Money::of('1000000.00'),
            'status' => AccountStatus::Active,
        ]);

        $bill = Bill::factory()->create([
            'user_id' => $user->id,
            'name' => 'Internet Kos',
            'amount' => Money::of('350000.00'),
            'status' => BillStatus::Upcoming,
        ]);

        $response = $this->actingAs($user)->post(route('bills.pay', $bill), [
            'source_account_id' => $account->id,
            'notes' => 'Dibayar via m-BCA',
        ]);

        $response->assertRedirect(route('bills.index'));

        $bill->refresh();
        $this->assertSame(BillStatus::Paid, $bill->status);
        $this->assertNotNull($bill->paid_transaction_id);

        // Verify linked transaction
        $transaction = Transaction::findOrFail($bill->paid_transaction_id);
        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('350000.00', $transaction->amount->toDecimalString());
        $this->assertSame($account->id, $transaction->source_account_id);
        $this->assertSame('Pembayaran Tagihan: Internet Kos', $transaction->description);
        $this->assertSame(TransactionStatus::Posted, $transaction->status);

        // Verify balance decreased via AccountBalanceService
        $balanceService = app(AccountBalanceService::class);
        $currentBalance = $balanceService->getBalance($account);
        $this->assertSame('650000.00', $currentBalance->toDecimalString());
    }

    public function test_bill_cannot_be_paid_twice(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('1000000.00'),
            'status' => AccountStatus::Active,
        ]);

        $bill = Bill::factory()->create([
            'user_id' => $user->id,
            'status' => BillStatus::Upcoming,
            'amount' => Money::of('100000.00'),
        ]);

        // First payment succeeds
        $this->actingAs($user)->post(route('bills.pay', $bill), [
            'source_account_id' => $account->id,
        ]);

        // Second payment attempt is rejected
        $response = $this->actingAs($user)->post(route('bills.pay', $bill), [
            'source_account_id' => $account->id,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_cancelling_bill_payment_voids_the_linked_transaction_and_restores_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('1000000.00'),
            'status' => AccountStatus::Active,
        ]);

        $bill = Bill::factory()->create([
            'user_id' => $user->id,
            'name' => 'Langganan TV Kabel',
            'amount' => Money::of('250000.00'),
            'status' => BillStatus::Upcoming,
        ]);

        // Pay first
        $this->actingAs($user)->post(route('bills.pay', $bill), [
            'source_account_id' => $account->id,
        ]);

        $bill->refresh();
        $txId = $bill->paid_transaction_id;
        $this->assertNotNull($txId);

        $balanceService = app(AccountBalanceService::class);
        $this->assertSame('750000.00', $balanceService->getBalance($account)->toDecimalString());

        // Now cancel payment
        $cancelResponse = $this->actingAs($user)->post(route('bills.cancel-payment', $bill));
        $cancelResponse->assertRedirect(route('bills.index'));

        $bill->refresh();
        $this->assertSame(BillStatus::Upcoming, $bill->status);
        $this->assertNull($bill->paid_transaction_id);

        // Transaction is voided
        $transaction = Transaction::findOrFail($txId);
        $this->assertSame(TransactionStatus::Voided, $transaction->status);

        // Balance is fully restored to 1.000.000
        $this->assertSame('1000000.00', $balanceService->getBalance($account)->toDecimalString());
    }

    public function test_user_can_skip_a_bill(): void
    {
        $user = User::factory()->create();
        $bill = Bill::factory()->create([
            'user_id' => $user->id,
            'status' => BillStatus::Upcoming,
        ]);

        $response = $this->actingAs($user)->post(route('bills.skip', $bill));
        $response->assertRedirect(route('bills.index'));

        $this->assertSame(BillStatus::Skipped, $bill->fresh()->status);
    }

    public function test_user_cannot_pay_or_modify_another_users_bill(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountB = Account::factory()->create(['user_id' => $userB->id]);
        $billA = Bill::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB)->post(route('bills.pay', $billA), [
            'source_account_id' => $accountB->id,
        ]);
        $response->assertForbidden();

        $editResponse = $this->actingAs($userB)->put(route('bills.update', $billA), [
            'name' => 'Hacked',
            'amount' => '1000.00',
            'due_date' => '2026-10-25',
        ]);
        $editResponse->assertForbidden();
    }
}
