<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'type',
        'product_id',
        'metadata',
        'is_read',
        'reply_to_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read'  => 'boolean',
    ];

    protected $appends = [
        'is_edited',
    ];

    public function getIsEditedAttribute(): bool
    {
        return $this->updated_at && $this->created_at && $this->updated_at->diffInSeconds($this->created_at) > 1;
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'product_id');
    }

    /** The message this is replying to (one level deep). */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_id');
    }
}
