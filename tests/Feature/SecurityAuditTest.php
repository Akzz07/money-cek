<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\TransactionFilter;
use App\Enums\AccountType;
use App\Enums\BillStatus;
use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Transactions\TransactionQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_or_modify_another_users_account(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobsAccount = Account::factory()->for($bob)->create([
            'name' => "Bob's Secret Vault",
        ]);

        $this->actingAs($alice)->get("/accounts/{$bobsAccount->id}/edit")->assertForbidden();

        $this->actingAs($alice)->put("/accounts/{$bobsAccount->id}", [
            'name' => 'Hacked Name',
            'type' => AccountType::Bank->value,
            'currency' => 'IDR',
            'initial_balance' => '50000',
        ])->assertForbidden();

        $this->actingAs($alice)->delete("/accounts/{$bobsAccount->id}")->assertForbidden();

        $this->actingAs($alice)->post("/accounts/{$bobsAccount->id}/reconcile", [
            'real_balance' => '0',
            'reason' => 'Unauthorized reconciliation attempt',
        ])->assertForbidden();
    }

    public function test_user_cannot_access_or_modify_another_users_category(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobsCategory = Category::factory()->for($bob)->create([
            'name' => "Bob's Category",
            'is_system' => false,
        ]);

        $this->actingAs($alice)->get("/categories/{$bobsCategory->id}/edit")->assertForbidden();

        $this->actingAs($alice)->put("/categories/{$bobsCategory->id}", [
            'name' => 'Altered Category',
            'type' => CategoryType::Expense->value,
            'color' => '#10B981',
            'icon' => 'tag',
        ])->assertForbidden();

        $this->actingAs($alice)->delete("/categories/{$bobsCategory->id}")->assertForbidden();
    }

    public function test_user_cannot_void_another_users_transaction(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobsAccount = Account::factory()->for($bob)->create();
        $bobsCategory = Category::factory()->for($bob)->create();

        $bobsTransaction = Transaction::factory()->for($bob)->create([
            'source_account_id' => $bobsAccount->id,
            'category_id' => $bobsCategory->id,
            'destination_account_id' => null,
            'status' => TransactionStatus::Posted,
        ]);

        $this->actingAs($alice)->post("/transactions/{$bobsTransaction->id}/void", [
            'reason' => 'Unauthorized void attempt by Alice',
        ])->assertForbidden();

        $bobsTransaction->refresh();
        $this->assertSame(TransactionStatus::Posted, $bobsTransaction->status);
    }

    public function test_user_cannot_access_or_modify_another_users_budget(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobsCategory = Category::factory()->for($bob)->create(['type' => CategoryType::Expense]);
        $bobsBudget = Budget::create([
            'user_id' => $bob->id,
            'category_id' => $bobsCategory->id,
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'limit_amount' => '3000000.00',
            'warning_threshold' => 80,
            'status' => 'active',
        ]);

        $this->actingAs($alice)->get("/budgets/{$bobsBudget->id}/edit")->assertForbidden();

        $this->actingAs($alice)->put("/budgets/{$bobsBudget->id}", [
            'limit_amount' => '1000000',
            'warning_threshold' => 70,
        ])->assertForbidden();

        $this->actingAs($alice)->delete("/budgets/{$bobsBudget->id}")->assertForbidden();
    }

    public function test_user_cannot_access_or_modify_another_users_bill(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobsCategory = Category::factory()->for($bob)->create(['type' => CategoryType::Expense]);
        $bobsBill = Bill::create([
            'user_id' => $bob->id,
            'category_id' => $bobsCategory->id,
            'name' => "Bob's Electricity Bill",
            'amount' => '500000.00',
            'due_date' => '2026-10-25',
            'status' => BillStatus::Upcoming,
        ]);

        $this->actingAs($alice)->get("/bills/{$bobsBill->id}/edit")->assertForbidden();

        $this->actingAs($alice)->put("/bills/{$bobsBill->id}", [
            'category_id' => $bobsCategory->id,
            'name' => 'Hacked Bill Name',
            'amount' => '999999',
            'due_date' => '2026-10-28',
            'reminder_days_before' => 3,
        ])->assertForbidden();

        $this->actingAs($alice)->post("/bills/{$bobsBill->id}/pay", [
            'source_account_id' => 9999,
        ])->assertForbidden();

        $this->actingAs($alice)->post("/bills/{$bobsBill->id}/cancel-payment")->assertForbidden();
        $this->actingAs($alice)->post("/bills/{$bobsBill->id}/skip")->assertForbidden();
        $this->actingAs($alice)->delete("/bills/{$bobsBill->id}")->assertForbidden();
    }

    public function test_user_cannot_access_or_modify_another_users_savings_goal(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $bobsGoal = SavingsGoal::create([
            'user_id' => $bob->id,
            'name' => "Bob's Dream Car",
            'target_amount' => '200000000.00',
            'status' => 'active',
        ]);

        $this->actingAs($alice)->get("/savings-goals/{$bobsGoal->id}")->assertForbidden();
        $this->actingAs($alice)->get("/savings-goals/{$bobsGoal->id}/edit")->assertForbidden();

        $this->actingAs($alice)->put("/savings-goals/{$bobsGoal->id}", [
            'name' => 'Hacked Goal Name',
            'target_amount' => '1000',
            'status' => 'active',
        ])->assertForbidden();

        $this->actingAs($alice)->delete("/savings-goals/{$bobsGoal->id}")->assertForbidden();

        $this->actingAs($alice)->post("/savings-goals/{$bobsGoal->id}/entries", [
            'direction' => 'contribution',
            'amount' => '50000',
            'entry_date' => '2026-10-18',
        ])->assertForbidden();
    }

    public function test_login_endpoint_locks_out_after_5_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors('email');
        }

        // 6th attempt must be locked out (HTTP 429 Too Many Requests)
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_csv_export_endpoint_throttles_after_10_requests_per_minute(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->get('/transactions/export');
            $response->assertOk();
        }

        // 11th request must receive 429 Too Many Requests
        $response = $this->actingAs($user)->get('/transactions/export');
        $response->assertStatus(429);
    }

    public function test_search_sanitizes_sql_like_wildcard_characters(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        // Create transaction with literal % character
        Transaction::factory()->for($user)->create([
            'source_account_id' => $account->id,
            'description' => 'Discount 50% Off Promo',
            'transaction_date' => CarbonImmutable::now(),
            'status' => TransactionStatus::Posted,
        ]);

        // Create another transaction with word Fifty
        Transaction::factory()->for($user)->create([
            'source_account_id' => $account->id,
            'description' => 'Discount Fifty Off Promo',
            'transaction_date' => CarbonImmutable::now(),
            'status' => TransactionStatus::Posted,
        ]);

        $queryService = app(TransactionQueryService::class);

        // Searching for "50%" should ONLY match the transaction with literal %
        $results = $queryService->paginate($user, new TransactionFilter(search: '50%'));
        $this->assertCount(1, $results);
        $this->assertSame('Discount 50% Off Promo', $results->first()->description);
    }

    public function test_blade_views_escape_html_and_xss_payloads(): void
    {
        $user = User::factory()->create();
        $xssPayload = "<script>alert('xss-attack')</script>";

        $account = Account::factory()->for($user)->create([
            'name' => $xssPayload,
        ]);

        $response = $this->actingAs($user)->get('/accounts');
        $response->assertOk();

        // Raw unescaped script tag MUST NOT appear in the response HTML
        $response->assertDontSee($xssPayload, false);

        // Escaped HTML entities MUST appear
        $response->assertSee(e($xssPayload), false);
    }

    public function test_application_serves_security_headers_and_health_check(): void
    {
        // 1. Health check endpoint
        $healthResponse = $this->get('/up');
        $healthResponse->assertOk();

        // 2. Security headers
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
