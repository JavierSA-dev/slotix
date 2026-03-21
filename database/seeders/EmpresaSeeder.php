<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        // Crear empresa #1: Minigolf Córdoba
        $empresa = Empresa::firstOrCreate(
            ['id' => 'minigolf_cordoba'],
            [
                'nombre' => 'Minigolf Córdoba',
                'colores' => [
                    'primary' => '#c19849',
                    'secondary' => '#535353',
                    'accent' => '#00d4e8',
                ],
                'activo' => true,
                'en_mantenimiento' => false,
            ]
        );

        if ($empresa->wasRecentlyCreated) {
            $this->command->info('Empresa "Minigolf Córdoba" creada (ID: minigolf_cordoba).');
            $this->command->info('Base de datos de tenant creada y migrada.');
        } else {
            $this->command->info('Empresa "Minigolf Córdoba" ya existe.');
        }

        // Activar módulo "reservas" para esta empresa
        $moduloReservas = Modulo::where('nombre', 'reservas')->first();
        if ($moduloReservas) {
            $existe = $empresa->modulos()->where('modulo_id', $moduloReservas->id)->exists();
            if (! $existe) {
                $empresa->modulos()->attach($moduloReservas->id, ['activo' => true]);
                $this->command->info('Módulo "reservas" activado para Minigolf Córdoba.');
            }
        }

        // Asignar todos los usuarios existentes a esta empresa
        $usuarios = User::all();
        foreach ($usuarios as $user) {
            $yaAsignado = $empresa->users()->where('user_id', $user->id)->exists();
            if (! $yaAsignado) {
                $empresa->users()->attach($user->id);
            }
        }

        $this->command->info("Usuarios asignados a la empresa: {$usuarios->count()}");
    }
}
