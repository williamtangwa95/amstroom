<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnBatchAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_return_id',
        'sale_return_item_id',
        'sale_batch_allocation_id',
        'stock_type',
        'stock_id',
        'quantity',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleReturnItem()
    {
        return $this->belongsTo(SaleReturnItem::class);
    }

    public function saleBatchAllocation()
    {
        return $this->belongsTo(SaleBatchAllocation::class);
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
