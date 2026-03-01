<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DataTableActions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests para DataTableActions helper.
 *
 * Verifican que los botones de acción se generan correctamente.
 */
class DataTableActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = User::factory()->create();
    }

    // =========================================================================
    // Tests de renderizado básico
    // =========================================================================

    /** @test */
    public function genera_todos_los_botones_por_defecto(): void
    {
        $html = DataTableActions::render($this->testUser, 'users');

        // Verificar que contiene los 3 botones
        $this->assertStringContainsString('btn-primary', $html); // Ver
        $this->assertStringContainsString('btn-success', $html); // Editar
        $this->assertStringContainsString('btn-danger', $html);  // Eliminar
        $this->assertStringContainsString('fa-eye', $html);
        $this->assertStringContainsString('fa-edit', $html);
        $this->assertStringContainsString('fa-trash', $html);
    }

    /** @test */
    public function genera_rutas_correctas(): void
    {
        $html = DataTableActions::render($this->testUser, 'users');

        // Verificar que las rutas contienen el ID del usuario
        $this->assertStringContainsString("users/{$this->testUser->id}", $html);
    }

    /** @test */
    public function puede_ocultar_boton_ver(): void
    {
        $html = DataTableActions::render($this->testUser, 'users', show: false);

        $this->assertStringNotContainsString('fa-eye', $html);
        $this->assertStringContainsString('fa-edit', $html);
        $this->assertStringContainsString('fa-trash', $html);
    }

    /** @test */
    public function puede_ocultar_boton_editar(): void
    {
        $html = DataTableActions::render($this->testUser, 'users', edit: false);

        $this->assertStringContainsString('fa-eye', $html);
        $this->assertStringNotContainsString('fa-edit', $html);
        $this->assertStringContainsString('fa-trash', $html);
    }

    /** @test */
    public function puede_ocultar_boton_eliminar(): void
    {
        $html = DataTableActions::render($this->testUser, 'users', delete: false);

        $this->assertStringContainsString('fa-eye', $html);
        $this->assertStringContainsString('fa-edit', $html);
        $this->assertStringNotContainsString('fa-trash', $html);
    }

    // =========================================================================
    // Tests de botones AJAX
    // =========================================================================

    /** @test */
    public function editar_como_boton_ajax_genera_button_no_anchor(): void
    {
        $html = DataTableActions::render($this->testUser, 'users', editAsButton: true);

        // Debe ser button, no anchor
        $this->assertStringContainsString('<button type="button"', $html);
        $this->assertStringContainsString('btn-edit', $html);
        $this->assertStringContainsString('data-url=', $html);
    }

    /** @test */
    public function eliminar_como_boton_ajax_genera_button_no_form(): void
    {
        $html = DataTableActions::render($this->testUser, 'users', deleteAsButton: true);

        // Debe ser button con clase btn-delete, no form
        $this->assertStringContainsString('btn-delete', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('method="POST"', $html);
    }

    // =========================================================================
    // Tests con Policy
    // =========================================================================

    /** @test */
    public function con_policy_oculta_botones_sin_permiso(): void
    {
        // Crear permisos necesarios para que la Policy funcione
        Permission::create(['name' => 'users.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web']);

        // Crear un usuario sin permisos
        $userWithoutPermissions = User::factory()->create();

        // Autenticar como usuario sin permisos
        $this->actingAs($userWithoutPermissions);

        $targetUser = User::factory()->create();

        $html = DataTableActions::render($targetUser, 'users', withPolicy: true);

        // Sin permisos, no debería mostrar botones de editar/eliminar
        $this->assertStringNotContainsString('fa-eye', $html);
        $this->assertStringNotContainsString('fa-edit', $html);
        $this->assertStringNotContainsString('fa-trash', $html);
    }

    /** @test */
    public function superadmin_ve_todos_los_botones_con_policy(): void
    {
        // Crear SuperAdmin
        Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.show', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SuperAdmin');

        $this->actingAs($superAdmin);

        $targetUser = User::factory()->create();

        $html = DataTableActions::render($targetUser, 'users', withPolicy: true);

        // SuperAdmin debe ver todos los botones
        $this->assertStringContainsString('fa-eye', $html);
        $this->assertStringContainsString('fa-edit', $html);
        $this->assertStringContainsString('fa-trash', $html);
    }

    // =========================================================================
    // Tests de estructura HTML
    // =========================================================================

    /** @test */
    public function contenedor_tiene_clase_flex(): void
    {
        $html = DataTableActions::render($this->testUser, 'users');

        $this->assertStringContainsString('d-flex', $html);
        $this->assertStringContainsString('gap-1', $html);
        $this->assertStringContainsString('justify-content-center', $html);
    }

    /** @test */
    public function botones_tienen_tamaño_sm(): void
    {
        $html = DataTableActions::render($this->testUser, 'users');

        $this->assertStringContainsString('btn-sm', $html);
    }
}
