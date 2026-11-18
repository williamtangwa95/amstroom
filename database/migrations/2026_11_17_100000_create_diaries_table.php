<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['personal', 'shop'])->default('personal');
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('event_name');
            $table->text('event_description');
            $table->date('event_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->boolean('needs_follow_up')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diaries');
    }
};
