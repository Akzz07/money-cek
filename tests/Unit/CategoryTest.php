<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_helper_methods(): void
    {
        $systemCategory = Category::factory()->system()->create();
        $userCategory = Category::factory()->create();

        $this->assertTrue($systemCategory->isSystem());
        $this->assertFalse($userCategory->isSystem());
        $this->assertTrue($userCategory->isActive());

        $userCategory->update(['status' => CategoryStatus::Archived]);
        $this->assertFalse($userCategory->fresh()->isActive());
    }

    public function test_category_type_and_status_scopes(): void
    {
        Category::factory()->income()->create();
        Category::factory()->expense()->create();
        Category::factory()->archived()->create();

        $this->assertGreaterThanOrEqual(1, Category::income()->count());
        $this->assertGreaterThanOrEqual(1, Category::expense()->count());
        $this->assertGreaterThanOrEqual(1, Category::archived()->count());
    }

    public function test_for_user_scope_includes_system_and_user_categories(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $systemCat = Category::factory()->system()->create(['name' => 'System Category']);
        $userACat = Category::factory()->create(['user_id' => $userA->id, 'name' => 'User A Cat']);
        $userBCat = Category::factory()->create(['user_id' => $userB->id, 'name' => 'User B Cat']);

        $userACategories = Category::forUser($userA)->get();

        $this->assertTrue($userACategories->contains($systemCat));
        $this->assertTrue($userACategories->contains($userACat));
        $this->assertFalse($userACategories->contains($userBCat));
    }

    public function test_category_seeder_is_idempotent(): void
    {
        $this->seed(CategorySeeder::class);
        $countFirst = Category::where('is_system', true)->count();
        $this->assertEquals(18, $countFirst); // 11 expense + 7 income

        // Re-run seeder
        $this->seed(CategorySeeder::class);
        $countSecond = Category::where('is_system', true)->count();
        $this->assertEquals(18, $countSecond);
    }
}
