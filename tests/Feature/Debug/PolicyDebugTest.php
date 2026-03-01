<?php

namespace Tests\Feature\Debug;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Facades\Gate;

/**
 * Test para debuggear el problema de permisos.
 */
class PolicyDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_permisos_y_policy(): void
    {
        // Setup
        Permission::create(['name' => 'users.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web']);

        $superAdminRole = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo(['users.index', 'users.show', 'users.delete']);

        $adminUser = User::factory()->create();
        $adminUser->assignRole('SuperAdmin');
        $adminUser->givePermissionTo(['users.index', 'users.show', 'users.delete']);

        $normalUser = User::factory()->create();

        // Autenticar
        $this->actingAs($adminUser);

        // DEBUGGEAR: Verificar permisos
        dump([
            'AdminUser ID' => $adminUser->id,
            'Tiene rol SuperAdmin?' => $adminUser->hasRole('SuperAdmin'),
            'Permisos directos' => $adminUser->permissions->pluck('name')->toArray(),
            'Todos los permisos (con roles)' => $adminUser->getAllPermissions()->pluck('name')->toArray(),
            'Puede users.index?' => $adminUser->hasPermissionTo('users.index'),
            'Puede users.show?' => $adminUser->hasPermissionTo('users.show'),
            'Puede users.delete?' => $adminUser->hasPermissionTo('users.delete'),
        ]);

        // DEBUGGEAR: Verificar Gate
        dump([
            'Gate::allows(view, normalUser)?' => Gate::allows('view', $normalUser),
            'Gate::allows(delete, normalUser)?' => Gate::allows('delete', $normalUser),
            'Gate::check(view, normalUser)?' => Gate::check('view', $normalUser),
        ]);

        // Intentar acceder a la ruta
        $response = $this->get(route('users.show', $normalUser->id));

        dump([
            'Status Code' => $response->status(),
            'Response' => $response->getContent(),
        ]);

        // El test falla intencionalmente para ver el dump
        $this->assertTrue(true);
    }
}
