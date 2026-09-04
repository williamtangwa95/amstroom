<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Setting;
use App\Helpers\ImageCompressor;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        return view('shops.index');
    }

    public function data(Request $request)
    {
        $user = auth()->user();
        $query = Shop::query();

        if (!$user->isOwner()) {
            $query->where('id', $user->shop_id);
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('shop_name', 'like', "%{$searchValue}%")
                  ->orWhere('location', 'like', "%{$searchValue}%")
                  ->orWhere('phone', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $shops = $query->withCount(['users', 'sales'])
            ->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($shops as $index => $shop) {
            $iteration = $start + $index + 1;
            
            $nameHtml = '<div><div style="font-weight:600;font-size:.85rem;">' . e($shop->shop_name) . '</div>';
            if ($shop->email) {
                $nameHtml .= '<div style="font-size:.73rem;color:var(--text-secondary);">' . e($shop->email) . '</div>';
            }
            $nameHtml .= '</div>';

            $employeesHtml = '<span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">' . $shop->users_count . '</span>';
            $salesHtml = '<span style="background:rgba(63,185,80,.12);color:#3fb950;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">' . $shop->sales_count . '</span>';
            
            $statusClass = $shop->status === 'active' ? 'badge-active' : 'badge-inactive';
            $statusHtml = '<span class="status-badge ' . $statusClass . '"><span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span> ' . e(ucfirst($shop->status)) . '</span>';

            $actions = '<div class="d-flex gap-1">
                <a href="' . route('shops.show', $shop) . '" class="btn btn-xs btn-outline-custom" title="View"><i class="bi bi-eye"></i></a>
                <a href="' . route('shops.edit', $shop) . '" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>';

            if ($user->isOwner()) {
                $actions .= '<form method="POST" action="' . route('shops.destroy', $shop) . '" id="del-shop-' . $shop->id . '" class="d-inline">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="button" class="btn btn-xs btn-outline-custom"
                        data-confirm="Delete this shop?"
                        data-text="All associated data may be affected."
                        data-form="del-shop-' . $shop->id . '">
                        <i class="bi bi-trash" style="color:#e94560;"></i>
                    </button>
                </form>';
            }
            $actions .= '</div>';

            $data[] = [
                'no' => $iteration,
                'shop_name' => $nameHtml,
                'location' => e($shop->location),
                'phone' => e($shop->phone ?: '—'),
                'employees' => $employeesHtml,
                'sales' => $salesHtml,
                'status' => $statusHtml,
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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

        $maxKb = ((int) Setting::get('max_upload_size_mb', 5)) * 1024;

        $data = $request->validate([
            'shop_name' => 'required|string|max:150',
            'location'  => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'slogan'    => 'nullable|string|max:255',
            'logo'      => "nullable|image|mimes:jpeg,png,jpg,gif,webp|max:{$maxKb}",
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

        $maxKb = ((int) Setting::get('max_upload_size_mb', 5)) * 1024;

        $rules = [
            'shop_name' => 'required|string|max:150',
            'location'  => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'slogan'    => 'nullable|string|max:255',
            'logo'      => "nullable|image|mimes:jpeg,png,jpg,gif,webp|max:{$maxKb}",
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
