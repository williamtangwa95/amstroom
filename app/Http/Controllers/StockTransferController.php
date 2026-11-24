<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StockTransferController extends Controller
{
    /**
     * List all stock transfers.
     */
    public function index()
    {
        $user = Auth::user();

        $shops = Shop::active()->orderBy('shop_name')->get();
        $items = Item::where('is_admin_item', false)->orderBy('item_name')->get();
        $dispatchers = User::whereIn('role', ['owner', 'system_admin'])
            ->orWhereIn('id', StockTransfer::select('approved_by')->whereNotNull('approved_by')->distinct())
            ->orderBy('name')
            ->get();

        $summaryQuery = StockTransfer::query();
        if ($user->isShopAdmin() || $user->isSeller()) {
            $summaryQuery->where('to_shop', $user->shop_id);
        }

        $stats = [
            'total'    => (clone $summaryQuery)->count(),
            'pending'  => (clone $summaryQuery)->whereIn('status', ['pending_receipt', 'partially_received'])->count(),
            'received' => (clone $summaryQuery)->where('status', 'received')->count(),
            'rejected' => (clone $summaryQuery)->where('status', 'rejected')->count(),
        ];

        return view('stock-transfers.index', compact('shops', 'items', 'dispatchers', 'stats'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $isOwner = $user->isOwner();

        $query = StockTransfer::query();

        if ($user->isShopAdmin() || $user->isSeller()) {
            $query->where('stock_transfers.to_shop', $user->shop_id);
        }

        $recordsTotal = (clone $query)->count();

        // Filter by destination shop
        $shopId = $request->input('shop_id', $request->input('to_shop'));
        if (!empty($shopId)) {
            $query->where('stock_transfers.to_shop', $shopId);
        }

        // Filter by item / product
        $itemId = $request->input('item_id');
        if (!empty($itemId)) {
            $query->whereHas('items', function ($iq) use ($itemId) {
                $iq->where('item_id', $itemId);
            });
        }

        // Filter by dispatched by
        $dispatchedBy = $request->input('dispatched_by', $request->input('approved_by'));
        if (!empty($dispatchedBy)) {
            $query->where('stock_transfers.approved_by', $dispatchedBy);
        }

        // Filter by status
        $statusFilter = $request->input('status');
        if (!empty($statusFilter)) {
            if ($statusFilter === 'awaiting') {
                $query->whereIn('stock_transfers.status', ['pending_receipt', 'partially_received']);
            } else {
                $query->where('stock_transfers.status', $statusFilter);
            }
        }

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $cleanId = preg_replace('/[^0-9]/', '', $searchValue);
                if ($cleanId !== '') {
                    $q->orWhere('stock_transfers.id', $cleanId);
                }
                $q->orWhere('stock_transfers.status', 'like', "%{$searchValue}%")
                  ->orWhere('stock_transfers.from_store', 'like', "%{$searchValue}%")
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('approver', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('items.item', function ($iq) use ($searchValue) {
                      $iq->where('item_name', 'like', "%{$searchValue}%")
                         ->orWhere('brand', 'like', "%{$searchValue}%")
                         ->orWhere('model', 'like', "%{$searchValue}%")
                         ->orWhere('specification', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = $request->input('order.0.column', $isOwner ? 2 : 1);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        switch ((int) $orderColumnIndex) {
            case ($isOwner ? 1 : 0):
                $query->orderBy('stock_transfers.id', $orderDirection);
                break;
            case ($isOwner ? 2 : 1):
                $query->orderBy('stock_transfers.transfer_date', $orderDirection);
                break;
            case ($isOwner ? 3 : 2):
                $query->leftJoin('shops', 'shops.id', '=', 'stock_transfers.to_shop')
                      ->orderBy('shops.shop_name', $orderDirection)
                      ->select('stock_transfers.*');
                break;
            case ($isOwner ? 5 : 4):
                $query->leftJoin('users', 'users.id', '=', 'stock_transfers.approved_by')
                      ->orderBy('users.name', $orderDirection)
                      ->select('stock_transfers.*');
                break;
            case ($isOwner ? 6 : 5):
                $query->orderBy('stock_transfers.status', $orderDirection);
                break;
            default:
                $query->orderBy('stock_transfers.transfer_date', $orderDirection)->orderBy('stock_transfers.id', $orderDirection);
                break;
        }

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $transfers = $query->with(['shop', 'approver', 'items.item'])
            ->withCount(['items', 'pendingItems', 'receivedItems', 'rejectedItems'])
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($transfers as $index => $transfer) {
            $iteration = $start + $index + 1;
            $dateLabel = $transfer->transfer_date ? $transfer->transfer_date->format('M d, Y') : 'N/A';
            $isAwaiting = in_array($transfer->status, ['pending_receipt', 'partially_received'], true);
            $waitSub = ($isAwaiting && $transfer->created_at)
                ? '<div class="text-warning-emphasis" style="font-size:.69rem; font-weight:600; margin-top:2px;"><i class="bi bi-clock me-1"></i>' . e($transfer->created_at->diffForHumans()) . '</div>'
                : ($transfer->created_at ? '<div class="text-muted" style="font-size:.69rem; margin-top:2px;">' . e($transfer->created_at->format('h:i A')) . '</div>' : '');
            $transferDate = '<div class="fw-600 text-dark" style="white-space:nowrap;">' . e($dateLabel) . '</div>' . $waitSub;

            $destinationShop = '<div class="d-inline-flex align-items-center gap-1 fw-600 text-dark">'
                . '<span class="d-inline-flex align-items-center justify-content-center rounded-2" style="width:24px;height:24px;background:rgba(0,119,182,.1);color:#0077b6;flex-shrink:0;"><i class="bi bi-shop" style="font-size:.75rem;"></i></span>'
                . '<span>' . e($transfer->shop?->shop_name ?? 'N/A') . '</span>'
                . '</div>';

            $dispatchedBy = '<div class="d-inline-flex align-items-center gap-1 text-secondary" style="font-size:.8rem; font-weight:500;">'
                . '<i class="bi bi-person-circle text-muted"></i> '
                . '<span>' . e($transfer->approver?->name ?? 'System') . '</span>'
                . '</div>';

            $itemRows = [];
            foreach ($transfer->items as $tItem) {
                $itemName = $tItem->item?->item_name ?? 'Unknown Item';
                $qty = (int) $tItem->quantity;
                $statusTag = '';
                if ($tItem->status === 'pending') {
                    $statusTag = ' <span class="badge-soft-warning" style="font-size:.62rem; padding:.12rem .4rem;">pending</span>';
                } elseif ($tItem->status === 'rejected') {
                    $statusTag = ' <span class="badge-soft-danger" style="font-size:.62rem; padding:.12rem .4rem;">rejected</span>';
                }

                $itemRows[] = '<div class="d-flex align-items-center gap-1 mb-1 flex-wrap" style="font-size:.78rem;">'
                    . '<i class="bi bi-box-seam text-muted" style="font-size:.75rem;"></i>'
                    . '<span class="fw-600 text-dark" title="' . e($itemName) . '">' . e($itemName) . '</span>'
                    . '<span class="badge bg-light text-secondary border" style="font-size:.66rem; font-weight:700;">Qty: ' . $qty . '</span>'
                    . $statusTag
                    . '</div>';
            }

            if (empty($itemRows)) {
                $itemsHtml = '<span class="text-muted small">No items</span>';
            } elseif (count($itemRows) <= 3) {
                $itemsHtml = '<div class="d-flex flex-column py-1">' . implode('', $itemRows) . '</div>';
            } else {
                $visible = array_slice($itemRows, 0, 3);
                $remaining = count($itemRows) - 3;
                $itemsHtml = '<div class="d-flex flex-column py-1">'
                    . implode('', $visible)
                    . '<span class="text-primary fw-600" style="font-size:.71rem;">+' . $remaining . ' more item' . ($remaining > 1 ? 's' : '') . '</span>'
                    . '</div>';
            }

            if ($transfer->status === 'received') {
                $statusBadge = '<span class="badge-soft-success" style="font-size:.7rem;"><i class="bi bi-check-circle-fill"></i> Received</span>';
            } elseif ($transfer->status === 'rejected') {
                $statusBadge = '<span class="badge-soft-danger" style="font-size:.7rem;"><i class="bi bi-x-circle-fill"></i> Rejected</span>';
            } elseif ($transfer->status === 'partially_received') {
                $statusBadge = '<span class="badge-soft-info" style="font-size:.7rem;"><i class="bi bi-pie-chart-fill"></i> Partial</span>';
            } else {
                $statusBadge = '<span class="badge-soft-warning" style="font-size:.7rem;"><i class="bi bi-hourglass-split"></i> Pending Receipt</span>';
            }

            $showUrl = route('stock-transfers.show', $transfer);
            $deleteUrl = route('stock-transfers.destroy', $transfer);

            if ($user->isShopAdmin() && $isAwaiting) {
                $viewBtn = '<a href="' . $showUrl . '" class="btn btn-sm btn-accent" style="padding:.28rem .68rem; font-size:.75rem;"><i class="bi bi-check2-circle me-1"></i> Review</a>';
            } else {
                $viewBtn = '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-custom" style="padding:.28rem .68rem; font-size:.75rem;"><i class="bi bi-eye me-1"></i> View</a>';
            }

            $actions = '<div class="action-btn-group justify-content-end">' . $viewBtn;
            if ($isOwner) {
                $actions .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-transfer" data-url="' . $deleteUrl . '" data-id="' . $transfer->id . '" title="Delete transfer" style="padding:.28rem .58rem; font-size:.75rem;"><i class="bi bi-trash-fill"></i></button>';
            }
            $actions .= '</div>';

            $row = [];
            if ($isOwner) {
                $row['checkbox'] = '<input type="checkbox" class="form-check-input transfer-checkbox" value="' . $transfer->id . '" style="cursor:pointer;">';
            }
            $row['iteration'] = '<span class="fw-700 text-muted">#' . $transfer->id . '</span>';
            $row['transfer_date'] = $transferDate;
            $row['destination_shop'] = $destinationShop;
            $row['items'] = $itemsHtml;
            $row['dispatched_by'] = $dispatchedBy;
            $row['status'] = $statusBadge;
            $row['actions'] = $actions;

            $data[] = $row;
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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
        $idempotencyKey = $request->input('idempotency_key');
        if ($idempotencyKey) {
            $existingId = \Illuminate\Support\Facades\Cache::get("idempotency_transfer_{$idempotencyKey}");
            if ($existingId) {
                return redirect()->route('transfers.show', $existingId)
                    ->with('success', 'Stock transfer already submitted!');
            }
        }

        $request->validate([
            'idempotency_key' => 'nullable|string|max:64',
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

        DB::transaction(function () use ($request, $shop, $shopId, $idempotencyKey) {
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

            if ($idempotencyKey) {
                \Illuminate\Support\Facades\Cache::put("idempotency_transfer_{$idempotencyKey}", $transfer->id, now()->addMinutes(10));
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
     * Owner: Delete a transfer item (pending, rejected, or received) and restore stock to main warehouse.
     */
    public function deleteItem(StockTransferItem $transferItem)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Only the owner can delete transfer items.');
        }

        $transfer = $transferItem->transfer;
        if ($transfer) {
            $transfer->load('shop');
        }

        $transferId      = $transfer?->id;
        $transferDeleted = false;

        DB::transaction(function () use ($transferItem, $transfer, $user, &$transferDeleted) {
            if ($transferItem->status === 'received' && $transfer) {
                // Deduct from shop stock
                $shopStock = ShopStock::where('shop_id', $transfer->to_shop)
                    ->where('item_id', $transferItem->item_id)
                    ->first();

                if ($shopStock) {
                    $shopStock->quantity = max(0, $shopStock->quantity - $transferItem->quantity);
                    $shopStock->remaining_quantity = max(0, $shopStock->remaining_quantity - $transferItem->quantity);
                    if ($shopStock->quantity <= 0 && $shopStock->remaining_quantity <= 0) {
                        $shopStock->delete();
                    } else {
                        $shopStock->save();
                    }
                }

                // Restore stock to main warehouse
                $this->restoreMainStock($transferItem->item_id, $transferItem->quantity);

                StockLog::create([
                    'item_id'          => $transferItem->item_id,
                    'from_location'    => $transfer->shop?->shop_name ?? 'Shop',
                    'to_location'      => 'Main Warehouse (Item Deleted)',
                    'quantity'         => $transferItem->quantity,
                    'transaction_type' => 'ADJUSTMENT',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => "Received transfer item removed from shop stock & returned to Main Warehouse (Transfer #{$transfer->id})",
                ]);
            } else {
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
            }

            $transferItem->delete();

            // Check if transfer now has no items — if so, delete the transfer too
            $remainingItems = $transfer->items()->count();
            if ($remainingItems === 0) {
                $transfer->delete();
                $transferDeleted = true;
            } else {
                $this->updateTransferStatus($transfer);
            }
        });

        if ($transferDeleted) {
            return redirect()->route('stock-transfers.index')
                ->with('success', 'Last item removed. Transfer has been deleted as it contained no more items.');
        }

        return back()->with('success', 'Item removed from transfer and stock returned to Main Warehouse.');
    }

    /**
     * Owner: Delete a transfer (even if fully or partially received).
     */
    public function destroyTransfer(StockTransfer $stockTransfer)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Only the owner can delete transfers.');
        }

        $itemsCount = $stockTransfer->items()->count();

        DB::transaction(function () use ($stockTransfer, $user) {
            foreach ($stockTransfer->items as $item) {
                if ($item->status === 'received') {
                    // Revert shop stock for received items
                    $shopStock = ShopStock::where('shop_id', $stockTransfer->to_shop)
                        ->where('item_id', $item->item_id)
                        ->first();

                    if ($shopStock) {
                        $shopStock->quantity = max(0, $shopStock->quantity - $item->quantity);
                        $shopStock->remaining_quantity = max(0, $shopStock->remaining_quantity - $item->quantity);
                        if ($shopStock->quantity <= 0 && $shopStock->remaining_quantity <= 0) {
                            $shopStock->delete();
                        } else {
                            $shopStock->save();
                        }
                    }

                    $this->restoreMainStock($item->item_id, $item->quantity);

                    StockLog::create([
                        'item_id'          => $item->item_id,
                        'from_location'    => $stockTransfer->shop?->shop_name ?? 'Shop',
                        'to_location'      => 'Main Warehouse (Transfer Deleted)',
                        'quantity'         => $item->quantity,
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by'     => $user->id,
                        'date'             => now()->toDateString(),
                        'notes'            => "Transfer #{$stockTransfer->id} deleted — received item stock removed from shop stock and returned to Main Warehouse.",
                    ]);
                } else {
                    // Restore warehouse stock for pending/rejected items
                    $this->restoreMainStock($item->item_id, $item->quantity);

                    StockLog::create([
                        'item_id'          => $item->item_id,
                        'from_location'    => 'Main Warehouse',
                        'to_location'      => $stockTransfer->shop?->shop_name ?? 'Shop',
                        'quantity'         => $item->quantity,
                        'transaction_type' => 'ADJUSTMENT',
                        'performed_by'     => $user->id,
                        'date'             => now()->toDateString(),
                        'notes'            => "Transfer #{$stockTransfer->id} deleted — stock returned to Main Warehouse.",
                    ]);
                }
            }

            $stockTransfer->items()->delete();
            $stockTransfer->delete();
        });

        $msg = $itemsCount > 0
            ? "Transfer deleted. {$itemsCount} item(s) stock returned to Main Warehouse."
            : 'Empty transfer deleted.';

        return redirect()->route('stock-transfers.index')->with('success', $msg);
    }

    /**
     * Owner: Bulk delete transfers (revert shop stock if received & restore main warehouse stock).
     */
    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Only the owner can delete transfers.');
        }

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:stock_transfers,id',
        ]);

        $deletedCount = 0;
        $totalItemsCount = 0;

        DB::transaction(function () use ($request, $user, &$deletedCount, &$totalItemsCount) {
            $transfers = StockTransfer::with('items', 'shop')->whereIn('id', $request->ids)->get();

            foreach ($transfers as $stockTransfer) {
                $totalItemsCount += $stockTransfer->items->count();

                foreach ($stockTransfer->items as $item) {
                    if ($item->status === 'received') {
                        // Revert shop stock for received items
                        $shopStock = ShopStock::where('shop_id', $stockTransfer->to_shop)
                            ->where('item_id', $item->item_id)
                            ->first();

                        if ($shopStock) {
                            $shopStock->quantity = max(0, $shopStock->quantity - $item->quantity);
                            $shopStock->remaining_quantity = max(0, $shopStock->remaining_quantity - $item->quantity);
                            if ($shopStock->quantity <= 0 && $shopStock->remaining_quantity <= 0) {
                                $shopStock->delete();
                            } else {
                                $shopStock->save();
                            }
                        }

                        $this->restoreMainStock($item->item_id, $item->quantity);

                        StockLog::create([
                            'item_id'          => $item->item_id,
                            'from_location'    => $stockTransfer->shop?->shop_name ?? 'Shop',
                            'to_location'      => 'Main Warehouse (Bulk Delete)',
                            'quantity'         => $item->quantity,
                            'transaction_type' => 'ADJUSTMENT',
                            'performed_by'     => $user->id,
                            'date'             => now()->toDateString(),
                            'notes'            => "Transfer #{$stockTransfer->id} bulk deleted — received item stock removed from shop stock and returned to Main Warehouse.",
                        ]);
                    } else {
                        // Restore warehouse stock for pending/rejected items
                        $this->restoreMainStock($item->item_id, $item->quantity);

                        StockLog::create([
                            'item_id'          => $item->item_id,
                            'from_location'    => 'Main Warehouse',
                            'to_location'      => $stockTransfer->shop?->shop_name ?? 'Shop',
                            'quantity'         => $item->quantity,
                            'transaction_type' => 'ADJUSTMENT',
                            'performed_by'     => $user->id,
                            'date'             => now()->toDateString(),
                            'notes'            => "Transfer #{$stockTransfer->id} bulk deleted — stock returned to Main Warehouse.",
                        ]);
                    }
                }

                $stockTransfer->items()->delete();
                $stockTransfer->delete();
                $deletedCount++;
            }
        });

        $msg = "Successfully deleted {$deletedCount} stock transfer(s). Stock for {$totalItemsCount} item(s) has been returned to the Main Warehouse.";

        return redirect()->route('stock-transfers.index')->with('success', $msg);
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
     * Return pending stock transfers awaiting confirmation by the current Shop Admin.
     */
    public function pendingReminders(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isShopAdmin() || !$user->shop_id) {
            return response()->json([
                'due_transfers' => [],
                'total_pending' => 0,
                'ringtone_url'  => null,
                'index_url'     => route('stock-transfers.index'),
                'server_now_ts' => now()->timestamp,
            ]);
        }

        $now = now();
        $transfers = StockTransfer::with(['shop', 'approver', 'pendingItems.item.unit'])
            ->withCount(['items', 'receivedItems', 'pendingItems'])
            ->where('to_shop', $user->shop_id)
            ->whereIn('status', ['pending_receipt', 'partially_received'])
            ->whereHas('pendingItems')
            ->orderBy('created_at', 'asc')
            ->get();

        $dueTransfers = [];
        foreach ($transfers as $transfer) {
            $cacheKey = "stock_transfer_reminder_snooze_{$user->id}_{$transfer->id}";
            $snoozedUntil = Cache::get($cacheKey);
            if ($snoozedUntil && (int) $snoozedUntil > $now->timestamp) {
                continue;
            }

            $submittedAt = $transfer->created_at ?? $now;
            $elapsedSeconds = max(0, (int) $submittedAt->diffInSeconds($now));

            $pendingItems = $transfer->pendingItems;
            $itemsPreview = $pendingItems->take(5)->map(function ($tItem) {
                $item = $tItem->item;
                $metaParts = array_filter([$item?->brand, $item?->model]);
                return [
                    'name'          => $item?->item_name ?? 'Product',
                    'meta'          => implode(' · ', $metaParts),
                    'image_url'     => $item && $item->image_path ? asset('media/' . $item->image_path) : null,
                    'qty'           => (int) $tItem->quantity,
                    'unit'          => $item?->unit?->symbol ?? 'pcs',
                    'selling_price' => $tItem->selling_price !== null ? (float) $tItem->selling_price : null,
                ];
            })->values()->all();

            $dueTransfers[] = [
                'id'                   => $transfer->id,
                'url'                  => route('stock-transfers.show', $transfer),
                'status'               => $transfer->status,
                'from_store'           => $transfer->from_store ?: 'Main Store',
                'shop_name'            => $transfer->shop?->shop_name ?? 'Your Branch',
                'dispatched_by'        => $transfer->approver?->name ?? 'Main Store',
                'transfer_date'        => $transfer->transfer_date ? $transfer->transfer_date->format('M d, Y') : $submittedAt->format('M d, Y'),
                'submitted_at_ts'      => $submittedAt->timestamp,
                'submitted_at_display' => $submittedAt->format('M d, Y · h:i A'),
                'elapsed_seconds'      => $elapsedSeconds,
                'elapsed_human'        => $this->formatElapsedDuration($elapsedSeconds),
                'total_items_count'    => (int) $transfer->items_count,
                'received_items_count' => (int) $transfer->received_items_count,
                'pending_items_count'  => (int) $transfer->pending_items_count,
                'pending_total_qty'    => (int) $pendingItems->sum('quantity'),
                'items_preview'        => $itemsPreview,
                'extra_items_count'    => max(0, $pendingItems->count() - 5),
            ];
        }

        $customRingtone = Setting::get('notification_sound_user_' . $user->id);
        $ringtoneUrl = null;
        if ($customRingtone && Storage::disk('public')->exists($customRingtone)) {
            $ringtoneUrl = asset('media/' . $customRingtone);
        }

        return response()->json([
            'due_transfers' => $dueTransfers,
            'total_pending' => $transfers->count(),
            'shop_name'     => $user->shop?->shop_name ?? 'Your Branch',
            'ringtone_url'  => $ringtoneUrl,
            'index_url'     => route('stock-transfers.index'),
            'server_now_ts' => $now->timestamp,
        ]);
    }

    /**
     * Dismiss (snooze) pending stock transfer reminders for 3 hours for the current Shop Admin.
     */
    public function dismissPendingReminders(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->isShopAdmin() || !$user->shop_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'transfer_ids'   => 'required|array|min:1',
            'transfer_ids.*' => 'integer',
        ]);

        $snoozeUntil = now()->addHours(3);
        $validIds = StockTransfer::where('to_shop', $user->shop_id)
            ->whereIn('id', $validated['transfer_ids'])
            ->pluck('id');

        foreach ($validIds as $transferId) {
            $cacheKey = "stock_transfer_reminder_snooze_{$user->id}_{$transferId}";
            Cache::put($cacheKey, $snoozeUntil->timestamp, $snoozeUntil);
        }

        return response()->json([
            'success'       => true,
            'snoozed_until' => $snoozeUntil->timestamp,
            'snooze_hours'  => 3,
        ]);
    }

    /**
     * Helper: format elapsed seconds into a clear human-readable waiting duration.
     */
    private function formatElapsedDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return 'Just now';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hr' : 'hrs');
        }
        if ($days === 0 && $minutes > 0) {
            $parts[] = $minutes . ' ' . ($minutes === 1 ? 'min' : 'mins');
        }

        return implode(', ', $parts) ?: 'Less than a minute';
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
