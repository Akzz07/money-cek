<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Bills\CreateBill;
use App\Actions\Bills\PayBill;
use App\Actions\Budgets\CreateBudget;
use App\Actions\SavingsGoals\AddSavingsGoalEntry;
use App\Actions\SavingsGoals\CreateSavingsGoal;
use App\Actions\Transactions\PostTransaction;
use App\Actions\Transactions\PostTransfer;
use App\Domain\Money\Money;
use App\DTOs\AccountData;
use App\DTOs\BillData;
use App\DTOs\BudgetData;
use App\DTOs\SavingsGoalData;
use App\DTOs\TransactionData;
use App\DTOs\TransferData;
use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\SavingsGoalEntryDirection;
use App\Enums\SavingsGoalStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Services\Accounts\AccountBalanceService;
use App\Services\Budgets\BudgetUsageService;
use App\Services\Reporting\FinancialReportService;
use App\Services\SafeToSpend\SafeToSpendService;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MvpAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_e2e_financial_lifecycle_according_to_prd_lampiran_a(): void
    {
        // 1. Provisioning & Login
        $this->seed(CategorySeeder::class);

        $user = User::factory()->admin()->create([
            'email' => 'owner@monetra.test',
            'name' => 'Akmal Monetra Owner',
            'timezone' => 'Asia/Jakarta',
            'payday_day' => 25,
        ]);

        $this->actingAs($user);

        // Public registration disabled:
        $this->get('/register')->assertNotFound();

        // 2. Create 3 Accounts (Bank BCA, GoPay, Tunai)
        $createAccount = app(CreateAccount::class);
        $today = CarbonImmutable::now('Asia/Jakarta');

        $bca = $createAccount->handle($user, new AccountData(
            name: 'Bank BCA',
            type: AccountType::Bank,
            accountNumber: '1234567890',
            institutionName: 'BCA',
            openingBalance: Money::of('10000000.00'),
            openingDate: $today,
            includeInTotalAssets: true,
            includeInSafeToSpend: true,
        ));

        $gopay = $createAccount->handle($user, new AccountData(
            name: 'GoPay',
            type: AccountType::EWallet,
            accountNumber: '08123456789',
            institutionName: 'GoTo',
            openingBalance: Money::of('500000.00'),
            openingDate: $today,
            includeInTotalAssets: true,
            includeInSafeToSpend: true,
        ));

        $cash = $createAccount->handle($user, new AccountData(
            name: 'Uang Tunai Dompet',
            type: AccountType::Cash,
            accountNumber: null,
            institutionName: null,
            openingBalance: Money::of('300000.00'),
            openingDate: $today,
            includeInTotalAssets: true,
            includeInSafeToSpend: true,
        ));

        // 3. Verify Initial Balances & Net Worth
        $balanceService = app(AccountBalanceService::class);
        $totalNetWorth = $balanceService->getTotalNetWorth($user);
        $this->assertSame('10800000.00', $totalNetWorth->toDecimalString());

        // 4. Record Income: Gaji Rp 15.000.000 -> BCA balance increases
        $salaryCategory = Category::forUser($user, CategoryType::Income)->firstOrFail();
        $postTransaction = app(PostTransaction::class);

        $now = CarbonImmutable::now('Asia/Jakarta');
        $postTransaction->handle($user, new TransactionData(
            type: TransactionType::Income,
            amount: Money::of('15000000.00'),
            transactionDate: $now,
            sourceAccountId: $bca->id,
            categoryId: $salaryCategory->id,
            description: 'Gaji Bulanan',
        ));

        $bcaBalance = $balanceService->getBalance($bca);
        $this->assertSame('25000000.00', $bcaBalance->toDecimalString());

        // 5. Create Budget & Record Expense: Makan Rp 200.000 -> GoPay decreases, Budget moves
        $foodCategory = Category::forUser($user, CategoryType::Expense)->firstOrFail();
        $createBudget = app(CreateBudget::class);

        $budget = $createBudget->handle($user, new BudgetData(
            categoryId: $foodCategory->id,
            periodStart: $now->startOfMonth(),
            limitAmount: Money::of('2000000.00'),
            warningThreshold: 80,
        ));

        $postTransaction->handle($user, new TransactionData(
            type: TransactionType::Expense,
            amount: Money::of('200000.00'),
            transactionDate: $now,
            sourceAccountId: $gopay->id,
            categoryId: $foodCategory->id,
            description: 'Makan Siang Resto',
        ));

        $gopayBalance = $balanceService->getBalance($gopay);
        $this->assertSame('300000.00', $gopayBalance->toDecimalString());

        $budgetService = app(BudgetUsageService::class);
        $overview = $budgetService->getMonthlyBudgetOverview($user, $now->format('Y-m'));
        $this->assertSame('200000.00', $overview->totalSpent->toDecimalString());

        // 6. Record Atomic Transfer: BCA Rp 1.000.000 -> GoPay
        $postTransfer = app(PostTransfer::class);
        $postTransfer->handle($user, new TransferData(
            amount: Money::of('1000000.00'),
            transactionDate: $now,
            sourceAccountId: $bca->id,
            destinationAccountId: $gopay->id,
            description: 'Topup GoPay dari BCA',
        ));

        $this->assertSame('24000000.00', $balanceService->getBalance($bca)->toDecimalString());
        $this->assertSame('1300000.00', $balanceService->getBalance($gopay)->toDecimalString());
        // Net worth remains 25.600.000 (10.8m + 15m - 0.2m = 25.6m)
        $this->assertSame('25600000.00', $balanceService->getTotalNetWorth($user)->toDecimalString());

        // 7. Create Upcoming Bill: Listrik Rp 500.000
        $createBill = app(CreateBill::class);
        $bill = $createBill->handle($user, new BillData(
            name: 'Tagihan Listrik PLN',
            amount: Money::of('500000.00'),
            dueDate: $now->addDays(3),
            categoryId: $foodCategory->id,
            notes: 'Tagihan rutin bulanan',
        ));

        // 8. Pay Bill from BCA -> Creates linked transaction, marks bill paid
        $payBill = app(PayBill::class);
        $payBill->handle($user, $bill, $bca, 'Bayar via m-BCA');
        $bill->refresh();
        $this->assertTrue($bill->isPaid());
        $this->assertNotNull($bill->paid_transaction_id);

        $this->assertSame('23500000.00', $balanceService->getBalance($bca)->toDecimalString());

        // 9. Create Savings Goal & Deposit
        $createGoal = app(CreateSavingsGoal::class);
        $goal = $createGoal->handle($user, new SavingsGoalData(
            name: 'Dana Darurat 6 Bulan',
            targetAmount: Money::of('30000000.00'),
            targetDate: $now->addMonths(6),
            status: SavingsGoalStatus::Active,
            color: '#10B981',
            icon: 'shield-check',
        ));

        $addEntry = app(AddSavingsGoalEntry::class);
        $addEntry->handle(
            user: $user,
            goal: $goal,
            direction: SavingsGoalEntryDirection::Contribution,
            amount: Money::of('5000000.00'),
            entryDate: $now,
            notes: 'Alokasi tabungan awal',
        );

        // 10. Compute Safe-to-Spend
        $safeToSpendService = app(SafeToSpendService::class);
        $safeResult = $safeToSpendService->calculate($user);
        $this->assertNotNull($safeResult->safeToSpend);
        $this->assertNotEmpty($safeResult->assumptions);

        // 11. View Reports & Analytics
        $reportService = app(FinancialReportService::class);
        $report = $reportService->getReport($user, 'this_month');
        $this->assertSame('15000000.00', $report->totalIncome->toDecimalString());
        // 200k food + 500k bill = 700k
        $this->assertSame('700000.00', $report->totalExpense->toDecimalString());
        $this->assertSame('14300000.00', $report->netCashFlow->toDecimalString());

        // 12. Export CSV (Formula Injection prevention check)
        $csvResponse = $this->get('/transactions/export');
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('content-type'));

        // 13. Admin Panel Protected Access
        $this->get('/admin')->assertOk();
        $this->get('/admin/transactions')->assertOk();
        $this->get('/admin/accounts')->assertOk();
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/audit-logs')->assertOk();

        // Unauthorized user blocked from admin
        $stranger = User::factory()->create(['is_admin' => false]);
        $this->actingAs($stranger)->get('/admin')->assertForbidden();
    }
}
