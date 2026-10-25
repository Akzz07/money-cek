<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

final class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_redirects_to_google_oauth_provider(): void
    {
        $response = $this->get(route('auth.google'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', (string) $response->headers->get('Location'));
    }

    public function test_google_callback_creates_new_user_and_authenticates(): void
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-uid-999');
        $abstractUser->shouldReceive('getEmail')->andReturn('newgoogle@gmail.com');
        $abstractUser->shouldReceive('getName')->andReturn('Google Baru');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/photo.jpg');

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'newgoogle@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-uid-999', $user->google_id);
        $this->assertEquals('Google Baru', $user->name);
        $this->assertEquals('https://lh3.googleusercontent.com/photo.jpg', $user->avatar);
        $this->assertEquals('Asia/Jakarta', $user->timezone);
        $this->assertEquals('IDR', $user->currency);
        $this->assertNull($user->password);
    }

    public function test_google_callback_links_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@gmail.com',
            'google_id' => null,
            'avatar' => null,
        ]);

        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn('google-uid-existing');
        $abstractUser->shouldReceive('getEmail')->andReturn('existing@gmail.com');
        $abstractUser->shouldReceive('getName')->andReturn('Updated Name');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existingUser);
        $response->assertRedirect(route('home', absolute: false));

        $existingUser->refresh();
        $this->assertEquals('google-uid-existing', $existingUser->google_id);
        $this->assertEquals('https://lh3.googleusercontent.com/avatar.jpg', $existingUser->avatar);
    }

    public function test_google_callback_handles_oauth_exception_gracefully(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andThrow(new Exception('Invalid authorization code or user cancelled'));

        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }
}
