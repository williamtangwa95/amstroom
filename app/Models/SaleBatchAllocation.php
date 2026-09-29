<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleBatchAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'sale_item_id',
        'stock_type',
        'stock_id',
        'quantity',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function returnAllocations()
    {
        return $this->hasMany(SaleReturnBatchAllocation::class);
    }

    public function getReturnedQuantityAttribute(): int
    {
        return (int) $this->returnAllocations()->sum('quantity');
    }

    public function getAvailableReturnQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    public function shopStock()
    {
        return $this->belongsTo(ShopStock::class, 'stock_id');
    }

    public function mainStock()
    {
        return $this->belongsTo(MainStock::class, 'stock_id');
    }
}
