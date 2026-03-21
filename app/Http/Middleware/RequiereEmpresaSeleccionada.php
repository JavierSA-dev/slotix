<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiereEmpresaSeleccionada
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // SuperAdmin: si no tiene empresa seleccionada, autoasignar la primera disponible
        if ($user->hasRole('SuperAdmin')) {
            if (! session('empresa_id')) {
                $primera = Empresa::where('activo', true)->first();
                if ($primera) {
                    session(['empresa_id' => $primera->id]);
                }
            }

            return $next($request);
        }

        // Admin: si no tiene empresa seleccionada, autoasignar su primera empresa
        if (! session('empresa_id')) {
            $primera = $user->empresas()->where('tenants.activo', true)->first();
            if ($primera) {
                session(['empresa_id' => $primera->id]);
            }
        }

        // Si aún sin empresa y no es SuperAdmin, redirigir al home
        if (! session('empresa_id')) {
            abort(403, 'No tienes ninguna empresa asignada.');
        }

        return $next($request);
    }
}
