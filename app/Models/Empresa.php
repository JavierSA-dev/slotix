<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant;

class Empresa extends Tenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    protected $fillable = [
        'id',
        'nombre',
        'logo',
        'colores',
        'activo',
        'en_mantenimiento',
        'tema',
    ];

    protected function casts(): array
    {
        return [
            'colores' => 'array',
            'activo' => 'boolean',
            'en_mantenimiento' => 'boolean',
        ];
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'nombre',
            'logo',
            'colores',
            'activo',
            'en_mantenimiento',
            'tema',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'empresa_user', 'empresa_id', 'user_id');
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'empresa_modulo', 'empresa_id', 'modulo_id')
            ->withPivot(['activo', 'config'])
            ->withTimestamps();
    }

    public function modulosActivos(): BelongsToMany
    {
        return $this->modulos()->wherePivot('activo', true);
    }

    public function tieneModulo(string $nombre): bool
    {
        return $this->modulosActivos()
            ->where('modulos.nombre', $nombre)
            ->exists();
    }

    public function getColoresDefecto(): array
    {
        return $this->colores ?? [
            'primary' => '#c19849',
            'secondary' => '#535353',
            'accent' => '#00d4e8',
        ];
    }
}
