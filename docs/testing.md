# Testing

Guía completa de la suite de tests del Starter Kit TE 2.0.

---

## Ejecución rápida

```bash
# Todos los tests
php artisan test

# Solo Unit tests (rápidos, sin BD)
php artisan test --testsuite=Unit

# Solo Feature tests (con BD de test)
php artisan test --testsuite=Feature

# Con cobertura de código
php artisan test --coverage

# Un fichero o clase concretos
php artisan test tests/Unit/Traits/HasRoleHierarchyTest.php
php artisan test --filter HasRoleHierarchyTest

# Un test concreto
php artisan test --filter "test_admin_cannot_assign_superadmin"
```

> **Regla de oro:** nunca desplegar con tests rotos.

---

## Cobertura actual

| Suite | Clase | Qué cubre |
|-------|-------|-----------|
| Unit | `HasRoleHierarchyTest` | Lógica de jerarquía de roles (niveles, `canAssignRole`, `canManageUser`) |
| Unit | `UserServiceTest` | Creación, actualización, sincronización de roles y permisos |
| Feature | `UserPolicyTest` | Autorización: quién puede ver, crear, editar y borrar usuarios |
| Feature | `UserControllerTest` | Endpoints HTTP: respuestas, redirecciones, validaciones |

---

## Estructura de directorios

```
tests/
├── Unit/
│   ├── Traits/
│   │   └── HasRoleHierarchyTest.php
│   └── Services/
│       └── UserServiceTest.php
└── Feature/
    ├── Policies/
    │   └── UserPolicyTest.php
    └── Controllers/
        └── UserControllerTest.php
```

---

## Configuración del entorno de test

Los tests usan una base de datos SQLite en memoria definida en `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Cada test que usa la BD debe incluir el trait `RefreshDatabase`:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MiTest extends TestCase
{
    use RefreshDatabase;
}
```

---

## Cómo escribir nuevos tests

### Test unitario (sin BD)

Ideal para lógica de negocio pura (traits, servicios, helpers):

```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductService;

class ProductServiceTest extends TestCase
{
    public function test_calcula_precio_con_iva(): void
    {
        $service = new ProductService();
        $this->assertEquals(121.0, $service->precioConIva(100.0, 21));
    }
}
```

### Test de Policy (Feature)

```php
namespace Tests\Feature\Policies;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Crear roles necesarios
        \Spatie\Permission\Models\Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'Admin',      'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'User',       'guard_name' => 'web']);
    }

    public function test_admin_puede_ver_productos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->assertTrue($admin->can('viewAny', Product::class));
    }

    public function test_usuario_no_puede_crear_productos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->assertFalse($user->can('create', Product::class));
    }
}
```

### Test de Controller (Feature)

```php
namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requiere_autenticacion(): void
    {
        $response = $this->get(route('products.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_puede_ver_lista_de_productos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('products.index'));
        $response->assertOk();
    }

    public function test_store_crea_producto_correctamente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'name'  => 'Producto Test',
            'price' => 99.99,
            'stock' => 10,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('products', ['name' => 'Producto Test']);
    }
}
```

---

## Jerarquía de roles en los tests

El sistema de roles tiene tres niveles definidos en `config/roles.php`:

| Rol | Nivel | Puede gestionar |
|-----|-------|-----------------|
| SuperAdmin | 1 | SuperAdmin, Admin, User |
| Admin | 2 | Admin, User |
| User | 3 | — |

En los tests, **siempre crea los tres roles en `setUp()`** para evitar errores:

```php
protected function setUp(): void
{
    parent::setUp();
    \Spatie\Permission\Models\Role::create(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Role::create(['name' => 'Admin',      'guard_name' => 'web']);
    \Spatie\Permission\Models\Role::create(['name' => 'User',       'guard_name' => 'web']);
}
```

---

## Patrones útiles

### Crear usuario con rol y permisos

```php
$admin = User::factory()->create();
$admin->assignRole('Admin');
$admin->givePermissionTo(['users.view', 'users.create']);
```

### Simular request autenticado

```php
$this->actingAs($admin)->get(route('users.index'));
$this->actingAs($admin)->post(route('users.store'), $data);
$this->actingAs($admin)->delete(route('users.destroy', $user));
```

### Verificar respuesta JSON de CRUD

```php
$response->assertJson(['success' => true]);
$response->assertJson(['success' => false]);
$response->assertJsonValidationErrors(['name', 'email']);
```

### Verificar en BD

```php
$this->assertDatabaseHas('users', ['email' => 'test@test.com']);
$this->assertDatabaseMissing('users', ['email' => 'borrado@test.com']);
```
