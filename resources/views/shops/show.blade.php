@extends('layouts.app')
@section('title', $shop->shop_name)
@section('page-title', 'Shop Profile')
@section('breadcrumb')
@if(auth()->user()->isOwner())
<li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Shops</a></li>
@endif
<li class="breadcrumb-item active">{{ $shop->shop_name }}</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       SHOP PROFILE — EXECUTIVE LAYOUT (MATCHES /profile)
       ══════════════════════════════════════════════ */
    .shop-card {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }

    /* ── Left Identity Card ── */
    .shop-cover {
        height: 76px;
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        position: relative;
    }
    .shop-avatar-wrap {
        margin-top: -44px;
        position: relative;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .shop-avatar-img,
    .shop-avatar-fallback {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .12);
    }
    .shop-avatar-img {
        object-fit: contain;
        background: #fff;
        padding: 6px;
    }
    .shop-avatar-fallback {
        background: linear-gradient(135deg, #0077b6, #004e89);
        color: #fff;
        font-size: 1.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .shop-name-title {
        font-size: 1.06rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: .65rem 0 .2rem;
        letter-spacing: -.01em;
    }
    .shop-slogan-text {
        font-size: .78rem;
        color: var(--text-secondary);
        margin-bottom: .7rem;
        line-height: 1.4;
    }

    .shop-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        padding: .26rem .72rem;
        border-radius: 50px;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }
    .shop-status-pill.is-active {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
    }
    .shop-status-pill.is-inactive {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
    }
    .shop-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .shop-meta-list {
        border-top: 1px solid #f1f5f9;
        margin-top: 1.15rem;
        padding-top: .35rem;
        text-align: left;
    }
    .shop-meta-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .65rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .8rem;
    }
    .shop-meta-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .shop-meta-label {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: var(--text-secondary);
        font-weight: 500;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .shop-meta-label i {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .74rem;
    }
    .shop-meta-value {
        color: var(--text-primary);
        font-weight: 600;
        text-align: right;
        min-width: 0;
        word-break: break-word;
    }

    .shop-identity-actions {
        padding: .9rem 1.25rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: .55rem;
    }

    /* ── Quick Jump Navigation ── */
    .shop-nav {
        padding: .6rem;
        display: flex;
        flex-direction: column;
        gap: .25rem;
    }
    .shop-nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
        padding: .6rem .8rem;
        border-radius: 9px;
        text-decoration: none;
        color: #475569;
        font-size: .82rem;
        font-weight: 600;
        transition: background .15s, color .15s;
    }
    .shop-nav-link:hover {
        background: #f8fafc;
        color: var(--text-primary);
    }
    .shop-nav-link.active {
        background: rgba(0, 119, 182, .08);
        color: #0077b6;
    }
    .shop-nav-link-left {
        display: inline-flex;
        align-items: center;
        gap: .65rem;
    }
    .shop-nav-link i {
        font-size: .95rem;
        opacity: .85;
    }
    .shop-nav-count {
        font-size: .7rem;
        font-weight: 700;
        padding: .14rem .48rem;
        border-radius: 50px;
        background: #f1f5f9;
        color: #475569;
    }
    .shop-nav-link.active .shop-nav-count {
        background: rgba(0, 119, 182, .14);
        color: #0077b6;
    }

    /* ── Section Cards (Right Column) ── */
    .shop-section {
        scroll-margin-top: 86px;
        transition: box-shadow .3s ease, border-color .3s ease;
    }
    .shop-section:target {
        border-color: rgba(0, 119, 182, .45);
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .08), 0 4px 16px rgba(15, 23, 42, .06);
    }
    .shop-section-header {
        padding: 1rem 1.35rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        flex-wrap: wrap;
        background: #fff;
    }
    .shop-section-header-left {
        display: flex;
        align-items: center;
        gap: .8rem;
        min-width: 0;
    }
    .shop-section-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .shop-icon-primary { background: rgba(0, 119, 182, .1); color: #0077b6; }
    .shop-icon-success { background: rgba(16, 185, 129, .1); color: #059669; }
    .shop-icon-indigo  { background: rgba(99, 102, 241, .1); color: #4f46e5; }

    .shop-section-title {
        font-size: .94rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.25;
    }
    .shop-section-subtitle {
        font-size: .75rem;
        color: var(--text-secondary);
        margin: .12rem 0 0;
        line-height: 1.3;
    }
    .shop-section-body {
        padding: 1.2rem 1.35rem;
    }
    .shop-section-footer {
        padding: .85rem 1.35rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .shop-footer-note {
        font-size: .74rem;
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }

    /* ── KPI Tiles (Clean Executive Grid) ── */
    .shop-kpi-tile {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .95rem 1.05rem;
        border-radius: 11px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        height: 100%;
        transition: border-color .15s, background .15s;
    }
    .shop-kpi-tile:hover {
        border-color: #cbd5e1;
        background: #fff;
    }
    .shop-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .shop-kpi-content {
        min-width: 0;
        flex: 1;
    }
    .shop-kpi-label {
        font-size: .7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary);
        margin-bottom: .18rem;
    }
    .shop-kpi-value {
        font-size: 1.08rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        word-break: break-word;
    }
    .shop-kpi-sub {
        font-size: .71rem;
        color: var(--text-secondary);
        margin-top: .18rem;
    }

    /* ── Inventory Table ── */
    .shop-stock-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }
    .shop-stock-table thead th {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8fafc;
        padding: .7rem 1.1rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .shop-stock-table tbody td {
        padding: .8rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: .82rem;
    }
    .shop-stock-table tbody tr:last-child td {
        border-bottom: none;
    }
    .shop-stock-table tbody tr:hover {
        background: #f8fafc;
    }
    .shop-cat-badge {
        display: inline-block;
        padding: .18rem .55rem;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        font-size: .71rem;
        font-weight: 600;
    }
    .shop-qty-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .22rem .58rem;
        border-radius: 50px;
        font-size: .74rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .shop-qty-pill.qty-ok {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .shop-qty-pill.qty-low {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    /* ── Employee Directory Rows ── */
    .shop-emp-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        padding: .85rem 1.35rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background .15s;
    }
    .shop-emp-row:last-child {
        border-bottom: none;
    }
    .shop-emp-row:hover {
        background: #f8fafc;
    }
    .shop-emp-left {
        display: flex;
        align-items: center;
        gap: .8rem;
        min-width: 0;
        flex: 1;
    }
    .shop-emp-avatar,
    .shop-emp-initials {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .shop-emp-avatar {
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .1);
    }
    .shop-emp-initials {
        background: linear-gradient(135deg, #0077b6, #004e89);
        color: #fff;
        font-size: .82rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .shop-emp-name {
        font-size: .85rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .shop-emp-meta {
        font-size: .73rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Action Buttons (Match /profile) ── */
    .btn-shop-primary {
        background: linear-gradient(135deg, #0077b6, #005691);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: .5rem 1.05rem;
        font-size: .8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .42rem;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(0, 119, 182, .22);
        transition: all .18s ease;
    }
    .btn-shop-primary:hover {
        background: linear-gradient(135deg, #00669e, #004473);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 119, 182, .3);
    }
    .btn-shop-outline {
        background: #fff;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        padding: .48rem .95rem;
        font-size: .8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        text-decoration: none;
        transition: all .15s ease;
    }
    .btn-shop-outline:hover {
        border-color: #0077b6;
        color: #0077b6;
        background: #f0f9ff;
    }

    @media (max-width: 575.98px) {
        .shop-section-header,
        .shop-section-body,
        .shop-section-footer,
        .shop-emp-row {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .shop-stock-table thead th,
        .shop-stock-table tbody td {
            padding-left: .85rem;
            padding-right: .85rem;
        }
        .shop-emp-row {
            flex-wrap: wrap;
        }
    }
</style>
@endpush

@section('content')
@php
    $shopInitials = collect(explode(' ', trim($shop->shop_name)))
        ->filter()
        ->take(2)
        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $hasLogo = $shop->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($shop->logo);
    $totalStockRecords = $shop->shopStocks->count();
    $previewStocks = $shop->shopStocks->take(8);
@endphp

<div class="row g-4">
    {{-- Left Column: Branch Identity Card & Quick Jump Nav --}}
    <div class="col-lg-4">
        <div class="shop-card mb-3">
            <div class="shop-cover"></div>
            <div class="px-4 pb-4 text-center">
                <div class="shop-avatar-wrap">
                    @if($hasLogo)
                        <img src="{{ asset('media/' . $shop->logo) }}" alt="{{ $shop->shop_name }}" class="shop-avatar-img">
                    @else
                        <div class="shop-avatar-fallback">
                            {{ $shopInitials ?: 'SH' }}
                        </div>
                    @endif
                </div>

                <h5 class="shop-name-title">{{ $shop->shop_name }}</h5>
                <p class="shop-slogan-text">
                    {{ $shop->slogan ?: ($shop->location ?: 'Retail Branch') }}
                </p>

                <div>
                    <span class="shop-status-pill {{ $shop->status === 'active' ? 'is-active' : 'is-inactive' }}">
                        <span class="shop-status-dot"></span>
                        {{ ucfirst($shop->status) }} Branch
                    </span>
                </div>

                <div class="shop-meta-list">
                    <div class="shop-meta-item">
                        <span class="shop-meta-label">
                            <i class="bi bi-geo-alt"></i>Location
                        </span>
                        <span class="shop-meta-value">{{ $shop->location ?: 'Not provided' }}</span>
                    </div>
                    <div class="shop-meta-item">
                        <span class="shop-meta-label">
                            <i class="bi bi-telephone"></i>Phone
                        </span>
                        <span class="shop-meta-value">{{ $shop->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="shop-meta-item">
                        <span class="shop-meta-label">
                            <i class="bi bi-envelope"></i>Email
                        </span>
                        <span class="shop-meta-value">{{ $shop->email ?: 'Not provided' }}</span>
                    </div>
                    <div class="shop-meta-item">
                        <span class="shop-meta-label">
                            <i class="bi bi-calendar3"></i>Created
                        </span>
                        <span class="shop-meta-value">{{ $shop->created_at ? $shop->created_at->format('M d, Y') : '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="shop-identity-actions">
                <a href="{{ route('shops.edit', $shop) }}" class="btn-shop-primary flex-grow-1">
                    <i class="bi bi-pencil-square"></i> Edit Shop
                </a>
                @if(auth()->user()->isOwner())
                <a href="{{ route('shops.index') }}" class="btn-shop-outline">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                @endif
            </div>
        </div>

        {{-- Quick Jump Navigation --}}
        <div class="shop-card d-none d-lg-block">
            <div class="shop-nav">
                <a href="#overviewSection" class="shop-nav-link active" data-section="overviewSection">
                    <span class="shop-nav-link-left">
                        <i class="bi bi-speedometer2"></i>
                        <span>Branch Overview</span>
                    </span>
                </a>
                <a href="#inventorySection" class="shop-nav-link" data-section="inventorySection">
                    <span class="shop-nav-link-left">
                        <i class="bi bi-layers"></i>
                        <span>Shop Inventory</span>
                    </span>
                    <span class="shop-nav-count">{{ $totalStockRecords }}</span>
                </a>
                <a href="#employeesSection" class="shop-nav-link" data-section="employeesSection">
                    <span class="shop-nav-link-left">
                        <i class="bi bi-people"></i>
                        <span>Branch Team</span>
                    </span>
                    <span class="shop-nav-count">{{ $shop->users->count() }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Right Column: KPIs, Inventory, and Team Directory --}}
    <div class="col-lg-8">
        {{-- 1. Branch Performance Overview --}}
        <div class="shop-card shop-section mb-4" id="overviewSection">
            <div class="shop-section-header">
                <div class="shop-section-header-left">
                    <div class="shop-section-icon shop-icon-primary">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <h6 class="shop-section-title">Branch Overview</h6>
                        <p class="shop-section-subtitle">Live sales performance, inventory volume, and staffing summary.</p>
                    </div>
                </div>
            </div>

            <div class="shop-section-body">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <div class="shop-kpi-tile">
                            <div class="shop-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="shop-kpi-content">
                                <div class="shop-kpi-label">Total Sales Revenue</div>
                                <div class="shop-kpi-value" style="color:#059669;">TZS {{ number_format($sales_total, 0) }}</div>
                                <div class="shop-kpi-sub">{{ number_format($sales_count) }} completed {{ \Illuminate\Support\Str::plural('sale', $sales_count) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <div class="shop-kpi-tile">
                            <div class="shop-kpi-icon" style="background:rgba(0,119,182,.12);color:#0077b6;">
                                <i class="bi bi-box-seam-fill"></i>
                            </div>
                            <div class="shop-kpi-content">
                                <div class="shop-kpi-label">Units in Stock</div>
                                <div class="shop-kpi-value" style="color:#0077b6;">{{ number_format($stock_count) }}</div>
                                <div class="shop-kpi-sub">
                                    {{ number_format($totalStockRecords) }} {{ \Illuminate\Support\Str::plural('product', $totalStockRecords) }}
                                    @if($low_stock_count > 0)
                                        &middot; <span style="color:#dc2626;font-weight:600;">{{ $low_stock_count }} low</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <div class="shop-kpi-tile">
                            <div class="shop-kpi-icon" style="background:rgba(99,102,241,.12);color:#4f46e5;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="shop-kpi-content">
                                <div class="shop-kpi-label">Assigned Users</div>
                                <div class="shop-kpi-value" style="color:#4f46e5;">{{ $shop->users->count() }}</div>
                                <div class="shop-kpi-sub">Active branch personnel</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <div class="shop-kpi-tile">
                            <div class="shop-kpi-icon" style="background:{{ $low_stock_count > 0 ? 'rgba(220,38,38,.1)' : 'rgba(16,185,129,.12)' }};color:{{ $low_stock_count > 0 ? '#dc2626' : '#059669' }};">
                                <i class="bi {{ $low_stock_count > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                            </div>
                            <div class="shop-kpi-content">
                                <div class="shop-kpi-label">Stock Health</div>
                                <div class="shop-kpi-value" style="color:{{ $low_stock_count > 0 ? '#dc2626' : '#059669' }};">
                                    {{ $low_stock_count > 0 ? $low_stock_count . ' Low Alert' . ($low_stock_count > 1 ? 's' : '') : 'Healthy' }}
                                </div>
                                <div class="shop-kpi-sub">
                                    {{ $low_stock_count > 0 ? 'Items at or below alert threshold' : 'All products above alert threshold' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Shop Inventory Card --}}
        <div class="shop-card shop-section mb-4" id="inventorySection">
            <div class="shop-section-header">
                <div class="shop-section-header-left">
                    <div class="shop-section-icon shop-icon-success">
                        <i class="bi bi-layers-fill"></i>
                    </div>
                    <div>
                        <h6 class="shop-section-title">Shop Inventory</h6>
                        <p class="shop-section-subtitle">Top stocked items currently available at {{ $shop->shop_name }}.</p>
                    </div>
                </div>
                <a href="{{ route('shop-stock.index', ['shop_id' => $shop->id]) }}" class="btn-shop-outline">
                    <i class="bi bi-box-arrow-up-right"></i> View All Stock
                </a>
            </div>

            <div class="table-responsive">
                <table class="shop-stock-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="d-none d-sm-table-cell">Category</th>
                            <th class="text-center">Remaining Qty</th>
                            <th class="text-end">Selling Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($previewStocks as $stock)
                        <tr>
                            <td>
                                <div class="fw-600" style="color:var(--text-primary);">{{ $stock->item->item_name ?? '—' }}</div>
                                <div class="d-sm-none mt-1">
                                    <span class="shop-cat-badge">{{ $stock->item->category->category_name ?? 'General' }}</span>
                                </div>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                <span class="shop-cat-badge">{{ $stock->item->category->category_name ?? 'General' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="shop-qty-pill {{ $stock->isLowStock() ? 'qty-low' : 'qty-ok' }}">
                                    @if($stock->isLowStock())
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    @endif
                                    {{ number_format($stock->remaining_quantity) }} {{ $stock->item->unit_symbol ?? '' }}
                                </span>
                            </td>
                            <td class="text-end fw-600" style="color:var(--text-primary);font-variant-numeric:tabular-nums;white-space:nowrap;">
                                TZS {{ number_format($stock->selling_price, 0) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4" style="color:var(--text-secondary);">
                                <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                                No stock items recorded in this branch yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="shop-section-footer">
                <span class="shop-footer-note">
                    <i class="bi bi-info-circle"></i>
                    Showing {{ $previewStocks->count() }} of {{ number_format($totalStockRecords) }} stocked {{ \Illuminate\Support\Str::plural('item', $totalStockRecords) }}.
                </span>
                <a href="{{ route('shop-stock.index', ['shop_id' => $shop->id]) }}" style="font-size:.78rem;font-weight:600;color:#0077b6;text-decoration:none;">
                    Manage full inventory <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        {{-- 3. Branch Team / Employees Card --}}
        <div class="shop-card shop-section" id="employeesSection">
            <div class="shop-section-header">
                <div class="shop-section-header-left">
                    <div class="shop-section-icon shop-icon-indigo">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h6 class="shop-section-title">Branch Team</h6>
                        <p class="shop-section-subtitle">Staff members assigned to operate and manage this branch.</p>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" class="btn-shop-outline">
                    <i class="bi bi-person-gear"></i> Manage Team
                </a>
            </div>

            <div>
                @forelse($shop->users as $employee)
                @php
                    $empInitials = collect(explode(' ', trim($employee->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                        ->implode('');
                @endphp
                <div class="shop-emp-row">
                    <div class="shop-emp-left">
                        @if($employee->avatar_path)
                            <img src="{{ asset('media/' . $employee->avatar_path) }}" alt="{{ $employee->name }}" class="shop-emp-avatar">
                        @else
                            <div class="shop-emp-initials">{{ $empInitials ?: 'U' }}</div>
                        @endif
                        <div style="min-width:0;">
                            <div class="shop-emp-name">{{ $employee->name }}</div>
                            <div class="shop-emp-meta">
                                {{ $employee->email }}@if($employee->phone) &middot; {{ $employee->phone }}@endif
                            </div>
                        </div>
                    </div>
                    <span class="role-pill role-{{ $employee->role }}" style="flex-shrink:0;">
                        {{ str_replace('_', ' ', strtoupper($employee->role)) }}
                    </span>
                </div>
                @empty
                <div class="text-center py-4 px-3" style="color:var(--text-secondary);font-size:.82rem;">
                    <i class="bi bi-person-x fs-4 d-block mb-1 opacity-50"></i>
                    No users assigned to this branch yet.
                </div>
                @endforelse
            </div>

            <div class="shop-section-footer">
                <span class="shop-footer-note">
                    <i class="bi bi-shield-check"></i>
                    {{ $shop->users->count() }} {{ \Illuminate\Support\Str::plural('user', $shop->users->count()) }} assigned to {{ $shop->shop_name }}.
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.shop-nav-link');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const sectionIds = ['overviewSection', 'inventorySection', 'employeesSection'];
    const sections = sectionIds.map(id => document.getElementById(id)).filter(Boolean);
    if ('IntersectionObserver' in window && sections.length) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    navLinks.forEach(l => l.classList.toggle('active', l.dataset.section === id));
                }
            });
        }, { rootMargin: '-25% 0px -60% 0px' });
        sections.forEach(sec => observer.observe(sec));
    }
});
</script>
@endpush
