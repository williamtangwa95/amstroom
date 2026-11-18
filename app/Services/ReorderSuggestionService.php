<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockRequestItem;
use App\Models\StockTransferItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderSuggestionService
{
    /**
     * Get reorder suggestions for a specific shop based on sales velocity and finished/low stock.
     *
     * @param int $shopId
     * @param int $velocityDays Time window for sales rate calculation (default 30)
     * @param int $targetCoverDays Target days of stock cover (default 14)
     * @return Collection
     */
    public function getSuggestions(int $shopId, int $velocityDays = 30, int $targetCoverDays = 14): Collection
    {
        $velocityDays = max(1, $velocityDays);
        $targetCoverDays = max(1, $targetCoverDays);
        $sinceDate = now()->subDays($velocityDays)->toDateString();

        // 1. Current stock in this shop by item_id
        $shopStocks = ShopStock::where('shop_id', $shopId)
            ->where('is_admin_stock', false)
            ->select('item_id', DB::raw('SUM(remaining_quantity) as total_stock'), DB::raw('MAX(low_stock_alert) as alert_threshold'))
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        // 2. Sales in this shop during the velocity window
        $salesData = SaleItem::whereHas('sale', function ($q) use ($shopId, $sinceDate) {
            $q->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->where('sale_date', '>=', $sinceDate);
        })
        ->select('item_id', DB::raw('SUM(quantity) as total_sold'))
        ->groupBy('item_id')
        ->get()
        ->keyBy('item_id');

        // 3. Main warehouse stock availability by item_id
        $mainWarehouseStock = MainStock::select('item_id', DB::raw('SUM(remaining_quantity) as total_available'))
            ->where('remaining_quantity', '>', 0)
            ->groupBy('item_id')
            ->pluck('total_available', 'item_id');

        // 4. Pending stock requests in this shop
        $pendingRequests = StockRequestItem::whereHas('request', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId)->where('status', 'pending');
        })
        ->select('item_id', DB::raw('SUM(quantity) as pending_qty'))
        ->groupBy('item_id')
        ->pluck('pending_qty', 'item_id');

        // 5. Incoming transfers in transit (pending receipt) to this shop
        $incomingTransfers = StockTransferItem::whereHas('transfer', function ($q) use ($shopId) {
            $q->where('to_shop', $shopId)->whereIn('status', ['pending_receipt', 'partially_received']);
        })
        ->where('status', 'pending')
        ->select('item_id', DB::raw('SUM(quantity) as incoming_qty'))
        ->groupBy('item_id')
        ->pluck('incoming_qty', 'item_id');

        // Collect all relevant item IDs (stocked items or recently sold items)
        $relevantItemIds = $shopStocks->keys()
            ->merge($salesData->keys())
            ->unique()
            ->filter();

        if ($relevantItemIds->isEmpty()) {
            return collect();
        }

        // Load items with relationships
        $items = Item::with(['category', 'unit'])
            ->where('is_admin_item', false)
            ->whereIn('id', $relevantItemIds)
            ->get()
            ->keyBy('id');

        $suggestions = collect();

        foreach ($relevantItemIds as $itemId) {
            $item = $items->get($itemId);
            if (!$item) {
                continue;
            }

            $currentStock = (int) ($shopStocks->get($itemId)?->total_stock ?? 0);
            $alertThreshold = (int) ($shopStocks->get($itemId)?->alert_threshold ?? 5);
            $totalSold = (int) ($salesData->get($itemId)?->total_sold ?? 0);
            $dailySalesRate = round($totalSold / $velocityDays, 2);
            $mainStoreStock = (int) ($mainWarehouseStock->get($itemId) ?? 0);
            $pendingRequestQty = (int) ($pendingRequests->get($itemId) ?? 0);
            $incomingTransferQty = (int) ($incomingTransfers->get($itemId) ?? 0);

            // Compute days of cover
            $daysRemaining = null;
            if ($currentStock <= 0) {
                $daysRemaining = 0;
            } elseif ($dailySalesRate > 0) {
                $daysRemaining = round($currentStock / $dailySalesRate, 1);
            }

            // Determine urgency
            $urgency = 'healthy';
            if ($currentStock <= 0) {
                $urgency = 'out_of_stock';
            } elseif ($currentStock <= $alertThreshold || ($daysRemaining !== null && $daysRemaining <= 3)) {
                $urgency = 'critical';
            } elseif ($daysRemaining !== null && $daysRemaining <= 7) {
                $urgency = 'high_risk';
            } elseif ($daysRemaining !== null && $daysRemaining <= $targetCoverDays) {
                $urgency = 'suggested';
            }

            // Only suggest if needs reorder
            if ($urgency === 'healthy') {
                continue;
            }

            // Calculate suggested reorder quantity
            // Target stock needed for targetCoverDays + safety buffer
            if ($dailySalesRate > 0) {
                $targetStock = max(ceil($dailySalesRate * $targetCoverDays), $alertThreshold);
                $suggestedQty = (int) max(1, $targetStock - $currentStock);
            } else {
                // Out of stock with zero recent sales: restore safety threshold
                $suggestedQty = (int) max(1, $alertThreshold - $currentStock);
            }

            // Sort weight for ordering: out_of_stock (1) -> critical (2) -> high_risk (3) -> suggested (4)
            $urgencyWeight = match ($urgency) {
                'out_of_stock' => 1,
                'critical'     => 2,
                'high_risk'    => 3,
                'suggested'    => 4,
                default        => 5,
            };

            $suggestions->push((object) [
                'item_id'                => $item->id,
                'item_name'              => $item->item_name,
                'category_name'          => $item->category?->category_name ?? 'Uncategorized',
                'brand'                  => $item->brand,
                'model'                  => $item->model,
                'specification'          => $item->specification,
                'unit_symbol'            => $item->unit_symbol,
                'current_stock'          => $currentStock,
                'alert_threshold'        => $alertThreshold,
                'total_sold'             => $totalSold,
                'velocity_days'          => $velocityDays,
                'daily_sales_rate'       => $dailySalesRate,
                'days_remaining'         => $daysRemaining,
                'main_store_available'   => $mainStoreStock,
                'pending_request_qty'    => $pendingRequestQty,
                'incoming_transfer_qty'  => $incomingTransferQty,
                'suggested_reorder_qty'  => $suggestedQty,
                'urgency'                => $urgency,
                'urgency_weight'         => $urgencyWeight,
            ]);
        }

        // Sort by urgency first, then by total sales volume descending, then by item name
        return $suggestions->sort(function ($a, $b) {
            if ($a->urgency_weight !== $b->urgency_weight) {
                return $a->urgency_weight <=> $b->urgency_weight;
            }
            if ($a->total_sold !== $b->total_sold) {
                return $b->total_sold <=> $a->total_sold;
            }
            return strcmp($a->item_name, $b->item_name);
        })->values();
    }

    /**
     * Calculate summary KPI counts for a shop.
     *
     * @param int $shopId
     * @param int $velocityDays
     * @param int $targetCoverDays
     * @return array
     */
    public function getShopReorderKpis(int $shopId, int $velocityDays = 30, int $targetCoverDays = 14): array
    {
        $suggestions = $this->getSuggestions($shopId, $velocityDays, $targetCoverDays);

        $outOfStockCount = $suggestions->where('urgency', 'out_of_stock')->count();
        $criticalCount = $suggestions->where('urgency', 'critical')->count();
        $fastMoversAtRisk = $suggestions->whereIn('urgency', ['critical', 'high_risk'])
            ->where('daily_sales_rate', '>=', 0.5)
            ->count();
        $warehouseFulfillable = $suggestions->filter(function ($item) {
            return $item->main_store_available >= $item->suggested_reorder_qty;
        })->count();
        $warehouseOutStock = $suggestions->where('main_store_available', 0)->count();

        return [
            'total_suggested'       => $suggestions->count(),
            'out_of_stock'          => $outOfStockCount,
            'critical'              => $criticalCount,
            'fast_movers_at_risk'   => $fastMoversAtRisk,
            'warehouse_fulfillable' => $warehouseFulfillable,
            'warehouse_out_stock'   => $warehouseOutStock,
            'total_suggested_units' => $suggestions->sum('suggested_reorder_qty'),
        ];
    }
}
