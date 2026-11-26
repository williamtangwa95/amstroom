<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleBatchAllocation;
use App\Models\SaleReturnBatchAllocation;
use App\Models\ShopStock;
use App\Models\StockLog;
use App\Services\StockAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    /**
     * Display a listing of sale returns.
     */
    public function index()
    {
        $user = Auth::user();
        $query = SaleReturn::query();

        if ($user->isOwner()) {
            $query->whereHas('sale', function ($q) {
                $q->where('is_admin_stock', false);
            });
        } else {
            $query->whereHas('sale', function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id);
            });
        }

        $stats = [
            'total'    => (clone $query)->count(),
            'pending'  => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        return view('sales-returns.index', compact('stats'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $canManage = $user->isShopAdmin() || $user->isOwner();

        $query = SaleReturn::query();

        if ($user->isOwner()) {
            $query->whereHas('sale', function ($q) {
                $q->where('is_admin_stock', false);
            });
        } else {
            $query->whereHas('sale', function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id);
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'approved', 'rejected'], true)) {
            $query->where('sale_returns.status', $request->input('status'));
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $cleanId = preg_replace('/[^0-9]/', '', $searchValue);
                if ($cleanId !== '') {
                    $q->orWhere('sale_returns.id', $cleanId)->orWhere('sale_returns.sale_id', $cleanId);
                }
                $q->orWhere('sale_returns.status', 'like', "%{$searchValue}%")
                  ->orWhere('sale_returns.reason', 'like', "%{$searchValue}%")
                  ->orWhereHas('sale.shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('requester', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('items.item', function ($sq) use ($searchValue) {
                      $sq->where('item_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $returns = $query->with('sale.shop', 'requester', 'approver', 'items.item')
            ->orderBy('sale_returns.return_date', 'desc')
            ->orderBy('sale_returns.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($returns as $index => $ret) {
            $iteration = $start + $index + 1;
            $returnDate = $ret->return_date ? $ret->return_date->format('M d, Y') : 'N/A';
            $saleIdLink = '<a href="' . route('sales.show', $ret->sale_id) . '" class="fw-600">#SL-' . $ret->sale_id . '</a>';
            $shopName = e($ret->sale?->shop?->shop_name ?? 'Main Store (Owner)');

            $itemsHtml = '';
            foreach ($ret->items as $ri) {
                $itemsHtml .= '<div class="small fw-600">' . e($ri->item?->item_name ?? 'Item') . ' <span class="badge bg-secondary ms-1">Qty: ' . $ri->quantity . '</span></div>';
            }

            $reasonHtml = '<div class="small text-muted" style="max-width:200px;word-wrap:break-word;">' . e($ret->reason ?? '') . '</div>';
            $requesterName = e($ret->requester?->name ?? 'System');

            if ($ret->status === 'approved') {
                $statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>';
                if ($ret->approver) {
                    $statusBadge .= '<div class="text-muted mt-1" style="font-size:.68rem;">by ' . e($ret->approver->name) . '</div>';
                }
            } elseif ($ret->status === 'reverted') {
                $statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.72rem;"><i class="bi bi-arrow-counterclockwise me-1"></i>Reverted</span>';
            } elseif ($ret->status === 'rejected') {
                $statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.72rem;"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>';
            } else {
                $statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.72rem;"><i class="bi bi-hourglass-split me-1"></i>Pending Approval</span>';
            }

            $canDeleteReturn = $user->isOwner() || ($user->isShopAdmin() && $user->shop_id == $ret->sale?->shop_id && $ret->isAdminStock());

            $actions = '';
            if ($canManage) {
                $actions .= '<div class="d-flex align-items-center justify-content-center gap-1">';
                if ($ret->status === 'pending') {
                    $actions .= '<form method="POST" action="' . route('sales-returns.approve', $ret) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-success px-2 py-1" onclick="return confirm(\'Confirm approval? Items will be restocked.\')"><i class="bi bi-check-lg"></i> Approve</button></form>';
                    $actions .= '<form method="POST" action="' . route('sales-returns.reject', $ret) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm(\'Confirm rejection?\')"><i class="bi bi-x-lg"></i> Reject</button></form>';
                }
                if ($canDeleteReturn) {
                    $actions .= '<form method="POST" action="' . route('sales-returns.destroy', $ret) . '" class="d-inline">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm(\'Are you sure you want to delete this return record?\')"><i class="bi bi-trash"></i> Delete</button></form>';
                }
                $actions .= '</div>';
            } else {
                $actions = '<span class="text-muted small">—</span>';
            }

            $row = [];
            if ($canManage) {
                $checkboxAttr = $canDeleteReturn ? 'class="form-check-input return-checkbox" value="' . $ret->id . '"' : 'class="form-check-input return-checkbox" disabled style="opacity:0.5;cursor:not-allowed;" title="Shop Admin cannot delete owner stock returns"';
                $row['checkbox'] = '<input type="checkbox" ' . $checkboxAttr . '>';
            }
            $row['iteration'] = $iteration;
            $row['return_date'] = $returnDate;
            $row['sale_id'] = $saleIdLink;
            $row['shop'] = $shopName;
            $row['items'] = $itemsHtml;
            $row['reason'] = $reasonHtml;
            $row['requester'] = $requesterName;
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
     * Show the form for requesting/creating a return for a specific sale.
     */
    public function create(Sale $sale)
    {
        $user = Auth::user();

        // Authorization check: User can only return for their own shop
        if (!$user->isOwner() && $sale->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load('items.item', 'items.components.item', 'shop');
        return view('sales-returns.create', compact('sale'));
    }

    /**
     * Calculate returnable quantity for a single sale item.
     */
    private function getReturnableQuantity(Sale $sale, SaleItem $saleItem): int
    {
        $allocations = SaleBatchAllocation::where('sale_item_id', $saleItem->id)->get();
        if ($allocations->isNotEmpty()) {
            $totalAllocated = (int) $allocations->sum('quantity');
            $totalReturned = (int) SaleReturnBatchAllocation::whereIn('sale_batch_allocation_id', $allocations->pluck('id'))->sum('quantity');
            return max(0, $totalAllocated - $totalReturned);
        }

        $previouslyReturned = (int) SaleReturnItem::whereHas('saleReturn', function ($q) use ($sale) {
            $q->where('sale_id', $sale->id)->whereIn('status', ['approved', 'pending']);
        })->where('item_id', $saleItem->item_id)->sum('quantity');

        return max(0, $saleItem->quantity - $previouslyReturned);
    }

    /**
     * Store the return request.
     */
    public function store(Request $request, Sale $sale)
    {
        $user = Auth::user();

        // Authorization
        if (!$user->isOwner() && $sale->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'reason'               => 'required|string|max:500',
            'items'                => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.qty'          => 'required|integer|min:1',
        ]);

        // Collect submitted items keyed by sale_item_id (deduplicating if needed)
        $entriesBySaleItemId = [];
        foreach ($request->items as $entry) {
            $saleItem = SaleItem::with('item', 'components.item')->findOrFail($entry['sale_item_id']);
            if ($saleItem->sale_id !== $sale->id) {
                return back()->withInput()->withErrors([
                    'items' => "Invalid item for Sale #{$sale->id}."
                ]);
            }

            $qty = (int) $entry['qty'];
            if (isset($entriesBySaleItemId[$saleItem->id])) {
                $entriesBySaleItemId[$saleItem->id]['qty'] += $qty;
            } else {
                $entriesBySaleItemId[$saleItem->id] = [
                    'sale_item' => $saleItem,
                    'qty'       => $qty,
                ];
            }
        }

        // Automatically include attached component items whenever their parent item is selected
        foreach (array_values($entriesBySaleItemId) as $parentEntry) {
            $parentItem = $parentEntry['sale_item'];
            if ($parentItem->parent_id !== null) {
                continue;
            }

            $children = SaleItem::with('item')
                ->where('sale_id', $sale->id)
                ->where('parent_id', $parentItem->id)
                ->get();

            foreach ($children as $childItem) {
                $childReturnable = $this->getReturnableQuantity($sale, $childItem);
                if ($childReturnable <= 0) {
                    continue;
                }

                $isFullParentReturn = $parentEntry['qty'] >= $parentItem->quantity;
                if ($isFullParentReturn) {
                    // When returning the entire parent, all remaining attached quantity must return with it
                    // so child attachments are restored to stock instead of cascade-deleted without restocking.
                    $targetChildQty = min($childItem->quantity, $childReturnable);
                    $entriesBySaleItemId[$childItem->id] = [
                        'sale_item' => $childItem,
                        'qty'       => $targetChildQty,
                    ];
                } elseif (!isset($entriesBySaleItemId[$childItem->id])) {
                    // Partial parent return: calculate proportional attachment quantity
                    $proportionalQty = (int) round(($childItem->quantity / max(1, $parentItem->quantity)) * $parentEntry['qty']);
                    $targetChildQty = max(1, min($childItem->quantity, $childReturnable, max(1, $proportionalQty)));
                    $entriesBySaleItemId[$childItem->id] = [
                        'sale_item' => $childItem,
                        'qty'       => $targetChildQty,
                    ];
                }
            }
        }

        // Validate returned quantities against sale quantities & returnable allocations
        foreach ($entriesBySaleItemId as $entry) {
            $saleItem = $entry['sale_item'];
            $returnable = $this->getReturnableQuantity($sale, $saleItem);

            if ($entry['qty'] > $returnable) {
                $itemName = $saleItem->display_name;
                return back()->withInput()->withErrors([
                    'items' => "Unable to process return for \"{$itemName}\". Returnable quantity: {$returnable}, Requested return: {$entry['qty']}. No stock was restored."
                ]);
            }
        }

        // Determine initial status: Admin/Owner auto-approves, Seller is pending
        $isAdminOrOwner = $user->isOwner() || $user->isShopAdmin();
        $status = $isAdminOrOwner ? 'approved' : 'pending';

        try {
            $saleReturn = DB::transaction(function () use ($request, $sale, $user, $status, $isAdminOrOwner, $entriesBySaleItemId) {
                $saleReturn = SaleReturn::create([
                    'sale_id'      => $sale->id,
                    'requested_by' => $user->id,
                    'approved_by'  => $isAdminOrOwner ? $user->id : null,
                    'status'       => $status,
                    'reason'       => $request->reason,
                    'return_date'  => now()->toDateString(),
                ]);

                // Process child/attachment items BEFORE parent items so parent deletion
                // never cascades and removes an attachment before its stock is restored.
                $orderedEntries = collect($entriesBySaleItemId)
                    ->sortBy(fn ($entry) => $entry['sale_item']->parent_id !== null ? 0 : 1)
                    ->values();

                // Lock and load all targeted SaleItems up-front before any deletes occur
                $saleItemIds = $orderedEntries->map(fn ($e) => $e['sale_item']->id)->all();
                $lockedSaleItems = SaleItem::whereIn('id', $saleItemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Phase 1: Create SaleReturnItem records & restore stock allocations for all items
                $pendingDeletions = [];
                foreach ($orderedEntries as $entry) {
                    $saleItem = $lockedSaleItems->get($entry['sale_item']->id);
                    if (!$saleItem) {
                        throw new \Exception("Sale item #{$entry['sale_item']->id} could not be found for return.");
                    }

                    $returnItem = SaleReturnItem::create([
                        'sale_return_id' => $saleReturn->id,
                        'item_id'        => $saleItem->item_id,
                        'quantity'       => $entry['qty'],
                    ]);

                    if ($isAdminOrOwner) {
                        // Restore exact batch allocations while all sale_items and allocations still exist
                        StockAllocationService::restoreReturnAllocations(
                            $saleReturn,
                            $returnItem,
                            $saleItem,
                            $entry['qty'],
                            $user->id
                        );

                        $pendingDeletions[] = [
                            'sale_item' => $saleItem,
                            'qty'       => $entry['qty'],
                        ];
                    }
                }

                // Phase 2: Decrement or delete SaleItem records (children first, then parents)
                if ($isAdminOrOwner) {
                    foreach ($pendingDeletions as $step) {
                        $saleItem = $step['sale_item'];
                        $qty = $step['qty'];

                        if ($saleItem->quantity > $qty) {
                            $saleItem->decrement('quantity', $qty);
                        } else {
                            $saleItem->delete();
                        }
                    }

                    $remainingCount = $sale->items()->count();
                    if ($remainingCount === 0) {
                        $sale->delete();
                    } else {
                        $newTotal = $sale->items()->get()->sum(fn($i) => $i->quantity * $i->selling_price);
                        $sale->update(['total_amount' => $newTotal]);
                    }
                }

                return $saleReturn;
            });
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        if (!$isAdminOrOwner) {
            $admins = \App\Models\User::where('shop_id', $sale->shop_id)
                ->where('role', 'shop_admin')
                ->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title'   => 'New Sales Return Request',
                    'message' => "Seller {$user->name} has submitted a return request for Sale #{$sale->id} (Return #{$saleReturn->id}).",
                ]);
            }
        }

        $msg = $isAdminOrOwner 
            ? 'Sale return processed and approved. Shop stock updated successfully.' 
            : 'Sale return request submitted successfully. Awaiting Admin approval.';

        return redirect()->route('sales-returns.index')->with('success', $msg);
    }

    /**
     * Admin: Approve the pending return request.
     */
    public function approve(SaleReturn $saleReturn)
    {
        $user = Auth::user();

        // Authorization: Admin or Owner of the target shop
        if (!$user->isOwner() && (!$user->isShopAdmin() || $user->shop_id !== $saleReturn->sale->shop_id)) {
            abort(403, 'Unauthorized action.');
        }

        if ($saleReturn->status !== 'pending') {
            return back()->with('error', 'This return request has already been processed.');
        }

        try {
            DB::transaction(function () use ($saleReturn, $user) {
                $saleReturn->update([
                    'status'      => 'approved',
                    'approved_by' => $user->id,
                ]);

                $sale = $saleReturn->sale;

                // Load and lock all current sale items up-front before any deletes occur
                $saleItems = SaleItem::where('sale_id', $sale->id)
                    ->lockForUpdate()
                    ->get();

                $matchedSaleItemIds = [];
                $restorationPlan = [];

                foreach ($saleReturn->items as $returnItem) {
                    $saleItem = $saleItems->first(function ($candidate) use ($returnItem, $matchedSaleItemIds) {
                        return $candidate->item_id === $returnItem->item_id
                            && !in_array($candidate->id, $matchedSaleItemIds, true);
                    });

                    if ($saleItem) {
                        $matchedSaleItemIds[] = $saleItem->id;
                        $restorationPlan[$saleItem->id] = [
                            'return_item' => $returnItem,
                            'sale_item'   => $saleItem,
                            'qty'         => (int) $returnItem->quantity,
                        ];
                    }
                }

                // Safety net: if a parent item is being fully returned, ensure any remaining
                // attached child components are also returned and restocked before parent deletion.
                foreach (array_values($restorationPlan) as $planEntry) {
                    $parentItem = $planEntry['sale_item'];
                    if ($parentItem->parent_id !== null || $planEntry['qty'] < $parentItem->quantity) {
                        continue;
                    }

                    $attachedChildren = $saleItems->where('parent_id', $parentItem->id);
                    foreach ($attachedChildren as $childItem) {
                        if (isset($restorationPlan[$childItem->id])) {
                            continue;
                        }

                        $childReturnable = $this->getReturnableQuantity($sale, $childItem);
                        $childQty = min($childItem->quantity, $childReturnable);
                        if ($childQty <= 0) {
                            continue;
                        }

                        $childReturnItem = SaleReturnItem::create([
                            'sale_return_id' => $saleReturn->id,
                            'item_id'        => $childItem->item_id,
                            'quantity'       => $childQty,
                        ]);

                        $restorationPlan[$childItem->id] = [
                            'return_item' => $childReturnItem,
                            'sale_item'   => $childItem,
                            'qty'         => $childQty,
                        ];
                    }
                }

                // Process child/attachment items BEFORE parent items so parent deletion
                // never cascades and removes an attachment before its stock is restored.
                $orderedPlan = collect($restorationPlan)
                    ->sortBy(fn ($entry) => $entry['sale_item']->parent_id !== null ? 0 : 1)
                    ->values();

                // Phase 1: Restore batch allocations while all sale_items still exist
                foreach ($orderedPlan as $entry) {
                    StockAllocationService::restoreReturnAllocations(
                        $saleReturn,
                        $entry['return_item'],
                        $entry['sale_item'],
                        $entry['qty'],
                        $user->id
                    );
                }

                // Phase 2: Decrement or delete SaleItem records (children first, then parents)
                foreach ($orderedPlan as $entry) {
                    $saleItem = $entry['sale_item'];
                    $qty = $entry['qty'];

                    if ($saleItem->quantity > $qty) {
                        $saleItem->decrement('quantity', $qty);
                    } else {
                        $saleItem->delete();
                    }
                }

                $remainingCount = $sale->items()->count();
                if ($remainingCount === 0) {
                    $sale->delete();
                } else {
                    $newTotal = $sale->items()->get()->sum(fn($i) => $i->quantity * $i->selling_price);
                    $sale->update(['total_amount' => $newTotal]);
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve return: ' . $e->getMessage());
        }

        return redirect()->route('sales-returns.index')
            ->with('success', 'Sale return approved and shop stock updated successfully.');
    }

    /**
     * Admin: Reject the pending return request.
     */
    public function reject(SaleReturn $saleReturn)
    {
        $user = Auth::user();

        // Authorization: Admin or Owner of the target shop
        if (!$user->isOwner() && (!$user->isShopAdmin() || $user->shop_id !== $saleReturn->sale->shop_id)) {
            abort(403, 'Unauthorized action.');
        }

        if ($saleReturn->status !== 'pending') {
            return back()->with('error', 'This return request has already been processed.');
        }

        $saleReturn->update([
            'status' => 'rejected',
        ]);

        return redirect()->route('sales-returns.index')
            ->with('success', 'Sale return request rejected.');
    }

    /**
     * Admin: Delete a single sale return record.
     */
    public function destroy(SaleReturn $saleReturn)
    {
        $user = Auth::user();

        // Authorization: Admin or Owner of the target shop
        if (!$user->isOwner() && (!$user->isShopAdmin() || $user->shop_id !== $saleReturn->sale?->shop_id)) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->isShopAdmin() && !$saleReturn->isAdminStock()) {
            return redirect()->route('sales-returns.index')
                ->with('error', 'Shop Admins cannot delete sales return records associated with Owner stock.');
        }

        $saleReturn->delete();

        return redirect()->route('sales-returns.index')
            ->with('success', 'Sale return record deleted successfully.');
    }

    /**
     * Admin: Bulk delete multiple sale return records.
     */
    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sale_returns,id',
        ]);

        $errors = [];
        $validIds = [];

        // Authorization check for each item
        foreach ($request->ids as $id) {
            $saleReturn = SaleReturn::find($id);
            if (!$saleReturn) continue;

            if (!$user->isOwner() && (!$user->isShopAdmin() || $user->shop_id !== $saleReturn->sale?->shop_id)) {
                $errors[] = "Return #{$id}: Unauthorized action.";
                continue;
            }

            if ($user->isShopAdmin() && !$saleReturn->isAdminStock()) {
                $errors[] = "Return #{$id}: Shop Admins cannot delete sales return records associated with Owner stock.";
                continue;
            }

            $validIds[] = $id;
        }

        if (!empty($errors)) {
            return redirect()->route('sales-returns.index')
                ->with('error', implode(' ', $errors));
        }

        if (!empty($validIds)) {
            SaleReturn::destroy($validIds);
        }

        return redirect()->route('sales-returns.index')
            ->with('success', 'Selected sale return records deleted successfully.');
    }

    /**
     * Helper to return items to shop stock.
     */
    private function stabilizeStock($shopId, $itemId, $quantity, Sale $sale)
    {
        $saleItem = SaleItem::where('sale_id', $sale->id)->where('item_id', $itemId)->first();
        $itemIsAdminStock = $saleItem ? (bool) $saleItem->is_admin_stock : (bool) $sale->is_admin_stock;

        $shopStock = ShopStock::where('shop_id', $shopId)
            ->where('item_id', $itemId)
            ->where('is_admin_stock', $itemIsAdminStock)
            ->first();

        if ($shopStock) {
            $shopStock->increment('remaining_quantity', $quantity);
        } else {
            // In case stock record doesn't exist (edge case), create one
            ShopStock::create([
                'shop_id'            => $shopId,
                'item_id'            => $itemId,
                'buying_price'       => 0,
                'selling_price'      => $saleItem?->selling_price ?? 0,
                'quantity'           => $quantity,
                'remaining_quantity' => $quantity,
                'date_received'      => now()->toDateString(),
                'is_admin_stock'     => $itemIsAdminStock,
            ]);
        }

        // Add transaction log
        StockLog::create([
            'item_id'          => $itemId,
            'from_location'    => $sale->customer_name ?: 'Walk-in Customer',
            'to_location'      => $sale->shop->shop_name,
            'quantity'         => $quantity,
            'transaction_type' => 'STOCK_RECEIVED',
            'performed_by'     => Auth::id(),
            'date'             => now()->toDateString(),
            'notes'            => "Customer Sale Return (Sale #{$sale->id})",
            'is_admin_stock'   => $itemIsAdminStock,
        ]);
    }
}
