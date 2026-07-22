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

        $sales = $query->latest()->get();
        $totalRevenue = $query->sum('total_amount');

        return view('sales.index', compact('sales', 'totalRevenue'));
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $shopStocks = \App\Models\MainStock::with('item.category')
                ->where('remaining_quantity', '>', 0)
                ->get();
        } else {
            $shopStocks = ShopStock::with('item.category')
                ->where('shop_id', $user->shop_id)
                ->where('remaining_quantity', '>', 0)
                ->get();
        }

        return view('sales.create', compact('shopStocks'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isOwner = $user->isOwner();

        $request->validate([
            'customer_name'     => 'nullable|string|max:150',
            'payment_method'    => 'required|in:cash,card,mobile_money,bank_transfer',
            'items'             => 'required|array|min:1',
            'items.*.shop_stock_id' => [
                'required',
                $isOwner ? 'exists:main_stocks,id' : 'exists:shop_stocks,id'
            ],
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.price'     => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $user, $isOwner) {
            $totalAmount = 0;
            $saleItemsData = [];

            foreach ($request->items as $cartItem) {
                if ($isOwner) {
                    $stock = \App\Models\MainStock::findOrFail($cartItem['shop_stock_id']);
                } else {
                    $stock = ShopStock::findOrFail($cartItem['shop_stock_id']);
                }

                if ($cartItem['quantity'] > $stock->remaining_quantity) {
                    throw new \Exception("Insufficient stock for: {$stock->item->item_name}. Available: {$stock->remaining_quantity}");
                }

                // Negotiable price check
                $submittedPrice = floatval($cartItem['price']);
                if ($submittedPrice < floatval($stock->selling_price)) {
                    throw new \Exception("Price for {$stock->item->item_name} cannot be less than dedicated selling price TZS " . number_format($stock->selling_price, 0));
                }

                $subtotal = $cartItem['quantity'] * $submittedPrice;
                $totalAmount += $subtotal;

                $saleItemsData[] = [
                    'stock'      => $stock,
                    'quantity'   => $cartItem['quantity'],
                    'price'      => $submittedPrice,
                ];
            }

            $sale = Sale::create([
                'shop_id'        => $isOwner ? null : $user->shop_id,
                'seller_id'      => $user->id,
                'customer_name'  => $request->customer_name,
                'total_amount'   => $totalAmount,
                'payment_method' => $request->payment_method,
                'sale_date'      => now()->toDateString(),
            ]);

            foreach ($saleItemsData as $data) {
                SaleItem::create([
                    'sale_id'       => $sale->id,
                    'item_id'       => $data['stock']->item_id,
                    'quantity'      => $data['quantity'],
                    'selling_price' => $data['price'],
                ]);

                $data['stock']->decrement('remaining_quantity', $data['quantity']);

                StockLog::create([
                    'item_id'          => $data['stock']->item_id,
                    'from_location'    => $isOwner ? 'Main Store' : $data['stock']->shop->shop_name,
                    'to_location'      => $request->customer_name ?? 'Walk-in Customer',
                    'quantity'         => $data['quantity'],
                    'transaction_type' => 'SALE',
                    'performed_by'     => $user->id,
                    'date'             => now()->toDateString(),
                    'notes'            => "Sale #{$sale->id}" . ($isOwner ? " (Direct Sale from Main Store)" : ""),
                ]);
            }

            session(['last_sale_id' => $sale->id]);
        });

        $saleId = session('last_sale_id');

        $printerEnabled = \App\Models\Setting::get('printer_enabled_user_' . $user->id, '1');
        if ($printerEnabled === '0') {
            return redirect()->route('sales.index')
                ->with('success', 'Sale completed successfully.');
        }

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
