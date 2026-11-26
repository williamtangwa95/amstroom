<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = StockRequest::query();
        if (!$user->isOwner()) {
            $query->where('shop_id', $user->shop_id);
        }

        $pendingCount  = (clone $query)->where('status', 'pending')->count();
        $approvedCount = (clone $query)->where('status', 'approved')->count();
        $rejectedCount = (clone $query)->where('status', 'rejected')->count();
        $totalCount    = (clone $query)->count();

        $stats = [
            'total'    => $totalCount,
            'pending'  => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
        ];

        $shops = $user->isOwner()
            ? Shop::where('status', 'active')->orderBy('shop_name')->get()
            : collect();

        return view('stock-requests.index', compact('pendingCount', 'stats', 'shops'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = StockRequest::query();

        if (!$user->isOwner()) {
            $query->where('stock_requests.shop_id', $user->shop_id);
        } elseif ($request->filled('shop_id')) {
            $query->where('stock_requests.shop_id', (int) $request->input('shop_id'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'approved', 'rejected'], true)) {
            $query->where('stock_requests.status', $request->input('status'));
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $cleanId = preg_replace('/[^0-9]/', '', $searchValue);
                if ($cleanId !== '') {
                    $q->orWhere('stock_requests.id', $cleanId);
                }
                $q->orWhere('stock_requests.status', 'like', "%{$searchValue}%")
                  ->orWhere('stock_requests.notes', 'like', "%{$searchValue}%")
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('requester', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = $request->input('order.0.column', 3);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        switch ((int) $orderColumnIndex) {
            case 1:
                $query->leftJoin('shops', 'shops.id', '=', 'stock_requests.shop_id')
                      ->orderBy('shops.shop_name', $orderDirection)
                      ->select('stock_requests.*');
                break;
            case 2:
                $query->leftJoin('users', 'users.id', '=', 'stock_requests.requested_by')
                      ->orderBy('users.name', $orderDirection)
                      ->select('stock_requests.*');
                break;
            case 3:
                $query->orderBy('stock_requests.request_date', $orderDirection);
                break;
            case 4:
                $query->orderBy('stock_requests.status', $orderDirection);
                break;
            default:
                $query->orderBy('stock_requests.request_date', $orderDirection)->orderBy('stock_requests.id', $orderDirection);
                break;
        }

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $requests = $query->with('shop', 'requester')
            ->withCount('items')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($requests as $index => $req) {
            $iteration = $start + $index + 1;
            $shopName = e($req->shop?->shop_name ?? 'Shop');
            $requesterName = e($req->requester?->name ?? 'User');
            $requestDate = $req->request_date ? $req->request_date->format('M d, Y') : 'N/A';
            $statusBadge = '<span class="status-badge badge-' . $req->status . '">' . e(ucfirst($req->status)) . '</span>';

            $itemsHtml = '<span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">' . $req->items_count . ' item(s)</span> ';
            $itemsHtml .= '<button type="button" class="btn btn-xs btn-outline-custom toggle-details" data-id="' . $req->id . '" title="Toggle Details"><i class="bi bi-chevron-down"></i></button>';

            $showUrl = route('stock-requests.show', $req);
            $actions = '<div class="d-flex align-items-center gap-1">';
            $actions .= '<a href="' . $showUrl . '" class="btn btn-xs btn-outline-custom">View / Action</a>';
            $actions .= '</div>';

            $data[] = [
                'iteration' => $iteration,
                'shop' => $shopName,
                'requester' => $requesterName,
                'date' => $requestDate,
                'status' => $statusBadge,
                'items' => $itemsHtml,
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function details(StockRequest $stockRequest)
    {
        $user = Auth::user();
        if (!$user->isOwner() && $stockRequest->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        $stockRequest->load('items.item.category', 'items.item.mainStocks');

        return view('stock-requests._details', compact('stockRequest'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user->shop_id) {
            return redirect()->route('stock-requests.index')
                ->with('error', 'No active shop is assigned to your account.');
        }

        $items = Item::with(['category', 'unit'])
            ->where('is_admin_item', false)
            ->orderBy('item_name')
            ->get();

        $shop = $user->shop;
        $selectedItemId = $request->get('item_id');

        // Shop Admin's OWN branch stock only (never expose Main Warehouse stock here)
        $myShopStockMap = ShopStock::where('shop_id', $user->shop_id)
            ->where('is_admin_stock', false)
            ->select('item_id', DB::raw('SUM(remaining_quantity) as total_rem'))
            ->groupBy('item_id')
            ->pluck('total_rem', 'item_id')
            ->map(fn ($v) => (int) $v);

        return view('stock-requests.create', compact('items', 'shop', 'selectedItemId', 'myShopStockMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes'            => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        if (!$user->shop_id) {
            return back()->with('error', 'No active shop is assigned to your account.');
        }

        // Merge duplicate product rows cleanly
        $mergedItems = [];
        foreach ($request->items as $row) {
            $itemId = (int) $row['item_id'];
            $qty = (int) $row['quantity'];
            $mergedItems[$itemId] = ($mergedItems[$itemId] ?? 0) + $qty;
        }

        $stockRequest = DB::transaction(function () use ($user, $request, $mergedItems) {
            $stockRequest = StockRequest::create([
                'shop_id'      => $user->shop_id,
                'requested_by' => $user->id,
                'request_date' => now()->toDateString(),
                'status'       => 'pending',
                'notes'        => $request->notes,
            ]);

            foreach ($mergedItems as $itemId => $qty) {
                StockRequestItem::create([
                    'request_id' => $stockRequest->id,
                    'item_id'    => $itemId,
                    'quantity'   => $qty,
                ]);
            }

            return $stockRequest;
        });

        // Notify all sellers of this shop
        $sellers = \App\Models\User::where('shop_id', $user->shop_id)
            ->where('role', 'seller')
            ->get();
        foreach ($sellers as $seller) {
            \App\Models\Notification::create([
                'user_id' => $seller->id,
                'title'   => 'New Stock Request Submitted',
                'message' => "Shop Admin has requested new stock from the main warehouse (Request #{$stockRequest->id}).",
            ]);
        }

        // Notify all owners
        $shopName = $user->shop?->shop_name ?? 'Shop';
        $itemCount = count($mergedItems);
        $totalUnits = array_sum($mergedItems);
        $owners = \App\Models\User::whereIn('role', ['owner', 'system_admin'])->get();
        foreach ($owners as $owner) {
            \App\Models\Notification::create([
                'user_id' => $owner->id,
                'title'   => 'New Stock Request Submitted',
                'message' => "{$shopName} requested {$itemCount} product(s) ({$totalUnits} units) from Main Warehouse (Request #{$stockRequest->id}). Review, regulate quantities, and dispatch.",
            ]);
        }

        return redirect()->route('stock-requests.index')
            ->with('success', "Stock request #{$stockRequest->id} submitted successfully ({$itemCount} product(s), {$totalUnits} units). Awaiting owner review and dispatch.");
    }

    public function show(StockRequest $stockRequest)
    {
        $user = Auth::user();
        if (!$user->isOwner() && (int) $stockRequest->shop_id !== (int) $user->shop_id) {
            abort(403, 'Unauthorized access to this stock request.');
        }

        $stockRequest->load([
            'shop',
            'requester',
            'items.item.category',
            'items.item.unit',
            'transfer.items',
        ]);

        // Map dispatched quantities by item_id if this request has already been dispatched
        $dispatchedByItem = collect();
        if ($stockRequest->transfer) {
            $dispatchedByItem = $stockRequest->transfer->items
                ->groupBy('item_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'));
        }

        return view('stock-requests.show', compact('stockRequest', 'dispatchedByItem'));
    }

    public function approve(Request $request, StockRequest $stockRequest)
    {
        if (!Auth::user()->isOwner()) {
            abort(403, 'Only the Owner can approve and dispatch stock requests.');
        }

        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $stockRequest->load(['shop', 'items.item']);

        $isSelectiveDispatch = $request->boolean('selective_dispatch') || $request->has('selected_items');
        $dispatchPlan = []; // array of ['request_item' => StockRequestItem, 'qty' => int]

        if ($isSelectiveDispatch) {
            $validated = $request->validate([
                'selected_items'   => 'required|array|min:1',
                'selected_items.*' => 'integer',
                'quantities'       => 'nullable|array',
                'quantities.*'     => 'nullable|integer|min:1',
            ], [
                'selected_items.required' => 'Please select at least one product to dispatch.',
                'selected_items.min'      => 'Please select at least one product to dispatch.',
            ]);

            $selectedIds = array_map('intval', $validated['selected_items']);
            $regulatedQuantities = $validated['quantities'] ?? [];

            foreach ($stockRequest->items as $reqItem) {
                if (!in_array((int) $reqItem->id, $selectedIds, true)) {
                    // Unselected products MUST NOT be included in the dispatch items
                    continue;
                }

                $qty = isset($regulatedQuantities[$reqItem->id])
                    ? (int) $regulatedQuantities[$reqItem->id]
                    : (int) $reqItem->quantity;

                if ($qty < 1) {
                    $name = $reqItem->item?->item_name ?? "Item #{$reqItem->item_id}";
                    return back()->withInput()->with('error', "Dispatch quantity for \"{$name}\" must be at least 1.");
                }

                $dispatchPlan[] = [
                    'request_item' => $reqItem,
                    'qty'          => $qty,
                ];
            }

            if (empty($dispatchPlan)) {
                return back()->withInput()->with('error', 'No valid products were selected for dispatch.');
            }
        } else {
            // Backward-compatible direct approval of all requested items
            foreach ($stockRequest->items as $reqItem) {
                $dispatchPlan[] = [
                    'request_item' => $reqItem,
                    'qty'          => (int) $reqItem->quantity,
                ];
            }
        }

        $transfer = null;
        $dispatchedCount = count($dispatchPlan);
        $excludedCount = max(0, $stockRequest->items->count() - $dispatchedCount);
        $totalDispatchedUnits = 0;

        try {
            DB::transaction(function () use ($stockRequest, $dispatchPlan, $isSelectiveDispatch, &$transfer, &$totalDispatchedUnits) {
                $lockedRequest = StockRequest::where('id', $stockRequest->id)->lockForUpdate()->firstOrFail();
                if ($lockedRequest->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                // Create transfer record (dispatched to destination shop)
                $transfer = StockTransfer::create([
                    'from_store'    => 'Main Warehouse',
                    'to_shop'       => $stockRequest->shop_id,
                    'approved_by'   => Auth::id(),
                    'request_id'    => $stockRequest->id,
                    'transfer_date' => now()->toDateString(),
                    'status'        => 'pending_receipt',
                ]);

                foreach ($dispatchPlan as $entry) {
                    /** @var StockRequestItem $requestItem */
                    $requestItem = $entry['request_item'];
                    $item = $requestItem->item;
                    $qty  = (int) $entry['qty'];

                    // Lock Main Warehouse batches (FIFO)
                    $batches = MainStock::where('item_id', $item->id)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('date_received')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $availableInWarehouse = (int) $batches->sum('remaining_quantity');

                    if ($isSelectiveDispatch && $availableInWarehouse < $qty) {
                        throw new \Exception("Insufficient Main Warehouse stock for \"{$item->item_name}\". Available: {$availableInWarehouse}, Dispatch Qty: {$qty}.");
                    }

                    // Get most recent available batch for price info
                    $mainStock = $batches->last() ?: MainStock::where('item_id', $item->id)->orderByDesc('date_received')->first();

                    // Deduct from main stock (FIFO)
                    $remaining = $qty;
                    foreach ($batches as $batch) {
                        if ($remaining <= 0) break;
                        $deduct = min((int) $batch->remaining_quantity, $remaining);
                        $batch->decrement('remaining_quantity', $deduct);
                        $remaining -= $deduct;
                    }

                    // Transfer item record - created ONLY for selected items with regulated quantity
                    StockTransferItem::create([
                        'transfer_id'   => $transfer->id,
                        'item_id'       => $item->id,
                        'quantity'      => $qty,
                        'buying_price'  => $mainStock?->buying_price ?? 0,
                        'selling_price' => $mainStock?->selling_price ?? 0,
                        'status'        => 'pending',
                    ]);

                    $totalDispatchedUnits += $qty;

                    // Audit log
                    StockLog::create([
                        'item_id'          => $item->id,
                        'from_location'    => 'Main Warehouse',
                        'to_location'      => $stockRequest->shop->shop_name,
                        'quantity'         => $qty,
                        'transaction_type' => 'STOCK_TRANSFER',
                        'performed_by'     => Auth::id(),
                        'date'             => now()->toDateString(),
                        'notes'            => "Dispatched {$qty} unit(s) for Request #{$stockRequest->id} (Requested: {$requestItem->quantity})",
                    ]);
                }

                $lockedRequest->update(['status' => 'approved']);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Notify shop admins of the shop (sellers are NOT notified until admin approves receipt)
        $shopAdmins = \App\Models\User::where('shop_id', $stockRequest->shop_id)
            ->where('role', 'shop_admin')
            ->get();
        foreach ($shopAdmins as $admin) {
            $excludedNote = $excludedCount > 0 ? " ({$excludedCount} unselected item(s) omitted)" : '';
            \App\Models\Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Stock Request Approved (Pending Receipt)',
                'message' => "Stock request #{$stockRequest->id} has been approved by the Owner. {$dispatchedCount} product(s) ({$totalDispatchedUnits} units) dispatched in Transfer #{$transfer->id}{$excludedNote} — awaiting your receipt confirmation.",
            ]);
        }

        $msg = "Dispatched {$dispatchedCount} product(s) ({$totalDispatchedUnits} units) to {$stockRequest->shop->shop_name} via Stock Transfer #{$transfer->id}.";
        if ($excludedCount > 0) {
            $msg .= " {$excludedCount} unselected product(s) were excluded from dispatch.";
        }

        return redirect()->route('stock-requests.show', $stockRequest)->with('success', $msg);
    }

    public function reject(Request $request, StockRequest $stockRequest)
    {
        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $stockRequest->update([
            'status' => 'rejected',
            'notes'  => $request->input('reject_reason', $stockRequest->notes),
        ]);

        return redirect()->route('stock-requests.index')
            ->with('success', 'Stock request rejected.');
    }

    public function updateItem(Request $request, \App\Models\StockRequestItem $stockRequestItem)
    {
        if (!Auth::user()->isOwner()) {
            abort(403);
        }

        if ($stockRequestItem->request->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be modified.');
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $stockRequestItem->update([
            'quantity' => $request->quantity,
        ]);

        return back()->with('success', 'Requested quantity updated successfully.');
    }
}
