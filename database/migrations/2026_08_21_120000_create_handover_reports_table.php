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
        Schema::create('handover_reports', function (Blueprint $table) {
            $table->id();
            $table->string('handover_no')->unique();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('shop_admin_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            
            $table->decimal('total_owner_sales', 15, 2);
            $table->decimal('total_admin_sales', 15, 2);
            $table->decimal('admin_stock_cost', 15, 2);
            $table->decimal('total_expenses', 15, 2);
            $table->decimal('net_profit', 15, 2);
            $table->decimal('expected_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->string('difference_status'); // exact, shortage, excess
            
            $table->text('difference_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected, completed
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_at')->nullable();
            $table->text('received_remarks')->nullable();
            $table->decimal('amount_received', 15, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('handover_report_id')->nullable()->constrained('handover_reports')->onDelete('set null');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('handover_report_id')->nullable()->constrained('handover_reports')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['handover_report_id']);
            $table->dropColumn('handover_report_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['handover_report_id']);
            $table->dropColumn('handover_report_id');
        });

        Schema::dropIfExists('handover_reports');
    }
};
