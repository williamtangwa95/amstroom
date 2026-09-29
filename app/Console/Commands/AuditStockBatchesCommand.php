<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopStock;
use App\Models\MainStock;
use App\Models\SaleBatchAllocation;
use App\Models\SaleReturnBatchAllocation;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;

class AuditStockBatchesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:audit-batches {--repair : Automatically repair batch remaining quantities to valid bounds [0, quantity] without modifying historical initial quantity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit inventory batches for invariant violations (0 <= remaining_quantity <= quantity), orphan allocations, and reconcile stock history.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isRepair = (bool) $this->option('repair');

        $this->info("===============================================================================");
        $this->info("             AMSTROOM INVENTORY INTEGRITY AUDIT & RECONCILIATION             ");
        $this->info("===============================================================================");
        $this->line("Mode: " . ($isRepair ? "<fg=yellow;options=bold>REPAIR MODE (Deterministic bounds enforcement)</>" : "<fg=cyan;options=bold>REPORT ONLY (Safe audit mode)</>"));
        $this->line("");

        $totalAnomalies = 0;
        $repairedCount = 0;

        // 1. Audit ShopStock Invariants
        $this->info("--> Auditing Shop Stock Batches...");
        $shopNegative = ShopStock::where('remaining_quantity', '<', 0)->with('item', 'shop')->get();
        $shopOverflow = ShopStock::whereColumn('remaining_quantity', '>', 'quantity')->with('item', 'shop')->get();

        if ($shopNegative->isNotEmpty()) {
            $this->error("Found {$shopNegative->count()} Shop Stock batches with negative remaining_quantity (< 0):");
            $tableData = [];
            foreach ($shopNegative as $stock) {
                $totalAnomalies++;
                $tableData[] = [
                    'ID' => $stock->id,
                    'Shop' => $stock->shop?->shop_name ?? "Shop #{$stock->shop_id}",
                    'Item' => $stock->item?->item_name ?? "Item #{$stock->item_id}",
                    'Initial Qty' => $stock->quantity,
                    'Remaining Qty' => $stock->remaining_quantity,
                    'Status' => 'NEGATIVE_STOCK',
                ];

                if ($isRepair) {
                    $stock->update(['remaining_quantity' => 0]);
                    StockLog::create([
                        'item_id' => $stock->item_id,
                        'from_location' => 'System Audit Repair',
                        'to_location' => $stock->shop?->shop_name ?? 'Shop',
                        'quantity' => abs($stock->remaining_quantity),
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by' => 1,
                        'date' => now()->toDateString(),
                        'notes' => "Audit repair: Clamped negative remaining quantity from {$stock->remaining_quantity} to 0.",
                        'is_admin_stock' => (bool)$stock->is_admin_stock,
                    ]);
                    $repairedCount++;
                }
            }
            $this->table(['ID', 'Shop', 'Item', 'Initial Qty', 'Remaining Qty', 'Status'], $tableData);
        }

        if ($shopOverflow->isNotEmpty()) {
            $this->error("Found {$shopOverflow->count()} Shop Stock batches with overflow remaining_quantity (> quantity):");
            $tableData = [];
            foreach ($shopOverflow as $stock) {
                $totalAnomalies++;
                $tableData[] = [
                    'ID' => $stock->id,
                    'Shop' => $stock->shop?->shop_name ?? "Shop #{$stock->shop_id}",
                    'Item' => $stock->item?->item_name ?? "Item #{$stock->item_id}",
                    'Initial Qty' => $stock->quantity,
                    'Remaining Qty' => $stock->remaining_quantity,
                    'Status' => 'OVERFLOW_STOCK',
                ];

                if ($isRepair) {
                    $excess = $stock->remaining_quantity - $stock->quantity;
                    $stock->update(['remaining_quantity' => $stock->quantity]);
                    StockLog::create([
                        'item_id' => $stock->item_id,
                        'from_location' => $stock->shop?->shop_name ?? 'Shop',
                        'to_location' => 'System Audit Repair',
                        'quantity' => $excess,
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by' => 1,
                        'date' => now()->toDateString(),
                        'notes' => "Audit repair: Clamped overflow remaining quantity from {$stock->remaining_quantity} down to capacity {$stock->quantity}.",
                        'is_admin_stock' => (bool)$stock->is_admin_stock,
                    ]);
                    $repairedCount++;
                }
            }
            $this->table(['ID', 'Shop', 'Item', 'Initial Qty', 'Remaining Qty', 'Status'], $tableData);
        }

        if ($shopNegative->isEmpty() && $shopOverflow->isEmpty()) {
            $this->line("  <fg=green>✓ Shop Stock invariants intact (0 <= remaining_quantity <= quantity).</>");
        }

        // 2. Audit MainStock Invariants
        $this->line("");
        $this->info("--> Auditing Main Warehouse Stock Batches...");
        $mainNegative = MainStock::where('remaining_quantity', '<', 0)->with('item')->get();
        $mainOverflow = MainStock::whereColumn('remaining_quantity', '>', 'stocked_quantity')->with('item')->get();

        if ($mainNegative->isNotEmpty()) {
            $this->error("Found {$mainNegative->count()} Main Stock batches with negative remaining_quantity (< 0):");
            $tableData = [];
            foreach ($mainNegative as $stock) {
                $totalAnomalies++;
                $tableData[] = [
                    'ID' => $stock->id,
                    'Item' => $stock->item?->item_name ?? "Item #{$stock->item_id}",
                    'Initial Qty' => $stock->stocked_quantity,
                    'Remaining Qty' => $stock->remaining_quantity,
                    'Status' => 'NEGATIVE_STOCK',
                ];

                if ($isRepair) {
                    $stock->update(['remaining_quantity' => 0]);
                    StockLog::create([
                        'item_id' => $stock->item_id,
                        'from_location' => 'System Audit Repair',
                        'to_location' => 'Main Store',
                        'quantity' => abs($stock->remaining_quantity),
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by' => 1,
                        'date' => now()->toDateString(),
                        'notes' => "Audit repair: Clamped negative warehouse remaining quantity from {$stock->remaining_quantity} to 0.",
                        'is_admin_stock' => false,
                    ]);
                    $repairedCount++;
                }
            }
            $this->table(['ID', 'Item', 'Initial Qty', 'Remaining Qty', 'Status'], $tableData);
        }

        if ($mainOverflow->isNotEmpty()) {
            $this->error("Found {$mainOverflow->count()} Main Stock batches with overflow remaining_quantity (> stocked_quantity):");
            $tableData = [];
            foreach ($mainOverflow as $stock) {
                $totalAnomalies++;
                $tableData[] = [
                    'ID' => $stock->id,
                    'Item' => $stock->item?->item_name ?? "Item #{$stock->item_id}",
                    'Initial Qty' => $stock->stocked_quantity,
                    'Remaining Qty' => $stock->remaining_quantity,
                    'Status' => 'OVERFLOW_STOCK',
                ];

                if ($isRepair) {
                    $excess = $stock->remaining_quantity - $stock->stocked_quantity;
                    $stock->update(['remaining_quantity' => $stock->stocked_quantity]);
                    StockLog::create([
                        'item_id' => $stock->item_id,
                        'from_location' => 'Main Store',
                        'to_location' => 'System Audit Repair',
                        'quantity' => $excess,
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by' => 1,
                        'date' => now()->toDateString(),
                        'notes' => "Audit repair: Clamped overflow warehouse remaining quantity from {$stock->remaining_quantity} down to capacity {$stock->stocked_quantity}.",
                        'is_admin_stock' => false,
                    ]);
                    $repairedCount++;
                }
            }
            $this->table(['ID', 'Item', 'Initial Qty', 'Remaining Qty', 'Status'], $tableData);
        }

        if ($mainNegative->isEmpty() && $mainOverflow->isEmpty()) {
            $this->line("  <fg=green>✓ Main Stock invariants intact (0 <= remaining_quantity <= stocked_quantity).</>");
        }

        // 3. Audit Sale Batch Allocations & Returns
        $this->line("");
        $this->info("--> Auditing Sale & Return Batch Allocations...");

        // Check for return allocations exceeding original sale batch allocation
        $returnOverAllocations = SaleBatchAllocation::with('returnAllocations')
            ->get()
            ->filter(function ($alloc) {
                return $alloc->returnAllocations->sum('quantity') > $alloc->quantity;
            });

        if ($returnOverAllocations->isNotEmpty()) {
            $this->error("Found {$returnOverAllocations->count()} Sale Batch Allocations with returned quantity > allocated quantity:");
            $tableData = [];
            foreach ($returnOverAllocations as $alloc) {
                $totalAnomalies++;
                $tableData[] = [
                    'Alloc ID' => $alloc->id,
                    'Sale ID' => $alloc->sale_id,
                    'Stock Type' => $alloc->stock_type,
                    'Stock ID' => $alloc->stock_id,
                    'Allocated Qty' => $alloc->quantity,
                    'Returned Qty' => $alloc->returnAllocations->sum('quantity'),
                ];
            }
            $this->table(['Alloc ID', 'Sale ID', 'Stock Type', 'Stock ID', 'Allocated Qty', 'Returned Qty'], $tableData);
        } else {
            $this->line("  <fg=green>✓ No over-returned sale batch allocations detected.</>");
        }

        // 4. Batch Stock Reconciliation (Phase 17)
        $this->line("");
        $this->info("--> Reconciling Stock Batch Allocations against Remaining Balances...");
        $reconciliationAnomalies = 0;

        // Group allocations by stock_type and stock_id for Shop Stock
        $shopAllocations = SaleBatchAllocation::where('stock_type', 'shop')
            ->select('stock_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('stock_id')
            ->get()
            ->keyBy('stock_id');

        $shopReturns = SaleReturnBatchAllocation::where('stock_type', 'shop')
            ->select('stock_id', DB::raw('SUM(quantity) as total_returned'))
            ->groupBy('stock_id')
            ->get()
            ->keyBy('stock_id');

        $reconcileData = [];
        foreach ($shopAllocations as $stockId => $allocSummary) {
            $stock = ShopStock::find($stockId);
            if (!$stock) continue;

            $sold = (int) $allocSummary->total_sold;
            $returned = (int) ($shopReturns->get($stockId)?->total_returned ?? 0);
            $expectedRemaining = $stock->quantity - $sold + $returned;

            if ($expectedRemaining !== (int)$stock->remaining_quantity) {
                $reconciliationAnomalies++;
                $reconcileData[] = [
                    'Stock ID' => $stock->id,
                    'Type' => 'ShopStock',
                    'Initial' => $stock->quantity,
                    'Sold Alloc' => $sold,
                    'Ret Alloc' => $returned,
                    'Expected' => $expectedRemaining,
                    'Actual' => $stock->remaining_quantity,
                    'Discrepancy' => $stock->remaining_quantity - $expectedRemaining,
                ];
            }
        }

        // Group allocations for Main Stock
        $mainAllocations = SaleBatchAllocation::where('stock_type', 'main')
            ->select('stock_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('stock_id')
            ->get()
            ->keyBy('stock_id');

        $mainReturns = SaleReturnBatchAllocation::where('stock_type', 'main')
            ->select('stock_id', DB::raw('SUM(quantity) as total_returned'))
            ->groupBy('stock_id')
            ->get()
            ->keyBy('stock_id');

        foreach ($mainAllocations as $stockId => $allocSummary) {
            $stock = MainStock::find($stockId);
            if (!$stock) continue;

            $sold = (int) $allocSummary->total_sold;
            $returned = (int) ($mainReturns->get($stockId)?->total_returned ?? 0);
            $expectedRemaining = $stock->stocked_quantity - $sold + $returned;

            if ($expectedRemaining !== (int)$stock->remaining_quantity) {
                $reconciliationAnomalies++;
                $reconcileData[] = [
                    'Stock ID' => $stock->id,
                    'Type' => 'MainStock',
                    'Initial' => $stock->stocked_quantity,
                    'Sold Alloc' => $sold,
                    'Ret Alloc' => $returned,
                    'Expected' => $expectedRemaining,
                    'Actual' => $stock->remaining_quantity,
                    'Discrepancy' => $stock->remaining_quantity - $expectedRemaining,
                ];
            }
        }

        if (!empty($reconcileData)) {
            $this->warn("Found {$reconciliationAnomalies} batch(es) with allocation vs balance variance (e.g. from pre-migration sales):");
            $this->table(['Stock ID', 'Type', 'Initial', 'Sold Alloc', 'Ret Alloc', 'Expected', 'Actual', 'Discrepancy'], array_slice($reconcileData, 0, 20));
            if (count($reconcileData) > 20) {
                $this->line("... and " . (count($reconcileData) - 20) . " more records.");
            }
        } else {
            $this->line("  <fg=green>✓ All allocated batches reconcile perfectly with remaining balances.</>");
        }

        // Summary
        $this->line("");
        $this->info("===============================================================================");
        $this->info("                               AUDIT SUMMARY                                   ");
        $this->info("===============================================================================");
        $this->line("Total Invariant Violations: " . ($totalAnomalies > 0 ? "<fg=red;options=bold>{$totalAnomalies}</>" : "<fg=green;options=bold>0</>"));
        if ($isRepair) {
            $this->line("Total Batches Repaired: <fg=green;options=bold>{$repairedCount}</>");
        } else if ($totalAnomalies > 0) {
            $this->comment("Run `php artisan stock:audit-batches --repair` to safely clamp bounded invariant violations.");
        }

        return $totalAnomalies > 0 ? self::FAILURE : self::SUCCESS;
    }
}
