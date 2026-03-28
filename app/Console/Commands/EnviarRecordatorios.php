<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioReservaMail;
use App\Models\Empresa;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar {--dry-run : Simula el envío sin enviar nada}';

    protected $description = 'Envía recordatorios por email a los clientes con reserva mañana';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $mañana = Carbon::tomorrow()->toDateString();
        $dryRun = $this->option('dry-run');
        $totalEmail = 0;

        $empresas = Empresa::where('activo', true)->get();

        foreach ($empresas as $empresa) {
            tenancy()->initialize($empresa);

            $reservas = Reserva::where('fecha', $mañana)
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->where('recordatorio_enviado', false)
                ->get();

            foreach ($reservas as $reserva) {
                $horaFormateada = $this->formatearHora($reserva->hora_inicio, $reserva->hora_fin);

                if (! $dryRun) {
                    Mail::to($reserva->email)->send(
                        new RecordatorioReservaMail(
                            reserva: $reserva,
                            horaFormateada: $horaFormateada,
                            empresaNombre: $empresa->nombre,
                            empresaSlug: $empresa->id,
                        )
                    );
                    $totalEmail++;

                    $reserva->update(['recordatorio_enviado' => true]);
                } else {
                    $this->line("  [dry-run] {$empresa->id} → {$reserva->nombre} <{$reserva->email}> — {$reserva->fecha->format('d/m/Y')} {$horaFormateada}");
                }
            }

            tenancy()->end();
        }

        if (! $dryRun) {
            $this->info("Recordatorios enviados: {$totalEmail} emails.");
        }

        return Command::SUCCESS;
    }

    private function formatearHora(int $inicio, int $fin): string
    {
        return sprintf('%02d:%02d', intdiv($inicio, 60), $inicio % 60)
            .' - '
            .sprintf('%02d:%02d', intdiv($fin, 60), $fin % 60);
    }
}
