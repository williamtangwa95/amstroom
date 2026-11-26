<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diaries', function (Blueprint $table) {
            if (!Schema::hasColumn('diaries', 'remind_days_before')) {
                $table->unsignedSmallInteger('remind_days_before')->default(0)->after('needs_follow_up');
            }
            if (!Schema::hasColumn('diaries', 'reminder_date')) {
                $table->date('reminder_date')->nullable()->index()->after('remind_days_before');
            }
            if (!Schema::hasColumn('diaries', 'reminder_time')) {
                $table->string('reminder_time', 5)->default('10:00')->after('reminder_date');
            }
            if (!Schema::hasColumn('diaries', 'notify_email')) {
                $table->boolean('notify_email')->default(false)->after('reminder_time');
            }
            if (!Schema::hasColumn('diaries', 'reminder_notified_at')) {
                $table->timestamp('reminder_notified_at')->nullable()->after('notify_email');
            }
            if (!Schema::hasColumn('diaries', 'due_notified_at')) {
                $table->timestamp('due_notified_at')->nullable()->after('reminder_notified_at');
            }
            if (!Schema::hasColumn('diaries', 'reminder_popup_acknowledged_at')) {
                $table->timestamp('reminder_popup_acknowledged_at')->nullable()->after('due_notified_at');
            }
            if (!Schema::hasColumn('diaries', 'due_popup_acknowledged_at')) {
                $table->timestamp('due_popup_acknowledged_at')->nullable()->after('reminder_popup_acknowledged_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('diaries', function (Blueprint $table) {
            $cols = [
                'remind_days_before',
                'reminder_date',
                'reminder_time',
                'notify_email',
                'reminder_notified_at',
                'due_notified_at',
                'reminder_popup_acknowledged_at',
                'due_popup_acknowledged_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('diaries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
