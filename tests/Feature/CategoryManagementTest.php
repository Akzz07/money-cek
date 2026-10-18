<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('categories.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_category_list(): void
    {
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();

        $customCategory = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kopi Kafe',
            'type' => CategoryType::Expense,
        ]);

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Makanan'); // from seeder
        $response->assertSee('Kopi Kafe');
    }

    public function test_user_cannot_see_other_users_custom_categories(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $catA = Category::factory()->create([
            'user_id' => $userA->id,
            'name' => 'Kategori Rahasia A',
        ]);
        $catB = Category::factory()->create([
            'user_id' => $userB->id,
            'name' => 'Kategori Rahasia B',
        ]);

        $response = $this->actingAs($userA)->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Kategori Rahasia A');
        $response->assertDontSee('Kategori Rahasia B');
    }

    public function test_user_can_create_custom_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Langganan Netflix',
            'type' => 'expense',
            'icon' => 'ticket',
            'color' => 'rose',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Langganan Netflix',
            'type' => 'expense',
            'is_system' => false,
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_create_duplicate_category_for_same_type(): void
    {
        $user = User::factory()->create();

        Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Investasi Saham',
            'type' => CategoryType::Expense,
        ]);

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Investasi Saham',
            'type' => 'expense',
            'icon' => 'chart-bar',
            'color' => 'emerald',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_user_can_update_their_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Hobi Lama',
            'type' => CategoryType::Expense,
        ]);

        $response = $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'Hobi Baru',
            'type' => 'expense',
            'icon' => 'sparkles',
            'color' => 'purple',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Hobi Baru',
            'color' => 'purple',
        ]);
    }

    public function test_user_cannot_update_system_category(): void
    {
        $user = User::factory()->create();
        $systemCategory = Category::factory()->system()->create([
            'name' => 'Makanan',
            'type' => CategoryType::Expense,
        ]);

        $response = $this->actingAs($user)->put(route('categories.update', $systemCategory), [
            'name' => 'Kuliner Ubahan',
            'type' => 'expense',
            'icon' => 'utensils',
            'color' => 'amber',
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_update_other_users_category(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $categoryB = Category::factory()->create([
            'user_id' => $userB->id,
            'name' => 'Kategori B',
        ]);

        $response = $this->actingAs($userA)->put(route('categories.update', $categoryB), [
            'name' => 'Diretas A',
            'type' => 'expense',
            'icon' => 'tag',
            'color' => 'blue',
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_system_category(): void
    {
        $user = User::factory()->create();
        $systemCategory = Category::factory()->system()->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $systemCategory));

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_category(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $categoryB = Category::factory()->create([
            'user_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA)->delete(route('categories.destroy', $categoryB));

        $response->assertForbidden();
    }

    public function test_user_can_archive_their_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'status' => CategoryStatus::Active,
        ]);

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $this->assertEquals(CategoryStatus::Archived, $category->fresh()->status);
    }
}
