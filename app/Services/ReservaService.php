<?php

namespace App\Services;

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
     * @return array<int, array{hora_inicio: float, hora_fin: float, hora_inicio_fmt: string, hora_fin_fmt: string, reservadas: int, aforo: int, disponible: bool}>
     */
    public function getFranjasDisponibles(Carbon $fecha): array
    {
        $horario = $this->getHorarioActivo();

        if (! $horario) {
            return [];
        }

        // Convertir dayOfWeekIso (1=lunes...7=domingo) a 0-6 (lunes=0)
        $diaSemana = $fecha->dayOfWeekIso - 1;

        if (! in_array($diaSemana, $horario->dias_semana)) {
            return [];
        }

        $franjas = [];
        $paso = $horario->duracion_tramo / 60;
        $horaActual = (float) $horario->hora_apertura;
        $horaCierre = (float) $horario->hora_cierre;

        // Obtener reservas del día de una sola consulta (previene N+1)
        $reservasPorFranja = Reserva::where('fecha', $fecha->format('Y-m-d'))
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->selectRaw('hora_inicio, SUM(num_personas) as total')
            ->groupBy('hora_inicio')
            ->pluck('total', 'hora_inicio')
            ->toArray();

        while ($horaActual < $horaCierre - $paso + 0.001) {
            $horaFin = round($horaActual + $paso, 4);
            $reservadas = (int) ($reservasPorFranja[(string) $horaActual] ?? $reservasPorFranja[$horaActual] ?? 0);

            $franjas[] = [
                'hora_inicio' => $horaActual,
                'hora_fin' => $horaFin,
                'hora_inicio_fmt' => $this->decimalAHora($horaActual),
                'hora_fin_fmt' => $this->decimalAHora($horaFin),
                'reservadas' => $reservadas,
                'aforo' => $horario->aforo_por_tramo,
                'disponible' => $reservadas < $horario->aforo_por_tramo,
            ];

            $horaActual = round($horaActual + $paso, 4);
        }

        return $franjas;
    }

    /**
     * Verifica si una franja tiene aforo disponible para N personas adicionales.
     */
    public function validarFranja(Carbon $fecha, float $horaInicio, int $numPersonas): bool
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
        $paso = $horario ? $horario->duracion_tramo / 60 : 0.5;

        return Reserva::create([
            'user_id' => auth()->id(),
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => round($data['hora_inicio'] + $paso, 4),
            'num_personas' => $data['num_personas'],
            'token' => Str::uuid()->toString(),
            'estado' => 'confirmada',
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
     * Convierte hora decimal a string HH:MM.
     * Ejemplo: 10.5 -> "10:30"
     */
    public function decimalAHora(float $decimal): string
    {
        $horas = (int) $decimal;
        $minutos = (int) round(($decimal - $horas) * 60);

        return sprintf('%02d:%02d', $horas, $minutos);
    }

    /**
     * Convierte string HH:MM a decimal.
     * Ejemplo: "10:30" -> 10.5
     */
    public function horaADecimal(string $hora): float
    {
        [$h, $m] = explode(':', $hora);

        return (int) $h + ((int) $m / 60);
    }
}
