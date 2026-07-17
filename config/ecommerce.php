<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cart storage
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "session" and "database".
    |
    */
    'storage' => env('ECOMMERCE_STORAGE', 'session'),

    'database' => [
        // A null connection uses Laravel's current default database connection.
        'connection' => env('ECOMMERCE_DB_CONNECTION'),
        'table' => env('ECOMMERCE_DB_TABLE', 'ecommerce_carts'),
        'expiration_minutes' => env('ECOMMERCE_CART_EXPIRATION', 60 * 24 * 30),
        'conflict_retries' => env('ECOMMERCE_CART_CONFLICT_RETRIES', 3),

        // How many days after a cart expires (see expiration_minutes above)
        // it may be permanently deleted by the ecommerce:prune-carts
        // command. Set to 0 to disable pruning entirely (rows are then kept
        // forever, e.g. so an abandoned-cart follow-up job can still read
        // the stamped user_id after the cart itself has expired).
        'prune_after_days' => env('ECOMMERCE_CART_PRUNE_DAYS', 30),

        // Standard cron expression controlling how often the prune command
        // itself runs, once it is registered on the application schedule.
        'prune_cron' => env('ECOMMERCE_CART_PRUNE_CRON', '0 3 * * *'),

        // Rows are deleted in batches of this size instead of a single
        // unbounded DELETE, so pruning a large backlog (e.g. the first run
        // after enabling this feature) doesn't hold a long-running lock.
        'prune_chunk_size' => env('ECOMMERCE_CART_PRUNE_CHUNK_SIZE', 500),
    ],

    'cookie' => [
        'name' => 'cart_identifier',
        'minutes' => env('ECOMMERCE_COOKIE_MINUTES', 60 * 24 * 30),
        'path' => '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Disable default route
    |--------------------------------------------------------------------------
    |
    */
    'disable_default_route' => false,

    'prefix' => 'ecommerce',

    'middleware' => ['web'],
];
