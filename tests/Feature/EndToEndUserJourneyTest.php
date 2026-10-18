<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Money\Money;
use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EndToEndUserJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_flow_from_login_to_dashboard_activity_and_void(): void
    {
        // 1. Provision User
        $user = User::factory()->create([
            'name' => 'Akmal Zulfikar',
            'email' => 'akmal@monetra.test',
        ]);

        // 2. Set up Accounts
        $bankAccount = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Rekening Utama',
            'type' => AccountType::Bank,
            'opening_balance' => Money::zero(),
            'include_in_total_assets' => true,
            'include_in_safe_to_spend' => true,
        ]);

        $cashAccount = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Dompet Fisik',
            'type' => AccountType::Cash,
            'opening_balance' => Money::zero(),
            'include_in_total_assets' => true,
            'include_in_safe_to_spend' => true,
        ]);

        $incomeCat = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Gaji Bulanan',
            'type' => CategoryType::Income,
        ]);

        $expenseCat = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Makan & Minum',
            'type' => CategoryType::Expense,
        ]);

        $today = CarbonImmutable::now();

        // 3. User records income: Gaji Rp 10.000.000
        $incomeResponse = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'income',
            'source_account_id' => $bankAccount->id,
            'category_id' => $incomeCat->id,
            'amount' => '10000000.00',
            'transaction_date' => $today->toDateString(),
            'description' => 'Gaji Perusahaan',
            'client_request_id' => (string) Str::uuid(),
        ]);
        $incomeResponse->assertRedirect(route('transactions.index'));

        // 4. User records expense: Makan Siang Rp 50.000
        $expenseResponse = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'source_account_id' => $bankAccount->id,
            'category_id' => $expenseCat->id,
            'amount' => '50000.00',
            'transaction_date' => $today->toDateString(),
            'description' => 'Makan Siang Resto',
            'client_request_id' => (string) Str::uuid(),
        ]);
        $expenseResponse->assertRedirect(route('transactions.index'));

        // 5. User transfers from BCA to Cash: Rp 500.000
        $transferResponse = $this->actingAs($user)->post(route('transfers.store'), [
            'source_account_id' => $bankAccount->id,
            'destination_account_id' => $cashAccount->id,
            'amount' => '500000.00',
            'transaction_date' => $today->toDateString(),
            'description' => 'Tarik Tunai ATM',
            'client_request_id' => (string) Str::uuid(),
        ]);
        $transferResponse->assertRedirect(route('transactions.index'));

        // 6. User opens Dashboard (Home)
        // Net worth: 10.000.000 - 50.000 = 9.950.000
        // Monthly Income: 10.000.000
        // Monthly Expense: 50.000 (transfer is not expense)
        // Net Cash Flow: +9.950.000
        $dashboardResponse = $this->actingAs($user)->get(route('home'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Halo, Akmal Zulfikar');
        $dashboardResponse->assertSee('9.950.000'); // Total Saldo
        $dashboardResponse->assertSee('10.000.000'); // Pemasukan
        $dashboardResponse->assertSee('50.000'); // Pengeluaran
        $dashboardResponse->assertSee('+Rp 9.950.000'); // Net Cash Flow
        $dashboardResponse->assertSee('Tarik Tunai ATM'); // Recent Transactions

        // 7. User searches and filters Activity
        $activityResponse = $this->actingAs($user)->get(route('transactions.index', ['search' => 'Resto']));
        $activityResponse->assertOk();
        $activityResponse->assertSee('Makan Siang Resto');
        $activityResponse->assertDontSee('Gaji Perusahaan');
        $activityResponse->assertDontSee('Tarik Tunai ATM');

        // 8. User voids the expense transaction
        $expenseTx = Transaction::where('description', 'Makan Siang Resto')->firstOrFail();
        $voidResponse = $this->actingAs($user)->post(route('transactions.void', $expenseTx), [
            'reason' => 'Salah input transaksi pengeluaran oleh kasir',
        ]);
        $voidResponse->assertRedirect(route('transactions.index'));

        $this->assertTrue($expenseTx->fresh()->isVoided());

        // 9. Verify Dashboard after void: expense is 0, Net Worth restored to 10.000.000
        $dashboardAfterVoid = $this->actingAs($user)->get(route('home'));
        $dashboardAfterVoid->assertOk();
        $dashboardAfterVoid->assertSee('10.000.000'); // Restored Total Saldo
        $dashboardAfterVoid->assertDontSee('- Rp 50.000'); // Voided expense not in active metrics
    }
}
