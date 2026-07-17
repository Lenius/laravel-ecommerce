<?php

namespace Lenius\LaravelEcommerce\Facades;

use Illuminate\Support\Facades\Facade;
use Lenius\Basket\ItemInterface;

/**
 * Cart facade
 *
 * @method static array<string, ItemInterface|array<array-key, mixed>> contents(bool $asArray = false)
 * @method static int totalItems(bool $unique = false)
 * @method static float total(bool $includeTax = true)
 * @method static float tax()
 * @method static ItemInterface|false item(string $itemIdentifier)
 * @method static void update(string $itemIdentifier, mixed $key, mixed $value = null)
 * @method static void remove(string $itemIdentifier)
 * @method static float weight()
 * @method static void destroy()
 * @method static ItemInterface|false inc(string $itemIdentifier)
 * @method static ItemInterface|false dec(string $itemIdentifier)
 * @method static string insert(ItemInterface $item)
 */
class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cart';
    }
}
