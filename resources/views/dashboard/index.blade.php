@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    @php $user = auth()->user(); @endphp

    <style>
        .quick-access-card {
            transition: all 0.2s ease-in-out;
        }

        .quick-access-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: var(--accent) !important;
        }

        /* Analytics style KPI cards */
        .kpi-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            padding: 1.25rem 1.4rem;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
            transition: transform .25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,.08);
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--kpi-color, #0088cc);
            border-radius: 16px 16px 0 0;
        }
        .kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .kpi-label {
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
        .kpi-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-top: 0.15rem;
        }
        .kpi-sub {
            font-size: .72rem;
            color: var(--text-secondary);
            margin-top: .2rem;
        }
    </style>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-700 text-primary">
                <i class="bi bi-lightning-fill me-1"></i>Quick Access Actions
            </h6>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                @php
                    $isSeller = $user->isSeller();
                    $colClass = $isSeller ? 'col-12 col-md-4' : 'col-6 col-md-3';
                @endphp
                <div class="{{ $colClass }}">
                    <a href="{{ $user->isOwner() ? route('main-stock.index') : route('shop-stock.index') }}"
                        class="d-flex align-items-center p-3 rounded text-decoration-none transition-all quick-access-card bg-light border border-light-subtle">
                        <div class="rounded-circle p-2 bg-primary-subtle text-primary me-3 d-flex align-items-center justify-content-center"
                            style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-700 text-dark" style="font-size: .88rem;">Stock Inventory</div>
                            <small class="text-secondary" style="font-size: .72rem;">View & manage stock</small>
                        </div>
                    </a>
                </div>
                @if (!$isSeller)
                    <div class="{{ $colClass }}">
                        <a href="{{ route('stock-requests.index') }}"
                            class="d-flex align-items-center p-3 rounded text-decoration-none transition-all quick-access-card bg-light border border-light-subtle">
                            <div class="rounded-circle p-2 bg-warning-subtle text-warning me-3 d-flex align-items-center justify-content-center"
                                style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-clock-history fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-700 text-dark" style="font-size: .88rem;">Stock Requests</div>
                                <small class="text-secondary" style="font-size: .72rem;">Request replenishment</small>
                            </div>
                        </a>
                    </div>
                @endif
                <div class="{{ $colClass }}">
                    <a href="{{ route('sales.index') }}"
                        class="d-flex align-items-center p-3 rounded text-decoration-none transition-all quick-access-card bg-light border border-light-subtle">
                        <div class="rounded-circle p-2 bg-success-subtle text-success me-3 d-flex align-items-center justify-content-center"
                            style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-receipt fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-700 text-dark" style="font-size: .88rem;">Sales History</div>
                            <small class="text-secondary" style="font-size: .72rem;">View transactions</small>
                        </div>
                    </a>
                </div>
                <div class="{{ $colClass }}">
                    <a href="{{ route('sales.create') }}"
                        class="d-flex align-items-center p-3 rounded text-decoration-none transition-all quick-access-card bg-light border border-light-subtle">
                        <div class="rounded-circle p-2 bg-info-subtle text-info me-3 d-flex align-items-center justify-content-center"
                            style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-cart-plus fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-700 text-dark" style="font-size: .88rem;">New Sale (POS)</div>
                            <small class="text-secondary" style="font-size: .72rem;">Checkout customer</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── OWNER DASHBOARD ── --}}
    @if ($user->isOwner())

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color: #e94560;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(233,69,96,.1); color: #e94560;">
                            <i class="bi bi-shop"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Total Shops</div>
                    <div class="kpi-value">{{ $totalShops }}</div>
                    <div class="kpi-sub">Registered locations</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color: #58a6ff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(88,166,255,.1); color: #58a6ff;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Employees</div>
                    <div class="kpi-value">{{ $totalEmployees }}</div>
                    <div class="kpi-sub">Active team members</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color: #3fb950;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(63,185,80,.1); color: #3fb950;">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Products</div>
                    <div class="kpi-value">{{ $totalItems }}</div>
                    <div class="kpi-sub">Catalog inventory</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color: #bc8cff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(188,140,255,.1); color: #bc8cff;">
                            <i class="bi bi-tags-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Categories</div>
                    <div class="kpi-value">{{ $totalCategories }}</div>
                    <div class="kpi-sub">Item classifications</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="kpi-card" style="--kpi-color: #d29922;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(210,153,34,.1); color: #d29922;">
                            <i class="bi bi-building-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Main Store Stock Value (Cost)</div>
                    <div class="kpi-value" style="font-size:1.22rem;">TZS {{ number_format($mainStockValue, 0) }}</div>
                    <div class="kpi-sub">Warehouse valuation</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card" style="--kpi-color: #3fb950;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(63,185,80,.1); color: #3fb950;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Total Sales Revenue</div>
                    <div class="kpi-value" style="font-size:1.22rem;">TZS {{ number_format($totalSales, 0) }}</div>
                    <div class="kpi-sub">Lifetime turnover</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card" style="--kpi-color: #58a6ff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(88,166,255,.1); color: #58a6ff;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Estimated Profit</div>
                    <div class="kpi-value" style="font-size:1.22rem;">TZS {{ number_format(max(0, $profit), 0) }}</div>
                    <div class="kpi-sub">Margin after cost</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="kpi-card" style="--kpi-color: #d29922;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(210,153,34,.1); color: #d29922;">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Pending Stock Requests</div>
                    <div class="kpi-value">{{ $pendingRequests }}</div>
                    <div class="kpi-sub">Awaiting action</div>
                    @if ($pendingRequests > 0)
                        <a href="{{ route('stock-requests.index') }}" class="btn btn-sm btn-accent mt-2 py-1 px-3" style="font-size:0.75rem;">Review Now</a>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card" style="--kpi-color: #e94560;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(233,69,96,.1); color: #e94560;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Defective Items Reported</div>
                    <div class="kpi-value">{{ $totalDefects }}</div>
                    <div class="kpi-sub">Flagged inventory</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card" style="--kpi-color: #bc8cff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(188,140,255,.1); color: #bc8cff;">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Total Transactions</div>
                    <div class="kpi-value">{{ number_format($totalSalesCount) }}</div>
                    <div class="kpi-sub">Completed checkout slips</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-graph-up-arrow me-2" style="color:#3fb950;"></i>Sales — Last 6 Months</span>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header"><i class="bi bi-shop me-2" style="color:#58a6ff;"></i>Shops Performance
                    </div>
                    <div class="card-body p-0">
                        @foreach ($shopsSummary as $shop)
                            <div style="padding:.75rem 1rem; border-bottom:1px solid var(--card-border);">
                                <div style="font-size:.82rem;font-weight:600;">{{ $shop->shop_name }}</div>
                                <div style="font-size:.75rem;color:var(--text-secondary);">
                                    {{ $shop->sales_count }} sales &bull;
                                    TZS {{ number_format($shop->sales_sum_total_amount ?? 0, 0) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-receipt me-2" style="color:#3fb950;"></i>Recent Sales</span>
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-custom">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Shop</th>
                                    <th>Seller</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td><span
                                                style="font-size:.78rem;">{{ $sale->shop->shop_name ?? 'Main Store (Owner)' }}</span>
                                        </td>
                                        <td><span
                                                style="font-size:.78rem;">{{ $sale->seller->name ?? 'System Owner' }}</span>
                                        </td>
                                        <td><strong style="color:#3fb950;font-size:.82rem;">TZS
                                                {{ number_format($sale->report_revenue, 0) }}</strong></td>
                                        <td><span
                                                style="font-size:.75rem;color:var(--text-secondary);">{{ $sale->sale_date->format('M d') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3"
                                            style="color:var(--text-secondary);font-size:.8rem;">No sales yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-fill me-2" style="color:#d29922;"></i>Pending Requests</span>
                        <a href="{{ route('stock-requests.index') }}" class="btn btn-sm btn-outline-custom">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Shop</th>
                                    <th>Requester</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRequests as $req)
                                    <tr>
                                        <td><span style="font-size:.78rem;">{{ $req->shop->shop_name }}</span></td>
                                        <td><span style="font-size:.78rem;">{{ $req->requester->name }}</span></td>
                                        <td><span
                                                style="font-size:.75rem;color:var(--text-secondary);">{{ $req->request_date->format('M d') }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('stock-requests.show', $req) }}"
                                                class="btn btn-xs btn-accent"
                                                style="font-size:.7rem;padding:.2rem .5rem;">Review</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3"
                                            style="color:var(--text-secondary);font-size:.8rem;">No pending requests</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SHOP ADMIN DASHBOARD ── --}}
    @elseif($user->isShopAdmin())
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color: #3fb950;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(63,185,80,.1); color: #3fb950;">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Today's Sales</div>
                    <div class="kpi-value" style="font-size:1.15rem;">TZS {{ number_format($todaySales, 0) }}</div>
                    <div class="kpi-sub">Sales logged today</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color: #58a6ff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(88,166,255,.1); color: #58a6ff;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <div class="kpi-label">This Month's Sales</div>
                    <div class="kpi-value" style="font-size:1.1rem;">TZS {{ number_format($shopSales, 0) }}</div>
                    <div class="kpi-sub">Monthly cumulative sales</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color: #bc8cff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(188,140,255,.1); color: #bc8cff;">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Today's Admin Sales</div>
                    <div class="kpi-value" style="font-size:1.1rem;">TZS {{ number_format($adminStockSales, 0) }}</div>
                    <div class="kpi-sub">Admin-owned stock</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color: #d29922;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(210,153,34,.1); color: #d29922;">
                            <i class="bi bi-shop-window"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Today's Normal Sales</div>
                    <div class="kpi-value" style="font-size:1.1rem;">TZS {{ number_format($normalStockSales, 0) }}</div>
                    <div class="kpi-sub">Owner-owned stock</div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color: #d29922;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(210,153,34,.1); color: #d29922;">
                            <i class="bi bi-layers-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Units in Stock</div>
                    <div class="kpi-value">{{ number_format($shopStock) }}</div>
                    <div class="kpi-sub">Current physical stock</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color: #bc8cff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(188,140,255,.1); color: #bc8cff;">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Pending Requests</div>
                    <div class="kpi-value">{{ $pendingRequests }}</div>
                    <div class="kpi-sub">Awaiting store response</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color: #e94560;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(233,69,96,.1); color: #e94560;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Low Stock Items</div>
                    <div class="kpi-value">{{ $lowStockCount }}</div>
                    <div class="kpi-sub">Below threshold level</div>
                </div>
            </div>
        </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-graph-up-arrow me-2" style="color:#3fb950;"></i>Monthly
                        Sales — {{ $shop->shop_name }}</div>
                    <div class="card-body"><canvas id="salesChart" height="100"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-receipt me-2" style="color:#58a6ff;"></i>Recent Sales</div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Seller</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td style="font-size:.78rem;">{{ $sale->seller->name ?? '-' }}</td>
                                        <td style="font-size:.78rem;color:#3fb950;font-weight:600;">TZS
                                            {{ number_format($sale->report_revenue, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-3"
                                            style="color:var(--text-secondary);font-size:.8rem;">No sales yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SELLER DASHBOARD ── --}}
    @else
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color: #3fb950;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(63,185,80,.1); color: #3fb950;">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Today's Sales</div>
                    <div class="kpi-value" style="font-size:1.15rem;">TZS {{ number_format($todaySales, 0) }}</div>
                    <div class="kpi-sub">Shop total today</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color: #58a6ff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(88,166,255,.1); color: #58a6ff;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <div class="kpi-label">My Total Sales</div>
                    <div class="kpi-value" style="font-size:1.1rem;">TZS {{ number_format($mySales, 0) }}</div>
                    <div class="kpi-sub">My total revenue contribution</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color: #bc8cff;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(188,140,255,.1); color: #bc8cff;">
                            <i class="bi bi-cart-check-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">My Transactions</div>
                    <div class="kpi-value">{{ number_format($mySalesCount) }}</div>
                    <div class="kpi-sub">Receipts issued by me</div>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-6">
                <div class="kpi-card" style="--kpi-color: #3fb950;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(63,185,80,.1); color: #3fb950;">
                            <i class="bi bi-layers-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Products Available</div>
                    <div class="kpi-value">{{ $availableStock }}</div>
                    <div class="kpi-sub">Active catalog items</div>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="kpi-card" style="--kpi-color: #e94560;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="kpi-icon" style="background: rgba(233,69,96,.1); color: #e94560;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Low Stock Alerts</div>
                    <div class="kpi-value">{{ $lowStockCount }}</div>
                    <div class="kpi-sub">Replenishment required</div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-receipt me-2" style="color:#3fb950;"></i>My Recent Sales</span>
                        <a href="{{ route('sales.create') }}" class="btn btn-sm btn-accent"><i
                                class="bi bi-plus-circle me-1"></i>New Sale</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td style="font-size:.82rem;">{{ $sale->customer_name ?: 'Walk-in' }}</td>
                                        <td style="font-size:.78rem;color:var(--text-secondary);">
                                            {{ $sale->items->count() }} item(s)</td>
                                        <td><strong style="color:#3fb950;">TZS
                                                {{ number_format($sale->report_revenue, 0) }}</strong></td>
                                        <td style="font-size:.75rem;">
                                            {{ str_replace('_', ' ', ucfirst($sale->payment_method)) }}</td>
                                        <td style="font-size:.75rem;color:var(--text-secondary);">
                                            {{ $sale->sale_date->format('M d, Y') }}</td>
                                        <td><a href="{{ route('sales.receipt', $sale) }}"
                                                class="btn btn-xs btn-outline-custom" style="font-size:.7rem;">Receipt</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4" style="color:var(--text-secondary);">
                                            No sales recorded yet. <a href="{{ route('sales.create') }}"
                                                class="text-decoration-none" style="color:#e94560;">Make your first
                                                sale!</a></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Floating Live Chat Quick Access Icon -->
    <a href="{{ route('chats.index') }}"
        class="btn rounded-circle d-flex align-items-center justify-content-center shadow-lg position-fixed"
        style="bottom: 30px; right: 30px; width: 60px; height: 60px; z-index: 1050; background: linear-gradient(135deg, #0088cc, #005f9e); color: #ffffff; border: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 25px rgba(0, 136, 204, 0.4) !important;"
        onmouseover="this.style.transform='scale(1.15) translateY(-3px)'; this.style.boxShadow='0 15px 30px rgba(0, 136, 204, 0.5) !important';"
        onmouseout="this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='0 10px 25px rgba(0, 136, 204, 0.4) !important';"
        title="Open Live Chat">
        <i class="bi bi-chat-dots-fill fs-3"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
            id="dashboardChatBadge" style="font-size: 0.65rem; padding: 0.35em 0.55em; border: 2px solid #ffffff;">
            0
        </span>
    </a>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        @if (isset($monthlySales) && count($monthlySales) > 0)
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                new Chart(salesCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($monthlySales->pluck('month')) !!},
                        datasets: [{
                            label: 'Revenue (TZS)',
                            data: {!! json_encode($monthlySales->pluck('total')) !!},
                            backgroundColor: 'rgba(233,69,96,.7)',
                            borderColor: '#e94560',
                            borderWidth: 2,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#8b949e',
                                    font: {
                                        family: 'Inter'
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#8b949e'
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.04)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#8b949e',
                                    callback: v => 'TZS ' + Number(v).toLocaleString()
                                },
                                grid: {
                                    color: 'rgba(255,255,255,.04)'
                                }
                            }
                        }
                    }
                });
            }
        @endif
    </script>
@endpush
