<?php

namespace Lenius\LaravelEcommerce\Items;

use Lenius\Basket\Item;
use Lenius\Basket\ItemInterface;
use Lenius\LaravelEcommerce\Contracts\ItemFactoryInterface;

class ItemFactory implements ItemFactoryInterface
{
    public function create(array $data, string $identifier): ItemInterface
    {
        $item = new Item($data);
        $item->setIdentifier($identifier);

        return $item;
    }
}
