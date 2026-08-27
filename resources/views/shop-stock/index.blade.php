@extends('layouts.app')
@section('title', 'Shop Inventory')
@section('page-title', 'Shop Stock')
@section('breadcrumb')
<li class="breadcrumb-item active">Shop Stock</li>
@endsection
@section('content')
<style>
    #shopStockTable .bi {
        font-size: 0.78rem !important;
    }
    #shopStockTable .btn .bi {
        font-size: 0.72rem !important;
    }
    #shopStockTable .btn,
    #shopStockTable .btn-xs {
        padding: 3.5px 7px !important;
        font-size: 0.76rem !important;
        line-height: 1.3 !important;
        min-height: auto !important;
    }
    /* Specific adjustment for toggler to stay perfectly square */
    #shopStockTable .toggle-child-details {
        padding: 3px 8px !important;
    }
    .hover-lift {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .hover-lift:hover {
        transform: translateY(-1.5px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
    }
</style>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h5 class="mb-0 fw-700" style="color: var(--text-primary); font-size: 1.25rem;">Shop Inventory</h5>
        <small style="color: var(--text-secondary); font-size: 0.82rem;">Available products in retail shops</small>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        <a href="{{ route('shop-stock.export-available', request()->only('shop_id')) }}" class="btn btn-sm btn-outline-success border-success-subtle text-success fw-600 rounded-3 shadow-xs hover-lift d-inline-flex align-items-center px-3 py-1.5" style="font-size: 0.82rem;">
            <i class="bi bi-file-earmark-arrow-down-fill me-1.5"></i> Download Available Stock
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle fw-600 rounded-3 shadow-xs hover-lift d-inline-flex align-items-center px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#uploadShopStockModal" style="font-size: 0.82rem; color: var(--text-primary);">
            <i class="bi bi-file-earmark-excel-fill me-1.5 text-success"></i> Upload Stock
        </button>
        @endif

        @if(auth()->user()->isShopAdmin())
            @if(auth()->user()->allow_stock_addition)
            <button type="button" class="btn btn-sm btn-success text-white fw-600 rounded-3 shadow-xs hover-lift d-inline-flex align-items-center px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#addOwnerStockModal" style="font-size: 0.82rem; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                <i class="bi bi-plus-lg me-1.5"></i> Add Owner Stock
            </button>
            @endif
            <button type="button" class="btn btn-sm btn-accent text-white fw-600 rounded-3 shadow-xs hover-lift d-inline-flex align-items-center px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#addAdminStockModal" style="font-size: 0.82rem; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                <i class="bi bi-plus-circle-fill me-1.5"></i> Add Admin Stock
            </button>
        @endif

        @if($lowStockItems > 0)
        <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-3 fw-600 shadow-xs" style="font-size: 0.78rem; background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3);">
            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> {{ $lowStockItems }} item(s) low in stock!
        </div>
        @endif
    </div>
</div>

@if(session('import_errors'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background: rgba(233, 69, 96, 0.1); border-color: rgba(233, 69, 96, 0.2); color: #e94560;">
    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Import Failed! Please correct the following errors and try again:</h6>
    <ul class="mb-0 ps-3 small" style="max-height: 200px; overflow-y: auto;">
        @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1);"></button>
</div>
@endif

@if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
@php
    $totalStockValue = 0;
    $totalExpectedProfit = 0;
    $remainingStockValue = 0;
    $remainingExpectedProfit = 0;
    $soldStockValue = 0;
    $soldExpectedProfit = 0;

    foreach ($stocks as $st) {
        if ($st->item) {
            if ($st->item->components()->exists()) {
                $bp = $st->item->getDynamicPriceForMainStore('buying_price');
                $sp = $st->item->getDynamicPriceForMainStore('selling_price');
            } else {
                $msStock = \App\Models\MainStock::where('item_id', $st->item_id)->orderByDesc('date_received')->first();
                $bp = ($st->buying_price > 0) ? $st->buying_price : ($msStock ? $msStock->buying_price : $st->item->buying_price);
                $sp = ($st->selling_price > 0) ? $st->selling_price : ($msStock ? $msStock->selling_price : $st->item->selling_price);
            }
        } else {
            $bp = $st->buying_price;
            $sp = $st->selling_price;
        }

        $totalStockValue += $st->quantity * $bp;
        $totalExpectedProfit += $st->quantity * ($sp - $bp);
        $remainingStockValue += $st->remaining_quantity * $bp;
        $remainingExpectedProfit += $st->remaining_quantity * ($sp - $bp);
        $soldStockValue += ($st->quantity - $st->remaining_quantity) * $bp;
        $soldExpectedProfit += ($st->quantity - $st->remaining_quantity) * ($sp - $bp);
    }

    $totalQuantity = $stocks->sum('quantity');
    $totalRemainingQty = $stocks->sum('remaining_quantity');
    $totalSoldQty = $totalQuantity - $totalRemainingQty;
@endphp

<div class="row g-2 mb-4">
    <!-- Stock Value Card -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(2, 132, 199, 0.1); color: var(--accent-blue); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($totalStockValue, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Stock Value ({{ number_format($totalQuantity) }} units)">Stock Value <span class="small">({{ number_format($totalQuantity) }})</span></div>
            </div>
        </div>
    </div>

    <!-- Remaining Stock Card -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(139, 92, 246, 0.1); color: var(--accent-purple); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($remainingStockValue, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Remaining Stock ({{ number_format($totalRemainingQty) }} units)">Remaining <span class="small">({{ number_format($totalRemainingQty) }})</span></div>
            </div>
        </div>
    </div>

    <!-- Sold Stock Card -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-red); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0 text-danger" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($soldStockValue, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Sold Stock Value ({{ number_format($totalSoldQty) }} units)">Sold Value <span class="small">({{ number_format($totalSoldQty) }})</span></div>
            </div>
        </div>
    </div>

    <!-- Expected Profit from Stock Value Card -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0 text-success" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($totalExpectedProfit, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Expected Profit (Stock Value)">Exp. Profit (Stock)</div>
            </div>
        </div>
    </div>

    <!-- Expected Profit from Remaining Stock Card -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-yellow); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-piggy-bank"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="color: var(--accent-yellow) !important; font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($remainingExpectedProfit, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Expected Profit (Remaining Stock)">Exp. Profit (Rem.)</div>
            </div>
        </div>
    </div>

    <!-- Profit for Sold Stock Card -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(255, 183, 0, 0.15); color: var(--accent-gold); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-coin"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="color: #ff9f00 !important; font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($soldExpectedProfit, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Profit for Sold Stock">Profit (Sold)</div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('shop-stock.index') }}" class="row align-items-center g-2">
            <div class="col-auto"><label class="form-label mb-0">Filter by Shop:</label></div>
            <div class="col-auto">
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
@endif
@endif

<div class="d-flex align-items-center justify-content-between mb-3 px-3 py-2 rounded border d-none" id="bulkActionsBar" style="background: var(--card-bg) !important; border-color: var(--card-border) !important;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check2-square text-accent fs-5"></i>
        <span class="fw-600 small" id="selectedCountText" style="color:var(--text-primary);">0 items selected</span>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if(auth()->user()->isOwner())
        <button type="button" class="btn btn-xs btn-accent px-3 py-1" id="bulkEnableBtn" style="font-size: .75rem;">Enable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-warning px-3 py-1" id="bulkDisableBtn" style="font-size: .75rem;">Disable Custom Components</button>
        @endif
        <button type="button" class="btn btn-xs btn-outline-danger px-3 py-1" id="bulkDeleteBtn" style="font-size: .75rem;">
            <i class="bi bi-trash me-1"></i> Delete Selected
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="shopStockTable">
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="checkAllStocks" style="cursor:pointer;"></th>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Initial Qty</th>
                    <th>Remaining Qty</th>
                    <th>Alert Threshold</th>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th>Buying Price</th>
                    @endif
                    <th>Selling Price</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                $groupedStocks = $stocks->groupBy(function($st) {
                return $st->shop_id . '-' . $st->item_id . '-' . (float)$st->buying_price . '-' . (float)$st->selling_price;
                });
                $iterator = 1;
                @endphp
                @foreach($groupedStocks as $groupKey => $groupItems)
                @php
                // Filter out batches with zero remaining quantity for a cleaner batch list
                $activeGroupItems = $groupItems->filter(fn($st) => $st->remaining_quantity > 0);
                $firstSt = $groupItems->first();
                $totalQty = $groupItems->sum('quantity');
                $totalRemainingQty = $groupItems->sum('remaining_quantity');
                $isLowStockGroup = $totalRemainingQty <= $groupItems->max('low_stock_alert');

                $allIds = $groupItems->pluck('id')->toArray();
                // hasMultiple based on active (non-zero) batches
                $hasMultiple = $activeGroupItems->count() > 1;

                $hasPendingQtyRequest = $groupItems->contains(function($item) {
                return !is_null($item->pending_quantity_request);
                });
                $hasPendingPriceRequest = $groupItems->contains(function($item) {
                return $item->is_price_pending;
                });
                @endphp

                @if($hasMultiple)
                <!-- Parent row for grouped batches -->
                <tr class="parent-row {{ $isLowStockGroup ? 'low-stock-row' : '' }}">
                    <td>
                        @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $firstSt->shop_id))
                        <input type="checkbox" class="stock-checkbox-parent" data-ids="{{ json_encode($allIds) }}" style="cursor:pointer;">
                        @else
                        <input type="checkbox" disabled style="cursor:not-allowed; opacity: 0.5;">
                        @endif
                    </td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $iterator++ }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $firstSt->shop->shop_name }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($firstSt->item->image_path)
                            <img src="{{ asset('media/' . $firstSt->item->image_path) }}"
                                alt="{{ $firstSt->item->item_name }}"
                                class="rounded img-lightbox"
                                style="width: 32px; height: 32px; object-fit: cover; border: 1px solid var(--card-border);"
                                onclick="openLightbox(this.src, '{{ addslashes($firstSt->item->item_name) }}')"
                                title="Click to enlarge">
                            @else
                            <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width: 32px; height: 32px; border: 1px solid var(--card-border);">
                                <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:.83rem;">{{ $firstSt->item->item_name }}</div>
                                <div style="font-size:.7rem;color:var(--text-secondary);">
                                    {{ $firstSt->item->brand }}
                                    <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">{{ $activeGroupItems->count() }} Batch{{ $activeGroupItems->count() === 1 ? '' : 'es' }}</span>
                                    @if($firstSt->is_admin_stock)
                                    <span style="background:rgba(57,178,255,.12);color:#39b2ff;padding:.15rem .4rem;border-radius:6px;font-size:.65rem;font-weight:600;margin-left:5px;">Admin Stock</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($hasPendingQtyRequest)
                        <div class="mt-2 text-warning small fw-600 d-flex align-items-center gap-1 text-start" style="font-size: .75rem;">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i> Pending Edit Request in Group (Expand to view)
                        </div>
                        @endif
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $firstSt->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">{{ $totalQty }}</td>
                    <td>
                        <strong style="color:{{ $isLowStockGroup ? '#e94560' : '#3fb950' }};font-size:.9rem;">
                            {{ $totalRemainingQty }}
                        </strong>
                        @if($isLowStockGroup)
                        <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#e94560;font-size:.75rem;" title="Low Stock!"></i>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">
                        {{ $firstSt->low_stock_alert }} units
                        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                        <button type="button" class="btn btn-xs btn-outline-warning btn-edit-alert ms-1 p-0 px-1"
                            data-ids="{{ json_encode($allIds) }}"
                            data-current="{{ $firstSt->low_stock_alert }}"
                            data-item="{{ $firstSt->item->item_name }}"
                            title="Edit Alert Threshold">
                            <i class="bi bi-pencil" style="font-size:.65rem;"></i>
                        </button>
                        @endif
                    </td>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <td style="font-size:.82rem;font-weight:600;color:var(--text-secondary);">
                        @php
                            if (auth()->user()->isOwner()) {
                                if ($firstSt->item && $firstSt->item->components()->exists()) {
                                    $displayBp = $firstSt->item->getDynamicPriceForMainStore('buying_price');
                                } else {
                                    $msStock = \App\Models\MainStock::where('item_id', $firstSt->item_id)->orderByDesc('date_received')->first();
                                    $displayBp = $msStock ? $msStock->buying_price : $firstSt->buying_price;
                                }
                            } else {
                                $displayBp = $firstSt->buying_price;
                            }
                        @endphp
                        TZS {{ number_format($displayBp, 0) }}
                    </td>
                    @endif
                    <td style="font-size:.82rem;font-weight:600;">
                        @if(auth()->user()->isOwner())
                            @php
                                if ($firstSt->item && $firstSt->item->components()->exists()) {
                                    $displaySp = $firstSt->item->getDynamicPriceForMainStore('selling_price');
                                } else {
                                    $msStock = \App\Models\MainStock::where('item_id', $firstSt->item_id)->orderByDesc('date_received')->first();
                                    $displaySp = $msStock ? $msStock->selling_price : $firstSt->selling_price;
                                }
                            @endphp
                            TZS {{ number_format($displaySp, 0) }}
                        @else
                            @if(\App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT' && !$firstSt->is_sellable)
                            <span class="text-danger">TZS {{ number_format($firstSt->selling_price, 0) }}</span>
                            <div class="badge bg-danger mt-1 d-block text-wrap" style="font-size:.7rem;" title="Locked pending selling price update">
                                <i class="bi bi-lock-fill"></i> PENDING_PRICE_UPDATE
                            </div>
                            <div class="small mt-1 text-info" style="font-size:.7rem; font-weight:normal;">
                                New Transfer Cost: TZS {{ number_format($firstSt->buying_price, 0) }}
                            </div>
                            @else
                            TZS {{ number_format($firstSt->selling_price, 0) }}
                            @if($hasPendingPriceRequest)
                            <div class="small mt-1 text-warning" title="Pending Owner Approval">
                                <i class="bi bi-hourglass-split"></i> Pending: <strong>TZS {{ number_format($firstSt->pending_selling_price ?? $firstSt->buying_price, 0) }}</strong>
                            </div>
                            @endif
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-xs btn-outline-info toggle-child-details" style="padding: 2.5px 7px; font-weight:600;" title="Toggle Batches">
                                <i class="bi bi-chevron-down me-1"></i>
                            </button>

                            @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $firstSt->shop_id))
                            <button type="button" class="btn btn-xs btn-outline-success btn-quick-restock"
                                data-shop-id="{{ $firstSt->shop_id }}"
                                data-item-id="{{ $firstSt->item_id }}"
                                data-item-name="{{ $firstSt->item->item_name }}"
                                data-buying-price="{{ (int)$firstSt->buying_price }}"
                                data-selling-price="{{ (int)$firstSt->selling_price }}"
                                data-low-stock-alert="{{ $firstSt->low_stock_alert }}"
                                data-is-admin-stock="{{ $firstSt->is_admin_stock ? 1 : 0 }}"
                                title="Quick Restock">
                                <i class="bi bi-plus-square me-1"></i>
                            </button>
                            @endif

                            <div class="child-details-template d-none">
                                <div class="p-3 border-top border-bottom" style="background: rgba(0,0,0,0.12);">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="small fw-700 text-secondary mb-0 uppercase"><i class="bi bi-layers-half me-1"></i> Individual Stock Batches for {{ $firstSt->item->item_name }}</h6>
                                    </div>
                                    <div class="table-responsive text-start">
                                        <table class="table table-sm table-hover mb-0" style="font-size: .8rem; border: 1px solid var(--card-border) !important; background: var(--body-bg) !important; color: var(--text-primary) !important;">
                                            <thead>
                                                <tr style="background: rgba(255,255,255,0.03);">
                                                    <th style="width: 30px;"></th>
                                                    <th style="width: 50px;">No</th>
                                                    <th>Date Received</th>
                                                    <th>Initial Qty</th>
                                                    <th>Remaining Qty</th>
                                                    <th>Stock Source</th>
                                                    <th>Alert Threshold</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($activeGroupItems as $st)
                                                <tr class="{{ $st->isLowStock() ? 'low-stock-row' : '' }}">
                                                    <td>
                                                        @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id))
                                                        <input type="checkbox" class="stock-checkbox" data-id="{{ $st->id }}" style="cursor:pointer;">
                                                        @else
                                                        <input type="checkbox" disabled style="cursor:not-allowed; opacity: 0.5;">
                                                        @endif
                                                    </td>
                                                    <td style="font-weight:600;">{{ $loop->iteration }}</td>
                                                    <td>{{ $st->date_received ? $st->date_received->format('Y-m-d') : 'N/A' }}</td>
                                                    <td>{{ $st->quantity }}</td>
                                                    <td>
                                                        <strong style="color:{{ $st->isLowStock() ? '#e94560' : '#3fb950' }};">
                                                            {{ $st->remaining_quantity }}
                                                        </strong>
                                                        @if($st->isLowStock())
                                                        <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#e94560;font-size:.7rem;" title="Low Stock!"></i>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($st->is_admin_stock)
                                                        <span style="background:rgba(57,178,255,.12);color:#39b2ff;padding:.15rem .4rem;border-radius:6px;font-size:.65rem;font-weight:600;">Admin Stock</span>
                                                        @else
                                                        <span style="background:rgba(245,158,11,.12);color:#f59e0b;padding:.15rem .4rem;border-radius:6px;font-size:.65rem;font-weight:600;">Owner Stock</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-secondary">
                                                        {{ $st->low_stock_alert }} units
                                                        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                                                        <button type="button" class="btn btn-xs btn-outline-warning btn-edit-alert ms-1 p-0 px-1"
                                                            data-ids="{{ json_encode([$st->id]) }}"
                                                            data-current="{{ $st->low_stock_alert }}"
                                                            data-item="{{ $st->item->item_name }}"
                                                            title="Edit Alert Threshold">
                                                            <i class="bi bi-pencil" style="font-size:.65rem;"></i>
                                                        </button>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if($st->is_price_pending && (auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id)))
                                                            <button type="button" class="btn btn-xs btn-success btn-approve-price"
                                                                data-url="{{ route('shop-stock.approve-price', $st) }}"
                                                                data-pending-price="{{ $st->pending_selling_price ?? $st->buying_price }}"
                                                                data-buying-price="{{ $st->buying_price }}"
                                                                data-current-selling-price="{{ $st->selling_price }}"
                                                                data-item-name="{{ $st->item->item_name }}"
                                                                data-mode="{{ \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') }}"
                                                                title="Approve Price Change">
                                                                <i class="bi bi-check-lg"></i> {{ \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT' ? 'Update Price' : 'Approve' }}
                                                            </button>
                                                            @endif
                                                            @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id))
                                                            <button type="button" class="btn btn-xs btn-outline-success btn-quick-restock"
                                                                data-shop-id="{{ $st->shop_id }}"
                                                                data-item-id="{{ $st->item_id }}"
                                                                data-item-name="{{ $st->item->item_name }}"
                                                                data-buying-price="{{ (int)$st->buying_price }}"
                                                                data-selling-price="{{ (int)$st->selling_price }}"
                                                                data-low-stock-alert="{{ $st->low_stock_alert }}"
                                                                data-is-admin-stock="{{ $st->is_admin_stock ? 1 : 0 }}"
                                                                title="Quick Restock">
                                                                <i class="bi bi-plus-square"></i>
                                                            </button>
                                                            @endif
                                                            <a href="{{ route('shop-stock.show', $st) }}" class="btn btn-xs btn-outline-custom" title="View details"><i class="bi bi-eye"></i></a>
                                                            @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id))
                                                            <a href="{{ route('shop-stock.edit', $st) }}" class="btn btn-xs btn-outline-custom" title="Edit batch"><i class="bi bi-pencil"></i></a>

                                                            @if(auth()->user()->isOwner() || $st->is_admin_stock)
                                                            @if($st->quantity == $st->remaining_quantity)
                                                            <form action="{{ route('shop-stock.destroy', $st) }}" method="POST" class="d-inline delete-stock-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button" class="btn btn-xs btn-outline-danger confirm-delete-btn" title="Delete stock batch">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                            @endif
                                                            @endif

                                                            @if(!auth()->user()->isOwner() && !$st->is_admin_stock)
                                                            <button type="button" class="btn btn-xs btn-outline-warning" data-bs-toggle="modal" data-bs-target="#requestEditModal{{ $st->id }}" title="Request quantity edit to Owner">
                                                                <i class="bi bi-envelope-exclamation"></i>
                                                            </button>
                                                            @endif
                                                            <div class="form-check form-switch ms-1 mb-0 d-flex align-items-center">
                                                                <input class="form-check-input toggle-components-btn" type="checkbox" data-id="{{ $st->id }}" style="cursor:pointer; width: 30px; height: 16px;"
                                                                    {{ $st->allow_components ? 'checked' : '' }} title="Toggle custom components capability">
                                                            </div>
                                                            @endif
                                                        </div>

                                                        <!-- Display edit request panel in-row if pending -->
                                                        @if(auth()->user()->isOwner() && !is_null($st->pending_quantity_request))
                                                        <div class="mt-2 p-2 rounded border border-warning text-start" style="background: rgba(245, 158, 11, 0.05); max-width: 320px;">
                                                            <div class="text-warning fw-700 small mb-1">
                                                                <i class="bi bi-exclamation-triangle-fill"></i> Pending Edit Request
                                                            </div>
                                                            <div class="small text-secondary mb-2" style="font-size: .75rem; line-height: 1.3;">
                                                                <strong>Request:</strong> Change remaining qty from {{ $st->remaining_quantity }} to <span class="badge bg-warning text-dark">{{ $st->pending_quantity_request }}</span>
                                                                <br>
                                                                <strong>Reason:</strong> "{{ $st->pending_quantity_reason }}"
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <form action="{{ route('shop-stock.approve-quantity', $st) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-xs btn-success px-2 py-0.5" style="font-size: .65rem;">Confirm</button>
                                                                </form>
                                                                <form action="{{ route('shop-stock.reject-quantity', $st) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-xs btn-outline-danger px-2 py-0.5" style="font-size: .65rem;">Reject</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        @elseif(auth()->user()->isShopAdmin() && !is_null($st->pending_quantity_request))
                                                        <div class="mt-1 text-warning small fw-600 d-flex align-items-center gap-1 text-start" style="font-size: .7rem;" title="Reason: {{ $st->pending_quantity_reason }}">
                                                            <i class="bi bi-hourglass-split"></i> Edit Pending: {{ $st->pending_quantity_request }} qty
                                                        </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @else
                <!-- Single batch row (original layout) -->
                <tr class="{{ $firstSt->isLowStock() ? 'low-stock-row' : '' }}">
                    <td>
                        @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $firstSt->shop_id))
                        <input type="checkbox" class="stock-checkbox" data-id="{{ $firstSt->id }}" style="cursor:pointer;">
                        @else
                        <input type="checkbox" disabled style="cursor:not-allowed; opacity: 0.5;">
                        @endif
                    </td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $iterator++ }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $firstSt->shop->shop_name }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($firstSt->item->image_path)
                            <img src="{{ asset('media/' . $firstSt->item->image_path) }}"
                                alt="{{ $firstSt->item->item_name }}"
                                class="rounded img-lightbox"
                                style="width: 32px; height: 32px; object-fit: cover; border: 1px solid var(--card-border);"
                                onclick="openLightbox(this.src, '{{ addslashes($firstSt->item->item_name) }}')"
                                title="Click to enlarge">
                            @else
                            <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width: 32px; height: 32px; border: 1px solid var(--card-border);">
                                <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:.83rem;">{{ $firstSt->item->item_name }}</div>
                                <div style="font-size:.7rem;color:var(--text-secondary);">
                                    {{ $firstSt->item->brand }}
                                    @if($firstSt->is_admin_stock)
                                    <span style="background:rgba(57,178,255,.12);color:#39b2ff;padding:.15rem .4rem;border-radius:6px;font-size:.65rem;font-weight:600;margin-left:5px;">Admin Stock</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if(auth()->user()->isOwner() && !is_null($firstSt->pending_quantity_request))
                        <div class="mt-2 p-2 rounded border border-warning text-start" style="background: rgba(245, 158, 11, 0.05); max-width: 350px;">
                            <div class="text-warning fw-700 small mb-1">
                                <i class="bi bi-exclamation-triangle-fill"></i> Pending Edit Request
                            </div>
                            <div class="small text-secondary mb-2" style="font-size: .78rem; line-height: 1.3;">
                                <strong>Request:</strong> Change remaining qty from {{ $firstSt->remaining_quantity }} to <span class="badge bg-warning text-dark">{{ $firstSt->pending_quantity_request }}</span>
                                <br>
                                <strong>Reason:</strong> "{{ $firstSt->pending_quantity_reason }}"
                            </div>
                            <div class="d-flex gap-2">
                                <form action="{{ route('shop-stock.approve-quantity', $firstSt) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success px-2 py-0.5" style="font-size: .7rem;">Confirm</button>
                                </form>
                                <form action="{{ route('shop-stock.reject-quantity', $firstSt) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-danger px-2 py-0.5" style="font-size: .7rem;">Reject</button>
                                </form>
                            </div>
                        </div>
                        @elseif(auth()->user()->isShopAdmin() && !is_null($firstSt->pending_quantity_request))
                        <div class="mt-2 text-warning small fw-600 d-flex align-items-center gap-1 text-start" title="Reason: {{ $firstSt->pending_quantity_reason }}">
                            <i class="bi bi-hourglass-split"></i> Edit Pending: {{ $firstSt->pending_quantity_request }} qty
                        </div>
                        @endif
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $firstSt->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">{{ $firstSt->quantity }}</td>
                    <td>
                        <strong style="color:{{ $firstSt->isLowStock() ? '#e94560' : '#3fb950' }};font-size:.9rem;">
                            {{ $firstSt->remaining_quantity }}
                        </strong>
                        @if($firstSt->isLowStock())
                        <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#e94560;font-size:.75rem;" title="Low Stock!"></i>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">
                        {{ $firstSt->low_stock_alert }} units
                        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                        <button type="button" class="btn btn-xs btn-outline-warning btn-edit-alert ms-1 p-0 px-1"
                            data-ids="{{ json_encode([$firstSt->id]) }}"
                            data-current="{{ $firstSt->low_stock_alert }}"
                            data-item="{{ $firstSt->item->item_name }}"
                            title="Edit Alert Threshold">
                            <i class="bi bi-pencil" style="font-size:.65rem;"></i>
                        </button>
                        @endif
                    </td>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <td style="font-size:.82rem;font-weight:600;color:var(--text-secondary);">
                        @php
                            if (auth()->user()->isOwner()) {
                                if ($firstSt->item && $firstSt->item->components()->exists()) {
                                    $displayBp = $firstSt->item->getDynamicPriceForMainStore('buying_price');
                                } else {
                                    $msStock = \App\Models\MainStock::where('item_id', $firstSt->item_id)->orderByDesc('date_received')->first();
                                    $displayBp = $msStock ? $msStock->buying_price : $firstSt->buying_price;
                                }
                            } else {
                                $displayBp = $firstSt->buying_price;
                            }
                        @endphp
                        TZS {{ number_format($displayBp, 0) }}
                    </td>
                    @endif
                    <td style="font-size:.82rem;font-weight:600;">
                        @if(auth()->user()->isOwner())
                            @php
                                if ($firstSt->item && $firstSt->item->components()->exists()) {
                                    $displaySp = $firstSt->item->getDynamicPriceForMainStore('selling_price');
                                } else {
                                    $msStock = \App\Models\MainStock::where('item_id', $firstSt->item_id)->orderByDesc('date_received')->first();
                                    $displaySp = $msStock ? $msStock->selling_price : $firstSt->selling_price;
                                }
                            @endphp
                            TZS {{ number_format($displaySp, 0) }}
                        @else
                            @if(\App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT' && !$firstSt->is_sellable)
                            <span class="text-danger">TZS {{ number_format($firstSt->selling_price, 0) }}</span>
                            <div class="badge bg-danger mt-1 d-block text-wrap" style="font-size:.7rem;" title="Locked pending selling price update">
                                <i class="bi bi-lock-fill"></i> PENDING_PRICE_UPDATE
                            </div>
                            <div class="small mt-1 text-info" style="font-size:.7rem; font-weight:normal;">
                                New Transfer Cost: TZS {{ number_format($firstSt->buying_price, 0) }}
                            </div>
                            @else
                            TZS {{ number_format($firstSt->selling_price, 0) }}
                            @if($firstSt->is_price_pending)
                            <div class="small mt-1 text-warning" title="Pending Owner Approval">
                                <i class="bi bi-hourglass-split"></i> Pending: <strong>TZS {{ number_format($firstSt->pending_selling_price ?? $firstSt->buying_price, 0) }}</strong>
                            </div>
                            @endif
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($firstSt->is_price_pending && (auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $firstSt->shop_id)))
                            <button type="button" class="btn btn-xs btn-success btn-approve-price"
                                data-url="{{ route('shop-stock.approve-price', $firstSt) }}"
                                data-pending-price="{{ $firstSt->pending_selling_price ?? $firstSt->buying_price }}"
                                data-buying-price="{{ $firstSt->buying_price }}"
                                data-current-selling-price="{{ $firstSt->selling_price }}"
                                data-item-name="{{ $firstSt->item->item_name }}"
                                data-mode="{{ \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') }}"
                                title="Approve Price Change">
                                <i class="bi bi-check-lg"></i> {{ \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT' ? 'Update Price' : 'Approve' }}
                            </button>
                            @endif
                            @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $firstSt->shop_id))
                            <button type="button" class="btn btn-xs btn-outline-success btn-quick-restock"
                                data-shop-id="{{ $firstSt->shop_id }}"
                                data-item-id="{{ $firstSt->item_id }}"
                                data-item-name="{{ $firstSt->item->item_name }}"
                                data-buying-price="{{ (int)$firstSt->buying_price }}"
                                data-selling-price="{{ (int)$firstSt->selling_price }}"
                                data-low-stock-alert="{{ $firstSt->low_stock_alert }}"
                                data-is-admin-stock="{{ $firstSt->is_admin_stock ? 1 : 0 }}"
                                title="Quick Restock">
                                <i class="bi bi-plus-square me-1"></i>
                            </button>
                            @endif
                            <a href="{{ route('shop-stock.show', $firstSt) }}" class="btn btn-xs btn-outline-custom" title="View details"><i class="bi bi-eye"></i></a>
                            @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $firstSt->shop_id))
                            <a href="{{ route('shop-stock.edit', $firstSt) }}" class="btn btn-xs btn-outline-custom" title="Edit batch"><i class="bi bi-pencil"></i></a>

                            @if(auth()->user()->isOwner() || $firstSt->is_admin_stock)
                            @if($firstSt->quantity == $firstSt->remaining_quantity)
                            <form action="{{ route('shop-stock.destroy', $firstSt) }}" method="POST" class="d-inline delete-stock-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-danger confirm-delete-btn" title="Delete stock batch">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                            @endif

                            @if(!auth()->user()->isOwner() && !$firstSt->is_admin_stock)
                            <button type="button" class="btn btn-xs btn-outline-warning" data-bs-toggle="modal" data-bs-target="#requestEditModal{{ $firstSt->id }}" title="Request quantity edit to Owner">
                                <i class="bi bi-envelope-exclamation"></i>
                            </button>
                            @endif
                            <div class="form-check form-switch ms-1 mb-0 d-flex align-items-center">
                                <input class="form-check-input toggle-components-btn" type="checkbox" data-id="{{ $firstSt->id }}" style="cursor:pointer; width: 30px; height: 16px;"
                                    {{ $firstSt->allow_components ? 'checked' : '' }} title="Toggle custom components capability">
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Approve Price Modal -->
<div class="modal fade" id="approvePriceModal" tabindex="-1" aria-labelledby="approvePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="approvePriceModalLabel"><i class="bi bi-check-circle-fill text-success me-2"></i>Approve Price Change</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvePriceForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p class="mb-3 text-secondary" style="font-size:0.88rem;" id="modalInstructions">
                        Assign a new selling price for <strong id="modalItemName" class="text-white"></strong>.
                        The owner proposed TZS <span id="modalProposedPrice" class="fw-bold text-success"></span>.
                    </p>
                    <div class="mb-3">
                        <label for="modalSellingPrice" class="form-label" style="font-size:0.8rem;">Selling Price (TZS)</label>
                        <input type="text" class="form-control" id="modalSellingPrice" required autocomplete="off">
                        <input type="hidden" id="modalSellingPriceHidden" name="selling_price">
                        <div id="approveModalPriceWarning" class="text-danger small mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                        <div id="modalBuyingPriceHelp" class="form-text text-muted" style="font-size:0.75rem;"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Save & Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(auth()->user()->isShopAdmin())
<style>
    .product-block {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 4px solid var(--accent, #e3b341) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .product-block:hover {
        border-color: rgba(227, 179, 65, 0.3) !important;
        border-left-color: var(--accent, #e3b341) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        background: rgba(255, 255, 255, 0.035) !important;
    }
    .bg-accent-subtle {
        background: rgba(227, 179, 65, 0.12) !important;
        border: 1px solid rgba(227, 179, 65, 0.25) !important;
    }
    .input-group-text-custom {
        background: rgba(255, 255, 255, 0.03) !important;
        border-color: var(--card-border) !important;
        color: var(--text-secondary) !important;
    }
</style>
<!-- Add Admin Stock Modal -->
<div class="modal fade" id="addAdminStockModal" tabindex="-1" aria-labelledby="addAdminStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addAdminStockModalLabel"><i class="bi bi-plus-circle text-accent me-2"></i>Add Admin Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.store-admin-stock') }}" method="POST">
                @csrf
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <div id="adminStockProductsContainer">
                        <!-- Product block 0 -->
                        <div class="product-block border rounded p-3 mb-3" data-index="0" style="background: rgba(255, 255, 255, 0.02); border-color: var(--card-border) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: rgba(255, 255, 255, 0.08) !important;">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary fw-700 me-2 px-2.5 py-1.5 product-title fw-bold text-white" style="font-size: 0.75rem;">Product #1</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input create-new-product-toggle" type="checkbox" name="products[0][create_new_product]" value="1" id="createNewProductToggle_0" style="cursor: pointer;">
                                        <label class="form-check-label small fw-600 text-muted mb-0" for="createNewProductToggle_0" style="cursor: pointer; font-size: 0.75rem;">New Product instead</label>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-product d-none" style="padding: 2px 8px !important; border-radius: 4px;"><i class="bi bi-trash3 me-1"></i> Remove</button>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mb-2">
                                <div class="col-md-12">
                                    <div class="existing-product-group">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Select Product *</label>
                                        <select name="products[0][item_id]" class="form-select form-select-sm item-id-select" required>
                                            <option value="">-- Choose Product --</option>
                                            @foreach($items as $item)
                                            <option value="{{ $item->id }}"
                                                    data-admin-buying="{{ $item->shopStocks->where('is_admin_stock', true)->last()?->buying_price ?? '' }}"
                                                    data-admin-selling="{{ $item->shopStocks->where('is_admin_stock', true)->last()?->selling_price ?? '' }}"
                                                    data-owner-buying="{{ $item->shopStocks->where('is_admin_stock', false)->last()?->buying_price ?? '' }}"
                                                    data-owner-selling="{{ $item->shopStocks->where('is_admin_stock', false)->last()?->selling_price ?? '' }}"
                                                    data-main-selling="{{ $item->mainStocks->last()?->selling_price ?? '' }}"
                                            >{{ $item->item_name }} ({{ $item->brand }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="new-product-group" style="display: none;">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Product Name *</label>
                                        <input type="text" name="products[0][new_item_name]" class="form-control form-control-sm new-item-name-input" placeholder="e.g. Wireless Keyboard K120">
                                    </div>
                                </div>
                            </div>

                            <div class="new-product-group border rounded p-3 mb-2" style="display: none; background: rgba(255, 255, 255, 0.01); border-color: var(--card-border) !important;">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label mb-0" style="font-size:0.8rem;">Category *</label>
                                            <div class="form-check form-switch mb-0" style="padding-left: 2.2em;">
                                                <input class="form-check-input create-new-category-toggle" type="checkbox" name="products[0][create_new_category]" value="1" id="createNewCategoryToggle_0" style="cursor: pointer;">
                                                <label class="form-check-label text-muted" for="createNewCategoryToggle_0" style="cursor: pointer; font-size:0.65rem;">New Category</label>
                                            </div>
                                        </div>
                                        <div class="existing-category-group">
                                            <select name="products[0][category_id]" class="form-select form-select-sm category-id-select">
                                                <option value="">-- Choose Category --</option>
                                                @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="new-category-group" style="display: none;">
                                            <input type="text" name="products[0][new_category_name]" class="form-control form-control-sm new-category-name-input" placeholder="e.g. Gaming Gear">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Brand</label>
                                        <input type="text" name="products[0][brand]" class="form-control form-control-sm brand-input" placeholder="e.g. Logitech">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Model</label>
                                        <input type="text" name="products[0][model]" class="form-control form-control-sm model-input" placeholder="e.g. K120">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Specification</label>
                                        <input type="text" name="products[0][specification]" class="form-control form-control-sm specification-input" placeholder="e.g. USB connection">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label mb-1" style="font-size:0.8rem;">Quantity *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom"><i class="bi bi-hash"></i></span>
                                        <input type="text" class="form-control quantity-display-input" required autocomplete="off" placeholder="e.g. 10">
                                    </div>
                                    <input type="hidden" name="products[0][quantity]" class="quantity-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom">TZS</span>
                                        <input type="text" class="form-control buying-price-display-input" required autocomplete="off" placeholder="e.g. 1,000">
                                    </div>
                                    <input type="hidden" name="products[0][buying_price]" class="buying-price-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom">TZS</span>
                                        <input type="text" class="form-control selling-price-display-input" required autocomplete="off" placeholder="e.g. 1,500">
                                    </div>
                                    <input type="hidden" name="products[0][selling_price]" class="selling-price-hidden-input">
                                    <div class="text-danger small mt-1 price-warning" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-success w-100 fw-600" id="btnAddProduct">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product
                        </button>
                    </div>

                    <div class="mb-3 border-top pt-3" style="border-color: var(--card-border) !important;">
                        <label for="date_received" class="form-label" style="font-size:0.8rem;">Date Received *</label>
                        <input type="date" name="date_received" id="date_received" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isShopAdmin() && auth()->user()->allow_stock_addition)
<style>
    .product-block-owner {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 4px solid var(--success, #198754) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .product-block-owner:hover {
        border-color: rgba(25, 135, 84, 0.3) !important;
        border-left-color: var(--success, #198754) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        background: rgba(255, 255, 255, 0.035) !important;
    }
</style>
<div class="modal fade" id="addOwnerStockModal" tabindex="-1" aria-labelledby="addOwnerStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addOwnerStockModalLabel"><i class="bi bi-plus-circle text-success me-2"></i>Add Owner Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.store-owner-stock') }}" method="POST">
                @csrf
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <div id="ownerStockProductsContainer">
                        <!-- Product block 0 -->
                        <div class="product-block-owner border rounded p-3 mb-3" data-index="0" style="background: rgba(255, 255, 255, 0.02); border-color: var(--card-border) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: rgba(255, 255, 255, 0.08) !important;">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success fw-700 me-2 px-2.5 py-1.5 product-title fw-bold text-white" style="font-size: 0.75rem;">Product #1</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input owner-create-new-product-toggle" type="checkbox" name="products[0][create_new_product]" value="1" id="ownerCreateNewProductToggle_0" style="cursor: pointer;">
                                        <label class="form-check-label small fw-600 text-muted mb-0" for="ownerCreateNewProductToggle_0" style="cursor: pointer; font-size: 0.75rem;">New Product instead</label>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-owner-product d-none" style="padding: 2px 8px !important; border-radius: 4px;"><i class="bi bi-trash3 me-1"></i> Remove</button>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mb-2">
                                <div class="col-md-12">
                                    <div class="owner-existing-product-group">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Select Product *</label>
                                        <select name="products[0][item_id]" class="form-select form-select-sm owner-item-id-select" required>
                                            <option value="">-- Choose Product --</option>
                                            @foreach($items as $item)
                                            <option value="{{ $item->id }}"
                                                    data-admin-buying="{{ $item->shopStocks->where('is_admin_stock', true)->last()?->buying_price ?? '' }}"
                                                    data-admin-selling="{{ $item->shopStocks->where('is_admin_stock', true)->last()?->selling_price ?? '' }}"
                                                    data-owner-buying="{{ $item->shopStocks->where('is_admin_stock', false)->last()?->buying_price ?? '' }}"
                                                    data-owner-selling="{{ $item->shopStocks->where('is_admin_stock', false)->last()?->selling_price ?? '' }}"
                                                    data-main-selling="{{ $item->mainStocks->last()?->selling_price ?? '' }}"
                                            >{{ $item->item_name }} ({{ $item->brand }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="owner-new-product-group" style="display: none;">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Product Name *</label>
                                        <input type="text" name="products[0][new_item_name]" class="form-control form-control-sm owner-new-item-name-input" placeholder="e.g. Wireless Keyboard K120">
                                    </div>
                                </div>
                            </div>

                            <div class="owner-new-product-group border rounded p-3 mb-2" style="display: none; background: rgba(255, 255, 255, 0.01); border-color: var(--card-border) !important;">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label mb-0" style="font-size:0.8rem;">Category *</label>
                                            <div class="form-check form-switch mb-0" style="padding-left: 2.2em;">
                                                <input class="form-check-input owner-create-new-category-toggle" type="checkbox" name="products[0][create_new_category]" value="1" id="ownerCreateNewCategoryToggle_0" style="cursor: pointer;">
                                                <label class="form-check-label text-muted" for="ownerCreateNewCategoryToggle_0" style="cursor: pointer; font-size:0.65rem;">New Category</label>
                                            </div>
                                        </div>
                                        <div class="owner-existing-category-group">
                                            <select name="products[0][category_id]" class="form-select form-select-sm owner-category-id-select">
                                                <option value="">-- Choose Category --</option>
                                                @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="owner-new-category-group" style="display: none;">
                                            <input type="text" name="products[0][new_category_name]" class="form-control form-control-sm owner-new-category-name-input" placeholder="e.g. Gaming Gear">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Brand</label>
                                        <input type="text" name="products[0][brand]" class="form-control form-control-sm owner-brand-input" placeholder="e.g. Logitech">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Model</label>
                                        <input type="text" name="products[0][model]" class="form-control form-control-sm owner-model-input" placeholder="e.g. K120">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1" style="font-size:0.8rem;">Specification</label>
                                        <input type="text" name="products[0][specification]" class="form-control form-control-sm owner-specification-input" placeholder="e.g. USB connection">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label mb-1" style="font-size:0.8rem;">Quantity *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom"><i class="bi bi-hash"></i></span>
                                        <input type="text" class="form-control owner-quantity-display-input" required autocomplete="off" placeholder="e.g. 10">
                                    </div>
                                    <input type="hidden" name="products[0][quantity]" class="owner-quantity-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom">TZS</span>
                                        <input type="text" class="form-control owner-buying-price-display-input" required autocomplete="off" placeholder="e.g. 1,000">
                                    </div>
                                    <input type="hidden" name="products[0][buying_price]" class="owner-buying-price-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom">TZS</span>
                                        <input type="text" class="form-control owner-selling-price-display-input" required autocomplete="off" placeholder="e.g. 1,500">
                                    </div>
                                    <input type="hidden" name="products[0][selling_price]" class="owner-selling-price-hidden-input">
                                    <div class="text-danger small mt-1 owner-price-warning" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-success w-100 fw-600" id="btnOwnerAddProduct">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product
                        </button>
                    </div>

                    <div class="mb-3 border-top pt-3" style="border-color: var(--card-border) !important;">
                        <label for="owner_date_received" class="form-label" style="font-size:0.8rem;">Date Received *</label>
                        <input type="date" name="date_received" id="owner_date_received" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Add Owner Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
<!-- Upload Shop Stock Modal -->
<div class="modal fade" id="uploadShopStockModal" tabindex="-1" aria-labelledby="uploadShopStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="uploadShopStockModalLabel"><i class="bi bi-file-earmark-excel text-accent me-2"></i>Upload Shop Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <form action="{{ route('shop-stock.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if(auth()->user()->isOwner())
                    <div class="mb-3">
                        <label for="import_shop_id" class="form-label" style="font-size:0.8rem;">Target Retail Shop *</label>
                        <select name="shop_id" id="import_shop_id" class="form-select form-select-sm" required>
                            <option value="">-- Select Retail Shop --</option>
                            @foreach($shops as $s)
                            <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(auth()->user()->isShopAdmin() && auth()->user()->allow_stock_addition)
                    <div class="mb-3">
                        <label for="import_stock_type" class="form-label" style="font-size:0.8rem;">Stock Type *</label>
                        <select name="stock_type" id="import_stock_type" class="form-select form-select-sm" required>
                            <option value="admin">Admin Stock</option>
                            <option value="owner">Owner Stock</option>
                        </select>
                    </div>
                    @endif

                    <div class="mb-4 text-center py-3 border border-dashed rounded bg-light" style="border-style: dashed !important; border-width: 2px !important; border-color: var(--accent) !important; background: rgba(0, 136, 204, 0.03) !important;">
                        <i class="bi bi-cloud-arrow-up text-accent" style="font-size: 3rem;"></i>
                        <p class="mt-2 small text-muted">Select an Excel or CSV file to import shop stocks.</p>
                        <div class="d-grid gap-2 px-4 mt-3">
                            <input type="file" name="excel_file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.8rem; background: rgba(0, 136, 204, 0.08); border-color: rgba(0, 136, 204, 0.15); color: #005f9e;">
                        <i class="bi bi-info-circle-fill me-2"></i><strong>Tip:</strong> Need a starting point?
                        <a href="{{ route('shop-stock.import-template') }}" class="fw-bold text-accent text-decoration-none ms-1 me-2"><i class="bi bi-download me-1"></i>Download Template (.xlsx)</a> or
                        <a href="{{ route('shop-stock.export-available', request()->only('shop_id')) }}" class="fw-bold text-success text-decoration-none ms-1"><i class="bi bi-file-earmark-arrow-down me-1"></i>Download Available Stock (.xlsx)</a>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent"><i class="bi bi-upload me-1"></i>Import Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Render all Request Edit modals for Shop Admin at the bottom to avoid HTML/Bootstrap stacking issues inside the nested tables --}}
@if(auth()->user()->isShopAdmin())
@foreach($stocks as $st)
@if(!$st->is_admin_stock)
<div class="modal fade" id="requestEditModal{{ $st->id }}" tabindex="-1" aria-labelledby="requestEditModalLabel{{ $st->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="requestEditModalLabel{{ $st->id }}"><i class="bi bi-envelope-exclamation text-warning me-2"></i>Request Stock Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <form action="{{ route('shop-stock.request-edit', $st) }}" method="POST">
                @csrf
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-600">Product</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $st->item->item_name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-600">Current Remaining Quantity</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $st->remaining_quantity }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="requested_quantity{{ $st->id }}" class="form-label small fw-600">Requested Quantity *</label>
                        <input type="number" name="requested_quantity" id="requested_quantity{{ $st->id }}" class="form-control form-control-sm" min="0" required placeholder="e.g. 15">
                    </div>
                    <div class="mb-3">
                        <label for="reason{{ $st->id }}" class="form-label small fw-600">Reason for Request *</label>
                        <textarea name="reason" id="reason{{ $st->id }}" class="form-control form-control-sm" rows="3" required placeholder="Describe why you need this edit (e.g. counting error during transfer receiving)"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endif

<!-- Edit Alert Threshold Modal -->
<div class="modal fade" id="editAlertModal" tabindex="-1" aria-labelledby="editAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="editAlertModalLabel"><i class="bi bi-bell-fill text-warning me-2"></i>Edit Alert Threshold</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-3">Set low-stock alert threshold for <strong id="alertItemName" class="" style="color:var(--text-primary);"></strong>.</p>
                <div class="mb-0">
                    <label for="alertThresholdInput" class="form-label small fw-600">Threshold (units) <span class="text-danger">*</span></label>
                    <input type="number" id="alertThresholdInput" class="form-control form-control-sm" min="1" required placeholder="e.g. 5">
                </div>
                <div id="alertModalError" class="text-danger small mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning fw-600" id="saveAlertBtn"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Restock Modal -->
<div class="modal fade" id="quickRestockModal" tabindex="-1" aria-labelledby="quickRestockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="quickRestockModalLabel"><i class="bi bi-arrow-left-right text-success me-2"></i>Quick Restock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <form action="{{ route('shop-stock.quick-restock') }}" method="POST">
                @csrf
                <div class="modal-body text-start">

                    <input type="hidden" name="shop_id" id="restockShopId">
                    <input type="hidden" name="item_id" id="restockItemId">
                    <input type="hidden" name="buying_price" id="restockBuyingPrice">
                    <input type="hidden" name="selling_price" id="restockSellingPrice">
                    <input type="hidden" name="low_stock_alert" id="restockLowStockAlert">
                    <input type="hidden" name="is_admin_stock" id="restockIsAdminStock">

                    @if(auth()->user()->isOwner())
                    {{-- Owner flow: stock transfer from warehouse --}}
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.82rem; background:rgba(57,178,255,.08); border-color:rgba(57,178,255,.25); color:#39b2ff;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        This will create a <strong>stock transfer</strong> from the Main Warehouse to the shop.
                        The shop admin will confirm receipt before stock is added.
                    </div>
                    <p class="mb-3 text-secondary small">
                        Dispatching <strong id="restockItemName" class="text-white"></strong> from Main Warehouse to shop.
                    </p>
                    <div class="mb-2 p-2 rounded" style="background:rgba(63,185,80,.06); border:1px solid rgba(63,185,80,.18); font-size:.82rem;">
                        <span class="text-secondary">Warehouse Available:</span>
                        <strong id="restockWarehouseAvailable" class="text-success ms-1">—</strong>
                        <span class="text-secondary ms-1">units</span>
                        <div id="restockWarehouseWarning" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Requested quantity exceeds available warehouse stock!
                        </div>
                    </div>
                    @else
                    {{-- ShopAdmin flow: direct admin stock restock --}}
                    <p class="mb-3 text-secondary small">
                        Adding a new admin stock batch for <strong id="restockItemName" class="text-white"></strong>.
                    </p>
                    @endif

                    <div class="mb-3">
                        <label for="restock_quantity" class="form-label small fw-600">Quantity to Restock *</label>
                        <input type="number" name="quantity" id="restock_quantity" class="form-control form-control-sm" min="1" required placeholder="e.g. 20">
                    </div>

                    @if(!auth()->user()->isOwner())
                    <div class="mb-3">
                        <label for="restock_date" class="form-label small fw-600">Date Received *</label>
                        <input type="date" name="date_received" id="restock_date" class="form-control form-control-sm" required value="{{ now()->toDateString() }}">
                    </div>
                    @endif
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" id="restockSubmitBtn">
                        @if(auth()->user()->isOwner())
                        <i class="bi bi-send me-1"></i> Dispatch to Shop
                        @else
                        <i class="bi bi-plus-circle me-1"></i> Save Restock
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const table = $('#shopStockTable').DataTable({
            "columnDefs": [{
                "orderable": false,
                "targets": [0, -1]
            }]
        });
        // ── Edit Alert Threshold ──────────────────────────────────────────
        let alertEditIds = [];
        $(document).on('click', '.btn-edit-alert', function() {
            alertEditIds = $(this).data('ids');
            if (!Array.isArray(alertEditIds)) alertEditIds = JSON.parse(alertEditIds);
            const current = $(this).data('current');
            const item    = $(this).data('item');
            $('#alertItemName').text(item);
            $('#alertThresholdInput').val(current);
            $('#alertModalError').hide().text('');
            $('#saveAlertBtn').prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save');
            new bootstrap.Modal(document.getElementById('editAlertModal')).show();
        });

        $('#saveAlertBtn').on('click', function() {
            const val = parseInt($('#alertThresholdInput').val());
            if (!val || val < 1) {
                $('#alertModalError').text('Please enter a valid threshold (min 1).').show();
                return;
            }
            $('#alertModalError').hide();
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Saving...');

            // Fire PATCH requests for each ID sequentially then reload
            const requests = alertEditIds.map(function(id) {
                return $.ajax({
                    url: '/shop-stock/' + id + '/alert',
                    type: 'POST',
                    data: { _method: 'PATCH', _token: '{{ csrf_token() }}', low_stock_alert: val }
                });
            });

            $.when.apply($, requests)
                .done(function() {
                    Swal.fire({ icon: 'success', title: 'Saved', text: 'Alert threshold updated!', timer: 1400, showConfirmButton: false, background: '#161b22', color: '#e6edf3' })
                        .then(() => location.reload());
                })
                .fail(function() {
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Save');
                    $('#alertModalError').text('Failed to update. Please try again.').show();
                });
        });
        // ── Quick Restock Modal ───────────────────────────────────────────
        // Populating and opening Quick Restock Modal

        $('#shopStockTable tbody').on('click', '.btn-quick-restock', function() {
            const shopId = $(this).data('shop-id');
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');
            const buyingPrice = $(this).data('buying-price');
            const sellingPrice = $(this).data('selling-price');
            const lowStockAlert = $(this).data('low-stock-alert');
            const isAdminStock = $(this).data('is-admin-stock');

            $('#restockShopId').val(shopId);
            $('#restockItemId').val(itemId);
            $('#restockItemName').text(itemName);
            $('#restockBuyingPrice').val(buyingPrice);
            $('#restockSellingPrice').val(sellingPrice);
            $('#restockLowStockAlert').val(lowStockAlert);
            $('#restockIsAdminStock').val(isAdminStock);

            // Clear previous quantity input and warnings
            $('#restock_quantity').val('').removeClass('is-invalid');
            $('#restockWarehouseWarning').addClass('d-none');
            $('#restockSubmitBtn').prop('disabled', false);

            @if(auth()->user()->isOwner())
            // Owner flow: fetch available warehouse stock for this item
            $('#restockWarehouseAvailable').text('Loading...');
            $.get('{{ route("shop-stock.warehouse-available") }}', { item_id: itemId })
                .done(function(res) {
                    const available = parseInt(res.available || 0);
                    $('#restockWarehouseAvailable').text(available.toLocaleString());
                    $('#restockWarehouseAvailable').data('available', available);
                    // Validate current qty input if already filled
                    const qty = parseInt($('#restock_quantity').val() || 0);
                    if (qty > 0 && qty > available) {
                        $('#restockWarehouseWarning').removeClass('d-none');
                        $('#restockSubmitBtn').prop('disabled', true);
                    }
                })
                .fail(function() {
                    $('#restockWarehouseAvailable').text('N/A');
                });
            @endif

            const modal = new bootstrap.Modal(document.getElementById('quickRestockModal'));
            modal.show();
        });

        @if(auth()->user()->isOwner())
        // Validate quantity vs warehouse availability in real-time (owner only)
        $('#restock_quantity').on('input', function() {
            const qty = parseInt($(this).val() || 0);
            const available = parseInt($('#restockWarehouseAvailable').data('available') || 0);
            if (available > 0 && qty > available) {
                $('#restockWarehouseWarning').removeClass('d-none');
                $('#restock_quantity').addClass('is-invalid');
                $('#restockSubmitBtn').prop('disabled', true);
            } else {
                $('#restockWarehouseWarning').addClass('d-none');
                $('#restock_quantity').removeClass('is-invalid');
                $('#restockSubmitBtn').prop('disabled', false);
            }
        });
        @endif
        // Toggle Expand/Collapse Child Row
        $('#shopStockTable tbody').on('click', '.toggle-child-details', function() {
            const tr = $(this).closest('tr');
            const row = table.row(tr);
            const icon = $(this).find('i');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                $(this).removeClass('btn-info').addClass('btn-outline-info');
            } else {
                const childHtml = tr.find('.child-details-template').html();
                row.child(childHtml).show();
                tr.addClass('shown');
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                $(this).removeClass('btn-outline-info').addClass('btn-info');

                // Initialize toggle component switch listeners inside the child row
                row.child().find('.toggle-components-btn').on('change', function() {
                    const isChecked = $(this).is(':checked');
                    const shopStockId = $(this).data('id');
                    const self = $(this);

                    $.post("{{ route('settings.toggle-components') }}", {
                            _token: "{{ csrf_token() }}",
                            shop_stock_id: shopStockId,
                            enabled: isChecked ? 1 : 0
                        })
                        .done(function(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: isChecked ? 'Manual components enabled for this product.' : 'Manual components disabled for this product.',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    background: '#161b22',
                                    color: '#e6edf3'
                                });
                            }
                        })
                        .fail(function(err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to update setting. Please try again.',
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                            self.prop('checked', !isChecked);
                        });
                });

                // Initialize approve price button listener for child rows
                row.child().find('.btn-approve-price').on('click', function() {
                    const url = $(this).data('url');
                    const pendingPrice = $(this).data('pending-price');
                    const buyingPrice = $(this).data('buying-price');
                    const currentSellingPrice = $(this).data('current-selling-price');
                    const itemName = $(this).data('item-name');
                    const mode = $(this).data('mode');

                    $('#approvePriceForm').attr('action', url);
                    $('#modalItemName').text(itemName);

                    if (mode === 'INDEPENDENT') {
                        $('#approvePriceModalLabel').html('<i class="bi bi-shield-lock-fill text-warning me-2"></i>Update Selling Price');
                        $('#modalInstructions').html(
                            'Main Store updated the transfer price for <strong class="text-white">' + itemName + '</strong>.<br>' +
                            'New transfer cost (Buying Price): <strong class="text-success">TZS ' + parseFloat(buyingPrice).toLocaleString() + '</strong>.<br>' +
                            'Please update your Selling Price to restore sales eligibility.'
                        );

                        const preFill = Math.max(parseFloat(currentSellingPrice), parseFloat(buyingPrice));
                        const formattedPrice = formatNumber(preFill.toString());
                        $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
                        $('#modalSellingPriceHidden').val(preFill);
                    } else {
                        $('#approvePriceModalLabel').html('<i class="bi bi-check-circle-fill text-success me-2"></i>Approve Price Change');
                        $('#modalInstructions').html(
                            'Assign a new selling price for <strong class="text-white">' + itemName + '</strong>.<br>' +
                            'The owner proposed TZS <span class="fw-bold text-success">' + parseFloat(pendingPrice).toLocaleString() + '</span>.'
                        );

                        const formattedPrice = formatNumber(pendingPrice.toString());
                        $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
                        $('#modalSellingPriceHidden').val(pendingPrice);
                    }

                    $('#modalBuyingPriceHelp').text('Minimum required price (Buying Price): TZS ' + parseFloat(buyingPrice).toLocaleString());

                    // Reset styling and show warning if needed
                    $('#modalSellingPrice').removeClass('is-invalid');
                    $('#approveModalPriceWarning').hide();
                    $('#approvePriceForm').find('button[type="submit"]').prop('disabled', false);

                    const modal = new bootstrap.Modal(document.getElementById('approvePriceModal'));
                    modal.show();
                });

                // Initialize delete confirmation button listener for child rows
                row.child().find('.confirm-delete-btn').on('click', function(e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    Swal.fire({
                        title: 'Delete Stock Batch?',
                        text: "Are you sure you want to delete this stock batch? This action cannot be undone.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        background: '#161b22',
                        color: '#e6edf3'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            }
        });

        function formatNumber(val) {
            let clean = val.replace(/,/g, '');
            if (isNaN(parseFloat(clean))) return '';
            let parts = clean.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        // Auto-comma formatter
        $('#modalSellingPrice').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;

            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }

            this.value = formatNumber(cleanVal);
            $('#modalSellingPriceHidden').val(cleanVal);

            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));

            // Realtime validation
            const buyingPrice = parseFloat($('#modalSellingPrice').data('buying-price') || 0);
            const enteredPrice = parseFloat(cleanVal || 0);
            const warning = $('#approveModalPriceWarning');
            const submitBtn = $('#approvePriceForm').find('button[type="submit"]');

            if (enteredPrice < buyingPrice) {
                warning.show();
                $('#modalSellingPrice').addClass('is-invalid');
                submitBtn.prop('disabled', true);
            } else {
                warning.hide();
                $('#modalSellingPrice').removeClass('is-invalid');
                submitBtn.prop('disabled', false);
            }
        });

        @if(auth()->user()->isShopAdmin())
        // Auto-comma formatter for Add Admin Stock Modal
        $('#addAdminStockModal').on('input', '.quantity-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9]/g, '');
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block').find('.quantity-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#addAdminStockModal').on('input', '.buying-price-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block').find('.buying-price-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateBlockPrices($(this).closest('.product-block'));
        });

        $('#addAdminStockModal').on('input', '.selling-price-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block').find('.selling-price-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateBlockPrices($(this).closest('.product-block'));
        });

        function validateBlockPrices(block) {
            const buying = parseFloat(block.find('.buying-price-hidden-input').val() || 0);
            const selling = parseFloat(block.find('.selling-price-hidden-input').val() || 0);
            const warning = block.find('.price-warning');

            if (selling > 0 && buying > 0 && selling < buying) {
                warning.show();
                block.find('.selling-price-display-input').addClass('is-invalid');
            } else {
                warning.hide();
                block.find('.selling-price-display-input').removeClass('is-invalid');
            }
            validateAllPrices();
        }

        function validateAllPrices() {
            let isValid = true;
            $('#addAdminStockModal .product-block').each(function() {
                const buying = parseFloat($(this).find('.buying-price-hidden-input').val() || 0);
                const selling = parseFloat($(this).find('.selling-price-hidden-input').val() || 0);
                if (selling > 0 && buying > 0 && selling < buying) {
                    isValid = false;
                }
            });
            $('#addAdminStockModal').find('button[type="submit"]').prop('disabled', !isValid);
        }

        let productIndex = 0;
        $('#btnAddProduct').on('click', function() {
            productIndex++;
            const originalBlock = $('#adminStockProductsContainer .product-block').first();
            
            // Destroy Select2 on original before cloning
            originalBlock.find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            const newBlock = originalBlock.clone();
            
            // Re-initialize Select2 on original
            originalBlock.find('select').each(function() {
                if ($(this).find('option').length > 8 && window.initSearchableSelect) {
                    window.initSearchableSelect(this);
                }
            });
            
            newBlock.attr('data-index', productIndex);
            newBlock.find('.product-title').text('Product #' + (productIndex + 1));
            
            newBlock.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/products\[\d+\]/, 'products[' + productIndex + ']');
                    $(this).attr('name', newName);
                }
                
                const id = $(this).attr('id');
                if (id) {
                    const newId = id.replace(/_\d+$/, '_' + productIndex);
                    $(this).attr('id', newId);
                    newBlock.find('label[for="' + id + '"]').attr('for', newId);
                }
            });
            
            newBlock.find('input[type="text"], input[type="number"]').val('');
            
            // Clean Select2 classes and reset select values in the cloned block
            newBlock.find('select').each(function() {
                $(this).removeClass('select2-hidden-accessible');
                $(this).val('');
                $(this).find('option').prop('selected', false);
                $(this).find('option').first().prop('selected', true);
            });
            newBlock.find('.select2-container').remove();
            
            newBlock.find('input[type="checkbox"]').prop('checked', false);
            newBlock.find('.quantity-hidden-input').val('');
            newBlock.find('.buying-price-hidden-input').val('');
            newBlock.find('.selling-price-hidden-input').val('');
            newBlock.find('.price-warning').hide();
            newBlock.find('.selling-price-display-input').removeClass('is-invalid');
            
            newBlock.find('.existing-product-group').show();
            newBlock.find('.new-product-group').hide();
            newBlock.find('.item-id-select').prop('required', true);
            newBlock.find('.new-item-name-input').prop('required', false);
            
            newBlock.find('.btn-remove-product').removeClass('d-none');
            
            $('#adminStockProductsContainer').append(newBlock);
            
            // Initialize Select2 on the new block selects
            newBlock.find('select').each(function() {
                if ($(this).find('option').length > 8 && window.initSearchableSelect) {
                    window.initSearchableSelect(this);
                }
            });
            
            updateRemoveButtons();
        });

        $('#addAdminStockModal').on('click', '.btn-remove-product', function() {
            $(this).closest('.product-block').remove();
            reindexBlocks();
            updateRemoveButtons();
            validateAllPrices();
        });

        function reindexBlocks() {
            productIndex = 0;
            $('#adminStockProductsContainer .product-block').each(function() {
                const block = $(this);
                block.attr('data-index', productIndex);
                block.find('.product-title').text('Product #' + (productIndex + 1));
                
                block.find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/products\[\d+\]/, 'products[' + productIndex + ']');
                        $(this).attr('name', newName);
                    }
                    
                    const id = $(this).attr('id');
                    if (id) {
                        const newId = id.replace(/_\d+$/, '_' + productIndex);
                        $(this).attr('id', newId);
                        block.find('label[for="' + id + '"]').attr('for', newId);
                    }
                });
                productIndex++;
            });
            productIndex--;
        }

        function updateRemoveButtons() {
            const count = $('#adminStockProductsContainer .product-block').length;
            if (count > 1) {
                $('#adminStockProductsContainer .product-block .btn-remove-product').removeClass('d-none');
            } else {
                $('#adminStockProductsContainer .product-block .btn-remove-product').addClass('d-none');
            }
        }

        $('#addAdminStockModal').on('hidden.bs.modal', function() {
            $('#adminStockProductsContainer .product-block').slice(1).remove();
            
            const block = $('#adminStockProductsContainer .product-block').first();
            block.attr('data-index', 0);
            block.find('.product-title').text('Product #1');
            block.find('input[type="text"], input[type="number"]').val('');
            
            // Destroy Select2, reset select value, clean up layout before re-initializing
            block.find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
                $(this).removeClass('select2-hidden-accessible');
                $(this).val('');
                $(this).find('option').prop('selected', false);
                $(this).find('option').first().prop('selected', true);
            });
            block.find('.select2-container').remove();
            
            block.find('input[type="checkbox"]').prop('checked', false);
            block.find('.quantity-hidden-input').val('');
            block.find('.buying-price-hidden-input').val('');
            block.find('.selling-price-hidden-input').val('');
            block.find('.price-warning').hide();
            block.find('.selling-price-display-input').removeClass('is-invalid');
            block.find('.existing-product-group').show();
            block.find('.new-product-group').hide();
            block.find('.item-id-select').prop('required', true);
            block.find('.new-item-name-input').prop('required', false);
            
            $('#date_received').val('{{ date("Y-m-d") }}');
            
            // Re-initialize Select2 on first row
            block.find('select').each(function() {
                if ($(this).find('option').length > 8 && window.initSearchableSelect) {
                    window.initSearchableSelect(this);
                }
            });
            
            productIndex = 0;
            updateRemoveButtons();
            validateAllPrices();
        });
        @endif

        $('.btn-approve-price').on('click', function() {
            const url = $(this).data('url');
            const pendingPrice = $(this).data('pending-price');
            const buyingPrice = $(this).data('buying-price');
            const currentSellingPrice = $(this).data('current-selling-price');
            const itemName = $(this).data('item-name');
            const mode = $(this).data('mode');

            $('#approvePriceForm').attr('action', url);
            $('#modalItemName').text(itemName);

            if (mode === 'INDEPENDENT') {
                $('#approvePriceModalLabel').html('<i class="bi bi-shield-lock-fill text-warning me-2"></i>Update Selling Price');
                $('#modalInstructions').html(
                    'Main Store updated the transfer price for <strong class="text-white">' + itemName + '</strong>.<br>' +
                    'New transfer cost (Buying Price): <strong class="text-success">TZS ' + parseFloat(buyingPrice).toLocaleString() + '</strong>.<br>' +
                    'Please update your Selling Price to restore sales eligibility.'
                );

                const preFill = Math.max(parseFloat(currentSellingPrice), parseFloat(buyingPrice));
                const formattedPrice = formatNumber(preFill.toString());
                $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
                $('#modalSellingPriceHidden').val(preFill);
            } else {
                $('#approvePriceModalLabel').html('<i class="bi bi-check-circle-fill text-success me-2"></i>Approve Price Change');
                $('#modalInstructions').html(
                    'Assign a new selling price for <strong class="text-white">' + itemName + '</strong>.<br>' +
                    'The owner proposed TZS <span class="fw-bold text-success">' + parseFloat(pendingPrice).toLocaleString() + '</span>.'
                );

                const formattedPrice = formatNumber(pendingPrice.toString());
                $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
                $('#modalSellingPriceHidden').val(pendingPrice);
            }

            $('#modalBuyingPriceHelp').text('Minimum required price (Buying Price): TZS ' + parseFloat(buyingPrice).toLocaleString());

            const modal = new bootstrap.Modal(document.getElementById('approvePriceModal'));
            modal.show();
        });

        // Form validation
        $('#approvePriceForm').on('submit', function(e) {
            const buyingPrice = parseFloat($('#modalSellingPrice').data('buying-price'));
            const enteredPrice = parseFloat($('#modalSellingPriceHidden').val());

            if (enteredPrice < buyingPrice) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Price Too Low',
                    text: 'Selling price cannot be less than the buying price TZS ' + buyingPrice.toLocaleString() + '.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });

        @if(auth()->user()->isShopAdmin())

        function updateBlockCategoryFields(block) {
            const isNewProduct = block.find('.create-new-product-toggle').is(':checked');
            const isNewCategory = block.find('.create-new-category-toggle').is(':checked');

            if (isNewProduct) {
                if (isNewCategory) {
                    block.find('.existing-category-group').hide();
                    block.find('.category-id-select').val('').prop('required', false);
                    block.find('.new-category-group').show();
                    block.find('.new-category-name-input').prop('required', true);
                } else {
                    block.find('.new-category-group').hide();
                    block.find('.new-category-name-input').val('').prop('required', false);
                    block.find('.existing-category-group').show();
                    block.find('.category-id-select').prop('required', true);
                }
            } else {
                block.find('.category-id-select').prop('required', false);
                block.find('.new-category-name-input').prop('required', false);
            }
        }

        // Toggle new product inputs vs select product dropdown
        $('#addAdminStockModal').on('change', '.create-new-product-toggle', function() {
            const block = $(this).closest('.product-block');
            if (this.checked) {
                block.find('.existing-product-group').hide();
                block.find('.item-id-select').val('').prop('required', false);
                block.find('.new-product-group').show();
                block.find('.new-item-name-input').prop('required', true);
                updateBlockCategoryFields(block);
            } else {
                block.find('.new-product-group').hide();
                block.find('.new-item-name-input').val('').prop('required', false);
                block.find('.existing-product-group').show();
                block.find('.item-id-select').prop('required', true);
                updateBlockCategoryFields(block);
            }
        });

        // Toggle new category input vs category select dropdown
        $('#addAdminStockModal').on('change', '.create-new-category-toggle', function() {
            const block = $(this).closest('.product-block');
            updateBlockCategoryFields(block);
        });

        // Auto-suggest values when selecting a product (Admin Stock modal)
        $('#addAdminStockModal').on('change', '.item-id-select', function() {
            const block = $(this).closest('.product-block');
            const selectedOpt = $(this).find('option:selected');
            if (!selectedOpt.val()) {
                block.find('.buying-price-display-input').val('');
                block.find('.buying-price-hidden-input').val('');
                block.find('.selling-price-display-input').val('');
                block.find('.selling-price-hidden-input').val('');
                return;
            }

            const shopBuying = selectedOpt.data('admin-buying');
            const shopSelling = selectedOpt.data('admin-selling');
            const mainSelling = selectedOpt.data('main-selling');

            if (shopBuying && shopSelling) {
                block.find('.buying-price-hidden-input').val(shopBuying);
                block.find('.buying-price-display-input').val(formatNumber(shopBuying));
                block.find('.selling-price-hidden-input').val(shopSelling);
                block.find('.selling-price-display-input').val(formatNumber(shopSelling));
            } else if (mainSelling) {
                block.find('.buying-price-hidden-input').val(mainSelling);
                block.find('.buying-price-display-input').val(formatNumber(mainSelling));
                block.find('.selling-price-hidden-input').val('');
                block.find('.selling-price-display-input').val('');
            } else {
                block.find('.buying-price-display-input').val('');
                block.find('.buying-price-hidden-input').val('');
                block.find('.selling-price-display-input').val('');
                block.find('.selling-price-hidden-input').val('');
            }
            validateBlockPrices(block);
        });

        // Add Admin Stock Form validation
        $('#addAdminStockModal form').on('submit', function(e) {
            let hasPriceError = false;
            let firstErrorPrice = 0;
            let firstErrorItem = '';

            $('#addAdminStockModal .product-block').each(function() {
                const buyingPrice = parseFloat($(this).find('.buying-price-hidden-input').val()) || 0;
                const sellingPrice = parseFloat($(this).find('.selling-price-hidden-input').val()) || 0;

                if (sellingPrice < buyingPrice) {
                    hasPriceError = true;
                    firstErrorPrice = buyingPrice;
                    const isNew = $(this).find('.create-new-product-toggle').is(':checked');
                    firstErrorItem = isNew ? $(this).find('.new-item-name-input').val() : $(this).find('.item-id-select option:selected').text();
                    return false; // break loop
                }
            });

            if (hasPriceError) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Price Too Low',
                    text: `Selling price for "${firstErrorItem}" cannot be less than the buying price TZS ${firstErrorPrice.toLocaleString()}.`,
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });
        @endif
        // Toggle shop components POS visibility per product (delegated for all DataTables pages)
        $(document).on('change', '.toggle-components-btn', function() {
            const isChecked = $(this).is(':checked');
            const shopStockId = $(this).data('id');
            const self = $(this);

            $.post("{{ route('settings.toggle-components') }}", {
                    _token: "{{ csrf_token() }}",
                    shop_stock_id: shopStockId,
                    enabled: isChecked ? 1 : 0
                })
                .done(function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved',
                            text: isChecked ? 'Manual components enabled for this product.' : 'Manual components disabled for this product.',
                            timer: 1500,
                            showConfirmButton: false,
                            background: '#161b22',
                            color: '#e6edf3'
                        });
                    }
                })
                .fail(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update setting. Please try again.',
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    self.prop('checked', !isChecked);
                });
        });

        function getSelectedStockIds() {
            const checkedIds = new Set();

            if (typeof table !== 'undefined' && table) {
                table.$('.stock-checkbox:checked').each(function() {
                    const id = $(this).data('id');
                    if (id) checkedIds.add(id);
                });

                table.$('.stock-checkbox-parent:checked').each(function() {
                    const ids = $(this).data('ids');
                    if (Array.isArray(ids)) {
                        ids.forEach(id => checkedIds.add(id));
                    }
                });
            } else {
                $('.stock-checkbox:checked').each(function() {
                    const id = $(this).data('id');
                    if (id) checkedIds.add(id);
                });

                $('.stock-checkbox-parent:checked').each(function() {
                    const ids = $(this).data('ids');
                    if (Array.isArray(ids)) {
                        ids.forEach(id => checkedIds.add(id));
                    }
                });
            }

            return Array.from(checkedIds);
        }

        function updateBulkActionsBar() {
            const checkedIds = getSelectedStockIds();
            const count = checkedIds.length;
            if (count > 0) {
                $('#selectedCountText').html(`<strong>${count}</strong> item(s) selected`);
                $('#bulkActionsBar').removeClass('d-none');
            } else {
                $('#bulkActionsBar').addClass('d-none');
            }
        }

        table.on('draw', function() {
            updateBulkActionsBar();
        });

        $('#checkAllStocks').on('change', function() {
            const isChecked = $(this).is(':checked');
            if (typeof table !== 'undefined' && table) {
                table.$('.stock-checkbox:not(:disabled)').prop('checked', isChecked);
                table.$('.stock-checkbox-parent:not(:disabled)').prop('checked', isChecked);
            }
            $('.stock-checkbox:not(:disabled)').prop('checked', isChecked);
            $('.stock-checkbox-parent:not(:disabled)').prop('checked', isChecked);
            $('#shopStockTable tbody .stock-checkbox:not(:disabled)').prop('checked', isChecked);
            updateBulkActionsBar();
        });

        $(document).on('change', '.stock-checkbox-parent', function() {
            const isChecked = $(this).is(':checked');
            const tr = $(this).closest('tr');
            const row = table.row(tr);
            if (row.child.isShown()) {
                row.child().find('.stock-checkbox:not(:disabled)').prop('checked', isChecked);
            }
            updateBulkActionsBar();
        });

        $(document).on('change', '.stock-checkbox', function() {
            updateBulkActionsBar();
            if (!$(this).is(':checked')) {
                $('#checkAllStocks').prop('checked', false);
            }
        });

        function sendBulkUpdate(enabled) {
            const checkedIds = getSelectedStockIds();
            if (checkedIds.length === 0) return;

            Swal.fire({
                title: 'Please wait...',
                html: 'Updating selected products components capability...',
                allowOutsideClick: false,
                background: '#161b22',
                color: '#e6edf3',
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.post("{{ route('settings.toggle-components') }}", {
                    _token: "{{ csrf_token() }}",
                    shop_stock_ids: checkedIds,
                    enabled: enabled ? 1 : 0
                })
                .done(function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: 'Products updated successfully!',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#e6edf3'
                    }).then(() => {
                        location.reload();
                    });
                })
                .fail(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to perform bulk update. Please try again.',
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                });
        }

        function sendBulkDelete() {
            const checkedIds = getSelectedStockIds();

            if (checkedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one stock item to delete.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Selected Stock Batches?',
                text: `Are you sure you want to delete ${checkedIds.length} selected stock batch(es)? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete selected!',
                cancelButtonText: 'Cancel',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while selected stock items are being deleted.',
                        allowOutsideClick: false,
                        background: '#161b22',
                        color: '#e6edf3',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('shop-stock.bulk-destroy') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: 'DELETE',
                            ids: checkedIds
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: res.message || 'Selected stock batches deleted successfully.',
                                timer: 1800,
                                showConfirmButton: false,
                                background: '#161b22',
                                color: '#e6edf3'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            let errorMsg = 'Failed to delete selected stock batches. Please try again.';
                            let htmlContent = '';
                            
                            if (xhr.responseJSON) {
                                if (xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                if (xhr.responseJSON.errors && Array.isArray(xhr.responseJSON.errors) && xhr.responseJSON.errors.length > 0) {
                                    htmlContent = '<div class="text-start mt-2 p-2 rounded" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); max-height: 220px; overflow-y: auto; font-size: 0.8rem; color: #f87171;"><ul class="mb-0 ps-3">' +
                                        xhr.responseJSON.errors.map(err => `<li>${err}</li>`).join('') +
                                        '</ul></div>';
                                }
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Bulk Delete Restricted',
                                html: htmlContent ? `<div class="mb-2 fw-semibold">${errorMsg}</div>${htmlContent}` : errorMsg,
                                background: '#161b22',
                                color: '#e6edf3',
                                confirmButtonColor: '#e94560'
                            });
                        }
                    });
                }
            });
        }

        @if(auth()->user()->allow_stock_addition)
        // Formatting for Owner Stock Modal inputs
        $('#addOwnerStockModal').on('input', '.owner-quantity-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9]/g, '');
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block-owner').find('.owner-quantity-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#addOwnerStockModal').on('input', '.owner-buying-price-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block-owner').find('.owner-buying-price-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateOwnerBlockPrices($(this).closest('.product-block-owner'));
        });

        $('#addOwnerStockModal').on('input', '.owner-selling-price-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block-owner').find('.owner-selling-price-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateOwnerBlockPrices($(this).closest('.product-block-owner'));
        });

        function validateOwnerBlockPrices(block) {
            const buying = parseFloat(block.find('.owner-buying-price-hidden-input').val() || 0);
            const selling = parseFloat(block.find('.owner-selling-price-hidden-input').val() || 0);
            const warning = block.find('.owner-price-warning');

            if (selling > 0 && buying > 0 && selling < buying) {
                warning.show();
                block.find('.owner-selling-price-display-input').addClass('is-invalid');
            } else {
                warning.hide();
                block.find('.owner-selling-price-display-input').removeClass('is-invalid');
            }
            validateAllOwnerPrices();
        }

        function validateAllOwnerPrices() {
            let isValid = true;
            $('#addOwnerStockModal .product-block-owner').each(function() {
                const buying = parseFloat($(this).find('.owner-buying-price-hidden-input').val() || 0);
                const selling = parseFloat($(this).find('.owner-selling-price-hidden-input').val() || 0);
                if (selling > 0 && buying > 0 && selling < buying) {
                    isValid = false;
                }
            });
            $('#addOwnerStockModal').find('button[type="submit"]').prop('disabled', !isValid);
        }

        function updateOwnerBlockCategoryFields(block) {
            const isNewProduct = block.find('.owner-create-new-product-toggle').is(':checked');
            const isNewCategory = block.find('.owner-create-new-category-toggle').is(':checked');

            if (isNewProduct) {
                if (isNewCategory) {
                    block.find('.owner-existing-category-group').hide();
                    block.find('.owner-category-id-select').val('').prop('required', false);
                    block.find('.owner-new-category-group').show();
                    block.find('.owner-new-category-name-input').prop('required', true);
                } else {
                    block.find('.owner-new-category-group').hide();
                    block.find('.owner-new-category-name-input').val('').prop('required', false);
                    block.find('.owner-existing-category-group').show();
                    block.find('.owner-category-id-select').prop('required', true);
                }
            } else {
                block.find('.owner-category-id-select').prop('required', false);
                block.find('.owner-new-category-name-input').prop('required', false);
            }
        }

        // Toggle new product inputs vs select product dropdown (Owner Stock)
        $('#addOwnerStockModal').on('change', '.owner-create-new-product-toggle', function() {
            const block = $(this).closest('.product-block-owner');
            if (this.checked) {
                block.find('.owner-existing-product-group').hide();
                block.find('.owner-item-id-select').val('').prop('required', false);
                block.find('.owner-new-product-group').show();
                block.find('.owner-new-item-name-input').prop('required', true);
                updateOwnerBlockCategoryFields(block);
            } else {
                block.find('.owner-new-product-group').hide();
                block.find('.owner-new-item-name-input').val('').prop('required', false);
                block.find('.owner-existing-product-group').show();
                block.find('.owner-item-id-select').prop('required', true);
                updateOwnerBlockCategoryFields(block);
            }
        });

        // Toggle new category input vs category select dropdown (Owner Stock)
        $('#addOwnerStockModal').on('change', '.owner-create-new-category-toggle', function() {
            const block = $(this).closest('.product-block-owner');
            updateOwnerBlockCategoryFields(block);
        });

        // Auto-suggest values when selecting a product (Owner Stock modal)
        $('#addOwnerStockModal').on('change', '.owner-item-id-select', function() {
            const block = $(this).closest('.product-block-owner');
            const selectedOpt = $(this).find('option:selected');
            if (!selectedOpt.val()) {
                block.find('.owner-buying-price-display-input').val('');
                block.find('.owner-buying-price-hidden-input').val('');
                block.find('.owner-selling-price-display-input').val('');
                block.find('.owner-selling-price-hidden-input').val('');
                return;
            }

            const shopBuying = selectedOpt.data('owner-buying');
            const shopSelling = selectedOpt.data('owner-selling');
            const mainSelling = selectedOpt.data('main-selling');

            if (shopBuying && shopSelling) {
                block.find('.owner-buying-price-hidden-input').val(shopBuying);
                block.find('.owner-buying-price-display-input').val(formatNumber(shopBuying));
                block.find('.owner-selling-price-hidden-input').val(shopSelling);
                block.find('.owner-selling-price-display-input').val(formatNumber(shopSelling));
            } else if (mainSelling) {
                block.find('.owner-buying-price-hidden-input').val(mainSelling);
                block.find('.owner-buying-price-display-input').val(formatNumber(mainSelling));
                block.find('.owner-selling-price-hidden-input').val('');
                block.find('.owner-selling-price-display-input').val('');
            } else {
                block.find('.owner-buying-price-display-input').val('');
                block.find('.owner-buying-price-hidden-input').val('');
                block.find('.owner-selling-price-display-input').val('');
                block.find('.owner-selling-price-hidden-input').val('');
            }
            validateOwnerBlockPrices(block);
        });

        // Dynamic clone/reindex/remove handler for repeatable Owner Stock rows
        let ownerProductIndex = 0;
        $('#btnOwnerAddProduct').on('click', function() {
            ownerProductIndex++;
            const originalBlock = $('#ownerStockProductsContainer .product-block-owner').first();
            
            // Destroy Select2 on original before cloning
            originalBlock.find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            const newBlock = originalBlock.clone();
            
            // Re-initialize Select2 on original
            originalBlock.find('select').each(function() {
                if ($(this).find('option').length > 8 && window.initSearchableSelect) {
                    window.initSearchableSelect(this);
                }
            });
            
            newBlock.attr('data-index', ownerProductIndex);
            newBlock.find('.product-title').text('Product #' + (ownerProductIndex + 1));
            
            newBlock.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/products\[\d+\]/, 'products[' + ownerProductIndex + ']');
                    $(this).attr('name', newName);
                }
                
                const id = $(this).attr('id');
                if (id) {
                    const newId = id.replace(/_\d+$/, '_' + ownerProductIndex);
                    $(this).attr('id', newId);
                    newBlock.find('label[for="' + id + '"]').attr('for', newId);
                }
            });
            
            newBlock.find('input[type="text"], input[type="number"]').val('');
            
            // Clean Select2 classes and reset select values in the cloned block
            newBlock.find('select').each(function() {
                $(this).removeClass('select2-hidden-accessible');
                $(this).val('');
                $(this).find('option').prop('selected', false);
                $(this).find('option').first().prop('selected', true);
            });
            newBlock.find('.select2-container').remove();
            
            newBlock.find('input[type="checkbox"]').prop('checked', false);
            newBlock.find('.owner-quantity-hidden-input').val('');
            newBlock.find('.owner-buying-price-hidden-input').val('');
            newBlock.find('.owner-selling-price-hidden-input').val('');
            newBlock.find('.owner-price-warning').hide();
            newBlock.find('.owner-selling-price-display-input').removeClass('is-invalid');
            
            newBlock.find('.owner-existing-product-group').show();
            newBlock.find('.owner-new-product-group').hide();
            newBlock.find('.owner-item-id-select').prop('required', true);
            newBlock.find('.owner-new-item-name-input').prop('required', false);
            
            newBlock.find('.btn-remove-owner-product').removeClass('d-none');
            
            $('#ownerStockProductsContainer').append(newBlock);
            
            // Initialize Select2 on the new block selects
            newBlock.find('select').each(function() {
                if ($(this).find('option').length > 8 && window.initSearchableSelect) {
                    window.initSearchableSelect(this);
                }
            });
            
            updateOwnerRemoveButtons();
        });

        $('#addOwnerStockModal').on('click', '.btn-remove-owner-product', function() {
            $(this).closest('.product-block-owner').remove();
            reindexOwnerBlocks();
            updateOwnerRemoveButtons();
            validateAllOwnerPrices();
        });

        function reindexOwnerBlocks() {
            ownerProductIndex = 0;
            $('#ownerStockProductsContainer .product-block-owner').each(function() {
                const block = $(this);
                block.attr('data-index', ownerProductIndex);
                block.find('.product-title').text('Product #' + (ownerProductIndex + 1));
                
                block.find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/products\[\d+\]/, 'products[' + ownerProductIndex + ']');
                        $(this).attr('name', newName);
                    }
                    
                    const id = $(this).attr('id');
                    if (id) {
                        const newId = id.replace(/_\d+$/, '_' + ownerProductIndex);
                        $(this).attr('id', newId);
                        block.find('label[for="' + id + '"]').attr('for', newId);
                    }
                });
                ownerProductIndex++;
            });
            ownerProductIndex--;
        }

        function updateOwnerRemoveButtons() {
            const count = $('#ownerStockProductsContainer .product-block-owner').length;
            if (count > 1) {
                $('#ownerStockProductsContainer .product-block-owner .btn-remove-owner-product').removeClass('d-none');
            } else {
                $('#ownerStockProductsContainer .product-block-owner .btn-remove-owner-product').addClass('d-none');
            }
        }

        $('#addOwnerStockModal').on('hidden.bs.modal', function() {
            $('#ownerStockProductsContainer .product-block-owner').slice(1).remove();
            
            const block = $('#ownerStockProductsContainer .product-block-owner').first();
            block.attr('data-index', 0);
            block.find('.product-title').text('Product #1');
            block.find('input[type="text"], input[type="number"]').val('');
            
            // Destroy Select2, reset select value, clean up layout before re-initializing
            block.find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
                $(this).removeClass('select2-hidden-accessible');
                $(this).val('');
                $(this).find('option').prop('selected', false);
                $(this).find('option').first().prop('selected', true);
            });
            block.find('.select2-container').remove();
            
            block.find('input[type="checkbox"]').prop('checked', false);
            block.find('.owner-quantity-hidden-input').val('');
            block.find('.owner-buying-price-hidden-input').val('');
            block.find('.owner-selling-price-hidden-input').val('');
            block.find('.owner-price-warning').hide();
            block.find('.owner-selling-price-display-input').removeClass('is-invalid');
            block.find('.owner-existing-product-group').show();
            block.find('.owner-new-product-group').hide();
            block.find('.owner-item-id-select').prop('required', true);
            block.find('.owner-new-item-name-input').prop('required', false);
            
            $('#owner_date_received').val('{{ date("Y-m-d") }}');
            
            // Re-initialize Select2 on first row
            block.find('select').each(function() {
                if ($(this).find('option').length > 8 && window.initSearchableSelect) {
                    window.initSearchableSelect(this);
                }
            });
            
            ownerProductIndex = 0;
            updateOwnerRemoveButtons();
            validateAllOwnerPrices();
        });
        @endif

        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        $('#bulkEnableBtn').on('click', () => sendBulkUpdate(true));
        $('#bulkDisableBtn').on('click', () => sendBulkUpdate(false));
        @endif
        $('#bulkDeleteBtn').on('click', sendBulkDelete);

        $(document).on('click', '.confirm-delete-btn', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            Swal.fire({
                title: 'Delete Stock Batch?',
                text: "Are you sure you want to delete this stock batch? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush