<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles con firstOrCreate
        $rolSuperAdmin = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $rolAdmin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $rolUser = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        // ─────────────────────────────────────────────────────────────────────
        // PERMISOS DE ROLES (Solo SuperAdmin)
        // ─────────────────────────────────────────────────────────────────────
        $permisosRoles = [
            'roles.index',
            'roles.create',
            'roles.show',
            'roles.edit',
        ];

        foreach ($permisosRoles as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
        $rolSuperAdmin->givePermissionTo($permisosRoles);

        // ─────────────────────────────────────────────────────────────────────
        // PERMISOS DE PERMISOS (Solo SuperAdmin)
        // ─────────────────────────────────────────────────────────────────────
        $permisosPermisos = [
            'permissions.index',
            'permissions.create',
            'permissions.show',
            'permissions.edit',
        ];

        foreach ($permisosPermisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
        $rolSuperAdmin->givePermissionTo($permisosPermisos);

        // ─────────────────────────────────────────────────────────────────────
        // PERMISOS DE USUARIOS (SuperAdmin y Admin)
        // ─────────────────────────────────────────────────────────────────────
        $permisosUsuarios = [
            'users.index',
            'users.create',
            'users.show',
            'users.edit',
        ];

        foreach ($permisosUsuarios as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }
        $rolSuperAdmin->givePermissionTo($permisosUsuarios);
        $rolAdmin->givePermissionTo($permisosUsuarios);

        // ─────────────────────────────────────────────────────────────────────
        // ASIGNAR ROL SUPERADMIN AL USUARIO PRINCIPAL
        // ─────────────────────────────────────────────────────────────────────
        $user = User::where('email', 'desarrollo@tallerempresarial.es')->first();
        if ($user && !$user->hasRole('SuperAdmin')) {
            $user->assignRole($rolSuperAdmin);
            $this->command->info('Rol SuperAdmin asignado al usuario desarrollo@tallerempresarial.es');
        }
    }
}
