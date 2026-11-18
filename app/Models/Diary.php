<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'event_date'      => 'date',
        'due_date'        => 'date',
        'needs_follow_up' => 'boolean',
        'cost'            => 'decimal:2',
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
        return $this->due_date && $this->due_date->isPast() && $this->needs_follow_up;
    }
}
