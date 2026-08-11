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
        Schema::table('main_stocks', function (Blueprint $table) {
            $table->boolean('allow_components')->default(false)->after('remaining_quantity');
        });

        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->boolean('allow_components')->default(false)->after('remaining_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_stocks', function (Blueprint $table) {
            $table->dropColumn('allow_components');
        });

        Schema::table('shop_stocks', function (Blueprint $table) {
            $table->dropColumn('allow_components');
        });
    }
};
