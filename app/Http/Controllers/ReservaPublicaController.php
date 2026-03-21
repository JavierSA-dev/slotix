<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearReservaRequest;
use App\Mail\ReservaCanceladaMail;
use App\Mail\ReservaConfirmadaMail;
use App\Models\Reserva;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\NotificacionService;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ReservaPublicaController extends Controller
{
    public function __construct(
        protected ReservaService $reservaService,
        protected NotificacionService $notificacionService,
    ) {}

    /**
     * Vista pública del calendario (próximos 14 días hábiles).
     */
    public function index(string $empresa): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('inicio');
        }

        $horario = $this->reservaService->getHorarioActivo();
        $fechasDisponibles = $horario ? $this->reservaService->generarFechasDisponibles() : [];
        $empresaSlug = $empresa;
        $tenant = tenancy()->tenant;
        $temaCss = AppServiceProvider::generarTemaCss($tenant->tema ?? 'neon', $tenant->colores ?? []);

        return view('reservas.public.index', compact('horario', 'fechasDisponibles', 'empresaSlug', 'temaCss'));
    }

    /**
     * AJAX: devuelve franjas disponibles para una fecha.
     */
    public function franjas(string $empresa, Request $request): JsonResponse
    {
        $fecha = Carbon::parse($request->input('fecha'));
        $franjas = $this->reservaService->getFranjasDisponibles($fecha);

        return response()->json($franjas);
    }

    /**
     * Crear nueva reserva.
     */
    public function store(string $empresa, CrearReservaRequest $request): JsonResponse
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

        $empresaNombre = tenancy()->tenant->nombre ?? config('app.name');
        Mail::to($reserva->email)->send(new ReservaConfirmadaMail($reserva, $horaFormateada, $empresa, $empresaNombre));
        $this->notificacionService->nuevaReserva($reserva, $empresa, $horaFormateada);

        return response()->json([
            'message' => '¡Reserva recibida! En breve recibirás un email de confirmación.',
            'token' => $reserva->token,
            'url' => route('reservas.show', [$empresa, $reserva->token]),
            'google_calendar_url' => $this->buildGoogleCalendarUrl($reserva, $horaFormateada, $horaFinFormateada),
        ]);
    }

    /**
     * Ver detalle de reserva por token.
     */
    public function show(string $empresa, string $token): View
    {
        $reserva = Reserva::where('token', $token)->firstOrFail();
        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);
        $horaFinFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_fin);
        $googleCalendarUrl = $this->buildGoogleCalendarUrl($reserva, $horaFormateada, $horaFinFormateada);
        $empresaSlug = $empresa;
        $tenant = tenancy()->tenant;
        $temaCss = AppServiceProvider::generarTemaCss($tenant->tema ?? 'neon', $tenant->colores ?? []);

        return view('reservas.public.show', compact('reserva', 'horaFormateada', 'horaFinFormateada', 'googleCalendarUrl', 'empresaSlug', 'temaCss'));
    }

    /**
     * Cancelar reserva por token.
     */
    public function cancelar(string $empresa, string $token): JsonResponse
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

        $empresaNombre = tenancy()->tenant->nombre ?? config('app.name');
        $this->reservaService->cancelarReserva($reserva);
        Mail::to($reserva->email)->send(new ReservaCanceladaMail($reserva, $horaFormateada, $empresa, $empresaNombre));
        $this->notificacionService->reservaCancelada($reserva, $empresa, $horaFormateada);

        return response()->json(['message' => 'Reserva cancelada correctamente.']);
    }

    /**
     * Genera los próximos 14 días hábiles según la configuración de horario.
     *
     * @return array<int, array{valor: string, etiqueta: string, dia_nombre: string}>
     */
    /**
     * Auto-login para acceso rápido en modo demo.
     */
    public function demoAcceder(string $empresa, string $tipo): RedirectResponse
    {
        if (! str_starts_with($empresa, 'demo_')) {
            abort(403);
        }

        $email = $tipo === 'admin'
            ? "admin_{$empresa}@demo.slotix.app"
            : "user_{$empresa}@demo.slotix.app";

        $user = User::where('email', $email)->first();

        if (! $user) {
            abort(404);
        }

        Auth::login($user);

        if ($tipo === 'admin') {
            session(['empresa_id' => $empresa]);

            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('reservas.public.index', $empresa);
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
