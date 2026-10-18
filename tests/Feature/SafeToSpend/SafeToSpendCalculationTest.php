<?php

declare(strict_types=1);

namespace Tests\Feature\SafeToSpend;

use App\Domain\Money\Money;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\BillStatus;
use App\Enums\SavingsGoalEntryDirection;
use App\Enums\SavingsGoalStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use App\Models\User;
use App\Services\SafeToSpend\SafeToSpendService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SafeToSpendCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_safe_to_spend_details(): void
    {
        $response = $this->get(route('safe-to-spend.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_safe_to_spend_positive_result(): void
    {
        $user = User::factory()->create([
            'payday_day' => 25,
        ]);

        // 1. Spendable Account: 10.000.000
        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Operasional',
            'opening_balance' => Money::of('10000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // 2. Upcoming Bill: 2.000.000 (due before payday)
        $planningDate = CarbonImmutable::now()->addDays(10);
        Bill::factory()->create([
            'user_id' => $user->id,
            'name' => 'Sewa Rumah',
            'amount' => Money::of('2000000.00'),
            'due_date' => CarbonImmutable::now()->addDays(3)->toDateString(),
            'status' => BillStatus::Upcoming,
            'paid_transaction_id' => null,
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, $planningDate);

        $this->assertSame('10000000.00', $result->spendableBalance->toDecimalString());
        $this->assertSame('2000000.00', $result->unpaidBills->toDecimalString());
        $this->assertSame('0.00', $result->pendingAllocations->toDecimalString());
        $this->assertSame('8000000.00', $result->safeToSpend->toDecimalString());
        $this->assertTrue($result->isPositive());
    }

    public function test_safe_to_spend_negative_result_when_bills_exceed_balance(): void
    {
        $user = User::factory()->create();

        // Saldo 1.000.000
        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('1000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // Tagihan 3.500.000
        Bill::factory()->create([
            'user_id' => $user->id,
            'amount' => Money::of('3500000.00'),
            'due_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, CarbonImmutable::now()->addDays(5));

        $this->assertSame('-2500000.00', $result->safeToSpend->toDecimalString());
        $this->assertTrue($result->isNegative());
        $this->assertFalse($result->isPositive());
    }

    public function test_safe_to_spend_zero_result(): void
    {
        $user = User::factory()->create();

        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('1500000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'amount' => Money::of('1500000.00'),
            'due_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, CarbonImmutable::now()->addDays(5));

        $this->assertSame('0.00', $result->safeToSpend->toDecimalString());
        $this->assertTrue($result->isZero());
    }

    public function test_accounts_marked_not_spendable_are_strictly_excluded(): void
    {
        $user = User::factory()->create();

        // 1. Akun Spendable: 5.000.000
        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Kas Harian',
            'opening_balance' => Money::of('5000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // 2. Akun Investasi (Non-Spendable): 50.000.000
        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Bibit Reksa Dana',
            'type' => AccountType::Investment,
            'opening_balance' => Money::of('50000000.00'),
            'include_in_safe_to_spend' => false,
            'status' => AccountStatus::Active,
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, CarbonImmutable::now()->addDays(7));

        // Spendable balance must be 5.000.000 only, NOT 55.000.000
        $this->assertSame('5000000.00', $result->spendableBalance->toDecimalString());
        $this->assertCount(1, $result->spendableAccounts);
    }

    public function test_bills_due_after_planning_date_are_not_deducted(): void
    {
        $user = User::factory()->create();

        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('5000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        $planningDate = CarbonImmutable::now()->addDays(5);

        // Tagihan 1: Jatuh tempo dalam 2 hari (sebelum gajian) -> HARUS dikurang
        Bill::factory()->create([
            'user_id' => $user->id,
            'amount' => Money::of('1000000.00'),
            'due_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);

        // Tagihan 2: Jatuh tempo dalam 15 hari (setelah gajian) -> JANGAN dikurang
        Bill::factory()->create([
            'user_id' => $user->id,
            'amount' => Money::of('2000000.00'),
            'due_date' => CarbonImmutable::now()->addDays(15)->toDateString(),
            'status' => BillStatus::Upcoming,
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, $planningDate);

        $this->assertSame('1000000.00', $result->unpaidBills->toDecimalString());
        $this->assertSame('4000000.00', $result->safeToSpend->toDecimalString());
    }

    public function test_paid_and_skipped_bills_are_not_deducted(): void
    {
        $user = User::factory()->create();

        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('5000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // Paid Bill
        Bill::factory()->create([
            'user_id' => $user->id,
            'amount' => Money::of('1000000.00'),
            'due_date' => CarbonImmutable::now()->addDays(2)->toDateString(),
            'status' => BillStatus::Paid,
            'paid_transaction_id' => null,
        ]);

        // Skipped Bill
        Bill::factory()->create([
            'user_id' => $user->id,
            'amount' => Money::of('500000.00'),
            'due_date' => CarbonImmutable::now()->addDays(3)->toDateString(),
            'status' => BillStatus::Skipped,
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, CarbonImmutable::now()->addDays(7));

        $this->assertSame('0.00', $result->unpaidBills->toDecimalString());
        $this->assertSame('5000000.00', $result->safeToSpend->toDecimalString());
    }

    public function test_anti_double_deduction_for_dedicated_account_contributions(): void
    {
        $user = User::factory()->create();

        // Akun Kas Operasional (Spendable): Sisa saldo 6.000.000 (sudah berkurang saat transfer keluar)
        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('6000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // Akun Tabungan Khusus (Non-Spendable): 4.000.000
        $savingsAccount = Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('4000000.00'),
            'include_in_safe_to_spend' => false,
            'status' => AccountStatus::Active,
        ]);

        // Target Tabungan: Target 10.000.000, sudah terkumpul 4.000.000 di rekening khusus
        // Tenggat waktu masih 6 bulan lagi (tidak jatuh tempo sebelum tanggal rencana)
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Beli Motor',
            'target_amount' => Money::of('10000000.00'),
            'target_account_id' => $savingsAccount->id,
            'target_date' => CarbonImmutable::now()->addMonths(6)->toDateString(),
            'status' => SavingsGoalStatus::Active,
        ]);

        SavingsGoalEntry::factory()->create([
            'savings_goal_id' => $goal->id,
            'user_id' => $user->id,
            'direction' => SavingsGoalEntryDirection::Contribution,
            'amount' => Money::of('4000000.00'),
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, CarbonImmutable::now()->addDays(10));

        // Dana 4.000.000 yang sudah ada di rekening khusus TIDAK boleh dikurangkan ganda dari 6.000.000!
        // Safe-to-Spend tetap 6.000.000 penuh.
        $this->assertSame('6000000.00', $result->spendableBalance->toDecimalString());
        $this->assertSame('0.00', $result->pendingAllocations->toDecimalString());
        $this->assertSame('6000000.00', $result->safeToSpend->toDecimalString());
    }

    public function test_virtual_savings_goals_reserve_accumulated_balance_from_spendable_pool(): void
    {
        $user = User::factory()->create();

        // Akun Kas Harian: 10.000.000 (termasuk 3.000.000 dana cadangan virtual)
        Account::factory()->create([
            'user_id' => $user->id,
            'opening_balance' => Money::of('10000000.00'),
            'include_in_safe_to_spend' => true,
            'status' => AccountStatus::Active,
        ]);

        // Virtual Goal tanpa rekening terpisah
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'name' => 'Dana Darurat Virtual',
            'target_amount' => Money::of('20000000.00'),
            'target_account_id' => null,
            'target_date' => CarbonImmutable::now()->addMonths(12)->toDateString(),
            'status' => SavingsGoalStatus::Active,
        ]);

        SavingsGoalEntry::factory()->create([
            'savings_goal_id' => $goal->id,
            'user_id' => $user->id,
            'direction' => SavingsGoalEntryDirection::Contribution,
            'amount' => Money::of('3000000.00'),
        ]);

        $service = app(SafeToSpendService::class);
        $result = $service->calculate($user, CarbonImmutable::now()->addDays(10));

        // Karena uang 3.000.000 masih mengendap di rekening operasional, harus dicadangkan!
        // Safe-to-Spend = 10.000.000 - 3.000.000 = 7.000.000.
        $this->assertSame('10000000.00', $result->spendableBalance->toDecimalString());
        $this->assertSame('3000000.00', $result->pendingAllocations->toDecimalString());
        $this->assertSame('7000000.00', $result->safeToSpend->toDecimalString());
    }

    public function test_user_can_view_transparent_safe_to_spend_page(): void
    {
        $user = User::factory()->create([
            'payday_day' => 25,
        ]);

        Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'BCA Operasional',
            'opening_balance' => Money::of('8000000.00'),
            'include_in_safe_to_spend' => true,
        ]);

        $response = $this->actingAs($user)->get(route('safe-to-spend.show'));
        $response->assertOk();
        $response->assertSee('Kalkulasi Safe-to-Spend');
        $response->assertSee('Formula Transparan');
        $response->assertSee('Rincian Komponen Perhitungan');
        $response->assertSee('Asumsi Transparan & Kebijakan Deduksi', false);
        $response->assertSee('Disclaimer: Safe-to-Spend merupakan estimasi perencanaan keuangan mandiri', false);
    }
}
