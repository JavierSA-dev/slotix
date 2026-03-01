<?php

namespace App\Http\Middleware;

use App\Models\HorarioConfig;
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
            if (HorarioConfig::enMantenimiento()) {
                return response()->view('mantenimiento.public', [], 503);
            }
        } catch (\Exception $e) {
            // Si hay error de BD, no bloqueamos
        }

        return $next($request);
    }
}
