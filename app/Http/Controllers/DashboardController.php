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

        $totalSales     = Sale::sum('total_amount');
        $totalSalesCount= Sale::count();

        // Profit estimate: total sales - total buying cost of sold items
        $totalCost = SaleItem::join('shop_stocks', function($j) {
            $j->on('sale_items.item_id', '=', 'shop_stocks.item_id');
        })->selectRaw('SUM(sale_items.quantity * shop_stocks.buying_price) as cost')->value('cost') ?? 0;
        $profit = $totalSales - $totalCost;

        $pendingRequests = StockRequest::where('status', 'pending')->count();
        $totalDefects    = Defect::count();

        // Monthly sales for chart (last 6 months)
        $monthlySales = Sale::selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as month, SUM(total_amount) as total')
            ->where('sale_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $recentSales = Sale::with('shop', 'seller')->latest()->take(5)->get();
        $recentRequests = StockRequest::with('shop', 'requester')->where('status', 'pending')->latest()->take(5)->get();

        $shopsSummary = Shop::withCount('sales')->withSum('sales', 'total_amount')->get();

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
        $shopSales = Sale::where('shop_id', $user->shop_id)->sum('total_amount');
        $todaySales = Sale::where('shop_id', $user->shop_id)->whereDate('sale_date', today())->sum('total_amount');
        $pendingRequests = StockRequest::where('shop_id', $user->shop_id)->where('status', 'pending')->count();
        $lowStockCount = ShopStock::where('shop_id', $user->shop_id)->whereColumn('remaining_quantity', '<=', 'low_stock_alert')->count();
        $defectsCount = Defect::where('shop_id', $user->shop_id)->count();
        $recentSales = Sale::with('seller', 'items.item')->where('shop_id', $user->shop_id)->latest()->take(5)->get();

        $monthlySales = Sale::selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as month, SUM(total_amount) as total')
            ->where('shop_id', $user->shop_id)
            ->where('sale_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard.index', compact(
            'shop', 'shopStock', 'shopSales', 'todaySales', 'pendingRequests',
            'lowStockCount', 'defectsCount', 'recentSales', 'monthlySales'
        ));
    }

    private function sellerDashboard(User $user)
    {
        $shop = $user->shop;
        $mySales = Sale::where('seller_id', $user->id)->sum('total_amount');
        $mySalesCount = Sale::where('seller_id', $user->id)->count();
        $todaySales = Sale::where('seller_id', $user->id)->whereDate('sale_date', today())->sum('total_amount');
        $availableStock = ShopStock::where('shop_id', $user->shop_id)->where('remaining_quantity', '>', 0)->count();
        $lowStockCount = ShopStock::where('shop_id', $user->shop_id)->whereColumn('remaining_quantity', '<=', 'low_stock_alert')->count();
        $recentSales = Sale::with('items.item')->where('seller_id', $user->id)->latest()->take(5)->get();

        return view('dashboard.index', compact(
            'shop', 'mySales', 'mySalesCount', 'todaySales', 'availableStock', 'lowStockCount', 'recentSales'
        ));
    }
}
