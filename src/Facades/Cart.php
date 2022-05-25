<?php

namespace Lenius\LaravelEcommerce\Facades;

use Illuminate\Support\Facades\Facade;
use Lenius\Basket\Item;

/**
 * Cart facade
 *
 * @method static array contents()
 * @method static int totalItems()
 * @method static float total($includeTax = true)
 * @method static float tax()
 * @method static item(string $itemIdentifier)
 * @method static remove(string $itemIdentifier)
 * @method static float weight()
 * @method static destroy()
 * @method static inc(string $itemIdentifier)
 * @method static dec(string $itemIdentifier)
 * @method static string insert(Item $param)
 */
class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cart';
    }
}
