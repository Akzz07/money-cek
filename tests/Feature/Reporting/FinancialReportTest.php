<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Reporting\FinancialReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get(route('assets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_reports_page_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('assets.index'));
        $response->assertOk();
        $response->assertSee('Aset & Laporan', false);
        $response->assertSee('Total Kekayaan Bersih');
        $response->assertSee('Arus Kas Periode');
        $response->assertSee('Tidak ada pengeluaran yang tercatat pada periode ini.');
    }

    public function test_cash_flow_totals_match_income_and_expense_transactions_exactly(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('1000000.00'),
            'status' => AccountStatus::Active,
        ]);

        $currentMonth = CarbonImmutable::now()->startOfMonth();

        // Income: 10.000.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'destination_account_id' => null,
            'type' => TransactionType::Income,
            'amount' => Money::of('10000000.00'),
            'transaction_date' => $currentMonth->addDays(5)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // Expense 1: 3.500.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'destination_account_id' => null,
            'type' => TransactionType::Expense,
            'amount' => Money::of('3500000.00'),
            'transaction_date' => $currentMonth->addDays(10)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // Expense 2: 1.500.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'destination_account_id' => null,
            'type' => TransactionType::Expense,
            'amount' => Money::of('1500000.00'),
            'transaction_date' => $currentMonth->addDays(12)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        $service = app(FinancialReportService::class);
        $report = $service->getReport($user, 'this_month');

        $this->assertSame('10000000.00', $report->totalIncome->toDecimalString());
        $this->assertSame('5000000.00', $report->totalExpense->toDecimalString());
        $this->assertSame('5000000.00', $report->netCashFlow->toDecimalString());
        $this->assertSame(50.0, $report->savingsRate);
    }

    public function test_transfers_and_voided_transactions_do_not_inflate_cash_flow(): void
    {
        $user = User::factory()->create();
        $accountA = Account::factory()->create(['user_id' => $user->id]);
        $accountB = Account::factory()->create(['user_id' => $user->id]);

        $currentMonth = CarbonImmutable::now()->startOfMonth();

        // 1. Real Expense: 1.000.000
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountA->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('1000000.00'),
            'transaction_date' => $currentMonth->addDays(2)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // 2. Internal Transfer: 50.000.000 (NOT an income or expense!)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountA->id,
            'destination_account_id' => $accountB->id,
            'type' => TransactionType::Transfer,
            'amount' => Money::of('50000000.00'),
            'transaction_date' => $currentMonth->addDays(3)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // 3. Voided Expense: 5.000.000 (MUST be ignored)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountA->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('5000000.00'),
            'transaction_date' => $currentMonth->addDays(4)->toDateString(),
            'status' => TransactionStatus::Voided,
        ]);

        $service = app(FinancialReportService::class);
        $report = $service->getReport($user, 'this_month');

        $this->assertSame('0.00', $report->totalIncome->toDecimalString());
        $this->assertSame('1000000.00', $report->totalExpense->toDecimalString());
        $this->assertSame('-1000000.00', $report->netCashFlow->toDecimalString());
    }

    public function test_category_breakdown_orders_categories_by_spending_and_calculates_percentages(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $catFood = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Makanan & Minuman',
            'type' => CategoryType::Expense,
            'color' => '#F59E0B',
        ]);

        $catTransport = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Transportasi',
            'type' => CategoryType::Expense,
            'color' => '#3B82F6',
        ]);

        $currentMonth = CarbonImmutable::now()->startOfMonth();

        // 6.000.000 for Food (60%)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catFood->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('6000000.00'),
            'transaction_date' => $currentMonth->addDays(5)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // 4.000.000 for Transport (40%)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catTransport->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('4000000.00'),
            'transaction_date' => $currentMonth->addDays(7)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        $service = app(FinancialReportService::class);
        $report = $service->getReport($user, 'this_month');

        $this->assertCount(2, $report->categoryBreakdown);

        $first = $report->categoryBreakdown->first();
        $this->assertSame('Makanan & Minuman', $first->categoryName);
        $this->assertSame('6000000.00', $first->amount->toDecimalString());
        $this->assertSame(60.0, $first->percentage);

        $second = $report->categoryBreakdown->last();
        $this->assertSame('Transportasi', $second->categoryName);
        $this->assertSame('4000000.00', $second->amount->toDecimalString());
        $this->assertSame(40.0, $second->percentage);
    }

    public function test_net_worth_sums_only_active_accounts_with_include_in_total_assets(): void
    {
        $user = User::factory()->create();

        // 1. Bank Utama: 15.000.000 (Included)
        Account::factory()->create([
            'user_id' => $user->id,
            'type' => AccountType::Bank,
            'opening_balance' => Money::of('15000000.00'),
            'include_in_total_assets' => true,
            'status' => AccountStatus::Active,
        ]);

        // 2. Investasi: 30.000.000 (Included)
        Account::factory()->create([
            'user_id' => $user->id,
            'type' => AccountType::Investment,
            'opening_balance' => Money::of('30000000.00'),
            'include_in_total_assets' => true,
            'status' => AccountStatus::Active,
        ]);

        // 3. Akun Tersembunyi (Excluded from total assets): 5.000.000
        Account::factory()->create([
            'user_id' => $user->id,
            'type' => AccountType::Other,
            'opening_balance' => Money::of('5000000.00'),
            'include_in_total_assets' => false,
            'status' => AccountStatus::Active,
        ]);

        $service = app(FinancialReportService::class);
        $report = $service->getReport($user, 'this_month');

        // Net worth must be 15 + 30 = 45.000.000 (excluding 5.000.000)
        $this->assertSame('45000000.00', $report->totalNetWorth->toDecimalString());
    }
}
