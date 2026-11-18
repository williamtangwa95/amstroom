<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\StockLog;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\ReorderSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReorderController extends Controller
{
    public function __construct(
        protected ReorderSuggestionService $suggestionService
    ) {}

    /**
     * Display the reorder suggestions dashboard and multi-select order view.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $shops = Shop::where('status', 'active')->orderBy('shop_name')->get();
            $defaultShopId = $shops->first()?->id;
            $shopId = (int) $request->get('shop_id', $defaultShopId);
            $currentShop = $shops->firstWhere('id', $shopId) ?? $shops->first();
        } else {
            $shops = collect([$user->shop]);
            $shopId = (int) $user->shop_id;
            $currentShop = $user->shop;
        }

        if (!$currentShop) {
            return redirect()->route('dashboard')->with('error', 'No active shop found.');
        }

        $velocityDays = in_array((int) $request->get('velocity_days'), [14, 30, 60, 90], true)
            ? (int) $request->get('velocity_days')
            : 30;

        $targetCoverDays = in_array((int) $request->get('target_cover_days'), [7, 14, 21, 30], true)
            ? (int) $request->get('target_cover_days')
            : 14;

        $suggestions = $this->suggestionService->getSuggestions($shopId, $velocityDays, $targetCoverDays);
        $kpis = $this->suggestionService->getShopReorderKpis($shopId, $velocityDays, $targetCoverDays);

        // Fetch all non-admin catalog items for "+ Add Extra Item" selection
        $catalogItems = Item::with(['category', 'unit'])
            ->where('is_admin_item', false)
            ->orderBy('item_name')
            ->get();

        // Warehouse stock mapped by item_id
        $warehouseStockMap = MainStock::select('item_id', DB::raw('SUM(remaining_quantity) as total_qty'))
            ->where('remaining_quantity', '>', 0)
            ->groupBy('item_id')
            ->pluck('total_qty', 'item_id');

        return view('reorder.index', compact(
            'shops',
            'currentShop',
            'shopId',
            'velocityDays',
            'targetCoverDays',
            'suggestions',
            'kpis',
            'catalogItems',
            'warehouseStockMap'
        ));
    }

    /**
     * Submit a consolidated stock request from Shop Admin (or Owner) to the Main Store.
     */
    public function storeRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'shop_id'          => 'required|exists:shops,id',
            'notes'            => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $shopId = $user->isOwner() ? (int) $request->shop_id : (int) $user->shop_id;
        $shop = Shop::findOrFail($shopId);

        $stockRequest = DB::transaction(function () use ($request, $shopId, $user) {
            $stockRequest = StockRequest::create([
                'shop_id'      => $shopId,
                'requested_by' => $user->id,
                'request_date' => now()->toDateString(),
                'status'       => 'pending',
                'notes'        => $request->notes ?: 'Generated from Reorder Suggestions',
            ]);

            foreach ($request->items as $itemData) {
                StockRequestItem::create([
                    'request_id' => $stockRequest->id,
                    'item_id'    => $itemData['item_id'],
                    'quantity'   => (int) $itemData['quantity'],
                ]);
            }

            return $stockRequest;
        });

        $itemCount = count($request->items);
        $totalUnits = collect($request->items)->sum('quantity');

        // Notify sellers of this shop
        $sellers = User::where('shop_id', $shopId)->where('role', 'seller')->get();
        foreach ($sellers as $seller) {
            Notification::create([
                'user_id' => $seller->id,
                'title'   => 'New Stock Request Submitted',
                'message' => "Stock request #{$stockRequest->id} for {$itemCount} items ({$totalUnits} units) has been submitted to the main warehouse.",
            ]);
        }

        // Notify owners and system admins
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'New Reorder Request from ' . $shop->shop_name,
                'message' => "Shop '{$shop->shop_name}' submitted stock request #{$stockRequest->id} with {$itemCount} suggested replenishment items.",
            ]);
        }

        return redirect()->route('reorder.index', ['shop_id' => $shopId])
            ->with('success', "Stock request #{$stockRequest->id} for {$itemCount} items ({$totalUnits} units) submitted successfully to Main Store!");
    }

    /**
     * Owner/System Admin: Direct transfer and dispatch from Main Store to the shop based on suggestions.
     */
    public function storeDirectTransfer(Request $request)
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            abort(403, 'Only the owner or system admin can initiate direct transfers from the Main Store.');
        }

        $request->validate([
            'shop_id'          => 'required|exists:shops,id',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $shopId = (int) $request->shop_id;
        $shop = Shop::findOrFail($shopId);

        // 1. Verify warehouse availability for all items
        foreach ($request->items as $itemData) {
            $itemId = (int) $itemData['item_id'];
            $reqQty = (int) $itemData['quantity'];
            $available = (int) MainStock::where('item_id', $itemId)->sum('remaining_quantity');

            if ($available < $reqQty) {
                $item = Item::find($itemId);
                $itemName = $item ? $item->item_name : "Item #{$itemId}";
                return back()->withInput()->withErrors([
                    'transfer' => "Insufficient Main Warehouse stock for '{$itemName}'. Available: {$available}, Requested to Transfer: {$reqQty}.",
                ]);
            }
        }

        // 2. Perform direct transfer with FIFO deduction
        $transfer = DB::transaction(function () use ($request, $shop, $shopId, $user) {
            $transfer = StockTransfer::create([
                'from_store'    => 'Main Warehouse',
                'to_shop'       => $shopId,
                'approved_by'   => $user->id,
                'transfer_date' => now()->toDateString(),
                'status'        => 'pending_receipt',
            ]);

            foreach ($request->items as $itemData) {
                $itemId = (int) $itemData['item_id'];
                $qty = (int) $itemData['quantity'];

                $latestBatch = MainStock::where('item_id', $itemId)
                    ->where('remaining_quantity', '>', 0)
                    ->orderByDesc('date_received')
                    ->first();

                // Deduct from MainStock batches using FIFO
                $remaining = $qty;
                $batches = MainStock::where('item_id', $itemId)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('date_received')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->remaining_quantity, $remaining);
                    $batch->decrement('remaining_quantity', $deduct);
                    $remaining -= $deduct;
                }

                StockTransferItem::create([
                    'transfer_id'   => $transfer->id,
                    'item_id'       => $itemId,
                    'quantity'      => $qty,
                    'buying_price'  => $latestBatch?->buying_price ?? 0,
                    'selling_price' => $latestBatch?->selling_price ?? 0,
                    'status'        => 'pending',
                ]);

                StockLog::create([
                    'item_id'          => $itemId,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => $shop->shop_name,
                    'quantity'         => $qty,
                    'transaction_type' => 'STOCK_TRANSFER',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => "Direct transfer based on Reorder Suggestion #{$transfer->id}",
                ]);
            }

            return $transfer;
        });

        // Notify Shop Admins of the destination shop
        $shopAdmins = User::where('shop_id', $shopId)->where('role', 'shop_admin')->get();
        foreach ($shopAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'New Stock Transfer Dispatched (Pending Receipt)',
                'message' => "Owner dispatched stock transfer #{$transfer->id} to {$shop->shop_name} based on reorder suggestions. Please confirm receipt upon arrival.",
            ]);
        }

        return redirect()->route('stock-transfers.show', $transfer)
            ->with('success', "Stock transfer #{$transfer->id} successfully dispatched to {$shop->shop_name}! Awaiting shop receipt confirmation.");
    }
}
