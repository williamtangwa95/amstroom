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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('url', 255);
            $table->string('method', 10);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->default('Desktop');
            $table->string('browser', 50)->default('Unknown');
            $table->string('platform', 50)->default('Unknown');
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('session_id', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
