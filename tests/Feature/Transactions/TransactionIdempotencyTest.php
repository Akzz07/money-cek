<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Domain\Money\Money;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TransactionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_transaction_with_same_client_request_id_is_idempotent_and_creates_only_one_row(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'opening_balance' => Money::of('5000000.00')]);
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => CategoryType::Expense]);

        $clientRequestId = (string) Str::uuid();

        $payload = [
            'type' => 'expense',
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => '150000.00',
            'transaction_date' => '2026-10-18',
            'description' => 'Beli Bensin',
            'client_request_id' => $clientRequestId,
        ];

        // First POST
        $response1 = $this->actingAs($user)->post(route('transactions.store'), $payload);
        $response1->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('transactions', 1);
        $firstId = Transaction::first()->id;

        // Second POST with same client_request_id (simulate network retry / double-click)
        $response2 = $this->actingAs($user)->post(route('transactions.store'), $payload);
        $response2->assertRedirect(route('transactions.index'));

        // Database must still have strictly one record
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame($firstId, Transaction::first()->id);
    }

    public function test_post_transfer_with_same_client_request_id_is_idempotent_and_creates_only_one_row(): void
    {
        $user = User::factory()->create();
        $source = Account::factory()->create(['user_id' => $user->id, 'opening_balance' => Money::of('10000000.00')]);
        $dest = Account::factory()->create(['user_id' => $user->id, 'opening_balance' => Money::zero()]);

        $clientRequestId = (string) Str::uuid();

        $payload = [
            'source_account_id' => $source->id,
            'destination_account_id' => $dest->id,
            'amount' => '500000.00',
            'transaction_date' => '2026-10-18',
            'description' => 'Top Up E-Wallet',
            'client_request_id' => $clientRequestId,
        ];

        // First transfer POST
        $response1 = $this->actingAs($user)->post(route('transfers.store'), $payload);
        $response1->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('transactions', 1);
        $firstId = Transaction::first()->id;

        // Second transfer POST with same client_request_id
        $response2 = $this->actingAs($user)->post(route('transfers.store'), $payload);
        $response2->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame($firstId, Transaction::first()->id);
    }
}
