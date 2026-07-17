<?php

namespace Lenius\LaravelEcommerce\Test;

use Illuminate\Console\Scheduling\Schedule;

class SessionStorageTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Pin the storage driver regardless of any ECOMMERCE_STORAGE
        // environment variable the test runner may have set (e.g. the CI
        // workflow sets it to "database" for the step that also runs
        // FeatureTest.php against the database driver).
        $app['config']->set('ecommerce.storage', 'session');
    }

    public function test_prune_command_is_a_noop_for_session_storage(): void
    {
        $this->artisan('ecommerce:prune-carts')->assertExitCode(0);
    }

    public function test_the_prune_command_is_not_scheduled_for_session_storage(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $scheduled = collect($schedule->events())->contains(
            fn ($event) => is_string($event->command ?? null) && str_contains($event->command, 'ecommerce:prune-carts'),
        );

        $this->assertFalse($scheduled, 'Did not expect the prune command to be scheduled without database storage.');
    }
}
