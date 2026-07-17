<?php

namespace Lenius\LaravelEcommerce\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;

class PruneExpiredCarts extends Command
{
    protected $signature = 'ecommerce:prune-carts';

    protected $description = 'Delete database-stored carts that expired more than the configured retention period ago.';

    public function handle(ConnectionResolverInterface $resolver): int
    {
        if (config('ecommerce.storage', 'session') !== 'database') {
            $this->components->info('The ecommerce database storage driver is not active; nothing to prune.');

            return self::SUCCESS;
        }

        $days = config('ecommerce.database.prune_after_days', 30);

        if (! is_numeric($days) || (int) $days <= 0) {
            $this->components->info('Cart pruning is disabled (ecommerce.database.prune_after_days <= 0).');

            return self::SUCCESS;
        }

        $connection = config('ecommerce.database.connection');
        $table = config('ecommerce.database.table', 'ecommerce_carts');
        $table = is_string($table) && $table !== '' ? $table : 'ecommerce_carts';

        $chunkSize = config('ecommerce.database.prune_chunk_size', 500);
        $chunkSize = is_numeric($chunkSize) && (int) $chunkSize > 0 ? (int) $chunkSize : 500;

        $cutoff = CarbonImmutable::now()->subDays((int) $days);

        $query = $resolver
            ->connection(is_string($connection) && $connection !== '' ? $connection : null)
            ->table($table)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $cutoff);

        $deleted = 0;

        // Delete in bounded batches rather than a single unbounded DELETE,
        // so a large backlog (e.g. the first run after enabling pruning)
        // doesn't hold a long-running lock against the table.
        do {
            $ids = (clone $query)->orderBy('id')->limit($chunkSize)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += $resolver
                ->connection(is_string($connection) && $connection !== '' ? $connection : null)
                ->table($table)
                ->whereIn('id', $ids)
                ->delete();
        } while ($ids->count() === $chunkSize);

        $this->components->info("Pruned {$deleted} expired cart(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
