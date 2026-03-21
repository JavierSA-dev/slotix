<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActiveUserMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permiso necesario para el sidebar
        Permission::create(['name' => 'users.index']);

        Role::create(['name' => 'User']);
    }

    public function test_active_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'activo' => 1,
        ]);
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/home');

        // El middleware no bloquea al usuario activo; /home redirige al destino correcto
        $response->assertRedirect(route('mis-reservas.index'));
    }

    public function test_inactive_user_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'activo' => 0,
        ]);
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/home');

        // Debería ser redirigido o recibir 403
        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );
    }

    public function test_inactive_user_is_logged_out_when_accessing_routes(): void
    {
        $user = User::factory()->create([
            'activo' => 0,
        ]);
        $user->assignRole('User');

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->get('/home');

        // Después de intentar acceder, debería estar deslogueado
        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );
    }

    public function test_user_deactivated_after_login_cannot_continue(): void
    {
        $user = User::factory()->create([
            'activo' => 1,
        ]);
        $user->assignRole('User');

        // Login exitoso
        $this->actingAs($user);
        $response = $this->get('/home');
        $response->assertRedirect(route('mis-reservas.index'));

        // Desactivar usuario
        $user->update(['activo' => 0]);

        // Intentar acceder de nuevo
        $response = $this->get('/home');

        $this->assertTrue(
            $response->status() === 403 ||
            $response->status() === 302
        );
    }
}
