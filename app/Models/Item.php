<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name', 'category_id', 'specification', 'brand', 'model', 'warranty_period', 'image_path',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function mainStocks()
    {
        return $this->hasMany(MainStock::class);
    }

    public function shopStocks()
    {
        return $this->hasMany(ShopStock::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function defects()
    {
        return $this->hasMany(Defect::class);
    }

    public function stockLogs()
    {
        return $this->hasMany(StockLog::class);
    }

    public function getTotalMainStock(): int
    {
        if ($this->relationLoaded('mainStocks')) {
            return $this->mainStocks->sum('remaining_quantity');
        }
        return $this->mainStocks()->sum('remaining_quantity');
    }
}
