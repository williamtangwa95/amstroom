<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->enum('stock_type', ['shop', 'main'])->default('shop');
            $table->unsignedBigInteger('stock_id'); // shop_stocks.id or main_stocks.id
            $table->integer('quantity');
            $table->timestamps();

            $table->index(['sale_id']);
            $table->index(['sale_item_id']);
            $table->index(['stock_type', 'stock_id']);
            $table->index(['stock_id']);
        });

        Schema::create('sale_return_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_return_item_id')->constrained('sale_return_items')->cascadeOnDelete();
            $table->foreignId('sale_batch_allocation_id')->nullable()->constrained('sale_batch_allocations')->nullOnDelete();
            $table->enum('stock_type', ['shop', 'main'])->default('shop');
            $table->unsignedBigInteger('stock_id');
            $table->integer('quantity');
            $table->timestamps();

            $table->index(['sale_return_id']);
            $table->index(['sale_return_item_id']);
            $table->index(['sale_batch_allocation_id'], 'srba_alloc_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_batch_allocations');
        Schema::dropIfExists('sale_batch_allocations');
    }
};
