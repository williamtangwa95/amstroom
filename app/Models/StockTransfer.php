<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_store', 'to_shop', 'approved_by', 'request_id', 'transfer_date', 'status',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'to_shop');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function request()
    {
        return $this->belongsTo(StockRequest::class, 'request_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    public function pendingItems()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id')->where('status', 'pending');
    }

    public function receivedItems()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id')->where('status', 'received');
    }

    public function rejectedItems()
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id')->where('status', 'rejected');
    }
}
