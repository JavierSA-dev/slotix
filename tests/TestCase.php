<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // En testing con SQLite :memory:, compartir el mismo PDO entre la conexión
        // por defecto y 'central' para que las migraciones sean visibles en ambas.
        if (config('database.default') === 'sqlite') {
            DB::connection('central')->setPdo(DB::connection()->getPdo());
        }

        // Limpiar caché de permisos de Spatie en cada test
        if (method_exists(\Spatie\Permission\PermissionRegistrar::class, 'forgetCachedPermissions')) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
}
