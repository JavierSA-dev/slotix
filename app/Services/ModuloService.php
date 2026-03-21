<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Collection;

class ModuloService
{
    public function getModulosActivos(Empresa $empresa): Collection
    {
        return $empresa->modulosActivos()->get();
    }

    public function empresaTieneModulo(Empresa $empresa, string $nombre): bool
    {
        return $empresa->tieneModulo($nombre);
    }
}
