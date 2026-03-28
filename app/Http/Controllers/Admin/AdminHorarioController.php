<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminHorarioRequest;
use App\Models\DiaCerrado;
use App\Models\HorarioConfig;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminHorarioController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    public function index(): View
    {
        $horario = HorarioConfig::where('activo', true)->first();

        $horarioAperturaFmt = $horario
            ? $this->reservaService->minutosAHora((int) $horario->hora_apertura)
            : '10:00';

        $horarioCierreFmt = $horario
            ? $this->reservaService->minutosAHora((int) $horario->hora_cierre)
            : '20:00';

        $diasCerrados = DiaCerrado::orderBy('fecha_inicio')->get();

        return view('admin.horario.index', compact('horario', 'horarioAperturaFmt', 'horarioCierreFmt', 'diasCerrados'));
    }

    public function update(AdminHorarioRequest $request): RedirectResponse
    {
        $horario = HorarioConfig::where('activo', true)->first();

        if ($horario) {
            $horario->update($request->validated());
        } else {
            HorarioConfig::create(array_merge($request->validated(), ['activo' => true]));
        }

        return redirect()->route('admin.horario.index')->with('success', 'Horario actualizado correctamente.');
    }
}
