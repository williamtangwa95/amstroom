<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Item;
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
        $user = auth()->user();
        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
        $itemId = $request->get('item_id');
        $stockType = $request->get('stock_type');

        if ($user->isOwner()) {
            $stockType = 'normal';
        }

        $query = Sale::completed()->with('shop', 'seller');

        if ($stockType === 'admin') {
            $query->where(function ($q) {
                $q->where('is_admin_stock', true)
                  ->orWhereHas('items', function ($sq) {
                      $sq->where('is_admin_stock', true);
                  });
            });
        } elseif ($stockType === 'normal') {
            $query->where('is_admin_stock', false);
        } elseif ($stockType === 'all') {
            // No constraint on is_admin_stock
        } else {
            // Default behavior
            if ($user->isOwner()) {
                $query->where('is_admin_stock', false);
            }
        }

        if ($shopId) {
            if ($shopId === 'owner') {
                $query->whereNull('shop_id');
            } else {
                $query->where('shop_id', $shopId);
            }
        }

        if ($itemId) {
            $query->whereHas('items', function ($q) use ($itemId) {
                $q->where('item_id', $itemId);
            });
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

        $sales = $query->latest()->with('items.item')->get();

        $isOwner = auth()->check() && auth()->user()->isOwner();
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        $totalAdminRevenue = 0;
        $totalAdminProfit = 0;
        $totalNormalRevenue = 0;
        $totalNormalProfit = 0;

        foreach ($sales as $sale) {
            $filteredItems = $sale->items
                ->when($itemId, function ($items) use ($itemId) {
                    return $items->where('item_id', $itemId);
                })
                ->when($stockType === 'admin', function ($items) {
                    return $items->where('is_admin_stock', true);
                })
                ->when($stockType === 'normal', function ($items) {
                    return $items->where('is_admin_stock', false);
                })
                ->when(empty($stockType) && auth()->user()->isOwner(), function ($items) {
                    return $items->where('is_admin_stock', false);
                });

            $saleRevenue = 0;
            $saleCost = 0;

            foreach ($filteredItems as $item) {
                if ($isOwner && $isIndependent && $sale->shop_id !== null) {
                    $itemRevenue = (float) ($item->owner_realized_sp ?? $item->selling_price) * $item->quantity;
                } else {
                    $itemRevenue = (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
                }

                if ($isOwner) {
                    $itemCost = (float) ($item->owner_cost_price ?? 0) * $item->quantity;
                } else {
                    $itemCost = (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? 0) * $item->quantity;
                }

                $itemProfit = $itemRevenue - $itemCost;

                $saleRevenue += $itemRevenue;
                $saleCost += $itemCost;

                if ($item->is_admin_stock) {
                    $totalAdminRevenue += $itemRevenue;
                    $totalAdminProfit += $itemProfit;
                } else {
                    $totalNormalRevenue += $itemRevenue;
                    $totalNormalProfit += $itemProfit;
                }
            }

            $saleProfit = $saleRevenue - $saleCost;

            $sale->filtered_revenue = $saleRevenue;
            $sale->filtered_cost = $saleCost;
            $sale->filtered_profit = $saleProfit;

            $totalRevenue += $saleRevenue;
            $totalCost += $saleCost;
            $totalProfit += $saleProfit;
        }

        // Summary by shop computed dynamically using filtered amounts
        $salesByShop = $sales->groupBy('shop_id')->map(function ($group, $shopId) {
            return (object) [
                'shop_id' => $shopId,
                'shop' => $group->first()->shop,
                'count' => $group->count(),
                'revenue' => $group->sum(fn($s) => $s->filtered_revenue),
                'profit' => $group->sum(fn($s) => $s->filtered_profit),
            ];
        })->values();

        $shops = $user->isOwner() ? Shop::all() : Shop::where('id', $user->shop_id)->get();
        $items = Item::when($user->isOwner(), fn($q) => $q->where('is_admin_item', false))
            ->when(!$user->isOwner(), fn($q) => $q->where(function ($sq) use ($user) {
                $sq->where('is_admin_item', false)
                  ->orWhere(function ($ssq) use ($user) {
                      $ssq->where('is_admin_item', true)
                         ->where('shop_id', $user->shop_id);
                  });
            }))
            ->orderBy('item_name')
            ->get();

        // Chart data computed dynamically
        $chartData = $sales->where('sale_date', '>=', now()->subDays(30))
            ->groupBy(fn($s) => $s->sale_date->toDateString())
            ->map(function ($group, $date) {
                return (object) [
                    'sale_date' => \Carbon\Carbon::parse($date),
                    'total' => $group->sum(fn($s) => $s->filtered_revenue),
                ];
            })->sortBy('sale_date')->values();

        // Build header info for Excel/PDF exports
        $reportHeader = [];
        if ($user->isOwner()) {
            $reportHeader = [
                'name'    => \App\Models\Setting::get('system_name', 'AMSTROOM'),
                'slogan'  => \App\Models\Setting::get('slogan', ''),
                'address' => \App\Models\Setting::get('company_address', ''),
                'tin'     => \App\Models\Setting::get('company_tin', ''),
                'phone'   => '',
                'type'    => 'owner',
            ];
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            $reportHeader = [
                'name'    => $shop?->shop_name ?? 'My Shop',
                'slogan'  => $shop?->slogan ?? '',
                'address' => $shop?->address ?? '',
                'tin'     => $shop?->tin_number ?? '',
                'phone'   => $shop?->phone ?? '',
                'type'    => 'admin',
            ];
        }

        return view('reports.sales', compact(
            'sales', 'totalRevenue', 'totalProfit', 'salesByShop', 'shops', 'items',
            'period', 'chartData', 'itemId', 'reportHeader',
            'totalAdminRevenue', 'totalAdminProfit', 'totalNormalRevenue', 'totalNormalProfit',
            'stockType'
        ));

    }

    public function stock(Request $request)
    {
        $user = auth()->user();
        $type = $request->get('type', 'main');
        if (!$user->isOwner()) {
            $type = 'shop';
        }

        $mainStocks = \App\Models\MainStock::with('item.category')
            ->selectRaw('item_id, SUM(remaining_quantity) as qty, SUM(remaining_quantity * buying_price) as value, SUM(remaining_quantity * selling_price) as sell_value')
            ->groupBy('item_id')
            ->with('item.category')
            ->get();

        $shopStocksQuery = \App\Models\ShopStock::with('item.category', 'shop')
            ->where('remaining_quantity', '>', 0);

        if (!$user->isOwner()) {
            $shopStocksQuery->where('shop_id', $user->shop_id);
        } else {
            $shopStocksQuery->where('is_admin_stock', false);
        }

        $shopStocks = $shopStocksQuery->get();

        // Report header (same pattern as sales)
        $reportHeader = [];
        if ($user->isOwner()) {
            $reportHeader = [
                'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
                'slogan' => \App\Models\Setting::get('slogan', ''),
                'address'=> \App\Models\Setting::get('company_address', ''),
                'tin'    => \App\Models\Setting::get('company_tin', ''),
                'phone'  => '',
                'type'   => 'owner',
            ];
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            $reportHeader = [
                'name'   => $shop?->shop_name ?? 'My Shop',
                'slogan' => $shop?->slogan ?? '',
                'address'=> $shop?->address ?? '',
                'tin'    => $shop?->tin_number ?? '',
                'phone'  => $shop?->phone ?? '',
                'type'   => 'admin',
            ];
        }

        // Totals for PDF footer rows
        $mainTotalQty       = $mainStocks->sum('qty');
        $mainTotalValue     = $mainStocks->sum('value');
        $mainTotalSellValue = $mainStocks->sum('sell_value');

        $shopTotalQty       = $shopStocks->sum('remaining_quantity');
        $shopTotalValuation = $shopStocks->sum(fn($s) => $s->remaining_quantity * $s->selling_price);

        return view('reports.stock', compact(
            'mainStocks', 'shopStocks', 'type', 'reportHeader',
            'mainTotalQty', 'mainTotalValue', 'mainTotalSellValue',
            'shopTotalQty', 'shopTotalValuation'
        ));

    }

    public function transfer(Request $request)
    {
        $status = $request->get('status', 'all');
        $user = auth()->user();

        $query = StockRequest::with('shop', 'requester', 'transfer');

        if ($user->isShopAdmin()) {
            $query->where('shop_id', $user->shop_id);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->latest()->get();
        $stats = [
            'pending'  => StockRequest::when($user->isShopAdmin(), fn($q) => $q->where('shop_id', $user->shop_id))->where('status', 'pending')->count(),
            'approved' => StockRequest::when($user->isShopAdmin(), fn($q) => $q->where('shop_id', $user->shop_id))->where('status', 'approved')->count(),
            'rejected' => StockRequest::when($user->isShopAdmin(), fn($q) => $q->where('shop_id', $user->shop_id))->where('status', 'rejected')->count(),
        ];

        return view('reports.transfer', compact('requests', 'stats', 'status'));
    }

    public function defect(Request $request)
    {
        $user = auth()->user();
        $query = Defect::with('shop', 'item.category', 'reporter');

        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $defects = $query->latest()->get();
        $totalDefective = $query->sum('quantity');
        $shops = $user->isOwner() ? Shop::all() : Shop::where('id', $user->shop_id)->get();

        // Report header (same pattern as sales, stock, and expenses)
        $reportHeader = [];
        if ($user->isOwner()) {
            $reportHeader = [
                'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
                'slogan' => \App\Models\Setting::get('slogan', ''),
                'address'=> \App\Models\Setting::get('company_address', ''),
                'tin'    => \App\Models\Setting::get('company_tin', ''),
                'phone'  => '',
                'type'   => 'owner',
            ];
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            $reportHeader = [
                'name'   => $shop?->shop_name ?? 'My Shop',
                'slogan' => $shop?->slogan ?? '',
                'address'=> $shop?->address ?? '',
                'tin'    => $shop?->tin_number ?? '',
                'phone'  => $shop?->phone ?? '',
                'type'   => 'admin',
            ];
        }

        return view('reports.defect', compact('defects', 'totalDefective', 'shops', 'reportHeader'));
    }

    public function expenses(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
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
        $totalAmount = $expenses->sum('amount');

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
        $shops = $user->isOwner() ? Shop::all() : Shop::where('id', $user->shop_id)->get();

        // Report header (same pattern as sales and stock)
        $reportHeader = [];
        if ($user->isOwner()) {
            $reportHeader = [
                'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
                'slogan' => \App\Models\Setting::get('slogan', ''),
                'address'=> \App\Models\Setting::get('company_address', ''),
                'tin'    => \App\Models\Setting::get('company_tin', ''),
                'phone'  => '',
                'type'   => 'owner',
            ];
        } elseif ($user->isShopAdmin()) {
            $shop = $user->shop;
            $reportHeader = [
                'name'   => $shop?->shop_name ?? 'My Shop',
                'slogan' => $shop?->slogan ?? '',
                'address'=> $shop?->address ?? '',
                'tin'    => $shop?->tin_number ?? '',
                'phone'  => $shop?->phone ?? '',
                'type'   => 'admin',
            ];
        }

        return view('reports.expenses', compact('expenses', 'totalAmount', 'expensesByCategory', 'categories', 'shops', 'period', 'reportHeader'));
    }

    public function salesVsExpenses(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');

        // 1. Get Sales
        $salesQuery = Sale::completed();
        if ($user->isOwner()) {
            $salesQuery->where('is_admin_stock', false);
        }
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

        $totalSales = $salesQuery->with('items')->get()->sum(fn($s) => $s->report_revenue);
        $totalExpenses = $expensesQuery->sum('amount');
        $netProfit = $totalSales - $totalExpenses;

        $shops = $user->isOwner() ? Shop::all() : Shop::where('id', $user->shop_id)->get();

        return view('reports.sales_vs_expenses', compact('totalSales', 'totalExpenses', 'netProfit', 'shops', 'period'));
    }
}
