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

    public static function generarTemaCss(string $tema, array $colores = []): string
    {
        $temas = config('temas');
        $vars = $temas[$tema]['vars'] ?? $temas['neon']['vars'];

        $primary = $colores['primary'] ?? null;
        $secondary = $colores['secondary'] ?? null;
        $accent = $colores['accent'] ?? null;

        // Colores simples de marca
        if ($primary) {
            $vars['--mg-gold'] = $primary;
            $vars['--mg-gold-glow'] = self::hexToRgba($primary, 0.4);
            $vars['--mg-btn-primary-bg'] = $primary;
            $vars['--mg-title-shadow'] = '0 0 16px '.self::hexToRgba($primary, 0.5);
        }

        if ($secondary) {
            $vars['--mg-gray'] = $secondary;
        }

        if ($accent) {
            $vars['--mg-neon-cyan'] = $accent;
            $vars['--mg-neon-cyan-glow'] = self::hexToRgba($accent, 0.25);
            $vars['--mg-chip-active-bg'] = self::hexToRgba($accent, 0.12);
            $vars['--mg-chip-active-shadow'] = '0 0 12px '.self::hexToRgba($accent, 0.22).', inset 0 0 8px '.self::hexToRgba($accent, 0.08);
        }

        // Variables compuestas (sombras, gradientes) que dependen del color de acento/primario,
        // recalculadas por tema para respetar su estética particular
        switch ($tema) {
            case 'neon':
                if ($accent) {
                    $vars['--mg-header-border'] = '2px solid '.$accent;
                    $vars['--mg-header-shadow'] = '0 2px 16px '.self::hexToRgba($accent, 0.22);
                    $vars['--mg-shadow-card'] = '0 0 20px '.self::hexToRgba($accent, 0.12).', 0 8px 32px rgba(0,0,0,0.5)';
                    $vars['--mg-shadow-hover'] = '0 0 18px '.self::hexToRgba($accent, 0.3).', 0 4px 16px rgba(0,0,0,0.5)';
                    $vars['--mg-shadow-modal'] = '0 0 30px '.self::hexToRgba($accent, 0.22).', 0 8px 40px rgba(0,0,0,0.6)';
                    $vars['--mg-body-gradient'] = 'radial-gradient(ellipse at 20% 0%, '.self::hexToRgba($accent, 0.04).' 0%, transparent 60%), radial-gradient(ellipse at 80% 100%, '.self::hexToRgba($accent, 0.04).' 0%, transparent 60%)';
                }
                break;

            case 'clasico':
                if ($primary) {
                    $vars['--mg-shadow-card'] = '0 1px 4px rgba(0,0,0,0.08), 0 4px 16px '.self::hexToRgba($primary, 0.08);
                    $vars['--mg-shadow-hover'] = '0 6px 24px '.self::hexToRgba($primary, 0.16);
                }
                break;

            case 'pastel':
                $p = $primary ?? '#8b5cf6';
                $a = $accent ?? '#f472b6';
                $vars['--mg-shadow-card'] = '0 4px 24px '.self::hexToRgba($p, 0.14);
                $vars['--mg-shadow-hover'] = '0 8px 32px '.self::hexToRgba($p, 0.22);
                $vars['--mg-shadow-modal'] = '0 12px 48px '.self::hexToRgba($p, 0.2);
                $vars['--mg-header-shadow'] = '0 4px 16px '.self::hexToRgba($p, 0.15);
                $vars['--mg-body-gradient'] = 'radial-gradient(ellipse at 10% 10%, '.self::hexToRgba($p, 0.1).' 0%, transparent 50%), radial-gradient(ellipse at 90% 90%, '.self::hexToRgba($a, 0.12).' 0%, transparent 50%)';
                $vars['--mg-chip-active-shadow'] = '0 4px 16px '.self::hexToRgba($a, 0.25);
                break;
        }

        $css = ':root {';
        foreach ($vars as $prop => $value) {
            $css .= "{$prop}:{$value};";
        }
        $css .= '}';

        return $css;
    }

    private static function hexToRgba(string $hex, float $opacity): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r},{$g},{$b},{$opacity})";
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
                $view->with('temaCss', self::generarTemaCss($empresa?->tema ?? 'neon', $empresa?->getColoresDefecto() ?? []));
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
