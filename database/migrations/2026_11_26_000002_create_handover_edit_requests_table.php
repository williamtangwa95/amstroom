<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('handover_edit_requests')) {
            Schema::create('handover_edit_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('handover_report_id')->constrained('handover_reports')->cascadeOnDelete();
                $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
                $table->enum('record_type', ['sale', 'expense']);
                $table->unsignedBigInteger('record_id');
                $table->enum('action_type', ['edit', 'delete', 'return'])->default('edit');
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->text('reason');
                $table->enum('status', ['pending', 'approved', 'rejected', 'consumed'])->default('pending')->index();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_remarks')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();

                $table->index(['record_type', 'record_id', 'status'], 'her_record_status_idx');
                $table->index(['handover_report_id', 'status'], 'her_handover_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_edit_requests');
    }
};
