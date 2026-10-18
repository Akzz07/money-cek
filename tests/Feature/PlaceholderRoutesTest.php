<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaceholderRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }

    public function test_home_page_returns_ok_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Halo, '.$user->name);
        $response->assertSee('Total Saldo');
    }

    public function test_transactions_index_page_returns_ok_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Riwayat Transaksi');
    }

    public function test_transactions_create_page_returns_ok_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk();
        $response->assertSee('Tambah Transaksi');
    }

    public function test_budgets_index_page_returns_ok_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('budgets.index'));

        $response->assertOk();
        $response->assertSee('Anggaran Bulanan');
    }

    public function test_assets_index_page_returns_ok_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('assets.index'));

        $response->assertOk();
        $response->assertSee('Ringkasan Kekayaan Bersih');
    }
}
