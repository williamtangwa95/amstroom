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
        'is_price_pending', 'pending_selling_price', 'is_sellable', 'is_admin_stock', 'allow_components',
    ];

    protected $casts = [
        'date_received' => 'date',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_price_pending' => 'boolean',
        'pending_selling_price' => 'decimal:2',
        'is_sellable' => 'boolean',
        'is_admin_stock' => 'boolean',
        'allow_components' => 'boolean',
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

    public function getRemainingQuantityAttribute($value)
    {
        if ($this->item && $this->item->components()->exists()) {
            return $this->item->getDynamicStockForShop($this->shop_id, (bool)($this->is_admin_stock ?? false));
        }
        return $value;
    }

    public function getSellingPriceAttribute($value)
    {
        if ($value > 0) {
            return $value;
        }
        if ($this->item && $this->item->components()->exists()) {
            return $this->item->getDynamicPriceForShop($this->shop_id, 'selling_price', (bool)($this->is_admin_stock ?? false));
        }
        return $value;
    }

    public function getBuyingPriceAttribute($value)
    {
        if ($value > 0) {
            return $value;
        }
        if ($this->item && $this->item->components()->exists()) {
            return $this->item->getDynamicPriceForShop($this->shop_id, 'buying_price', (bool)($this->is_admin_stock ?? false));
        }
        return $value;
    }
}
