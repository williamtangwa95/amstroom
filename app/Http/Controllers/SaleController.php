<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShopStock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Sale::with('shop', 'seller', 'items.item');

        if (!$user->isOwner()) {
            $query->where('shop_id', $user->shop_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(15);
        $totalRevenue = $query->sum('total_amount');

        return view('sales.index', compact('sales', 'totalRevenue'));
    }

    public function create()
    {
        $user = Auth::user();
        $shopId = $user->shop_id;

        $shopStocks = ShopStock::with('item.category')
            ->where('shop_id', $shopId)
            ->where('remaining_quantity', '>', 0)
            ->get();

        return view('sales.create', compact('shopStocks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name'     => 'nullable|string|max:150',
            'payment_method'    => 'required|in:cash,card,mobile_money,bank_transfer',
            'items'             => 'required|array|min:1',
            'items.*.shop_stock_id' => 'required|exists:shop_stocks,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {
            $totalAmount = 0;
            $saleItemsData = [];

            foreach ($request->items as $cartItem) {
                $shopStock = ShopStock::findOrFail($cartItem['shop_stock_id']);

                if ($cartItem['quantity'] > $shopStock->remaining_quantity) {
                    throw new \Exception("Insufficient stock for: {$shopStock->item->item_name}. Available: {$shopStock->remaining_quantity}");
                }

                $subtotal = $cartItem['quantity'] * $shopStock->selling_price;
                $totalAmount += $subtotal;

                $saleItemsData[] = [
                    'shop_stock' => $shopStock,
                    'quantity'   => $cartItem['quantity'],
                    'price'      => $shopStock->selling_price,
                ];
            }

            $sale = Sale::create([
                'shop_id'        => $user->shop_id,
                'seller_id'      => $user->id,
                'customer_name'  => $request->customer_name,
                'total_amount'   => $totalAmount,
                'payment_method' => $request->payment_method,
                'sale_date'      => now()->toDateString(),
            ]);

            foreach ($saleItemsData as $data) {
                SaleItem::create([
                    'sale_id'       => $sale->id,
                    'item_id'       => $data['shop_stock']->item_id,
                    'quantity'      => $data['quantity'],
                    'selling_price' => $data['price'],
                ]);

                $data['shop_stock']->decrement('remaining_quantity', $data['quantity']);

                StockLog::create([
                    'item_id'          => $data['shop_stock']->item_id,
                    'from_location'    => $data['shop_stock']->shop->shop_name,
                    'to_location'      => $request->customer_name ?? 'Walk-in Customer',
                    'quantity'         => $data['quantity'],
                    'transaction_type' => 'SALE',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => "Sale #{$sale->id}",
                ]);
            }

            session(['last_sale_id' => $sale->id]);
        });

        $saleId = session('last_sale_id');
        return redirect()->route('sales.receipt', $saleId)
            ->with('success', 'Sale completed successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load('shop', 'seller', 'items.item.category');
        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $sale->load('shop', 'seller', 'items.item');
        return view('sales.receipt', compact('sale'));
    }
}
