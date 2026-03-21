<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modulo extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'nombre',
        'label',
        'icono',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'empresa_modulo', 'modulo_id', 'empresa_id')
            ->withPivot(['activo', 'config'])
            ->withTimestamps();
    }
}
