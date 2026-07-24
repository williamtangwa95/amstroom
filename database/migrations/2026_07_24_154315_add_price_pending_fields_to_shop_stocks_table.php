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
            $table->boolean('is_price_pending')->default(false)->after('selling_price');
            $table->decimal('pending_selling_price', 15, 2)->nullable()->after('is_price_pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->dropColumn(['is_price_pending', 'pending_selling_price']);
        });
    }
};
