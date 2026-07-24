<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_category_id',
        'activity',
        'description',
        'amount',
        'activity_date',
        'recorded_by',
        'approved_by',
        'status',
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
}
