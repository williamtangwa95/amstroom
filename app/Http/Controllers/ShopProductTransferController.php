<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\ShopProductTransfer;
use App\Models\ShopProductTransferItem;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopProductTransferController extends Controller
{
    /**
     * Ensure inter-shop product transfer tables exist so the feature works
     * immediately even before `php artisan migrate` is manually run.
     */
    public static function ensureTablesExist(): void
    {
        if (!Schema::hasTable('shop_product_transfers')) {
            Schema::create('shop_product_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_number', 50)->unique();
                $table->foreignId('from_shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('to_shop_id')->constrained('shops')->cascadeOnDelete();
                $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled', 'reverted'])->default('pending')->index();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reverted_at')->nullable();
                $table->text('revert_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['from_shop_id', 'status']);
                $table->index(['to_shop_id', 'status']);
            });
        }

        if (!Schema::hasTable('shop_product_transfer_items')) {
            Schema::create('shop_product_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_product_transfer_id')->constrained('shop_product_transfers')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->boolean('is_admin_stock')->default(false);
                $table->decimal('buying_price', 15, 2)->default(0);
                $table->decimal('selling_price', 15, 2)->default(0);
                $table->json('batch_allocations')->nullable();
                $table->timestamps();

                $table->index(['shop_product_transfer_id', 'item_id'], 'spti_transfer_item_idx');
            });
        }
    }

    /**
     * Authorize view access to a transfer record.
     */
    private function authorizeAccess(ShopProductTransfer $transfer): void
    {
        $user = Auth::user();
        if ($user->isOwner()) {
            return;
        }
        $shopId = (int) $user->shop_id;
        if ($shopId !== (int) $transfer->from_shop_id && $shopId !== (int) $transfer->to_shop_id) {
            abort(403, 'Unauthorized access to this product transfer.');
        }
    }

    /**
     * Display listing of Inter-Shop Product Transfers.
     */
    public function index(Request $request)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        $isOwner = $user->isOwner();

        $query = ShopProductTransfer::with([
            'fromShop',
            'toShop',
            'items.item.unit',
            'requester',
            'approver',
        ]);

        if ($isOwner) {
            if ($request->filled('shop_id')) {
                $filterShopId = (int) $request->shop_id;
                $query->where(function ($q) use ($filterShopId) {
                    $q->where('from_shop_id', $filterShopId)
                      ->orWhere('to_shop_id', $filterShopId);
                });
            }
        } else {
            $shopId = (int) $user->shop_id;
            $query->where(function ($q) use ($shopId) {
                $q->where('from_shop_id', $shopId)
                  ->orWhere('to_shop_id', $shopId);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('fromShop', fn ($sq) => $sq->where('shop_name', 'like', "%{$search}%"))
                  ->orWhereHas('toShop', fn ($sq) => $sq->where('shop_name', 'like', "%{$search}%"))
                  ->orWhereHas('items.item', fn ($iq) => $iq->where('item_name', 'like', "%{$search}%"));
            });
        }

        $baseCountQuery = clone $query;

        $counts = [
            'all'             => (clone $baseCountQuery)->count(),
            'action_required' => $isOwner
                ? (clone $baseCountQuery)->where('status', 'pending')->count()
                : (clone $baseCountQuery)->where('status', 'pending')->where('from_shop_id', $user->shop_id)->count(),
            'outgoing'        => $isOwner
                ? (clone $baseCountQuery)->where('status', 'pending')->count()
                : (clone $baseCountQuery)->where('status', 'pending')->where('to_shop_id', $user->shop_id)->count(),
            'confirmed'       => (clone $baseCountQuery)->where('status', 'confirmed')->count(),
            'history'         => (clone $baseCountQuery)->whereIn('status', ['rejected', 'cancelled', 'reverted'])->count(),
        ];

        $tab = $request->get('tab', 'all');
        $validTabs = ['all', 'action_required', 'outgoing', 'confirmed', 'history'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'all';
        }

        if ($tab === 'action_required') {
            $query->where('status', 'pending');
            if (!$isOwner) {
                $query->where('from_shop_id', $user->shop_id);
            }
        } elseif ($tab === 'outgoing') {
            $query->where('status', 'pending');
            if (!$isOwner) {
                $query->where('to_shop_id', $user->shop_id);
            }
        } elseif ($tab === 'confirmed') {
            $query->where('status', 'confirmed');
        } elseif ($tab === 'history') {
            $query->whereIn('status', ['rejected', 'cancelled', 'reverted']);
        }

        $transfers = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $shops = $isOwner ? Shop::where('status', 'active')->orderBy('shop_name')->get() : collect();

        return view('product-transfers.index', compact('transfers', 'counts', 'tab', 'shops'));
    }

    /**
     * Show form for a Shop Admin (Shop B) to request products from another shop (Shop A).
     */
    public function create(Request $request)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$user->isShopAdmin() || !$user->shop_id) {
            abort(403, 'Only Shop Admins can request inter-shop product transfers.');
        }

        $myShop = $user->shop;
        if (!$myShop) {
            return redirect()->route('product-transfers.index')->with('error', 'No active shop assigned to your account.');
        }

        $otherShops = Shop::where('status', 'active')
            ->where('id', '!=', $myShop->id)
            ->orderBy('shop_name')
            ->get();

        // Current shop stock per item (for live before/after preview!)
        $myShopStockMap = ShopStock::where('shop_id', $myShop->id)
            ->where('is_admin_stock', false)
            ->select('item_id', DB::raw('SUM(remaining_quantity) as total_rem'))
            ->groupBy('item_id')
            ->pluck('total_rem', 'item_id')
            ->map(fn ($v) => (int) $v);

        return view('product-transfers.create', compact('myShop', 'otherShops', 'myShopStockMap'));
    }

    /**
     * AJAX: Fetch available Owner/Main-Store stock items in the chosen supplying shop (Shop A).
     */
    public function getSourceItems(Request $request)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$user->isShopAdmin() || !$user->shop_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'from_shop_id' => 'required|integer|exists:shops,id',
        ]);

        $fromShopId = (int) $request->from_shop_id;
        $toShopId = (int) $user->shop_id;

        if ($fromShopId === $toShopId) {
            return response()->json(['success' => false, 'message' => 'Please select a different shop.'], 422);
        }

        $sourceBatches = ShopStock::with(['item.category', 'item.unit'])
            ->where('shop_id', $fromShopId)
            ->where('is_admin_stock', false)
            ->where('remaining_quantity', '>', 0)
            ->get();

        $myStockMap = ShopStock::where('shop_id', $toShopId)
            ->where('is_admin_stock', false)
            ->select('item_id', DB::raw('SUM(remaining_quantity) as total_rem'))
            ->groupBy('item_id')
            ->pluck('total_rem', 'item_id');

        $grouped = $sourceBatches->groupBy('item_id')->map(function ($batches) use ($myStockMap) {
            $first = $batches->first();
            $item = $first->item;
            if (!$item) {
                return null;
            }
            $sourceAvailable = (int) $batches->sum(fn ($b) => (int) $b->getRawOriginal('remaining_quantity'));
            return [
                'item_id'          => (int) $item->id,
                'item_name'        => $item->item_name,
                'category_name'    => $item->category?->category_name ?? 'General',
                'brand'            => $item->brand ?: '',
                'unit_symbol'      => $item->unit_symbol,
                'source_available' => $sourceAvailable,
                'my_current_stock' => (int) ($myStockMap[$item->id] ?? 0),
                'selling_price'    => (float) $first->selling_price,
            ];
        })->filter()->sortBy('item_name')->values();

        return response()->json([
            'success' => true,
            'count'   => $grouped->count(),
            'items'   => $grouped,
        ]);
    }

    /**
     * Store a new inter-shop product transfer request (pending supplying shop confirmation).
     */
    public function store(Request $request)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$user->isShopAdmin() || !$user->shop_id) {
            abort(403, 'Only Shop Admins can submit inter-shop product transfer requests.');
        }

        $validated = $request->validate([
            'from_shop_id'     => 'required|integer|exists:shops,id',
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|integer|exists:items,id',
            'items.*.qty'      => 'required|integer|min:1',
            'notes'            => 'nullable|string|max:500',
        ]);

        $toShopId = (int) $user->shop_id;
        $fromShopId = (int) $validated['from_shop_id'];

        if ($fromShopId === $toShopId) {
            return back()->withInput()->withErrors([
                'from_shop_id' => 'You cannot request stock from your own shop. Please pick another branch.',
            ]);
        }

        // Merge duplicate item rows safely
        $mergedItems = [];
        foreach ($validated['items'] as $entry) {
            $itemId = (int) $entry['id'];
            $qty = (int) $entry['qty'];
            $mergedItems[$itemId] = ($mergedItems[$itemId] ?? 0) + $qty;
        }

        // Verify live stock availability in supplying shop (Shop A)
        foreach ($mergedItems as $itemId => $qty) {
            $available = (int) ShopStock::where('shop_id', $fromShopId)
                ->where('item_id', $itemId)
                ->where('is_admin_stock', false)
                ->sum('remaining_quantity');

            if ($available < $qty) {
                $item = Item::find($itemId);
                $itemName = $item?->item_name ?? "Item #{$itemId}";
                return back()->withInput()->withErrors([
                    'items' => "Insufficient stock in the supplying shop for \"{$itemName}\". Available: {$available}, Requested: {$qty}.",
                ]);
            }
        }

        $fromShop = Shop::findOrFail($fromShopId);
        $toShop = Shop::findOrFail($toShopId);

        $transfer = DB::transaction(function () use ($fromShopId, $toShopId, $user, $validated, $mergedItems) {
            $transfer = ShopProductTransfer::create([
                'transfer_number' => ShopProductTransfer::generateNumber(),
                'from_shop_id'    => $fromShopId,
                'to_shop_id'      => $toShopId,
                'status'          => 'pending',
                'requested_by'    => $user->id,
                'notes'           => $validated['notes'] ?? null,
            ]);

            foreach ($mergedItems as $itemId => $qty) {
                $sampleBatch = ShopStock::where('shop_id', $fromShopId)
                    ->where('item_id', $itemId)
                    ->where('is_admin_stock', false)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('date_received')
                    ->orderBy('id')
                    ->first();

                ShopProductTransferItem::create([
                    'shop_product_transfer_id' => $transfer->id,
                    'item_id'                  => $itemId,
                    'quantity'                 => $qty,
                    'is_admin_stock'           => false,
                    'buying_price'             => $sampleBatch?->buying_price ?? 0,
                    'selling_price'            => $sampleBatch?->selling_price ?? 0,
                ]);
            }

            return $transfer;
        });

        // Notify supplying shop (Shop A) admins to review & confirm
        $loadedItems = $transfer->items()->with('item')->get();
        $itemsSummary = $loadedItems
            ->map(fn ($ti) => "{$ti->quantity}x " . ($ti->item?->item_name ?? 'Item'))
            ->implode(', ');
        $totalAmountFormatted = 'TZS ' . number_format($transfer->total_amount, 0);

        $supplyingAdmins = User::where('shop_id', $fromShopId)->where('role', 'shop_admin')->get();
        foreach ($supplyingAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Incoming Product Transfer Request',
                'message' => "{$toShop->shop_name} requested {$itemsSummary} ({$totalAmountFormatted}) from your shop ({$transfer->transfer_number}). Review and confirm to move stock.",
            ]);
        }

        // Notify Owner for full real-time transparency on transfer initiation
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Inter-Shop Product Transfer Requested',
                'message' => "Transfer {$transfer->transfer_number} (Pending): {$itemsSummary} ({$totalAmountFormatted}) requested from {$fromShop->shop_name} to {$toShop->shop_name} by {$user->name}.",
            ]);
        }

        return redirect()->route('product-transfers.show', $transfer)
            ->with('success', "Product Transfer request {$transfer->transfer_number} sent to {$fromShop->shop_name} for confirmation.");
    }

    /**
     * Show Product Transfer details with live branch stock comparison & contextual actions.
     */
    public function show(ShopProductTransfer $productTransfer)
    {
        self::ensureTablesExist();
        $this->authorizeAccess($productTransfer);

        $productTransfer->load([
            'fromShop',
            'toShop',
            'items.item.category',
            'items.item.unit',
            'requester',
            'approver',
            'rejecter',
            'canceller',
            'reverter',
        ]);

        $itemIds = $productTransfer->items->pluck('item_id')->all();

        $fromStockMap = ShopStock::where('shop_id', $productTransfer->from_shop_id)
            ->where('is_admin_stock', false)
            ->whereIn('item_id', $itemIds)
            ->select('item_id', DB::raw('SUM(remaining_quantity) as total_rem'))
            ->groupBy('item_id')
            ->pluck('total_rem', 'item_id')
            ->map(fn ($v) => (int) $v);

        $toStockMap = ShopStock::where('shop_id', $productTransfer->to_shop_id)
            ->where('is_admin_stock', false)
            ->whereIn('item_id', $itemIds)
            ->select('item_id', DB::raw('SUM(remaining_quantity) as total_rem'))
            ->groupBy('item_id')
            ->pluck('total_rem', 'item_id')
            ->map(fn ($v) => (int) $v);

        return view('product-transfers.show', compact('productTransfer', 'fromStockMap', 'toStockMap'));
    }

    /**
     * Supplying Shop Admin (Shop A) confirms the request — executes atomic FIFO stock transfer.
     */
    public function confirm(Request $request, ShopProductTransfer $productTransfer)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$productTransfer->canConfirm($user)) {
            abort(403, 'Only the supplying shop admin can confirm this product transfer.');
        }

        try {
            DB::transaction(function () use ($productTransfer, $user) {
                $transfer = ShopProductTransfer::with(['fromShop', 'toShop', 'items.item'])
                    ->where('id', $productTransfer->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($transfer->status !== 'pending') {
                    throw new \Exception('This product transfer has already been processed.');
                }

                $fromShop = $transfer->fromShop;
                $toShop = $transfer->toShop;

                foreach ($transfer->items as $tItem) {
                    $qtyNeeded = (int) $tItem->quantity;
                    $isAdminStock = (bool) $tItem->is_admin_stock;

                    // Lock Shop A batches (FIFO)
                    $sourceBatches = ShopStock::where('shop_id', $transfer->from_shop_id)
                        ->where('item_id', $tItem->item_id)
                        ->where('is_admin_stock', $isAdminStock)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('date_received', 'asc')
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailable = (int) $sourceBatches->sum(fn ($b) => (int) $b->getRawOriginal('remaining_quantity'));
                    if ($totalAvailable < $qtyNeeded) {
                        $name = $tItem->item?->item_name ?? "Item #{$tItem->item_id}";
                        throw new \Exception("Cannot confirm: {$fromShop->shop_name} only has {$totalAvailable} pc of \"{$name}\" available, but {$qtyNeeded} pc were requested.");
                    }

                    $remainingToMove = $qtyNeeded;
                    $allocations = [];
                    $lastBuyingPrice = 0;
                    $lastSellingPrice = 0;

                    foreach ($sourceBatches as $srcBatch) {
                        if ($remainingToMove <= 0) {
                            break;
                        }

                        $srcRem = (int) $srcBatch->getRawOriginal('remaining_quantity');
                        $take = min($srcRem, $remainingToMove);
                        if ($take <= 0) {
                            continue;
                        }

                        // Deduct from Shop A: adjust BOTH remaining_quantity AND quantity so
                        // transferred units are never counted as "sold" in Shop A's KPIs!
                        $srcQty = (int) $srcBatch->quantity;
                        $newSrcRem = $srcRem - $take;
                        $newSrcQty = max($newSrcRem, $srcQty - $take);

                        $srcBatch->update([
                            'quantity'           => $newSrcQty,
                            'remaining_quantity' => $newSrcRem,
                        ]);

                        $bp = (float) $srcBatch->getRawOriginal('buying_price');
                        $sp = (float) $srcBatch->getRawOriginal('selling_price');
                        $lastBuyingPrice = $bp;
                        $lastSellingPrice = $sp;

                        // Credit to Shop B: merge into matching batch (same item, ownership & buying_price)
                        // or create a fresh batch so Shop B's stock increases cleanly (e.g. 1 -> 5 pc).
                        $destBatch = ShopStock::where('shop_id', $transfer->to_shop_id)
                            ->where('item_id', $tItem->item_id)
                            ->where('is_admin_stock', $isAdminStock)
                            ->where('buying_price', $bp)
                            ->orderByDesc('id')
                            ->lockForUpdate()
                            ->first();

                        $createdNewBatch = false;
                        if ($destBatch) {
                            $destBatch->increment('quantity', $take);
                            $destBatch->increment('remaining_quantity', $take);
                            $destBatch->update([
                                'date_received' => now()->toDateString(),
                                'is_sellable'   => true,
                            ]);
                        } else {
                            $destBatch = ShopStock::create([
                                'shop_id'            => $transfer->to_shop_id,
                                'item_id'            => $tItem->item_id,
                                'buying_price'       => $bp,
                                'selling_price'      => $sp,
                                'quantity'           => $take,
                                'remaining_quantity' => $take,
                                'low_stock_alert'    => $srcBatch->low_stock_alert ?? 1,
                                'date_received'      => now()->toDateString(),
                                'is_sellable'        => true,
                                'is_price_pending'   => false,
                                'is_admin_stock'     => $isAdminStock,
                                'allow_components'   => (bool) $srcBatch->allow_components,
                            ]);
                            $createdNewBatch = true;
                        }

                        $allocations[] = [
                            'source_batch_id'   => (int) $srcBatch->id,
                            'dest_batch_id'     => (int) $destBatch->id,
                            'qty'               => $take,
                            'buying_price'      => $bp,
                            'selling_price'     => $sp,
                            'created_new_batch' => $createdNewBatch,
                        ];

                        $remainingToMove -= $take;
                    }

                    $tItem->update([
                        'buying_price'      => $lastBuyingPrice,
                        'selling_price'     => $lastSellingPrice,
                        'batch_allocations' => $allocations,
                    ]);

                    // Audit Trail in StockLog
                    StockLog::create([
                        'item_id'          => $tItem->item_id,
                        'from_location'    => $fromShop->shop_name,
                        'to_location'      => $toShop->shop_name,
                        'quantity'         => $qtyNeeded,
                        'transaction_type' => 'STOCK_TRANSFER',
                        'performed_by'     => $user->id,
                        'date'             => now()->toDateString(),
                        'notes'            => "Inter-Shop Product Transfer {$transfer->transfer_number} confirmed by {$user->name} ({$fromShop->shop_name} -> {$toShop->shop_name}).",
                        'is_admin_stock'   => $isAdminStock,
                    ]);
                }

                $transfer->update([
                    'status'      => 'confirmed',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $productTransfer->refresh()->load(['fromShop', 'toShop', 'items.item']);
        $itemsSummary = $productTransfer->items
            ->map(fn ($ti) => "{$ti->quantity}x " . ($ti->item?->item_name ?? 'Item'))
            ->implode(', ');

        // Notify requesting shop (Shop B) admins & sellers
        $receivingUsers = User::where('shop_id', $productTransfer->to_shop_id)
            ->whereIn('role', ['shop_admin', 'seller'])
            ->get();
        foreach ($receivingUsers as $ru) {
            Notification::create([
                'user_id' => $ru->id,
                'title'   => 'Product Transfer Confirmed',
                'message' => "{$productTransfer->fromShop->shop_name} confirmed Product Transfer {$productTransfer->transfer_number} ({$itemsSummary}). Your shop stock has been updated.",
            ]);
        }

        // Notify Owner for full operational visibility
        $totalAmountFormatted = 'TZS ' . number_format($productTransfer->total_amount, 0);
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Inter-Shop Product Transfer Completed',
                'message' => "Transfer {$productTransfer->transfer_number} (Confirmed): {$itemsSummary} ({$totalAmountFormatted}) moved from {$productTransfer->fromShop->shop_name} to {$productTransfer->toShop->shop_name} (confirmed by {$user->name}).",
            ]);
        }

        return redirect()->route('product-transfers.show', $productTransfer)
            ->with('success', "Product Transfer {$productTransfer->transfer_number} confirmed! Stock has been moved from {$productTransfer->fromShop->shop_name} to {$productTransfer->toShop->shop_name}.");
    }

    /**
     * Supplying Shop Admin (Shop A) rejects a pending request (zero stock impact).
     */
    public function reject(Request $request, ShopProductTransfer $productTransfer)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$productTransfer->canReject($user)) {
            abort(403, 'Unauthorized to reject this product transfer.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $productTransfer->update([
            'status'           => 'rejected',
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        if ($productTransfer->requested_by) {
            Notification::create([
                'user_id' => $productTransfer->requested_by,
                'title'   => 'Product Transfer Rejected',
                'message' => "{$productTransfer->fromShop->shop_name} rejected your Product Transfer {$productTransfer->transfer_number}. Reason: {$validated['rejection_reason']}",
            ]);
        }

        // Notify Owner for full transparency
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Inter-Shop Product Transfer Rejected',
                'message' => "Transfer {$productTransfer->transfer_number} (Rejected): {$productTransfer->fromShop->shop_name} -> {$productTransfer->toShop->shop_name} rejected by {$user->name}. Reason: {$validated['rejection_reason']}",
            ]);
        }

        return redirect()->route('product-transfers.show', $productTransfer)
            ->with('success', "Product Transfer {$productTransfer->transfer_number} has been rejected.");
    }

    /**
     * Requesting Shop Admin (Shop B) or Owner cancels a pending request.
     */
    public function cancel(Request $request, ShopProductTransfer $productTransfer)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$productTransfer->canCancel($user)) {
            abort(403, 'Unauthorized to cancel this product transfer.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $productTransfer->update([
            'status'              => 'cancelled',
            'cancelled_by'        => $user->id,
            'cancelled_at'        => now(),
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        // Notify supplying shop admins that the pending request was cancelled
        $supplyingAdmins = User::where('shop_id', $productTransfer->from_shop_id)->where('role', 'shop_admin')->get();
        foreach ($supplyingAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Product Transfer Cancelled',
                'message' => "Product Transfer {$productTransfer->transfer_number} from {$productTransfer->toShop->shop_name} was cancelled by {$user->name}. Reason: {$validated['cancellation_reason']}",
            ]);
        }

        // Notify Owner for full transparency
        $owners = User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title'   => 'Inter-Shop Product Transfer Cancelled',
                'message' => "Transfer {$productTransfer->transfer_number} (Cancelled): {$productTransfer->fromShop->shop_name} -> {$productTransfer->toShop->shop_name} cancelled by {$user->name}. Reason: {$validated['cancellation_reason']}",
            ]);
        }

        return redirect()->route('product-transfers.show', $productTransfer)
            ->with('success', "Product Transfer {$productTransfer->transfer_number} has been cancelled.");
    }

    /**
     * Owner reverts a confirmed inter-shop product transfer cleanly back to original batches.
     */
    public function revert(Request $request, ShopProductTransfer $productTransfer)
    {
        self::ensureTablesExist();

        $user = Auth::user();
        if (!$productTransfer->canRevert($user)) {
            abort(403, 'Only the System Owner can revert a confirmed product transfer.');
        }

        $validated = $request->validate([
            'revert_reason' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($productTransfer, $user, $validated) {
                $transfer = ShopProductTransfer::with(['fromShop', 'toShop', 'items.item'])
                    ->where('id', $productTransfer->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($transfer->status !== 'confirmed') {
                    throw new \Exception('Only confirmed product transfers can be reverted.');
                }

                $fromShop = $transfer->fromShop;
                $toShop = $transfer->toShop;

                foreach ($transfer->items as $tItem) {
                    $allocations = $tItem->batch_allocations ?? [];
                    $qtyToRevert = (int) $tItem->quantity;
                    $isAdminStock = (bool) $tItem->is_admin_stock;

                    // Verify Shop B still holds enough remaining stock to revert safely
                    $destAvailable = (int) ShopStock::where('shop_id', $transfer->to_shop_id)
                        ->where('item_id', $tItem->item_id)
                        ->where('is_admin_stock', $isAdminStock)
                        ->lockForUpdate()
                        ->get()
                        ->sum(fn ($b) => (int) $b->getRawOriginal('remaining_quantity'));

                    if ($destAvailable < $qtyToRevert) {
                        $name = $tItem->item?->item_name ?? "Item #{$tItem->item_id}";
                        throw new \Exception("Cannot revert: {$toShop->shop_name} only has {$destAvailable} pc of \"{$name}\" left (some transferred units have already been sold).");
                    }

                    if (!empty($allocations)) {
                        foreach ($allocations as $alloc) {
                            $qty = (int) ($alloc['qty'] ?? 0);
                            if ($qty <= 0) {
                                continue;
                            }

                            // 1. Deduct back from Shop B
                            $destBatch = ShopStock::where('id', $alloc['dest_batch_id'] ?? 0)->lockForUpdate()->first();
                            if ($destBatch && (int) $destBatch->getRawOriginal('remaining_quantity') >= $qty) {
                                $newRem = (int) $destBatch->getRawOriginal('remaining_quantity') - $qty;
                                $newQty = max($newRem, (int) $destBatch->quantity - $qty);
                                if (!empty($alloc['created_new_batch']) && $newQty === 0 && $newRem === 0) {
                                    $destBatch->delete();
                                } else {
                                    $destBatch->update(['quantity' => $newQty, 'remaining_quantity' => $newRem]);
                                }
                            } else {
                                // Fallback: deduct across Shop B's batches
                                $left = $qty;
                                $fallbackBatches = ShopStock::where('shop_id', $transfer->to_shop_id)
                                    ->where('item_id', $tItem->item_id)
                                    ->where('is_admin_stock', $isAdminStock)
                                    ->where('remaining_quantity', '>', 0)
                                    ->orderByDesc('id')
                                    ->lockForUpdate()
                                    ->get();
                                foreach ($fallbackBatches as $fb) {
                                    if ($left <= 0) break;
                                    $rem = (int) $fb->getRawOriginal('remaining_quantity');
                                    $take = min($rem, $left);
                                    $fb->update([
                                        'quantity'           => max($rem - $take, (int) $fb->quantity - $take),
                                        'remaining_quantity' => $rem - $take,
                                    ]);
                                    $left -= $take;
                                }
                            }

                            // 2. Restore exact Shop A source batch
                            $srcBatch = ShopStock::where('id', $alloc['source_batch_id'] ?? 0)->lockForUpdate()->first();
                            if ($srcBatch) {
                                $srcBatch->increment('quantity', $qty);
                                $srcBatch->increment('remaining_quantity', $qty);
                            } else {
                                ShopStock::create([
                                    'shop_id'            => $transfer->from_shop_id,
                                    'item_id'            => $tItem->item_id,
                                    'buying_price'       => $alloc['buying_price'] ?? $tItem->buying_price,
                                    'selling_price'      => $alloc['selling_price'] ?? $tItem->selling_price,
                                    'quantity'           => $qty,
                                    'remaining_quantity' => $qty,
                                    'date_received'      => now()->toDateString(),
                                    'is_sellable'        => true,
                                    'is_admin_stock'     => $isAdminStock,
                                ]);
                            }
                        }
                    }

                    StockLog::create([
                        'item_id'          => $tItem->item_id,
                        'from_location'    => $toShop->shop_name,
                        'to_location'      => $fromShop->shop_name,
                        'quantity'         => $qtyToRevert,
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by'     => $user->id,
                        'date'             => now()->toDateString(),
                        'notes'            => "Reverted Inter-Shop Product Transfer {$transfer->transfer_number} by Owner ({$user->name}). Reason: {$validated['revert_reason']}",
                        'is_admin_stock'   => $isAdminStock,
                    ]);
                }

                $transfer->update([
                    'status'        => 'reverted',
                    'reverted_by'   => $user->id,
                    'reverted_at'   => now(),
                    'revert_reason' => $validated['revert_reason'],
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        // Notify both branches
        $branchAdmins = User::whereIn('shop_id', [$productTransfer->from_shop_id, $productTransfer->to_shop_id])
            ->where('role', 'shop_admin')
            ->get();
        foreach ($branchAdmins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Product Transfer Reverted',
                'message' => "Owner reverted Product Transfer {$productTransfer->transfer_number}. Stock has been restored to original levels. Reason: {$validated['revert_reason']}",
            ]);
        }

        return redirect()->route('product-transfers.show', $productTransfer)
            ->with('success', "Product Transfer {$productTransfer->transfer_number} reverted and stock restored to both branches.");
    }
}
