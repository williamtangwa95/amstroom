<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandoverEditRequest extends Model
{
    use HasFactory;

    private static bool $tableVerified = false;

    protected $fillable = [
        'handover_report_id',
        'shop_id',
        'record_type',
        'record_id',
        'action_type',
        'requested_by',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
        'consumed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /**
     * Auto-bootstrap table if accessed before `php artisan migrate` is run.
     */
    public static function ensureTableExists(): void
    {
        if (self::$tableVerified) {
            return;
        }
        self::$tableVerified = true;

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

    public function handoverReport()
    {
        return $this->belongsTo(HandoverReport::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'return' => 'Sale Return / Refund',
            'delete' => 'Delete Expense',
            default  => $this->record_type === 'sale' ? 'Edit Sale' : 'Edit Expense',
        };
    }

    public function getRecordLabelAttribute(): string
    {
        if ($this->record_type === 'sale') {
            return "Sale #SL-{$this->record_id}";
        }

        $expense = Expense::find($this->record_id);
        return $expense
            ? "Expense #{$expense->id} ({$expense->activity})"
            : "Expense #{$this->record_id}";
    }

    /**
     * Mark an approved unlock request as consumed (auto re-locks the record).
     */
    public function markConsumed(): void
    {
        if ($this->status === 'approved') {
            $this->update([
                'status'      => 'consumed',
                'consumed_at' => now(),
            ]);
        }
    }
}
