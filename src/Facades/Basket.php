<?php

namespace Lenius\LaravelEcommerce\Facades;

use Illuminate\Support\Facades\Facade;
use Lenius\Basket\Item;

/**
 * Basket facade
 *
 * @method static array contents()
 * @method static int totalItems()
 * @method static float total()
 * @method static float tax()
 * @method static item(string $itemIdentifier)
 * @method static remove(string $itemIdentifier)
 * @method static float weight()
 * @method static destroy()
 * @method static string insert(Item $param)
 */
class Basket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'basket';
    }
}
