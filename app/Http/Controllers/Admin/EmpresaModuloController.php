<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaModuloController extends Controller
{
    public function toggle(Request $request, Empresa $empresa, Modulo $modulo): JsonResponse
    {
        $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $existe = $empresa->modulos()->where('modulo_id', $modulo->id)->exists();

        if ($existe) {
            $empresa->modulos()->updateExistingPivot($modulo->id, ['activo' => $request->boolean('activo')]);
        } else {
            $empresa->modulos()->attach($modulo->id, ['activo' => $request->boolean('activo')]);
        }

        $estado = $request->boolean('activo') ? 'activado' : 'desactivado';

        return response()->json(['message' => "Módulo {$estado} correctamente."]);
    }
}
