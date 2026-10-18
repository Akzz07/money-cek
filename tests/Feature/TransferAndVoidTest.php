<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Transactions\VoidTransaction;
use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransferAndVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_transfer_between_two_owned_accounts(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->create(['user_id' => $user->id, 'name' => 'BCA Rekening']);
        $dest = Account::factory()->create(['user_id' => $user->id, 'name' => 'Dompet Gopay']);

        $response = $this->actingAs($user)->post(route('transfers.store'), [
            'source_account_id' => $source->id,
            'destination_account_id' => $dest->id,
            'amount' => '150000.00',
            'transaction_date' => now()->toDateString(),
            'description' => 'Topup Gopay dari BCA',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'source_account_id' => $source->id,
            'destination_account_id' => $dest->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => '150000.00',
            'status' => 'posted',
        ]);
    }

    public function test_transfer_with_same_source_and_destination_fails(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('transfers.store'), [
            'source_account_id' => $source->id,
            'destination_account_id' => $source->id,
            'amount' => '10000.00',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['destination_account_id']);
    }

    public function test_transfer_with_archived_account_fails(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->create(['user_id' => $user->id]);
        $destArchived = Account::factory()->create([
            'user_id' => $user->id,
            'status' => AccountStatus::Archived,
        ]);

        $response = $this->actingAs($user)->post(route('transfers.store'), [
            'source_account_id' => $source->id,
            'destination_account_id' => $destArchived->id,
            'amount' => '50000.00',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['destination_account_id']);
    }

    public function test_transfer_between_other_users_accounts_fails(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $sourceA = Account::factory()->create(['user_id' => $userA->id]);
        $destB = Account::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->post(route('transfers.store'), [
            'source_account_id' => $sourceA->id,
            'destination_account_id' => $destB->id,
            'amount' => '50000.00',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['destination_account_id']);
    }

    public function test_user_can_void_posted_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.void', $transaction), [
            'reason' => 'Salah input tanggal',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $fresh = $transaction->fresh();
        $this->assertEquals(TransactionStatus::Voided, $fresh->status);
        $this->assertEquals('Salah input tanggal', $fresh->void_reason);
        $this->assertNotNull($fresh->voided_at);
    }

    public function test_voiding_transaction_records_audit_log(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'amount' => Money::of('80000.00'),
            'status' => TransactionStatus::Posted,
        ]);

        $action = app(VoidTransaction::class);
        $action->handle($user, $transaction, 'Koreksi salah nominal');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'transaction.voided',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);

        $log = AuditLog::where('action', 'transaction.voided')->where('entity_id', $transaction->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Koreksi salah nominal', $log->metadata['void_reason']);
    }

    public function test_double_void_is_idempotent_and_safe(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'status' => TransactionStatus::Posted,
        ]);

        $action = app(VoidTransaction::class);
        $res1 = $action->handle($user, $transaction, 'Pertama kali void');
        $this->assertEquals(TransactionStatus::Voided, $res1->status);

        // Second call should return gracefully without re-logging or throwing
        $res2 = $action->handle($user, $res1, 'Kedua kali void');
        $this->assertEquals(TransactionStatus::Voided, $res2->status);
        $this->assertEquals(1, AuditLog::where('action', 'transaction.voided')->where('entity_id', $transaction->id)->count());
    }

    public function test_user_cannot_void_other_users_transaction(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountB = Account::factory()->create(['user_id' => $userB->id]);
        $transactionB = Transaction::factory()->create([
            'user_id' => $userB->id,
            'source_account_id' => $accountB->id,
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($userA)->post(route('transactions.void', $transactionB), [
            'reason' => 'Mencoba void punya orang',
        ]);

        $response->assertForbidden();
    }
}
