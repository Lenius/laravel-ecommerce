<?php

namespace Lenius\LaravelEcommerce;

use Illuminate\Contracts\Events\Dispatcher;
use Lenius\Basket\Basket;
use Lenius\Basket\IdentifierInterface;
use Lenius\Basket\Item;
use Lenius\Basket\ItemInterface;
use Lenius\Basket\StorageInterface;
use Lenius\LaravelEcommerce\Events\CartDestroyed;
use Lenius\LaravelEcommerce\Events\CartItemDecreased;
use Lenius\LaravelEcommerce\Events\CartItemIncremented;
use Lenius\LaravelEcommerce\Events\CartItemRemoved;
use Lenius\LaravelEcommerce\Events\CartItemUpdated;

class Cart extends Basket
{
    /**
     * Instance of the event dispatcher.
     *
     * @var Dispatcher
     */
    private Dispatcher $events;

    public function __construct(StorageInterface $store, IdentifierInterface $identifier, Dispatcher $events)
    {
        $this->events = $events;

        parent::__construct($store, $identifier);
    }

    /**
     * Update an item.
     *
     * @param string $itemIdentifier
     * @param mixed $key
     * @param mixed $value
     */
    public function update(string $itemIdentifier, $key, $value = null): void
    {
        /** @var ItemInterface $item */
        foreach ($this->contents() as $item) {
            if ($item->identifier == $itemIdentifier) {
                $item->update($key, $value);
                $this->events->dispatch(new CartItemUpdated($item));

                break;
            }
        }
    }

    /**
     * Remove an item from the cart.
     *
     * @param string $identifier
     */
    public function remove(string $identifier): void
    {
        $this->events->dispatch(new CartItemRemoved($this->item($identifier)));

        $this->store->remove($identifier);
    }

    /**
     * Destroy/empty the basket.
     */
    public function destroy(): void
    {
        $this->store->destroy();

        $this->events->dispatch(new CartDestroyed());
    }

    /**
     * Increment an item from the cart.
     *
     * @param string $itemIdentifier
     *
     * @return ItemInterface|bool
     */
    public function inc(string $itemIdentifier): ItemInterface|bool
    {
        if ($item = Cart::item($itemIdentifier)) {
            /** @var ItemInterface $item */
            $item->setQuantity($item->getQuantity() + 1);
            $this->events->dispatch(new CartItemIncremented($item));

            return $item;
        }

        return false;
    }

    /**
     * Decrement an item from the cart.
     *
     * @param string $itemIdentifier
     *
     * @return ItemInterface|bool
     */
    public function dec(string $itemIdentifier): ItemInterface|bool
    {
        if ($item = Cart::item($itemIdentifier)) {
            /** @var ItemInterface $item */
            if ($item->getQuantity() > 1) {
                $item->setQuantity($item->getQuantity() - 1);
                $this->events->dispatch(new CartItemDecreased($item));

                return $item;
            } else {
                $this->events->dispatch(new CartItemRemoved($item));
                Cart::remove($itemIdentifier);
            }
        }

        return false;
    }
}
