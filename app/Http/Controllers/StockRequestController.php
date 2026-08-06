<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
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

        $query = StockRequest::with(['shop', 'requester', 'items.item.category', 'items.item.mainStocks']);

        if ($user->isOwner()) {
            // Owner sees all
        } elseif ($user->isShopAdmin() || $user->isSeller()) {
            $query->where('shop_id', $user->shop_id);
        }

        $requests = $query->latest()->get();
        $pendingCount = StockRequest::where('status', 'pending')->count();

        return view('stock-requests.index', compact('requests', 'pendingCount'));
    }

    public function create()
    {
        $user = Auth::user();
        $items = Item::with('category')->where('is_admin_item', false)->orderBy('item_name')->get();
        $shop = $user->shop;

        return view('stock-requests.create', compact('items', 'shop'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes'          => 'nullable|string|max:500',
            'items'          => 'required|array|min:1',
            'items.*.item_id'=> 'required|exists:items,id',
            'items.*.quantity'=> 'required|integer|min:1',
        ]);

        $user = Auth::user();

        $stockRequest = StockRequest::create([
            'shop_id'      => $user->shop_id,
            'requested_by' => $user->id,
            'request_date' => now()->toDateString(),
            'status'       => 'pending',
            'notes'        => $request->notes,
        ]);

        foreach ($request->items as $item) {
            StockRequestItem::create([
                'request_id' => $stockRequest->id,
                'item_id'    => $item['item_id'],
                'quantity'   => $item['quantity'],
            ]);
        }

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
        $owners = \App\Models\User::where('role', 'owner')->get();
        foreach ($owners as $owner) {
            \App\Models\Notification::create([
                'user_id' => $owner->id,
                'title'   => 'New Stock Request Submitted',
                'message' => "Shop Admin has requested new stock from the main warehouse (Request #{$stockRequest->id}).",
            ]);
        }

        return redirect()->route('stock-requests.index')
            ->with('success', 'Stock request submitted successfully. Awaiting owner approval.');
    }

    public function show(StockRequest $stockRequest)
    {
        $stockRequest->load('shop', 'requester', 'items.item.category');
        return view('stock-requests.show', compact('stockRequest'));
    }

    public function approve(StockRequest $stockRequest)
    {
        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $transfer = null;

        DB::transaction(function () use ($stockRequest, &$transfer) {
            // Create transfer record
            $transfer = StockTransfer::create([
                'from_store'    => 'Main Warehouse',
                'to_shop'       => $stockRequest->shop_id,
                'approved_by'   => Auth::id(),
                'request_id'    => $stockRequest->id,
                'transfer_date' => now()->toDateString(),
                'status'        => 'pending_receipt',
            ]);

            foreach ($stockRequest->items as $requestItem) {
                $item = $requestItem->item;
                $qty  = $requestItem->quantity;

                // Get main stock for price info (use most recent)
                $mainStock = MainStock::where('item_id', $item->id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderByDesc('date_received')
                    ->first();

                // Deduct from main stock (FIFO)
                $remaining = $qty;
                $batches = MainStock::where('item_id', $item->id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('date_received')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->remaining_quantity, $remaining);
                    $batch->decrement('remaining_quantity', $deduct);
                    $remaining -= $deduct;
                }

                // Transfer item record - created in pending status, NOT added to shop stock immediately
                StockTransferItem::create([
                    'transfer_id'  => $transfer->id,
                    'item_id'      => $item->id,
                    'quantity'     => $qty,
                    'buying_price' => $mainStock?->buying_price ?? 0,
                    'selling_price'=> $mainStock?->selling_price ?? 0,
                    'status'       => 'pending',
                ]);

                // Audit log
                StockLog::create([
                    'item_id'          => $item->id,
                    'from_location'    => 'Main Warehouse',
                    'to_location'      => $stockRequest->shop->shop_name,
                    'quantity'         => $qty,
                    'transaction_type' => 'STOCK_TRANSFER',
                    'performed_by'     => Auth::id(),
                    'date'             => now()->toDateString(),
                    'notes'            => "Transfer for request #{$stockRequest->id}",
                ]);
            }

            $stockRequest->update(['status' => 'approved']);
        });

        // Notify shop admins of the shop (sellers are NOT notified until admin approves receipt)
        $shopAdmins = \App\Models\User::where('shop_id', $stockRequest->shop_id)
            ->where('role', 'shop_admin')
            ->get();
        foreach ($shopAdmins as $admin) {
            \App\Models\Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Stock Request Approved (Pending Receipt)',
                'message' => "Stock request #{$stockRequest->id} has been approved by the Owner. A new pending stock transfer #{$transfer->id} is awaiting your receipt confirmation.",
            ]);
        }

        return redirect()->route('stock-requests.show', $stockRequest)
            ->with('success', 'Stock request approved. A pending stock transfer has been created for the shop admin to confirm receipt.');
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
