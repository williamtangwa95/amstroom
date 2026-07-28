<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Allow item_id to be null for off-catalog / custom line items (proforma only)
            $table->foreignId('item_id')->nullable()->change();
            // Store a custom product description when item is not in the system
            $table->string('custom_name', 255)->nullable()->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('custom_name');
            $table->foreignId('item_id')->nullable(false)->change();
        });
    }
};
