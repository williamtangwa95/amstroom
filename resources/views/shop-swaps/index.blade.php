@extends('layouts.app')
@section('title', 'Product Swaps — Inter-Shop Exchange')
@section('page-title', 'Product Swaps')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Stock &amp; Warehouse</a></li>
<li class="breadcrumb-item active">Product Swaps</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       PRODUCT SWAPS — EXECUTIVE & ZERO-SCROLL MOBILE
       ══════════════════════════════════════════════ */
    .sw-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .sw-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .sw-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .sw-role-pill {
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
    .sw-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .sw-select-wrap {
        display: inline-flex;
        align-items: center;
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 9px;
        padding: 0 .2rem 0 .6rem;
        height: 34px;
    }
    .sw-select-wrap i {
        color: #64748b;
        font-size: .8rem;
        margin-right: .35rem;
    }
    .sw-select-wrap select {
        border: none;
        background: transparent;
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
        padding: .2rem 1.65rem .2rem .1rem;
        cursor: pointer;
        box-shadow: none !important;
    }
    .sw-select-wrap select:focus {
        outline: none;
    }

    /* ── Executive KPI Grid (Matches /stock-reorder, /diary, /units) ── */
    .sw-kpi-grid {
        display: grid;
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .sw-kpi-grid-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .sw-kpi-grid-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .sw-kpi-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .sw-kpi-card {
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
        height: 100%;
    }
    .sw-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .sw-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .sw-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .sw-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .sw-kpi-label {
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
    .sw-kpi-val {
        font-size: clamp(1.05rem, 1.4vw, 1.3rem);
        font-weight: 800;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }
    .sw-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Main Table Card & Toolbar ── */
    .sw-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .sw-toolbar {
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #fff;
    }
    .sw-pills {
        display: inline-flex;
        flex-wrap: wrap;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .sw-pill-link {
        text-decoration: none;
        color: #475569;
        font-size: .74rem;
        font-weight: 600;
        padding: .3rem .65rem;
        border-radius: 7px;
        transition: all .15s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
    .sw-pill-link:hover {
        color: #0f172a;
    }
    .sw-pill-link.active {
        background: #fff;
        color: #0077b6;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }
    .sw-pill-count {
        font-size: .68rem;
        font-weight: 700;
        opacity: .75;
    }

    .sw-search-form {
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .sw-search-wrap {
        position: relative;
        width: 210px;
    }
    .sw-search-wrap i {
        position: absolute;
        left: .65rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: .78rem;
        pointer-events: none;
    }
    .sw-search-input {
        width: 100%;
        height: 32px;
        padding: .25rem .65rem .25rem 1.9rem;
        font-size: .78rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: var(--text-primary, #0f172a);
        transition: border-color .15s, background .15s;
    }
    .sw-search-input:focus {
        outline: none;
        background: #fff;
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }

    /* ── Clean Desktop Table ── */
    .sw-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }
    .sw-table thead th {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8fafc;
        padding: .7rem .95rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .sw-table tbody td {
        padding: .8rem .95rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: .82rem;
    }
    .sw-table tbody tr:last-child td {
        border-bottom: none;
    }
    .sw-table tbody tr:hover {
        background: #f8fafc;
    }

    .sw-num-link {
        font-weight: 700;
        color: #0077b6;
        text-decoration: none;
        font-size: .83rem;
        white-space: nowrap;
    }
    .sw-num-link:hover {
        text-decoration: underline;
    }
    .sw-date-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .12rem;
    }

    .sw-item-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .35rem;
    }
    .sw-item-name {
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        font-size: .83rem;
        line-height: 1.25;
    }
    .sw-qty-chip {
        display: inline-flex;
        align-items: center;
        padding: .1rem .42rem;
        border-radius: 5px;
        background: #f1f5f9;
        color: #334155;
        font-size: .68rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .sw-shop-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .28rem;
        margin-top: .22rem;
        font-size: .71rem;
        color: #64748b;
    }

    .sw-badge {
        display: inline-flex;
        align-items: center;
        gap: .26rem;
        padding: .24rem .58rem;
        border-radius: 50px;
        font-size: .71rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .sw-badge-action  { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .sw-badge-warning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .sw-badge-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .sw-badge-danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .sw-badge-muted   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .sw-btn-review {
        background: linear-gradient(135deg, #0077b6, #005691);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: .3rem .72rem;
        font-size: .74rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        white-space: nowrap;
    }
    .sw-btn-review:hover {
        background: linear-gradient(135deg, #00669e, #004473);
        color: #fff;
    }
    .sw-btn-view {
        background: #fff;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: .28rem .68rem;
        font-size: .74rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        white-space: nowrap;
    }
    .sw-btn-view:hover {
        border-color: #0077b6;
        color: #0077b6;
        background: #f0f9ff;
    }

    .sw-pagination {
        padding: .75rem 1.15rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }
    .sw-mobile-label {
        display: none;
    }

    /* ══════════════════════════════════════════════
       RESPONSIVE BREAKPOINTS (ZERO HORIZONTAL SCROLL)
       ══════════════════════════════════════════════ */
    @media (max-width: 991.98px) {
        .sw-kpi-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
        .sw-kpi-grid-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
        .sw-kpi-grid-3 .sw-kpi-first {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 767.98px) {
        .sw-controls {
            width: 100%;
        }
        .sw-controls form,
        .sw-select-wrap,
        .sw-select-wrap select,
        .sw-controls .btn {
            width: 100%;
        }
        .sw-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .sw-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .sw-toolbar {
            padding: .75rem .9rem;
        }
        .sw-pills {
            width: 100%;
            overflow-x: auto;
            flex-wrap: nowrap;
        }
        .sw-pill-link {
            flex: 1 0 auto;
            justify-content: center;
            padding: .3rem .5rem;
        }
        .sw-search-form {
            width: 100%;
        }
        .sw-search-wrap {
            flex: 1;
            width: auto;
        }

        /* Convert rows into zero-scroll exchange cards */
        .sw-table thead {
            display: none;
        }
        .sw-table,
        .sw-table tbody {
            display: block;
            width: 100%;
        }
        .sw-table tbody tr.sw-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
            padding: .9rem;
            border-bottom: 1px solid #e2e8f0;
            align-items: start;
        }
        .sw-table tbody tr.sw-row:last-child {
            border-bottom: none;
        }
        .sw-table tbody tr.sw-row td {
            display: block;
            padding: 0;
            border: none;
            text-align: left !important;
        }
        .sw-table tbody tr.sw-row td.sw-col-num {
            grid-column: 1 / 2;
            grid-row: 1 / 2;
        }
        .sw-table tbody tr.sw-row td.sw-col-status {
            grid-column: 2 / 3;
            grid-row: 1 / 2;
            text-align: right !important;
        }
        .sw-table tbody tr.sw-row td.sw-col-src {
            grid-column: 1 / 2;
            grid-row: 2 / 3;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 9px;
            padding: .5rem .6rem;
        }
        .sw-table tbody tr.sw-row td.sw-col-dst {
            grid-column: 2 / 3;
            grid-row: 2 / 3;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 9px;
            padding: .5rem .6rem;
        }
        .sw-table tbody tr.sw-row td.sw-col-reason {
            grid-column: 1 / 2;
            grid-row: 3 / 4;
            align-self: center;
        }
        .sw-table tbody tr.sw-row td.sw-col-action {
            grid-column: 2 / 3;
            grid-row: 3 / 4;
            text-align: right !important;
            align-self: center;
        }
        .sw-mobile-label {
            display: block !important;
            font-size: .63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #94a3b8;
            margin-bottom: .2rem;
        }
        .sw-table tbody tr.sw-empty-row,
        .sw-table tbody tr.sw-empty-row td {
            display: block;
            width: 100%;
        }
    }
    @media (max-width: 440px) {
        .sw-table tbody tr.sw-row td.sw-col-src {
            grid-column: 1 / 3;
            grid-row: 2 / 3;
        }
        .sw-table tbody tr.sw-row td.sw-col-dst {
            grid-column: 1 / 3;
            grid-row: 3 / 4;
        }
        .sw-table tbody tr.sw-row td.sw-col-reason {
            grid-column: 1 / 2;
            grid-row: 4 / 5;
        }
        .sw-table tbody tr.sw-row td.sw-col-action {
            grid-column: 2 / 3;
            grid-row: 4 / 5;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header & Controls --}}
<div class="sw-header">
    <div class="sw-title-row">
        <h5 class="sw-title">
            <i class="bi bi-arrow-left-right text-info me-1"></i>Product Swaps
        </h5>
        <span class="sw-role-pill">
            @if(auth()->user()->isOwner())
                <i class="bi bi-check2-circle"></i>Completed Swaps
            @else
                <i class="bi bi-shop"></i>{{ auth()->user()->shop?->shop_name ?? 'Branch Exchange' }}
            @endif
        </span>
    </div>

    <div class="sw-controls">
        @if(auth()->user()->isOwner() && $shops->count() > 0)
        <form method="GET" action="{{ route('shop-swaps.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
            <div class="sw-select-wrap">
                <i class="bi bi-shop"></i>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter by Branch">
                    <option value="">All Branches</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        @endif

        @if(auth()->user()->isShopAdmin())
        <a href="{{ route('shop-swaps.create') }}" class="btn btn-sm btn-accent shadow-sm" style="height:34px;border-radius:9px;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-circle"></i> New Swap
        </a>
        @endif
    </div>
</div>

{{-- Executive KPI Cards --}}
@if(auth()->user()->isOwner())
<div class="sw-kpi-grid sw-kpi-grid-3">
    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'all'])) }}" class="sw-kpi-link sw-kpi-first">
        <div class="sw-kpi-card {{ $tab === 'all' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">Total Done</p>
                <div class="sw-kpi-val" style="color:#0077b6;">{{ number_format($counts['all']) }}</div>
                <div class="sw-kpi-sub">Completed exchanges</div>
            </div>
        </div>
    </a>

    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'confirmed'])) }}" class="sw-kpi-link">
        <div class="sw-kpi-card {{ $tab === 'confirmed' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">Confirmed</p>
                <div class="sw-kpi-val" style="color:#059669;">{{ number_format($counts['confirmed']) }}</div>
                <div class="sw-kpi-sub">Active in branches</div>
            </div>
        </div>
    </a>

    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'reverted'])) }}" class="sw-kpi-link">
        <div class="sw-kpi-card {{ $tab === 'reverted' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(100,116,139,.12);color:#475569;">
                <i class="bi bi-arrow-counterclockwise"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">Reverted</p>
                <div class="sw-kpi-val" style="color:#475569;">{{ number_format($counts['reverted']) }}</div>
                <div class="sw-kpi-sub">Reversed by Owner</div>
            </div>
        </div>
    </a>
</div>
@else
<div class="sw-kpi-grid sw-kpi-grid-4">
    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'action_required'])) }}" class="sw-kpi-link">
        <div class="sw-kpi-card {{ $tab === 'action_required' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">Needs Approval</p>
                <div class="sw-kpi-val" style="color:#d97706;">{{ number_format($counts['action_required']) }}</div>
                <div class="sw-kpi-sub">Incoming requests</div>
            </div>
        </div>
    </a>

    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'pending_destination'])) }}" class="sw-kpi-link">
        <div class="sw-kpi-card {{ $tab === 'pending_destination' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">Pending</p>
                <div class="sw-kpi-val" style="color:#0077b6;">{{ number_format($counts['pending_destination']) }}</div>
                <div class="sw-kpi-sub">Awaiting confirmation</div>
            </div>
        </div>
    </a>

    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'confirmed'])) }}" class="sw-kpi-link">
        <div class="sw-kpi-card {{ $tab === 'confirmed' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">Confirmed</p>
                <div class="sw-kpi-val" style="color:#059669;">{{ number_format($counts['confirmed']) }}</div>
                <div class="sw-kpi-sub">Stock exchanged</div>
            </div>
        </div>
    </a>

    <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'history'])) }}" class="sw-kpi-link">
        <div class="sw-kpi-card {{ $tab === 'history' ? 'is-active' : '' }}">
            <div class="sw-kpi-icon" style="background:rgba(100,116,139,.12);color:#475569;">
                <i class="bi bi-archive"></i>
            </div>
            <div class="sw-kpi-body">
                <p class="sw-kpi-label">History</p>
                <div class="sw-kpi-val" style="color:#475569;">{{ number_format($counts['history']) }}</div>
                <div class="sw-kpi-sub">Cancelled / Reverted</div>
            </div>
        </div>
    </a>
</div>
@endif

{{-- Main Swaps Card --}}
<div class="sw-card">
    <div class="sw-toolbar">
        <div class="sw-pills">
            @if(auth()->user()->isOwner())
                <a class="sw-pill-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'all'])) }}">
                    All Done <span class="sw-pill-count">({{ $counts['all'] }})</span>
                </a>
                <a class="sw-pill-link {{ $tab === 'confirmed' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'confirmed'])) }}">
                    Confirmed <span class="sw-pill-count">({{ $counts['confirmed'] }})</span>
                </a>
                <a class="sw-pill-link {{ $tab === 'reverted' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'reverted'])) }}">
                    Reverted <span class="sw-pill-count">({{ $counts['reverted'] }})</span>
                </a>
            @else
                <a class="sw-pill-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'all'])) }}">
                    All <span class="sw-pill-count">({{ $counts['all'] }})</span>
                </a>
                <a class="sw-pill-link {{ $tab === 'action_required' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'action_required'])) }}">
                    Needs Approval <span class="sw-pill-count">({{ $counts['action_required'] }})</span>
                </a>
                <a class="sw-pill-link {{ $tab === 'pending_destination' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'pending_destination'])) }}">
                    Pending <span class="sw-pill-count">({{ $counts['pending_destination'] }})</span>
                </a>
                <a class="sw-pill-link {{ $tab === 'confirmed' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'confirmed'])) }}">
                    Confirmed <span class="sw-pill-count">({{ $counts['confirmed'] }})</span>
                </a>
                <a class="sw-pill-link {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'history'])) }}">
                    History <span class="sw-pill-count">({{ $counts['history'] }})</span>
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('shop-swaps.index') }}" class="sw-search-form">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if(request('shop_id'))<input type="hidden" name="shop_id" value="{{ request('shop_id') }}">@endif
            <div class="sw-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="sw-search-input" placeholder="Search swap or item..." value="{{ request('search') }}">
            </div>
            @if(request('search'))
            <a href="{{ route('shop-swaps.index', ['tab' => $tab, 'shop_id' => request('shop_id')]) }}" class="btn btn-sm btn-outline-custom" style="height:32px;border-radius:8px;" title="Clear Search">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </form>
    </div>

    <div>
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width:130px;">Swap #</th>
                    <th>Source Gives</th>
                    <th>Destination Gives</th>
                    <th style="width:160px;">Reason</th>
                    <th class="text-center" style="width:135px;">Status</th>
                    <th class="text-end" style="width:95px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($swaps as $swap)
                @php
                    $isMyDest = auth()->user()->shop_id == $swap->destination_shop_id;
                    $needsMyAction = $swap->status === 'pending_destination' && $isMyDest;
                @endphp
                <tr class="sw-row">
                    {{-- 1. Swap # --}}
                    <td class="sw-col-num">
                        <a href="{{ route('shop-swaps.show', $swap) }}" class="sw-num-link">
                            {{ $swap->swap_number }}
                        </a>
                        <div class="sw-date-sub">{{ $swap->created_at->format('M d, Y') }}</div>
                    </td>

                    {{-- 2. Source Shop + Offered Item --}}
                    <td class="sw-col-src">
                        <span class="sw-mobile-label">Source Gives</span>
                        <div class="sw-item-line">
                            <span class="sw-item-name">{{ $swap->sourceItem->item_name }}</span>
                            <span class="sw-qty-chip">{{ $swap->source_quantity }} {{ $swap->sourceItem->unit_symbol }}</span>
                        </div>
                        <div class="sw-shop-meta">
                            <span><i class="bi bi-shop text-primary me-1"></i>{{ $swap->sourceShop->shop_name }}</span>
                            <span>&middot; {{ $swap->initiator->name ?? 'Admin' }}</span>
                        </div>
                    </td>

                    {{-- 3. Destination Shop + Requested Item --}}
                    <td class="sw-col-dst">
                        <span class="sw-mobile-label">Destination Gives</span>
                        <div class="sw-item-line">
                            <span class="sw-item-name">{{ $swap->destinationItem->item_name }}</span>
                            <span class="sw-qty-chip">{{ $swap->destination_quantity }} {{ $swap->destinationItem->unit_symbol }}</span>
                        </div>
                        <div class="sw-shop-meta">
                            <span><i class="bi bi-shop text-info me-1"></i>{{ $swap->destinationShop->shop_name }}</span>
                            @if($swap->destinationApprover)
                            <span>&middot; {{ $swap->destinationApprover->name }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- 4. Reason --}}
                    <td class="sw-col-reason">
                        <div class="fw-600" style="font-size:.77rem;color:var(--text-primary);">{{ $swap->reason_label }}</div>
                        @if($swap->notes)
                        <div class="text-muted text-truncate" style="max-width:155px;font-size:.7rem;" title="{{ $swap->notes }}">
                            {{ $swap->notes }}
                        </div>
                        @endif
                    </td>

                    {{-- 5. Status --}}
                    <td class="text-center sw-col-status">
                        @if($needsMyAction)
                            <span class="sw-badge sw-badge-action">
                                <i class="bi bi-bell-fill"></i> Needs Approval
                            </span>
                        @elseif($swap->status === 'pending_destination')
                            <span class="sw-badge sw-badge-warning">
                                <i class="bi bi-clock-history"></i> Pending
                            </span>
                        @elseif($swap->status === 'confirmed')
                            <span class="sw-badge sw-badge-success">
                                <i class="bi bi-check-circle-fill"></i> Confirmed
                            </span>
                        @elseif($swap->status === 'cancelled')
                            <span class="sw-badge sw-badge-danger">
                                <i class="bi bi-x-circle"></i> Cancelled
                            </span>
                        @elseif($swap->status === 'reverted')
                            <span class="sw-badge sw-badge-muted">
                                <i class="bi bi-arrow-counterclockwise"></i> Reverted
                            </span>
                        @endif
                    </td>

                    {{-- 6. Action --}}
                    <td class="text-end sw-col-action">
                        @if($needsMyAction)
                        <a href="{{ route('shop-swaps.show', $swap) }}" class="sw-btn-review">
                            <i class="bi bi-check2-circle"></i> Review
                        </a>
                        @else
                        <a href="{{ route('shop-swaps.show', $swap) }}" class="sw-btn-view">
                            <i class="bi bi-eye"></i> View
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="sw-empty-row">
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-right fs-2 d-block mb-2 opacity-50"></i>
                        <div class="fw-700 text-dark mb-1" style="font-size:.92rem;">No Product Swaps Found</div>
                        @if(auth()->user()->isOwner())
                        <small>Completed swaps confirmed by branch Shop Admins appear here once done.</small>
                        @else
                        <small>No swaps match your current filter.</small>
                        <div class="mt-3">
                            <a href="{{ route('shop-swaps.create') }}" class="btn btn-sm btn-accent">
                                <i class="bi bi-plus-circle me-1"></i> New Swap
                            </a>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($swaps->hasPages())
    <div class="sw-pagination">
        {{ $swaps->links() }}
    </div>
    @endif
</div>
@endsection
