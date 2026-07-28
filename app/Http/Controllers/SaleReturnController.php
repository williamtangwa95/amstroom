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
        $user = Auth::user();
        $query = SaleReturn::with('sale.shop', 'requester', 'approver', 'items.item');

        if (!$user->isOwner()) {
            $query->whereHas('sale', function ($q) use ($user) {
                $q->where('shop_id', $user->shop_id);
            });
        }

        $returns = $query->latest()->get();
        return view('sales-returns.index', compact('returns'));
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
        $shopStock = ShopStock::where('shop_id', $shopId)
            ->where('item_id', $itemId)
            ->first();

        if ($shopStock) {
            $shopStock->increment('remaining_quantity', $quantity);
        } else {
            // In case stock record doesn't exist (edge case), create one
            $saleItem = SaleItem::where('sale_id', $sale->id)->where('item_id', $itemId)->first();
            
            ShopStock::create([
                'shop_id'            => $shopId,
                'item_id'            => $itemId,
                'buying_price'       => 0,
                'selling_price'      => $saleItem?->selling_price ?? 0,
                'quantity'           => $quantity,
                'remaining_quantity' => $quantity,
                'date_received'      => now()->toDateString(),
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
        ]);
    }
}
