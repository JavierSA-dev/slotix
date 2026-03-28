<?php

namespace App\Services;

use App\Models\DiaCerrado;
use App\Models\HorarioConfig;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ReservaService
{
    /**
     * Obtiene la configuración de horario activa.
     */
    public function getHorarioActivo(): ?HorarioConfig
    {
        return HorarioConfig::where('activo', true)->first();
    }

    /**
     * Genera el listado de franjas horarias para una fecha dada,
     * con el conteo de personas reservadas y disponibilidad calculada.
     *
     * @return array<int, array{hora_inicio: int, hora_fin: int, hora_inicio_fmt: string, hora_fin_fmt: string, reservadas: int, aforo: int, disponible: bool}>
     */
    public function getFranjasDisponibles(Carbon $fecha): array
    {
        $horario = $this->getHorarioActivo();

        if (! $horario) {
            return [];
        }

        // Comprobar si la fecha cae dentro de algún período cerrado
        if (DiaCerrado::where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
            ->where('fecha_fin', '>=', $fecha->format('Y-m-d'))
            ->exists()) {
            return [];
        }

        // Convertir dayOfWeekIso (1=lunes...7=domingo) a 0-6 (lunes=0)
        $diaSemana = $fecha->dayOfWeekIso - 1;

        if (! in_array($diaSemana, $horario->dias_semana)) {
            return [];
        }

        $franjas = [];
        $paso = (int) $horario->duracion_tramo; // minutos
        $horaActual = (int) $horario->hora_apertura; // minutos desde medianoche
        $horaCierre = (int) $horario->hora_cierre; // minutos desde medianoche

        // Hora mínima permitida para reservar hoy (hora actual + antelación mínima configurada).
        $esHoy = $fecha->isSameDay(Carbon::today());
        $horaMinPermitida = null;
        if ($esHoy) {
            $ahora = Carbon::now();
            $horaAhoraMinutos = $ahora->hour * 60 + $ahora->minute;
            $horaMinPermitida = $horaAhoraMinutos + ($horario->horas_min_reserva * 60);
        }

        // Obtener reservas del día de una sola consulta (previene N+1).
        $reservasPorFranja = Reserva::where('fecha', $fecha->format('Y-m-d'))
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->selectRaw('hora_inicio, SUM(num_personas) as total')
            ->groupBy('hora_inicio')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->hora_inicio => (int) $r->total])
            ->toArray();

        while ($horaActual <= $horaCierre - $paso) {
            $horaFin = $horaActual + $paso;
            $reservadas = $reservasPorFranja[$horaActual] ?? 0;

            // Bloquear franjas pasadas o que no cumplen la antelación mínima.
            $bloqueadaPorTiempo = $horaMinPermitida !== null && $horaActual < $horaMinPermitida;

            $franjas[] = [
                'hora_inicio' => $horaActual,
                'hora_fin' => $horaFin,
                'hora_inicio_fmt' => $this->minutosAHora($horaActual),
                'hora_fin_fmt' => $this->minutosAHora($horaFin),
                'reservadas' => $reservadas,
                'aforo' => $horario->aforo_por_tramo,
                'disponible' => ! $bloqueadaPorTiempo && $reservadas < $horario->aforo_por_tramo,
            ];

            $horaActual += $paso;
        }

        return $franjas;
    }

    /**
     * Verifica si una franja tiene aforo disponible para N personas adicionales.
     */
    public function validarFranja(Carbon $fecha, int $horaInicio, int $numPersonas): bool
    {
        $horario = $this->getHorarioActivo();

        if (! $horario) {
            return false;
        }

        $reservadas = Reserva::where('fecha', $fecha->format('Y-m-d'))
            ->where('hora_inicio', $horaInicio)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->sum('num_personas');

        return ($reservadas + $numPersonas) <= $horario->aforo_por_tramo;
    }

    /**
     * Crea una reserva. Vincula user_id si hay sesión autenticada.
     * Genera token UUID único para gestión por invitados.
     */
    public function crearReserva(array $data): Reserva
    {
        $horario = $this->getHorarioActivo();
        $paso = $horario ? (int) $horario->duracion_tramo : 60; // minutos

        return Reserva::create([
            'user_id' => auth()->id(),
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'fecha' => $data['fecha'],
            'hora_inicio' => (int) $data['hora_inicio'],
            'hora_fin' => (int) $data['hora_inicio'] + $paso,
            'num_personas' => $data['num_personas'],
            'token' => Str::uuid()->toString(),
            'estado' => 'pendiente',
            'notas' => $data['notas'] ?? null,
        ]);
    }

    /**
     * Cancela una reserva cambiando su estado.
     */
    public function cancelarReserva(Reserva $reserva): void
    {
        $reserva->update(['estado' => 'cancelada']);
    }

    /**
     * Convierte minutos desde medianoche a string HH:MM.
     * Ejemplo: 630 -> "10:30"
     */
    public function minutosAHora(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $mins = $minutos % 60;

        return sprintf('%02d:%02d', $horas, $mins);
    }

    /**
     * Convierte string HH:MM a minutos desde medianoche.
     * Ejemplo: "10:30" -> 630
     */
    public function horaAMinutos(string $hora): int
    {
        [$h, $m] = explode(':', $hora);

        return (int) $h * 60 + (int) $m;
    }

    /**
     * Genera el listado de fechas disponibles según el horario activo.
     *
     * @return array<int, array{valor: string, etiqueta: string, dia_nombre: string}>
     */
    public function generarFechasDisponibles(): array
    {
        $horario = $this->getHorarioActivo();
        $diasHabiles = $horario ? $horario->dias_semana : [0, 1, 2, 3, 4, 5, 6];
        $semanasMax = $horario ? (int) $horario->semanas_max_reserva : 4;
        $nombresDias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        $limite = Carbon::today()->addWeeks($semanasMax);

        // Cargar períodos cerrados que se solapen con el rango visible (una sola consulta)
        $periodosCerrados = DiaCerrado::where('fecha_fin', '>=', Carbon::today()->toDateString())
            ->where('fecha_inicio', '<=', $limite->toDateString())
            ->get(['fecha_inicio', 'fecha_fin']);

        $fechas = [];
        $dia = Carbon::today();
        $intentos = 0;

        while ($dia->lte($limite) && $intentos < 120) {
            $diaSemana = $dia->dayOfWeekIso - 1;
            $fechaStr = $dia->format('Y-m-d');

            $estaCerrada = $periodosCerrados->contains(
                fn ($p) => $fechaStr >= $p->fecha_inicio->format('Y-m-d') && $fechaStr <= $p->fecha_fin->format('Y-m-d')
            );

            if (in_array($diaSemana, $diasHabiles) && ! $estaCerrada) {
                $fechas[] = [
                    'valor' => $fechaStr,
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
