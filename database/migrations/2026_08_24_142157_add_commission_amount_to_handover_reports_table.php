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
        Schema::table('handover_reports', function (Blueprint $table) {
            $table->decimal('commission_amount', 15, 2)->nullable()->after('expected_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('handover_reports', function (Blueprint $table) {
            $table->dropColumn('commission_amount');
        });
    }
};
