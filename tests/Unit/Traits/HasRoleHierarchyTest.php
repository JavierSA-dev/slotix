<?php

namespace Tests\Unit\Traits;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HasRoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles para los tests
        Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'User', 'guard_name' => 'web']);
    }

    public function test_superadmin_has_level_1(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $this->assertEquals(1, $user->getHierarchyLevel());
    }

    public function test_admin_has_level_2(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertEquals(2, $user->getHierarchyLevel());
    }

    public function test_user_has_level_3(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->assertEquals(3, $user->getHierarchyLevel());
    }

    public function test_user_without_role_has_max_level(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(PHP_INT_MAX, $user->getHierarchyLevel());
    }

    public function test_superadmin_can_assign_all_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $this->assertTrue($user->canAssignRole('SuperAdmin'));
        $this->assertTrue($user->canAssignRole('Admin'));
        $this->assertTrue($user->canAssignRole('User'));
    }

    public function test_admin_can_assign_admin_and_user_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertFalse($user->canAssignRole('SuperAdmin'));
        $this->assertTrue($user->canAssignRole('Admin'));
        $this->assertTrue($user->canAssignRole('User'));
    }

    public function test_user_cannot_assign_superadmin_or_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->assertFalse($user->canAssignRole('SuperAdmin'));
        $this->assertFalse($user->canAssignRole('Admin'));
        $this->assertTrue($user->canAssignRole('User'));
    }

    public function test_get_assignable_roles_for_superadmin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $assignable = $user->getAssignableRoles();

        $this->assertContains('SuperAdmin', $assignable);
        $this->assertContains('Admin', $assignable);
        $this->assertContains('User', $assignable);
    }

    public function test_get_assignable_roles_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $assignable = $user->getAssignableRoles();

        $this->assertNotContains('SuperAdmin', $assignable);
        $this->assertContains('Admin', $assignable);
        $this->assertContains('User', $assignable);
    }

    public function test_superadmin_can_manage_any_user(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('SuperAdmin');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $regularUser = User::factory()->create();
        $regularUser->assignRole('User');

        $this->assertTrue($superadmin->canManageUser($admin));
        $this->assertTrue($superadmin->canManageUser($regularUser));
    }

    public function test_admin_can_manage_users_but_not_superadmin(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('SuperAdmin');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('Admin');

        $regularUser = User::factory()->create();
        $regularUser->assignRole('User');

        // Admin puede gestionar usuarios de nivel inferior
        $this->assertTrue($admin->canManageUser($regularUser));

        // Admin NO puede gestionar SuperAdmin
        $this->assertFalse($admin->canManageUser($superadmin));

        // Admin puede gestionar otros Admin (mismo nivel)
        $this->assertTrue($admin->canManageUser($otherAdmin));
    }

    public function test_user_can_manage_only_themselves(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $regularUser = User::factory()->create();
        $regularUser->assignRole('User');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('User');

        // Usuario puede gestionarse a sí mismo
        $this->assertTrue($regularUser->canManageUser($regularUser));

        // Usuario NO puede gestionar otros usuarios del mismo nivel
        $this->assertFalse($regularUser->canManageUser($otherUser));

        // Usuario NO puede gestionar admins
        $this->assertFalse($regularUser->canManageUser($admin));
    }

    public function test_can_create_users_for_allowed_roles(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('SuperAdmin');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $regularUser = User::factory()->create();
        $regularUser->assignRole('User');

        $this->assertTrue($superadmin->canCreateUsers());
        $this->assertTrue($admin->canCreateUsers());
        $this->assertFalse($regularUser->canCreateUsers());
    }

    public function test_get_assignable_role_models_returns_correct_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $roles = $admin->getAssignableRoleModels();

        $roleNames = $roles->pluck('name')->toArray();

        $this->assertNotContains('SuperAdmin', $roleNames);
        $this->assertContains('Admin', $roleNames);
        $this->assertContains('User', $roleNames);
    }
}
