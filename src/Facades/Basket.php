<?php

namespace Lenius\LaravelEcommerce\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Basket facade
 *
 * @method static array contents()
 * @method static int totalItems()
 * @method static float total()
 * @method static float tax()
 */
class Basket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'basket';
    }
}
