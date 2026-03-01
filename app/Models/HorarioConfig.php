<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioConfig extends Model
{
    protected $table = 'horario_config';

    protected $fillable = [
        'dias_semana',
        'hora_apertura',
        'hora_cierre',
        'duracion_tramo',
        'aforo_por_tramo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'dias_semana' => 'array',
            'hora_apertura' => 'decimal:2',
            'hora_cierre' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }
}
