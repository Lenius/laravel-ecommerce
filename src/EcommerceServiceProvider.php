<?php

namespace Lenius\LaravelEcommerce;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Lenius\Basket\IdentifierInterface;
use Lenius\Basket\StorageInterface;
use Lenius\LaravelEcommerce\Console\Commands\PruneExpiredCarts;
use Lenius\LaravelEcommerce\Contracts\ItemFactoryInterface;
use Lenius\LaravelEcommerce\Identifier\LaravelCookie;
use Lenius\LaravelEcommerce\Items\ItemFactory;
use Lenius\LaravelEcommerce\Storage\LaravelDatabase;
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

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ecommerce');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ecommerce');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneExpiredCarts::class]);
        }

        $this->schedulePruning();

        if ($this->mustLoadRoute()) {
            Route::group($this->routeConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__.'/routes.php');
            });
        }

        // Rotate the cart identifier on authentication changes so a stale
        // cookie can't keep pointing at a previous user's persisted cart
        // (e.g. on a shared/public device).
        $events = $this->app->make(Dispatcher::class);

        $events->listen(Login::class, function (): void {
            $this->app->make(IdentifierInterface::class)->regenerate();
        });

        $events->listen(Logout::class, function (): void {
            $this->app->make(IdentifierInterface::class)->forget();
        });
    }

    protected function schedulePruning(): void
    {
        if (config('ecommerce.storage', 'session') !== 'database') {
            return;
        }

        $days = config('ecommerce.database.prune_after_days', 30);

        if (! is_numeric($days) || (int) $days <= 0) {
            return;
        }

        $this->app->booted(function (): void {
            if (! $this->app->bound(Schedule::class)) {
                return;
            }

            $cron = config('ecommerce.database.prune_cron', '0 3 * * *');
            $cron = is_string($cron) && $cron !== '' ? $cron : '0 3 * * *';

            $this->app->make(Schedule::class)
                ->command(PruneExpiredCarts::class)
                ->cron($cron)
                ->name('ecommerce:prune-carts')
                ->onOneServer()
                ->withoutOverlapping();
        });
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
        $this->mergeConfigFrom(__DIR__.'/../config/ecommerce.php', 'ecommerce');

        $this->app->bindIf(ItemFactoryInterface::class, ItemFactory::class);
        $this->app->bindIf(IdentifierInterface::class, LaravelCookie::class);

        $this->app->bind(StorageInterface::class, function (): StorageInterface {
            $driver = config('ecommerce.storage', 'session');

            if (! is_string($driver)) {
                throw new InvalidArgumentException('The ecommerce storage driver must be a string.');
            }

            if ($driver === 'session') {
                return new LaravelSession();
            }

            if ($driver !== 'database') {
                throw new InvalidArgumentException("Unsupported ecommerce storage driver [{$driver}].");
            }

            $connection = config('ecommerce.database.connection');
            $table = config('ecommerce.database.table', 'ecommerce_carts');
            $expirationMinutes = config('ecommerce.database.expiration_minutes', 43200);
            $conflictRetries = config('ecommerce.database.conflict_retries', 3);

            return new LaravelDatabase(
                $this->app->make(ConnectionResolverInterface::class),
                is_string($connection) && $connection !== '' ? $connection : null,
                is_string($table) ? $table : 'ecommerce_carts',
                is_numeric($expirationMinutes) ? (int) $expirationMinutes : null,
                static function (): mixed {
                    return auth()->guard()->id();
                },
                $this->app->make(ItemFactoryInterface::class),
                is_numeric($conflictRetries) ? max(0, (int) $conflictRetries) : 3,
                function (): string {
                    return $this->app->make(IdentifierInterface::class)->regenerate();
                },
            );
        });

        $this->app->singleton('cart', function () {
            return new Cart(
                $this->app->make(StorageInterface::class),
                $this->app->make(IdentifierInterface::class),
                $this->app->make(Dispatcher::class),
            );
        });
    }
}
