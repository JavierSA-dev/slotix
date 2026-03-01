<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
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
}
