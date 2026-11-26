<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'shop_id',
        'event_name',
        'event_description',
        'event_date',
        'due_date',
        'cost',
        'needs_follow_up',
        'remind_days_before',
        'reminder_date',
        'reminder_time',
        'notify_email',
        'reminder_notified_at',
        'due_notified_at',
        'reminder_popup_acknowledged_at',
        'due_popup_acknowledged_at',
    ];

    protected $casts = [
        'event_date'                     => 'date',
        'due_date'                       => 'date',
        'reminder_date'                  => 'date',
        'needs_follow_up'                => 'boolean',
        'notify_email'                   => 'boolean',
        'remind_days_before'             => 'integer',
        'cost'                           => 'decimal:2',
        'reminder_notified_at'           => 'datetime',
        'due_notified_at'                => 'datetime',
        'reminder_popup_acknowledged_at' => 'datetime',
        'due_popup_acknowledged_at'      => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function isPersonal(): bool
    {
        return $this->type === 'personal';
    }

    public function isShop(): bool
    {
        return $this->type === 'shop';
    }

    public function needsFollowUp(): bool
    {
        return (bool) $this->needs_follow_up;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->due_date->isToday() && $this->needs_follow_up;
    }

    /**
     * Effective reminder date: uses stored reminder_date, or computes from due_date - remind_days_before.
     */
    public function getEffectiveReminderDateAttribute(): ?Carbon
    {
        if ($this->reminder_date) {
            return $this->reminder_date->copy();
        }
        if ($this->due_date) {
            $days = max(0, (int) ($this->remind_days_before ?? 0));
            return $this->due_date->copy()->subDays($days);
        }
        return null;
    }

    /**
     * Normalized HH:MM reminder time (defaults to 10:00).
     */
    public function getReminderTimeValueAttribute(): string
    {
        $raw = trim((string) ($this->reminder_time ?? ''));
        if (preg_match('/^\d{2}:\d{2}/', $raw, $m)) {
            return $m[0];
        }
        return '10:00';
    }

    /**
     * Human-readable 12-hour reminder time (e.g. 10:00 AM).
     */
    public function getFormattedReminderTimeAttribute(): string
    {
        try {
            return Carbon::createFromFormat('H:i', $this->reminder_time_value)->format('h:i A');
        } catch (\Throwable $e) {
            return '10:00 AM';
        }
    }

    /**
     * Determine current active follow-up milestone ('due', 'reminder', or null)
     * honoring both the date and configured reminder_time (default 10:00 AM).
     */
    public function currentTriggerStage(?Carbon $now = null): ?string
    {
        if (!$this->needs_follow_up || !$this->due_date) {
            return null;
        }

        $now = ($now ?? now())->copy();
        $time = $this->reminder_time_value;
        $dueAt = Carbon::parse($this->due_date->toDateString() . ' ' . $time);
        $remDate = $this->effective_reminder_date ?? $this->due_date;
        $reminderAt = Carbon::parse($remDate->toDateString() . ' ' . $time);

        if ($now->gte($dueAt)) {
            return 'due';
        }

        if ($now->gte($reminderAt)) {
            return 'reminder';
        }

        return null;
    }
}
