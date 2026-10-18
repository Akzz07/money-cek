<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\CategoryStatus;
use App\Enums\TransactionStatus;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class FilamentAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_unauthorized_user_is_forbidden_from_admin_panel(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_authorized_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_unauthorized_user_cannot_access_any_admin_resource_pages(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)->get('/admin/transactions')->assertForbidden();
        $this->actingAs($user)->get('/admin/accounts')->assertForbidden();
        $this->actingAs($user)->get('/admin/categories')->assertForbidden();
        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_admin_can_view_transaction_list_and_details(): void
    {
        $admin = User::factory()->admin()->create();
        $account = Account::factory()->for($admin)->create();
        $category = Category::factory()->for($admin)->create();
        $transaction = Transaction::factory()->for($admin)->create([
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'destination_account_id' => null,
            'status' => TransactionStatus::Posted,
        ]);

        $this->actingAs($admin)->get('/admin/transactions')->assertOk();
        $this->actingAs($admin)->get("/admin/transactions/{$transaction->id}")->assertOk();
    }

    public function test_admin_can_view_accounts_categories_and_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $account = Account::factory()->for($admin)->create();
        $category = Category::factory()->for($admin)->create();
        $auditLog = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test.action',
            'entity_type' => 'Account',
            'entity_id' => $account->id,
            'metadata' => ['test' => true],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/accounts')->assertOk();
        $this->actingAs($admin)->get("/admin/accounts/{$account->id}")->assertOk();
        $this->actingAs($admin)->get('/admin/categories')->assertOk();
        $this->actingAs($admin)->get("/admin/categories/{$category->id}")->assertOk();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
        $this->actingAs($admin)->get("/admin/audit-logs/{$auditLog->id}")->assertOk();
    }

    public function test_admin_void_transaction_triggers_void_domain_action_and_records_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $account = Account::factory()->for($admin)->create();
        $category = Category::factory()->for($admin)->create();
        $transaction = Transaction::factory()->for($admin)->create([
            'source_account_id' => $account->id,
            'category_id' => $category->id,
            'destination_account_id' => null,
            'status' => TransactionStatus::Posted,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListTransactions::class)
            ->callTableAction('void', $transaction, data: [
                'reason' => 'Kesalahan input tanggal oleh pemelihara sistem',
            ])
            ->assertHasNoTableActionErrors();

        $transaction->refresh();
        $this->assertSame(TransactionStatus::Voided, $transaction->status);
        $this->assertSame('Kesalahan input tanggal oleh pemelihara sistem', $transaction->void_reason);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'transaction.voided',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
        ]);
    }

    public function test_account_archive_domain_action_updates_status_properly(): void
    {
        $admin = User::factory()->admin()->create();
        $account = Account::factory()->for($admin)->create([
            'status' => AccountStatus::Active,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListAccounts::class)
            ->callTableAction('archive', $account)
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertSame(AccountStatus::Archived, $account->status);
    }

    public function test_category_archive_domain_action_updates_status_properly(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->for($admin)->create([
            'status' => CategoryStatus::Active,
            'is_system' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callTableAction('archive', $category)
            ->assertHasNoTableActionErrors();

        $category->refresh();
        $this->assertSame(CategoryStatus::Archived, $category->status);
    }
}
