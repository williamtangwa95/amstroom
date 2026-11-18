<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('items') && !Schema::hasColumn('items', 'unit_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('unit_id')->nullable()->after('category_id')->constrained('units')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'unit_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            });
        }
    }
};
