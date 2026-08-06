<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('reply_to_id')
                  ->nullable()
                  ->after('is_read')
                  ->constrained('chat_messages')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeignIfExists(['reply_to_id']);
            $table->dropColumnIfExists('reply_to_id');
        });
    }
};
