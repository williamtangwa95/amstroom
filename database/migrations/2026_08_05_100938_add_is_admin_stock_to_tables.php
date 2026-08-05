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
            $table->boolean('is_admin_stock')->default(false)->after('is_sellable');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_admin_stock')->default(false)->after('status');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->boolean('is_admin_stock')->default(false)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->dropColumn('is_admin_stock');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('is_admin_stock');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropColumn('is_admin_stock');
        });
    }
};
