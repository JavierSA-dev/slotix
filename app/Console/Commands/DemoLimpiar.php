<?php

namespace App\Console\Commands;

use App\Models\DemoInvitacion;
use App\Models\Empresa;
use Illuminate\Console\Command;

class DemoLimpiar extends Command
{
    protected $signature = 'demo:limpiar {--force : Eliminar también demos activas}';

    protected $description = 'Elimina las demos expiradas (tenant + base de datos + invitación)';

    public function handle(): int
    {
        $query = DemoInvitacion::query();

        if (! $this->option('force')) {
            $query->where('expira_en', '<', now());
        }

        $demos = $query->get();

        if ($demos->isEmpty()) {
            $this->info('No hay demos expiradas que limpiar.');

            return Command::SUCCESS;
        }

        $eliminadas = 0;

        foreach ($demos as $demo) {
            $empresa = Empresa::find($demo->tenant_id);

            if ($empresa) {
                $empresa->delete();
            }

            $demo->delete();
            $eliminadas++;

            $this->line("  ✓ Eliminada: {$demo->tenant_id}");
        }

        $this->info("Se han eliminado {$eliminadas} demo(s) expirada(s).");

        return Command::SUCCESS;
    }
}
