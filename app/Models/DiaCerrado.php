<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaCerrado extends Model
{
    protected $table = 'dias_cerrados';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }
}
