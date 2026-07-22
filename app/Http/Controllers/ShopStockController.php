<?php

namespace App\Http\Controllers;

use App\Models\ShopStock;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopStockController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $shopId = $user->isOwner() ? $request->get('shop_id', null) : $user->shop_id;

        $query = ShopStock::with('item.category', 'shop');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $stocks = $query->latest()->get();
        $shops  = $user->isOwner() ? Shop::active()->get() : collect();

        $lowStockItems = ShopStock::with('item', 'shop')
            ->whereColumn('remaining_quantity', '<=', 'low_stock_alert')
            ->when(!$user->isOwner(), fn($q) => $q->where('shop_id', $user->shop_id))
            ->count();

        return view('shop-stock.index', compact('stocks', 'shops', 'shopId', 'lowStockItems'));
    }

    public function show(ShopStock $shopStock)
    {
        $shopStock->load('item.category', 'shop');
        return view('shop-stock.show', compact('shopStock'));
    }

    public function updateAlert(Request $request, ShopStock $shopStock)
    {
        $request->validate([
            'low_stock_alert' => 'required|integer|min:0',
        ]);
        $shopStock->update(['low_stock_alert' => $request->low_stock_alert]);
        return back()->with('success', 'Low stock alert threshold updated.');
    }

    public function updatePrice(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403);
        }

        $request->validate([
            'selling_price' => 'required|numeric|min:' . $shopStock->buying_price,
        ]);

        $shopStock->update(['selling_price' => $request->selling_price]);

        return back()->with('success', 'Selling price updated successfully.');
    }
}
