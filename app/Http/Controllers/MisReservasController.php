<?php

namespace App\Http\Controllers;

use App\Mail\ReservaCanceladaMail;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MisReservasController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    public function index(): View
    {
        $reservas = auth()->user()
            ->reservas()
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->get()
            ->map(function ($reserva) {
                $reserva->hora_inicio_fmt = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);
                $reserva->hora_fin_fmt = $this->reservaService->decimalAHora((float) $reserva->hora_fin);

                return $reserva;
            });

        return view('mis-reservas.index', compact('reservas'));
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
}
