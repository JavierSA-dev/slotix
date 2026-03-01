<?php

namespace Tests\Feature\Debug;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test para debuggear el middleware de permisos.
 */
class MiddlewareDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_middleware_spatie(): void
    {
        // Setup
        Permission::create(['name' => 'test.permission', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'TestRole', 'guard_name' => 'web']);
        $role->givePermissionTo('test.permission');

        $user = User::factory()->create();
        $user->assignRole('TestRole');

        // Crear ruta de prueba con el mismo middleware
        Route::middleware(['auth', 'permission:test.permission'])
            ->get('/test-route', function () {
                return response()->json(['success' => true]);
            });

        // Autenticar
        $this->actingAs($user);

        dump([
            'User activo?' => $user->activo,
            'User tiene rol?' => $user->hasRole('TestRole'),
            'User tiene permiso directo?' => $user->hasPermissionTo('test.permission'),
            'Todos los permisos' => $user->getAllPermissions()->pluck('name')->toArray(),
        ]);

        // Intentar acceder
        $response = $this->get('/test-route');

        dump([
            'Status' => $response->status(),
            'Content' => $response->getContent(),
        ]);

        $this->assertTrue(true);
    }

    /** @test */
    public function debug_ruta_users_show_real(): void
    {
        // Setup exacto del test fallido
        $permissions = ['users.index', 'users.show', 'users.create', 'users.edit', 'users.delete'];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($permissions);

        $adminUser = User::factory()->create();
        $adminUser->assignRole('SuperAdmin');
        $adminUser->givePermissionTo($permissions);

        $normalUser = User::factory()->create();

        $this->actingAs($adminUser);

        dump([
            '=== ADMIN USER ===' => '',
            'ID' => $adminUser->id,
            'activo' => $adminUser->activo,
            'roles' => $adminUser->getRoleNames()->toArray(),
            'direct permissions' => $adminUser->getDirectPermissions()->pluck('name')->toArray(),
            'all permissions' => $adminUser->getAllPermissions()->pluck('name')->toArray(),
            'can users.index' => $adminUser->can('users.index'),
            'can users.show' => $adminUser->can('users.show'),
        ]);

        // Intentar SIN middleware primero - acceder directamente al controller
        $controller = app(\App\Http\Controllers\UserController::class);

        dump('=== Intentando acceder a users.show ===');

        $response = $this->get(route('users.show', $normalUser->id));

        dump([
            'Status' => $response->status(),
            'Response type' => get_class($response->baseResponse ?? $response),
        ]);

        // Revisar si hay algún error en la sesión
        if ($response->status() === 403) {
            dump([
                'Session errors' => session()->get('errors'),
                'Session all' => session()->all(),
            ]);
        }

        $this->assertTrue(true);
    }
}
