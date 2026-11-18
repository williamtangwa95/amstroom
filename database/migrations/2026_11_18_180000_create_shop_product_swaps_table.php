<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_product_swaps', function (Blueprint $table) {
            $table->id();
            $table->string('swap_number')->unique();

            // Participating Branches
            $table->foreignId('source_shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('destination_shop_id')->constrained('shops')->cascadeOnDelete();

            // Source Item (from Shop A)
            $table->foreignId('source_item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('source_quantity')->default(1);

            // Destination Item (from Shop B)
            $table->foreignId('destination_item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('destination_quantity')->default(1);

            // Swap context and reason
            $table->string('reason_type')->default('customer_preference');
            $table->text('notes')->nullable();

            // State Machine
            $table->enum('status', [
                'pending_destination',
                'pending_owner',
                'confirmed',
                'cancelled',
                'reverted'
            ])->default('pending_destination');

            // Audit Actors & Timestamps
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();

            $table->foreignId('destination_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('destination_approved_at')->nullable();

            $table->foreignId('owner_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('owner_approved_at')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reverted_at')->nullable();
            $table->text('revert_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_product_swaps');
    }
};
