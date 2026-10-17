<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stock_transfers', 'status')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->enum('status', ['pending_receipt', 'partially_received', 'received'])->default('received')->after('transfer_date');
            });
        }

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_items', 'status')) {
                $table->enum('status', ['pending', 'received'])->default('received')->after('selling_price');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'received_by')) {
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['status', 'received_by', 'received_at']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn(['status']);
        });
    }
};
