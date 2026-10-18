<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_accounts_list(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->bank()->create(['name' => 'BCA Utama']);

        $response = $this->actingAs($user)->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee('BCA Utama');
    }

    public function test_user_can_create_new_bank_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Mandiri Operasional',
            'type' => 'bank',
            'institution_name' => 'Bank Mandiri',
            'account_number' => '1400012345678',
            'opening_balance' => '5000000',
            'opening_date' => now()->toDateString(),
            'include_in_total_assets' => '1',
            'include_in_safe_to_spend' => '1',
            'notes' => 'Rekening belanja bulanan',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Mandiri Operasional',
            'type' => 'bank',
            'opening_balance' => '5000000.00',
            'status' => 'active',
        ]);
    }

    public function test_investment_account_defaults_to_not_included_in_safe_to_spend(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Bibit Reksadana',
            'type' => 'investment',
            'institution_name' => 'Bibit',
            'opening_balance' => '10000000',
            'opening_date' => now()->toDateString(),
            'include_in_total_assets' => '1',
            // include_in_safe_to_spend not passed, should default to false for investment
        ]);

        $response->assertRedirect(route('accounts.index'));

        $account = Account::where('name', 'Bibit Reksadana')->first();
        $this->assertNotNull($account);
        $this->assertFalse($account->include_in_safe_to_spend);
        $this->assertTrue($account->include_in_total_assets);
    }

    public function test_cannot_create_account_with_negative_opening_balance(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Invalid Account',
            'type' => 'bank',
            'opening_balance' => '-50000',
            'opening_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['opening_balance']);
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_cannot_create_account_with_future_opening_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            'name' => 'Future Account',
            'type' => 'bank',
            'opening_balance' => '100000',
            'opening_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertSessionHasErrors(['opening_date']);
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_user_can_update_account_details(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->bank()->create(['name' => 'Nama Lama']);

        $response = $this->actingAs($user)->put(route('accounts.update', $account), [
            'name' => 'Nama Baru',
            'type' => 'bank',
            'institution_name' => 'Bank BCA Baru',
            'account_number' => '9999999999',
            'include_in_total_assets' => '1',
            'include_in_safe_to_spend' => '1',
            'notes' => 'Catatan diperbarui',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Nama Baru',
            'institution_name' => 'Bank BCA Baru',
        ]);
    }

    public function test_user_can_archive_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['status' => AccountStatus::Active]);

        $response = $this->actingAs($user)->delete(route('accounts.destroy', $account));

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'status' => AccountStatus::Archived->value,
        ]);
    }

    public function test_user_cannot_access_or_modify_other_users_account(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $account = Account::factory()->for($owner)->create();

        // Stranger tries to edit
        $editResponse = $this->actingAs($stranger)->get(route('accounts.edit', $account));
        $editResponse->assertForbidden();

        // Stranger tries to update
        $updateResponse = $this->actingAs($stranger)->put(route('accounts.update', $account), [
            'name' => 'Hacked Name',
            'type' => 'bank',
        ]);
        $updateResponse->assertForbidden();

        // Stranger tries to archive
        $deleteResponse = $this->actingAs($stranger)->delete(route('accounts.destroy', $account));
        $deleteResponse->assertForbidden();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => $account->name,
        ]);
    }
}
