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
            ->withCount(['items', 'pendingItems', 'receivedItems', 'rejectedItems']);

        // Shop admin & seller only see transfers to their shop
        if ($user->isShopAdmin() || $user->isSeller()) {
            $query->where('to_shop', $user->shop_id);
        }

        $transfers = $query->latest()->get();

        return view('stock-transfers.index', compact('transfers'));
    }

    /**
     * Show transfer details.
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load('shop', 'approver', 'request', 'items.item.category', 'items.receiver', 'items.rejecter');

        $items = Item::with('category')->where('is_admin_item', false)->orderBy('item_name')->get();

        $availableStock = MainStock::select('item_id', DB::raw('SUM(remaining_quantity) as total_available'))
            ->where('remaining_quantity', '>', 0)
            ->groupBy('item_id')
            ->pluck('total_available', 'item_id');

        return view('stock-transfers.show', compact('stockTransfer', 'items', 'availableStock'));
    }

    /**
     * Owner: Show form to directly assign stock to a shop.
     */
    public function create()
    {
        $shops = Shop::orderBy('shop_name')->get();

        // Get items that have remaining stock in main warehouse
        $items = Item::where('is_admin_item', false)->whereHas('mainStocks', function ($q) {
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
                $this->deductMainStock($itemId, $qty);

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

            // Notify all shop admins of the target shop
            $shopAdmins = \App\Models\User::where('shop_id', $shopId)
                ->where('role', 'shop_admin')
                ->get();
            foreach ($shopAdmins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title'   => 'New Stock Transfer Dispatched',
                    'message' => "Owner has dispatched a new stock transfer #{$transfer->id} to your shop ({$shop->shop_name}). Please review and confirm receipt.",
                ]);
            }
        });

        return redirect()->route('stock-transfers.index')
            ->with('success', "Stock dispatched to {$shop->shop_name} successfully. Awaiting shop admin receipt confirmation.");
    }

    /**
     * Shop Admin / Admin: Approve a single transfer item (mark as received).
     */
    public function approveItem(Request $request, StockTransferItem $transferItem)
    {
        $user = Auth::user();
        $transfer = $transferItem->transfer;
        if ($transfer) {
            $transfer->load('shop');
        }

        // Authorization: must be shop_admin of target shop or owner
        if ((!$user->isShopAdmin() || $user->shop_id !== ($transfer?->to_shop ?? null)) && !$user->isOwner()) {
            abort(403, 'You are not authorized to approve this item.');
        }

        if ($transferItem->status === 'received') {
            return back()->with('error', 'This item has already been received.');
        }

        $request->validate([
            'selling_price' => 'required|numeric|min:' . $transferItem->selling_price,
        ], [
            'selling_price.min' => 'The selling price must be greater than or equal to the buying price (TZS ' . number_format($transferItem->selling_price) . ').',
        ]);

        DB::transaction(function () use ($transferItem, $transfer, $user, $request) {
            // Mark item as received
            $transferItem->update([
                'status'           => 'received',
                'received_by'      => $user->id,
                'received_at'      => now(),
                'rejection_reason' => null,
            ]);

            // Add to shop stock:
            // buying_price for admin = owner's selling_price
            // selling_price for admin = custom selling_price assigned by admin
            $this->addToShopStock(
                $transfer->to_shop,
                $transferItem->item_id,
                $transferItem->quantity,
                $transferItem->selling_price,
                $request->selling_price
            );

            // Audit log
            StockLog::create([
                'item_id'          => $transferItem->item_id,
                'from_location'    => 'Main Warehouse',
                'to_location'      => $transfer->shop?->shop_name ?? 'Shop',
                'quantity'         => $transferItem->quantity,
                'transaction_type' => 'STOCK_RECEIVED',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Item received & confirmed (Transfer #{$transfer->id})",
            ]);

            // Notify all sellers of this shop
            $sellers = \App\Models\User::where('shop_id', $transfer->to_shop)
                ->where('role', 'seller')
                ->get();
            $itemName = $transferItem->item?->item_name ?? 'Item';
            foreach ($sellers as $seller) {
                \App\Models\Notification::create([
                    'user_id' => $seller->id,
                    'title'   => 'New Stock Added to Shop Stock',
                    'message' => "Shop Admin has approved and received {$transferItem->quantity} units of \"{$itemName}\" into the shop stock.",
                ]);
            }

            // Update transfer status
            $this->updateTransferStatus($transfer);
        });

        return back()->with('success', 'Item received and added to shop stock.');
    }

    /**
     * Shop Admin / Admin: Bulk approve multiple transfer items.
     */
    public function approveBulk(Request $request, StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        // Authorization
        if ((!$user->isShopAdmin() || $user->shop_id !== $stockTransfer->to_shop) && !$user->isOwner()) {
            abort(403, 'You are not authorized to approve these items.');
        }

        $request->validate([
            'item_ids'   => 'required|array|min:1',
            'item_ids.*' => 'required|integer|exists:stock_transfer_items,id',
            'selling_prices' => 'required|array',
            'selling_prices.*' => 'required|numeric|min:0',
        ]);

        $items = StockTransferItem::whereIn('id', $request->item_ids)
            ->where('transfer_id', $stockTransfer->id)
            ->where('status', 'pending')
            ->get();

        $errors = [];
        foreach ($items as $transferItem) {
            $sellingPrice = $request->selling_prices[$transferItem->id] ?? null;
            $cleanPrice = $sellingPrice !== null ? floatval(str_replace(',', '', $sellingPrice)) : null;

            if ($cleanPrice === null || $cleanPrice < $transferItem->selling_price) {
                $itemName = $transferItem->item?->item_name ?? 'Item';
                $errors["selling_prices.{$transferItem->id}"] = "The selling price for \"{$itemName}\" must be greater than or equal to the buying price (TZS " . number_format($transferItem->selling_price) . ").";
            }
        }

        if (!empty($errors)) {
            return back()->withInput()->withErrors($errors);
        }

        $stockTransfer->load('shop');

        DB::transaction(function () use ($request, $stockTransfer, $user) {
            $items = StockTransferItem::whereIn('id', $request->item_ids)
                ->where('transfer_id', $stockTransfer->id)
                ->where('status', 'pending')
                ->get();

            foreach ($items as $transferItem) {
                $sellingPrice = $request->selling_prices[$transferItem->id] ?? $transferItem->selling_price;

                $transferItem->update([
                    'status'           => 'received',
                    'received_by'      => $user->id,
                    'received_at'      => now(),
                    'rejection_reason' => null,
                ]);

                // Add to shop stock:
                // buying_price for admin = owner's selling_price
                // selling_price for admin = custom selling_price assigned by admin in bulk form
                $this->addToShopStock(
                    $stockTransfer->to_shop,
                    $transferItem->item_id,
                    $transferItem->quantity,
                    $transferItem->selling_price,
                    $sellingPrice
                );

                StockLog::create([
                    'item_id'          => $transferItem->item_id,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => $stockTransfer->shop?->shop_name ?? 'Shop',
                    'quantity'         => $transferItem->quantity,
                    'transaction_type' => 'STOCK_RECEIVED',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => "Bulk received & confirmed (Transfer #{$stockTransfer->id})",
                ]);
            }

            // Notify all sellers of this shop
            $sellers = \App\Models\User::where('shop_id', $stockTransfer->to_shop)
                ->where('role', 'seller')
                ->get();
            foreach ($sellers as $seller) {
                \App\Models\Notification::create([
                    'user_id' => $seller->id,
                    'title'   => 'New Stock Added (Bulk Approval)',
                    'message' => "Shop Admin has bulk-approved and received stock items (Transfer #{$stockTransfer->id}) into the shop stock.",
                ]);
            }

            $this->updateTransferStatus($stockTransfer);
        });

        return back()->with('success', count($request->item_ids) . ' item(s) received and added to shop stock.');
    }

    /**
     * Admin / Shop Admin: Reject a transfer item with a reason.
     */
    public function rejectItem(Request $request, StockTransferItem $transferItem)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }
        $transfer = $transferItem->transfer;
        if ($transfer) {
            $transfer->load('shop');
        }

        if ((!$user->isShopAdmin() || $user->shop_id !== ($transfer?->to_shop ?? null)) && !$user->isOwner()) {
            abort(403, 'You are not authorized to reject this item.');
        }

        if ($transferItem->status === 'received') {
            return back()->with('error', 'Received items cannot be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($transferItem, $transfer, $request, $user) {
            $transferItem->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'rejected_by'      => $user->id,
                'rejected_at'      => now(),
            ]);

            StockLog::create([
                'item_id'          => $transferItem->item_id,
                'from_location'    => 'Main Warehouse',
                'to_location'      => $transfer->shop?->shop_name ?? 'Shop',
                'quantity'         => $transferItem->quantity,
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Item rejected: {$request->rejection_reason} (Transfer #{$transfer->id})",
            ]);

            $this->updateTransferStatus($transfer);
        });

        return back()->with('success', 'Item rejected with reason. The owner has been notified to modify it.');
    }

    /**
     * Owner: Update an existing pending or rejected transfer item.
     */
    public function updateItem(Request $request, StockTransferItem $transferItem)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Only the owner can update transfer items.');
        }

        if ($transferItem->status === 'received') {
            return back()->with('error', 'Received items cannot be edited.');
        }

        $request->validate([
            'item_id'  => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $transfer = $transferItem->transfer;
        if ($transfer) {
            $transfer->load('shop');
        }
        $oldItemId = (int)$transferItem->item_id;
        $oldQty    = (int)$transferItem->quantity;
        $newItemId = (int)$request->item_id;
        $newQty    = (int)$request->quantity;

        // Check stock availability if increasing or changing item
        if ($newItemId === $oldItemId) {
            $delta = $newQty - $oldQty;
            if ($delta > 0) {
                $available = MainStock::where('item_id', $newItemId)->sum('remaining_quantity');
                if ($available < $delta) {
                    $item = Item::find($newItemId);
                    return back()->with('error', "Insufficient main stock for \"{$item->item_name}\". Available: {$available}, Additional required: {$delta}.");
                }
            }
        } else {
            $available = MainStock::where('item_id', $newItemId)->sum('remaining_quantity');
            if ($available < $newQty) {
                $item = Item::find($newItemId);
                return back()->with('error', "Insufficient main stock for \"{$item->item_name}\". Available: {$available}, Required: {$newQty}.");
            }
        }

        DB::transaction(function () use ($transferItem, $transfer, $oldItemId, $oldQty, $newItemId, $newQty, $user) {
            if ($newItemId === $oldItemId) {
                $delta = $newQty - $oldQty;
                if ($delta > 0) {
                    $this->deductMainStock($newItemId, $delta);
                } elseif ($delta < 0) {
                    $this->restoreMainStock($newItemId, abs($delta));
                }
            } else {
                $this->restoreMainStock($oldItemId, $oldQty);
                $this->deductMainStock($newItemId, $newQty);
            }

            // Get pricing for new item
            $mainStock = MainStock::where('item_id', $newItemId)
                ->where('remaining_quantity', '>', 0)
                ->orderByDesc('date_received')
                ->first() ?? MainStock::where('item_id', $newItemId)->orderByDesc('date_received')->first();

            $transferItem->update([
                'item_id'          => $newItemId,
                'quantity'         => $newQty,
                'buying_price'     => $mainStock?->buying_price ?? $transferItem->buying_price,
                'selling_price'    => $mainStock?->selling_price ?? $transferItem->selling_price,
                'status'           => 'pending',
                'rejection_reason' => null,
                'rejected_by'      => null,
                'rejected_at'      => null,
            ]);

            StockLog::create([
                'item_id'          => $newItemId,
                'from_location'    => 'Main Warehouse',
                'to_location'      => $transfer->shop?->shop_name ?? 'Shop',
                'quantity'         => $newQty,
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Transfer item #{$transferItem->id} modified by owner (Transfer #{$transfer->id})",
            ]);

            $this->updateTransferStatus($transfer);
        });

        return back()->with('success', 'Transfer item updated successfully.');
    }

    /**
     * Owner: Delete a pending or rejected transfer item and restore stock to main warehouse.
     */
    public function deleteItem(StockTransferItem $transferItem)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Only the owner can delete transfer items.');
        }

        if ($transferItem->status === 'received') {
            return back()->with('error', 'Received items cannot be deleted.');
        }

        $transfer = $transferItem->transfer;
        if ($transfer) {
            $transfer->load('shop');
        }

        DB::transaction(function () use ($transferItem, $transfer, $user) {
            // Restore stock to main warehouse
            $this->restoreMainStock($transferItem->item_id, $transferItem->quantity);

            StockLog::create([
                'item_id'          => $transferItem->item_id,
                'from_location'    => 'Main Warehouse',
                'to_location'      => $transfer->shop?->shop_name ?? 'Shop',
                'quantity'         => $transferItem->quantity,
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Transfer item removed & stock returned to Main Warehouse (Transfer #{$transfer->id})",
            ]);

            $transferItem->delete();

            $this->updateTransferStatus($transfer);
        });

        return back()->with('success', 'Item removed from transfer and stock returned to Main Warehouse.');
    }

    /**
     * Owner: Add another item to an existing stock transfer.
     */
    public function addItem(Request $request, StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Only the owner can add items to a transfer.');
        }

        $request->validate([
            'item_id'  => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $itemId = (int)$request->item_id;
        $qty    = (int)$request->quantity;

        $available = MainStock::where('item_id', $itemId)->sum('remaining_quantity');
        if ($available < $qty) {
            $item = Item::find($itemId);
            return back()->with('error', "Insufficient main stock for \"{$item->item_name}\". Available: {$available}, Requested: {$qty}.");
        }

        $stockTransfer->load('shop');

        DB::transaction(function () use ($stockTransfer, $itemId, $qty, $user) {
            // Deduct from main warehouse FIFO
            $this->deductMainStock($itemId, $qty);

            $mainStock = MainStock::where('item_id', $itemId)
                ->where('remaining_quantity', '>', 0)
                ->orderByDesc('date_received')
                ->first() ?? MainStock::where('item_id', $itemId)->orderByDesc('date_received')->first();

            StockTransferItem::create([
                'transfer_id'   => $stockTransfer->id,
                'item_id'       => $itemId,
                'quantity'      => $qty,
                'buying_price'  => $mainStock?->buying_price ?? 0,
                'selling_price' => $mainStock?->selling_price ?? 0,
                'status'        => 'pending',
            ]);

            StockLog::create([
                'item_id'          => $itemId,
                'from_location'    => 'Main Warehouse',
                'to_location'      => $stockTransfer->shop?->shop_name ?? 'Shop',
                'quantity'         => $qty,
                'transaction_type' => 'STOCK_TRANSFER',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "New item added to transfer by owner (Transfer #{$stockTransfer->id})",
            ]);

            $this->updateTransferStatus($stockTransfer);
        });

        return back()->with('success', 'Item added to transfer successfully.');
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
        $rejected = $transfer->items()->where('status', 'rejected')->count();

        if ($total === 0) {
            $transfer->update(['status' => 'pending_receipt']);
        } elseif ($received >= $total) {
            $transfer->update(['status' => 'received']);
        } elseif ($rejected > 0) {
            $transfer->update(['status' => 'rejected']);
        } elseif ($received > 0) {
            $transfer->update(['status' => 'partially_received']);
        } else {
            $transfer->update(['status' => 'pending_receipt']);
        }
    }

    /**
     * Helper: restore stock to main warehouse (LIFO back to available batches).
     */
    private function restoreMainStock(int $itemId, int $qty): void
    {
        $remainingToRestore = $qty;

        $batches = MainStock::where('item_id', $itemId)
            ->whereRaw('remaining_quantity < stocked_quantity')
            ->orderByDesc('date_received')
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToRestore <= 0) break;
            $room = $batch->stocked_quantity - $batch->remaining_quantity;
            $add  = min($room, $remainingToRestore);
            $batch->increment('remaining_quantity', $add);
            $remainingToRestore -= $add;
        }

        if ($remainingToRestore > 0) {
            $latestBatch = MainStock::where('item_id', $itemId)
                ->orderByDesc('date_received')
                ->first();
            if ($latestBatch) {
                $latestBatch->increment('remaining_quantity', $remainingToRestore);
            }
        }
    }

    /**
     * Helper: deduct stock from main warehouse (FIFO).
     */
    private function deductMainStock(int $itemId, int $qty): void
    {
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
    }
}
