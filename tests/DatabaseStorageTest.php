<?php

namespace Lenius\LaravelEcommerce\Test;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lenius\Basket\IdentifierInterface;
use Lenius\Basket\Item;
use Lenius\Basket\ItemInterface;
use Lenius\LaravelEcommerce\Cart;
use Lenius\LaravelEcommerce\Contracts\ItemFactoryInterface;
use Lenius\LaravelEcommerce\Exceptions\CartConflictException;
use Lenius\LaravelEcommerce\Storage\LaravelDatabase;

class DatabaseStorageTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('ecommerce.storage', 'database');
    }

    public function test_it_persists_and_restores_a_cart(): void
    {
        $storage = $this->storage();
        $storage->setIdentifier('00000000-0000-4000-8000-000000000001');

        $item = $this->item('product-a', 2);
        $item->setIdentifier('item-a');
        $storage->insertUpdate($item);

        $restored = $this->storage();
        $restored->setIdentifier('00000000-0000-4000-8000-000000000001');

        $this->assertTrue($restored->has('item-a'));
        $this->assertInstanceOf(ItemInterface::class, $restored->item('item-a'));
        $this->assertSame(2, $restored->item('item-a')->getQuantity());
        $this->assertSame('product-a', $restored->find('product-a')->id);
        $this->assertSame('Large', $restored->data(true)['item-a']['options']['size']);
        $this->assertSame(2, $this->app['db']->table('ecommerce_carts')->value('version'));
    }

    public function test_it_can_restore_a_custom_item_with_an_item_factory(): void
    {
        $identifier = '00000000-0000-4000-8000-000000000006';
        $factory = new CustomItemFactory();
        $storage = new LaravelDatabase(
            database: $this->app['db'],
            itemFactory: $factory,
        );
        $storage->setIdentifier($identifier);

        $item = new CustomDatabaseItem($this->item('product-a', 2)->toArray());
        $item->setIdentifier('custom-item');
        $storage->insertUpdate($item);

        $restored = new LaravelDatabase(
            database: $this->app['db'],
            itemFactory: $factory,
        );
        $restored->setIdentifier($identifier);
        $restoredItem = $restored->item('custom-item');

        $this->assertInstanceOf(CustomDatabaseItem::class, $restoredItem);
        $this->assertSame(4, $restoredItem->getQuantity());
    }

    public function test_carts_are_isolated_and_can_be_removed_or_destroyed(): void
    {
        $first = $this->storage();
        $first->setIdentifier('00000000-0000-4000-8000-000000000002');
        $firstItem = $this->item('product-a');
        $firstItem->setIdentifier('item-a');
        $first->insertUpdate($firstItem);

        $second = $this->storage();
        $second->setIdentifier('00000000-0000-4000-8000-000000000003');
        $secondItem = $this->item('product-b');
        $secondItem->setIdentifier('item-b');
        $second->insertUpdate($secondItem);

        $first->remove('item-a');
        $this->assertFalse($first->has('item-a'));
        $this->assertTrue($second->has('item-b'));

        $second->destroy();
        $this->assertSame([], $second->data());
        $this->assertSame(2, $this->app['db']->table('ecommerce_carts')->count());
    }

    public function test_it_reloads_and_retries_after_a_version_conflict(): void
    {
        $identifier = '00000000-0000-4000-8000-000000000004';
        $first = $this->storage();
        $second = $this->storage();

        $first->setIdentifier($identifier);
        $second->setIdentifier($identifier);

        $firstItem = $this->item('product-a');
        $firstItem->setIdentifier('item-a');
        $first->insertUpdate($firstItem);

        $secondItem = $this->item('product-b');
        $secondItem->setIdentifier('item-b');
        $second->insertUpdate($secondItem);

        $restored = $this->storage();
        $restored->setIdentifier($identifier);

        $this->assertTrue($restored->has('item-a'));
        $this->assertTrue($restored->has('item-b'));
        $this->assertSame(3, $this->app['db']->table('ecommerce_carts')->value('version'));
    }

    public function test_read_only_load_does_not_refresh_recent_metadata(): void
    {
        CarbonImmutable::setTestNow('2026-01-01 12:00:00');

        try {
            $identifier = '00000000-0000-4000-8000-000000000007';
            $storage = new LaravelDatabase($this->app['db'], expirationMinutes: 100);
            $storage->setIdentifier($identifier);

            $before = $this->app['db']->table('ecommerce_carts')->where('identifier', $identifier)->first();

            CarbonImmutable::setTestNow('2026-01-01 12:01:00');
            $restored = new LaravelDatabase($this->app['db'], expirationMinutes: 100);
            $restored->setIdentifier($identifier);

            $after = $this->app['db']->table('ecommerce_carts')->where('identifier', $identifier)->first();

            $this->assertNotNull($before);
            $this->assertNotNull($after);
            $this->assertSame($before->expires_at, $after->expires_at);
            $this->assertSame($before->updated_at, $after->updated_at);
            $this->assertSame(1, (int) $after->version);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_read_only_load_refreshes_metadata_near_expiration(): void
    {
        CarbonImmutable::setTestNow('2026-01-01 12:00:00');

        try {
            $identifier = '00000000-0000-4000-8000-000000000008';
            $storage = new LaravelDatabase($this->app['db'], expirationMinutes: 100);
            $storage->setIdentifier($identifier);

            CarbonImmutable::setTestNow('2026-01-01 12:51:00');
            $restored = new LaravelDatabase($this->app['db'], expirationMinutes: 100);
            $restored->setIdentifier($identifier);

            $row = $this->app['db']->table('ecommerce_carts')->where('identifier', $identifier)->first();

            $this->assertNotNull($row);
            $this->assertSame('2026-01-01 14:31:00', $row->expires_at);
            $this->assertSame('2026-01-01 12:51:00', $row->updated_at);
            $this->assertSame(1, (int) $row->version);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_a_stale_identifier_cannot_read_or_reassign_another_users_cart(): void
    {
        $identifier = '00000000-0000-4000-8000-00000000000a';

        $alice = new LaravelDatabase(database: $this->app['db'], userIdentifierResolver: fn () => 'alice');
        $alice->setIdentifier($identifier);
        $aliceItem = $this->item('product-a');
        $aliceItem->setIdentifier('item-a');
        $alice->insertUpdate($aliceItem);

        $rotatedTo = null;
        $bob = new LaravelDatabase(
            database: $this->app['db'],
            userIdentifierResolver: fn () => 'bob',
            identifierRotator: function () use (&$rotatedTo) {
                $rotatedTo = (string) Str::uuid();

                return $rotatedTo;
            },
        );
        $bob->setIdentifier($identifier);

        $this->assertNotNull($rotatedTo, 'Expected the identifier to be rotated away from the foreign cart.');
        $this->assertSame($rotatedTo, $bob->getIdentifier());
        $this->assertFalse($bob->has('item-a'));

        $guest = new LaravelDatabase(database: $this->app['db']);
        $guest->setIdentifier($identifier);
        $this->assertFalse(
            $guest->has('item-a'),
            'A guest (no resolved user) must not be able to read a cart owned by an authenticated user either.',
        );

        $stillAlice = new LaravelDatabase(database: $this->app['db'], userIdentifierResolver: fn () => 'alice');
        $stillAlice->setIdentifier($identifier);
        $this->assertTrue($stillAlice->has('item-a'), "Alice's own cart must remain untouched.");
    }

    public function test_prune_command_deletes_carts_expired_beyond_the_retention_window(): void
    {
        $this->app['config']->set('ecommerce.database.prune_after_days', 30);

        CarbonImmutable::setTestNow('2026-01-01 00:00:00');

        try {
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(40));
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(10));
            $this->insertRawCart(expiresAt: null);

            $this->artisan('ecommerce:prune-carts')->assertExitCode(0);

            $this->assertSame(2, $this->app['db']->table('ecommerce_carts')->count());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_prune_command_deletes_across_multiple_batches(): void
    {
        $this->app['config']->set('ecommerce.database.prune_after_days', 30);
        $this->app['config']->set('ecommerce.database.prune_chunk_size', 1);

        CarbonImmutable::setTestNow('2026-01-01 00:00:00');

        try {
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(40));
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(45));
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(50));
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(10));

            $this->artisan('ecommerce:prune-carts')->assertExitCode(0);

            $this->assertSame(1, $this->app['db']->table('ecommerce_carts')->count());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_prune_command_is_disabled_when_prune_after_days_is_zero(): void
    {
        $this->app['config']->set('ecommerce.database.prune_after_days', 0);

        CarbonImmutable::setTestNow('2026-01-01 00:00:00');

        try {
            $this->insertRawCart(expiresAt: CarbonImmutable::now()->subDays(400));

            $this->artisan('ecommerce:prune-carts')->assertExitCode(0);

            $this->assertSame(1, $this->app['db']->table('ecommerce_carts')->count());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_the_prune_command_is_scheduled_when_pruning_is_enabled(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $scheduled = collect($schedule->events())->contains(
            fn ($event) => is_string($event->command ?? null) && str_contains($event->command, 'ecommerce:prune-carts'),
        );

        $this->assertTrue($scheduled, 'Expected the prune command to be registered on the application schedule.');
    }

    private function insertRawCart(?CarbonImmutable $expiresAt): void
    {
        $now = CarbonImmutable::now();

        $this->app['db']->table('ecommerce_carts')->insert([
            'identifier' => (string) Str::uuid(),
            'items' => '[]',
            'version' => 1,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_cart_conflicts_are_rendered_as_http_409(): void
    {
        $request = Request::create('/cart', server: ['HTTP_ACCEPT' => 'application/json']);
        $exception = CartConflictException::forIdentifier('test-cart');

        $response = $exception->toResponse($request);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            ['message' => 'The cart [test-cart] was changed by another request.'],
            $response->getData(true),
        );
    }

    public function test_cart_mutations_are_written_to_the_database(): void
    {
        $identifier = '00000000-0000-4000-8000-000000000005';
        $cart = new Cart(
            $this->storage(),
            new FixedIdentifier($identifier),
            $this->app->make(Dispatcher::class),
        );

        $itemIdentifier = $cart->insert($this->item('product-a'));
        $cart->inc($itemIdentifier);
        $cart->update($itemIdentifier, 'name', 'Updated product');
        $cart->dec($itemIdentifier);

        $restored = $this->storage();
        $restored->setIdentifier($identifier);
        $item = $restored->item($itemIdentifier);

        $this->assertInstanceOf(ItemInterface::class, $item);
        $this->assertSame(1, $item->getQuantity());
        $this->assertSame('Updated product', $item->name);
        $this->assertSame(5, $this->app['db']->table('ecommerce_carts')->value('version'));
    }

    public function test_the_service_provider_can_select_the_database_driver(): void
    {
        $this->app['config']->set('ecommerce.storage', 'database');
        $this->actingAs(User::query()->firstOrFail());
        $this->app->forgetInstance('cart');

        /** @var Cart $cart */
        $cart = $this->app->make('cart');
        $cart->insert($this->item('product-a'));

        $storedCart = $this->app['db']->table('ecommerce_carts')->first();

        $this->assertNotNull($storedCart);
        $this->assertSame('1', $storedCart->user_id);
        $this->assertSame(2, (int) $storedCart->version);
    }

    private function storage(): LaravelDatabase
    {
        return new LaravelDatabase($this->app['db']);
    }

    private function item(string $id, int $quantity = 1): Item
    {
        return new Item([
            'id' => $id,
            'name' => 'Product',
            'price' => 100,
            'quantity' => $quantity,
            'weight' => 200,
            'tax' => 25,
            'options' => ['size' => 'Large'],
        ]);
    }
}

class FixedIdentifier implements IdentifierInterface
{
    public function __construct(private string $identifier)
    {
    }

    public function get(): string
    {
        return $this->identifier;
    }

    public function regenerate(): string
    {
        return $this->identifier;
    }

    public function forget(): void
    {
        $this->identifier = '';
    }
}

class CustomItemFactory implements ItemFactoryInterface
{
    public function create(array $data, string $identifier): ItemInterface
    {
        $item = new CustomDatabaseItem($data);
        $item->setIdentifier($identifier);

        return $item;
    }
}

class CustomDatabaseItem extends Item
{
    public function getQuantity(): int
    {
        return parent::getQuantity() * 2;
    }
}
