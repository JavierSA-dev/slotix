<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HorarioConfig;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $hoy = today();

        $reservasHoy = Reserva::whereDate('fecha', $hoy)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->count();

        $reservasSemana = Reserva::whereBetween('fecha', [$hoy, $hoy->copy()->addDays(7)])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->count();

        $canceladasMes = Reserva::where('estado', 'cancelada')
            ->whereMonth('created_at', now()->month)
            ->count();

        return view('admin.dashboard', compact('reservasHoy', 'reservasSemana', 'canceladasMes'));
    }

    public function toggleMantenimiento(): JsonResponse
    {
        $horario = HorarioConfig::where('activo', true)->firstOrFail();
        $horario->update(['en_mantenimiento' => ! $horario->en_mantenimiento]);
        cache()->forget('mantenimiento_activo');

        return response()->json([
            'en_mantenimiento' => $horario->en_mantenimiento,
            'message' => $horario->en_mantenimiento ? 'Mantenimiento activado.' : 'Mantenimiento desactivado.',
        ]);
    }
}
