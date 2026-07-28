<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'item_id', 'quantity', 'selling_price',
        'owner_cost_price', 'owner_realized_sp', 'shop_cost_price', 'shop_realized_sp',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'owner_cost_price' => 'decimal:2',
        'owner_realized_sp' => 'decimal:2',
        'shop_cost_price' => 'decimal:2',
        'shop_realized_sp' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->selling_price;
    }
}
