<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->enum('type', ['invoice', 'proforma', 'delivery'])->index();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_id', 100)->nullable();
            $table->string('customer_po_box', 150)->nullable();
            $table->string('deliver_to', 255)->nullable();
            $table->date('issue_date');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time', 20)->nullable();
            $table->date('validity_date')->nullable();
            $table->string('terms_of_payment', 150)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('issued');
            $table->timestamps();

            $table->index(['shop_id', 'type']);
            $table->index(['sale_id', 'type']);
            $table->index('issue_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
