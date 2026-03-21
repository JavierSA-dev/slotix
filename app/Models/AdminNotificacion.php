<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotificacion extends Model
{
    protected $connection = 'central';

    protected $table = 'admin_notificaciones';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'tipo',
        'datos',
        'leida',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'leida' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
