<?php

namespace Lenius\LaravelEcommerce;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lenius\LaravelEcommerce\Identifier\LaravelCookie;
use Lenius\LaravelEcommerce\Storage\LaravelSession;

class EcommerceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ecommerce.php' => config_path('ecommerce.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/ecommerce'),
            ], 'views');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/ecommerce'),
            ], 'lang');
        };

        $this->loadMigrationsFrom(__DIR__.'/../databases/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ecommerce');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ecommerce');

        if ($this->mustLoadRoute()) {
            Route::group($this->routeConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/routes.php');
            });
        }
    }

    protected function mustLoadRoute(): bool
    {
        return ! config('ecommerce.disable_default_route', false);
    }

    protected function routeConfiguration(): array
    {
        $prefix = config('ecommerce.prefix', 'ecommerce');
        $middleware = config('ecommerce.middleware', ['web']);

        if (! is_string($prefix)) {
            $prefix = 'ecommerce';
        }

        if (! is_string($middleware) && ! is_array($middleware)) {
            $middleware = ['web'];
        }

        return [
            'prefix'     => $prefix,
            'middleware' => $middleware,
        ];
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('cart', function () {
            return new Cart(new LaravelSession(), new LaravelCookie(), $this->app->make(Dispatcher::class));
        });
    }
}
