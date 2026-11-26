@extends('layouts.app')
@section('title', 'Stock Reorder — ' . $currentShop->shop_name)
@section('page-title', 'Stock Reorder')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Stock &amp; Warehouse</a></li>
<li class="breadcrumb-item active">Stock Reorder</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       STOCK REORDER — CLEAN EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .ro-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .ro-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .ro-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .ro-branch-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 50px;
        background: rgba(0, 119, 182, .08);
        color: #0077b6;
        font-size: .72rem;
        font-weight: 600;
    }
    .ro-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .ro-select-wrap {
        display: inline-flex;
        align-items: center;
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 9px;
        padding: 0 .2rem 0 .6rem;
        height: 34px;
    }
    .ro-select-wrap i {
        color: #64748b;
        font-size: .8rem;
        margin-right: .35rem;
    }
    .ro-select-wrap select {
        border: none;
        background: transparent;
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
        padding: .2rem 1.65rem .2rem .1rem;
        cursor: pointer;
        box-shadow: none !important;
    }
    .ro-select-wrap select:focus {
        outline: none;
    }

    /* ── Executive KPI Grid (Matches /diary & /units) ── */
    .ro-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .ro-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .95rem 1.05rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
    }
    .ro-kpi-card.is-clickable {
        cursor: pointer;
    }
    .ro-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .ro-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .ro-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .ro-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .ro-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ro-kpi-val {
        font-size: clamp(1.05rem, 1.4vw, 1.3rem);
        font-weight: 800;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }
    .ro-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Main Card & Toolbar ── */
    .ro-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .ro-toolbar {
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #fff;
    }
    .ro-select-summary {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        font-size: .82rem;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
    }
    .ro-count-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        padding: 0 .45rem;
        border-radius: 50px;
        background: #0077b6;
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .ro-toolbar-right {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .ro-pills {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .ro-pill-btn {
        border: none;
        background: transparent;
        color: #475569;
        font-size: .74rem;
        font-weight: 600;
        padding: .28rem .62rem;
        border-radius: 7px;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }
    .ro-pill-btn:hover {
        color: #0f172a;
    }
    .ro-pill-btn.active {
        background: #fff;
        color: #0077b6;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }

    #reorderTable tbody tr.is-custom-row {
        background: #f0f9ff;
    }

    .ro-prod-name {
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        font-size: .85rem;
        line-height: 1.25;
    }
    .ro-prod-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .32rem;
        margin-top: .22rem;
        font-size: .7rem;
        color: #64748b;
    }
    .ro-cat-chip {
        padding: .1rem .45rem;
        border-radius: 5px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        font-size: .68rem;
    }
    .ro-transit-chip {
        display: inline-flex;
        align-items: center;
        gap: .22rem;
        padding: .1rem .42rem;
        border-radius: 5px;
        font-size: .66rem;
        font-weight: 700;
    }

    .ro-badge {
        display: inline-flex;
        align-items: center;
        gap: .26rem;
        padding: .22rem .55rem;
        border-radius: 50px;
        font-size: .73rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .ro-badge-danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .ro-badge-warning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .ro-badge-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .ro-badge-info    { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
    .ro-badge-muted   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .ro-demand-cell {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem;
        font-variant-numeric: tabular-nums;
    }
    .ro-demand-main {
        font-weight: 700;
        font-size: .8rem;
        color: var(--text-primary, #0f172a);
    }
    .ro-demand-rate {
        font-size: .71rem;
        color: #64748b;
    }
    .ro-cover-chip {
        padding: .12rem .42rem;
        border-radius: 5px;
        font-size: .68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .ro-qty-group {
        display: inline-flex;
        align-items: center;
        width: 108px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .ro-qty-group input {
        width: 100%;
        border: none;
        text-align: center;
        font-weight: 700;
        font-size: .82rem;
        padding: .28rem .35rem;
        color: var(--text-primary, #0f172a);
        font-variant-numeric: tabular-nums;
    }
    .ro-qty-group input:focus {
        outline: none;
    }
    .ro-qty-unit {
        background: #f8fafc;
        border-left: 1px solid #e2e8f0;
        color: #64748b;
        font-size: .7rem;
        font-weight: 600;
        padding: .3rem .45rem;
        white-space: nowrap;
    }

    /* ── Footer Bar ── */
    .ro-footer {
        padding: .9rem 1.15rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .ro-footer-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    /* ══════════════════════════════════════════════
       RESPONSIVE BREAKPOINTS (ZERO HORIZONTAL SCROLL)
       ══════════════════════════════════════════════ */
    @media (max-width: 991.98px) {
        .ro-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }

    @media (max-width: 767.98px) {
        .ro-header {
            gap: .65rem;
        }
        .ro-controls {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
        }
        .ro-controls .ro-select-shop {
            grid-column: 1 / -1;
        }
        .ro-select-wrap {
            width: 100%;
        }
        .ro-select-wrap select {
            width: 100%;
        }
        .ro-controls .ro-add-btn {
            grid-column: 1 / -1;
            width: 100%;
        }
        .ro-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .ro-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .ro-toolbar {
            padding: .75rem .9rem;
        }
        .ro-toolbar-right {
            width: 100%;
        }
        .ro-pills {
            width: 100%;
            justify-content: space-between;
        }
        .ro-pill-btn {
            flex: 1;
            text-align: center;
            padding: .3rem .35rem;
        }
        .ro-footer {
            padding: .85rem .9rem;
        }
        .ro-footer-actions {
            width: 100%;
        }
        .ro-footer-actions .btn {
            flex: 1 1 100%;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header & Filters --}}
<div class="ro-header">
    <div>
        <div class="ro-title-row">
            <h5 class="ro-title">
                <i class="bi bi-magic text-warning me-1"></i>Stock Reorder
            </h5>
            <span class="ro-branch-pill">
                <i class="bi bi-shop"></i>{{ $currentShop->shop_name }}
            </span>
        </div>
    </div>

    <form method="GET" action="{{ route('reorder.index') }}" id="filterForm" class="ro-controls">
        @if(auth()->user()->isOwner())
        <div class="ro-select-wrap ro-select-shop">
            <i class="bi bi-shop"></i>
            <select name="shop_id" class="form-select form-select-sm auto-submit" aria-label="Select Shop">
                @foreach($shops as $s)
                <option value="{{ $s->id }}" {{ $s->id == $shopId ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                @endforeach
            </select>
        </div>
        @else
        <input type="hidden" name="shop_id" value="{{ $shopId }}">
        @endif

        <div class="ro-select-wrap">
            <i class="bi bi-speedometer2"></i>
            <select name="velocity_days" class="form-select form-select-sm auto-submit" title="Sales Rate Window">
                <option value="14" {{ $velocityDays == 14 ? 'selected' : '' }}>14d Rate</option>
                <option value="30" {{ $velocityDays == 30 ? 'selected' : '' }}>30d Rate</option>
                <option value="60" {{ $velocityDays == 60 ? 'selected' : '' }}>60d Rate</option>
                <option value="90" {{ $velocityDays == 90 ? 'selected' : '' }}>90d Rate</option>
            </select>
        </div>

        <div class="ro-select-wrap">
            <i class="bi bi-shield-check"></i>
            <select name="target_cover_days" class="form-select form-select-sm auto-submit" title="Target Cover Buffer">
                <option value="7" {{ $targetCoverDays == 7 ? 'selected' : '' }}>7d Cover</option>
                <option value="14" {{ $targetCoverDays == 14 ? 'selected' : '' }}>14d Cover</option>
                <option value="21" {{ $targetCoverDays == 21 ? 'selected' : '' }}>21d Cover</option>
                <option value="30" {{ $targetCoverDays == 30 ? 'selected' : '' }}>30d Cover</option>
            </select>
        </div>

        <button type="button" class="btn btn-sm btn-outline-custom ro-add-btn" data-bs-toggle="modal" data-bs-target="#addExtraProductModal" style="height:34px;border-radius:9px;">
            <i class="bi bi-plus-circle me-1"></i> Add Item
        </button>
    </form>
</div>

@if($errors->any())
<div class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.83rem;">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Executive KPI Summary Cards --}}
<div class="ro-kpi-grid">
    <div class="ro-kpi-card is-clickable kpi-card" data-filter="out_of_stock" title="Filter out-of-stock items">
        <div class="ro-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <i class="bi bi-x-octagon"></i>
        </div>
        <div class="ro-kpi-body">
            <p class="ro-kpi-label">Out of Stock</p>
            <div class="ro-kpi-val" style="color:#dc2626;">{{ $kpis['out_of_stock'] }}</div>
            <div class="ro-kpi-sub">0 in shop</div>
        </div>
    </div>

    <div class="ro-kpi-card is-clickable kpi-card" data-filter="critical" title="Filter low stock items">
        <div class="ro-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="ro-kpi-body">
            <p class="ro-kpi-label">Low Stock</p>
            <div class="ro-kpi-val" style="color:#d97706;">{{ $kpis['critical'] }}</div>
            <div class="ro-kpi-sub">At or below alert</div>
        </div>
    </div>

    <div class="ro-kpi-card is-clickable kpi-card" data-filter="fast_movers" title="Filter fast moving items">
        <div class="ro-kpi-icon" style="background:rgba(124,58,237,.12);color:#7c3aed;">
            <i class="bi bi-lightning-charge"></i>
        </div>
        <div class="ro-kpi-body">
            <p class="ro-kpi-label">Fast Movers</p>
            <div class="ro-kpi-val" style="color:#7c3aed;">{{ $kpis['fast_movers_at_risk'] }}</div>
            <div class="ro-kpi-sub">High daily sales</div>
        </div>
    </div>

    <div class="ro-kpi-card">
        <div class="ro-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-building-check"></i>
        </div>
        <div class="ro-kpi-body">
            <p class="ro-kpi-label">In Warehouse</p>
            <div class="ro-kpi-val" style="color:#059669;">{{ $kpis['warehouse_fulfillable'] }}</div>
            <div class="ro-kpi-sub">Ready to dispatch</div>
        </div>
    </div>
</div>

{{-- Main Reorder Table Card --}}
<div class="ro-card mb-4">
    <div class="ro-toolbar">
        <div class="ro-select-summary">
            <input class="form-check-input m-0" type="checkbox" id="selectAllCheckbox" title="Select / Deselect All">
            <span class="ro-count-pill" id="selectedCountBadge">0</span>
            <span>selected</span>
            <span class="text-muted fw-normal">&middot; <strong id="selectedUnitsBadge" style="color:#0077b6;">0</strong> units</span>
        </div>

        <div class="ro-toolbar-right">
            <div class="ro-pills" role="group">
                <button type="button" class="ro-pill-btn active filter-btn" data-filter="all">All ({{ $suggestions->count() }})</button>
                <button type="button" class="ro-pill-btn filter-btn" data-filter="out_of_stock">Out</button>
                <button type="button" class="ro-pill-btn filter-btn" data-filter="critical">Low</button>
                <button type="button" class="ro-pill-btn filter-btn" data-filter="high_risk">Risk</button>
            </div>
        </div>
    </div>

    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="reorderTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center no-sort" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Product</th>
                    <th data-priority="2" class="min-tablet-l">Shop Stock</th>
                    <th data-priority="3" class="min-tablet-l">Demand ({{ $velocityDays }}d)</th>
                    <th data-priority="3" class="min-tablet-l">Main Store</th>
                    <th data-priority="2" class="min-tablet-l no-sort text-center" style="width: 145px;">Order Qty</th>
                </tr>
            </thead>
            <tbody id="reorderTableBody">
                @foreach($suggestions as $item)
                @php
                    $isOut = $item->current_stock <= 0;
                    $isWhZero = $item->main_store_available <= 0;
                @endphp
                <tr class="reorder-row"
                    data-item-id="{{ $item->item_id }}"
                    data-urgency="{{ $item->urgency }}"
                    data-fast-mover="{{ $item->daily_sales_rate >= 0.5 ? '1' : '0' }}">

                    {{-- 1. Select --}}
                    <td class="text-center iter-col">
                        <input class="form-check-input item-checkbox m-0" type="checkbox"
                            data-item-id="{{ $item->item_id }}"
                            data-item-name="{{ $item->item_name }}"
                            data-wh-stock="{{ $item->main_store_available }}"
                            data-unit="{{ $item->unit_symbol }}">
                    </td>

                    {{-- 2. Product + Category + Active Transit/Request Chips --}}
                    <td class="primary-col">
                        <div class="ro-prod-name">{{ $item->item_name }}</div>
                        <div class="ro-prod-meta">
                            <span class="ro-cat-chip">{{ $item->category_name }}</span>
                            @if($item->brand)<span>{{ $item->brand }}</span>@endif
                            @if($item->model)<span>&middot; {{ $item->model }}</span>@endif
                            @if($item->pending_request_qty > 0)
                            <span class="ro-transit-chip" style="background:rgba(0,119,182,.1);color:#0077b6;" title="Pending Stock Request">
                                <i class="bi bi-clock"></i>{{ $item->pending_request_qty }} req
                            </span>
                            @endif
                            @if($item->incoming_transfer_qty > 0)
                            <span class="ro-transit-chip" style="background:#ede9fe;color:#7c3aed;" title="In Transit">
                                <i class="bi bi-truck"></i>{{ $item->incoming_transfer_qty }} transit
                            </span>
                            @endif
                        </div>
                    </td>

                    {{-- 3. Shop Stock --}}
                    <td data-order="{{ $item->current_stock }}">
                        @if($isOut)
                        <span class="ro-badge ro-badge-danger">
                            <i class="bi bi-x-circle"></i> 0 {{ $item->unit_symbol }}
                        </span>
                        @else
                        <span class="ro-badge ro-badge-warning">
                            {{ $item->current_stock }} {{ $item->unit_symbol }}
                        </span>
                        <span class="text-muted ms-1" style="font-size:.68rem;" title="Low stock alert threshold">min {{ $item->alert_threshold }}</span>
                        @endif
                    </td>

                    {{-- 4. Demand & Days Cover Combined --}}
                    <td data-order="{{ $item->total_sold }}">
                        <div class="ro-demand-cell">
                            <span class="ro-demand-main">{{ $item->total_sold }} sold</span>
                            <span class="ro-demand-rate">({{ number_format($item->daily_sales_rate, 1) }}/d)</span>
                            @if($item->days_remaining === 0)
                                <span class="ro-cover-chip" style="background:#dc2626;color:#fff;">0d left</span>
                            @elseif($item->days_remaining !== null && $item->days_remaining <= 3)
                                <span class="ro-cover-chip" style="background:#fef2f2;color:#dc2626;">{{ $item->days_remaining }}d left</span>
                            @elseif($item->days_remaining !== null && $item->days_remaining <= 7)
                                <span class="ro-cover-chip" style="background:#fffbeb;color:#d97706;">{{ $item->days_remaining }}d left</span>
                            @elseif($item->days_remaining !== null)
                                <span class="ro-cover-chip" style="background:#f0f9ff;color:#0284c7;">{{ $item->days_remaining }}d</span>
                            @endif
                        </div>
                    </td>

                    {{-- 5. Main Store --}}
                    <td data-order="{{ $item->main_store_available }}">
                        @if($isWhZero)
                        <span class="ro-badge ro-badge-danger" title="Out of stock in Main Warehouse">
                            <i class="bi bi-building-x"></i> 0 {{ $item->unit_symbol }}
                        </span>
                        @else
                        <span class="ro-badge ro-badge-success">
                            <i class="bi bi-building-check"></i> {{ $item->main_store_available }} {{ $item->unit_symbol }}
                        </span>
                        @endif
                    </td>

                    {{-- 6. Suggested Order Qty --}}
                    <td class="text-center">
                        <div>
                            <div class="ro-qty-group">
                                <input type="number" min="1"
                                    class="qty-input"
                                    value="{{ $item->suggested_reorder_qty }}"
                                    data-item-id="{{ $item->item_id }}"
                                    data-wh-stock="{{ $item->main_store_available }}">
                                <span class="ro-qty-unit">{{ $item->unit_symbol }}</span>
                            </div>
                            <div class="wh-warning text-danger mt-1 d-none" style="font-size:.66rem;font-weight:600;">
                                <i class="bi bi-exclamation-triangle"></i> Exceeds stock
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Bottom Action Bar --}}
    <div class="ro-footer">
        <button type="button" class="btn btn-sm btn-outline-custom" data-bs-toggle="modal" data-bs-target="#addExtraProductModal">
            <i class="bi bi-plus-circle me-1"></i> Add Item
        </button>

        <div class="ro-footer-actions">
            <button type="button" class="btn btn-accent btn-sm shadow-sm" id="btnOpenRequestModal" disabled>
                <i class="bi bi-cart-plus me-1"></i> Submit Stock Order to Main Store
            </button>

            @if(auth()->user()->isOwner())
            <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnOpenTransferModal" disabled>
                <i class="bi bi-truck me-1"></i> Direct Transfer to {{ $currentShop->shop_name }}
            </button>
            @endif
        </div>
    </div>
</div>

{{-- MODAL 1: Add Custom Item --}}
<div class="modal fade" id="addExtraProductModal" tabindex="-1" aria-labelledby="addExtraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700" id="addExtraModalLabel">
                    <i class="bi bi-plus-circle text-accent me-2"></i>Add Item to Order
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label small fw-700">Product *</label>
                    <select id="extraProductSelect" class="form-select form-select-sm">
                        <option value="">Select product...</option>
                        @foreach($catalogItems as $cItem)
                        @php $whAvail = (int) ($warehouseStockMap->get($cItem->id) ?? 0); @endphp
                        <option value="{{ $cItem->id }}"
                            data-name="{{ $cItem->item_name }}"
                            data-cat="{{ $cItem->category?->category_name ?? 'General' }}"
                            data-unit="{{ $cItem->unit_symbol }}"
                            data-wh-stock="{{ $whAvail }}">
                            {{ $cItem->item_name }} — Wh: {{ $whAvail }} {{ $cItem->unit_symbol }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-700">Quantity *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="extraProductQty" class="form-control form-control-sm text-center fw-700" value="10" min="1">
                            <span class="input-group-text bg-light text-muted" id="extraProductUnitSpan">pc</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-700 text-muted">Main Store</label>
                        <div class="form-control form-control-sm bg-light text-muted" id="extraProductWhDisplay">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-custom p-2">
                <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-accent" id="btnConfirmAddExtra">
                    <i class="bi bi-plus-lg me-1"></i> Add
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2: Confirm Stock Request --}}
<div class="modal fade" id="confirmRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700">
                    <i class="bi bi-cart-check text-accent me-2"></i>Confirm Stock Order
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reorder.request') }}" id="reorderRequestForm">
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shopId }}">
                <div class="modal-body p-3">
                    <div class="table-responsive mb-3" style="max-height: 280px;">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" style="width: 100px;">Qty</th>
                                    <th class="text-center" style="width: 110px;">Main Store</th>
                                </tr>
                            </thead>
                            <tbody id="requestSummaryBody"></tbody>
                        </table>
                    </div>

                    <div>
                        <label class="form-label small fw-700">Notes (Optional)</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional order note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-custom p-2">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">
                        <i class="bi bi-check-circle me-1"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(auth()->user()->isOwner())
{{-- MODAL 3: Confirm Direct Transfer (Owner Exclusive) --}}
<div class="modal fade" id="confirmTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700">
                    <i class="bi bi-truck text-primary me-2"></i>Direct Transfer &mdash; {{ $currentShop->shop_name }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reorder.transfer') }}" id="reorderTransferForm">
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shopId }}">
                <div class="modal-body p-3">
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" style="width: 100px;">Transfer</th>
                                    <th class="text-center" style="width: 110px;">Main Store</th>
                                    <th class="text-center" style="width: 85px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="transferSummaryBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-custom p-2">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSubmitTransferFinal">
                        <i class="bi bi-box-arrow-right me-1"></i> Dispatch Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
$(function() {
    let activeFilter = 'all';

    // Custom DataTables urgency / fast-mover filter
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'reorderTable') return true;
        if (activeFilter === 'all') return true;
        const rowNode = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!rowNode) return true;
        const $tr = $(rowNode);
        if (activeFilter === 'fast_movers') {
            return String($tr.attr('data-fast-mover')) === '1';
        }
        return String($tr.attr('data-urgency')) === activeFilter;
    });

    const reorderTable = $('#reorderTable').DataTable({
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[2, 'asc']],
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 5] }
        ],
        language: {
            searchPlaceholder: 'Search products...',
            emptyTable: '<div class="py-4 text-muted"><i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i><div class="fw-700 text-dark mb-1">Stock Levels Healthy</div><small>No urgent reorders needed for {{ e($currentShop->shop_name) }}.</small></div>'
        }
    });

    // Auto-submit filter changes
    $('.auto-submit').on('change', function() {
        $('#filterForm').submit();
    });

    function getAllRowNodes() {
        return $(reorderTable.rows().nodes());
    }

    function getFilteredRowNodes() {
        return $(reorderTable.rows({ search: 'applied' }).nodes());
    }

    function syncSelectAllState() {
        const $filtered = getFilteredRowNodes();
        const totalVisible = $filtered.find('.item-checkbox').length;
        const totalChecked = $filtered.find('.item-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', totalVisible > 0 && totalVisible === totalChecked);
    }

    // Update selection summary and button states across all DataTable pages
    function updateSelectionSummary() {
        let count = 0;
        let totalUnits = 0;

        getAllRowNodes().each(function() {
            const $row = $(this);
            const $cb = $row.find('.item-checkbox');
            if ($cb.is(':checked')) {
                count++;
                const qty = parseInt($row.find('.qty-input').val(), 10) || 0;
                totalUnits += qty;
            }
        });

        $('#selectedCountBadge').text(count);
        $('#selectedUnitsBadge').text(totalUnits.toLocaleString());

        const disabled = count === 0;
        $('#btnOpenRequestModal').prop('disabled', disabled);
        $('#btnOpenTransferModal').prop('disabled', disabled);
        syncSelectAllState();
    }

    // Select all toggle (applies to all currently filtered rows across pages)
    $('#selectAllCheckbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        getFilteredRowNodes().find('.item-checkbox').prop('checked', isChecked);
        updateSelectionSummary();
    });

    // Individual checkbox click (delegated)
    $(document).on('change', '#reorderTable .item-checkbox', function() {
        updateSelectionSummary();
    });

    // Qty input change (syncs desktop row & DataTables Responsive child drawer)
    $(document).on('input change', '#reorderTable .qty-input', function() {
        const $input = $(this);
        const itemId = $input.data('item-id');
        const qty = parseInt($input.val(), 10) || 0;
        const whStock = parseInt($input.data('wh-stock'), 10) || 0;

        // Sync matching row input if edited from mobile responsive child row
        getAllRowNodes().filter(`[data-item-id="${itemId}"]`).find('.qty-input').not($input).val($input.val());

        const $warning = $input.closest('td, li').find('.wh-warning');
        if (qty > whStock && whStock >= 0) {
            $warning.removeClass('d-none');
        } else {
            $warning.addClass('d-none');
        }

        updateSelectionSummary();
    });

    // Initial check on all row nodes
    getAllRowNodes().find('.qty-input').each(function() {
        const $input = $(this);
        const qty = parseInt($input.val(), 10) || 0;
        const whStock = parseInt($input.data('wh-stock'), 10) || 0;
        if (qty > whStock && whStock >= 0) {
            $input.closest('td').find('.wh-warning').removeClass('d-none');
        }
    });

    reorderTable.on('draw', function() {
        syncSelectAllState();
    });

    // Filter pills
    $('.filter-btn').on('click', function() {
        setActiveFilter($(this).data('filter'));
    });

    // KPI card click filter
    $('.kpi-card[data-filter]').on('click', function() {
        const filter = $(this).data('filter');
        if ($(this).hasClass('is-active')) {
            setActiveFilter('all');
        } else {
            setActiveFilter(filter);
        }
    });

    function setActiveFilter(filter) {
        activeFilter = filter;
        $('.filter-btn').removeClass('active');
        $(`.filter-btn[data-filter="${filter}"]`).addClass('active');
        $('.kpi-card[data-filter]').removeClass('is-active');
        if (filter !== 'all') {
            $(`.kpi-card[data-filter="${filter}"]`).addClass('is-active');
        }
        reorderTable.draw();
        updateSelectionSummary();
    }

    // Add Extra Product modal
    $('#extraProductSelect').on('change', function() {
        const opt = $(this).find('option:selected');
        const unit = opt.data('unit') || 'pc';
        const whStock = opt.data('wh-stock') !== undefined ? opt.data('wh-stock') : '—';

        $('#extraProductUnitSpan').text(unit);
        $('#extraProductWhDisplay').text(whStock !== '—' ? `${whStock} ${unit}` : '—');
    });

    $('#btnConfirmAddExtra').on('click', function() {
        const opt = $('#extraProductSelect').find('option:selected');
        const itemId = opt.val();
        if (!itemId) {
            alert('Please select a product.');
            return;
        }

        const itemName = opt.data('name');
        const catName = opt.data('cat') || 'General';
        const unit = opt.data('unit') || 'pc';
        const whStock = parseInt(opt.data('wh-stock'), 10) || 0;
        const qty = parseInt($('#extraProductQty').val(), 10) || 1;

        const $existingRow = getAllRowNodes().filter(`[data-item-id="${itemId}"]`);
        if ($existingRow.length > 0) {
            $existingRow.find('.item-checkbox').prop('checked', true);
            $existingRow.find('.qty-input').val(qty).trigger('change');
            setActiveFilter('all');
            $('#addExtraProductModal').modal('hide');
            updateSelectionSummary();
            return;
        }

        const safeName = $('<div>').text(itemName).html();
        const safeCat = $('<div>').text(catName).html();
        const safeUnit = $('<div>').text(unit).html();

        const newRowHtml = `
            <tr class="reorder-row is-custom-row" data-item-id="${itemId}" data-urgency="custom" data-fast-mover="0">
                <td class="text-center iter-col">
                    <input class="form-check-input item-checkbox m-0" type="checkbox" checked
                        data-item-id="${itemId}"
                        data-item-name="${safeName}"
                        data-wh-stock="${whStock}"
                        data-unit="${safeUnit}">
                </td>
                <td class="primary-col">
                    <div class="ro-prod-name">
                        ${safeName}
                        <span class="ro-transit-chip ms-1" style="background:rgba(0,119,182,.1);color:#0077b6;">Custom</span>
                    </div>
                    <div class="ro-prod-meta">
                        <span class="ro-cat-chip">${safeCat}</span>
                    </div>
                </td>
                <td data-order="999999">
                    <span class="ro-badge ro-badge-muted">Catalog</span>
                </td>
                <td data-order="0">
                    <span class="ro-demand-rate">Manual addition</span>
                </td>
                <td data-order="${whStock}">
                    <span class="ro-badge ${whStock > 0 ? 'ro-badge-success' : 'ro-badge-danger'}">
                        <i class="bi ${whStock > 0 ? 'bi-building-check' : 'bi-building-x'}"></i> ${whStock} ${safeUnit}
                    </span>
                </td>
                <td class="text-center">
                    <div>
                        <div class="ro-qty-group">
                            <input type="number" min="1"
                                class="qty-input"
                                value="${qty}"
                                data-item-id="${itemId}"
                                data-wh-stock="${whStock}">
                            <span class="ro-qty-unit">${safeUnit}</span>
                        </div>
                        <div class="wh-warning text-danger mt-1 ${qty > whStock ? '' : 'd-none'}" style="font-size:.66rem;font-weight:600;">
                            <i class="bi bi-exclamation-triangle"></i> Exceeds stock
                        </div>
                    </div>
                </td>
            </tr>
        `;

        reorderTable.row.add($(newRowHtml)[0]).draw(false);
        setActiveFilter('all');
        $('#addExtraProductModal').modal('hide');
        $('#extraProductSelect').val('');
        $('#extraProductWhDisplay').text('—');
        $('#extraProductQty').val(10);
        updateSelectionSummary();
    });

    // Populate Request Modal (reads checked items across all DataTable pages)
    $('#btnOpenRequestModal').on('click', function() {
        const tbody = $('#requestSummaryBody');
        tbody.empty();

        let index = 0;
        getAllRowNodes().each(function() {
            const $row = $(this);
            const $checkbox = $row.find('.item-checkbox');
            if (!$checkbox.is(':checked')) return;

            const itemId = $checkbox.data('item-id');
            const itemName = $checkbox.data('item-name');
            const unit = $checkbox.data('unit');
            const qty = parseInt($row.find('.qty-input').val(), 10) || 1;
            const whStock = parseInt($checkbox.data('wh-stock'), 10) || 0;

            tbody.append(`
                <tr>
                    <td>
                        <span class="fw-700">${$('<div>').text(itemName).html()}</span>
                        <input type="hidden" name="items[${index}][item_id]" value="${itemId}">
                    </td>
                    <td class="text-center fw-700">
                        ${qty} ${$('<div>').text(unit).html()}
                        <input type="hidden" name="items[${index}][quantity]" value="${qty}">
                    </td>
                    <td class="text-center">
                        <span class="badge ${whStock >= qty ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${whStock} ${$('<div>').text(unit).html()}
                        </span>
                    </td>
                </tr>
            `);
            index++;
        });

        $('#confirmRequestModal').modal('show');
    });

    // Populate Transfer Modal (Owner only, reads checked items across all DataTable pages)
    $('#btnOpenTransferModal').on('click', function() {
        const tbody = $('#transferSummaryBody');
        tbody.empty();

        let index = 0;
        let hasShortage = false;

        getAllRowNodes().each(function() {
            const $row = $(this);
            const $checkbox = $row.find('.item-checkbox');
            if (!$checkbox.is(':checked')) return;

            const itemId = $checkbox.data('item-id');
            const itemName = $checkbox.data('item-name');
            const unit = $checkbox.data('unit');
            const qty = parseInt($row.find('.qty-input').val(), 10) || 1;
            const whStock = parseInt($checkbox.data('wh-stock'), 10) || 0;
            const isShort = whStock < qty;

            if (isShort) {
                hasShortage = true;
            }

            tbody.append(`
                <tr class="${isShort ? 'table-danger' : ''}">
                    <td>
                        <span class="fw-700">${$('<div>').text(itemName).html()}</span>
                        <input type="hidden" name="items[${index}][item_id]" value="${itemId}">
                    </td>
                    <td class="text-center fw-700">
                        ${qty} ${$('<div>').text(unit).html()}
                        <input type="hidden" name="items[${index}][quantity]" value="${qty}">
                    </td>
                    <td class="text-center">
                        <span class="badge ${whStock >= qty ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${whStock} ${$('<div>').text(unit).html()}
                        </span>
                    </td>
                    <td class="text-center">
                        ${isShort ? '<span class="text-danger small fw-700"><i class="bi bi-x-circle me-1"></i>Short</span>' : '<span class="text-success small fw-700"><i class="bi bi-check-circle me-1"></i>OK</span>'}
                    </td>
                </tr>
            `);
            index++;
        });

        if (hasShortage) {
            $('#btnSubmitTransferFinal').prop('disabled', true).text('Warehouse Shortage');
        } else {
            $('#btnSubmitTransferFinal').prop('disabled', false).html('<i class="bi bi-box-arrow-right me-1"></i> Dispatch Transfer');
        }

        $('#confirmTransferModal').modal('show');
    });
});
</script>
@endpush
