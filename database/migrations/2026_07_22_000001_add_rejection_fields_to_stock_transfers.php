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
        // Change columns to string to allow 'rejected' status and be compatible with SQLite / MySQL
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('status')->default('pending_receipt')->change();
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->text('rejection_reason')->nullable()->after('received_at');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejection_reason', 'rejected_by', 'rejected_at']);
            $table->enum('status', ['pending', 'received'])->default('received')->change();
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->enum('status', ['pending_receipt', 'partially_received', 'received'])->default('received')->change();
        });
    }
};
