<?php

namespace App\Providers;

use App\Models\HorarioConfig;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        View::composer('layouts.topbar', function ($view) {
            try {
                $view->with('enMantenimiento', HorarioConfig::enMantenimiento());
            } catch (\Exception $e) {
                $view->with('enMantenimiento', false);
            }
        });
    }
}
