<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
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

    public function test_user_can_be_assigned_a_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->assertTrue($user->hasRole('User'));
    }

    public function test_user_can_be_assigned_multiple_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['User', 'Admin']);

        $this->assertTrue($user->hasRole('User'));
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_user_can_have_role_removed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        
        $this->assertTrue($user->hasRole('User'));
        
        $user->removeRole('User');
        
        $this->assertFalse($user->hasRole('User'));
    }

    public function test_role_can_be_assigned_permissions(): void
    {
        $role = Role::findByName('Admin');
        $role->givePermissionTo('users.index');

        $this->assertTrue($role->hasPermissionTo('users.index'));
    }

    public function test_role_can_have_multiple_permissions(): void
    {
        $role = Role::findByName('Admin');
        $role->givePermissionTo(['users.index', 'users.create', 'users.edit']);

        $this->assertTrue($role->hasPermissionTo('users.index'));
        $this->assertTrue($role->hasPermissionTo('users.create'));
        $this->assertTrue($role->hasPermissionTo('users.edit'));
    }

    public function test_role_can_have_permission_revoked(): void
    {
        $role = Role::findByName('Admin');
        $role->givePermissionTo('users.index');
        
        $this->assertTrue($role->hasPermissionTo('users.index'));
        
        $role->revokePermissionTo('users.index');
        
        $this->assertFalse($role->hasPermissionTo('users.index'));
    }

    public function test_user_inherits_permissions_from_role(): void
    {
        $role = Role::findByName('Admin');
        $role->givePermissionTo('users.index');
        
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertTrue($user->hasPermissionTo('users.index'));
    }

    public function test_user_can_have_direct_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo('users.create');

        $this->assertTrue($user->hasPermissionTo('users.create'));
    }

    public function test_user_permissions_combine_role_and_direct_permissions(): void
    {
        $role = Role::findByName('User');
        $role->givePermissionTo('users.index');
        
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo('users.create');

        $this->assertTrue($user->hasPermissionTo('users.index')); // Del rol
        $this->assertTrue($user->hasPermissionTo('users.create')); // Directo
    }

    public function test_sync_roles_replaces_existing_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['User', 'Admin']);
        
        $this->assertTrue($user->hasRole('User'));
        $this->assertTrue($user->hasRole('Admin'));
        
        $user->syncRoles(['Admin']);
        
        $this->assertFalse($user->hasRole('User'));
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_sync_permissions_replaces_existing_permissions(): void
    {
        $role = Role::findByName('Admin');
        $role->givePermissionTo(['users.index', 'users.create']);
        
        $role->syncPermissions(['users.edit', 'users.delete']);
        
        $this->assertFalse($role->hasPermissionTo('users.index'));
        $this->assertFalse($role->hasPermissionTo('users.create'));
        $this->assertTrue($role->hasPermissionTo('users.edit'));
        $this->assertTrue($role->hasPermissionTo('users.delete'));
    }

    public function test_check_permission_via_any_method(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo(['users.index', 'users.create']);

        $this->assertTrue($user->hasAnyPermission(['users.index', 'users.edit']));
        $this->assertFalse($user->hasAnyPermission(['users.edit', 'users.delete']));
    }

    public function test_check_all_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $user->givePermissionTo(['users.index', 'users.create']);

        $this->assertTrue($user->hasAllPermissions(['users.index', 'users.create']));
        $this->assertFalse($user->hasAllPermissions(['users.index', 'users.edit']));
    }

    public function test_role_hierarchy_is_respected(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->assertEquals(1, $superAdmin->getHierarchyLevel());
        $this->assertEquals(2, $admin->getHierarchyLevel());
        $this->assertEquals(3, $user->getHierarchyLevel());
    }

    public function test_can_check_if_user_has_any_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertTrue($user->hasAnyRole(['Admin', 'SuperAdmin']));
        $this->assertFalse($user->hasAnyRole(['User', 'SuperAdmin']));
    }

    public function test_can_check_if_user_has_all_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['Admin', 'User']);

        $this->assertTrue($user->hasAllRoles(['Admin', 'User']));
        $this->assertFalse($user->hasAllRoles(['Admin', 'SuperAdmin']));
    }

    public function test_permission_can_be_created_dynamically(): void
    {
        $permission = Permission::create(['name' => 'posts.create']);

        $this->assertDatabaseHas('permissions', [
            'name' => 'posts.create',
        ]);
    }

    public function test_role_can_be_created_dynamically(): void
    {
        $role = Role::create(['name' => 'Editor']);

        $this->assertDatabaseHas('roles', [
            'name' => 'Editor',
        ]);
    }

    public function test_user_without_roles_has_no_permissions(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermissionTo('users.index'));
        $this->assertFalse($user->hasRole('User'));
    }

    public function test_removing_all_roles_removes_role_based_permissions(): void
    {
        $role = Role::findByName('Admin');
        $role->givePermissionTo('users.index');
        
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo('users.create'); // Permiso directo
        
        $this->assertTrue($user->hasPermissionTo('users.index')); // Del rol
        $this->assertTrue($user->hasPermissionTo('users.create')); // Directo
        
        $user->removeRole('Admin');
        
        $this->assertFalse($user->hasPermissionTo('users.index')); // Del rol eliminado
        $this->assertTrue($user->hasPermissionTo('users.create')); // Directo permanece
    }

    public function test_superadmin_role_exists_in_database(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'SuperAdmin']);
    }

    public function test_admin_role_exists_in_database(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
    }

    public function test_user_role_exists_in_database(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'User']);
    }
}
