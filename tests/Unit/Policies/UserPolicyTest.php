<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests para UserPolicy.
 *
 * Verifican que las reglas de autorización funcionan correctamente.
 * Importante: Cada método de la Policy debe tener al menos un test.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $policy;
    protected User $superAdmin;
    protected User $adminWithPermissions;
    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy();

        // Crear roles
        Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'User', 'guard_name' => 'web']);

        // Crear permisos
        Permission::create(['name' => 'users.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web']);

        // SuperAdmin (tiene acceso a todo por el método before())
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('SuperAdmin');

        // Admin con permisos específicos (necesita rol para jerarquía)
        $this->adminWithPermissions = User::factory()->create();
        $this->adminWithPermissions->assignRole('Admin');
        $this->adminWithPermissions->givePermissionTo(['users.index', 'users.show', 'users.edit', 'users.delete']);

        // Usuario normal sin permisos
        $this->normalUser = User::factory()->create();
    }

    // =========================================================================
    // Tests del método before() - SuperAdmin bypass
    // =========================================================================

    /** @test */
    public function superadmin_puede_hacer_cualquier_accion(): void
    {
        $targetUser = User::factory()->create();

        // El método before() retorna true para SuperAdmin
        $this->assertTrue($this->policy->before($this->superAdmin, 'viewAny'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'view'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'create'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'update'));
        $this->assertTrue($this->policy->before($this->superAdmin, 'delete'));
    }

    /** @test */
    public function before_retorna_null_para_usuarios_normales(): void
    {
        // Debe retornar null para que continúe evaluando las policies individuales
        $result = $this->policy->before($this->normalUser, 'viewAny');

        $this->assertNull($result);
    }

    // =========================================================================
    // Tests de viewAny (index)
    // =========================================================================

    /** @test */
    public function usuario_con_permiso_puede_ver_listado(): void
    {
        $this->assertTrue($this->policy->viewAny($this->adminWithPermissions));
    }

    /** @test */
    public function usuario_sin_permiso_no_puede_ver_listado(): void
    {
        $this->assertFalse($this->policy->viewAny($this->normalUser));
    }

    // =========================================================================
    // Tests de view (show)
    // =========================================================================

    /** @test */
    public function usuario_con_permiso_puede_ver_otro_usuario(): void
    {
        $targetUser = User::factory()->create();

        $this->assertTrue($this->policy->view($this->adminWithPermissions, $targetUser));
    }

    /** @test */
    public function usuario_puede_verse_a_si_mismo(): void
    {
        // Aunque no tenga el permiso users.show, puede ver su propio perfil
        $this->normalUser->givePermissionTo('users.index'); // Solo tiene index

        // Verificamos que puede ver su propio perfil
        $this->assertTrue($this->policy->view($this->normalUser, $this->normalUser));
    }

    /** @test */
    public function usuario_sin_permiso_no_puede_ver_otros_usuarios(): void
    {
        $targetUser = User::factory()->create();

        $this->assertFalse($this->policy->view($this->normalUser, $targetUser));
    }

    // =========================================================================
    // Tests de create
    // =========================================================================

    /** @test */
    public function usuario_con_permiso_puede_crear_usuarios(): void
    {
        $userWithCreatePermission = User::factory()->create();
        $userWithCreatePermission->givePermissionTo('users.create');

        $this->assertTrue($this->policy->create($userWithCreatePermission));
    }

    /** @test */
    public function usuario_sin_permiso_no_puede_crear_usuarios(): void
    {
        $this->assertFalse($this->policy->create($this->normalUser));
    }

    // =========================================================================
    // Tests de update (edit)
    // =========================================================================

    /** @test */
    public function usuario_con_permiso_puede_editar_usuarios_de_nivel_inferior(): void
    {
        // Admin puede editar usuarios de nivel inferior (User)
        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        $this->assertTrue($this->policy->update($this->adminWithPermissions, $targetUser));
    }

    /** @test */
    public function usuario_puede_editar_su_propio_perfil(): void
    {
        // Aunque no tenga users.edit, debería poder editarse a sí mismo
        // (si la policy lo permite - depende de tu implementación)
        $this->assertTrue($this->policy->update($this->normalUser, $this->normalUser));
    }

    /** @test */
    public function usuario_sin_permiso_no_puede_editar_otros(): void
    {
        $targetUser = User::factory()->create();

        $this->assertFalse($this->policy->update($this->normalUser, $targetUser));
    }

    // =========================================================================
    // Tests de delete
    // =========================================================================

    /** @test */
    public function usuario_con_permiso_puede_eliminar_usuarios_de_nivel_inferior(): void
    {
        // Admin con permiso delete puede eliminar usuarios de nivel inferior
        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        $this->assertTrue($this->policy->delete($this->adminWithPermissions, $targetUser));
    }

    /** @test */
    public function usuario_no_puede_eliminarse_a_si_mismo(): void
    {
        // Protección: nadie debería poder auto-eliminarse
        $this->assertFalse($this->policy->delete($this->superAdmin, $this->superAdmin));
    }

    /** @test */
    public function usuario_sin_permiso_no_puede_eliminar(): void
    {
        $targetUser = User::factory()->create();

        $this->assertFalse($this->policy->delete($this->normalUser, $targetUser));
    }
}
