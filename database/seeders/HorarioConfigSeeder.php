<?php

namespace Database\Seeders;

use App\Models\HorarioConfig;
use Illuminate\Database\Seeder;

class HorarioConfigSeeder extends Seeder
{
    public function run(): void
    {
        HorarioConfig::firstOrCreate(
            ['activo' => true],
            [
                'dias_semana' => [0, 1, 2, 3, 4, 5, 6],
                'hora_apertura' => 10.00,
                'hora_cierre' => 20.00,
                'duracion_tramo' => 30,
                'aforo_por_tramo' => 8,
            ]
        );
    }
}
