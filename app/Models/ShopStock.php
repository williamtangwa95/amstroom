<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'item_id', 'buying_price', 'selling_price',
        'quantity', 'remaining_quantity', 'low_stock_alert', 'date_received',
    ];

    protected $casts = [
        'date_received' => 'date',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function isLowStock(): bool
    {
        return $this->remaining_quantity <= $this->low_stock_alert;
    }
}
