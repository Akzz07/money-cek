<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Transactions\PostTransaction;
use App\Domain\Money\Money;
use App\DTOs\TransactionData;
use App\Enums\AccountStatus;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PostTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('transactions.index'));
        $response->assertRedirect(route('login'));

        $responsePost = $this->post(route('transactions.store'), []);
        $responsePost->assertRedirect(route('login'));
    }

    public function test_user_can_record_expense_transaction(): void
    {
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::where('is_system', true)->where('type', CategoryType::Expense)->first();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '45000.00',
            'transaction_date' => now()->toDateString(),
            'description' => 'Makan siang bebek sinjay',
            'notes' => 'Bersama teman kantor',
            'client_request_id' => (string) Str::uuid(),
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '45000.00',
            'description' => 'Makan siang bebek sinjay',
            'status' => 'posted',
        ]);
    }

    public function test_user_can_record_income_transaction(): void
    {
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::where('is_system', true)->where('type', CategoryType::Income)->first();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'income',
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '5000000.00',
            'transaction_date' => now()->toDateString(),
            'description' => 'Gaji bulanan',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => '5000000.00',
            'status' => 'posted',
        ]);
    }

    public function test_amount_zero_or_negative_is_rejected(): void
    {
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::where('is_system', true)->where('type', CategoryType::Expense)->first();

        $responseZero = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '0',
            'transaction_date' => now()->toDateString(),
        ]);
        $responseZero->assertSessionHasErrors(['amount']);

        $responseNegative = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '-1000',
            'transaction_date' => now()->toDateString(),
        ]);
        $responseNegative->assertSessionHasErrors(['amount']);
    }

    public function test_category_type_mismatch_is_rejected_by_action(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $expenseCategory = Category::factory()->expense()->create(['user_id' => $user->id]);

        $action = app(PostTransaction::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Kategori tidak sesuai dengan jenis transaksi.');

        $action->handle($user, new TransactionData(
            type: TransactionType::Income, // mismatch with expenseCategory
            sourceAccountId: $account->id,
            amount: Money::of('100000.00'),
            transactionDate: CarbonImmutable::now(),
            categoryId: $expenseCategory->id,
        ));
    }

    public function test_transaction_cannot_be_posted_to_archived_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'status' => AccountStatus::Archived,
        ]);
        $category = Category::factory()->expense()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'type' => 'expense',
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '50000.00',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['source_account_id']);
    }

    public function test_transaction_cannot_be_posted_to_other_users_account(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $accountB = Account::factory()->create(['user_id' => $userB->id]);
        $category = Category::factory()->expense()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userA)->post(route('transactions.store'), [
            'type' => 'expense',
            'source_account_id' => $accountB->id,
            'category_id' => $category->id,
            'amount' => '50000.00',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['source_account_id']);
    }

    public function test_posting_transaction_dispatches_event_and_records_audit_log(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->expense()->create(['user_id' => $user->id]);

        $clientRequestId = (string) Str::uuid();

        $action = app(PostTransaction::class);
        $transaction = $action->handle($user, new TransactionData(
            type: TransactionType::Expense,
            sourceAccountId: $account->id,
            amount: Money::of('75000.00'),
            transactionDate: CarbonImmutable::now(),
            categoryId: $category->id,
            description: 'Audit log check',
            clientRequestId: $clientRequestId,
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'transaction.posted',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
            'request_id' => $clientRequestId,
        ]);

        $auditLog = AuditLog::where('entity_id', $transaction->id)->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('75000.00', $auditLog->metadata['amount']);
        $this->assertEquals('expense', $auditLog->metadata['type']);
    }

    public function test_idempotent_submission_with_same_client_request_id_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->expense()->create(['user_id' => $user->id]);

        $clientRequestId = (string) Str::uuid();

        $action = app(PostTransaction::class);

        $data = new TransactionData(
            type: TransactionType::Expense,
            sourceAccountId: $account->id,
            amount: Money::of('120000.00'),
            transactionDate: CarbonImmutable::now(),
            categoryId: $category->id,
            description: 'Belanja bulanan',
            clientRequestId: $clientRequestId,
        );

        $tx1 = $action->handle($user, $data);
        $tx2 = $action->handle($user, $data);

        $this->assertEquals($tx1->id, $tx2->id);
        $this->assertEquals(1, Transaction::where('client_request_id', $clientRequestId)->count());
    }
}
