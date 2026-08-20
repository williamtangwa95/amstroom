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

    public function deductStock($shopId, $qty, $userId, $saleId, $isAdminStock = false, $customerName = 'Walk-in Customer', $parentItem = null)
    {
        if ($this->components()->exists()) {
            foreach ($this->components as $component) {
                $childItem = $component->childItem;
                if (!$childItem) continue;

                $childQty = $qty * $component->quantity;
                $childItem->deductStock($shopId, $childQty, $userId, $saleId, $isAdminStock, $customerName, $this);
            }
            return;
        }

        $locationName = 'Main Store';
        if ($shopId) {
            $shop = \App\Models\Shop::find($shopId);
            $locationName = $shop ? $shop->shop_name : 'Shop';

            $stock = ShopStock::where('shop_id', $shopId)
                ->where('item_id', $this->id)
                ->where('is_admin_stock', $isAdminStock)
                ->first();

            if ($stock) {
                $stock->decrement('remaining_quantity', $qty);
            }
        } else {
            // FIFO deduction for Main Store
            $remaining = $qty;
            $batches = MainStock::where('item_id', $this->id)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('date_received')
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
                $deduct = min($batch->remaining_quantity, $remaining);
                $batch->decrement('remaining_quantity', $deduct);
                $remaining -= $deduct;
            }
        }

        $notes = "Sale #{$saleId}";
        if ($parentItem) {
            $notes .= " (Component of {$parentItem->item_name})";
        } elseif (!$shopId) {
            $notes .= " (Direct Sale from Main Store)";
        }

        StockLog::create([
            'item_id'          => $this->id,
            'from_location'    => $locationName,
            'to_location'      => $customerName,
            'quantity'         => $qty,
            'transaction_type' => 'SALE',
            'performed_by'     => $userId,
            'date'             => now()->toDateString(),
            'notes'            => $notes,
            'is_admin_stock'   => $isAdminStock,
        ]);
    }

    public function getTotalMainStock(): int
    {
        if ($this->relationLoaded('mainStocks')) {
            return $this->mainStocks->sum('remaining_quantity');
        }
        return $this->mainStocks()->sum('remaining_quantity');
    }
}
