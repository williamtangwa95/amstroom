<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\StockRequest;
use App\Models\StockTransfer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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
            if ($shopId === 'owner') {
                $query->whereNull('shop_id');
            } else {
                $query->where('shop_id', $shopId);
            }
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

        $sales = $query->latest()->get();
        $totalRevenue = $query->sum('total_amount');

        // Summary by shop
        $salesByShop = Sale::selectRaw('shop_id, SUM(total_amount) as revenue, COUNT(*) as count')
            ->when($shopId, function($q) use ($shopId) {
                if ($shopId === 'owner') {
                    return $q->whereNull('shop_id');
                }
                return $q->where('shop_id', $shopId);
            })
            ->groupBy('shop_id')
            ->with('shop')
            ->get();

        $shops = Shop::all();

        // Chart data: daily sales for period
        $chartData = Sale::selectRaw('sale_date, SUM(total_amount) as total')
            ->when($shopId, function($q) use ($shopId) {
                if ($shopId === 'owner') {
                    return $q->whereNull('shop_id');
                }
                return $q->where('shop_id', $shopId);
            })
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
            ->get();

        $shopStocks = \App\Models\ShopStock::with('item.category', 'shop')
            ->where('remaining_quantity', '>', 0)
            ->get();

        return view('reports.stock', compact('mainStocks', 'shopStocks', 'type'));
    }

    public function transfer(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = StockRequest::with('shop', 'requester', 'transfer');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->latest()->get();
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

        $defects = $query->latest()->get();
        $totalDefective = $query->sum('quantity');
        $shops = Shop::all();

        return view('reports.defect', compact('defects', 'totalDefective', 'shops'));
    }

    public function expenses(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $shopId = $request->get('shop_id');
        $categoryId = $request->get('expense_category_id');

        $query = Expense::with('category', 'recorder', 'approver')
            ->whereIn('status', ['approved', 'review_requested', 'editable']);

        if ($shopId) {
            if ($shopId === 'owner') {
                $query->whereHas('recorder', function ($q) {
                    $q->whereNull('shop_id');
                });
            } else {
                $query->whereHas('recorder', function ($q) use ($shopId) {
                    $q->where('shop_id', $shopId);
                });
            }
        }

        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($period === 'daily') {
            $query->whereDate('activity_date', today());
        } elseif ($period === 'monthly') {
            $query->whereMonth('activity_date', now()->month)->whereYear('activity_date', now()->year);
        } elseif ($period === 'yearly') {
            $query->whereYear('activity_date', now()->year);
        } elseif ($period === 'custom') {
            if ($request->filled('date_from')) $query->whereDate('activity_date', '>=', $request->date_from);
            if ($request->filled('date_to'))   $query->whereDate('activity_date', '<=', $request->date_to);
        }

        $expenses = $query->latest()->get();
        $totalAmount = $query->sum('amount');

        // Summary by category
        $expensesByCategory = Expense::selectRaw('expense_category_id, SUM(amount) as total_amount, COUNT(*) as count')
            ->whereIn('status', ['approved', 'review_requested', 'editable'])
            ->when($shopId, function($q) use ($shopId) {
                if ($shopId === 'owner') {
                    return $q->whereHas('recorder', function($sq) { $sq->whereNull('shop_id'); });
                }
                return $q->whereHas('recorder', function($sq) use ($shopId) { $sq->where('shop_id', $shopId); });
            })
            ->when($categoryId, function($q) use ($categoryId) {
                return $q->where('expense_category_id', $categoryId);
            })
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        $categories = ExpenseCategory::orderBy('name')->get();
        $shops = Shop::all();

        return view('reports.expenses', compact('expenses', 'totalAmount', 'expensesByCategory', 'categories', 'shops', 'period'));
    }

    public function salesVsExpenses(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $shopId = $request->get('shop_id');

        // 1. Get Sales
        $salesQuery = Sale::query();
        if ($shopId) {
            if ($shopId === 'owner') {
                $salesQuery->whereNull('shop_id');
            } else {
                $salesQuery->where('shop_id', $shopId);
            }
        }
        
        // 2. Get Expenses
        $expensesQuery = Expense::whereIn('status', ['approved', 'review_requested', 'editable']);
        if ($shopId) {
            if ($shopId === 'owner') {
                $expensesQuery->whereHas('recorder', function ($q) {
                    $q->whereNull('shop_id');
                });
            } else {
                $expensesQuery->whereHas('recorder', function ($q) use ($shopId) {
                    $q->where('shop_id', $shopId);
                });
            }
        }

        // Apply period to queries
        if ($period === 'daily') {
            $salesQuery->whereDate('sale_date', today());
            $expensesQuery->whereDate('activity_date', today());
        } elseif ($period === 'monthly') {
            $salesQuery->whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year);
            $expensesQuery->whereMonth('activity_date', now()->month)->whereYear('activity_date', now()->year);
        } elseif ($period === 'yearly') {
            $salesQuery->whereYear('sale_date', now()->year);
            $expensesQuery->whereYear('activity_date', now()->year);
        } elseif ($period === 'custom') {
            if ($request->filled('date_from')) {
                $salesQuery->whereDate('sale_date', '>=', $request->date_from);
                $expensesQuery->whereDate('activity_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $salesQuery->whereDate('sale_date', '<=', $request->date_to);
                $expensesQuery->whereDate('activity_date', '<=', $request->date_to);
            }
        }

        $totalSales = $salesQuery->sum('total_amount');
        $totalExpenses = $expensesQuery->sum('amount');
        $netProfit = $totalSales - $totalExpenses;

        $shops = Shop::all();

        return view('reports.sales_vs_expenses', compact('totalSales', 'totalExpenses', 'netProfit', 'shops', 'period'));
    }
}
