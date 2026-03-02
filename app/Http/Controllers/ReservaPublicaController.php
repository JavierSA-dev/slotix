<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearReservaRequest;
use App\Mail\ReservaCanceladaMail;
use App\Mail\ReservaConfirmadaMail;
use App\Models\Reserva;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ReservaPublicaController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    /**
     * Vista pública del calendario (próximos 14 días hábiles).
     */
    public function index(): View|RedirectResponse
    {
        if (auth()->check() && auth()->user()->hasAnyRole(['SuperAdmin', 'Admin'])) {
            return redirect()->route('admin.dashboard');
        }

        $horario = $this->reservaService->getHorarioActivo();
        $fechasDisponibles = $horario ? $this->generarProximas14Fechas() : [];

        return view('reservas.public.index', compact('horario', 'fechasDisponibles'));
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
        $horaFinFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_fin);

        Mail::to($reserva->email)->send(new ReservaConfirmadaMail($reserva, $horaFormateada));

        return response()->json([
            'message' => '¡Reserva recibida! En breve recibirás un email de confirmación.',
            'token' => $reserva->token,
            'url' => route('reservas.show', $reserva->token),
            'google_calendar_url' => $this->buildGoogleCalendarUrl($reserva, $horaFormateada, $horaFinFormateada),
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
        $googleCalendarUrl = $this->buildGoogleCalendarUrl($reserva, $horaFormateada, $horaFinFormateada);

        return view('reservas.public.show', compact('reserva', 'horaFormateada', 'horaFinFormateada', 'googleCalendarUrl'));
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

        $horario = $this->reservaService->getHorarioActivo();
        if ($horario && $horario->horas_min_cancelacion > 0) {
            $horaInicioDecimal = (float) $reserva->hora_inicio;
            $horaInicioH = (int) $horaInicioDecimal;
            $horaInicioM = (int) round(($horaInicioDecimal - $horaInicioH) * 60);
            $fechaHoraReserva = Carbon::parse($reserva->fecha->format('Y-m-d'))
                ->setTime($horaInicioH, $horaInicioM);
            $limiteAntelacion = $fechaHoraReserva->copy()->subHours($horario->horas_min_cancelacion);

            if (Carbon::now()->isAfter($limiteAntelacion)) {
                return response()->json([
                    'message' => "No es posible cancelar con menos de {$horario->horas_min_cancelacion} hora(s) de antelación.",
                ], 422);
            }
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
    private function generarProximas14Fechas(): array
    {
        $horario = $this->reservaService->getHorarioActivo();
        $diasHabiles = $horario ? $horario->dias_semana : [0, 1, 2, 3, 4, 5, 6];
        $semanasMax = $horario ? (int) $horario->semanas_max_reserva : 4;
        $nombresDias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $fechas = [];
        $dia = Carbon::today();
        $limite = Carbon::today()->addWeeks($semanasMax);
        $intentos = 0;

        while ($dia->lte($limite) && $intentos < 120) {
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

    /**
     * Genera la URL de Google Calendar para añadir una reserva.
     */
    private function buildGoogleCalendarUrl(Reserva $reserva, string $horaInicio, string $horaFin): string
    {
        $fecha = $reserva->fecha->format('Ymd');
        $inicio = str_replace(':', '', $horaInicio);
        $fin = str_replace(':', '', $horaFin);
        $title = urlencode('Minigolf Córdoba – Reserva');
        $details = urlencode("Reserva de {$reserva->num_personas} persona(s). Referencia: {$reserva->token}");
        $location = urlencode('Minigolf Córdoba');

        return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$fecha}T{$inicio}00/{$fecha}T{$fin}00&details={$details}&location={$location}";
    }
}
