<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('from_location');
            $table->string('to_location');
            $table->integer('quantity');
            $table->enum('transaction_type', [
                'STOCK_RECEIVED',
                'STOCK_TRANSFER',
                'SALE',
                'DEFECT',
                'ADJUSTMENT'
            ]);
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
