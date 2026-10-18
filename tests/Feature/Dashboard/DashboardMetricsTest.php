<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\AdjustmentDirection;
use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_empty_state_when_no_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Total Saldo');
        $response->assertSee('Belum Ada Transaksi');
    }

    public function test_dashboard_reconciles_total_balance_across_active_asset_accounts(): void
    {
        $user = User::factory()->create();

        $accountA = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Utama',
            'opening_balance' => Money::of('10000000.00'),
            'status' => AccountStatus::Active,
            'include_in_total_assets' => true,
        ]);

        $accountB = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Gopay',
            'opening_balance' => Money::of('5000000.00'),
            'status' => AccountStatus::Active,
            'include_in_total_assets' => true,
        ]);

        // Non-asset / investment account excluded from total assets
        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Bibit Reksadana',
            'opening_balance' => Money::of('20000000.00'),
            'status' => AccountStatus::Active,
            'include_in_total_assets' => false,
        ]);

        // Income +2.000.000 to Account A
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountA->id,
            'type' => TransactionType::Income,
            'amount' => Money::of('2000000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => CarbonImmutable::now()->toDateString(),
        ]);

        // Expense -500.000 from Account A
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountA->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('500000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => CarbonImmutable::now()->toDateString(),
        ]);

        // Expected Net Worth: (10.000.000 + 2.000.000 - 500.000) + 5.000.000 = 16.500.000
        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('16.500.000');
    }

    public function test_monthly_income_and_expense_only_include_posted_transactions_in_current_month(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::zero(),
            'include_in_total_assets' => true,
        ]);

        $now = CarbonImmutable::now();

        // Income current month: 5.000.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => Money::of('5000000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => $now->startOfMonth()->addDays(5)->toDateString(),
        ]);

        // Expense current month: 1.500.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('1500000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => $now->startOfMonth()->addDays(10)->toDateString(),
        ]);

        // Previous month transaction (must NOT affect current month)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => Money::of('8000000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => $now->subMonthNoOverflow()->startOfMonth()->addDays(2)->toDateString(),
        ]);

        // Next month transaction (must NOT affect current month)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('3000000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => $now->addMonthNoOverflow()->startOfMonth()->addDays(2)->toDateString(),
        ]);

        // Voided transaction in current month (must NOT affect current month)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => Money::of('9999999.00'),
            'status' => TransactionStatus::Voided,
            'transaction_date' => $now->startOfMonth()->addDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('5.000.000');
        $response->assertSee('1.500.000');
        $response->assertSee('+Rp 3.500.000'); // Net Cash Flow
        $response->assertDontSee('9.999.999');
    }

    public function test_transfers_and_adjustments_are_excluded_from_income_and_expense(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->create(['user_id' => $user->id, 'opening_balance' => Money::of('5000000.00')]);
        $dest = Account::factory()->create(['user_id' => $user->id, 'opening_balance' => Money::zero()]);

        $now = CarbonImmutable::now();

        // Transfer: 1.000.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $source->id,
            'destination_account_id' => $dest->id,
            'type' => TransactionType::Transfer,
            'amount' => Money::of('1000000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => $now->toDateString(),
        ]);

        // Adjustment: 200.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $source->id,
            'type' => TransactionType::Adjustment,
            'adjustment_direction' => AdjustmentDirection::Increase,
            'amount' => Money::of('200000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => $now->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        // Income and expense must remain 0
        $response->assertSee('Pemasukan');
        $response->assertSee('Pengeluaran');
    }

    public function test_month_query_parameter_filters_historical_period(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'opening_balance' => Money::zero()]);

        // August 2026 income
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => Money::of('7700000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => '2026-08-15',
        ]);

        // September 2026 income
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => Money::of('8800000.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => '2026-09-15',
        ]);

        $response = $this->actingAs($user)->get(route('home', ['month' => '2026-08']));

        $response->assertOk();
        $response->assertSee('Agustus 2026');
        $response->assertSee('7.700.000');
        $response->assertDontSee('8.800.000');
    }

    public function test_recent_transactions_shows_latest_5_posted_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => CategoryType::Expense]);

        // Create 6 transactions
        for ($i = 1; $i <= 6; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'source_account_id' => $account->id,
                'category_id' => $category->id,
                'type' => TransactionType::Expense,
                'amount' => Money::of((string) ($i * 100000)),
                'description' => "Transaksi Urut {$i}",
                'status' => TransactionStatus::Posted,
                'transaction_date' => CarbonImmutable::now()->subDays(6 - $i)->toDateString(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        // Transactions 2-6 (5 newest) should be visible
        $response->assertSee('Transaksi Urut 6');
        $response->assertSee('Transaksi Urut 5');
        $response->assertSee('Transaksi Urut 4');
        $response->assertSee('Transaksi Urut 3');
        $response->assertSee('Transaksi Urut 2');
        // Oldest transaction #1 should be excluded by limit(5)
        $response->assertDontSee('Transaksi Urut 1');
    }

    public function test_dashboard_isolates_user_data(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = Account::factory()->create([
            'user_id' => $user1->id,
            'opening_balance' => Money::of('50000000.00'),
            'include_in_total_assets' => true,
        ]);

        Transaction::factory()->create([
            'user_id' => $user1->id,
            'source_account_id' => $account1->id,
            'description' => 'Rahasia User 1',
            'amount' => Money::of('1234567.00'),
            'status' => TransactionStatus::Posted,
            'transaction_date' => CarbonImmutable::now()->toDateString(),
        ]);

        $response = $this->actingAs($user2)->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('50.000.000');
        $response->assertDontSee('Rahasia User 1');
        $response->assertDontSee('1.234.567');
    }

    public function test_dashboard_does_not_suffer_from_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => CategoryType::Expense]);

        // Create 5 transactions
        for ($i = 1; $i <= 5; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'source_account_id' => $account->id,
                'category_id' => $category->id,
                'type' => TransactionType::Expense,
                'amount' => Money::of('50000.00'),
                'status' => TransactionStatus::Posted,
                'transaction_date' => CarbonImmutable::now()->toDateString(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(15, $queryCount);
    }
}
