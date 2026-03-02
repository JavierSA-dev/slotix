<?php

namespace App\Http\Controllers;

use App\Mail\ReservaCanceladaMail;
use App\Models\Reserva;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MisReservasController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    public function index(Request $request): View|Response
    {
        $hoy = Carbon::today();
        $fechaDesde = $request->filled('fecha_desde')
            ? Carbon::parse($request->input('fecha_desde'))
            : $hoy->copy()->subDays(7);
        $fechaHasta = $request->filled('fecha_hasta')
            ? Carbon::parse($request->input('fecha_hasta'))
            : $hoy->copy()->addDays(7);
        $estado = $request->input('estado', '');

        $query = auth()->user()
            ->reservas()
            ->whereBetween('fecha', [$fechaDesde->format('Y-m-d'), $fechaHasta->format('Y-m-d')])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio');

        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        $diasAbrev = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $reservas = $query->get()->map(function ($reserva) use ($diasAbrev) {
            $horaInicio = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);
            $horaFin = $this->reservaService->decimalAHora((float) $reserva->hora_fin);
            $reserva->hora_inicio_fmt = $horaInicio;
            $reserva->hora_fin_fmt = $horaFin;
            $reserva->dia_semana = $diasAbrev[$reserva->fecha->dayOfWeekIso - 1];
            $reserva->google_calendar_url = $this->buildGoogleCalendarUrl($reserva, $horaInicio, $horaFin);

            return $reserva;
        });

        if ($request->ajax()) {
            return response(view('mis-reservas.partials.grid', compact('reservas'))->render());
        }

        $filtros = [
            'fecha_desde' => $fechaDesde->format('Y-m-d'),
            'fecha_hasta' => $fechaHasta->format('Y-m-d'),
            'estado' => $estado,
        ];

        return view('mis-reservas.index', compact('reservas', 'filtros'));
    }

    public function cancelar(Reserva $reserva): JsonResponse
    {
        abort_if($reserva->user_id !== auth()->id(), 403);

        if ($reserva->estado === 'cancelada') {
            return response()->json(['message' => 'Esta reserva ya estaba cancelada.'], 422);
        }

        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);

        $this->reservaService->cancelarReserva($reserva);
        Mail::to($reserva->email)->send(new ReservaCanceladaMail($reserva, $horaFormateada));

        return response()->json(['message' => 'Reserva cancelada correctamente.']);
    }

    private function buildGoogleCalendarUrl(Reserva $reserva, string $horaInicio, string $horaFin): string
    {
        $fecha = $reserva->fecha->format('Ymd');
        $inicio = str_replace(':', '', $horaInicio);
        $fin = str_replace(':', '', $horaFin);
        $title = urlencode('Minigolf Córdoba – Reserva');
        $details = urlencode("Reserva de {$reserva->num_personas} persona(s).");
        $location = urlencode('Minigolf Córdoba');

        return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$fecha}T{$inicio}00/{$fecha}T{$fin}00&details={$details}&location={$location}";
    }
}
