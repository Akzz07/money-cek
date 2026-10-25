<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Monetra');
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Alamat Email');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Akmal Baru',
            'email' => 'baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'baru@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Akmal Baru', $user->name);
        $this->assertEquals('Asia/Jakarta', $user->timezone);
        $this->assertEquals('id', $user->locale);
        $this->assertEquals('IDR', $user->currency);
        $this->assertFalse((bool) $user->is_admin);
    }

    public function test_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name' => 'Duplikat User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cannot_register_with_mismatched_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Mismatch User',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
