<?php

namespace App\Http\Controllers;

use App\Services\ReservaService;
use App\Traits\ResuelveTemaCss;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InicioController extends Controller
{
    use ResuelveTemaCss;

    public function __construct(protected ReservaService $reservaService) {}

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['SuperAdmin', 'Admin'])) {
            return redirect()->route('admin.dashboard');
        }

        $empresas = $user->empresas()->get();

        if ($empresas->isEmpty()) {
            return redirect()->route('mis-reservas.index');
        }

        if ($empresas->count() > 1 && ! session('empresa_usuario_id')) {
            return view('inicio.selector', compact('empresas'));
        }

        $empresaId = session('empresa_usuario_id', $empresas->first()->id);
        $empresa = $empresas->firstWhere('id', $empresaId) ?? $empresas->first();

        tenancy()->initialize($empresa);

        $horario = $this->reservaService->getHorarioActivo();
        $fechasDisponibles = $horario ? $this->reservaService->generarFechasDisponibles() : [];
        $empresaSlug = $empresa->id;
        $temaCss = $this->resolverTemaCss();

        tenancy()->end();

        return view('reservas.public.index', compact('horario', 'fechasDisponibles', 'empresaSlug', 'temaCss'));
    }

    public function seleccionarEmpresa(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $empresaId = $request->input('empresa_id');

        if (! $user->empresas()->where('tenants.id', $empresaId)->exists()) {
            abort(403);
        }

        session(['empresa_usuario_id' => $empresaId]);

        return redirect()->route('inicio');
    }
}
