<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class MainStock extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'item_id', 'buying_price', 'selling_price',
        'stocked_quantity', 'remaining_quantity', 'date_received',
        'is_price_pending', 'pending_selling_price', 'allow_components',
    ];

    protected $casts = [
        'date_received' => 'date',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_price_pending' => 'boolean',
        'pending_selling_price' => 'decimal:2',
        'allow_components' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function isLowStock(): bool
    {
        return $this->remaining_quantity <= 5;
    }

    public function getRemainingQuantityAttribute($value)
    {
        if ($this->item && $this->item->components()->exists()) {
            return $this->item->getDynamicStockForMainStore();
        }
        return $value;
    }

    public function getSellingPriceAttribute($value)
    {
        if ($value > 0) {
            return $value;
        }
        if ($this->item && $this->item->components()->exists()) {
            return $this->item->getDynamicPriceForMainStore('selling_price');
        }
        return $value;
    }

    public function getBuyingPriceAttribute($value)
    {
        if ($value > 0) {
            return $value;
        }
        if ($this->item && $this->item->components()->exists()) {
            return $this->item->getDynamicPriceForMainStore('buying_price');
        }
        return $value;
    }
}
