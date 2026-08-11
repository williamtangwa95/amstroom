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
        Schema::create('item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('component_item_id')->constrained('items')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['parent_item_id', 'component_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_components');
    }
};
