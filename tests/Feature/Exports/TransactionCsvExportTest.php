<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Domain\Money\Money;
use App\Enums\CategoryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Exports\TransactionCsvExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransactionCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_transactions(): void
    {
        $response = $this->get(route('transactions.export'));
        $response->assertRedirect(route('login'));
    }

    public function test_csv_injection_sanitization_escapes_formulas(): void
    {
        // Dangerous payload test cases
        $this->assertSame("'=1+1", TransactionCsvExporter::sanitizeCell('=1+1'));
        $this->assertSame("'+cmd|' /C calc'!A0", TransactionCsvExporter::sanitizeCell("+cmd|' /C calc'!A0"));
        $this->assertSame("'-5+2", TransactionCsvExporter::sanitizeCell('-5+2'));
        $this->assertSame("'@SUM(A1:A10)", TransactionCsvExporter::sanitizeCell('@SUM(A1:A10)'));
        $this->assertSame("'\tDDE(\"cmd\";\"/C calc\";\"__DdeLink__\")", TransactionCsvExporter::sanitizeCell("\tDDE(\"cmd\";\"/C calc\";\"__DdeLink__\")"));
        $this->assertSame("'\rPAYLOAD", TransactionCsvExporter::sanitizeCell("\rPAYLOAD"));

        // Safe normal text must remain unaltered
        $this->assertSame('Makan Siang Nasi Padang', TransactionCsvExporter::sanitizeCell('Makan Siang Nasi Padang'));
        $this->assertSame('150000.00', TransactionCsvExporter::sanitizeCell('150000.00'));
        $this->assertSame('', TransactionCsvExporter::sanitizeCell(null));
    }

    public function test_user_can_export_transactions_as_streamed_csv_with_utf8_bom(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'BCA Utama']);
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Belanja Bulanan',
            'type' => CategoryType::Expense,
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'type' => TransactionType::Expense,
            'amount' => Money::of('250000.00'),
            'transaction_date' => '2026-10-18',
            'description' => 'Superindo groceries',
            'notes' => 'Diskon 10%',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="monetra_transaksi_', (string) $response->headers->get('Content-Disposition'));

        // Capture streamed content
        $content = $response->streamedContent();

        // 1. Must start with UTF-8 BOM (\xEF\xBB\xBF)
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // 2. Must contain headers
        $this->assertStringContainsString('ID Transaksi', $content);
        $this->assertStringContainsString('Nominal (Rp)', $content);
        $this->assertStringContainsString('Kategori', $content);

        // 3. Must contain the transaction record
        $this->assertStringContainsString('2026-10-18', $content);
        $this->assertStringContainsString('Belanja Bulanan', $content);
        $this->assertStringContainsString('BCA Utama', $content);
        $this->assertStringContainsString('250000.00', $content);
        $this->assertStringContainsString('Superindo groceries', $content);
        $this->assertStringContainsString('Diskon 10%', $content);
    }

    public function test_user_only_exports_own_transactions_and_sanitizes_malicious_descriptions(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountA = Account::factory()->create(['user_id' => $userA->id]);
        $accountB = Account::factory()->create(['user_id' => $userB->id]);

        // User A's transaction with malicious formula description
        Transaction::factory()->create([
            'user_id' => $userA->id,
            'source_account_id' => $accountA->id,
            'amount' => Money::of('50000.00'),
            'description' => '=cmd|"/C calc"!A0',
            'status' => TransactionStatus::Posted,
        ]);

        // User B's transaction
        Transaction::factory()->create([
            'user_id' => $userB->id,
            'source_account_id' => $accountB->id,
            'amount' => Money::of('999999.00'),
            'description' => 'UserB Private Secret',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($userA)->get(route('transactions.export'));
        $content = $response->streamedContent();

        // Must escape formula injection with leading single quote
        $this->assertStringContainsString("'=cmd|", $content);

        // Must NOT leak user B's transaction
        $this->assertStringNotContainsString('UserB Private Secret', $content);
        $this->assertStringNotContainsString('999999.00', $content);
    }

    public function test_date_filters_are_respected_during_export(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        // Transaction inside filter: 2026-10-10
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'amount' => Money::of('111111.00'),
            'transaction_date' => '2026-10-10',
            'description' => 'Inside October Window',
            'status' => TransactionStatus::Posted,
        ]);

        // Transaction outside filter: 2026-08-01
        Transaction::factory()->create([
            'user_id' => $user->id,
            'source_account_id' => $account->id,
            'amount' => Money::of('222222.00'),
            'transaction_date' => '2026-08-01',
            'description' => 'Outside August Window',
            'status' => TransactionStatus::Posted,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.export', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Inside October Window', $content);
        $this->assertStringContainsString('111111.00', $content);
        $this->assertStringNotContainsString('Outside August Window', $content);
        $this->assertStringNotContainsString('222222.00', $content);
    }
}
