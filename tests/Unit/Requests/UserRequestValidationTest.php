<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'SuperAdmin']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'User']);

        Permission::create(['name' => 'users.index']);
        Permission::create(['name' => 'users.create']);
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => ['User'],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_without_name(): void
    {
        $data = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_without_email(): void
    {
        $data = [
            'name' => 'John Doe',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_email_format(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $data = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_short_password(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'newpassword' => '123', // Menos de 8 caracteres
            'newpassword_confirmation' => '123',
        ];

        // Simular creación (POST sin user en ruta)
        $request = new UserRequest;
        $request->setMethod('POST');
        $request->replace($data);

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('newpassword', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_very_long_name(): void
    {
        $data = [
            'name' => str_repeat('a', 255), // Exactamente 255 caracteres
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_name_too_long(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // Más de 255 caracteres
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_email_too_long(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => str_repeat('a', 250).'@example.com', // Más de 255 caracteres
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_non_existent_role(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => ['NonExistentRole'],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('role.0', $validator->errors()->toArray());
    }

    public function test_permissions_no_se_validan_directamente(): void
    {
        // Los permisos ya no se asignan directamente a usuarios (solo a través de roles),
        // por lo que el campo 'permissions' es ignorado por la validación.
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'permissions' => ['non.existent.permission'],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_xss_attempt_but_should_be_sanitized(): void
    {
        $data = [
            'name' => '<script>alert("XSS")</script>John',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        // La validación pasa, pero el nombre debería ser sanitizado en el modelo o controlador
        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_special_characters_in_name(): void
    {
        $data = [
            'name' => "O'Brien-Jones (Jr.)",
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_international_characters_in_name(): void
    {
        $data = [
            'name' => 'José María Núñez Ñoño',
            'email' => 'jose@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_email_with_plus_sign(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john+test@example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_email_with_subdomain(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@mail.example.com',
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_handles_sql_injection_attempt_in_email(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => "admin'--@example.com",  // Técnicamente es un email válido según RFC
            'password' => 'password123',
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        // Laravel acepta esto como email válido (lo cual es correcto según RFC)
        // La protección contra SQL injection viene de usar prepared statements, no de validación de email
        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_multiple_roles(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => ['User', 'Admin'],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_multiple_permissions(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'permissions' => ['users.index', 'users.create'],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_boolean_activo_field(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'activo' => true,
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_empty_role_array(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => [],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validation_accepts_empty_permissions_array(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'permissions' => [],
        ];

        $request = new UserRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
