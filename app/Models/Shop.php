<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_name', 'location', 'phone', 'email', 'logo', 'slogan', 'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stockRequests()
    {
        return $this->hasMany(StockRequest::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function shopStocks()
    {
        return $this->hasMany(ShopStock::class);
    }

    public function defects()
    {
        return $this->hasMany(Defect::class);
    }

    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_shop');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
