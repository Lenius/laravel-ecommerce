<?php

namespace Lenius\LaravelEcommerce\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemIncremented
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public mixed $item;

    /**
     * Create a new event instance.
     *
     * @param mixed $item
     */
    public function __construct($item)
    {
        $this->item = $item;
    }
}
