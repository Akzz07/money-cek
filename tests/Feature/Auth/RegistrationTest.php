<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_returns_404_not_found(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_public_user_registration_endpoint_returns_404_not_found(): void
    {
        $response = $this->post('/register', [
            'name' => 'Adversary User',
            'email' => 'adversary@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
    }
}
