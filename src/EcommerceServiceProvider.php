<?php

namespace Lenius\LaravelEcommerce;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lenius\Basket\Basket;
use Lenius\LaravelEcommerce\Identifier\LaravelCookie;
use Lenius\LaravelEcommerce\Storage\LaravelSession;

class EcommerceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ecommerce.php' => config_path('ecommerce.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__.'/../databases/migrations');

        if ($this->mustLoadRoute()) {
            Route::group($this->routeConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/routes.php');
            });
        }
    }

    protected function mustLoadRoute()
    {
        return ! config('ecommerce.disable_default_route', false);
    }

    protected function routeConfiguration()
    {
        return [
            'prefix'     => config('ecommerce.prefix', 'ecommerce'),
            'middleware' => config('ecommerce.middleware', 'web'),
        ];
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('basket', function () {
            return new Basket(new LaravelSession(), new LaravelCookie());
        });
    }
}
