<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * El orden es importante:
     * 1. UserSeeder - Crea el usuario SuperAdmin
     * 2. RolSeeder - Crea roles, permisos y asigna el rol al usuario
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RolSeeder::class,
            ModuloSeeder::class,
            EmpresaSeeder::class,
        ]);
    }
}
