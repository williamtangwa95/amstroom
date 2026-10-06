@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       DASHBOARD — Premium Redesign
       ══════════════════════════════════════════════ */

    /* ── Hero Welcome Banner ── */
    .dash-hero {
        background: linear-gradient(135deg, #0088cc 0%, #005f9e 55%, #003d7a 100%);
        border-radius: 18px;
        padding: 1.6rem 1.8rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        color: #fff;
        box-shadow: 0 8px 30px rgba(0,136,204,.35);
    }
    .dash-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.07);
        border-radius: 50%;
        pointer-events: none;
    }
    .dash-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 80px;
        width: 250px; height: 250px;
        background: rgba(255,255,255,.04);
        border-radius: 50%;
        pointer-events: none;
    }
    .dash-hero-title {
        font-size: 1.35rem;
        font-weight: 800;
        margin: 0 0 .2rem;
        letter-spacing: -.01em;
    }
    .dash-hero-sub {
        font-size: .82rem;
        color: rgba(255,255,255,.75);
        margin: 0 0 .5rem;
    }
    .dash-hero-badge {
        background: rgba(255,255,255,.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 50px;
        padding: .28rem .82rem;
        font-size: .7rem;
        font-weight: 700;
        color: #fff;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .dash-hero-date {
        flex-shrink: 0;
        text-align: right;
    }
    .dash-hero-day {
        font-size: 2rem;
        font-weight: 900;
        line-height: 1;
        color: #fff;
    }
    .dash-hero-meta {
        font-size: .7rem;
        color: rgba(255,255,255,.65);
        line-height: 1.4;
    }

    /* ── Quick Access Section ── */
    .qa-section {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 18px;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .qa-header {
        padding: .9rem 1.35rem .75rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        gap: .55rem;
    }
    .qa-header-icon {
        width: 28px; height: 28px;
        background: linear-gradient(135deg, #0088cc, #005f9e);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: .78rem;
        flex-shrink: 0;
    }
    .qa-header-title {
        font-size: .88rem;
        font-weight: 800;
        color: var(--text-primary);
        margin: 0;
    }
    .qa-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
    }
    .qa-grid-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    .qa-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 1.2rem .85rem;
        text-decoration: none;
        color: var(--text-primary);
        border-right: 1px solid var(--card-border);
        transition: all .22s cubic-bezier(0.4,0,0.2,1);
        position: relative;
        overflow: hidden;
    }
    .qa-tile:last-child { border-right: none; }
    .qa-tile:hover {
        background: #f0f7ff;
        text-decoration: none;
        color: var(--text-primary);
    }
    .qa-tile:hover .qa-tile-icon {
        transform: scale(1.1) rotate(-4deg);
    }
    .qa-tile-icon {
        width: 48px; height: 48px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        margin-bottom: .6rem;
        transition: transform .22s cubic-bezier(0.4,0,0.2,1);
        flex-shrink: 0;
    }
    .qa-tile-label {
        font-size: .78rem;
        font-weight: 700;
        line-height: 1.25;
        margin-bottom: .12rem;
    }
    .qa-tile-sub {
        font-size: .67rem;
        color: var(--text-secondary);
    }

    /* ── KPI Cards ── */
    .kpi-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--card-border);
        padding: 1.15rem 1.25rem;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        transition: transform .25s cubic-bezier(0.4,0,0.2,1), box-shadow .25s;
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(0,0,0,.1);
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3.5px;
        background: var(--kpi-color, #0088cc);
        border-radius: 16px 16px 0 0;
    }
    .kpi-glow {
        position: absolute;
        bottom: -20px; right: -20px;
        width: 80px; height: 80px;
        border-radius: 50%;
        background: var(--kpi-color, #0088cc);
        opacity: .06;
        pointer-events: none;
    }
    .kpi-inner {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
    }
    .kpi-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        margin-top: .2rem;
    }
    .kpi-body { flex: 1; min-width: 0; }
    .kpi-label {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--text-secondary);
        margin: 0 0 .18rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .kpi-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.15;
        margin: 0 0 .15rem;
        word-break: break-all;
    }
    .kpi-value-sm { font-size: 1.08rem; }
    .kpi-sub {
        font-size: .69rem;
        color: var(--text-secondary);
        margin: 0;
    }

    /* ── Chart Card ── */
    .chart-card {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        overflow: hidden;
        height: 100%;
    }
    .chart-card-header {
        padding: .9rem 1.25rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .85rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .chart-card-body { padding: 1rem 1.25rem; }

    /* ── List Card ── */
    .list-card {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .list-card-header {
        padding: .9rem 1.25rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .85rem;
        font-weight: 700;
        color: var(--text-primary);
        flex-shrink: 0;
    }
    .list-card-body {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        flex: 1;
    }
    .list-card-body table {
        width: 100%;
        min-width: 300px;
        border-collapse: collapse;
    }
    .list-card-body table thead th {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        font-weight: 700;
        padding: .6rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--card-border);
        white-space: nowrap;
    }
    .list-card-body table tbody td {
        padding: .65rem 1rem;
        font-size: .8rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: var(--text-primary);
    }
    .list-card-body table tbody tr:last-child td { border-bottom: none; }
    .list-card-body table tbody tr:hover td { background: rgba(0,136,204,.025); }

    /* ── Shop Performance ── */
    .shop-item {
        padding: .8rem 1.2rem;
        border-bottom: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        gap: .85rem;
        transition: background .15s;
    }
    .shop-item:last-child { border-bottom: none; }
    .shop-item:hover { background: #f8fafc; }
    .shop-avatar {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0088cc, #005f9e);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: .78rem;
        font-weight: 800;
        flex-shrink: 0;
    }
    .shop-name { font-size: .82rem; font-weight: 700; margin: 0; }
    .shop-meta { font-size: .71rem; color: var(--text-secondary); margin: .1rem 0 0; }
    .shop-amt { margin-left: auto; font-size: .8rem; font-weight: 700; color: #059669; white-space: nowrap; }

    /* ── Helpers ── */
    .amt-green { color: #059669 !important; font-weight: 700; }
    .amt-blue  { color: #0284c7 !important; font-weight: 700; }
    .empty-state-row { padding: 2rem 1rem; text-align: center; color: var(--text-secondary); font-size: .82rem; }
    .empty-state-row i { font-size: 1.8rem; display: block; margin-bottom: .4rem; opacity: .3; }

    /* ── Floating Chat FAB ── */
    .dash-fab {
        position: fixed;
        bottom: 24px; right: 24px;
        width: 56px; height: 56px;
        background: linear-gradient(135deg, #0088cc, #005f9e);
        color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        z-index: 1050;
        box-shadow: 0 8px 24px rgba(0,136,204,.45);
        transition: all .3s cubic-bezier(0.175,0.885,0.32,1.275);
        text-decoration: none;
    }
    .dash-fab:hover {
        transform: scale(1.12) translateY(-3px);
        box-shadow: 0 14px 32px rgba(0,136,204,.55);
        color: #fff;
    }
    .dash-fab .fab-badge {
        position: absolute;
        top: -2px; right: -2px;
        background: #ef4444;
        color: #fff;
        font-size: .58rem;
        font-weight: 800;
        border-radius: 50px;
        padding: .18em .48em;
        border: 2px solid #fff;
        line-height: 1;
        display: none;
    }

    /* ── Entrance Animation ── */
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .dash-hero   { animation: slideUp .3s ease both; }
    .qa-section  { animation: slideUp .35s .05s ease both; }
    .kpi-card    { animation: slideUp .35s ease both; }
    .chart-card,
    .list-card   { animation: slideUp .38s .1s ease both; }

    /* ═══════════════════════════════
       RESPONSIVE — MOBILE FIRST
       ═══════════════════════════════ */
    @media (max-width: 575.98px) {
        .dash-hero { padding: 1rem; border-radius: 14px; }
        .dash-hero-title { font-size: 1.05rem; }
        .dash-hero-sub { font-size: .74rem; }
        .dash-hero-day { font-size: 1.5rem; }

        /* Quick access: 2-col on xs */
        .qa-grid   { grid-template-columns: repeat(2, 1fr) !important; }
        .qa-tile   { border-right: none !important; border-bottom: 1px solid var(--card-border); }
        .qa-tile:nth-child(odd)  { border-right: 1px solid var(--card-border) !important; }
        .qa-tile:nth-last-child(-n+2):not(.qa-tile:nth-child(odd)) { border-bottom: none; }
        .qa-tile:last-child { border-bottom: none !important; }

        /* KPI on xs */
        .kpi-value { font-size: 1.1rem; }
        .kpi-value-sm { font-size: .9rem; }
        .kpi-icon { width: 38px; height: 38px; font-size: 1rem; }
        .kpi-card { padding: .85rem .9rem; }

        .dash-fab { width: 48px; height: 48px; font-size: 1.2rem; bottom: 18px; right: 14px; }
    }
</style>
@endpush

@section('content')
    @php $user = auth()->user(); @endphp

    {{-- ── Hero Welcome Banner ── --}}
    <div class="dash-hero mb-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div style="flex:1; min-width:0;">
                <h1 class="dash-hero-title">
                    Good {{ date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') }},
                    {{ explode(' ', $user->name)[0] }}! 👋
                </h1>
                <p class="dash-hero-sub">
                    @if($user->isOwner())
                        Overview of your entire business — real-time insights at a glance.
                    @elseif($user->isShopAdmin())
                        Managing <strong>{{ $shop->shop_name ?? 'your shop' }}</strong> — track sales &amp; stock.
                    @else
                        Your personal dashboard — track your sales performance.
                    @endif
                </p>
                <span class="dash-hero-badge">
                    <i class="bi bi-circle-fill" style="font-size:.45rem; color:#6ee7b7;"></i>
                    Live Dashboard
                </span>
            </div>
            <div class="dash-hero-date d-none d-sm-flex flex-column align-items-end justify-content-center">
                <div class="dash-hero-day">{{ now()->format('d') }}</div>
                <div class="dash-hero-meta">{{ now()->format('M Y') }}<br>{{ now()->format('l') }}</div>
            </div>
        </div>
    </div>

    {{-- ── Quick Access ── --}}
    @php
        $isSeller  = $user->isSeller();
        $qaGrid    = $isSeller ? 'qa-grid qa-grid-3' : 'qa-grid';
    @endphp
    <div class="qa-section mb-4">
        <div class="qa-header">
            <div class="qa-header-icon"><i class="bi bi-lightning-fill"></i></div>
            <h2 class="qa-header-title">Quick Access</h2>
        </div>
        <div class="{{ $qaGrid }}">
            <a href="{{ $user->isOwner() ? route('main-stock.index') : route('shop-stock.index') }}" class="qa-tile">
                <div class="qa-tile-icon" style="background:rgba(0,136,204,.12);color:#0088cc;"><i class="bi bi-box-seam-fill"></i></div>
                <div class="qa-tile-label">Stock Inventory</div>
                <div class="qa-tile-sub">View &amp; manage stock</div>
            </a>
            @if (!$isSeller)
            <a href="{{ route('stock-requests.index') }}" class="qa-tile">
                <div class="qa-tile-icon" style="background:rgba(245,158,11,.12);color:#d97706;"><i class="bi bi-clock-history"></i></div>
                <div class="qa-tile-label">Stock Requests</div>
                <div class="qa-tile-sub">Request replenishment</div>
            </a>
            @endif
            <a href="{{ route('sales.index') }}" class="qa-tile">
                <div class="qa-tile-icon" style="background:rgba(5,150,105,.12);color:#059669;"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="qa-tile-label">Sales History</div>
                <div class="qa-tile-sub">View transactions</div>
            </a>
            <a href="{{ route('sales.create') }}" class="qa-tile">
                <div class="qa-tile-icon" style="background:rgba(139,92,246,.12);color:#7c3aed;"><i class="bi bi-cart-plus-fill"></i></div>
                <div class="qa-tile-label">New Sale (POS)</div>
                <div class="qa-tile-sub">Checkout customer</div>
            </a>
        </div>
    </div>


    {{-- ══════════════════════════════════
         OWNER DASHBOARD
         ══════════════════════════════════ --}}
    @if ($user->isOwner())

        {{-- Row 1: 4 Overview KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color:#0088cc;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(0,136,204,.12);color:#0088cc;"><i class="bi bi-shop-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Total Shops</p>
                            <div class="kpi-value">{{ $totalShops }}</div>
                            <p class="kpi-sub">Registered locations</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color:#8b5cf6;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><i class="bi bi-people-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Employees</p>
                            <div class="kpi-value">{{ $totalEmployees }}</div>
                            <p class="kpi-sub">Active team members</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color:#059669;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(5,150,105,.12);color:#059669;"><i class="bi bi-box-seam-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Products</p>
                            <div class="kpi-value">{{ $totalItems }}</div>
                            <p class="kpi-sub">Catalog inventory</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card" style="--kpi-color:#d97706;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;"><i class="bi bi-tags-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Categories</p>
                            <div class="kpi-value">{{ $totalCategories }}</div>
                            <p class="kpi-sub">Item classifications</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Financial KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#d97706;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;"><i class="bi bi-building-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Main Store Stock Value</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($mainStockValue, 0) }}</div>
                            <p class="kpi-sub">Warehouse valuation (cost)</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#059669;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(5,150,105,.12);color:#059669;"><i class="bi bi-currency-dollar"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Total Sales Revenue</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($totalSales, 0) }}</div>
                            <p class="kpi-sub">Lifetime turnover</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#0088cc;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(0,136,204,.12);color:#0088cc;"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Estimated Profit</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format(max(0,$profit), 0) }}</div>
                            <p class="kpi-sub">Margin after cost</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 3: Operations KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#d97706;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;"><i class="bi bi-clock-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Pending Stock Requests</p>
                            <div class="kpi-value">{{ $pendingRequests }}</div>
                            <p class="kpi-sub">Awaiting action</p>
                            @if ($pendingRequests > 0)
                                <a href="{{ route('stock-requests.index') }}" class="btn btn-accent mt-2 py-1 px-3" style="font-size:.7rem;">
                                    <i class="bi bi-arrow-right me-1"></i>Review Now
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#ef4444;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Defective Items</p>
                            <div class="kpi-value">{{ $totalDefects }}</div>
                            <p class="kpi-sub">Flagged inventory</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#8b5cf6;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><i class="bi bi-receipt"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Total Transactions</p>
                            <div class="kpi-value">{{ number_format($totalSalesCount) }}</div>
                            <p class="kpi-sub">Completed checkouts</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 4: Chart + Shops Performance --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-8">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <span><i class="bi bi-bar-chart-fill me-2" style="color:#059669;"></i>Sales — Last 6 Months</span>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="salesChart" height="110"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="list-card">
                    <div class="list-card-header">
                        <span><i class="bi bi-shop me-2" style="color:#0088cc;"></i>Shops Performance</span>
                    </div>
                    @forelse($shopsSummary as $shop)
                    <div class="shop-item">
                        <div class="shop-avatar">{{ strtoupper(substr($shop->shop_name, 0, 1)) }}</div>
                        <div style="min-width:0;">
                            <p class="shop-name">{{ $shop->shop_name }}</p>
                            <p class="shop-meta">{{ $shop->sales_count }} sales</p>
                        </div>
                        <div class="shop-amt">TZS {{ number_format($shop->sales_sum_total_amount ?? 0, 0) }}</div>
                    </div>
                    @empty
                    <div class="empty-state-row"><i class="bi bi-shop"></i>No shop data yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Row 5: Recent Sales + Pending Requests --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="list-card">
                    <div class="list-card-header">
                        <span><i class="bi bi-receipt me-2" style="color:#059669;"></i>Recent Sales</span>
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-custom">View All</a>
                    </div>
                    <div class="list-card-body">
                        <table>
                            <thead><tr><th>Shop</th><th>Seller</th><th>Amount</th><th>Date</th></tr></thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ $sale->shop->shop_name ?? 'Main Store' }}</td>
                                    <td>{{ $sale->seller->name ?? 'Owner' }}</td>
                                    <td class="amt-green">TZS {{ number_format($sale->report_revenue, 0) }}</td>
                                    <td style="color:var(--text-secondary);">{{ $sale->sale_date->format('M d') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4"><div class="empty-state-row"><i class="bi bi-receipt"></i>No sales yet</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="list-card">
                    <div class="list-card-header">
                        <span><i class="bi bi-clock-fill me-2" style="color:#d97706;"></i>Pending Requests</span>
                        <a href="{{ route('stock-requests.index') }}" class="btn btn-sm btn-outline-custom">View All</a>
                    </div>
                    <div class="list-card-body">
                        <table>
                            <thead><tr><th>Shop</th><th>Requester</th><th>Date</th><th>Action</th></tr></thead>
                            <tbody>
                                @forelse($recentRequests as $req)
                                <tr>
                                    <td>{{ $req->shop->shop_name }}</td>
                                    <td>{{ $req->requester->name }}</td>
                                    <td style="color:var(--text-secondary);">{{ $req->request_date->format('M d') }}</td>
                                    <td>
                                        <a href="{{ route('stock-requests.show', $req) }}" class="btn btn-accent" style="font-size:.68rem;padding:.22rem .55rem;border-radius:6px;">Review</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4"><div class="empty-state-row"><i class="bi bi-inbox"></i>No pending requests</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    {{-- ══════════════════════════════════
         SHOP ADMIN DASHBOARD
         ══════════════════════════════════ --}}
    @elseif($user->isShopAdmin())

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color:#059669;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(5,150,105,.12);color:#059669;"><i class="bi bi-calendar-check-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Today's Sales</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($todaySales, 0) }}</div>
                            <p class="kpi-sub">Sales logged today</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color:#0088cc;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(0,136,204,.12);color:#0088cc;"><i class="bi bi-currency-dollar"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">This Month's Sales</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($shopSales, 0) }}</div>
                            <p class="kpi-sub">Monthly cumulative</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color:#8b5cf6;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><i class="bi bi-person-badge-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Admin Stock Sales</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($adminStockSales, 0) }}</div>
                            <p class="kpi-sub">Admin-owned stock</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="--kpi-color:#d97706;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;"><i class="bi bi-shop-window"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Normal Stock Sales</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($normalStockSales, 0) }}</div>
                            <p class="kpi-sub">Owner-owned stock</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color:#d97706;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;"><i class="bi bi-layers-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Units in Stock</p>
                            <div class="kpi-value">{{ number_format($shopStock) }}</div>
                            <p class="kpi-sub">Current physical stock</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color:#8b5cf6;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><i class="bi bi-clock-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Pending Requests</p>
                            <div class="kpi-value">{{ $pendingRequests }}</div>
                            <p class="kpi-sub">Awaiting store response</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#ef4444;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Low Stock Items</p>
                            <div class="kpi-value">{{ $lowStockCount }}</div>
                            <p class="kpi-sub">Below threshold level</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-8">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <span><i class="bi bi-bar-chart-fill me-2" style="color:#059669;"></i>Monthly Sales — {{ $shop->shop_name }}</span>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="salesChart" height="110"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="list-card">
                    <div class="list-card-header">
                        <span><i class="bi bi-receipt me-2" style="color:#0088cc;"></i>Recent Sales</span>
                    </div>
                    <div class="list-card-body">
                        <table>
                            <thead><tr><th>Seller</th><th>Amount</th></tr></thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ $sale->seller->name ?? '—' }}</td>
                                    <td class="amt-green">TZS {{ number_format($sale->report_revenue, 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2"><div class="empty-state-row"><i class="bi bi-receipt"></i>No sales yet</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    {{-- ══════════════════════════════════
         SELLER DASHBOARD
         ══════════════════════════════════ --}}
    @else

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="kpi-card" style="--kpi-color:#059669;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(5,150,105,.12);color:#059669;"><i class="bi bi-calendar-check-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Today's Sales</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($todaySales, 0) }}</div>
                            <p class="kpi-sub">Shop total today</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color:#0088cc;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(0,136,204,.12);color:#0088cc;"><i class="bi bi-currency-dollar"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">My Total Sales</p>
                            <div class="kpi-value kpi-value-sm">TZS {{ number_format($mySales, 0) }}</div>
                            <p class="kpi-sub">My total revenue</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card" style="--kpi-color:#8b5cf6;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6;"><i class="bi bi-cart-check-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">My Transactions</p>
                            <div class="kpi-value">{{ number_format($mySalesCount) }}</div>
                            <p class="kpi-sub">Receipts issued by me</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="kpi-card" style="--kpi-color:#059669;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(5,150,105,.12);color:#059669;"><i class="bi bi-layers-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Products Available</p>
                            <div class="kpi-value">{{ $availableStock }}</div>
                            <p class="kpi-sub">Active catalog items</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="kpi-card" style="--kpi-color:#ef4444;">
                    <div class="kpi-glow"></div>
                    <div class="kpi-inner">
                        <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:#ef4444;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div class="kpi-body">
                            <p class="kpi-label">Low Stock Alerts</p>
                            <div class="kpi-value">{{ $lowStockCount }}</div>
                            <p class="kpi-sub">Replenishment needed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="list-card">
                    <div class="list-card-header">
                        <span><i class="bi bi-receipt me-2" style="color:#059669;"></i>My Recent Sales</span>
                        <a href="{{ route('sales.create') }}" class="btn btn-sm btn-accent"><i class="bi bi-plus-circle me-1"></i>New Sale</a>
                    </div>
                    <div class="list-card-body">
                        <table style="min-width:460px;">
                            <thead>
                                <tr><th>Customer</th><th>Items</th><th>Amount</th><th>Payment</th><th>Date</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ $sale->customer_name ?: 'Walk-in' }}</td>
                                    <td style="color:var(--text-secondary);">{{ $sale->items->count() }} item(s)</td>
                                    <td class="amt-green">TZS {{ number_format($sale->report_revenue, 0) }}</td>
                                    <td>{{ str_replace('_',' ', ucfirst($sale->payment_method)) }}</td>
                                    <td style="color:var(--text-secondary);">{{ $sale->sale_date->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-outline-custom btn-sm" style="font-size:.68rem;padding:.22rem .55rem;">Receipt</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state-row">
                                            <i class="bi bi-receipt-cutoff"></i>
                                            No sales recorded yet.
                                            <a href="{{ route('sales.create') }}" style="color:#0088cc;font-weight:600;">Make your first sale!</a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @endif

    {{-- ── Floating Chat FAB ── --}}
    <a href="{{ route('chats.index') }}" class="dash-fab" title="Open Live Chat">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="fab-badge" id="dashboardChatBadge">0</span>
    </a>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    @if(isset($monthlySales) && count($monthlySales) > 0)
    (function() {
        const canvas = document.getElementById('salesChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const grad = ctx.createLinearGradient(0, 0, 0, 320);
        grad.addColorStop(0,   'rgba(0,136,204,.6)');
        grad.addColorStop(1,   'rgba(0,136,204,.04)');

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: {!! json_encode($monthlySales->pluck('month')) !!},
                datasets: [{
                    label: 'Revenue (TZS)',
                    data: {!! json_encode($monthlySales->pluck('total')) !!},
                    backgroundColor: grad,
                    borderColor: '#0088cc',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { labels: { color: '#64748b', font: { family: 'Inter', size: 11 } } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        cornerRadius: 10,
                        padding: 12,
                        callbacks: { label: c => 'TZS ' + Number(c.parsed.y).toLocaleString() }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } },
                        grid: { display: false }
                    },
                    y: {
                        ticks: {
                            color: '#94a3b8',
                            font: { family: 'Inter', size: 11 },
                            callback: v => 'TZS ' + Number(v).toLocaleString()
                        },
                        grid: { color: 'rgba(0,0,0,.04)', drawBorder: false }
                    }
                }
            }
        });
    })();
    @endif
</script>
@endpush
