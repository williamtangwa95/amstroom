<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Defect;
use App\Models\Item;
use App\Models\MainStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\StockRequest;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            return $this->ownerDashboard();
        } elseif ($user->isShopAdmin()) {
            return $this->shopAdminDashboard($user);
        } else {
            return $this->sellerDashboard($user);
        }
    }

    private function ownerDashboard()
    {
        $totalShops     = Shop::count();
        $totalEmployees = User::where('role', '!=', 'owner')->count();
        $totalItems     = Item::count();
        $totalCategories= Category::count();

        $mainStockValue     = MainStock::selectRaw('SUM(remaining_quantity * buying_price)')->value(DB::raw('SUM(remaining_quantity * buying_price)')) ?? 0;
        $mainStockSellValue = MainStock::selectRaw('SUM(remaining_quantity * selling_price)')->value(DB::raw('SUM(remaining_quantity * selling_price)')) ?? 0;
        $shopStockValue     = ShopStock::selectRaw('SUM(remaining_quantity * buying_price)')->value(DB::raw('SUM(remaining_quantity * buying_price)')) ?? 0;

        $allSales = Sale::completed()->with('items')->get();
        $totalSales = $allSales->sum(fn($s) => $s->report_revenue);
        $totalSalesCount = $allSales->count();
        $profit = $allSales->sum(fn($s) => $s->report_profit);

        $pendingRequests = StockRequest::where('status', 'pending')->count();
        $totalDefects    = Defect::count();

        // Monthly sales for chart (last 6 months)
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
        $monthlySales = $allSales->where('sale_date', '>=', $sixMonthsAgo)
            ->groupBy(fn($s) => $s->sale_date->format('Y-m'))
            ->map(function ($group, $month) {
                return (object) [
                    'month' => $month,
                    'total' => $group->sum(fn($s) => $s->report_revenue),
                ];
            })->sortKeys()->values();

        $recentSales = Sale::completed()->with('shop', 'seller', 'items.item')->latest()->take(5)->get();
        $recentRequests = StockRequest::with('shop', 'requester')->where('status', 'pending')->latest()->take(5)->get();

        $shopsSummary = Shop::all()->map(function ($shop) use ($allSales) {
            $shopSales = $allSales->where('shop_id', $shop->id);
            return (object) [
                'shop_name' => $shop->shop_name,
                'sales_count' => $shopSales->count(),
                'sales_sum_total_amount' => $shopSales->sum(fn($s) => $s->report_revenue),
            ];
        });

        return view('dashboard.index', compact(
            'totalShops', 'totalEmployees', 'totalItems', 'totalCategories',
            'mainStockValue', 'mainStockSellValue', 'shopStockValue',
            'totalSales', 'totalSalesCount', 'profit',
            'pendingRequests', 'totalDefects',
            'monthlySales', 'recentSales', 'recentRequests', 'shopsSummary'
        ));
    }

    private function shopAdminDashboard(User $user)
    {
        $shop = $user->shop;
        $shopStock = ShopStock::where('shop_id', $user->shop_id)->sum('remaining_quantity');
        
        $shopSalesList = Sale::completed()->with('items')->where('shop_id', $user->shop_id)->get();
        $shopSales = $shopSalesList->sum(fn($s) => $s->report_revenue);
        $todaySales = $shopSalesList->where('sale_date', today())->sum(fn($s) => $s->report_revenue);
        
        $pendingRequests = StockRequest::where('shop_id', $user->shop_id)->where('status', 'pending')->count();
        $lowStockCount = ShopStock::where('shop_id', $user->shop_id)->whereColumn('remaining_quantity', '<=', 'low_stock_alert')->count();
        $defectsCount = Defect::where('shop_id', $user->shop_id)->count();
        $recentSales = Sale::completed()->with('seller', 'items.item')->where('shop_id', $user->shop_id)->latest()->take(5)->get();

        $monthlySales = $shopSalesList->where('sale_date', '>=', now()->subMonths(6))
            ->groupBy(fn($s) => $s->sale_date->format('Y-m'))
            ->map(function ($group, $month) {
                return (object) [
                    'month' => $month,
                    'total' => $group->sum(fn($s) => $s->report_revenue),
                ];
            })->sortKeys()->values();

        return view('dashboard.index', compact(
            'shop', 'shopStock', 'shopSales', 'todaySales', 'pendingRequests',
            'lowStockCount', 'defectsCount', 'recentSales', 'monthlySales'
        ));
    }

    private function sellerDashboard(User $user)
    {
        $shop = $user->shop;
        $sellerSalesList = Sale::completed()->with('items')->where('seller_id', $user->id)->get();
        $mySales = $sellerSalesList->sum(fn($s) => $s->report_revenue);
        $mySalesCount = $sellerSalesList->count();
        $todaySales = $sellerSalesList->where('sale_date', today())->sum(fn($s) => $s->report_revenue);
        $availableStock = ShopStock::where('shop_id', $user->shop_id)->where('remaining_quantity', '>', 0)->count();
        $lowStockCount = ShopStock::where('shop_id', $user->shop_id)->whereColumn('remaining_quantity', '<=', 'low_stock_alert')->count();
        $recentSales = Sale::completed()->with('items.item')->where('seller_id', $user->id)->latest()->take(5)->get();

        return view('dashboard.index', compact(
            'shop', 'mySales', 'mySalesCount', 'todaySales', 'availableStock', 'lowStockCount', 'recentSales'
        ));
    }
}
