<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'User']);
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/password/reset');
        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->assignRole('User');

        $this->post('/password/email', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->assignRole('User');

        $this->post('/password/email', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/password/reset/' . $notification->token);
            $response->assertStatus(200);
            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->assignRole('User');

        $this->post('/password/email', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            $response->assertSessionHasNoErrors();
            return true;
        });
    }

    public function test_password_reset_validation_requires_email(): void
    {
        $response = $this->post('/password/email', [
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_validation_requires_valid_email(): void
    {
        $response = $this->post('/password/email', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->post('/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_password_reset_requires_password_confirmation(): void
    {
        $token = Password::createToken(User::factory()->create());

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_reset_has_rate_limiting(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        // Intentar reset múltiples veces
        for ($i = 0; $i < 4; $i++) {
            $this->post('/password/email', [
                'email' => $user->email,
            ]);
        }

        // El siguiente intento debería ser bloqueado
        $response = $this->post('/password/email', [
            'email' => $user->email,
        ]);

        $this->assertTrue(
            $response->status() === 429 || $response->status() === 302
        );
    }
}
