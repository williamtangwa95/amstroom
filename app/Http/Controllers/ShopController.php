<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Helpers\ImageCompressor;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->isOwner()) {
            $shops = Shop::withCount(['users', 'sales'])->latest()->get();
        } else {
            $shops = Shop::where('id', $user->shop_id)->withCount(['users', 'sales'])->get();
        }
        return view('shops.index', compact('shops'));
    }

    public function create()
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized.');
        }
        return view('shops.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized.');
        }

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
            $data['logo'] = ImageCompressor::compressAndStore($request->file('logo'), 'shop_logos', 'public', 800, 85);
        }

        Shop::create($data);

        return redirect()->route('shops.index')
            ->with('success', 'Shop created successfully.');
    }

    public function show(Shop $shop)
    {
        $user = auth()->user();
        if (!$user->isOwner() && $user->shop_id !== $shop->id) {
            abort(403, 'Unauthorized.');
        }

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
        $user = auth()->user();
        if (!$user->isOwner() && $user->shop_id !== $shop->id) {
            abort(403, 'Unauthorized.');
        }
        return view('shops.edit', compact('shop'));
    }

    public function update(Request $request, Shop $shop)
    {
        $user = auth()->user();
        if (!$user->isOwner() && $user->shop_id !== $shop->id) {
            abort(403, 'Unauthorized.');
        }

        $rules = [
            'shop_name' => 'required|string|max:150',
            'location'  => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'slogan'    => 'nullable|string|max:255',
            'logo'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];

        if ($user->isOwner()) {
            $rules['status'] = 'required|in:active,inactive';
        }

        $data = $request->validate($rules);

        if ($request->hasFile('logo')) {
            if ($shop->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($shop->logo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($shop->logo);
            }
            $data['logo'] = ImageCompressor::compressAndStore($request->file('logo'), 'shop_logos', 'public', 800, 85);
        }

        $shop->update($data);

        return redirect()->route('shops.show', $shop)
            ->with('success', 'Shop updated successfully.');
    }

    public function destroy(Shop $shop)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized.');
        }

        $shop->delete();
        return redirect()->route('shops.index')
            ->with('success', 'Shop deleted successfully.');
    }

    public function assignEmployee(Request $request, Shop $shop)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->update(['shop_id' => $shop->id]);

        return back()->with('success', 'Employee assigned to shop successfully.');
    }
}
