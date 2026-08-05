<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        $shops = Shop::withCount(['users', 'sales'])->latest()->get();
        return view('shops.index', compact('shops'));
    }

    public function create()
    {
        return view('shops.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'required|string|max:150',
            'location'  => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'slogan'    => 'nullable|string|max:255',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status'    => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('shop_logos', 'public');
        }

        Shop::create($data);

        return redirect()->route('shops.index')
            ->with('success', 'Shop created successfully.');
    }

    public function show(Shop $shop)
    {
        $shop->load([
            'users',
            'shopStocks' => function ($q) {
                $q->where('is_admin_stock', false)->with('item.category');
            }
        ]);
        $sales_total = $shop->sales()->where('is_admin_stock', false)->sum('total_amount');
        $stock_count = $shop->shopStocks()->where('is_admin_stock', false)->sum('remaining_quantity');
        return view('shops.show', compact('shop', 'sales_total', 'stock_count'));
    }

    public function edit(Shop $shop)
    {
        return view('shops.edit', compact('shop'));
    }

    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'shop_name' => 'required|string|max:150',
            'location'  => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'slogan'    => 'nullable|string|max:255',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status'    => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('logo')) {
            if ($shop->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($shop->logo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($shop->logo);
            }
            $data['logo'] = $request->file('logo')->store('shop_logos', 'public');
        }

        $shop->update($data);

        return redirect()->route('shops.index')
            ->with('success', 'Shop updated successfully.');
    }

    public function destroy(Shop $shop)
    {
        $shop->delete();
        return redirect()->route('shops.index')
            ->with('success', 'Shop deleted successfully.');
    }

    public function assignEmployee(Request $request, Shop $shop)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->update(['shop_id' => $shop->id]);

        return back()->with('success', 'Employee assigned to shop successfully.');
    }
}
