<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->schema()->create($this->table(), function (Blueprint $table): void {
            $table->id();
            $table->uuid('identifier')->unique();
            $table->string('user_id')->nullable()->index();
            $table->json('items');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists($this->table());
    }

    private function schema(): Builder
    {
        $connection = config('ecommerce.database.connection');

        return is_string($connection) && $connection !== ''
            ? Schema::connection($connection)
            : Schema::getFacadeRoot();
    }

    private function table(): string
    {
        $table = config('ecommerce.database.table', 'ecommerce_carts');

        return is_string($table) && $table !== '' ? $table : 'ecommerce_carts';
    }
};
