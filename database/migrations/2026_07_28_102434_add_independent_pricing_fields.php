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
        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->boolean('is_sellable')->default(true)->after('is_price_pending');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('owner_cost_price', 12, 2)->nullable()->after('selling_price');
            $table->decimal('owner_realized_sp', 12, 2)->nullable()->after('owner_cost_price');
            $table->decimal('shop_cost_price', 12, 2)->nullable()->after('owner_realized_sp');
            $table->decimal('shop_realized_sp', 12, 2)->nullable()->after('shop_cost_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->dropColumn('is_sellable');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['owner_cost_price', 'owner_realized_sp', 'shop_cost_price', 'shop_realized_sp']);
        });
    }
};
