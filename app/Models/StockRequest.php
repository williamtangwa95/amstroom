<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'requested_by', 'request_date', 'status', 'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(StockRequestItem::class, 'request_id');
    }

    public function transfer()
    {
        return $this->hasOne(StockTransfer::class, 'request_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
