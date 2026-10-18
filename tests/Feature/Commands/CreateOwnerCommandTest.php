<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_owner_interactively(): void
    {
        $this->artisan('monetra:create-owner', [
            '--email' => 'owner@monetra.test',
            '--name' => 'Akmal Owner',
        ])
            ->expectsQuestion('Masukkan kata sandi pemilik (minimal 8 karakter)', 'Secret12345!')
            ->expectsQuestion('Konfirmasi kata sandi', 'Secret12345!')
            ->assertSuccessful();

        $user = User::where('email', 'owner@monetra.test')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Akmal Owner', $user->name);
        $this->assertTrue(Hash::check('Secret12345!', $user->password));
        $this->assertEquals('Asia/Jakarta', $user->timezone);
        $this->assertEquals('IDR', $user->currency);
        $this->assertEquals('id', $user->locale);
        $this->assertEquals(25, $user->payday_day);
    }

    public function test_cannot_create_duplicate_owner_email(): void
    {
        User::factory()->create(['email' => 'existing@monetra.test']);

        $this->artisan('monetra:create-owner', [
            '--email' => 'existing@monetra.test',
            '--name' => 'Duplicate',
        ])
            ->assertFailed();
    }

    public function test_cannot_create_owner_with_short_password(): void
    {
        $this->artisan('monetra:create-owner', [
            '--email' => 'short@monetra.test',
            '--name' => 'Short',
        ])
            ->expectsQuestion('Masukkan kata sandi pemilik (minimal 8 karakter)', 'short')
            ->assertFailed();
    }
}
