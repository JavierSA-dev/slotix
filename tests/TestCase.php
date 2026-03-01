<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar caché de permisos de Spatie en cada test
        if (method_exists(\Spatie\Permission\PermissionRegistrar::class, 'forgetCachedPermissions')) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
}
