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

        if ($user->isOwner()) {
            $query->where('is_admin_stock', false);
        }

        $stocks = $query->latest()->get();
        $shops  = $user->isOwner() ? Shop::active()->get() : collect();

        $items = collect();
        $categories = collect();
        if ($user->isShopAdmin()) {
            $items = \App\Models\Item::where(function ($q) use ($user) {
                $q->where('is_admin_item', false)
                  ->orWhere(function ($sq) use ($user) {
                      $sq->where('is_admin_item', true)
                         ->where('shop_id', $user->shop_id);
                  });
            })->orderBy('item_name')->get();

            $categories = \App\Models\Category::where(function ($q) use ($user) {
                $q->where('is_admin_category', false)
                  ->orWhere(function ($sq) use ($user) {
                      $sq->where('is_admin_category', true)
                         ->where('shop_id', $user->shop_id);
                  });
            })->orderBy('category_name')->get();
        }

        $lowStockItems = ShopStock::with('item', 'shop')
            ->whereColumn('remaining_quantity', '<=', 'low_stock_alert')
            ->when(!$user->isOwner(), fn($q) => $q->where('shop_id', $user->shop_id))
            ->when($user->isOwner(), fn($q) => $q->where('is_admin_stock', false))
            ->count();

        return view('shop-stock.index', compact('stocks', 'shops', 'shopId', 'lowStockItems', 'items', 'categories'));
    }

    public function storeAdminStock(Request $request)
    {
        $user = Auth::user();
        if (!$user->isShopAdmin()) {
            abort(403, 'Only shop admins can add admin stock.');
        }

        $createNew = $request->boolean('create_new_product');
        $createNewCategory = $request->boolean('create_new_category');

        if ($createNew) {
            if ($createNewCategory) {
                $request->validate([
                    'new_item_name'     => 'required|string|max:150',
                    'new_category_name' => 'required|string|max:100',
                    'brand'             => 'nullable|string|max:100',
                    'model'             => 'nullable|string|max:100',
                    'specification'     => 'nullable|string',
                    'buying_price'      => 'required|numeric|min:0',
                    'selling_price' => 'required|numeric|min:' . $request->input('buying_price', 0),
                    'quantity'          => 'required|integer|min:1',
                    'date_received'     => 'required|date',
                ]);
            } else {
                $request->validate([
                    'new_item_name' => 'required|string|max:150',
                    'category_id'   => 'required|exists:categories,id',
                    'brand'         => 'nullable|string|max:100',
                    'model'         => 'nullable|string|max:100',
                    'specification' => 'nullable|string',
                    'buying_price'  => 'required|numeric|min:0',
                    'selling_price' => 'required|numeric|min:' . $request->input('buying_price', 0),
                    'quantity'      => 'required|integer|min:1',
                    'date_received' => 'required|date',
                ]);
            }
        } else {
            $request->validate([
                'item_id'       => 'required|exists:items,id',
                'buying_price'  => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:' . $request->input('buying_price', 0),
                'quantity'      => 'required|integer|min:1',
                'date_received' => 'required|date',
            ]);
        }

        if ($createNew) {
            if ($createNewCategory) {
                $categoryName = trim($request->new_category_name);
                $category = \App\Models\Category::where(function($q) use ($user) {
                    $q->where('is_admin_category', false)
                      ->orWhere(function($sq) use ($user) {
                          $sq->where('is_admin_category', true)
                             ->where('shop_id', $user->shop_id);
                      });
                })->where('category_name', $categoryName)->first();

                if (!$category) {
                    $category = \App\Models\Category::create([
                        'category_name'     => $categoryName,
                        'is_admin_category' => true,
                        'shop_id'           => $user->shop_id,
                    ]);
                }
                $categoryId = $category->id;
            } else {
                $categoryId = $request->category_id;
            }

            $item = \App\Models\Item::create([
                'item_name'     => $request->new_item_name,
                'category_id'   => $categoryId,
                'brand'         => $request->brand,
                'model'         => $request->model,
                'specification' => $request->specification,
                'is_admin_item' => true,
                'shop_id'       => $user->shop_id,
            ]);
            $itemId = $item->id;
        } else {
            $itemId = $request->item_id;
        }

        $stock = ShopStock::create([
            'shop_id'            => $user->shop_id,
            'item_id'            => $itemId,
            'buying_price'       => $request->buying_price,
            'selling_price'      => $request->selling_price,
            'quantity'           => $request->quantity,
            'remaining_quantity' => $request->quantity,
            'low_stock_alert'    => 1,
            'date_received'      => $request->date_received,
            'is_price_pending'   => false,
            'is_sellable'        => true,
            'is_admin_stock'     => true,
        ]);

        \App\Models\StockLog::create([
            'item_id'          => $stock->item_id,
            'from_location'    => 'Supplier (Admin)',
            'to_location'      => $user->shop->shop_name,
            'quantity'         => $stock->quantity,
            'transaction_type' => 'STOCK_RECEIVED',
            'performed_by'     => $user->id,
            'date'             => $stock->date_received,
            'notes'            => 'Admin stock added directly to shop',
            'is_admin_stock'   => true,
        ]);

        $item = \App\Models\Item::findOrFail($itemId);
        $sellers = \App\Models\User::where('shop_id', $user->shop_id)
            ->where('role', 'seller')
            ->get();
        foreach ($sellers as $seller) {
            \App\Models\Notification::create([
                'user_id' => $seller->id,
                'title'   => 'New Admin Stock Added',
                'message' => "Admin has added new stock for \"{$item->item_name}\" (Qty: {$stock->quantity}) to the shop stock.",
            ]);
        }

        return redirect()->route('shop-stock.index')
            ->with('success', 'Admin stock added to shop successfully.');
    }

    public function show(ShopStock $shopStock)
    {
        $shopStock->load('item.category', 'shop');
        return view('shop-stock.show', compact('shopStock'));
    }

    public function updateAlert(Request $request, ShopStock $shopStock)
    {
        $request->validate([
            'low_stock_alert' => 'required|integer|min:1',
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
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        if ($user->isShopAdmin()) {
            if ($isIndependent) {
                // Admin price update is direct in INDEPENDENT mode, bypassing owner approval
                $shopStock->update([
                    'selling_price' => $request->selling_price,
                    'is_price_pending' => false,
                    'pending_selling_price' => null,
                    'is_sellable' => true,
                ]);

                // Notify all sellers of this shop
                $sellers = \App\Models\User::where('shop_id', $shopStock->shop_id)
                    ->where('role', 'seller')
                    ->get();
                foreach ($sellers as $seller) {
                    \App\Models\Notification::create([
                        'user_id' => $seller->id,
                        'title'   => 'Shop Stock Price Updated',
                        'message' => "Admin has updated the selling price for \"{$itemName}\" to: TZS " . number_format($request->selling_price, 2),
                    ]);
                }

                return back()->with('success', 'Selling price updated and item unlocked successfully.');
            }

            // Admin update is pending Owner approval in DEPENDENT mode
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
            'is_sellable' => true,
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
                'is_sellable' => true,
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
