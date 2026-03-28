<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'email',
        'telefono',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'num_personas',
        'token',
        'estado',
        'notas',
        'notas_admin',
        'recordatorio_enviado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_inicio' => 'integer',
            'hora_fin' => 'integer',
            'recordatorio_enviado' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        // Usuario en BD central, reserva en BD tenant → se requiere setConnection explícito
        return $this->belongsTo(User::class)->setConnection('central');
    }
}
