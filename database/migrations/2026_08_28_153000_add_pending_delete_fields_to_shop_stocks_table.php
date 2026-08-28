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
            $table->boolean('is_delete_pending')->default(false);
            $table->text('pending_delete_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->dropColumn(['is_delete_pending', 'pending_delete_reason']);
        });
    }
};
