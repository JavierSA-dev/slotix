<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // Rutas de autenticación y admin siempre accesibles
        if ($request->is('admin', 'admin/*', 'login', 'logout', 'password/*', 'home', 'update-password/*', 'update-profile/*')) {
            return $next($request);
        }

        // Admins autenticados siempre pueden pasar
        if (auth()->check() && auth()->user()->hasAnyRole(['SuperAdmin', 'Admin'])) {
            return $next($request);
        }

        try {
            // Leemos en_mantenimiento de la Empresa (BD central) para evitar
            // problemas cuando el tenant aún no está inicializado en este middleware global
            $empresaId = session('empresa_id');
            if ($empresaId) {
                $empresa = Empresa::find($empresaId);
                if ($empresa && $empresa->en_mantenimiento) {
                    return response()->view('mantenimiento.public', [], 503);
                }
            }
        } catch (\Exception $e) {
            // Si hay error de BD, no bloqueamos
        }

        return $next($request);
    }
}
