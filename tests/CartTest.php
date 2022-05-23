<?php

namespace Lenius\LaravelEcommerce\Test;

use Illuminate\Contracts\Events\Dispatcher;
use Lenius\Basket\Identifier\Runtime as RuntimeIdentifier;
use Lenius\Basket\Item;
use Lenius\Basket\Storage\Runtime as RuntimeStore;
use Lenius\LaravelEcommerce\Cart;
use Lenius\LaravelEcommerce\Events\CartDestroyed;
use Lenius\LaravelEcommerce\Events\CartItemRemoved;
use Lenius\LaravelEcommerce\Events\CartItemUpdated;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    /** @var Cart */
    private Cart $cart;

    public function setUp(): void
    {
        $this->cart = new Cart(new RuntimeStore(), new RuntimeIdentifier(), new EventMock());
    }

    public function tearDown(): void
    {
        $this->cart->destroy();
    }

    public function testInsert(): void
    {
        $actualId = $this->cart->insert(new Item([
            'id'       => 'foo',
            'name'     => 'bar',
            'price'    => 100,
            'quantity' => 1,
            'weight'   => 200,
        ]));

        $identifier = md5('foo'.serialize([]));

        $this->assertEquals($identifier, $actualId);
    }
}

class EventMock implements Dispatcher
{

    public function listen($events, $listener = null)
    {
        // TODO: Implement listen() method.
    }

    public function hasListeners($eventName)
    {
        // TODO: Implement hasListeners() method.
    }

    public function subscribe($subscriber)
    {
        // TODO: Implement subscribe() method.
    }

    public function until($event, $payload = [])
    {
        // TODO: Implement until() method.
    }

    public function dispatch($event, $payload = [], $halt = false)
    {

    }

    public function push($event, $payload = [])
    {
        // TODO: Implement push() method.
    }

    public function flush($event)
    {
        // TODO: Implement flush() method.
    }

    public function forget($event)
    {
        // TODO: Implement forget() method.
    }

    public function forgetPushed()
    {
        // TODO: Implement forgetPushed() method.
    }
}
