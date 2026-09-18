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
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';

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

                if ($item->parent_id !== null) {
                    $itemCost = 0.0;
                } elseif ($isOwner) {
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

        $itemId = $request->get('item_id');

        // ── Sales query helper ─────────────────────────────────────────────
        $baseSaleQuery = function () use ($user, $shopId, $itemId) {
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
            if ($itemId) {
                $q->whereHas('items', function ($sq) use ($itemId) {
                    $sq->where('item_id', $itemId);
                });
            }
            return $q;
        };

        $sales = (clone $baseSaleQuery())
            ->whereDate('sale_date', '>=', $dateFrom)
            ->whereDate('sale_date', '<=', $dateTo)
            ->get();

        $isOwner       = $user->isOwner();
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';

        // ── Annotate each sale item with revenue / cost / profit ───────────
        $allItems = collect();
        $totalRevenue = 0;
        $totalCost    = 0;
        $totalProfit  = 0;

        foreach ($sales as $sale) {
            foreach ($sale->items as $si) {
                if ($isOwner && $si->is_admin_stock) continue;
                if ($itemId && $si->item_id != $itemId) continue;

                if ($isOwner && $isIndependent && $sale->shop_id !== null) {
                    $rev = (float)($si->owner_realized_sp ?? $si->selling_price) * $si->quantity;
                } else {
                    $rev = (float)($si->shop_realized_sp  ?? $si->selling_price) * $si->quantity;
                }
                $cost = ($si->parent_id !== null) ? 0.0 : ($isOwner
                    ? (float)($si->owner_cost_price ?? 0) * $si->quantity
                    : (float)($si->shop_cost_price ?? $si->owner_realized_sp ?? 0) * $si->quantity);

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
        if ($itemId) {
            $stopItemsQuery->where('item_id', $itemId);
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

    public function salesData(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
        $itemId = $request->get('item_id');
        $stockType = $request->get('stock_type');

        if ($user->isOwner()) {
            $stockType = 'normal';
        }

        $query = Sale::completed();

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
            // No constraint
        } else {
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

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $cleanId = preg_replace('/[^0-9]/', '', $searchValue);
                if ($cleanId !== '') {
                    $q->orWhere('sales.id', $cleanId);
                }
                $q->orWhere('sales.customer_name', 'like', "%{$searchValue}%")
                  ->orWhere('sales.payment_method', 'like', "%{$searchValue}%")
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('seller', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('items.item', function ($sq) use ($searchValue) {
                      $sq->where('item_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $sales = $query->with('shop', 'seller', 'items.item')
            ->orderBy('sales.sale_date', 'desc')
            ->orderBy('sales.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $isOwner = auth()->check() && auth()->user()->isOwner();
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';

        $data = [];
        foreach ($sales as $index => $sl) {
            $iteration = $start + $index + 1;
            $saleDate = $sl->sale_date ? $sl->sale_date->format('M d, Y') : 'N/A';
            $shopName = e($sl->shop?->shop_name ?? 'Main Store (Owner)');
            $sellerName = e($sl->seller?->name ?? 'System');
            $customerName = e($sl->customer_name ?: 'Walk-in');

            $displayItems = $sl->items;
            if ($itemId) {
                $displayItems = $displayItems->where('item_id', $itemId);
            }
            if (($stockType ?? '') === 'admin') {
                $displayItems = $displayItems->where('is_admin_stock', true);
            } elseif (($stockType ?? '') === 'normal') {
                $displayItems = $displayItems->where('is_admin_stock', false);
            } elseif (empty($stockType) && $isOwner) {
                $displayItems = $displayItems->where('is_admin_stock', false);
            }

            $itemsHtml = '';
            $saleRevenue = 0;
            $saleCost = 0;

            foreach ($displayItems as $item) {
                if ($isOwner && $isIndependent && $sl->shop_id !== null) {
                    $itemRevenue = (float) ($item->owner_realized_sp ?? $item->selling_price) * $item->quantity;
                } else {
                    $itemRevenue = (float) ($item->shop_realized_sp ?? $item->selling_price) * $item->quantity;
                }

                if ($item->parent_id !== null) {
                    $itemCost = 0.0;
                } elseif ($isOwner) {
                    $itemCost = (float) ($item->owner_cost_price ?? 0) * $item->quantity;
                } else {
                    $itemCost = (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? 0) * $item->quantity;
                }

                $saleRevenue += $itemRevenue;
                $saleCost += $itemCost;

                $itemsHtml .= '<div style="font-size:.78rem;line-height:1.4;margin-bottom:2px;" class="d-flex align-items-center gap-1 flex-wrap">';
                $itemsHtml .= '<span>' . e($item->item?->item_name ?? 'Unknown Item') . ' (x' . $item->quantity . ')</span>';
                if (!$isOwner) {
                    if ($item->is_admin_stock) {
                        $itemsHtml .= ' <span class="badge bg-info text-dark" style="font-size:.65rem;padding:.15rem .3rem;"><i class="bi bi-person-fill-lock"></i> Admin</span>';
                    } else {
                        $itemsHtml .= ' <span class="badge bg-secondary" style="font-size:.65rem;padding:.15rem .3rem;"><i class="bi bi-shop"></i> Normal</span>';
                    }
                }
                $itemsHtml .= '</div>';
            }

            $saleProfit = $saleRevenue - $saleCost;
            $method = e(str_replace('_', ' ', ucfirst($sl->payment_method)));
            $revenueHtml = '<strong style="color:#3fb950;">TZS ' . number_format($saleRevenue, 0) . '</strong>';
            $profitHtml = '<strong style="color:#ffc107;">TZS ' . number_format($saleProfit, 0) . '</strong>';

            $data[] = [
                'iteration' => $iteration,
                'sale_date' => $saleDate,
                'shop' => $shopName,
                'seller' => $sellerName,
                'customer' => $customerName,
                'items' => $itemsHtml,
                'method' => $method,
                'revenue' => $revenueHtml,
                'profit' => $profitHtml,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function stockData(Request $request)
    {
        $user = auth()->user();
        $type = $request->get('type', 'main');
        if (!$user->isOwner()) {
            $type = 'shop';
        }

        if ($type === 'main') {
            $groupedQuery = \App\Models\MainStock::selectRaw('item_id, SUM(remaining_quantity) as qty, SUM(remaining_quantity * buying_price) as value, SUM(remaining_quantity * selling_price) as sell_value')
                ->groupBy('item_id');

            $recordsTotal = DB::table(DB::raw("({$groupedQuery->toBase()->toSql()}) as sub"))
                ->mergeBindings($groupedQuery->toBase())
                ->count();

            $searchValue = trim($request->input('search.value', ''));
            if ($searchValue !== '') {
                $groupedQuery->whereHas('item', function($q) use ($searchValue) {
                    $q->where('item_name', 'like', "%{$searchValue}%")
                      ->orWhereHas('category', function($cq) use ($searchValue) {
                          $cq->where('category_name', 'like', "%{$searchValue}%");
                      });
                });
            }
            $recordsFiltered = DB::table(DB::raw("({$groupedQuery->toBase()->toSql()}) as sub"))
                ->mergeBindings($groupedQuery->toBase())
                ->count();

            $start = max(0, (int) $request->input('start', 0));
            $allowedLengths = [10, 25, 50, 100];
            $requestedLength = (int) $request->input('length', 10);
            $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

            $stocks = $groupedQuery->with('item.category')->skip($start)->take($length)->get();

            $data = [];
            foreach ($stocks as $index => $ms) {
                $iteration = $start + $index + 1;
                $productName = e($ms->item?->item_name ?? 'N/A');
                $categoryName = '<span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">' . e($ms->item?->category?->category_name ?? 'General') . '</span>';
                $qtyHtml = '<strong style="color:' . ($ms->qty > 0 ? '#3fb950' : '#e94560') . '">' . $ms->qty . '</strong>';
                $valueHtml = 'TZS ' . number_format($ms->value, 0);
                $sellValueHtml = '<strong style="color:#58a6ff;">TZS ' . number_format($ms->sell_value, 0) . '</strong>';

                $data[] = [
                    'iteration' => $iteration,
                    'product' => $productName,
                    'category' => $categoryName,
                    'qty' => $qtyHtml,
                    'value' => $valueHtml,
                    'sell_value' => $sellValueHtml,
                ];
            }
        } else {
            $query = \App\Models\ShopStock::with('item.category', 'shop')
                ->where('remaining_quantity', '>', 0);

            if (!$user->isOwner()) {
                $query->where('shop_id', $user->shop_id);
            } else {
                $query->where('is_admin_stock', false);
            }

            $recordsTotal = (clone $query)->count();

            $searchValue = trim($request->input('search.value', ''));
            if ($searchValue !== '') {
                $query->where(function($q) use ($searchValue) {
                    $q->orWhereHas('item', function($sq) use ($searchValue) {
                        $sq->where('item_name', 'like', "%{$searchValue}%")
                          ->orWhereHas('category', function($cq) use ($searchValue) {
                              $cq->where('category_name', 'like', "%{$searchValue}%");
                          });
                    })->orWhereHas('shop', function($sq) use ($searchValue) {
                        $sq->where('shop_name', 'like', "%{$searchValue}%");
                    });
                });
            }

            $recordsFiltered = (clone $query)->count();

            $start = max(0, (int) $request->input('start', 0));
            $allowedLengths = [10, 25, 50, 100];
            $requestedLength = (int) $request->input('length', 10);
            $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

            $stocks = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($stocks as $index => $ss) {
                $iteration = $start + $index + 1;
                $shopName = e($ss->shop?->shop_name ?? 'N/A');
                $productName = e($ss->item?->item_name ?? 'N/A');
                $categoryName = '<span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">' . e($ss->item?->category?->category_name ?? 'General') . '</span>';
                $qtyHtml = '<strong style="color:' . ($ss->isLowStock() ? '#e94560' : '#3fb950') . '">' . $ss->remaining_quantity . '</strong>';
                $priceHtml = 'TZS ' . number_format($ss->selling_price, 0);
                $totalValuationHtml = '<strong style="color:#58a6ff;">TZS ' . number_format($ss->remaining_quantity * $ss->selling_price, 0) . '</strong>';

                $data[] = [
                    'iteration' => $iteration,
                    'shop' => $shopName,
                    'product' => $productName,
                    'category' => $categoryName,
                    'qty' => $qtyHtml,
                    'price' => $priceHtml,
                    'total_valuation' => $totalValuationHtml,
                ];
            }
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function transferData(Request $request)
    {
        $status = $request->get('status', 'all');
        $user = auth()->user();

        $query = StockRequest::query();

        if ($user->isShopAdmin()) {
            $query->where('shop_id', $user->shop_id);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $cleanId = preg_replace('/[^0-9]/', '', $searchValue);
                if ($cleanId !== '') {
                    $q->orWhere('stock_requests.id', $cleanId);
                }
                $q->orWhere('stock_requests.status', 'like', "%{$searchValue}%")
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('requester', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $requests = $query->with('shop', 'requester', 'items')
            ->orderBy('stock_requests.request_date', 'desc')
            ->orderBy('stock_requests.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($requests as $index => $req) {
            $iteration = $start + $index + 1;
            $requestId = '#REQ-' . $req->id;
            $shopName = e($req->shop?->shop_name ?? 'N/A');
            $requesterName = e($req->requester?->name ?? 'N/A');
            $requestDate = $req->request_date ? $req->request_date->format('M d, Y') : 'N/A';
            $statusBadge = '<span class="status-badge badge-' . $req->status . '">' . e(ucfirst($req->status)) . '</span>';
            $itemsCount = '<span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">' . $req->items->count() . ' item(s)</span>';

            $data[] = [
                'iteration' => $iteration,
                'request_id' => $requestId,
                'shop' => $shopName,
                'requester' => $requesterName,
                'request_date' => $requestDate,
                'status' => $statusBadge,
                'items' => $itemsCount,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function defectData(Request $request)
    {
        $user = auth()->user();
        $query = Defect::query();

        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('defects.reason', 'like', "%{$searchValue}%")
                  ->orWhere('defects.status', 'like', "%{$searchValue}%")
                  ->orWhereHas('item', function ($sq) use ($searchValue) {
                      $sq->where('item_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('shop', function ($sq) use ($searchValue) {
                      $sq->where('shop_name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('reporter', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $defects = $query->with('shop', 'item', 'reporter')
            ->orderBy('defects.date', 'desc')
            ->orderBy('defects.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($defects as $index => $def) {
            $iteration = $start + $index + 1;
            $dateStr = $def->date ? $def->date->format('M d, Y') : 'N/A';
            $location = e($def->shop ? $def->shop->shop_name : 'Main Warehouse');
            $productName = e($def->item?->item_name ?? 'N/A');
            $qtyHtml = '<strong style="color:#e94560;">' . $def->quantity . '</strong>';
            $reason = e($def->reason);
            $reporterName = e($def->reporter?->name ?? 'N/A');
            $statusBadge = '<span class="status-badge badge-' . ($def->status === 'resolved' ? 'approved' : 'rejected') . '">' . e(ucfirst($def->status)) . '</span>';

            $data[] = [
                'iteration' => $iteration,
                'date' => $dateStr,
                'location' => $location,
                'product' => $productName,
                'qty' => $qtyHtml,
                'reason' => $reason,
                'reporter' => $reporterName,
                'status' => $statusBadge,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function expensesData(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
        $categoryId = $request->get('expense_category_id');

        $query = Expense::whereIn('status', ['approved', 'review_requested', 'editable']);

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

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('expenses.activity', 'like', "%{$searchValue}%")
                  ->orWhere('expenses.description', 'like', "%{$searchValue}%")
                  ->orWhereHas('category', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('recorder', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('approver', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $expenses = $query->with('category', 'recorder', 'approver')
            ->orderBy('expenses.activity_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($expenses as $index => $exp) {
            $iteration = $start + $index + 1;
            $dateStr = $exp->activity_date ? $exp->activity_date->format('M d, Y') : 'N/A';
            $categoryBadge = '<span class="badge" style="background:rgba(188,140,255,.12);color:#bc8cff;">' . e($exp->category?->name ?? 'General') . '</span>';
            $activity = '<strong>' . e($exp->activity) . '</strong>';
            $recorderName = e($exp->recorder?->name ?? '—');
            $approverName = e($exp->approver?->name ?? '—');
            $amountHtml = '<strong class="text-danger">TZS ' . number_format($exp->amount, 0) . '</strong>';

            $data[] = [
                'iteration' => $iteration,
                'date' => $dateStr,
                'category' => $categoryBadge,
                'activity' => $activity,
                'recorder' => $recorderName,
                'approver' => $approverName,
                'amount' => $amountHtml,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function visitorData(Request $request)
    {
        $query = \App\Models\VisitorLog::query();

        $recordsTotal = (clone $query)->count();

        $searchValue = trim($request->input('search.value', ''));
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('ip_address', 'like', "%{$searchValue}%")
                  ->orWhere('city', 'like', "%{$searchValue}%")
                  ->orWhere('country', 'like', "%{$searchValue}%")
                  ->orWhere('platform', 'like', "%{$searchValue}%")
                  ->orWhere('browser', 'like', "%{$searchValue}%")
                  ->orWhere('url', 'like', "%{$searchValue}%")
                  ->orWhereHas('user', function ($sq) use ($searchValue) {
                      $sq->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $start = max(0, (int) $request->input('start', 0));
        $allowedLengths = [10, 25, 50, 100];
        $requestedLength = (int) $request->input('length', 10);
        $length = in_array($requestedLength, $allowedLengths, true) ? $requestedLength : 10;

        $visitorLogs = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($visitorLogs as $log) {
            $timeHtml = '<div style="font-weight: 600; font-size: .78rem;">' . e($log->created_at->format('M d, H:i:s')) . '</div><div class="text-muted" style="font-size: .68rem; margin-top: 1px;">' . e($log->created_at->diffForHumans()) . '</div>';
            $ipAddress = e($log->ip_address);
            $location = '<span class="text-dark"><i class="bi bi-geo-alt-fill text-muted me-1" style="font-size: .85rem;"></i>' . e($log->city ?: 'Unknown') . ', ' . e($log->country ?: 'Unknown') . '</span>';
            $deviceBrowser = '<div style="font-weight: 600; font-size: .78rem;">' . e($log->platform ?: 'Unknown') . ' / ' . e($log->browser ?: 'Unknown') . '</div>';
            $requestUrl = '<div style="font-size: .75rem;" class="text-truncate" style="max-width: 250px;">' . e($log->url) . '</div>';
            $userAccount = e($log->user?->name ?? 'Guest');

            $data[] = [
                'time' => $timeHtml,
                'ip' => $ipAddress,
                'location' => $location,
                'device' => $deviceBrowser,
                'request' => $requestUrl,
                'user' => $userAccount,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function sendSalesEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'note'   => 'nullable|string|max:1000',
        ]);

        $emailsArray = array_map('trim', explode(',', $request->input('emails')));
        $emailsArray = array_filter($emailsArray, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emailsArray)) {
            return response()->json(['message' => 'Please provide at least one valid recipient email address.'], 422);
        }

        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
        $itemId = $request->get('item_id');
        $stockType = $request->get('stock_type');

        if ($user->isOwner()) {
            $stockType = 'normal';
        }

        $query = Sale::completed()->with(['shop', 'seller', 'items.item']);

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
            // No constraint
        } else {
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

        $periodLabel = '';
        if ($period === 'daily') {
            $query->whereDate('sale_date', today());
            $periodLabel = 'Today (' . today()->format('d M Y') . ')';
        } elseif ($period === 'monthly') {
            $query->whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year);
            $periodLabel = now()->format('F Y');
        } elseif ($period === 'yearly') {
            $query->whereYear('sale_date', now()->year);
            $periodLabel = 'Year ' . now()->format('Y');
        } elseif ($period === 'custom') {
            if ($request->filled('date_from')) $query->whereDate('sale_date', '>=', $request->date_from);
            if ($request->filled('date_to'))   $query->whereDate('sale_date', '<=', $request->date_to);
            $periodLabel = ($request->date_from ?? 'Start') . ' to ' . ($request->date_to ?? 'End');
        } else {
            $periodLabel = ucfirst($period);
        }

        $sales = $query->orderBy('sale_date', 'desc')->orderBy('id', 'desc')->get();

        $isOwner = $user->isOwner();
        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';

        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;
        $salesList = [];

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
                ->when(empty($stockType) && $isOwner, function ($items) {
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

                if ($item->parent_id !== null) {
                    $itemCost = 0.0;
                } elseif ($isOwner) {
                    $itemCost = (float) ($item->owner_cost_price ?? 0) * $item->quantity;
                } else {
                    $itemCost = (float) ($item->shop_cost_price ?? $item->owner_realized_sp ?? 0) * $item->quantity;
                }

                $saleRevenue += $itemRevenue;
                $saleCost += $itemCost;
            }

            $saleProfit = $saleRevenue - $saleCost;
            $sale->filtered_revenue = $saleRevenue;
            $sale->filtered_profit = $saleProfit;

            $totalRevenue += $saleRevenue;
            $totalCost += $saleCost;
            $totalProfit += $saleProfit;

            $itemsSummary = [];
            foreach ($filteredItems as $fi) {
                $name = $fi->item ? $fi->item->item_name : ($fi->custom_name ?: 'Custom Item');
                $itemsSummary[] = "{$name} (x{$fi->quantity})";
            }

            $salesList[] = [
                'id'       => $sale->id,
                'date'     => $sale->sale_date ? $sale->sale_date->format('d M Y') : 'N/A',
                'shop'     => $sale->shop?->shop_name ?? ($sale->shop_id === null ? 'Main Store (Owner)' : 'Shop'),
                'seller'   => $sale->seller?->name ?? 'System',
                'customer' => $sale->customer_name ?: 'Walk-in',
                'method'   => strtoupper($sale->payment_method ?: 'Cash'),
                'items'    => $itemsSummary,
                'revenue'  => $saleRevenue,
                'profit'   => $saleProfit,
            ];
        }

        // Limit sales rows in email to avoid huge payload if thousands of sales, e.g. first 100
        $hasMoreSales = count($salesList) > 100;
        $displaySalesList = array_slice($salesList, 0, 100);

        // Sales by shop summary
        $salesByShop = $sales->groupBy('shop_id')->map(function ($group, $sId) {
            return [
                'shop_name' => $group->first()->shop ? $group->first()->shop->shop_name : ($sId === null ? 'Main Store (Owner)' : 'Shop'),
                'count'     => $group->count(),
                'revenue'   => $group->sum(fn($s) => $s->filtered_revenue),
                'profit'    => $group->sum(fn($s) => $s->filtered_profit),
            ];
        })->values()->toArray();

        // Scope / Filter descriptions
        $shopLabel = 'All Shops';
        if ($shopId === 'owner') {
            $shopLabel = 'Main Store (Owner)';
        } elseif ($shopId) {
            $shopObj = Shop::find($shopId);
            $shopLabel = $shopObj ? $shopObj->shop_name : 'Selected Shop';
        }

        $itemLabel = null;
        if ($itemId) {
            $itemObj = Item::find($itemId);
            $itemLabel = $itemObj ? $itemObj->item_name : null;
        }

        $stockTypeLabel = null;
        if ($stockType === 'admin') {
            $stockTypeLabel = 'Admin Stock Only';
        } elseif ($stockType === 'normal') {
            $stockTypeLabel = 'Normal Stock Only';
        } elseif ($stockType === 'all') {
            $stockTypeLabel = 'All Stock Types';
        }

        $branding = [
            'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan' => \App\Models\Setting::get('slogan', 'Technology Innovations'),
        ];
        if (!$isOwner && $user->shop) {
            $branding['name'] = $user->shop->shop_name ?: $branding['name'];
            $branding['slogan'] = $user->shop->slogan ?: $branding['slogan'];
        }

        $reportData = [
            'scope'              => $shopLabel,
            'period_label'       => $periodLabel,
            'shop_label'         => $shopLabel,
            'item_label'         => $itemLabel,
            'stock_type_label'   => $stockTypeLabel,
            'total_revenue'      => $totalRevenue,
            'total_profit'       => $totalProfit,
            'total_transactions' => $sales->count(),
            'sales_by_shop'      => $salesByShop,
            'sales_list'         => $displaySalesList,
            'has_more_sales'     => $hasMoreSales,
            'note'               => $request->input('note'),
            'sender_name'        => $user->name,
            'generated_at'       => now()->format('d M Y H:i:s'),
            'branding'           => $branding,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\FilteredSalesReportMail($reportData));
            return response()->json([
                'success' => true,
                'message' => 'Sales report has been successfully sent to ' . implode(', ', $emailsArray) . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send sales report email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendStockEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'note'   => 'nullable|string|max:1000',
        ]);

        $emailsArray = array_map('trim', explode(',', $request->input('emails')));
        $emailsArray = array_filter($emailsArray, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emailsArray)) {
            return response()->json(['message' => 'Please provide at least one valid recipient email address.'], 422);
        }

        $type = $request->get('type', 'main');
        if (!$user->isOwner()) {
            $type = 'shop';
        }

        $itemsList = [];
        $totalQty = 0;
        $totalCostValue = null;
        $totalSellValue = 0;
        $stockTypeLabel = '';
        $isShopView = false;
        $showShopColumn = false;

        if ($type === 'main' && $user->isOwner()) {
            $stockTypeLabel = 'Main Store Stock';
            $mainStocks = \App\Models\MainStock::with('item.category')
                ->selectRaw('item_id, SUM(remaining_quantity) as qty, SUM(remaining_quantity * buying_price) as value, SUM(remaining_quantity * selling_price) as sell_value')
                ->groupBy('item_id')
                ->get();

            $totalQty = (float) $mainStocks->sum('qty');
            $totalCostValue = (float) $mainStocks->sum('value');
            $totalSellValue = (float) $mainStocks->sum('sell_value');

            foreach ($mainStocks as $ms) {
                $itemsList[] = [
                    'name'      => $ms->item?->item_name ?? 'Unknown Item',
                    'category'  => $ms->item?->category?->category_name ?? '—',
                    'qty'       => (float) $ms->qty,
                    'valuation' => (float) $ms->sell_value,
                    'cost'      => (float) $ms->value,
                ];
            }
        } else {
            $stockTypeLabel = $user->isOwner() ? 'Shop Stock Distribution' : ($user->shop?->shop_name . ' Stock');
            $isShopView = true;
            $showShopColumn = $user->isOwner();

            $shopStocksQuery = \App\Models\ShopStock::with('item.category', 'shop')
                ->where('remaining_quantity', '>', 0);

            if (!$user->isOwner()) {
                $shopStocksQuery->where('shop_id', $user->shop_id);
            } else {
                $shopStocksQuery->where('is_admin_stock', false);
            }

            $shopStocks = $shopStocksQuery->get();

            $totalQty = (float) $shopStocks->sum('remaining_quantity');
            $totalSellValue = (float) $shopStocks->sum(fn($s) => $s->remaining_quantity * $s->selling_price);

            foreach ($shopStocks as $ss) {
                $itemsList[] = [
                    'name'      => $ss->item?->item_name ?? 'Unknown Item',
                    'category'  => $ss->item?->category?->category_name ?? '—',
                    'shop'      => $ss->shop?->shop_name ?? 'Shop',
                    'qty'       => (float) $ss->remaining_quantity,
                    'valuation' => (float) ($ss->remaining_quantity * $ss->selling_price),
                ];
            }
        }

        $hasMore = count($itemsList) > 100;
        $displayItemsList = array_slice($itemsList, 0, 100);

        $branding = [
            'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan' => \App\Models\Setting::get('slogan', 'Technology Innovations'),
        ];
        if (!$user->isOwner() && $user->shop) {
            $branding['name'] = $user->shop->shop_name ?: $branding['name'];
            $branding['slogan'] = $user->shop->slogan ?: $branding['slogan'];
        }

        $reportData = [
            'type'               => $type,
            'scope'              => $stockTypeLabel,
            'stock_type_label'   => $stockTypeLabel,
            'total_qty'          => $totalQty,
            'total_cost_value'   => $totalCostValue,
            'total_sell_value'   => $totalSellValue,
            'items_list'         => $displayItemsList,
            'has_more'           => $hasMore,
            'is_shop_view'       => $isShopView,
            'show_shop_column'   => $showShopColumn,
            'note'               => $request->input('note'),
            'sender_name'        => $user->name,
            'generated_at'       => now()->format('d M Y H:i:s'),
            'branding'           => $branding,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\FilteredStockReportMail($reportData));
            return response()->json([
                'success' => true,
                'message' => 'Stock report has been successfully sent to ' . implode(', ', $emailsArray) . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send stock report email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendExpensesEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'note'   => 'nullable|string|max:1000',
        ]);

        $emailsArray = array_map('trim', explode(',', $request->input('emails')));
        $emailsArray = array_filter($emailsArray, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emailsArray)) {
            return response()->json(['message' => 'Please provide at least one valid recipient email address.'], 422);
        }

        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
        $categoryId = $request->get('expense_category_id');

        $query = Expense::with(['category', 'recorder.shop'])
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

        $periodLabel = '';
        if ($period === 'daily') {
            $query->whereDate('activity_date', today());
            $periodLabel = 'Today (' . today()->format('d M Y') . ')';
        } elseif ($period === 'monthly') {
            $query->whereMonth('activity_date', now()->month)->whereYear('activity_date', now()->year);
            $periodLabel = now()->format('F Y');
        } elseif ($period === 'yearly') {
            $query->whereYear('activity_date', now()->year);
            $periodLabel = 'Year ' . now()->format('Y');
        } elseif ($period === 'custom') {
            if ($request->filled('date_from')) $query->whereDate('activity_date', '>=', $request->date_from);
            if ($request->filled('date_to'))   $query->whereDate('activity_date', '<=', $request->date_to);
            $periodLabel = ($request->date_from ?? 'Start') . ' to ' . ($request->date_to ?? 'End');
        } else {
            $periodLabel = ucfirst($period);
        }

        $expenses = $query->orderBy('activity_date', 'desc')->orderBy('id', 'desc')->get();
        $totalAmount = (float) $expenses->sum('amount');

        // By Category
        $byCategory = $expenses->groupBy('expense_category_id')->map(function ($group) {
            return [
                'name'  => $group->first()->category?->name ?? 'Uncategorized',
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ];
        })->values()->toArray();

        $expensesList = [];
        foreach ($expenses as $exp) {
            $expensesList[] = [
                'id'          => $exp->id,
                'date'        => $exp->activity_date ? $exp->activity_date->format('d M Y') : 'N/A',
                'category'    => $exp->category?->name ?? 'General',
                'shop'        => $exp->recorder?->shop?->shop_name ?? 'Main Store',
                'description' => $exp->description ?: '—',
                'recorded_by' => $exp->recorder?->name ?? 'User',
                'amount'      => (float) $exp->amount,
            ];
        }

        $hasMore = count($expensesList) > 100;
        $displayExpensesList = array_slice($expensesList, 0, 100);

        $shopLabel = 'All Shops';
        if ($shopId === 'owner') {
            $shopLabel = 'Main Store (Owner)';
        } elseif ($shopId) {
            $shopObj = Shop::find($shopId);
            $shopLabel = $shopObj ? $shopObj->shop_name : 'Selected Shop';
        }

        $categoryLabel = null;
        if ($categoryId) {
            $catObj = ExpenseCategory::find($categoryId);
            $categoryLabel = $catObj ? $catObj->name : null;
        }

        $branding = [
            'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan' => \App\Models\Setting::get('slogan', 'Technology Innovations'),
        ];
        if (!$user->isOwner() && $user->shop) {
            $branding['name'] = $user->shop->shop_name ?: $branding['name'];
            $branding['slogan'] = $user->shop->slogan ?: $branding['slogan'];
        }

        $reportData = [
            'scope'          => $shopLabel,
            'period_label'   => $periodLabel,
            'shop_label'     => $shopLabel,
            'category_label' => $categoryLabel,
            'total_amount'   => $totalAmount,
            'total_records'  => $expenses->count(),
            'by_category'    => $byCategory,
            'expenses_list'  => $displayExpensesList,
            'has_more'       => $hasMore,
            'note'           => $request->input('note'),
            'sender_name'    => $user->name,
            'generated_at'   => now()->format('d M Y H:i:s'),
            'branding'       => $branding,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\FilteredExpensesReportMail($reportData));
            return response()->json([
                'success' => true,
                'message' => 'Expenses report has been successfully sent to ' . implode(', ', $emailsArray) . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send expenses report email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendSalesVsExpensesEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'note'   => 'nullable|string|max:1000',
        ]);

        $emailsArray = array_map('trim', explode(',', $request->input('emails')));
        $emailsArray = array_filter($emailsArray, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emailsArray)) {
            return response()->json(['message' => 'Please provide at least one valid recipient email address.'], 422);
        }

        $period = $request->get('period', 'monthly');
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');

        $salesQuery = Sale::completed()->with('items');
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

        $periodLabel = '';
        if ($period === 'daily') {
            $salesQuery->whereDate('sale_date', today());
            $expensesQuery->whereDate('activity_date', today());
            $periodLabel = 'Today (' . today()->format('d M Y') . ')';
        } elseif ($period === 'monthly') {
            $salesQuery->whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year);
            $expensesQuery->whereMonth('activity_date', now()->month)->whereYear('activity_date', now()->year);
            $periodLabel = now()->format('F Y');
        } elseif ($period === 'yearly') {
            $salesQuery->whereYear('sale_date', now()->year);
            $expensesQuery->whereYear('activity_date', now()->year);
            $periodLabel = 'Year ' . now()->format('Y');
        } elseif ($period === 'custom') {
            if ($request->filled('date_from')) {
                $salesQuery->whereDate('sale_date', '>=', $request->date_from);
                $expensesQuery->whereDate('activity_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $salesQuery->whereDate('sale_date', '<=', $request->date_to);
                $expensesQuery->whereDate('activity_date', '<=', $request->date_to);
            }
            $periodLabel = ($request->date_from ?? 'Start') . ' to ' . ($request->date_to ?? 'End');
        } else {
            $periodLabel = ucfirst($period);
        }

        $isOwner = $user->isOwner();
        $sales = $salesQuery->get();
        $totalSales = (float) $sales->sum(fn($s) => $s->calculateRevenue($isOwner));
        $totalExpenses = (float) $expensesQuery->sum('amount');
        $netProfit = $totalSales - $totalExpenses;

        $byShop = [];
        if ($isOwner && empty($shopId)) {
            $shops = Shop::active()->get();
            foreach ($shops as $sh) {
                $sSales = $sales->where('shop_id', $sh->id)->sum(fn($s) => $s->calculateRevenue(true));
                $sExp = (float) Expense::whereIn('status', ['approved', 'review_requested', 'editable'])
                    ->whereHas('recorder', fn($q) => $q->where('shop_id', $sh->id))
                    ->when($period === 'daily', fn($q) => $q->whereDate('activity_date', today()))
                    ->when($period === 'monthly', fn($q) => $q->whereMonth('activity_date', now()->month)->whereYear('activity_date', now()->year))
                    ->when($period === 'yearly', fn($q) => $q->whereYear('activity_date', now()->year))
                    ->when($period === 'custom', function ($q) use ($request) {
                        if ($request->filled('date_from')) $q->whereDate('activity_date', '>=', $request->date_from);
                        if ($request->filled('date_to'))   $q->whereDate('activity_date', '<=', $request->date_to);
                    })
                    ->sum('amount');
                $byShop[] = [
                    'shop_name' => $sh->shop_name,
                    'sales'     => (float) $sSales,
                    'expenses'  => (float) $sExp,
                    'net'       => (float) ($sSales - $sExp),
                ];
            }
        }

        $shopLabel = 'All Shops';
        if ($shopId === 'owner') {
            $shopLabel = 'Main Store (Owner)';
        } elseif ($shopId) {
            $shopObj = Shop::find($shopId);
            $shopLabel = $shopObj ? $shopObj->shop_name : 'Selected Shop';
        }

        $branding = [
            'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan' => \App\Models\Setting::get('slogan', 'Technology Innovations'),
        ];
        if (!$user->isOwner() && $user->shop) {
            $branding['name'] = $user->shop->shop_name ?: $branding['name'];
            $branding['slogan'] = $user->shop->slogan ?: $branding['slogan'];
        }

        $reportData = [
            'scope'          => $shopLabel,
            'period_label'   => $periodLabel,
            'shop_label'     => $shopLabel,
            'total_sales'    => $totalSales,
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
            'by_shop'        => $byShop,
            'note'           => $request->input('note'),
            'sender_name'    => $user->name,
            'generated_at'   => now()->format('d M Y H:i:s'),
            'branding'       => $branding,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\FilteredSalesVsExpensesReportMail($reportData));
            return response()->json([
                'success' => true,
                'message' => 'Sales vs Expenses report has been successfully sent to ' . implode(', ', $emailsArray) . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send sales vs expenses report email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendDefectEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'note'   => 'nullable|string|max:1000',
        ]);

        $emailsArray = array_map('trim', explode(',', $request->input('emails')));
        $emailsArray = array_filter($emailsArray, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emailsArray)) {
            return response()->json(['message' => 'Please provide at least one valid recipient email address.'], 422);
        }

        $query = Defect::with(['shop', 'item.category', 'reporter']);
        $shopId = $user->isShopAdmin() ? $user->shop_id : $request->get('shop_id');
        $status = $request->get('status');

        if ($request->filled('status')) {
            $query->where('status', $status);
        }
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $defects = $query->orderBy('created_at', 'desc')->get();
        $totalDefective = (int) $defects->sum('quantity');

        $defectsList = [];
        foreach ($defects as $d) {
            $defectsList[] = [
                'id'       => $d->id,
                'date'     => $d->created_at ? $d->created_at->format('d M Y') : 'N/A',
                'item'     => $d->item?->item_name ?? 'Unknown Product',
                'category' => $d->item?->category?->category_name ?? '—',
                'shop'     => $d->shop?->shop_name ?? 'Main Warehouse',
                'quantity' => (int) $d->quantity,
                'reason'   => $d->reason ?: 'Defective / Damaged',
                'reporter' => $d->reporter?->name ?? 'Staff',
                'status'   => $d->status ?? 'pending',
            ];
        }

        $hasMore = count($defectsList) > 100;
        $displayDefectsList = array_slice($defectsList, 0, 100);

        $shopLabel = 'All Warehouses / Shops';
        if ($shopId) {
            $shopObj = Shop::find($shopId);
            $shopLabel = $shopObj ? $shopObj->shop_name : 'Selected Shop';
        }

        $branding = [
            'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan' => \App\Models\Setting::get('slogan', 'Technology Innovations'),
        ];
        if (!$user->isOwner() && $user->shop) {
            $branding['name'] = $user->shop->shop_name ?: $branding['name'];
            $branding['slogan'] = $user->shop->slogan ?: $branding['slogan'];
        }

        $reportData = [
            'scope'            => $shopLabel,
            'shop_label'       => $shopLabel,
            'status_label'     => $status ? ucfirst($status) : null,
            'total_defective'  => $totalDefective,
            'incidents_count'  => $defects->count(),
            'defects_list'     => $displayDefectsList,
            'has_more'         => $hasMore,
            'note'             => $request->input('note'),
            'sender_name'      => $user->name,
            'generated_at'     => now()->format('d M Y H:i:s'),
            'branding'         => $branding,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\FilteredDefectReportMail($reportData));
            return response()->json([
                'success' => true,
                'message' => 'Defect report has been successfully sent to ' . implode(', ', $emailsArray) . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send defect report email: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendTransferEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user->isOwner() && !$user->isShopAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'emails' => 'required|string',
            'note'   => 'nullable|string|max:1000',
        ]);

        $emailsArray = array_map('trim', explode(',', $request->input('emails')));
        $emailsArray = array_filter($emailsArray, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        if (empty($emailsArray)) {
            return response()->json(['message' => 'Please provide at least one valid recipient email address.'], 422);
        }

        $status = $request->get('status', 'all');
        $query = StockRequest::with(['shop', 'requester', 'transfer.item']);

        if ($user->isShopAdmin()) {
            $query->where('shop_id', $user->shop_id);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $stats = [
            'pending'  => StockRequest::when($user->isShopAdmin(), fn($q) => $q->where('shop_id', $user->shop_id))->where('status', 'pending')->count(),
            'approved' => StockRequest::when($user->isShopAdmin(), fn($q) => $q->where('shop_id', $user->shop_id))->where('status', 'approved')->count(),
            'rejected' => StockRequest::when($user->isShopAdmin(), fn($q) => $q->where('shop_id', $user->shop_id))->where('status', 'rejected')->count(),
        ];

        $requestsList = [];
        foreach ($requests as $req) {
            $itemsSummary = 'Stock Transfer';
            if ($req->transfer && $req->transfer->item) {
                $itemsSummary = "{$req->transfer->item->item_name} (x{$req->transfer->quantity})";
            }

            $requestsList[] = [
                'id'            => $req->id,
                'date'          => $req->created_at ? $req->created_at->format('d M Y') : 'N/A',
                'shop'          => $req->shop?->shop_name ?? 'Shop',
                'requester'     => $req->requester?->name ?? 'Staff',
                'items_summary' => $itemsSummary,
                'notes'         => $req->notes ?: '',
                'status'        => $req->status ?? 'pending',
            ];
        }

        $hasMore = count($requestsList) > 100;
        $displayRequestsList = array_slice($requestsList, 0, 100);

        $shopLabel = $user->isOwner() ? 'All Shops' : ($user->shop?->shop_name ?? 'My Shop');

        $branding = [
            'name'   => \App\Models\Setting::get('system_name', 'AMSTROOM'),
            'slogan' => \App\Models\Setting::get('slogan', 'Technology Innovations'),
        ];
        if (!$user->isOwner() && $user->shop) {
            $branding['name'] = $user->shop->shop_name ?: $branding['name'];
            $branding['slogan'] = $user->shop->slogan ?: $branding['slogan'];
        }

        $reportData = [
            'scope'          => $shopLabel,
            'shop_label'     => $shopLabel,
            'status_filter'  => $status,
            'stats'          => $stats,
            'requests_list'  => $displayRequestsList,
            'has_more'       => $hasMore,
            'note'           => $request->input('note'),
            'sender_name'    => $user->name,
            'generated_at'   => now()->format('d M Y H:i:s'),
            'branding'       => $branding,
        ];

        try {
            \Illuminate\Support\Facades\Mail::to($emailsArray)->send(new \App\Mail\FilteredTransferReportMail($reportData));
            return response()->json([
                'success' => true,
                'message' => 'Transfer report has been successfully sent to ' . implode(', ', $emailsArray) . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send transfer report email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
