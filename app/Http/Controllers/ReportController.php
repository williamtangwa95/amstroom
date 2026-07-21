<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\StockRequest;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $shopId = $request->get('shop_id');

        $query = Sale::with('shop', 'seller');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($period === 'daily') {
            $query->whereDate('sale_date', today());
        } elseif ($period === 'monthly') {
            $query->whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year);
        } elseif ($period === 'yearly') {
            $query->whereYear('sale_date', now()->year);
        } elseif ($period === 'custom') {
            if ($request->filled('date_from')) $query->whereDate('sale_date', '>=', $request->date_from);
            if ($request->filled('date_to'))   $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(20);
        $totalRevenue = $query->sum('total_amount');

        // Summary by shop
        $salesByShop = Sale::selectRaw('shop_id, SUM(total_amount) as revenue, COUNT(*) as count')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->groupBy('shop_id')
            ->with('shop')
            ->get();

        $shops = Shop::all();

        // Chart data: daily sales for period
        $chartData = Sale::selectRaw('sale_date, SUM(total_amount) as total')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->where('sale_date', '>=', now()->subDays(30))
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        return view('reports.sales', compact('sales', 'totalRevenue', 'salesByShop', 'shops', 'period', 'chartData'));
    }

    public function stock(Request $request)
    {
        $type = $request->get('type', 'main');

        $mainStocks = \App\Models\MainStock::with('item.category')
            ->selectRaw('item_id, SUM(remaining_quantity) as qty, SUM(remaining_quantity * buying_price) as value, SUM(remaining_quantity * selling_price) as sell_value')
            ->groupBy('item_id')
            ->with('item.category')
            ->paginate(20);

        $shopStocks = \App\Models\ShopStock::with('item.category', 'shop')
            ->where('remaining_quantity', '>', 0)
            ->paginate(20);

        return view('reports.stock', compact('mainStocks', 'shopStocks', 'type'));
    }

    public function transfer(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = StockRequest::with('shop', 'requester', 'transfer');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(20);
        $stats = [
            'pending'  => StockRequest::where('status', 'pending')->count(),
            'approved' => StockRequest::where('status', 'approved')->count(),
            'rejected' => StockRequest::where('status', 'rejected')->count(),
        ];

        return view('reports.transfer', compact('requests', 'stats', 'status'));
    }

    public function defect(Request $request)
    {
        $query = Defect::with('shop', 'item.category', 'reporter');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        $defects = $query->latest()->paginate(20);
        $totalDefective = $query->sum('quantity');
        $shops = Shop::all();

        return view('reports.defect', compact('defects', 'totalDefective', 'shops'));
    }
}
