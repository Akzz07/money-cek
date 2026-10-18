<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccessibilityAndResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_have_exactly_one_h1_heading(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->create();

        $routes = [
            route('home'),
            route('transactions.index'),
            route('transactions.create'),
            route('budgets.index'),
            route('bills.index'),
            route('savings-goals.index'),
            route('assets.index'),
            route('safe-to-spend.show'),
            route('accounts.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();

            $html = $response->getContent();
            $h1Count = substr_count($html, '<h1');

            $this->assertSame(
                1,
                $h1Count,
                "Route [{$url}] must contain exactly one <h1> heading for WCAG accessibility compliance. Found: {$h1Count}."
            );
        }
    }

    public function test_layout_contains_mobile_viewport_meta_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertOk();

        $response->assertSee('name="viewport"', false);
        $response->assertSee('width=device-width', false);
    }

    public function test_bottom_navigation_has_accessible_aria_labels(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertOk();

        $response->assertSee('aria-label="Navigasi Utama"', false);
        $response->assertSee('aria-label="Tambah Transaksi"', false);
    }

    public function test_contrast_and_focus_ring_classes_are_present(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertOk();

        // Focus ring on interactive action button
        $response->assertSee('focus:ring-', false);
        // Clean contrast font colors
        $response->assertSee('text-slate-800', false);
    }
}
