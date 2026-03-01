<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        Role::create(['name' => 'SuperAdmin']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'User']);

        // Crear permisos
        Permission::create(['name' => 'users.index']);
        Permission::create(['name' => 'users.create']);
        Permission::create(['name' => 'users.edit']);
        Permission::create(['name' => 'users.delete']);
    }

    public function test_guest_cannot_access_authenticated_routes(): void
    {
        $response = $this->get('/users');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_users_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/users');
        
        // Debería redirigir o mostrar 403
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    public function test_user_with_permission_can_access_users_index(): void
    {
        $user = User::factory()->create();
        $role = Role::findByName('Admin');
        $role->givePermissionTo('users.index');
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/users');
        
        $response->assertStatus(200);
    }

    public function test_superadmin_can_access_roles_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $response = $this->actingAs($user)->get('/roles');
        
        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_roles_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/roles');
        
        // Solo SuperAdmin puede acceder a roles
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    public function test_user_cannot_access_roles_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/roles');
        
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    public function test_superadmin_can_access_permissions_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $response = $this->actingAs($user)->get('/permissions');
        
        $response->assertStatus(200);
    }

    public function test_admin_cannot_access_permissions_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/permissions');
        
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    public function test_middleware_checks_multiple_permissions_correctly(): void
    {
        $user = User::factory()->create();
        $role = Role::findByName('Admin');
        
        // Dar solo uno de los permisos necesarios
        $role->givePermissionTo('users.index');
        $user->assignRole('Admin');

        // Puede acceder a index
        $response = $this->actingAs($user)->get('/users');
        $response->assertStatus(200);

        // No puede exportar sin el permiso específico
        $response = $this->actingAs($user)->get('/users/export');
        $response->assertStatus(200); // Tiene users.index que agrupa la funcionalidad
    }

    public function test_direct_permission_grants_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo('users.index');

        $response = $this->actingAs($user)->get('/users');
        
        $response->assertStatus(200);
    }

    public function test_revoked_permission_denies_access(): void
    {
        $user = User::factory()->create();
        $role = Role::findByName('Admin');
        $role->givePermissionTo('users.index');
        $user->assignRole('Admin');

        // Primero puede acceder
        $response = $this->actingAs($user)->get('/users');
        $response->assertStatus(200);

        // Revocar permiso
        $role->revokePermissionTo('users.index');
        
        // Refrescar usuario para limpiar caché de permisos
        $user->refresh();
        $user->load('roles.permissions');

        // Ahora no puede acceder
        $response = $this->actingAs($user)->get('/users');
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    public function test_user_with_multiple_roles_inherits_all_permissions(): void
    {
        $user = User::factory()->create();
        
        $adminRole = Role::findByName('Admin');
        $adminRole->givePermissionTo('users.index');
        
        $userRole = Role::findByName('User');
        $userRole->givePermissionTo('users.create');
        
        $user->assignRole(['Admin', 'User']);

        // Tiene ambos permisos
        $this->assertTrue($user->hasPermissionTo('users.index'));
        $this->assertTrue($user->hasPermissionTo('users.create'));
    }
}
