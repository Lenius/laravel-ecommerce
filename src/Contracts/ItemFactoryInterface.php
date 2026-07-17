<?php

namespace Lenius\LaravelEcommerce\Contracts;

use Lenius\Basket\ItemInterface;

interface ItemFactoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, string $identifier): ItemInterface;
}
