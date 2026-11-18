<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\ShopProductSwap;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopProductSwapController extends Controller
{
    /**
     * Display listing of product swaps with tabs and filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = ShopProductSwap::with([
            'sourceShop',
            'destinationShop',
            'sourceItem.unit',
            'destinationItem.unit',
            'initiator',
            'destinationApprover',
            'ownerApprover',
        ]);

        // Branch scoping
        if (!$user->isOwner()) {
            $shopId = (int) $user->shop_id;
            $query->where(function ($q) use ($shopId) {
                $q->where('source_shop_id', $shopId)
                  ->orWhere('destination_shop_id', $shopId);
            });
        } elseif ($request->filled('shop_id')) {
            $filterShopId = (int) $request->shop_id;
            $query->where(function ($q) use ($filterShopId) {
                $q->where('source_shop_id', $filterShopId)
                  ->orWhere('destination_shop_id', $filterShopId);
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('swap_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('sourceItem', fn($sq) => $sq->where('item_name', 'like', "%{$search}%"))
                  ->orWhereHas('destinationItem', fn($sq) => $sq->where('item_name', 'like', "%{$search}%"))
                  ->orWhereHas('sourceShop', fn($sq) => $sq->where('shop_name', 'like', "%{$search}%"))
                  ->orWhereHas('destinationShop', fn($sq) => $sq->where('shop_name', 'like', "%{$search}%"));
            });
        }

        // Active tab filter
        $tab = $request->get('tab', 'all');
        $validTabs = ['all', 'action_required', 'pending_destination', 'pending_owner', 'confirmed', 'history'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'all';
        }

        $baseCountQuery = clone $query;

        // Counts for tabs
        $counts = [
            'all'                 => (clone $baseCountQuery)->count(),
            'pending_destination' => (clone $baseCountQuery)->where('status', 'pending_destination')->count(),
            'pending_owner'       => (clone $baseCountQuery)->where('status', 'pending_owner')->count(),
            'confirmed'           => (clone $baseCountQuery)->where('status', 'confirmed')->count(),
            'history'             => (clone $baseCountQuery)->whereIn('status', ['cancelled', 'reverted'])->count(),
        ];

        // Specific tab filtering
        if ($tab === 'pending_destination') {
            $query->where('status', 'pending_destination');
        } elseif ($tab === 'pending_owner') {
            $query->where('status', 'pending_owner');
        } elseif ($tab === 'confirmed') {
            $query->where('status', 'confirmed');
        } elseif ($tab === 'history') {
            $query->whereIn('status', ['cancelled', 'reverted']);
        } elseif ($tab === 'action_required') {
            if ($user->isOwner()) {
                $query->where('status', 'pending_owner');
            } else {
                $query->where('status', 'pending_destination')
                      ->where('destination_shop_id', $user->shop_id);
            }
        }

        $swaps = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $shops = $user->isOwner() ? Shop::where('status', 'active')->orderBy('shop_name')->get() : collect();

        return view('shop-swaps.index', compact('swaps', 'counts', 'tab', 'shops'));
    }

    /**
     * Show form to initiate a product swap proposal.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $shops = Shop::where('status', 'active')->orderBy('shop_name')->get();
            $sourceShopId = (int) $request->get('source_shop_id', $shops->first()?->id);
            $sourceShop = $shops->firstWhere('id', $sourceShopId) ?? $shops->first();
        } else {
            $shops = collect([$user->shop]);
            $sourceShopId = (int) $user->shop_id;
            $sourceShop = $user->shop;
        }

        if (!$sourceShop) {
            return redirect()->route('shop-swaps.index')->with('error', 'No active shop available to initiate a swap.');
        }

        // Destination shops are other active shops excluding source shop
        $destinationShops = Shop::where('status', 'active')
            ->where('id', '!=', $sourceShopId)
            ->orderBy('shop_name')
            ->get();

        // Source shop available items
        $sourceStockItems = ShopStock::where('shop_id', $sourceShopId)
            ->where('is_admin_stock', false)
            ->where('remaining_quantity', '>', 0)
            ->with(['item.category', 'item.unit'])
            ->get()
            ->sortBy('item.item_name');

        $preselectedItemId = (int) $request->get('item_id');

        return view('shop-swaps.create', compact(
            'shops',
            'sourceShop',
            'destinationShops',
            'sourceStockItems',
            'preselectedItemId'
        ));
    }

    /**
     * AJAX Endpoint: Fetch available items from destination shop filtered by category or exact product.
     */
    public function getDestinationItems(Request $request)
    {
        $request->validate([
            'destination_shop_id' => 'required|exists:shops,id',
            'source_item_id'      => 'nullable|exists:items,id',
            'filter_type'         => 'nullable|in:same_category,same_product,all',
        ]);

        $destShopId = (int) $request->destination_shop_id;
        $sourceItemId = $request->source_item_id ? (int) $request->source_item_id : null;
        $filterType = $request->filter_type ?? 'same_category';

        $sourceItem = $sourceItemId ? Item::find($sourceItemId) : null;

        $query = ShopStock::where('shop_id', $destShopId)
            ->where('is_admin_stock', false)
            ->where('remaining_quantity', '>', 0)
            ->with(['item.category', 'item.unit']);

        if ($sourceItem && $filterType === 'same_product') {
            $query->where('item_id', $sourceItem->id);
        } elseif ($sourceItem && $filterType === 'same_category' && $sourceItem->category_id) {
            $query->whereHas('item', function ($q) use ($sourceItem) {
                $q->where('category_id', $sourceItem->category_id);
            });
        }

        $stocks = $query->get()->map(function ($st) {
            return [
                'item_id'            => $st->item_id,
                'item_name'          => $st->item->item_name,
                'category_name'      => $st->item->category?->category_name ?? 'General',
                'brand'              => $st->item->brand ?: '—',
                'model'              => $st->item->model ?: '',
                'specification'      => $st->item->specification ?: '',
                'unit_symbol'        => $st->item->unit_symbol,
                'remaining_quantity' => (int) $st->remaining_quantity,
                'selling_price'      => (float) $st->selling_price,
            ];
        })->sortBy('item_name')->values();

        return response()->json([
            'success'     => true,
            'filter_type' => $filterType,
            'count'       => $stocks->count(),
            'items'       => $stocks,
        ]);
    }

    /**
     * Store and dispatch a new product swap proposal.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'source_shop_id'       => 'required|exists:shops,id',
            'destination_shop_id'  => 'required|exists:shops,id|different:source_shop_id',
            'source_item_id'       => 'required|exists:items,id',
            'source_quantity'      => 'required|integer|min:1',
            'destination_item_id'  => 'required|exists:items,id',
            'destination_quantity' => 'required|integer|min:1',
            'reason_type'          => 'required|string|max:50',
            'notes'                => 'nullable|string|max:500',
        ]);

        $sourceShopId = $user->isOwner() ? (int) $request->source_shop_id : (int) $user->shop_id;
        $destShopId = (int) $request->destination_shop_id;

        // 1. Verify source shop stock availability
        $sourceStock = ShopStock::where('shop_id', $sourceShopId)
            ->where('item_id', $request->source_item_id)
            ->first();

        if (!$sourceStock || $sourceStock->remaining_quantity < $request->source_quantity) {
            return back()->withInput()->withErrors([
                'source_item_id' => 'Insufficient stock in your shop for the selected product. Available: ' . ($sourceStock?->remaining_quantity ?? 0),
            ]);
        }

        // 2. Verify destination shop stock availability
        $destStock = ShopStock::where('shop_id', $destShopId)
            ->where('item_id', $request->destination_item_id)
            ->first();

        if (!$destStock || $destStock->remaining_quantity < $request->destination_quantity) {
            return back()->withInput()->withErrors([
                'destination_item_id' => 'The destination shop does not currently have enough stock of the requested product.',
            ]);
        }

        $sourceShop = Shop::findOrFail($sourceShopId);
        $destShop = Shop::findOrFail($destShopId);
        $sourceItem = Item::findOrFail($request->source_item_id);
        $destItem = Item::findOrFail($request->destination_item_id);

        $swap = ShopProductSwap::create([
            'source_shop_id'       => $sourceShopId,
            'destination_shop_id'  => $destShopId,
            'source_item_id'       => $request->source_item_id,
            'source_quantity'      => (int) $request->source_quantity,
            'destination_item_id'  => $request->destination_item_id,
            'destination_quantity' => (int) $request->destination_quantity,
            'reason_type'          => $request->reason_type,
            'notes'                => $request->notes,
            'status'               => 'pending_destination',
            'initiated_by'         => $user->id,
        ]);

        // Notify destination shop admins
        $destAdmins = User::where('shop_id', $destShopId)->where('role', 'shop_admin')->get();
        foreach ($destAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Incoming Product Swap Proposal',
                'message' => "Shop '{$sourceShop->shop_name}' proposed to swap {$swap->source_quantity}x {$sourceItem->item_name} for your {$swap->destination_quantity}x {$destItem->item_name} (Swap #{$swap->swap_number}).",
            ]);
        }

        // Notify owner and system admins
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'New Product Swap Proposal',
                'message' => "Swap #{$swap->swap_number} proposed between {$sourceShop->shop_name} and {$destShop->shop_name}.",
            ]);
        }

        return redirect()->route('shop-swaps.show', $swap)
            ->with('success', "Product swap proposal #{$swap->swap_number} created and sent to {$destShop->shop_name} for approval!");
    }

    /**
     * Display swap details, comparison card, approval progress stepper, and contextual action buttons.
     */
    public function show(ShopProductSwap $shopProductSwap)
    {
        $user = Auth::user();
        $this->authorizeAccess($shopProductSwap);

        $shopProductSwap->load([
            'sourceShop',
            'destinationShop',
            'sourceItem.category',
            'sourceItem.unit',
            'destinationItem.category',
            'destinationItem.unit',
            'initiator',
            'destinationApprover',
            'ownerApprover',
            'canceller',
            'reverter',
        ]);

        // Live current stock of both items in both shops for real-time visibility
        $sourceItemInSourceShop = (int) ShopStock::where('shop_id', $shopProductSwap->source_shop_id)
            ->where('item_id', $shopProductSwap->source_item_id)
            ->value('remaining_quantity') ?? 0;

        $destItemInDestShop = (int) ShopStock::where('shop_id', $shopProductSwap->destination_shop_id)
            ->where('item_id', $shopProductSwap->destination_item_id)
            ->value('remaining_quantity') ?? 0;

        $destItemInSourceShop = (int) ShopStock::where('shop_id', $shopProductSwap->source_shop_id)
            ->where('item_id', $shopProductSwap->destination_item_id)
            ->value('remaining_quantity') ?? 0;

        $sourceItemInDestShop = (int) ShopStock::where('shop_id', $shopProductSwap->destination_shop_id)
            ->where('item_id', $shopProductSwap->source_item_id)
            ->value('remaining_quantity') ?? 0;

        return view('shop-swaps.show', compact(
            'shopProductSwap',
            'sourceItemInSourceShop',
            'destItemInDestShop',
            'destItemInSourceShop',
            'sourceItemInDestShop'
        ));
    }

    /**
     * Destination Shop Admin approves the swap proposal.
     */
    public function approveDestination(Request $request, ShopProductSwap $shopProductSwap)
    {
        $user = Auth::user();

        if (!$shopProductSwap->canApproveDestination($user)) {
            abort(403, 'Unauthorized to approve this swap for the destination shop.');
        }

        // Re-check destination stock
        $destStock = ShopStock::where('shop_id', $shopProductSwap->destination_shop_id)
            ->where('item_id', $shopProductSwap->destination_item_id)
            ->first();

        if (!$destStock || $destStock->remaining_quantity < $shopProductSwap->destination_quantity) {
            return back()->with('error', 'Cannot approve: your shop currently has insufficient stock of the requested item.');
        }

        $shopProductSwap->update([
            'status'                  => 'pending_owner',
            'destination_approved_by' => $user->id,
            'destination_approved_at' => now(),
        ]);

        // Notify Owner & System Admins for final confirmation
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Product Swap Awaiting Final Confirmation',
                'message' => "Swap #{$shopProductSwap->swap_number} between {$shopProductSwap->sourceShop->shop_name} and {$shopProductSwap->destinationShop->shop_name} was approved by Destination Shop. Awaiting your confirmation to execute the stock exchange.",
            ]);
        }

        // Notify initiator at Shop A
        Notification::create([
            'user_id' => $shopProductSwap->initiated_by,
            'title'   => 'Product Swap Approved by Destination Shop',
            'message' => "Destination shop '{$shopProductSwap->destinationShop->shop_name}' approved your swap proposal #{$shopProductSwap->swap_number}. It is now awaiting final confirmation from the Owner.",
        ]);

        return back()->with('success', "Product swap #{$shopProductSwap->swap_number} approved! Now awaiting final confirmation by the System Owner.");
    }

    /**
     * System Owner / System Admin: Confirms swap and executes atomic stock alteration across both shops.
     */
    public function confirmOwner(Request $request, ShopProductSwap $shopProductSwap)
    {
        $user = Auth::user();

        if (!$shopProductSwap->canConfirmOwner($user)) {
            abort(403, 'Only the System Owner or System Admin can confirm and execute product swaps.');
        }

        // Verify stock in both shops
        $sourceStock = ShopStock::where('shop_id', $shopProductSwap->source_shop_id)
            ->where('item_id', $shopProductSwap->source_item_id)
            ->first();

        $destStock = ShopStock::where('shop_id', $shopProductSwap->destination_shop_id)
            ->where('item_id', $shopProductSwap->destination_item_id)
            ->first();

        if (!$sourceStock || $sourceStock->remaining_quantity < $shopProductSwap->source_quantity) {
            return back()->with('error', "Cannot confirm: {$shopProductSwap->sourceShop->shop_name} currently does not have enough stock of {$shopProductSwap->sourceItem->item_name}. Available: " . ($sourceStock?->remaining_quantity ?? 0));
        }

        if (!$destStock || $destStock->remaining_quantity < $shopProductSwap->destination_quantity) {
            return back()->with('error', "Cannot confirm: {$shopProductSwap->destinationShop->shop_name} currently does not have enough stock of {$shopProductSwap->destinationItem->item_name}. Available: " . ($destStock?->remaining_quantity ?? 0));
        }

        DB::transaction(function () use ($shopProductSwap, $user, $sourceStock, $destStock) {
            // 1. Shop A: Decrement Source Item
            $sourceStock->decrement('remaining_quantity', $shopProductSwap->source_quantity);

            // 2. Shop A: Increment or create Destination Item
            $stockA_dest = ShopStock::firstOrNew([
                'shop_id' => $shopProductSwap->source_shop_id,
                'item_id' => $shopProductSwap->destination_item_id,
            ]);
            $stockA_dest->quantity = ($stockA_dest->quantity ?? 0) + $shopProductSwap->destination_quantity;
            $stockA_dest->remaining_quantity = ($stockA_dest->remaining_quantity ?? 0) + $shopProductSwap->destination_quantity;
            if (!$stockA_dest->exists || empty($stockA_dest->buying_price)) {
                $stockA_dest->buying_price = $destStock->buying_price;
                $stockA_dest->selling_price = $destStock->selling_price;
            }
            $stockA_dest->date_received = now()->toDateString();
            $stockA_dest->save();

            // 3. Shop B: Decrement Destination Item
            $destStock->decrement('remaining_quantity', $shopProductSwap->destination_quantity);

            // 4. Shop B: Increment or create Source Item
            $stockB_source = ShopStock::firstOrNew([
                'shop_id' => $shopProductSwap->destination_shop_id,
                'item_id' => $shopProductSwap->source_item_id,
            ]);
            $stockB_source->quantity = ($stockB_source->quantity ?? 0) + $shopProductSwap->source_quantity;
            $stockB_source->remaining_quantity = ($stockB_source->remaining_quantity ?? 0) + $shopProductSwap->source_quantity;
            if (!$stockB_source->exists || empty($stockB_source->buying_price)) {
                $stockB_source->buying_price = $sourceStock->buying_price;
                $stockB_source->selling_price = $sourceStock->selling_price;
            }
            $stockB_source->date_received = now()->toDateString();
            $stockB_source->save();

            // 5. Audit logs for both shops
            StockLog::create([
                'item_id'          => $shopProductSwap->source_item_id,
                'from_location'    => $shopProductSwap->sourceShop->shop_name,
                'to_location'      => $shopProductSwap->destinationShop->shop_name,
                'quantity'         => $shopProductSwap->source_quantity,
                'transaction_type' => 'STOCK_TRANSFER',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Product Swap #{$shopProductSwap->swap_number} confirmed by Owner. Swapped out for {$shopProductSwap->destinationItem->item_name}.",
            ]);

            StockLog::create([
                'item_id'          => $shopProductSwap->destination_item_id,
                'from_location'    => $shopProductSwap->destinationShop->shop_name,
                'to_location'      => $shopProductSwap->sourceShop->shop_name,
                'quantity'         => $shopProductSwap->destination_quantity,
                'transaction_type' => 'STOCK_TRANSFER',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Product Swap #{$shopProductSwap->swap_number} confirmed by Owner. Received in exchange from {$shopProductSwap->destinationShop->shop_name}.",
            ]);

            // 6. Update Swap State
            $shopProductSwap->update([
                'status'            => 'confirmed',
                'owner_approved_by' => $user->id,
                'owner_approved_at' => now(),
            ]);
        });

        // Notify both branches
        $notifyUsers = User::whereIn('shop_id', [$shopProductSwap->source_shop_id, $shopProductSwap->destination_shop_id])
            ->whereIn('role', ['shop_admin', 'seller'])
            ->get();

        foreach ($notifyUsers as $nu) {
            Notification::create([
                'user_id' => $nu->id,
                'title'   => 'Product Swap Executed & Confirmed',
                'message' => "Product Swap #{$shopProductSwap->swap_number} has been confirmed by Owner. Stock has been adopted and updated in both {$shopProductSwap->sourceShop->shop_name} and {$shopProductSwap->destinationShop->shop_name}.",
            ]);
        }

        return back()->with('success', "Product swap #{$shopProductSwap->swap_number} successfully confirmed! Stock has been updated in both branches.");
    }

    /**
     * Cancel the swap proposal with a reason (by Initiator, Destination Admin, or Owner).
     */
    public function cancel(Request $request, ShopProductSwap $shopProductSwap)
    {
        $user = Auth::user();

        if (!$shopProductSwap->canCancel($user)) {
            abort(403, 'Unauthorized to cancel this swap.');
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $shopProductSwap->update([
            'status'              => 'cancelled',
            'cancelled_by'        => $user->id,
            'cancelled_at'        => now(),
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        // Notify initiator if someone else cancelled
        if ($shopProductSwap->initiated_by !== $user->id) {
            Notification::create([
                'user_id' => $shopProductSwap->initiated_by,
                'title'   => 'Product Swap Proposal Cancelled',
                'message' => "Swap proposal #{$shopProductSwap->swap_number} was cancelled by {$user->name}. Reason: {$request->cancellation_reason}",
            ]);
        }

        return back()->with('success', "Product swap #{$shopProductSwap->swap_number} has been cancelled.");
    }

    /**
     * System Owner / System Admin: Revert a confirmed product swap.
     */
    public function revert(Request $request, ShopProductSwap $shopProductSwap)
    {
        $user = Auth::user();

        if (!$shopProductSwap->canRevert($user)) {
            abort(403, 'Only the System Owner or System Admin can revert a confirmed swap.');
        }

        $request->validate([
            'revert_reason' => 'required|string|max:500',
        ]);

        // Pre-check stock:
        // Shop A must have destination_item >= destination_quantity
        // Shop B must have source_item >= source_quantity
        $stockA_dest = ShopStock::where('shop_id', $shopProductSwap->source_shop_id)
            ->where('item_id', $shopProductSwap->destination_item_id)
            ->first();

        $stockB_source = ShopStock::where('shop_id', $shopProductSwap->destination_shop_id)
            ->where('item_id', $shopProductSwap->source_item_id)
            ->first();

        $availA = $stockA_dest?->remaining_quantity ?? 0;
        $availB = $stockB_source?->remaining_quantity ?? 0;

        if ($availA < $shopProductSwap->destination_quantity) {
            return back()->with('error', "Cannot revert swap: {$shopProductSwap->sourceShop->shop_name} only has {$availA} of {$shopProductSwap->destinationItem->item_name}, but {$shopProductSwap->destination_quantity} is required to reverse the swap.");
        }

        if ($availB < $shopProductSwap->source_quantity) {
            return back()->with('error', "Cannot revert swap: {$shopProductSwap->destinationShop->shop_name} only has {$availB} of {$shopProductSwap->sourceItem->item_name}, but {$shopProductSwap->source_quantity} is required to reverse the swap.");
        }

        DB::transaction(function () use ($shopProductSwap, $user, $request, $stockA_dest, $stockB_source) {
            // 1. Reverse Shop A: decrement destination_item, increment source_item
            $stockA_dest->decrement('remaining_quantity', $shopProductSwap->destination_quantity);

            $stockA_source = ShopStock::firstOrNew([
                'shop_id' => $shopProductSwap->source_shop_id,
                'item_id' => $shopProductSwap->source_item_id,
            ]);
            $stockA_source->remaining_quantity = ($stockA_source->remaining_quantity ?? 0) + $shopProductSwap->source_quantity;
            $stockA_source->save();

            // 2. Reverse Shop B: decrement source_item, increment destination_item
            $stockB_source->decrement('remaining_quantity', $shopProductSwap->source_quantity);

            $stockB_dest = ShopStock::firstOrNew([
                'shop_id' => $shopProductSwap->destination_shop_id,
                'item_id' => $shopProductSwap->destination_item_id,
            ]);
            $stockB_dest->remaining_quantity = ($stockB_dest->remaining_quantity ?? 0) + $shopProductSwap->destination_quantity;
            $stockB_dest->save();

            // 3. Audit logs for reversal
            StockLog::create([
                'item_id'          => $shopProductSwap->source_item_id,
                'from_location'    => $shopProductSwap->destinationShop->shop_name,
                'to_location'      => $shopProductSwap->sourceShop->shop_name,
                'quantity'         => $shopProductSwap->source_quantity,
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Product Swap #{$shopProductSwap->swap_number} REVERTED by Owner. Reason: {$request->revert_reason}",
            ]);

            StockLog::create([
                'item_id'          => $shopProductSwap->destination_item_id,
                'from_location'    => $shopProductSwap->sourceShop->shop_name,
                'to_location'      => $shopProductSwap->destinationShop->shop_name,
                'quantity'         => $shopProductSwap->destination_quantity,
                'transaction_type' => 'ADJUSTMENT',
                'performed_by'     => $user->id,
                'date'             => now()->toDateString(),
                'notes'            => "Product Swap #{$shopProductSwap->swap_number} REVERTED by Owner. Reason: {$request->revert_reason}",
            ]);

            // 4. Update status
            $shopProductSwap->update([
                'status'        => 'reverted',
                'reverted_by'   => $user->id,
                'reverted_at'   => now(),
                'revert_reason' => $request->revert_reason,
            ]);
        });

        // Notify both branches
        $notifyUsers = User::whereIn('shop_id', [$shopProductSwap->source_shop_id, $shopProductSwap->destination_shop_id])
            ->whereIn('role', ['shop_admin', 'seller'])
            ->get();

        foreach ($notifyUsers as $nu) {
            Notification::create([
                'user_id' => $nu->id,
                'title'   => 'Product Swap Reverted by Owner',
                'message' => "Product Swap #{$shopProductSwap->swap_number} was reverted by Owner. Original stocks have been restored to both {$shopProductSwap->sourceShop->shop_name} and {$shopProductSwap->destinationShop->shop_name}.",
            ]);
        }

        return back()->with('success', "Product swap #{$shopProductSwap->swap_number} has been safely reverted. Original stock has been restored to both branches.");
    }

    /**
     * Authorize access to swap details.
     */
    private function authorizeAccess(ShopProductSwap $swap): void
    {
        $user = Auth::user();
        if ($user->isOwner()) return;

        if ($user->isShopAdmin()) {
            if ((int) $user->shop_id === (int) $swap->source_shop_id || (int) $user->shop_id === (int) $swap->destination_shop_id) {
                return;
            }
        }

        abort(403, 'Unauthorized access to this product swap.');
    }
}
