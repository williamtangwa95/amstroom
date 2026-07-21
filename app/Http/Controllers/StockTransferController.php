<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    /**
     * List all stock transfers.
     */
    public function index()
    {
        $user = Auth::user();

        $query = StockTransfer::with('shop', 'approver', 'request')
            ->withCount(['items', 'pendingItems', 'receivedItems']);

        // Shop admin & seller only see transfers to their shop
        if ($user->isShopAdmin() || $user->isSeller()) {
            $query->where('to_shop', $user->shop_id);
        }

        $transfers = $query->latest()->paginate(15);

        return view('stock-transfers.index', compact('transfers'));
    }

    /**
     * Show transfer details.
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load('shop', 'approver', 'request', 'items.item.category', 'items.receiver');

        return view('stock-transfers.show', compact('stockTransfer'));
    }

    /**
     * Owner: Show form to directly assign stock to a shop.
     */
    public function create()
    {
        $shops = Shop::orderBy('shop_name')->get();

        // Get items that have remaining stock in main warehouse
        $items = Item::whereHas('mainStocks', function ($q) {
            $q->where('remaining_quantity', '>', 0);
        })->with('category')->orderBy('item_name')->get();

        // Get available quantities per item
        $availableStock = MainStock::select('item_id', DB::raw('SUM(remaining_quantity) as total_available'))
            ->where('remaining_quantity', '>', 0)
            ->groupBy('item_id')
            ->pluck('total_available', 'item_id');

        return view('stock-transfers.create', compact('shops', 'items', 'availableStock'));
    }

    /**
     * Owner: Store a direct stock assignment to shop.
     * Deducts from main warehouse (FIFO), creates transfer with pending_receipt status.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_id'       => 'required|exists:shops,id',
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|exists:items,id',
            'items.*.qty'   => 'required|integer|min:1',
        ]);

        $shopId = $request->shop_id;
        $shop   = Shop::findOrFail($shopId);

        // Validate stock availability for each item
        foreach ($request->items as $entry) {
            $available = MainStock::where('item_id', $entry['id'])
                ->sum('remaining_quantity');

            if ($available < $entry['qty']) {
                $item = Item::find($entry['id']);
                return back()->withInput()->withErrors([
                    'items' => "Insufficient stock for \"{$item->item_name}\". Available: {$available}, Requested: {$entry['qty']}.",
                ]);
            }
        }

        DB::transaction(function () use ($request, $shop, $shopId) {
            // Create the transfer record
            $transfer = StockTransfer::create([
                'from_store'    => 'Main Warehouse',
                'to_shop'       => $shopId,
                'approved_by'   => Auth::id(),
                'request_id'    => null,
                'transfer_date' => now()->toDateString(),
                'status'        => 'pending_receipt',
            ]);

            foreach ($request->items as $entry) {
                $itemId = $entry['id'];
                $qty    = $entry['qty'];

                // Get the latest main stock for pricing info
                $mainStock = MainStock::where('item_id', $itemId)
                    ->where('remaining_quantity', '>', 0)
                    ->orderByDesc('date_received')
                    ->first();

                // Deduct from main warehouse (FIFO)
                $remaining = $qty;
                $batches   = MainStock::where('item_id', $itemId)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('date_received')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->remaining_quantity, $remaining);
                    $batch->decrement('remaining_quantity', $deduct);
                    $remaining -= $deduct;
                }

                // Create transfer item with pending status
                StockTransferItem::create([
                    'transfer_id'   => $transfer->id,
                    'item_id'       => $itemId,
                    'quantity'      => $qty,
                    'buying_price'  => $mainStock?->buying_price ?? 0,
                    'selling_price' => $mainStock?->selling_price ?? 0,
                    'status'        => 'pending',
                ]);

                // Audit log: dispatched
                StockLog::create([
                    'item_id'          => $itemId,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => $shop->shop_name,
                    'quantity'         => $qty,
                    'transaction_type' => 'STOCK_TRANSFER',
                    'performed_by'     => Auth::id(),
                    'date'             => now()->toDateString(),
                    'notes'            => "Direct stock assignment to {$shop->shop_name} (Transfer #{$transfer->id})",
                ]);
            }
        });

        return redirect()->route('stock-transfers.index')
            ->with('success', "Stock dispatched to {$shop->shop_name} successfully. Awaiting shop admin receipt confirmation.");
    }

    /**
     * Shop Admin: Approve a single transfer item (mark as received).
     */
    public function approveItem(StockTransferItem $transferItem)
    {
        $user = Auth::user();
        $transfer = $transferItem->transfer()->with('shop')->first();

        // Authorization: must be shop_admin of the target shop
        if (!$user->isShopAdmin() || $user->shop_id !== $transfer->to_shop) {
            abort(403, 'You are not authorized to approve this item.');
        }

        if ($transferItem->status === 'received') {
            return back()->with('error', 'This item has already been received.');
        }

        DB::transaction(function () use ($transferItem, $transfer, $user) {
            // Mark item as received
            $transferItem->update([
                'status'      => 'received',
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            // Add to shop stock
            $this->addToShopStock(
                $transfer->to_shop,
                $transferItem->item_id,
                $transferItem->quantity,
                $transferItem->buying_price,
                $transferItem->selling_price
            );

            // Audit log
            StockLog::create([
                'item_id'          => $transferItem->item_id,
                'from_location'    => 'Main Warehouse',
                'to_location'      => $transfer->shop->shop_name,
                'quantity'         => $transferItem->quantity,
                'transaction_type' => 'STOCK_RECEIVED',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Item received & confirmed (Transfer #{$transfer->id})",
            ]);

            // Update transfer status
            $this->updateTransferStatus($transfer);
        });

        return back()->with('success', 'Item received and added to shop stock.');
    }

    /**
     * Shop Admin: Bulk approve multiple transfer items.
     */
    public function approveBulk(Request $request, StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        // Authorization
        if (!$user->isShopAdmin() || $user->shop_id !== $stockTransfer->to_shop) {
            abort(403, 'You are not authorized to approve these items.');
        }

        $request->validate([
            'item_ids'   => 'required|array|min:1',
            'item_ids.*' => 'required|integer|exists:stock_transfer_items,id',
        ]);

        $stockTransfer->load('shop');

        DB::transaction(function () use ($request, $stockTransfer, $user) {
            $items = StockTransferItem::whereIn('id', $request->item_ids)
                ->where('transfer_id', $stockTransfer->id)
                ->where('status', 'pending')
                ->get();

            foreach ($items as $transferItem) {
                $transferItem->update([
                    'status'      => 'received',
                    'received_by' => $user->id,
                    'received_at' => now(),
                ]);

                $this->addToShopStock(
                    $stockTransfer->to_shop,
                    $transferItem->item_id,
                    $transferItem->quantity,
                    $transferItem->buying_price,
                    $transferItem->selling_price
                );

                StockLog::create([
                    'item_id'          => $transferItem->item_id,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => $stockTransfer->shop->shop_name,
                    'quantity'         => $transferItem->quantity,
                    'transaction_type' => 'STOCK_RECEIVED',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => "Bulk received & confirmed (Transfer #{$stockTransfer->id})",
                ]);
            }

            $this->updateTransferStatus($stockTransfer);
        });

        return back()->with('success', count($request->item_ids) . ' item(s) received and added to shop stock.');
    }

    /**
     * Helper: add stock to shop stock.
     */
    private function addToShopStock(int $shopId, int $itemId, int $qty, $buyingPrice, $sellingPrice): void
    {
        $shopStock = ShopStock::firstOrNew([
            'shop_id' => $shopId,
            'item_id' => $itemId,
        ]);
        $shopStock->quantity           = ($shopStock->quantity ?? 0) + $qty;
        $shopStock->remaining_quantity = ($shopStock->remaining_quantity ?? 0) + $qty;
        $shopStock->buying_price       = $buyingPrice;
        $shopStock->selling_price      = $sellingPrice;
        $shopStock->date_received      = now()->toDateString();
        $shopStock->save();
    }

    /**
     * Helper: update transfer parent status based on item statuses.
     */
    private function updateTransferStatus(StockTransfer $transfer): void
    {
        $total    = $transfer->items()->count();
        $received = $transfer->items()->where('status', 'received')->count();

        if ($received === 0) {
            $transfer->update(['status' => 'pending_receipt']);
        } elseif ($received >= $total) {
            $transfer->update(['status' => 'received']);
        } else {
            $transfer->update(['status' => 'partially_received']);
        }
    }
}
