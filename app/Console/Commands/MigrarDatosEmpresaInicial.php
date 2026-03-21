<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrarDatosEmpresaInicial extends Command
{
    protected $signature = 'empresa:migrar-inicial {--empresa=minigolf_cordoba : ID de la empresa destino} {--force : Sobrescribir datos existentes}';

    protected $description = 'Migra los datos de reservas y horario_config de la BD central al tenant de la empresa inicial.';

    public function handle(): int
    {
        $empresaId = $this->option('empresa');
        $force = $this->option('force');

        $empresa = \App\Models\Empresa::find($empresaId);

        if (! $empresa) {
            $this->error("Empresa '{$empresaId}' no encontrada. Ejecuta primero: php artisan db:seed --class=EmpresaSeeder");

            return self::FAILURE;
        }

        $this->info("Migrando datos al tenant: {$empresa->nombre} (ID: {$empresa->id})");

        // Datos de la BD central (tablas originales)
        $horarios = \Illuminate\Support\Facades\DB::connection('central')->table('horario_config')->get();
        $reservas = \Illuminate\Support\Facades\DB::connection('central')->table('reservas')->get();

        if ($horarios->isEmpty() && $reservas->isEmpty()) {
            $this->warn('No hay datos en la BD central para migrar (horario_config o reservas están vacías).');

            return self::SUCCESS;
        }

        tenancy()->initialize($empresa);

        // Migrar horario_config
        if ($horarios->isNotEmpty()) {
            $yaExisten = \Illuminate\Support\Facades\DB::table('horario_config')->count();
            if ($yaExisten && ! $force) {
                $this->warn("Ya existen {$yaExisten} registros en horario_config del tenant. Usa --force para sobrescribir.");
            } else {
                if ($force) {
                    \Illuminate\Support\Facades\DB::table('horario_config')->truncate();
                }
                foreach ($horarios as $h) {
                    \Illuminate\Support\Facades\DB::table('horario_config')->insert((array) $h);
                }
                $this->info("✔ {$horarios->count()} registros de horario_config migrados.");
            }
        }

        // Migrar reservas
        if ($reservas->isNotEmpty()) {
            $yaExisten = \Illuminate\Support\Facades\DB::table('reservas')->count();
            if ($yaExisten && ! $force) {
                $this->warn("Ya existen {$yaExisten} registros en reservas del tenant. Usa --force para sobrescribir.");
            } else {
                if ($force) {
                    \Illuminate\Support\Facades\DB::table('reservas')->truncate();
                }
                foreach ($reservas as $r) {
                    \Illuminate\Support\Facades\DB::table('reservas')->insert((array) $r);
                }
                $this->info("✔ {$reservas->count()} reservas migradas.");
            }
        }

        tenancy()->end();

        $this->info('Migración completada.');
        $this->info('');
        $this->warn('IMPORTANTE: Una vez verificados los datos en el tenant, puedes eliminar las tablas');
        $this->warn('horario_config y reservas de la BD central con:');
        $this->warn('  php artisan migrate --path=database/migrations/drop_legacy_central_tables.php');

        return self::SUCCESS;
    }
}
