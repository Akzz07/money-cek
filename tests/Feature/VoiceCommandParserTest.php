<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\User;
use App\Services\Command\TransactionCommandParser;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VoiceCommandParserTest extends TestCase
{
    use RefreshDatabase;

    private TransactionCommandParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
        $this->parser = new TransactionCommandParser;
    }

    public function test_parses_expense_with_shorthand_amount_and_account(): void
    {
        $user = User::factory()->create();
        $bca = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Syariah',
            'institution_name' => 'Bank Central Asia',
        ]);

        $result = $this->parser->parse($user, 'Beli kopi susu 25rb pakai BCA');

        $this->assertSame(TransactionType::Expense, $result->type);
        $this->assertNotNull($result->amount);
        $this->assertSame('25000.00', $result->amount->toDecimalString());
        $this->assertSame($bca->id, $result->accountId);
        $this->assertSame('BCA Syariah', $result->accountName);
        $this->assertNotNull($result->categoryId);
        $this->assertSame('Makanan', $result->categoryName);
        $this->assertStringContainsString('Kopi susu', $result->description ?? '');
        $this->assertTrue($result->isComplete());
    }

    public function test_parses_expense_with_millions_and_comma(): void
    {
        $user = User::factory()->create();
        $mandiri = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Mandiri Utama',
            'institution_name' => 'Bank Mandiri',
        ]);

        $result = $this->parser->parse($user, 'Beli sepatu sneakers 1,5 juta dari Mandiri');

        $this->assertSame(TransactionType::Expense, $result->type);
        $this->assertNotNull($result->amount);
        $this->assertSame('1500000.00', $result->amount->toDecimalString());
        $this->assertSame($mandiri->id, $result->accountId);
        $this->assertSame('Belanja', $result->categoryName);
        $this->assertStringContainsString('Sepatu sneakers', $result->description ?? '');
    }

    public function test_parses_income_with_gaji_and_juta(): void
    {
        $user = User::factory()->create();
        $bca = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Payroll',
            'institution_name' => 'BCA',
        ]);

        $result = $this->parser->parse($user, 'Gaji bulanan 8 juta masuk ke BCA');

        $this->assertSame(TransactionType::Income, $result->type);
        $this->assertNotNull($result->amount);
        $this->assertSame('8000000.00', $result->amount->toDecimalString());
        $this->assertSame($bca->id, $result->accountId);
        $this->assertSame('Gaji', $result->categoryName);
        $this->assertTrue($result->isComplete());
    }

    public function test_parses_word_numbers_and_cash_synonym(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Dompet Saku',
            'type' => AccountType::Cash,
        ]);

        $result = $this->parser->parse($user, 'Bayar makan siang dua puluh lima ribu tunai');

        $this->assertSame(TransactionType::Expense, $result->type);
        $this->assertNotNull($result->amount);
        $this->assertSame('25000.00', $result->amount->toDecimalString());
        $this->assertSame($cash->id, $result->accountId);
        $this->assertSame('Makanan', $result->categoryName);
    }

    public function test_parses_yesterday_date_token(): void
    {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id]);

        $result = $this->parser->parse($user, 'Beli bensin pertalite 50rb kemarin');

        $expectedDate = CarbonImmutable::now($user->timezone ?? 'Asia/Jakarta')->subDay()->toDateString();
        $this->assertSame($expectedDate, $result->transactionDate->toDateString());
        $this->assertSame('Transportasi', $result->categoryName);
    }

    public function test_defaults_to_first_active_account_if_unspecified(): void
    {
        $user = User::factory()->create();
        $firstAccount = Account::factory()->create(['user_id' => $user->id, 'name' => 'Rekening Utama']);

        $result = $this->parser->parse($user, 'Jajan bakso 15k');

        $this->assertSame($firstAccount->id, $result->accountId);
        $this->assertSame('15000.00', $result->amount?->toDecimalString());
        $this->assertSame('Makanan', $result->categoryName);
    }

    public function test_parses_explicit_category_phrase(): void
    {
        $user = User::factory()->create();
        $bca = Account::factory()->create(['user_id' => $user->id, 'name' => 'BCA']);

        $result = $this->parser->parse($user, 'Beli meja belajar seharga 250rb kategori Belanja lewat BCA');

        $this->assertSame(TransactionType::Expense, $result->type);
        $this->assertSame('250000.00', $result->amount?->toDecimalString());
        $this->assertSame($bca->id, $result->accountId);
        $this->assertSame('Belanja', $result->categoryName);
        $this->assertSame('Meja belajar', $result->description);
    }

    public function test_parses_freelance_income(): void
    {
        $user = User::factory()->create();
        $gopay = Account::factory()->create(['user_id' => $user->id, 'name' => 'GoPay', 'type' => AccountType::EWallet]);

        $result = $this->parser->parse($user, 'Terima freelance desain 750rb masuk ke Gopay');

        $this->assertSame(TransactionType::Income, $result->type);
        $this->assertSame('750000.00', $result->amount?->toDecimalString());
        $this->assertSame($gopay->id, $result->accountId);
        $this->assertSame('Freelance', $result->categoryName);
        $this->assertTrue($result->isComplete());
    }

    public function test_flags_missing_fields_when_amount_omitted(): void
    {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id]);

        $result = $this->parser->parse($user, 'Makan siang padang');

        $this->assertNull($result->amount);
        $this->assertFalse($result->isComplete());
        $this->assertContains('amount', $result->missingFields);
        $this->assertSame('Makanan', $result->categoryName);
    }

    public function test_voice_parse_endpoint_returns_json_structure(): void
    {
        $user = User::factory()->create();
        $bca = Account::factory()->create(['user_id' => $user->id, 'name' => 'BCA']);

        $response = $this->actingAs($user)->postJson(route('voice.parse'), [
            'text' => 'Beli bensin pertalite 50rb pakai BCA',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.amount', '50000.00')
            ->assertJsonPath('data.source_account_id', $bca->id)
            ->assertJsonPath('data.category_name', 'Transportasi');
    }
}
