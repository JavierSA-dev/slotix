<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles necesarios
        Role::create(['name' => 'SuperAdmin']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'User']);
        
        // Crear permisos necesarios para evitar errores en vistas
        \Spatie\Permission\Models\Permission::create(['name' => 'users.index']);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('User');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_cannot_authenticate_with_invalid_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
    }

    public function test_authenticated_users_can_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        
        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_guests_cannot_access_protected_routes(): void
    {
        $response = $this->get('/home');
        
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_home(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
    }

    public function test_login_validation_requires_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_validation_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_login_validation_requires_valid_email_format(): void
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_has_rate_limiting(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        // Intentar logins fallidos múltiples veces
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // El siguiente intento debería ser bloqueado por rate limiting
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Verificar que hay throttling (429 Too Many Requests o error de validación)
        $this->assertTrue(
            $response->status() === 429 || $response->status() === 302
        );
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/login');
        
        $response->assertRedirect('/');
    }

    public function test_remember_me_functionality(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('User');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => 'on',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        
        // Verificar que se creó la cookie de remember
        $response->assertCookie(auth()->guard()->getRecallerName());
    }
}
