<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests de Feature para UserController.
 *
 * Estos tests verifican el comportamiento completo de los endpoints HTTP,
 * incluyendo autenticación, autorización y respuestas correctas.
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles y permisos base
        $this->setupRolesAndPermissions();

        // Crear usuarios de prueba - SuperAdmin con todos los permisos
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('SuperAdmin');
        // También asignar permisos directamente para evitar problemas de caché
        $this->adminUser->givePermissionTo([
            'users.index', 'users.show', 'users.create', 'users.edit', 'users.delete',
        ]);

        $this->normalUser = User::factory()->create();
    }

    protected function setupRolesAndPermissions(): void
    {
        // Crear permisos de usuarios primero
        $permissions = [
            'users.index', 'users.show', 'users.create', 'users.edit', 'users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear roles según jerarquía
        $superAdminRole = Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($permissions);

        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($permissions);

        Role::create(['name' => 'User', 'guard_name' => 'web']);
    }

    // =========================================================================
    // Tests de autenticación
    // =========================================================================

    /** @test */
    public function usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function usuario_autenticado_puede_acceder_al_index(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
    }

    // =========================================================================
    // Tests de autorización (Policies)
    // =========================================================================

    /** @test */
    public function superadmin_puede_ver_cualquier_usuario(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('users.show', $this->normalUser->id));

        $response->assertRedirect(route('users.index'));
    }

    /** @test */
    public function usuario_normal_no_puede_ver_otros_usuarios(): void
    {
        // Dar permiso de index pero no de ver otros
        $this->normalUser->givePermissionTo('users.index');

        $this->actingAs($this->normalUser);

        $otherUser = User::factory()->create();

        $response = $this->get(route('users.show', $otherUser->id));

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function usuario_puede_ver_su_propio_perfil(): void
    {
        $this->normalUser->givePermissionTo('users.index');
        $this->normalUser->givePermissionTo('users.show');

        $this->actingAs($this->normalUser);

        $response = $this->get(route('users.show', $this->normalUser->id));

        // Debería poder ver su propio perfil (redirecciona al index en la arquitectura modal)
        $response->assertRedirect(route('users.index'));
    }

    // =========================================================================
    // Tests de creación de usuarios
    // =========================================================================

    /** @test */
    public function admin_puede_crear_usuario(): void
    {
        $this->actingAs($this->adminUser);

        $userData = [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'newpassword' => 'password123',
            'newpassword_confirmation' => 'password123',
            'activo' => true,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
        ]);
    }

    /** @test */
    public function no_puede_crear_usuario_con_email_duplicado(): void
    {
        $this->actingAs($this->adminUser);

        // Usar el email del usuario existente
        $userData = [
            'name' => 'Duplicado',
            'email' => $this->normalUser->email,
            'newpassword' => 'password123',
            'newpassword_confirmation' => 'password123',
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function validacion_rechaza_datos_invalidos(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('users.store'), [
            'name' => '', // Requerido
            'email' => 'not-an-email', // Formato inválido
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    // =========================================================================
    // Tests de actualización
    // =========================================================================

    /** @test */
    public function admin_puede_actualizar_usuario(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->put(route('users.update', $this->normalUser->id), [
            'name' => 'Nombre Actualizado',
            'email' => $this->normalUser->email,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertEquals('Nombre Actualizado', $this->normalUser->fresh()->name);
    }

    /** @test */
    public function puede_asignar_roles_validos(): void
    {
        $this->actingAs($this->adminUser);

        // Usar un rol de la jerarquía definida en config/roles.php
        $response = $this->put(route('users.update', $this->normalUser->id), [
            'name' => $this->normalUser->name,
            'email' => $this->normalUser->email,
            'role' => ['User'],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue($this->normalUser->fresh()->hasRole('User'));
    }

    /** @test */
    public function rechaza_roles_inexistentes(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->put(route('users.update', $this->normalUser->id), [
            'name' => $this->normalUser->name,
            'email' => $this->normalUser->email,
            'role' => ['RolQueNoExiste'],
        ]);

        $response->assertSessionHasErrors('role.0');
    }

    // =========================================================================
    // Tests de eliminación
    // =========================================================================

    /** @test */
    public function admin_puede_eliminar_usuario(): void
    {
        $this->actingAs($this->adminUser);

        $userToDelete = User::factory()->create();
        $userId = $userToDelete->id;

        $response = $this->delete(route('users.destroy', $userId));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /** @test */
    public function retorna_404_si_usuario_no_existe(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->delete(route('users.destroy', 99999));

        $response->assertStatus(404);
    }

    // =========================================================================
    // Tests de DataTable AJAX
    // =========================================================================

    /** @test */
    public function getajax_retorna_json_valido(): void
    {
        $this->actingAs($this->adminUser);

        // Crear algunos usuarios adicionales
        User::factory()->count(5)->create();

        $response = $this->getJson(route('users.getAjax'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    /** @test */
    public function getajax_filtra_por_busqueda(): void
    {
        $this->actingAs($this->adminUser);

        User::factory()->create(['name' => 'Juan Pérez']);
        User::factory()->create(['name' => 'María García']);

        $response = $this->getJson(route('users.getAjax', ['search' => 'Juan']));

        $response->assertStatus(200);
        // La respuesta debería contener solo usuarios que coincidan
    }
}
