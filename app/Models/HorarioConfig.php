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
        'horas_min_reserva',
        'horas_min_cancelacion',
        'semanas_max_reserva',
        'activo',
        'en_mantenimiento',
    ];

    protected function casts(): array
    {
        return [
            'dias_semana' => 'array',
            'hora_apertura' => 'decimal:2',
            'hora_cierre' => 'decimal:2',
            'activo' => 'boolean',
            'en_mantenimiento' => 'boolean',
        ];
    }

    public static function enMantenimiento(): bool
    {
        $tenantSlug = tenancy()->initialized ? tenancy()->tenant->getTenantKey() : 'central';
        $key = 'mantenimiento_activo_'.$tenantSlug;

        return (bool) cache()->remember($key, 60, function () {
            return static::where('activo', true)->value('en_mantenimiento') ?? false;
        });
    }
}
