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
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_inicio' => 'decimal:2',
            'hora_fin' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        // Usuario en BD central, reserva en BD tenant → se requiere setConnection explícito
        return $this->belongsTo(User::class)->setConnection('central');
    }
}
