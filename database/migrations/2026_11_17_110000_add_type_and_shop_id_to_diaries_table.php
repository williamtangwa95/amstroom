<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('diaries')) {
            Schema::table('diaries', function (Blueprint $table) {
                if (!Schema::hasColumn('diaries', 'type')) {
                    $table->enum('type', ['personal', 'shop'])->default('personal')->after('user_id');
                }
                if (!Schema::hasColumn('diaries', 'shop_id')) {
                    $table->foreignId('shop_id')->nullable()->after('type')->constrained('shops')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('diaries')) {
            Schema::table('diaries', function (Blueprint $table) {
                if (Schema::hasColumn('diaries', 'shop_id')) {
                    $table->dropForeign(['shop_id']);
                    $table->dropColumn('shop_id');
                }
                if (Schema::hasColumn('diaries', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }
    }
};
