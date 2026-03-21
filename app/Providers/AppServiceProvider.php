<?php

namespace App\Providers;

use App\Models\Empresa;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public static function generarTemaCss(string $tema): string
    {
        $temas = config('temas');
        $vars = $temas[$tema]['vars'] ?? $temas['neon']['vars'];
        $css = ':root {';
        foreach ($vars as $prop => $value) {
            $css .= "{$prop}:{$value};";
        }
        $css .= '}';

        return $css;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer('layouts.topbar', function ($view) {
            try {
                $empresaActual = null;
                $empresasDisponibles = collect();
                $enMantenimiento = false;

                $user = auth()->user();
                if ($user) {
                    if ($user->hasRole('SuperAdmin')) {
                        $empresasDisponibles = Empresa::where('activo', true)->orderBy('nombre')->get();
                    } elseif ($user->hasAnyRole(['Admin'])) {
                        $empresasDisponibles = $user->empresas()->where('tenants.activo', true)->orderBy('nombre')->get();
                    }

                    $empresaId = session('empresa_id');
                    if ($empresaId) {
                        $empresaActual = $empresasDisponibles->firstWhere('id', $empresaId)
                            ?? Empresa::find($empresaId);
                        $enMantenimiento = $empresaActual?->en_mantenimiento ?? false;
                    }
                }

                $view->with('enMantenimiento', $enMantenimiento);
                $view->with('empresaActual', $empresaActual);
                $view->with('empresasDisponibles', $empresasDisponibles);
            } catch (\Exception $e) {
                $view->with('enMantenimiento', false);
                $view->with('empresaActual', null);
                $view->with('empresasDisponibles', collect());
            }
        });

        View::composer('layouts.master', function ($view) {
            try {
                $empresa = null;
                $empresaId = session('empresa_id');
                if ($empresaId) {
                    $empresa = Empresa::find($empresaId);
                }
                $view->with('empresaMaster', $empresa);
                $view->with('temaCss', self::generarTemaCss($empresa?->tema ?? 'neon'));
            } catch (\Exception $e) {
                $view->with('empresaMaster', null);
                $view->with('temaCss', '');
            }
        });

        View::composer('layouts.sidebar', function ($view) {
            try {
                $modulosActivos = collect();
                $empresaId = session('empresa_id');
                if ($empresaId) {
                    $empresa = Empresa::find($empresaId);
                    if ($empresa) {
                        $modulosActivos = $empresa->modulosActivos()->pluck('nombre');
                    }
                }
                $view->with('modulosActivos', $modulosActivos);
            } catch (\Exception $e) {
                $view->with('modulosActivos', collect());
            }
        });
    }
}
