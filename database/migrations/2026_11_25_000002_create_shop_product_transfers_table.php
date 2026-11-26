<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shop_product_transfers')) {
            Schema::create('shop_product_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number', 50)->unique();
                $table->foreignId('from_shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('to_shop_id')->constrained('shops')->cascadeOnDelete();
                $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled', 'reverted'])->default('pending')->index();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reverted_at')->nullable();
                $table->text('revert_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['from_shop_id', 'status']);
                $table->index(['to_shop_id', 'status']);
            });
        }

        if (!Schema::hasTable('shop_product_transfer_items')) {
            Schema::create('shop_product_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_product_transfer_id')->constrained('shop_product_transfers')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->boolean('is_admin_stock')->default(false);
                $table->decimal('buying_price', 15, 2)->default(0);
                $table->decimal('selling_price', 15, 2)->default(0);
                $table->json('batch_allocations')->nullable();
                $table->timestamps();

                $table->index(['shop_product_transfer_id', 'item_id'], 'spti_transfer_item_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_transfer_items');
        Schema::dropIfExists('shop_product_transfers');
    }
};
