<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests unitarios para UserService.
 *
 * Estos tests verifican la lógica de negocio aislada del controlador.
 * Usan RefreshDatabase para tener una BD limpia en cada test.
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService;
    }

    // =========================================================================
    // Tests de creación de usuario
    // =========================================================================

    /** @test */
    public function puede_crear_un_usuario_con_password_hasheado(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'newpassword' => 'plainpassword123',
        ];

        $user = $this->userService->create($data);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Verificar que el password fue hasheado (no es el original)
        $this->assertNotEquals('plainpassword123', $user->password);
        $this->assertTrue(Hash::check('plainpassword123', $user->password));
    }

    /** @test */
    public function puede_crear_usuario_con_roles(): void
    {
        // Crear rol primero
        $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $data = [
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'newpassword' => 'password123',
            'role' => ['Editor'],
        ];

        $user = $this->userService->create($data);

        $this->assertTrue($user->hasRole('Editor'));
    }

    /** @test */
    public function puede_crear_usuario_con_permisos_directos(): void
    {
        $permission = Permission::create(['name' => 'edit-posts', 'guard_name' => 'web']);

        $data = [
            'name' => 'Writer',
            'email' => 'writer@example.com',
            'newpassword' => 'password123',
            'permissions' => ['edit-posts'],
        ];

        $user = $this->userService->create($data);

        $this->assertTrue($user->hasPermissionTo('edit-posts'));
    }

    // =========================================================================
    // Tests de actualización de usuario
    // =========================================================================

    /** @test */
    public function puede_actualizar_datos_basicos_del_usuario(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->userService->update($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    /** @test */
    public function puede_actualizar_password_con_hash(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $this->userService->update($user, [
            'newpassword' => 'newsecurepassword',
        ]);

        $user->refresh();

        $this->assertNotEquals($oldPassword, $user->password);
        $this->assertTrue(Hash::check('newsecurepassword', $user->password));
    }

    /** @test */
    public function no_modifica_password_si_no_se_proporciona(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('originalpassword'),
        ]);
        $originalHash = $user->password;

        $this->userService->update($user, [
            'name' => 'New Name',
            // No se incluye newpassword
        ]);

        $user->refresh();

        $this->assertEquals($originalHash, $user->password);
    }

    /** @test */
    public function puede_sincronizar_roles_en_actualizacion(): void
    {
        $roleAdmin = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $roleEditor = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('Editor'));

        $this->userService->update($user, [
            'role' => ['Editor'], // Cambiar de Admin a Editor
        ]);

        $user->refresh();

        $this->assertFalse($user->hasRole('Admin'));
        $this->assertTrue($user->hasRole('Editor'));
    }

    // =========================================================================
    // Tests de verificación de password
    // =========================================================================

    /** @test */
    public function verifica_password_correcto(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $this->assertTrue(
            $this->userService->verifyCurrentPassword($user, 'correctpassword')
        );
    }

    /** @test */
    public function rechaza_password_incorrecto(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $this->assertFalse(
            $this->userService->verifyCurrentPassword($user, 'wrongpassword')
        );
    }

    // =========================================================================
    // Tests de procesamiento de avatar
    // =========================================================================

    /** @test */
    public function rechaza_archivo_con_mime_type_invalido(): void
    {
        $user = User::factory()->create();

        // Simular un archivo PHP disfrazado de imagen
        $fakeFile = UploadedFile::fake()->create('malware.php', 100, 'application/x-php');

        $result = $this->userService->processAvatar($user, $fakeFile);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function acepta_imagen_valida(): void
    {
        $user = User::factory()->create();

        // Crear imagen fake válida
        $image = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $result = $this->userService->processAvatar($user, $image);

        $this->assertTrue($result['success']);
        $this->assertNotNull($user->fresh()->avatar);
    }

    // =========================================================================
    // Tests de eliminación
    // =========================================================================

    /** @test */
    public function puede_eliminar_usuario(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $this->userService->delete($user);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }
}
