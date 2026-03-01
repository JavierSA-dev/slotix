<?php

namespace Tests\Feature\Requests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRequestHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'User', 'guard_name' => 'web']);

        // Crear permisos necesarios
        Permission::create(['name' => 'users.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web']);

        // Asignar permisos a SuperAdmin y Admin
        $superAdminRole = Role::findByName('SuperAdmin');
        $superAdminRole->givePermissionTo(['users.index', 'users.create', 'users.show', 'users.edit', 'users.delete']);

        $adminRole = Role::findByName('Admin');
        $adminRole->givePermissionTo(['users.index', 'users.create', 'users.show', 'users.edit', 'users.delete']);
    }

    public function test_admin_cannot_create_user_with_superadmin_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => ['SuperAdmin'],
        ]);

        // La validación debe fallar
        $response->assertSessionHasErrors('role.0');
    }

    public function test_admin_can_create_user_with_user_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'newpassword' => 'password123',
            'newpassword_confirmation' => 'password123',
            'role' => ['User'],
        ]);

        // No debe haber errores de validación de rol
        $response->assertSessionDoesntHaveErrors('role.0');

        // El usuario debe haberse creado
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_admin_can_create_user_with_admin_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Another Admin',
            'email' => 'anotheradmin@example.com',
            'newpassword' => 'password123',
            'newpassword_confirmation' => 'password123',
            'role' => ['Admin'],
        ]);

        $response->assertSessionDoesntHaveErrors('role.0');
        $this->assertDatabaseHas('users', ['email' => 'anotheradmin@example.com']);
    }

    public function test_superadmin_can_create_user_with_any_role(): void
    {
        $superadmin = User::factory()->create(['email_verified_at' => now()]);
        $superadmin->assignRole('SuperAdmin');

        // Puede crear SuperAdmin
        $response = $this->actingAs($superadmin)->post(route('users.store'), [
            'name' => 'New SuperAdmin',
            'email' => 'newsuperadmin@example.com',
            'newpassword' => 'password123',
            'newpassword_confirmation' => 'password123',
            'role' => ['SuperAdmin'],
        ]);

        $response->assertSessionDoesntHaveErrors('role.0');
        $this->assertDatabaseHas('users', ['email' => 'newsuperadmin@example.com']);
    }

    public function test_admin_cannot_update_user_to_superadmin_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $regularUser = User::factory()->create(['email_verified_at' => now()]);
        $regularUser->assignRole('User');

        $response = $this->actingAs($admin)->put(route('users.update', $regularUser), [
            'name' => $regularUser->name,
            'email' => $regularUser->email,
            'role' => ['SuperAdmin'],
        ]);

        // La validación debe fallar
        $response->assertSessionHasErrors('role.0');
    }

    public function test_create_view_only_shows_assignable_roles_for_admin(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        // users.create redirige a users.index (arquitectura modal); los roles se pasan desde index
        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);

        // La vista debe recibir solo roles asignables (Admin, User)
        $roles = $response->viewData('roles');
        $roleNames = $roles->pluck('name')->toArray();

        $this->assertNotContains('SuperAdmin', $roleNames);
        $this->assertContains('Admin', $roleNames);
        $this->assertContains('User', $roleNames);
    }

    public function test_create_view_shows_all_roles_for_superadmin(): void
    {
        $superadmin = User::factory()->create(['email_verified_at' => now()]);
        $superadmin->assignRole('SuperAdmin');

        // users.create redirige a users.index (arquitectura modal); los roles se pasan desde index
        $response = $this->actingAs($superadmin)->get(route('users.index'));

        $response->assertStatus(200);

        $roles = $response->viewData('roles');
        $roleNames = $roles->pluck('name')->toArray();

        $this->assertContains('SuperAdmin', $roleNames);
        $this->assertContains('Admin', $roleNames);
        $this->assertContains('User', $roleNames);
    }

    public function test_admin_cannot_edit_superadmin_user(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $superadmin = User::factory()->create(['email_verified_at' => now()]);
        $superadmin->assignRole('SuperAdmin');

        // La política debe denegar el acceso
        $response = $this->actingAs($admin)->get(route('users.edit', $superadmin));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_delete_superadmin_user(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $superadmin = User::factory()->create(['email_verified_at' => now()]);
        $superadmin->assignRole('SuperAdmin');

        $response = $this->actingAs($admin)->delete(route('users.destroy', $superadmin));

        $response->assertStatus(403);

        // El SuperAdmin sigue existiendo
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    public function test_admin_can_edit_other_admin(): void
    {
        $admin1 = User::factory()->create(['email_verified_at' => now()]);
        $admin1->assignRole('Admin');

        $admin2 = User::factory()->create(['email_verified_at' => now()]);
        $admin2->assignRole('Admin');

        // users.edit devuelve JSON cuando se solicita con Accept: application/json (arquitectura modal)
        $response = $this->actingAs($admin1)->getJson(route('users.edit', $admin2));

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'name', 'email', 'activo', 'roles']);
    }

    public function test_admin_can_edit_regular_user(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $regularUser = User::factory()->create(['email_verified_at' => now()]);
        $regularUser->assignRole('User');

        // users.edit devuelve JSON cuando se solicita con Accept: application/json (arquitectura modal)
        $response = $this->actingAs($admin)->getJson(route('users.edit', $regularUser));

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'name', 'email', 'activo', 'roles']);
    }
}
