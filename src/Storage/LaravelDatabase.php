<?php

namespace Lenius\LaravelEcommerce\Storage;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Lenius\Basket\ItemInterface;
use Lenius\Basket\StorageInterface;
use Lenius\LaravelEcommerce\Contracts\ItemFactoryInterface;
use Lenius\LaravelEcommerce\Exceptions\CartConflictException;
use Lenius\LaravelEcommerce\Items\ItemFactory;
use RuntimeException;
use UnexpectedValueException;

class LaravelDatabase implements StorageInterface
{
    protected string $id = 'basket';

    /** @var array<string, ItemInterface> */
    protected array $cart = [];

    /** @var array<string, array<array-key, mixed>> */
    private array $arrayData = [];

    private ConnectionInterface $connection;

    private ?int $cartId = null;

    private int $version = 1;

    private ItemFactoryInterface $itemFactory;

    /**
     * @param Closure(): mixed|null $userIdentifierResolver
     * @param Closure(): string|null $identifierRotator
     */
    public function __construct(
        ConnectionResolverInterface $database,
        private readonly ?string $connectionName = null,
        private readonly string $table = 'ecommerce_carts',
        private readonly ?int $expirationMinutes = 43200,
        private readonly ?Closure $userIdentifierResolver = null,
        ?ItemFactoryInterface $itemFactory = null,
        private readonly int $conflictRetries = 3,
        private readonly ?Closure $identifierRotator = null,
    ) {
        if ($this->table === '') {
            throw new InvalidArgumentException('The database cart table name cannot be empty.');
        }

        if ($this->conflictRetries < 0) {
            throw new InvalidArgumentException('The database cart conflict retry count cannot be negative.');
        }

        $this->connection = $database->connection($this->connectionName);
        $this->itemFactory = $itemFactory ?? new ItemFactory();
    }

    public function restore(): void
    {
        // Basket calls restore() before it provides the cart identifier.
        // The database cart is therefore restored by setIdentifier().
    }

    public function insertUpdate(ItemInterface $item): void
    {
        $this->mutateAndPersist(function () use ($item): void {
            $this->cart[$item->identifier] = $item;
        });
    }

    /**
     * @return array<string, ItemInterface|array<array-key, mixed>>
     */
    public function &data(bool $asArray = false): array
    {
        if (! $asArray) {
            return $this->cart;
        }

        $this->arrayData = [];

        foreach ($this->cart as $identifier => $item) {
            $this->arrayData[$identifier] = $item->toArray();
        }

        return $this->arrayData;
    }

    public function has(string $identifier): bool
    {
        return array_key_exists($identifier, $this->cart);
    }

    public function item(string $identifier): ItemInterface|bool
    {
        return $this->cart[$identifier] ?? false;
    }

    public function find(string $id): ItemInterface|bool
    {
        foreach ($this->cart as $item) {
            if ($item->id == $id) {
                return $item;
            }
        }

        return false;
    }

    public function remove(string $id): void
    {
        if ($this->cartId === null) {
            unset($this->cart[$id]);

            return;
        }

        $this->mutateAndPersist(function () use ($id): void {
            unset($this->cart[$id]);
        });
    }

    public function destroy(): void
    {
        if ($this->cartId === null) {
            $this->cart = [];

            return;
        }

        $this->mutateAndPersist(function (): void {
            $this->cart = [];
        });
    }

    public function setIdentifier(string $identifier): void
    {
        $this->id = $identifier;
        $this->loadCart();
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    private function mutateAndPersist(Closure $mutation): void
    {
        for ($attempt = 0; $attempt <= $this->conflictRetries; $attempt++) {
            if ($attempt > 0) {
                $this->loadCart();
            }

            $mutation();

            if ($this->persist()) {
                return;
            }
        }

        throw CartConflictException::forIdentifier($this->id);
    }

    private function persist(): bool
    {
        if ($this->cartId === null) {
            return $this->insertCart();
        }

        $nextVersion = $this->version + 1;
        $attributes = [
            'items'      => $this->encodeItems(),
            'version'    => $nextVersion,
            'expires_at' => $this->expiresAt(),
            'updated_at' => CarbonImmutable::now(),
        ];

        if (($userIdentifier = $this->resolveUserIdentifier()) !== null) {
            $attributes['user_id'] = $userIdentifier;
        }

        $updated = $this->connection
            ->table($this->table)
            ->where('id', $this->cartId)
            ->where('version', $this->version)
            ->update($attributes);

        if ($updated !== 1) {
            return false;
        }

        $this->version = $nextVersion;

        return true;
    }

    private function insertCart(): bool
    {
        $now = CarbonImmutable::now();
        $inserted = $this->connection->table($this->table)->insertOrIgnore([
            'identifier' => $this->id,
            'user_id'    => $this->resolveUserIdentifier(),
            'items'      => $this->encodeItems(),
            'version'    => $this->version,
            'expires_at' => $this->expiresAt(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted !== 1) {
            return false;
        }

        $row = $this->connection
            ->table($this->table)
            ->where('identifier', $this->id)
            ->first(['id', 'version']);

        if ($row === null) {
            throw new RuntimeException("Unable to load newly created cart [{$this->id}].");
        }

        $this->setRowIdentity($row);

        return true;
    }

    private function loadCart(): void
    {
        $this->cartId = null;
        $this->version = 1;
        $this->cart = [];

        $row = $this->connection
            ->table($this->table)
            ->where('identifier', $this->id)
            ->first();

        if ($row !== null && ! $this->ownsRow($row)) {
            // The identifier cookie points at a cart owned by a different
            // authenticated user (e.g. a stale cookie surviving a logout on
            // a shared device). Rotate to a fresh identifier instead of
            // exposing or overwriting the other user's cart.
            $this->id = $this->rotateIdentifier();
            $this->loadCart();

            return;
        }

        if ($row === null) {
            return;
        }

        $this->setRowIdentity($row);
        $this->cart = $this->hydrateItems($row->items);

        $this->touchMetadata($row->expires_at ?? null, $row->user_id ?? null);
    }

    private function setRowIdentity(object $row): void
    {
        if (! isset($row->id, $row->version) || ! is_numeric($row->id) || ! is_numeric($row->version)) {
            throw new UnexpectedValueException('The stored cart id and version must be numeric.');
        }

        $this->cartId = (int) $row->id;
        $this->version = (int) $row->version;
    }

    private function touchMetadata(mixed $expiresAt, mixed $rowUserId): void
    {
        if ($this->cartId === null) {
            return;
        }

        $attributes = [];

        if ($this->needsRefresh($expiresAt)) {
            $attributes['expires_at'] = $this->expiresAt();
        }

        if (($userIdentifier = $this->resolveUserIdentifier()) !== null) {
            if (! is_string($rowUserId) && ! is_int($rowUserId)) {
                $attributes['user_id'] = $userIdentifier;
            }
        }

        if ($attributes === []) {
            return;
        }

        $attributes['updated_at'] = CarbonImmutable::now();

        $this->connection
            ->table($this->table)
            ->where('id', $this->cartId)
            ->update($attributes);
    }

    private function ownsRow(object $row): bool
    {
        $rowUserId = $row->user_id ?? null;

        if (! is_string($rowUserId) && ! is_int($rowUserId)) {
            return true;
        }

        $currentUserId = $this->resolveUserIdentifier();

        return $currentUserId !== null && hash_equals((string) $rowUserId, $currentUserId);
    }

    private function rotateIdentifier(): string
    {
        return $this->identifierRotator !== null
            ? ($this->identifierRotator)()
            : (string) Str::uuid();
    }

    private function needsRefresh(mixed $expiresAt): bool
    {
        if ($this->expirationMinutes === null || $this->expirationMinutes <= 0) {
            return false;
        }

        if (! is_string($expiresAt) || $expiresAt === '') {
            return true;
        }

        try {
            $expiration = CarbonImmutable::parse($expiresAt);
        } catch (\Throwable) {
            return true;
        }

        $refreshWindow = max(1, intdiv($this->expirationMinutes + 1, 2));

        return $expiration->lessThanOrEqualTo(CarbonImmutable::now()->addMinutes($refreshWindow));
    }

    /**
     * @return array<string, ItemInterface>
     */
    private function hydrateItems(mixed $items): array
    {
        if (is_string($items)) {
            try {
                $items = json_decode($items, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new UnexpectedValueException('The stored cart items contain invalid JSON.', 0, $exception);
            }
        }

        if (! is_array($items)) {
            throw new UnexpectedValueException('The stored cart items must be a JSON object or array.');
        }

        $cart = [];

        foreach ($items as $identifier => $itemData) {
            if (! is_string($identifier) || ! is_array($itemData)) {
                throw new UnexpectedValueException('The stored cart contains an invalid item.');
            }

            foreach (array_keys($itemData) as $key) {
                if (! is_string($key)) {
                    throw new UnexpectedValueException('The stored cart item data must use string keys.');
                }
            }

            /** @var array<string, mixed> $itemData */
            $item = $this->itemFactory->create($itemData, $identifier);
            $cart[$identifier] = $item;
        }

        return $cart;
    }

    private function encodeItems(): string
    {
        $items = [];

        foreach ($this->cart as $identifier => $item) {
            $items[$identifier] = $item->toArray();
        }

        try {
            return json_encode($items, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('The cart items could not be encoded as JSON.', 0, $exception);
        }
    }

    private function resolveUserIdentifier(): ?string
    {
        if ($this->userIdentifierResolver === null) {
            return null;
        }

        $identifier = ($this->userIdentifierResolver)();

        return is_int($identifier) || is_string($identifier) ? (string) $identifier : null;
    }

    private function expiresAt(): ?CarbonImmutable
    {
        if ($this->expirationMinutes === null || $this->expirationMinutes <= 0) {
            return null;
        }

        return CarbonImmutable::now()->addMinutes($this->expirationMinutes);
    }
}
