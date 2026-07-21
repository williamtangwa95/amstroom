<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('main_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('buying_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('stocked_quantity');
            $table->integer('remaining_quantity');
            $table->date('date_received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('main_stocks');
    }
};
