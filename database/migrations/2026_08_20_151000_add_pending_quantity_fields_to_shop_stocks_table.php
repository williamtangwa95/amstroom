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
            $table->integer('pending_quantity_request')->nullable();
            $table->text('pending_quantity_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->dropColumn(['pending_quantity_request', 'pending_quantity_reason']);
        });
    }
};
