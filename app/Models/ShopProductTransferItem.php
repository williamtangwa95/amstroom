<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopProductTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_product_transfer_id',
        'item_id',
        'quantity',
        'is_admin_stock',
        'buying_price',
        'selling_price',
        'batch_allocations',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'is_admin_stock'    => 'boolean',
        'buying_price'      => 'decimal:2',
        'selling_price'     => 'decimal:2',
        'batch_allocations' => 'array',
    ];

    public function transfer()
    {
        return $this->belongsTo(ShopProductTransfer::class, 'shop_product_transfer_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
