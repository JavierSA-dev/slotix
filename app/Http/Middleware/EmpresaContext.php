<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmpresaContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresaId = session('empresa_id');

        if (! $empresaId) {
            return $next($request);
        }

        $empresa = Empresa::find($empresaId);

        if (! $empresa) {
            session()->forget('empresa_id');

            return $next($request);
        }

        $user = auth()->user();

        if ($user && ! $user->puedeGestionarEmpresa($empresa)) {
            session()->forget('empresa_id');
            abort(403, 'No tienes acceso a esta empresa.');
        }

        tenancy()->initialize($empresa);

        $response = $next($request);

        tenancy()->end();

        return $response;
    }
}
