<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearReservaRequest;
use App\Mail\ReservaCanceladaMail;
use App\Mail\ReservaConfirmadaMail;
use App\Models\HorarioConfig;
use App\Models\Reserva;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ReservaPublicaController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    /**
     * Vista pública del calendario (próximos 14 días hábiles).
     */
    public function index(): View
    {
        $horario = $this->reservaService->getHorarioActivo();
        $fechas = $this->generarProximas14Fechas($horario);

        return view('reservas.public.index', compact('horario', 'fechas'));
    }

    /**
     * AJAX: devuelve franjas disponibles para una fecha.
     */
    public function franjas(Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fecha'));
        $franjas = $this->reservaService->getFranjasDisponibles($fecha);

        return response()->json($franjas);
    }

    /**
     * Crear nueva reserva.
     */
    public function store(CrearReservaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $horaInicio = (float) $data['hora_inicio'];
        $numPersonas = (int) $data['num_personas'];
        $fecha = Carbon::parse($data['fecha']);

        if (! $this->reservaService->validarFranja($fecha, $horaInicio, $numPersonas)) {
            return response()->json(['message' => 'Lo sentimos, ya no hay aforo disponible en esa franja.'], 422);
        }

        $reserva = $this->reservaService->crearReserva($data);
        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);

        Mail::to($reserva->email)->send(new ReservaConfirmadaMail($reserva, $horaFormateada));

        return response()->json([
            'message' => '¡Reserva confirmada! Te hemos enviado un email con los detalles.',
            'token' => $reserva->token,
            'url' => route('reservas.show', $reserva->token),
        ]);
    }

    /**
     * Ver detalle de reserva por token.
     */
    public function show(string $token): View
    {
        $reserva = Reserva::where('token', $token)->firstOrFail();
        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);
        $horaFinFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_fin);

        return view('reservas.public.show', compact('reserva', 'horaFormateada', 'horaFinFormateada'));
    }

    /**
     * Cancelar reserva por token.
     */
    public function cancelar(string $token): JsonResponse
    {
        $reserva = Reserva::where('token', $token)->firstOrFail();

        if ($reserva->estado === 'cancelada') {
            return response()->json(['message' => 'Esta reserva ya estaba cancelada.'], 422);
        }

        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);

        $this->reservaService->cancelarReserva($reserva);
        Mail::to($reserva->email)->send(new ReservaCanceladaMail($reserva, $horaFormateada));

        return response()->json(['message' => 'Reserva cancelada correctamente.']);
    }

    /**
     * Genera los próximos 14 días hábiles según la configuración de horario.
     *
     * @return array<int, array{valor: string, etiqueta: string, dia_nombre: string}>
     */
    private function generarProximas14Fechas(?HorarioConfig $horario): array
    {
        $diasHabiles = $horario ? $horario->dias_semana : [0, 1, 2, 3, 4, 5, 6];
        $nombresDias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $fechas = [];
        $dia = Carbon::today();
        $intentos = 0;

        while (count($fechas) < 14 && $intentos < 60) {
            $diaSemana = $dia->dayOfWeekIso - 1;

            if (in_array($diaSemana, $diasHabiles)) {
                $fechas[] = [
                    'valor' => $dia->format('Y-m-d'),
                    'etiqueta' => $nombresDias[$diaSemana].' '.$dia->format('d/m'),
                    'dia_nombre' => $dia->locale('es')->isoFormat('dddd'),
                ];
            }

            $dia = $dia->copy()->addDay();
            $intentos++;
        }

        return $fechas;
    }
}
