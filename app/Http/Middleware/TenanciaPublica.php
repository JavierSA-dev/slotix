<?php

namespace App\Http\Middleware;

use App\Models\DemoInvitacion;
use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenanciaPublica
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('empresa');
        $empresa = Empresa::find($slug);

        if (! $empresa || ! $empresa->activo) {
            abort(404);
        }

        if (str_starts_with($slug, 'demo_')) {
            $invitacion = DemoInvitacion::where('tenant_id', $slug)->first();

            if (! $invitacion || $invitacion->estaExpirada()) {
                return response()->view('errors.demo-expirado', [], 410);
            }

            view()->share('esDemo', true);
            view()->share('demoExpiraEn', $invitacion->expira_en);
        } else {
            view()->share('esDemo', false);
            view()->share('demoExpiraEn', null);
        }

        view()->share('empresaNombre', $empresa->nombre);
        view()->share('empresaLogo', $empresa->logo ?? null);

        tenancy()->initialize($empresa);

        $response = $next($request);

        tenancy()->end();

        return $response;
    }
}
