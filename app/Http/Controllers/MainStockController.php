<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MainStock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MainStockController extends Controller
{
    public function index()
    {
        $stocks = MainStock::with('item.category')
            ->latest()
            ->get();

        $totalValue = MainStock::selectRaw('SUM(remaining_quantity * buying_price) as val')->value('val') ?? 0;
        $totalSellValue = MainStock::selectRaw('SUM(remaining_quantity * selling_price) as val')->value('val') ?? 0;

        return view('main-stock.index', compact('stocks', 'totalValue', 'totalSellValue'));
    }

    public function create()
    {
        $items = Item::with('category')->orderBy('item_name')->get();
        return view('main-stock.create', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id'          => 'required|exists:items,id',
            'buying_price'     => 'required|numeric|min:0',
            'selling_price'    => 'required|numeric|min:0',
            'stocked_quantity' => 'required|integer|min:1',
            'date_received'    => 'required|date',
        ]);

        $stock = MainStock::create([
            'item_id'           => $request->item_id,
            'buying_price'      => $request->buying_price,
            'selling_price'     => $request->selling_price,
            'stocked_quantity'  => $request->stocked_quantity,
            'remaining_quantity'=> $request->stocked_quantity,
            'date_received'     => $request->date_received,
        ]);

        StockLog::create([
            'item_id'          => $stock->item_id,
            'from_location'    => 'Supplier',
            'to_location'      => 'Main Warehouse',
            'quantity'         => $stock->stocked_quantity,
            'transaction_type' => 'STOCK_RECEIVED',
            'performed_by'     => Auth::id(),
            'date'             => $stock->date_received,
            'notes'            => 'Initial stock received',
        ]);

        return redirect()->route('main-stock.index')
            ->with('success', 'Stock added to main warehouse successfully.');
    }

    public function show(MainStock $mainStock)
    {
        $mainStock->load('item.category');
        return view('main-stock.show', compact('mainStock'));
    }

    public function history()
    {
        $logs = StockLog::with('item.category', 'performer')
            ->whereIn('transaction_type', ['STOCK_RECEIVED', 'STOCK_TRANSFER', 'ADJUSTMENT'])
            ->latest()
            ->get();
        return view('main-stock.history', compact('logs'));
    }

    public function edit(MainStock $mainStock)
    {
        $items = Item::with('category')->orderBy('item_name')->get();
        return view('main-stock.edit', compact('mainStock', 'items'));
    }

    public function update(Request $request, MainStock $mainStock)
    {
        $request->validate([
            'buying_price'  => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'date_received' => 'required|date',
        ]);

        $newSellingPrice = floatval($request->selling_price);
        $oldSellingPrice = floatval($mainStock->selling_price);

        $mainStock->update($request->only('buying_price', 'selling_price', 'date_received'));

        if ($newSellingPrice != $oldSellingPrice) {
            $itemName = $mainStock->item?->item_name ?? 'Item';
            // Find all shop stocks for this item
            $shopStocks = \App\Models\ShopStock::where('item_id', $mainStock->item_id)->get();
            foreach ($shopStocks as $shopStock) {
                $shopStock->update([
                    'is_price_pending' => true,
                    'pending_selling_price' => $newSellingPrice,
                ]);

                // Notify all admins of this shop
                $admins = \App\Models\User::where('shop_id', $shopStock->shop_id)
                    ->where('role', 'shop_admin')
                    ->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'title'   => 'Main Store Price Updated',
                        'message' => "Owner updated the selling price for \"{$itemName}\" to TZS " . number_format($newSellingPrice, 2) . ". This is pending your approval.",
                    ]);
                }
            }
        }

        return redirect()->route('main-stock.index')
            ->with('success', 'Stock updated successfully. Associated shop stock prices are now pending admin approval.');
    }
}
