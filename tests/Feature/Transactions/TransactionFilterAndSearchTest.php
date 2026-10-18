<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionFilterAndSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_transactions_by_description_and_notes(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'description' => 'Beli Kopi Kenangan',
            'notes' => 'Latte gula aren',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'description' => 'Makan Siang Padang',
            'notes' => 'Rendang ayam',
            'status' => TransactionStatus::Posted,
        ]);

        // Search by description
        $response = $this->actingAs($user)->get(route('transactions.index', ['search' => 'Kopi']));
        $response->assertOk();
        $response->assertSee('Beli Kopi Kenangan');
        $response->assertDontSee('Makan Siang Padang');

        // Search by notes
        $response = $this->actingAs($user)->get(route('transactions.index', ['search' => 'Rendang']));
        $response->assertOk();
        $response->assertSee('Makan Siang Padang');
        $response->assertDontSee('Beli Kopi Kenangan');
    }

    public function test_search_sanitizes_special_characters_like_percent_and_underscore(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'description' => 'Diskon 50% Promo',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'description' => 'Transaksi Normal Tanpa Persen',
            'status' => TransactionStatus::Posted,
        ]);

        // Searching for literally '%' should only match the transaction with literal '%'
        $response = $this->actingAs($user)->get(route('transactions.index', ['search' => '%']));
        $response->assertOk();
        $response->assertSee('Diskon 50% Promo');
        $response->assertDontSee('Transaksi Normal Tanpa Persen');

        // Searching with quotes or underscores should not trigger SQL errors
        $response2 = $this->actingAs($user)->get(route('transactions.index', ['search' => "test's_value"]));
        $response2->assertOk();
    }

    public function test_can_filter_transactions_by_account(): void
    {
        $user = User::factory()->create();
        $accountA = Account::factory()->create(['user_id' => $user->id, 'name' => 'BCA Utama']);
        $accountB = Account::factory()->create(['user_id' => $user->id, 'name' => 'Mandiri Tabungan']);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountA->id,
            'description' => 'Pengeluaran BCA',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $accountB->id,
            'description' => 'Pengeluaran Mandiri',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index', ['account_id' => $accountA->id]));
        $response->assertOk();
        $response->assertSee('Pengeluaran BCA');
        $response->assertDontSee('Pengeluaran Mandiri');
    }

    public function test_can_filter_transactions_by_category(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $catFood = Category::factory()->create(['user_id' => $user->id, 'name' => 'Makanan', 'type' => CategoryType::Expense]);
        $catTransport = Category::factory()->create(['user_id' => $user->id, 'name' => 'Transportasi', 'type' => CategoryType::Expense]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catFood->id,
            'description' => 'Makan Siang Bento',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $catTransport->id,
            'description' => 'Naik Ojek Online',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index', ['category_id' => $catFood->id]));
        $response->assertOk();
        $response->assertSee('Makan Siang Bento');
        $response->assertDontSee('Naik Ojek Online');
    }

    public function test_can_filter_transactions_by_type(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Income,
            'description' => 'Gaji Pokok',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'type' => TransactionType::Expense,
            'description' => 'Bayar Tagihan Wifi',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index', ['type' => 'income']));
        $response->assertOk();
        $response->assertSee('Gaji Pokok');
        $response->assertDontSee('Bayar Tagihan Wifi');
    }

    public function test_can_filter_transactions_by_date_range(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'transaction_date' => '2026-10-10',
            'description' => 'Transaksi 10 Okt',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'transaction_date' => '2026-10-20',
            'description' => 'Transaksi 20 Okt',
            'status' => TransactionStatus::Posted,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'transaction_date' => '2026-10-30',
            'description' => 'Transaksi 30 Okt',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-25',
        ]));

        $response->assertOk();
        $response->assertSee('Transaksi 20 Okt');
        $response->assertDontSee('Transaksi 10 Okt');
        $response->assertDontSee('Transaksi 30 Okt');
    }

    public function test_pagination_preserves_query_string_parameters(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        // Create 30 transactions to trigger pagination (25 per page)
        for ($i = 1; $i <= 30; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'source_account_id' => $account->id,
                'description' => "Item Belanja {$i}",
                'type' => TransactionType::Expense,
                'status' => TransactionStatus::Posted,
            ]);
        }

        $response = $this->actingAs($user)->get(route('transactions.index', ['type' => 'expense', 'page' => 2]));

        $response->assertOk();
        // The paginator links must retain the type parameter
        $response->assertSee('type=expense');
    }

    public function test_transactions_index_does_not_suffer_from_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create(['user_id' => $user->id]);

        // Create 25 transactions with relations
        for ($i = 1; $i <= 25; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'source_account_id' => $account->id,
                'category_id' => $category->id,
                'description' => "Tx {$i}",
                'status' => TransactionStatus::Posted,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        // User auth + count transactions + fetch transactions with eager loaded accounts & categories + accounts dropdown + categories dropdown
        // Must be less than or equal to 10 queries, even when rendering 25 items!
        $this->assertLessThanOrEqual(10, $queryCount);
    }
}
