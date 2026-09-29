<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Item extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'item_name', 'category_id', 'specification', 'brand', 'model', 'warranty_period', 'image_path',
        'is_admin_item', 'shop_id',
    ];

    protected $casts = [
        'is_admin_item' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function mainStocks()
    {
        return $this->hasMany(MainStock::class);
    }

    public function mainStock()
    {
        return $this->hasOne(MainStock::class)->latestOfMany();
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

    public function components()
    {
        return $this->hasMany(ItemComponent::class, 'parent_item_id');
    }

    public function parentComponents()
    {
        return $this->hasMany(ItemComponent::class, 'component_item_id');
    }

    public function getDynamicStockForShop($shopId, $isAdminStock = false, &$visited = [])
    {
        if (in_array($this->id, $visited)) {
            return 0;
        }
        $visited[] = $this->id;

        if ($this->components()->exists()) {
            $minStock = null;
            foreach ($this->components as $component) {
                $childItem = $component->childItem;
                if (!$childItem) continue;

                $childStock = $childItem->getDynamicStockForShop($shopId, $isAdminStock, $visited);
                $qtyNeeded = $component->quantity;

                $possible = (int) floor($childStock / $qtyNeeded);
                if ($minStock === null || $possible < $minStock) {
                    $minStock = $possible;
                }
            }
            return $minStock ?? 0;
        }

        return (int) ShopStock::where('shop_id', $shopId)
            ->where('item_id', $this->id)
            ->where('is_admin_stock', $isAdminStock)
            ->sum('remaining_quantity');
    }

    public function getDynamicStockForMainStore(&$visited = [])
    {
        if (in_array($this->id, $visited)) {
            return 0;
        }
        $visited[] = $this->id;

        if ($this->components()->exists()) {
            $minStock = null;
            foreach ($this->components as $component) {
                $childItem = $component->childItem;
                if (!$childItem) continue;

                $childStock = $childItem->getDynamicStockForMainStore($visited);
                $qtyNeeded = $component->quantity;

                $possible = (int) floor($childStock / $qtyNeeded);
                if ($minStock === null || $possible < $minStock) {
                    $minStock = $possible;
                }
            }
            return $minStock ?? 0;
        }

        return (int) MainStock::where('item_id', $this->id)->sum('remaining_quantity');
    }

    public function getDynamicPriceForShop($shopId, $field, $isAdminStock = false, &$visited = [])
    {
        if (in_array($this->id, $visited)) {
            return 0.0;
        }
        $visited[] = $this->id;

        $total = 0.0;
        foreach ($this->components as $component) {
            $childItem = $component->childItem;
            if (!$childItem) continue;

            if ($childItem->components()->exists()) {
                $childPrice = $childItem->getDynamicPriceForShop($shopId, $field, $isAdminStock, $visited);
            } else {
                $stockRow = ShopStock::where('shop_id', $shopId)
                    ->where('item_id', $childItem->id)
                    ->where('is_admin_stock', $isAdminStock)
                    ->first();
                if (!$stockRow) {
                    $stockRow = MainStock::where('item_id', $childItem->id)
                        ->orderByDesc('date_received')
                        ->first();
                }
                $childPrice = $stockRow ? (float) $stockRow->$field : 0.0;
            }
            $total += $childPrice * $component->quantity;
        }
        return $total;
    }

    public function getDynamicPriceForMainStore($field, &$visited = [])
    {
        if (in_array($this->id, $visited)) {
            return 0.0;
        }
        $visited[] = $this->id;

        $total = 0.0;
        foreach ($this->components as $component) {
            $childItem = $component->childItem;
            if (!$childItem) continue;

            if ($childItem->components()->exists()) {
                $childPrice = $childItem->getDynamicPriceForMainStore($field, $visited);
            } else {
                $stockRow = MainStock::where('item_id', $childItem->id)
                    ->orderByDesc('date_received')
                    ->first();
                $childPrice = $stockRow ? (float) $stockRow->$field : 0.0;
            }
            $total += $childPrice * $component->quantity;
        }
        return $total;
    }

    public function deductStock($shopId, $qty, $userId, $saleId, $isAdminStock = false, $customerName = 'Walk-in Customer', $parentItem = null, $saleItemId = null)
    {
        if ($saleItemId === null && $saleId) {
            $saleItem = \App\Models\SaleItem::where('sale_id', $saleId)->where('item_id', $this->id)->first();
            $saleItemId = $saleItem?->id ?? 0;
        }

        if ($shopId) {
            \App\Services\StockAllocationService::allocateAndDeductShopStock(
                $shopId,
                $this->id,
                $qty,
                $saleId ?? 0,
                $saleItemId ?? 0,
                $userId,
                $isAdminStock,
                $customerName,
                $parentItem
            );
        } else {
            \App\Services\StockAllocationService::allocateAndDeductMainStock(
                $this->id,
                $qty,
                $saleId ?? 0,
                $saleItemId ?? 0,
                $userId,
                $customerName,
                $parentItem
            );
        }
    }

    public function getTotalMainStock(): int
    {
        if ($this->relationLoaded('mainStocks')) {
            return $this->mainStocks->sum('remaining_quantity');
        }
        return $this->mainStocks()->sum('remaining_quantity');
    }
}
