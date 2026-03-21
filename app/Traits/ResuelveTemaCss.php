<?php

namespace App\Traits;

use App\Models\Empresa;
use App\Providers\AppServiceProvider;

trait ResuelveTemaCss
{
    protected function resolverTemaCss(): string
    {
        // Si la tenencia ya está inicializada (contexto público con slug)
        if (tenancy()->initialized) {
            $tenant = tenancy()->tenant;

            return AppServiceProvider::generarTemaCss($tenant->tema ?? 'neon', $tenant->colores ?? []);
        }

        $empresaId = session('empresa_usuario_id') ?? session('empresa_id');

        if (! $empresaId && auth()->check()) {
            $empresaId = auth()->user()->empresas()->value('tenants.id');
        }

        if (! $empresaId) {
            return AppServiceProvider::generarTemaCss('neon', []);
        }

        $empresa = Empresa::find($empresaId);

        if (! $empresa) {
            return AppServiceProvider::generarTemaCss('neon', []);
        }

        tenancy()->initialize($empresa);
        $tenant = tenancy()->tenant;
        $css = AppServiceProvider::generarTemaCss($tenant->tema ?? 'neon', $tenant->colores ?? []);
        tenancy()->end();

        return $css;
    }
}
