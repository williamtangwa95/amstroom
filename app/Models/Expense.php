<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Expense extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'expense_category_id',
        'activity',
        'description',
        'amount',
        'activity_date',
        'recorded_by',
        'approved_by',
        'status',
        'handover_report_id',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function handoverReport()
    {
        return $this->belongsTo(HandoverReport::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isReviewRequested(): bool
    {
        return $this->status === 'review_requested';
    }

    public function isEditable(): bool
    {
        return $this->status === 'editable';
    }

    /**
     * True when this expense is linked to a submitted/under_review/approved/completed Cash Handover.
     */
    public function isLockedByHandover(): bool
    {
        if (!$this->handover_report_id) {
            return false;
        }

        $report = $this->relationLoaded('handoverReport')
            ? $this->handoverReport
            : $this->handoverReport()->first();

        return $report !== null && $report->locksTransactions();
    }

    /**
     * Latest active (pending or approved) handover unlock request for this expense.
     */
    public function activeHandoverEditRequest(?string $actionType = null): ?HandoverEditRequest
    {
        if (!$this->handover_report_id) {
            return null;
        }

        HandoverEditRequest::ensureTableExists();

        $query = HandoverEditRequest::where('record_type', 'expense')
            ->where('record_id', $this->id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        if ($actionType !== null) {
            $query->where('action_type', $actionType);
        }

        return $query->first();
    }

    /**
     * Approved unused unlock request for this expense (optionally matching edit|delete).
     */
    public function approvedHandoverUnlock(?string $actionType = null): ?HandoverEditRequest
    {
        if (!$this->handover_report_id) {
            return null;
        }

        HandoverEditRequest::ensureTableExists();

        $query = HandoverEditRequest::where('record_type', 'expense')
            ->where('record_id', $this->id)
            ->where('status', 'approved')
            ->orderByDesc('id');

        if ($actionType !== null) {
            $query->where('action_type', $actionType);
        }

        return $query->first();
    }

    /**
     * Check whether the given user may mutate (edit or delete) this expense under handover lock rules.
     */
    public function canMutateUnderHandover(User $user, ?string $actionType = null): bool
    {
        if (!$this->isLockedByHandover()) {
            return true;
        }

        if ($user->isOwner()) {
            return true;
        }

        return $this->approvedHandoverUnlock($actionType) !== null;
    }
}
