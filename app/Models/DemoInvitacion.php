<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoInvitacion extends Model
{
    protected $connection = 'central';

    protected $table = 'demo_invitaciones';

    protected $fillable = [
        'tenant_id',
        'creada_por',
        'expira_en',
    ];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
        ];
    }

    public function estaExpirada(): bool
    {
        return $this->expira_en->isPast();
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }
}
