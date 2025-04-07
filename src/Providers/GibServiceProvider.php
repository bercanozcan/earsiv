<?php

namespace Bercanozcan\Earsiv\Providers;

use Bercanozcan\Earsiv\Gib;
use Illuminate\Support\ServiceProvider;

class GibServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/earsiv.php', 'earsiv');

        $this->app->singleton(Gib::class, function ($app) {
            return new Gib(
                config('earsiv.test_mode'),
                config('earsiv.username'),
                config('earsiv.password')
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/earsiv.php' => config_path('earsiv.php'),
        ], 'config');
    }
}
