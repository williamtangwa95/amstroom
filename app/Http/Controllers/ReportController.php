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

        if ($user->isOwner()) {
            $mainStocks = \App\Models\MainStock::with('item.category')
                ->selectRaw('item_id, SUM(remaining_quantity) as qty, SUM(remaining_quantity * buying_price) as value, SUM(remaining_quantity * selling_price) as sell_value')
                ->groupBy('item_id')
                ->with('item.category')
                ->get();
        } else {
            $mainStocks = collect();
        }

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

    public function analytics(Request $request)
    {
        $user   = auth()->user();
        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');

        // ── Build base date range ───────────────────────────────────────────
        [$dateFrom, $dateTo] = $this->resolveDateRange($period, $request);

        // ── Sales query helper ─────────────────────────────────────────────
        $baseSaleQuery = function () use ($user, $shopId) {
            $q = Sale::completed()->with('items.item.category', 'seller', 'shop');
            if ($user->isOwner()) {
                $q->where('is_admin_stock', false);
            }
            if ($shopId) {
                if ($shopId === 'owner') {
                    $q->whereNull('shop_id');
                } else {
                    $q->where('shop_id', $shopId);
                }
            }
            return $q;
        };

        $sales = (clone $baseSaleQuery())
            ->whereDate('sale_date', '>=', $dateFrom)
            ->whereDate('sale_date', '<=', $dateTo)
            ->get();

        $isOwner       = $user->isOwner();
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';

        // ── Annotate each sale item with revenue / cost / profit ───────────
        $allItems = collect();
        $totalRevenue = 0;
        $totalCost    = 0;
        $totalProfit  = 0;

        foreach ($sales as $sale) {
            foreach ($sale->items as $si) {
                if ($isOwner && $si->is_admin_stock) continue;

                if ($isOwner && $isIndependent && $sale->shop_id !== null) {
                    $rev = (float)($si->owner_realized_sp ?? $si->selling_price) * $si->quantity;
                } else {
                    $rev = (float)($si->shop_realized_sp  ?? $si->selling_price) * $si->quantity;
                }
                $cost = $isOwner
                    ? (float)($si->owner_cost_price ?? 0) * $si->quantity
                    : (float)($si->shop_cost_price ?? $si->owner_realized_sp ?? 0) * $si->quantity;

                $profit = $rev - $cost;

                $si->_rev    = $rev;
                $si->_cost   = $cost;
                $si->_profit = $profit;

                $allItems->push($si);
                $totalRevenue += $rev;
                $totalCost    += $cost;
                $totalProfit  += $profit;
            }
        }

        // ── 1. Product Velocity ────────────────────────────────────────────
        $byItem = $allItems->filter(fn($si) => $si->item_id)->groupBy('item_id')->map(function ($items) {
            $first = $items->first();
            return (object)[
                'item_id'   => $first->item_id,
                'item_name' => optional($first->item)->item_name ?? 'Unknown',
                'category'  => optional(optional($first->item)->category)->name ?? '—',
                'qty_sold'  => $items->sum('quantity'),
                'revenue'   => $items->sum('_rev'),
                'profit'    => $items->sum('_profit'),
            ];
        })->values()->sortByDesc('qty_sold');

        $count = $byItem->count();
        $fastCut  = max(1, (int)ceil($count * 0.30));
        $slowCut  = max(1, (int)ceil($count * 0.30));
        $fastItems  = $byItem->take($fastCut);
        $slowItems  = $byItem->slice($count - $slowCut)->values();

        // Items in stock but zero sales in the period → "Stop Ordering"
        $soldItemIds = $byItem->pluck('item_id')->toArray();
        $stopItemsQuery = \App\Models\MainStock::with('item.category')
            ->selectRaw('item_id, SUM(remaining_quantity) as total_qty')
            ->groupBy('item_id')
            ->having('total_qty', '>', 0);
        if (!$isOwner) {
            $stopItemsQuery = \App\Models\ShopStock::with('item.category')
                ->selectRaw('item_id, SUM(remaining_quantity) as total_qty')
                ->where('shop_id', $user->shop_id)
                ->groupBy('item_id')
                ->having('total_qty', '>', 0);
        }
        $stopItems = $stopItemsQuery->get()
            ->filter(fn($s) => !in_array($s->item_id, $soldItemIds))
            ->map(fn($s) => (object)[
                'item_name' => optional($s->item)->item_name ?? 'Unknown',
                'category'  => optional(optional($s->item)->category)->name ?? '—',
                'qty_in_stock' => $s->total_qty,
            ])->values()->take(20);

        // ── 2. Profit Margin Analysis ──────────────────────────────────────
        $marginItems = $byItem->map(function ($i) {
            $margin = $i->revenue > 0 ? ($i->profit / $i->revenue) * 100 : 0;
            $tier   = $margin < 10 ? 'low' : ($margin < 25 ? 'moderate' : 'high');
            return (object)array_merge((array)$i, ['margin_pct' => $margin, 'margin_tier' => $tier]);
        })->sortByDesc('margin_pct')->values();

        $marginSummary = [
            'low'      => $marginItems->where('margin_tier', 'low')->count(),
            'moderate' => $marginItems->where('margin_tier', 'moderate')->count(),
            'high'     => $marginItems->where('margin_tier', 'high')->count(),
        ];

        // ── 3. Stock Suggestions ───────────────────────────────────────────
        $periodDays = max(1, \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1);

        $stockSuggestions = $byItem->map(function ($i) use ($periodDays, $isOwner, $user) {
            $dailyRate = $i->qty_sold / $periodDays;

            // get current stock
            if ($isOwner) {
                $currentStock = \App\Models\MainStock::where('item_id', $i->item_id)->sum('remaining_quantity');
            } else {
                $currentStock = \App\Models\ShopStock::where('item_id', $i->item_id)->where('shop_id', $user->shop_id)->sum('remaining_quantity');
            }

            $daysLeft = $dailyRate > 0 ? $currentStock / $dailyRate : PHP_INT_MAX;

            return (object)[
                'item_id'       => $i->item_id,
                'item_name'     => $i->item_name,
                'category'      => $i->category,
                'daily_rate'    => round($dailyRate, 2),
                'current_stock' => $currentStock,
                'days_left'     => $daysLeft === PHP_INT_MAX ? null : round($daysLeft, 1),
                'suggest_qty'   => (int)ceil($dailyRate * 30),
                'urgency'       => $daysLeft <= 3 ? 'critical' : ($daysLeft <= 7 ? 'warning' : 'ok'),
            ];
        })->filter(fn($s) => $s->urgency !== 'ok' || $s->days_left === null)
          ->sortBy(fn($s) => $s->days_left ?? 999)
          ->values()->take(20);

        // ── 4. Staff Performance ───────────────────────────────────────────
        $staffPerformance = $sales->groupBy('seller_id')->map(function ($group, $sellerId) {
            $rev    = $group->sum('_annotated_rev') ?: $group->sum(fn($s) => collect($s->items)->sum('_rev'));
            $profit = $group->sum(fn($s) => collect($s->items)->sum('_profit'));
            $first  = $group->first();
            return (object)[
                'seller_id'   => $sellerId,
                'seller_name' => optional($first->seller)->name ?? 'Unknown',
                'txn_count'   => $group->count(),
                'revenue'     => $group->sum(fn($s) => collect($s->items)->sum('_rev')),
                'profit'      => $group->sum(fn($s) => collect($s->items)->sum('_profit')),
                'avg_sale'    => $group->count() > 0 ? $group->sum(fn($s) => collect($s->items)->sum('_rev')) / $group->count() : 0,
            ];
        })->values()->sortByDesc('revenue');

        // ── 5. Daily Revenue Trend (last 60 days actual) ──────────────────
        $trendDays  = 60;
        $trendStart = now()->subDays($trendDays - 1)->startOfDay();
        $trendSales = (clone $baseSaleQuery())
            ->whereDate('sale_date', '>=', $trendStart)
            ->get();

        $dailyRevenue = collect();
        for ($d = 0; $d < $trendDays; $d++) {
            $day = $trendStart->copy()->addDays($d)->toDateString();
            $dayRev = $trendSales->filter(fn($s) => $s->sale_date->toDateString() === $day)
                ->sum(fn($s) => collect($s->items)->filter(fn($si) => !($isOwner && $si->is_admin_stock))
                    ->sum(fn($si) => $isOwner && $isIndependent && $s->shop_id
                        ? (float)($si->owner_realized_sp ?? $si->selling_price) * $si->quantity
                        : (float)($si->shop_realized_sp  ?? $si->selling_price) * $si->quantity
                    ));
            $dailyRevenue->push(['date' => $day, 'revenue' => $dayRev]);
        }

        // ── 6. Prediction (linear regression next 30 days) ─────────────────
        $n    = $dailyRevenue->count();
        $xArr = range(0, $n - 1);
        $yArr = $dailyRevenue->pluck('revenue')->toArray();
        $xMean = array_sum($xArr) / $n;
        $yMean = array_sum($yArr) / $n;
        $num   = 0; $den = 0;
        foreach ($xArr as $i) {
            $num += ($xArr[$i] - $xMean) * ($yArr[$i] - $yMean);
            $den += ($xArr[$i] - $xMean) ** 2;
        }
        $slope = $den > 0 ? $num / $den : 0;
        $intercept = $yMean - $slope * $xMean;

        $predictionDays = 30;
        $prediction = collect();
        for ($d = 0; $d < $predictionDays; $d++) {
            $x   = $n + $d;
            $day = now()->addDays($d + 1)->toDateString();
            $prediction->push(['date' => $day, 'predicted' => max(0, $slope * $x + $intercept)]);
        }

        // ── 7. Category Revenue Breakdown ──────────────────────────────────
        $categoryRevenue = $allItems->filter(fn($si) => $si->item_id)
            ->groupBy(fn($si) => optional(optional($si->item)->category)->name ?? 'Uncategorized')
            ->map(fn($items, $cat) => (object)[
                'category' => $cat,
                'revenue'  => $items->sum('_rev'),
                'profit'   => $items->sum('_profit'),
                'qty'      => $items->sum('quantity'),
            ])->values()->sortByDesc('revenue');

        // ── 7.5. Expenses and Sales vs Expenses ──────────────────────────────────
        $expensesQuery = Expense::whereIn('status', ['approved', 'review_requested', 'editable'])
            ->whereDate('activity_date', '>=', $dateFrom)
            ->whereDate('activity_date', '<=', $dateTo);

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

        $expenses = $expensesQuery->with('category')->get();
        $totalExpenses = $expenses->sum('amount');
        $netProfitValue = $totalProfit - $totalExpenses;

        $expensesByCategory = $expenses->groupBy('expense_category_id')->map(function ($group) {
            $first = $group->first();
            return (object)[
                'category_name' => optional($first->category)->name ?? 'Uncategorized',
                'total_amount'  => $group->sum('amount'),
                'count'         => $group->count(),
            ];
        })->values()->sortByDesc('total_amount');

        // ── 8. Shops + Items for filter dropdowns ─────────────────────────
        $shops = $user->isOwner() ? \App\Models\Shop::all() : \App\Models\Shop::where('id', $user->shop_id)->get();

        $totalTransactions = $sales->count();
        $avgOrderValue     = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        return view('reports.analytics', compact(
            'period', 'shopId', 'shops',
            'dateFrom', 'dateTo',
            'totalRevenue', 'totalCost', 'totalProfit', 'totalTransactions', 'avgOrderValue',
            'fastItems', 'slowItems', 'stopItems', 'byItem',
            'marginItems', 'marginSummary',
            'stockSuggestions',
            'staffPerformance',
            'dailyRevenue', 'prediction',
            'categoryRevenue',
            'totalExpenses', 'netProfitValue', 'expensesByCategory', 'expenses'
        ));
    }

    /** Resolve a [$from, $to] date-string pair from period/custom inputs. */
    private function resolveDateRange(string $period, \Illuminate\Http\Request $request): array
    {
        return match ($period) {
            'daily'  => [today()->toDateString(), today()->toDateString()],
            'yearly' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'custom' => [
                $request->get('date_from', now()->startOfMonth()->toDateString()),
                $request->get('date_to',   now()->toDateString()),
            ],
            default  => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }

    /**
     * Display Visitor Analytics reports.
     */
    public function visitorAnalytics(\Illuminate\Http\Request $request)
    {
        // 1. KPI Cards
        $totalPageViews = \App\Models\VisitorLog::count();
        $uniqueVisitors = \App\Models\VisitorLog::distinct('ip_address')->count('ip_address');

        // Top Device
        $topDeviceRow = \App\Models\VisitorLog::select('device_type')
            ->groupBy('device_type')
            ->orderByRaw('COUNT(*) DESC')
            ->first();
        $topDevice = $topDeviceRow ? $topDeviceRow->device_type : 'Desktop';

        // Top Country
        $topCountryRow = \App\Models\VisitorLog::select('country')
            ->groupBy('country')
            ->orderByRaw('COUNT(*) DESC')
            ->first();
        $topCountry = $topCountryRow ? $topCountryRow->country : 'Unknown';

        // 2. Top Visitor Locations
        $locations = \App\Models\VisitorLog::select('city', 'country')
            ->selectRaw('COUNT(*) as hits')
            ->groupBy('city', 'country')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->get();

        // 3. Devices Stats
        $deviceStats = \App\Models\VisitorLog::select('device_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('device_type')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        // 4. Browser Stats
        $browserStats = \App\Models\VisitorLog::select('browser')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('browser')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        // 5. Visitor logs list (Last 1000)
        $visitorLogs = \App\Models\VisitorLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        return view('reports.visitors', compact(
            'totalPageViews', 'uniqueVisitors', 'topDevice', 'topCountry',
            'locations', 'deviceStats', 'browserStats', 'visitorLogs'
        ));
    }
}
