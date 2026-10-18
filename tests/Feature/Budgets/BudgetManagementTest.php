<?php

declare(strict_types=1);

namespace Tests\Feature\Budgets;

use App\Domain\Money\Money;
use App\Enums\BudgetStatus;
use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BudgetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_budgets_module(): void
    {
        $response = $this->get(route('budgets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_monthly_budgets_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('budgets.index'));
        $response->assertOk();
        $response->assertSee('Anggaran Bulanan');
        $response->assertSee('Belum Ada Anggaran');
    }

    public function test_user_can_create_a_budget_for_expense_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Transportasi Harian',
            'type' => CategoryType::Expense,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'period' => '2026-10',
            'limit_amount' => '1500000.00',
            'warning_threshold' => 80,
        ]);

        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'warning_threshold' => 80,
            'status' => 'active',
        ]);

        $savedBudget = Budget::firstOrFail();
        $this->assertSame('2026-10-01', $savedBudget->period_start->format('Y-m-d'));
        $this->assertSame('2026-11-01', $savedBudget->period_end->format('Y-m-d'));
        $this->assertSame('1500000.00', $savedBudget->limit_amount->toDecimalString());
    }

    public function test_cannot_create_budget_for_income_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Gaji Pokok',
            'type' => CategoryType::Income,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'period' => '2026-10',
            'limit_amount' => '1000000.00',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_cannot_create_duplicate_active_budget_for_same_category_and_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'type' => CategoryType::Expense,
        ]);

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'period_start' => '2026-10-01',
            'period_end' => '2026-11-01',
            'status' => BudgetStatus::Active,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'period' => '2026-10',
            'limit_amount' => '2000000.00',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('budgets', 1);
    }

    public function test_budget_usage_and_threshold_warnings_calculate_correctly(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $catFood = Category::factory()->create(['user_id' => $user->id, 'name' => 'Kuliner', 'type' => CategoryType::Expense]);
        $catOther = Category::factory()->create(['user_id' => $user->id, 'name' => 'Hiburan', 'type' => CategoryType::Expense]);

        $startOfMonth = CarbonImmutable::now()->startOfMonth();
        $monthKey = $startOfMonth->format('Y-m');

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $catFood->id,
            'period_start' => $startOfMonth->toDateString(),
            'period_end' => $startOfMonth->addMonthNoOverflow()->toDateString(),
            'limit_amount' => Money::of('1000000.00'),
            'warning_threshold' => 80,
            'status' => BudgetStatus::Active,
        ]);

        // Tx 1: 500.000 current month Kuliner
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catFood->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('500000.00'),
            'transaction_date' => $startOfMonth->addDays(2)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // Tx 2: 200.000 previous month Kuliner (must NOT affect current month)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catFood->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('200000.00'),
            'transaction_date' => $startOfMonth->subMonthNoOverflow()->addDays(2)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // Tx 3: 300.000 current month DIFFERENT category (must NOT affect Kuliner)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catOther->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('300000.00'),
            'transaction_date' => $startOfMonth->addDays(3)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        // 1. Check Safe state (500.000 / 1.000.000 = 50%)
        $response1 = $this->actingAs($user)->get(route('budgets.index', ['month' => $monthKey]));
        $response1->assertOk();
        $response1->assertSee('500.000');
        $response1->assertSee('Aman');

        // Tx 4: Add 350.000 to Kuliner -> Total 850.000 (85% >= 80% threshold -> Warning)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catFood->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('350000.00'),
            'transaction_date' => $startOfMonth->addDays(5)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        $response2 = $this->actingAs($user)->get(route('budgets.index', ['month' => $monthKey]));
        $response2->assertOk();
        $response2->assertSee('850.000');
        $response2->assertSee('Mendekati Batas');

        // Tx 5: Add 200.000 to Kuliner -> Total 1.050.000 (105% > 100% -> Exceeded)
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catFood->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('200000.00'),
            'transaction_date' => $startOfMonth->addDays(10)->toDateString(),
            'status' => TransactionStatus::Posted,
        ]);

        $response3 = $this->actingAs($user)->get(route('budgets.index', ['month' => $monthKey]));
        $response3->assertOk();
        $response3->assertSee('1.050.000');
        $response3->assertSee('Melebihi Batas');
    }

    public function test_user_can_update_budget_limit_and_threshold(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'limit_amount' => Money::of('1000000.00'),
            'warning_threshold' => 80,
        ]);

        $response = $this->actingAs($user)->put(route('budgets.update', $budget), [
            'limit_amount' => '2500000.00',
            'warning_threshold' => 90,
        ]);

        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'limit_amount' => '2500000.00',
            'warning_threshold' => 90,
        ]);
    }

    public function test_user_can_archive_budget(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'status' => BudgetStatus::Active,
        ]);

        $response = $this->actingAs($user)->delete(route('budgets.destroy', $budget));
        $response->assertRedirect(route('budgets.index'));

        $this->assertSame(BudgetStatus::Archived, $budget->fresh()->status);
    }

    public function test_user_cannot_access_or_modify_another_users_budget(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $budgetA = Budget::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB)->put(route('budgets.update', $budgetA), [
            'limit_amount' => '9999999.00',
        ]);
        $response->assertForbidden();

        $deleteResponse = $this->actingAs($userB)->delete(route('budgets.destroy', $budgetA));
        $deleteResponse->assertForbidden();
    }

    public function test_budget_overview_has_zero_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $startOfMonth = CarbonImmutable::now()->startOfMonth();

        // Create 10 different category budgets
        for ($i = 1; $i <= 10; $i++) {
            $cat = Category::factory()->create(['user_id' => $user->id, 'type' => CategoryType::Expense]);
            $budget = Budget::factory()->create([
                'user_id' => $user->id,
                'category_id' => $cat->id,
                'period_start' => $startOfMonth->toDateString(),
                'period_end' => $startOfMonth->addMonthNoOverflow()->toDateString(),
                'status' => BudgetStatus::Active,
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'source_account_id' => $account->id,
                'category_id' => $cat->id,
                'type' => TransactionType::Expense,
                'amount' => Money::of('50000.00'),
                'transaction_date' => $startOfMonth->addDays(1)->toDateString(),
                'status' => TransactionStatus::Posted,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('budgets.index'));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        // Must be less than or equal to 8 queries even with 10 budgets!
        $this->assertLessThanOrEqual(8, $queryCount);
    }
}
