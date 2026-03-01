<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles
        Role::create(['name' => 'SuperAdmin']);
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'User']);
        
        // Crear permiso
        Permission::create(['name' => 'users.index']);
        
        // Asignar permiso al rol Admin
        $adminRole = Role::findByName('Admin');
        $adminRole->givePermissionTo('users.index');
    }

    public function test_user_without_permission_cannot_export_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('User');

        $response = $this->actingAs($user)->get('/users/export');
        
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302
        );
    }

    public function test_user_with_permission_can_export_users(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        // Crear algunos usuarios para exportar
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/users/export');
        
        $response->assertStatus(200);
        Excel::assertDownloaded('usuarios.xlsx');
    }

    public function test_export_contains_all_users(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        // Crear usuarios
        $users = User::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get('/users/export');
        
        Excel::assertDownloaded('usuarios.xlsx', function (UsersExport $export) use ($users) {
            $collection = $export->collection();
            
            // +1 por el admin
            $this->assertCount($users->count() + 1, $collection);
            return true;
        });
    }

    public function test_export_filters_by_search_term(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        // Crear usuarios con nombres específicos
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'John Johnson']);

        $response = $this->actingAs($admin)->get('/users/export?search=John');
        
        Excel::assertDownloaded('usuarios.xlsx', function (UsersExport $export) {
            $collection = $export->collection();
            
            foreach ($collection as $user) {
                $this->assertStringContainsString('John', $user->name);
            }
            return true;
        });
    }

    public function test_export_filters_by_role(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        // Crear usuarios con diferentes roles
        $user1 = User::factory()->create();
        $user1->assignRole('User');
        
        $user2 = User::factory()->create();
        $user2->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/users/export?role=User');
        
        Excel::assertDownloaded('usuarios.xlsx', function (UsersExport $export) {
            $collection = $export->collection();
            
            foreach ($collection as $user) {
                $this->assertTrue($user->hasRole('User'));
            }
            return true;
        });
    }

    public function test_export_filters_by_active_status(): void
    {
        Excel::fake();

        $admin = User::factory()->create(['activo' => 1]);
        $admin->assignRole('Admin');
        
        // Crear usuarios activos e inactivos
        User::factory()->count(2)->create(['activo' => 1]);
        User::factory()->count(3)->create(['activo' => 0]);

        $response = $this->actingAs($admin)->get('/users/export?active=1');
        
        Excel::assertDownloaded('usuarios.xlsx', function (UsersExport $export) {
            $collection = $export->collection();
            
            foreach ($collection as $user) {
                $this->assertEquals(1, $user->activo);
            }
            return true;
        });
    }

    public function test_export_filters_by_date_range(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        
        // Crear usuarios con diferentes fechas
        User::factory()->create(['created_at' => now()->subDays(10)]);
        User::factory()->create(['created_at' => now()->subDays(5)]);
        User::factory()->create(['created_at' => now()->subDays(1)]);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($admin)
            ->get("/users/export?date={$startDate} - {$endDate}");
        
        Excel::assertDownloaded('usuarios.xlsx');
    }

    public function test_export_with_multiple_filters(): void
    {
        Excel::fake();

        $admin = User::factory()->create(['activo' => 1]);
        $admin->assignRole('Admin');
        
        // Crear usuarios variados
        $user1 = User::factory()->create([
            'name' => 'John Active',
            'activo' => 1,
        ]);
        $user1->assignRole('User');
        
        $user2 = User::factory()->create([
            'name' => 'Jane Active',
            'activo' => 1,
        ]);
        $user2->assignRole('User');
        
        $user3 = User::factory()->create([
            'name' => 'John Inactive',
            'activo' => 0,
        ]);
        $user3->assignRole('User');

        $response = $this->actingAs($admin)
            ->get('/users/export?search=John&role=User&active=1');
        
        Excel::assertDownloaded('usuarios.xlsx', function (UsersExport $export) {
            $collection = $export->collection();
            
            foreach ($collection as $user) {
                $this->assertStringContainsString('John', $user->name);
                $this->assertTrue($user->hasRole('User'));
                $this->assertEquals(1, $user->activo);
            }
            return true;
        });
    }

    public function test_export_returns_excel_file(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get('/users/export');
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_with_no_results_still_downloads(): void
    {
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Buscar algo que no existe
        $response = $this->actingAs($admin)->get('/users/export?search=NonExistentUser12345');
        
        Excel::assertDownloaded('usuarios.xlsx', function (UsersExport $export) {
            $collection = $export->collection();
            $this->assertCount(0, $collection);
            return true;
        });
    }
}
