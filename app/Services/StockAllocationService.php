<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleBatchAllocation;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnBatchAllocation;
use App\Models\SaleReturnItem;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;

class StockAllocationService
{
    /**
     * Check available stock for a shop item.
     */
    public static function checkShopStockAvailability(int $shopId, int $itemId, bool $isAdminStock = false): int
    {
        $item = Item::find($itemId);
        if ($item && $item->components()->exists()) {
            return (int) $item->getDynamicStockForShop($shopId, $isAdminStock);
        }

        return (int) ShopStock::where('shop_id', $shopId)
            ->where('item_id', $itemId)
            ->where('is_admin_stock', $isAdminStock)
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');
    }

    /**
     * Check available stock for a main store item.
     */
    public static function checkMainStockAvailability(int $itemId): int
    {
        $item = Item::find($itemId);
        if ($item && $item->components()->exists()) {
            return (int) $item->getDynamicStockForMainStore();
        }

        return (int) MainStock::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->sum('remaining_quantity');
    }

    /**
     * FIFO deduction for Shop stock with row locking & batch allocation tracking.
     */
    public static function allocateAndDeductShopStock(
        int $shopId,
        int $itemId,
        int $requestedQty,
        int $saleId,
        int $saleItemId,
        int $userId,
        bool $isAdminStock = false,
        string $customerName = 'Walk-in Customer',
        ?Item $parentItem = null
    ): void {
        if ($requestedQty <= 0) return;

        $item = Item::findOrFail($itemId);

        // Handle static bundle components recursively
        if ($item->components()->exists()) {
            foreach ($item->components as $component) {
                $childItem = $component->childItem;
                if (!$childItem) continue;

                $childQty = $requestedQty * $component->quantity;
                self::allocateAndDeductShopStock(
                    $shopId,
                    $childItem->id,
                    $childQty,
                    $saleId,
                    $saleItemId,
                    $userId,
                    $isAdminStock,
                    $customerName,
                    $item
                );
            }
            return;
        }

        $shop = Shop::find($shopId);
        $locationName = $shop ? $shop->shop_name : 'Shop';

        // Lock batches for update in deterministic FIFO order (oldest received date, then lowest ID)
        $batches = ShopStock::where('shop_id', $shopId)
            ->where('item_id', $itemId)
            ->where('is_admin_stock', $isAdminStock)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('date_received', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $totalAvailable = (int) $batches->sum('remaining_quantity');

        if ($totalAvailable < $requestedQty) {
            $shortage = $requestedQty - $totalAvailable;
            throw new InsufficientStockException(
                "Unable to complete sale. Insufficient stock for \"{$item->item_name}\". Requested: {$requestedQty}, Available: {$totalAvailable}, Shortage: {$shortage}. No stock was deducted.",
                $item->item_name,
                $requestedQty,
                $totalAvailable,
                $shortage
            );
        }

        $remainingToDeduct = $requestedQty;

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $deduct = min((int) $batch->remaining_quantity, $remainingToDeduct);
            if ($deduct > 0) {
                $batch->decrement('remaining_quantity', $deduct);
                $remainingToDeduct -= $deduct;

                if ($saleId && DB::table('sales')->where('id', $saleId)->exists()) {
                    $validSaleItemId = ($saleItemId && DB::table('sale_items')->where('id', $saleItemId)->exists()) ? $saleItemId : null;
                    SaleBatchAllocation::create([
                        'sale_id'      => $saleId,
                        'sale_item_id' => $validSaleItemId,
                        'stock_type'   => 'shop',
                        'stock_id'     => $batch->id,
                        'quantity'     => $deduct,
                    ]);
                }
            }
        }

        if ($remainingToDeduct > 0) {
            throw new InsufficientStockException(
                "Critical stock deduction error for \"{$item->item_name}\". Allocation failed to fulfill requested quantity.",
                $item->item_name,
                $requestedQty,
                $totalAvailable,
                $remainingToDeduct
            );
        }

        $notes = "Sale #{$saleId}";
        if ($parentItem) {
            $notes .= " (Component of {$parentItem->item_name})";
        }

        StockLog::create([
            'item_id'          => $itemId,
            'from_location'    => $locationName,
            'to_location'      => $customerName,
            'quantity'         => $requestedQty,
            'transaction_type' => 'SALE',
            'performed_by'     => $userId,
            'date'             => now()->toDateString(),
            'notes'            => $notes,
            'is_admin_stock'   => $isAdminStock,
        ]);
    }

    /**
     * FIFO deduction for Main Store stock with row locking & batch allocation tracking.
     */
    public static function allocateAndDeductMainStock(
        int $itemId,
        int $requestedQty,
        int $saleId,
        int $saleItemId,
        int $userId,
        string $customerName = 'Walk-in Customer',
        ?Item $parentItem = null
    ): void {
        if ($requestedQty <= 0) return;

        $item = Item::findOrFail($itemId);

        // Handle static bundle components recursively
        if ($item->components()->exists()) {
            foreach ($item->components as $component) {
                $childItem = $component->childItem;
                if (!$childItem) continue;

                $childQty = $requestedQty * $component->quantity;
                self::allocateAndDeductMainStock(
                    $childItem->id,
                    $childQty,
                    $saleId,
                    $saleItemId,
                    $userId,
                    $customerName,
                    $item
                );
            }
            return;
        }

        // Lock batches for update in deterministic FIFO order
        $batches = MainStock::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('date_received', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $totalAvailable = (int) $batches->sum('remaining_quantity');

        if ($totalAvailable < $requestedQty) {
            $shortage = $requestedQty - $totalAvailable;
            throw new InsufficientStockException(
                "Unable to complete sale. Insufficient warehouse stock for \"{$item->item_name}\". Requested: {$requestedQty}, Available: {$totalAvailable}, Shortage: {$shortage}. No stock was deducted.",
                $item->item_name,
                $requestedQty,
                $totalAvailable,
                $shortage
            );
        }

        $remainingToDeduct = $requestedQty;

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $deduct = min((int) $batch->remaining_quantity, $remainingToDeduct);
            if ($deduct > 0) {
                $batch->decrement('remaining_quantity', $deduct);
                $remainingToDeduct -= $deduct;

                if ($saleId && DB::table('sales')->where('id', $saleId)->exists()) {
                    $validSaleItemId = ($saleItemId && DB::table('sale_items')->where('id', $saleItemId)->exists()) ? $saleItemId : null;
                    SaleBatchAllocation::create([
                        'sale_id'      => $saleId,
                        'sale_item_id' => $validSaleItemId,
                        'stock_type'   => 'main',
                        'stock_id'     => $batch->id,
                        'quantity'     => $deduct,
                    ]);
                }
            }
        }

        if ($remainingToDeduct > 0) {
            throw new InsufficientStockException(
                "Critical warehouse stock deduction error for \"{$item->item_name}\". Allocation failed.",
                $item->item_name,
                $requestedQty,
                $totalAvailable,
                $remainingToDeduct
            );
        }

        $notes = "Sale #{$saleId}";
        if ($parentItem) {
            $notes .= " (Component of {$parentItem->item_name})";
        } else {
            $notes .= " (Direct Sale from Main Store)";
        }

        StockLog::create([
            'item_id'          => $itemId,
            'from_location'    => 'Main Store',
            'to_location'      => $customerName,
            'quantity'         => $requestedQty,
            'transaction_type' => 'SALE',
            'performed_by'     => $userId,
            'date'             => now()->toDateString(),
            'notes'            => $notes,
            'is_admin_stock'   => false,
        ]);
    }

    /**
     * Restore stock for a return strictly against original sale batch allocations.
     */
    public static function restoreReturnAllocations(
        SaleReturn $saleReturn,
        SaleReturnItem $returnItem,
        SaleItem $saleItem,
        int $qtyToReturn,
        int $userId
    ): void {
        if ($qtyToReturn <= 0) return;

        $sale = $saleReturn->sale;
        $customerName = $sale?->customer_name ?: 'Customer';
        $locationName = $sale?->shop ? $sale->shop->shop_name : 'Main Store';

        // Find existing allocations for this sale item
        $allocations = SaleBatchAllocation::where('sale_item_id', $saleItem->id)
            ->with('returnAllocations')
            ->orderBy('id', 'desc') // LIFO for returning: restore into the most recently deducted batch first
            ->get();

        $remainingToRestore = $qtyToReturn;

        if ($allocations->isNotEmpty()) {
            // Verify total returnable across allocations
            $totalReturnable = $allocations->sum(function ($alloc) {
                $alreadyReturned = (int) $alloc->returnAllocations->sum('quantity');
                return max(0, $alloc->quantity - $alreadyReturned);
            });

            if ($qtyToReturn > $totalReturnable) {
                throw new \Exception("Unable to process return for \"{$saleItem->display_name}\". Returnable quantity on sale: {$totalReturnable}, Requested return: {$qtyToReturn}. No stock was restored.");
            }

            foreach ($allocations as $alloc) {
                if ($remainingToRestore <= 0) break;

                $alreadyReturned = (int) $alloc->returnAllocations->sum('quantity');
                $returnableOnThisAlloc = max(0, $alloc->quantity - $alreadyReturned);

                if ($returnableOnThisAlloc <= 0) continue;

                $fillQty = min($returnableOnThisAlloc, $remainingToRestore);

                if ($alloc->stock_type === 'shop') {
                    $batch = ShopStock::where('id', $alloc->stock_id)->lockForUpdate()->first();
                    if ($batch) {
                        // Invariant guard: remaining_quantity cannot exceed quantity
                        $capacity = max(0, (int)$batch->quantity - (int)$batch->remaining_quantity);
                        $actualFill = min($fillQty, $capacity > 0 ? $capacity : $fillQty);
                        $batch->increment('remaining_quantity', $actualFill);
                    }
                } else {
                    $batch = MainStock::where('id', $alloc->stock_id)->lockForUpdate()->first();
                    if ($batch) {
                        $capacity = max(0, (int)$batch->stocked_quantity - (int)$batch->remaining_quantity);
                        $actualFill = min($fillQty, $capacity > 0 ? $capacity : $fillQty);
                        $batch->increment('remaining_quantity', $actualFill);
                    }
                }

                SaleReturnBatchAllocation::create([
                    'sale_return_id'           => $saleReturn->id,
                    'sale_return_item_id'      => $returnItem->id,
                    'sale_batch_allocation_id' => $alloc->id,
                    'stock_type'               => $alloc->stock_type,
                    'stock_id'                 => $alloc->stock_id,
                    'quantity'                 => $fillQty,
                ]);

                $remainingToRestore -= $fillQty;
            }
        } else {
            // Fallback for legacy sales created before batch allocations migration:
            // Safely restore into batches having remaining_quantity < quantity without exceeding capacity
            if ($sale && $sale->shop_id) {
                $batches = ShopStock::where('shop_id', $sale->shop_id)
                    ->where('item_id', $saleItem->item_id)
                    ->where('is_admin_stock', (bool) $saleItem->is_admin_stock)
                    ->whereColumn('remaining_quantity', '<', 'quantity')
                    ->orderBy('date_received', 'desc')
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingToRestore <= 0) break;
                    $capacity = max(0, (int)$batch->quantity - (int)$batch->remaining_quantity);
                    $fill = min($capacity, $remainingToRestore);
                    if ($fill > 0) {
                        $batch->increment('remaining_quantity', $fill);
                        $remainingToRestore -= $fill;
                    }
                }

                if ($remainingToRestore > 0) {
                    $latestBatch = ShopStock::where('shop_id', $sale->shop_id)
                        ->where('item_id', $saleItem->item_id)
                        ->where('is_admin_stock', (bool) $saleItem->is_admin_stock)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();
                    if ($latestBatch) {
                        $cap = max(0, (int)$latestBatch->quantity - (int)$latestBatch->remaining_quantity);
                        $fill = min($cap, $remainingToRestore);
                        if ($fill > 0) {
                            $latestBatch->increment('remaining_quantity', $fill);
                        }
                    }
                }
            } else {
                $batches = MainStock::where('item_id', $saleItem->item_id)
                    ->whereColumn('remaining_quantity', '<', 'stocked_quantity')
                    ->orderBy('date_received', 'desc')
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingToRestore <= 0) break;
                    $capacity = max(0, (int)$batch->stocked_quantity - (int)$batch->remaining_quantity);
                    $fill = min($capacity, $remainingToRestore);
                    if ($fill > 0) {
                        $batch->increment('remaining_quantity', $fill);
                        $remainingToRestore -= $fill;
                    }
                }

                if ($remainingToRestore > 0) {
                    $latestBatch = MainStock::where('item_id', $saleItem->item_id)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();
                    if ($latestBatch) {
                        $cap = max(0, (int)$latestBatch->stocked_quantity - (int)$latestBatch->remaining_quantity);
                        $fill = min($cap, $remainingToRestore);
                        if ($fill > 0) {
                            $latestBatch->increment('remaining_quantity', $fill);
                        }
                    }
                }
            }
        }

        StockLog::create([
            'item_id'          => $saleItem->item_id,
            'from_location'    => $customerName,
            'to_location'      => $locationName,
            'quantity'         => $qtyToReturn,
            'transaction_type' => 'STOCK_RECEIVED',
            'performed_by'     => $userId,
            'date'             => now()->toDateString(),
            'notes'            => "Customer Sale Return (Sale #{$sale->id})",
            'is_admin_stock'   => (bool) $saleItem->is_admin_stock,
        ]);
    }

    /**
     * Restore stock for a removed component strictly against original allocations.
     */
    public static function restoreComponentAllocations(
        Sale $sale,
        SaleItem $componentSaleItem,
        int $userId
    ): void {
        $allocations = SaleBatchAllocation::where('sale_item_id', $componentSaleItem->id)->get();
        $locationName = $sale->shop ? $sale->shop->shop_name : 'Main Store';

        if ($allocations->isNotEmpty()) {
            foreach ($allocations as $alloc) {
                if ($alloc->stock_type === 'shop') {
                    $batch = ShopStock::where('id', $alloc->stock_id)->lockForUpdate()->first();
                    if ($batch) {
                        $capacity = max(0, (int)$batch->quantity - (int)$batch->remaining_quantity);
                        $fill = min($alloc->quantity, $capacity > 0 ? $capacity : $alloc->quantity);
                        $batch->increment('remaining_quantity', $fill);
                    }
                } else {
                    $batch = MainStock::where('id', $alloc->stock_id)->lockForUpdate()->first();
                    if ($batch) {
                        $capacity = max(0, (int)$batch->stocked_quantity - (int)$batch->remaining_quantity);
                        $fill = min($alloc->quantity, $capacity > 0 ? $capacity : $alloc->quantity);
                        $batch->increment('remaining_quantity', $fill);
                    }
                }
            }
            SaleBatchAllocation::where('sale_item_id', $componentSaleItem->id)->delete();
        } else {
            // Legacy component without allocations
            $qty = (int) $componentSaleItem->quantity;
            if ($sale->shop_id) {
                $batches = ShopStock::where('shop_id', $sale->shop_id)
                    ->where('item_id', $componentSaleItem->item_id)
                    ->where('is_admin_stock', (bool) $componentSaleItem->is_admin_stock)
                    ->whereColumn('remaining_quantity', '<', 'quantity')
                    ->orderBy('date_received', 'desc')
                    ->lockForUpdate()
                    ->get();

                $rem = $qty;
                foreach ($batches as $batch) {
                    if ($rem <= 0) break;
                    $cap = max(0, (int)$batch->quantity - (int)$batch->remaining_quantity);
                    $fill = min($cap, $rem);
                    if ($fill > 0) {
                        $batch->increment('remaining_quantity', $fill);
                        $rem -= $fill;
                    }
                }
                if ($rem > 0) {
                    $batch = ShopStock::where('shop_id', $sale->shop_id)
                        ->where('item_id', $componentSaleItem->item_id)
                        ->where('is_admin_stock', (bool) $componentSaleItem->is_admin_stock)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();
                    if ($batch) {
                        $cap = max(0, (int)$batch->quantity - (int)$batch->remaining_quantity);
                        $fill = min($cap, $rem);
                        if ($fill > 0) {
                            $batch->increment('remaining_quantity', $fill);
                        }
                    }
                }
            } else {
                $batches = MainStock::where('item_id', $componentSaleItem->item_id)
                    ->whereColumn('remaining_quantity', '<', 'stocked_quantity')
                    ->orderBy('date_received', 'desc')
                    ->lockForUpdate()
                    ->get();

                $rem = $qty;
                foreach ($batches as $batch) {
                    if ($rem <= 0) break;
                    $cap = max(0, (int)$batch->stocked_quantity - (int)$batch->remaining_quantity);
                    $fill = min($cap, $rem);
                    if ($fill > 0) {
                        $batch->increment('remaining_quantity', $fill);
                        $rem -= $fill;
                    }
                }
                if ($rem > 0) {
                    $batch = MainStock::where('item_id', $componentSaleItem->item_id)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();
                    if ($batch) {
                        $cap = max(0, (int)$batch->stocked_quantity - (int)$batch->remaining_quantity);
                        $fill = min($cap, $rem);
                        if ($fill > 0) {
                            $batch->increment('remaining_quantity', $fill);
                        }
                    }
                }
            }
        }

        StockLog::create([
            'item_id'          => $componentSaleItem->item_id,
            'from_location'    => $sale->customer_name ?? 'Customer',
            'to_location'      => $locationName,
            'quantity'         => $componentSaleItem->quantity,
            'transaction_type' => 'ADJUSTMENT',
            'performed_by'     => $userId,
            'date'             => now()->toDateString(),
            'notes'            => "Component ({$componentSaleItem->display_name}) removed from Sale #{$sale->id}",
            'is_admin_stock'   => (bool) $componentSaleItem->is_admin_stock,
        ]);
    }
}
