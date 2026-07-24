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

        $itemName = $shopStock->item?->item_name ?? 'Item';

        if ($user->isShopAdmin()) {
            // Admin update is pending Owner approval
            $shopStock->update([
                'is_price_pending' => true,
                'pending_selling_price' => $request->selling_price,
            ]);

            // Notify all owners
            $owners = \App\Models\User::where('role', 'owner')->get();
            foreach ($owners as $owner) {
                \App\Models\Notification::create([
                    'user_id' => $owner->id,
                    'title'   => 'Shop Price Change Pending',
                    'message' => "Admin {$user->name} updated the selling price for \"{$itemName}\" in {$shopStock->shop->shop_name} to: TZS " . number_format($request->selling_price, 2) . ". Pending owner approval.",
                ]);
            }

            return back()->with('success', 'Selling price update is pending owner approval.');
        }

        // Owner update is direct
        $shopStock->update([
            'selling_price' => $request->selling_price,
            'is_price_pending' => false,
            'pending_selling_price' => null,
        ]);

        // Notify all sellers of this shop
        $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
            ->where('role', 'seller')
            ->get();
        foreach ($sellers as $seller) {
            \App\Models\Notification::create([
                'user_id' => $seller->id,
                'title'   => 'Shop Stock Price Updated',
                'message' => "Owner has updated the selling price for \"{$itemName}\" to: TZS " . number_format($request->selling_price, 2),
            ]);
        }

        return back()->with('success', 'Selling price updated successfully.');
    }

    public function approvePrice(Request $request, ShopStock $shopStock)
    {
        $user = Auth::user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isShopAdmin() && $shopStock->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized shop.');
        }

        if ($shopStock->is_price_pending) {
            $request->validate([
                'selling_price' => 'nullable|numeric|min:' . $shopStock->buying_price,
            ]);

            $newPrice = $request->filled('selling_price') ? floatval($request->selling_price) : $shopStock->pending_selling_price;

            $shopStock->update([
                'selling_price' => $newPrice,
                'is_price_pending' => false,
                'pending_selling_price' => null,
            ]);

            // Notify all sellers of this shop
            $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
                ->where('role', 'seller')
                ->get();
            $itemName = $shopStock->item?->item_name ?? 'Item';
            foreach ($sellers as $seller) {
                \App\Models\Notification::create([
                    'user_id' => $seller->id,
                    'title'   => 'Shop Stock Price Approved',
                    'message' => "The selling price update for \"{$itemName}\" has been approved to: TZS " . number_format($newPrice, 2),
                ]);
            }

            // Notify other roles of approval
            if ($user->isShopAdmin()) {
                $owners = \App\Models\User::where('role', 'owner')->get();
                foreach ($owners as $owner) {
                    \App\Models\Notification::create([
                        'user_id' => $owner->id,
                        'title'   => 'Shop Price Change Approved by Admin',
                        'message' => "Admin {$user->name} approved the selling price change for \"{$itemName}\" to TZS " . number_format($newPrice, 2) . " in {$shopStock->shop->shop_name}.",
                    ]);
                }
            } else {
                $admins = \App\Models\User::where('shop_id', $shopStock->shop_id)->where('role', 'shop_admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'title'   => 'Shop Price Change Approved by Owner',
                        'message' => "Owner approved the selling price change for \"{$itemName}\" to TZS " . number_format($newPrice, 2) . " in {$shopStock->shop->shop_name}.",
                    ]);
                }
            }

            return back()->with('success', 'Shop stock price approved successfully.');
        }

        return back()->with('error', 'No pending price change found.');
    }
}
