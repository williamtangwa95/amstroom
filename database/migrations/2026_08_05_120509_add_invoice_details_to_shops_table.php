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
        Schema::table('shops', function (Blueprint $table) {
            $table->string('tin_number', 100)->nullable()->after('slogan');
            $table->string('address', 255)->nullable()->after('tin_number');
            $table->string('bank_name', 150)->nullable()->after('address');
            $table->string('bank_account', 100)->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['tin_number', 'address', 'bank_name', 'bank_account']);
        });
    }
};
