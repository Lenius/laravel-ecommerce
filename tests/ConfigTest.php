<?php

namespace Lenius\LaravelEcommerce\Test;

class ConfigTest extends TestCase
{
    public function test_disable_default_route()
    {
        $this->app['config']->set('ecommerce.disable_default_route', true);
    }
}
