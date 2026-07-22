<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id', 'seller_id', 'customer_name', 'total_amount', 'payment_method', 'sale_date',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
