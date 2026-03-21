<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

class ModuloSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            ['nombre' => 'reservas', 'label' => 'Reservas', 'icono' => 'bx bx-calendar-check', 'activo' => true],
            ['nombre' => 'eventos', 'label' => 'Eventos', 'icono' => 'bx bx-calendar-event', 'activo' => true],
            ['nombre' => 'catalogo', 'label' => 'Catálogo', 'icono' => 'bx bx-shopping-bag', 'activo' => true],
            ['nombre' => 'crm', 'label' => 'CRM', 'icono' => 'bx bx-user-circle', 'activo' => true],
            ['nombre' => 'informes', 'label' => 'Informes', 'icono' => 'bx bx-bar-chart-alt-2', 'activo' => true],
        ];

        foreach ($modulos as $modulo) {
            Modulo::firstOrCreate(['nombre' => $modulo['nombre']], $modulo);
        }

        $this->command->info('Módulos creados: '.count($modulos));
    }
}
