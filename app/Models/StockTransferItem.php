<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id', 'item_id', 'quantity', 'buying_price', 'selling_price',
        'status', 'received_by', 'received_at', 'rejection_reason', 'rejected_by', 'rejected_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
