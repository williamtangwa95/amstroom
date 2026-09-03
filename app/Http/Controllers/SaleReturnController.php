<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\ShopStock;
use App\Models\StockLog;
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
        return view('sales-returns.index');
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

            $actions = '';
            if ($canManage) {
                $actions .= '<div class="d-flex align-items-center justify-content-center gap-1">';
                if ($ret->status === 'pending') {
                    $actions .= '<form method="POST" action="' . route('sales-returns.approve', $ret) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-success px-2 py-1" onclick="return confirm(\'Confirm approval? Items will be restocked.\')"><i class="bi bi-check-lg"></i> Approve</button></form>';
                    $actions .= '<form method="POST" action="' . route('sales-returns.reject', $ret) . '" class="d-inline">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm(\'Confirm rejection?\')"><i class="bi bi-x-lg"></i> Reject</button></form>';
                }
                $actions .= '<form method="POST" action="' . route('sales-returns.destroy', $ret) . '" class="d-inline">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm(\'Are you sure you want to delete this return record?\')"><i class="bi bi-trash"></i> Delete</button></form>';
                $actions .= '</div>';
            } else {
                $actions = '<span class="text-muted small">—</span>';
            }

            $row = [];
            if ($canManage) {
                $row['checkbox'] = '<input type="checkbox" class="form-check-input return-checkbox" value="' . $ret->id . '">';
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

        $sale->load('items.item', 'shop');
        return view('sales-returns.create', compact('sale'));
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
            'reason'             => 'required|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.qty'        => 'required|integer|min:1',
        ]);

        // Validate returned quantities against sale quantities
        foreach ($request->items as $entry) {
            $saleItem = SaleItem::findOrFail($entry['sale_item_id']);
            
            // Check that they aren't returning more than was purchased
            if ($entry['qty'] > $saleItem->quantity) {
                return back()->withInput()->withErrors([
                    'items' => "You cannot return more than the sold quantity for {$saleItem->item->item_name}."
                ]);
            }
        }

        // Determine initial status: Admin/Owner auto-approves, Seller is pending
        $isAdminOrOwner = $user->isOwner() || $user->isShopAdmin();
        $status = $isAdminOrOwner ? 'approved' : 'pending';

        $saleReturn = DB::transaction(function () use ($request, $sale, $user, $status, $isAdminOrOwner) {
            $saleReturn = SaleReturn::create([
                'sale_id'      => $sale->id,
                'requested_by' => $user->id,
                'approved_by'  => $isAdminOrOwner ? $user->id : null,
                'status'       => $status,
                'reason'       => $request->reason,
                'return_date'  => now()->toDateString(),
            ]);

            foreach ($request->items as $entry) {
                $saleItem = SaleItem::findOrFail($entry['sale_item_id']);

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'item_id'        => $saleItem->item_id,
                    'quantity'       => $entry['qty'],
                ]);

                if ($isAdminOrOwner) {
                    // Update shop stock immediately
                    $this->stabilizeStock($sale->shop_id, $saleItem->item_id, $entry['qty'], $sale);
                }
            }

            if ($isAdminOrOwner) {
                $sale->delete();
            }

            return $saleReturn;
        });

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

        DB::transaction(function () use ($saleReturn, $user) {
            $saleReturn->update([
                'status'      => 'approved',
                'approved_by' => $user->id,
            ]);

            // Stabilize stock for each item in the return
            foreach ($saleReturn->items as $returnItem) {
                $this->stabilizeStock($saleReturn->sale->shop_id, $returnItem->item_id, $returnItem->quantity, $saleReturn->sale);
            }

            // Delete the sale record also
            $saleReturn->sale->delete();
        });

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

        // Authorization check for each item
        foreach ($request->ids as $id) {
            $saleReturn = SaleReturn::findOrFail($id);
            if (!$user->isOwner() && (!$user->isShopAdmin() || $user->shop_id !== $saleReturn->sale?->shop_id)) {
                abort(403, 'Unauthorized action.');
            }
        }

        SaleReturn::destroy($request->ids);

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
