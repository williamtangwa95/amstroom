<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'item_id', 'custom_name', 'quantity', 'selling_price',
        'owner_cost_price', 'owner_realized_sp', 'shop_cost_price', 'shop_realized_sp',
    ];

    protected $casts = [
        'selling_price'     => 'decimal:2',
        'owner_cost_price'  => 'decimal:2',
        'owner_realized_sp' => 'decimal:2',
        'shop_cost_price'   => 'decimal:2',
        'shop_realized_sp'  => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /** Returns the display name whether it's a catalog item or a custom off-catalog product. */
    public function getDisplayNameAttribute(): string
    {
        if ($this->item) {
            return $this->item->item_name;
        }
        return $this->custom_name ?? 'Custom Item';
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->selling_price;
    }
}
