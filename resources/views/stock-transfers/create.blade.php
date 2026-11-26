@extends('layouts.app')
@section('title', 'Assign Stock to Shops')
@section('page-title', 'Direct Stock Assignment')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
<li class="breadcrumb-item active">Assign Stock</li>
@endsection

@section('content')
<style>
    /* ══════════════════════════════════════════════
       DIRECT STOCK ASSIGNMENT — Executive UI
       ══════════════════════════════════════════════ */
    .st-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        border-radius: 14px;
        padding: 1.05rem 1.35rem;
        color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 86, 145, .18);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .st-hero-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: #ffffff;
        letter-spacing: -.01em;
    }
    .st-hero-sub {
        font-size: .77rem;
        color: rgba(255, 255, 255, .84);
        margin: .22rem 0 0;
        line-height: 1.35;
    }
    .st-hero-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .16rem .6rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .24);
        color: #ffffff;
        font-size: .65rem;
        font-weight: 600;
    }
    .st-hero-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .9rem;
        border-radius: 9px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .25);
        color: #ffffff;
        font-size: .78rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .15s ease;
    }
    .st-hero-btn:hover {
        background: rgba(255, 255, 255, .25);
        color: #ffffff;
        transform: translateY(-1px);
    }

    /* Section Cards */
    .st-step-card {
        background: #ffffff;
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }
    .st-step-head {
        padding: .9rem 1.2rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .st-step-num {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        background: #0077b6;
        color: #ffffff;
        font-size: .76rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .st-step-title {
        font-size: .94rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .st-step-desc {
        font-size: .74rem;
        color: #64748b;
        margin: .1rem 0 0;
    }
    .st-step-body {
        padding: 1.1rem 1.2rem;
    }

    /* Selectable Shop Cards Grid */
    .st-shop-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(225px, 1fr));
        gap: .75rem;
    }
    .st-shop-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 11px;
        padding: .75rem .85rem;
        background: #ffffff;
        display: flex;
        align-items: center;
        gap: .7rem;
        cursor: pointer;
        text-align: left;
        transition: all .16s ease;
        user-select: none;
        width: 100%;
    }
    .st-shop-card:hover {
        border-color: #7dd3fc;
        background: #f8fcff;
        transform: translateY(-1px);
    }
    .st-shop-card.is-selected {
        border-color: #0077b6;
        background: #f0f9ff;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .12);
    }
    .st-shop-card-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #f1f5f9;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        transition: all .15s ease;
    }
    .st-shop-card.is-selected .st-shop-card-icon {
        background: #0077b6;
        color: #ffffff;
    }
    .st-shop-card-info {
        flex: 1;
        min-width: 0;
    }
    .st-shop-card-name {
        font-size: .83rem;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
    }
    .st-shop-card-loc {
        font-size: .71rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: .1rem;
    }
    .st-shop-check {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        flex-shrink: 0;
        transition: all .15s ease;
    }
    .st-shop-card.is-selected .st-shop-check {
        border-color: #0077b6;
        background: #0077b6;
    }

    /* Mode Selector Cards */
    .st-mode-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .65rem;
        margin-bottom: 1.15rem;
    }
    .st-mode-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 11px;
        padding: .7rem .9rem;
        background: #f8fafc;
        display: flex;
        align-items: center;
        gap: .7rem;
        cursor: pointer;
        text-align: left;
        transition: all .15s ease;
        width: 100%;
    }
    .st-mode-card:hover {
        border-color: #94a3b8;
        background: #ffffff;
    }
    .st-mode-card.is-active {
        border-color: #0077b6;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .11);
    }
    .st-mode-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #e2e8f0;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .st-mode-card.is-active .st-mode-icon {
        background: rgba(0, 119, 182, .12);
        color: #0077b6;
    }
    .st-mode-title {
        font-size: .82rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .st-mode-sub {
        font-size: .71rem;
        color: #64748b;
        margin-top: .12rem;
        line-height: 1.25;
    }

    /* Per-Shop Dispatch Cards */
    .shop-dispatch-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-top: 3.5px solid #0077b6;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .shop-dispatch-head {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: .75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .shop-dispatch-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: rgba(0, 119, 182, .12);
        color: #0077b6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .shop-dispatch-body {
        padding: 1rem;
        background: #ffffff;
    }

    /* Product Row Cards */
    .item-row-card {
        background: #fafbfc;
        border: 1px solid #e2e8f0;
        border-left: 3.5px solid #0284c7 !important;
        border-radius: 10px;
        padding: .9rem 1rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .item-row-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .05);
    }

    /* Uniform 40px control height for Select2, Stock Pill & Stepper */
    .select2-container { width: 100% !important; }
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 40px !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        border-radius: 8px !important;
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
        color: #0f172a !important;
        padding: .2rem .65rem !important;
        font-size: .85rem !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: #0f172a !important;
        padding: 0 !important;
    }
    .select2-container--bootstrap-5 .select2-dropdown {
        border-radius: 9px !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .12) !important;
        z-index: 1060 !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background-color: #0077b6 !important;
        color: #ffffff !important;
    }
    .st-stock-pill {
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: .78rem;
        font-weight: 700;
        padding: 0 .6rem;
        white-space: nowrap;
    }
    .st-qty-group {
        height: 40px;
    }
    .st-qty-group .btn,
    .st-qty-group .form-control {
        height: 40px;
        border-color: #cbd5e1;
    }
    .st-qty-group .btn {
        width: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    /* Shared Multi-Shop Grid */
    .multi-shop-alloc-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: .8rem .9rem;
        margin-top: .85rem;
    }
    .shop-alloc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(215px, 1fr));
        gap: .65rem;
    }
    .shop-alloc-cell {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: .6rem .7rem;
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .shop-alloc-name {
        font-size: .77rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: .3rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .shop-alloc-loc {
        font-size: .66rem;
        color: #64748b;
        margin-top: -.2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Summary & Submit Bar */
    .st-summary-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, .06);
        margin-bottom: 1.5rem;
    }
    .st-summary-metrics {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
    }
    .st-metric {
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    .st-metric-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .st-metric-label {
        font-size: .64rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        line-height: 1.1;
    }
    .st-metric-val {
        font-size: .92rem;
        font-weight: 800;
        color: #0f172a;
        margin-top: .12rem;
    }

    @media (max-width: 767.98px) {
        .st-hero { padding: .95rem 1rem; }
        .st-hero-btn { width: 100%; justify-content: center; }
        .st-step-head, .st-step-body { padding: .85rem .95rem; }
        .st-shop-grid { grid-template-columns: 1fr; }
        .st-mode-grid { grid-template-columns: 1fr; }
        .shop-dispatch-body { padding: .8rem; }
        .item-row-card { padding: .8rem; }
        .shop-alloc-grid { grid-template-columns: 1fr; }
    }
</style>

@php
    $oldShopIds = old('shop_ids', old('shop_id') ? [old('shop_id')] : []);
    if (!is_array($oldShopIds)) {
        $oldShopIds = [$oldShopIds];
    }
    $oldShopIds = array_map('intval', $oldShopIds);
    $useCardGrid = $shops->count() <= 12;
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-xl-11">

        {{-- ── Executive Hero Header ── --}}
        <div class="st-hero">
            <div style="flex:1; min-width:220px;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="st-hero-title">Direct Stock Assignment</h1>
                    <span class="st-hero-pill"><i class="bi bi-building"></i> Main Warehouse</span>
                </div>
                <p class="st-hero-sub">Select one or more branch shops, assign the exact products &amp; quantities for each branch, and dispatch in one step.</p>
            </div>
            <div>
                <a href="{{ route('stock-transfers.index') }}" class="st-hero-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Transfers History</span>
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('stock-transfers.store') }}" id="assignForm">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

            {{-- ── STEP 1: Target Destination Shops ── --}}
            <div class="st-step-card">
                <div class="st-step-head">
                    <div class="d-flex align-items-center gap-3">
                        <span class="st-step-num">1</span>
                        <div>
                            <h6 class="st-step-title">Target Destination Shop(s) <span class="text-danger">*</span></h6>
                            <p class="st-step-desc">Click one or multiple branches below. Each selected shop receives its own stock transfer &amp; Shop Admin alert.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" id="selectedShopCountPill" style="font-size:.72rem;">0 selected</span>
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" id="btnSelectAllShops" style="font-size:.74rem; border-radius:7px;">
                            <i class="bi bi-check2-all me-1"></i>Select All ({{ $shops->count() }})
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" id="btnClearShops" style="font-size:.74rem; border-radius:7px;">
                            <i class="bi bi-x-lg me-1"></i>Clear
                        </button>
                    </div>
                </div>

                <div class="st-step-body">
                    @if($useCardGrid)
                        @if($shops->count() > 6)
                        <div class="mb-3" style="max-width: 320px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="shopCardSearch" class="form-control border-start-0" placeholder="Filter shops by name or location...">
                            </div>
                        </div>
                        @endif

                        {{-- Visual Branch Selection Cards --}}
                        <div class="st-shop-grid" id="shopCardsPicker">
                            @foreach($shops as $shop)
                                @php $isSel = in_array((int) $shop->id, $oldShopIds, true); @endphp
                                <button type="button"
                                        class="st-shop-card {{ $isSel ? 'is-selected' : '' }}"
                                        data-shop-id="{{ $shop->id }}"
                                        data-search="{{ strtolower($shop->shop_name . ' ' . ($shop->location ?? '')) }}">
                                    <div class="st-shop-card-icon">
                                        <i class="bi bi-shop"></i>
                                    </div>
                                    <div class="st-shop-card-info">
                                        <div class="st-shop-card-name">{{ $shop->shop_name }}</div>
                                        <div class="st-shop-card-loc">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $shop->location ?: 'Main Location' }}
                                        </div>
                                    </div>
                                    <div class="st-shop-check">
                                        <i class="bi bi-check-lg {{ $isSel ? '' : 'd-none' }}"></i>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Backing multi-select (visible as Select2 when >12 shops; hidden synced input when using card grid) --}}
                    <div class="{{ $useCardGrid ? 'd-none' : '' }}">
                        <select name="shop_ids[]" id="shop_id" class="form-select" multiple="multiple">
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}"
                                    data-name="{{ $shop->shop_name }}"
                                    data-location="{{ $shop->location }}"
                                    {{ in_array((int) $shop->id, $oldShopIds, true) ? 'selected' : '' }}>
                                    {{ $shop->shop_name }} ({{ $shop->location }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── STEP 2: Assign Products & Quantities ── --}}
            <div class="st-step-card">
                <div class="st-step-head">
                    <div class="d-flex align-items-center gap-3">
                        <span class="st-step-num">2</span>
                        <div>
                            <h6 class="st-step-title">Assign Products &amp; Quantities <span class="text-danger">*</span></h6>
                            <p class="st-step-desc">Choose how you want to allocate products across your selected shop(s).</p>
                        </div>
                    </div>
                </div>

                <div class="st-step-body">
                    {{-- Clear Mode Selector Cards --}}
                    <div class="st-mode-grid" role="group" aria-label="Dispatch mode">
                        <button type="button" class="st-mode-card is-active" data-mode="per_shop">
                            <div class="st-mode-icon"><i class="bi bi-shop-window"></i></div>
                            <div>
                                <div class="st-mode-title">Different Products per Shop</div>
                                <div class="st-mode-sub">Each shop gets its own independent product list &amp; quantities</div>
                            </div>
                        </button>
                        <button type="button" class="st-mode-card" data-mode="shared">
                            <div class="st-mode-icon"><i class="bi bi-diagram-3"></i></div>
                            <div>
                                <div class="st-mode-title">Same Products to All Shops</div>
                                <div class="st-mode-sub">Pick a product once and split quantities across every selected shop</div>
                            </div>
                        </button>
                    </div>

                    {{-- ════ MODE 1: DIFFERENT PRODUCTS PER SHOP ════ --}}
                    <div id="perShopModePanel">
                        <div id="perShopEmptyState" class="text-center py-4 px-3 rounded-3 border" style="background: #f8fafc; border-style: dashed !important; border-color: #cbd5e1 !important;">
                            <div class="mb-2 text-primary" style="font-size: 1.75rem;"><i class="bi bi-shop"></i></div>
                            <div class="fw-700 text-dark mb-1" style="font-size: .92rem;">Select one or more shops in Step 1 above</div>
                            <div class="text-muted small">Once selected, a dedicated product list card will appear here for each shop.</div>
                        </div>

                        <div id="shopCardsContainer" class="d-flex flex-column gap-3"></div>
                    </div>

                    {{-- ════ MODE 2: SAME PRODUCTS SPLIT ACROSS SHOPS ════ --}}
                    <div id="sharedModePanel" class="d-none">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <span class="small text-muted">Add a product once and enter quantities for each selected shop (set 0 to skip a shop).</span>
                            <button type="button" class="btn btn-sm btn-accent shadow-xs px-3" onclick="addSharedItemRow()">
                                <i class="bi bi-plus-lg me-1"></i> Add Product
                            </button>
                        </div>

                        <div id="shared-items-container" class="d-flex flex-column gap-3"></div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-custom w-100 py-2 d-flex align-items-center justify-content-center gap-2" onclick="addSharedItemRow()" style="border-style: dashed !important; border-width: 1.5px !important; border-radius: 9px;">
                                <i class="bi bi-plus-circle text-accent"></i>
                                <span class="fw-600">Add Another Product</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── STEP 3: Summary & Dispatch Action Bar ── --}}
            <div class="st-summary-card">
                <div class="row g-3 align-items-center justify-content-between">
                    <div class="col-12 col-lg-auto">
                        <div class="st-summary-metrics">
                            <div class="st-metric">
                                <div class="st-metric-icon" style="background: rgba(0,119,182,.1); color: #0077b6;">
                                    <i class="bi bi-shop"></i>
                                </div>
                                <div>
                                    <div class="st-metric-label">Target Shops</div>
                                    <div class="st-metric-val" id="summaryTargetShop">No shop selected</div>
                                </div>
                            </div>

                            <div class="st-metric">
                                <div class="st-metric-icon" style="background: rgba(139,92,246,.1); color: #7c3aed;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div>
                                    <div class="st-metric-label">Product Lines</div>
                                    <div class="st-metric-val" id="summaryTotalProducts">0 items</div>
                                </div>
                            </div>

                            <div class="st-metric">
                                <div class="st-metric-icon" style="background: rgba(16,185,129,.12); color: #059669;">
                                    <i class="bi bi-boxes"></i>
                                </div>
                                <div>
                                    <div class="st-metric-label">Total Dispatch Units</div>
                                    <div class="st-metric-val text-success" id="summaryTotalUnits">0 units</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-auto text-end">
                        <button type="submit" class="btn btn-accent px-4 py-2 fw-700 w-100 w-lg-auto shadow-sm" id="btnSubmitDispatch" style="min-height: 44px; border-radius: 10px;">
                            <i class="bi bi-truck me-2"></i><span id="submitBtnLabel">Dispatch Stock Now</span>
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- Reusable Product Options Template --}}
<template id="productOptionsTemplate">
    <option value="">— Search or select product —</option>
    @foreach($items as $item)
        <option value="{{ $item->id }}"
            data-name="{{ $item->item_name }}"
            data-brand="{{ $item->brand }}"
            data-category="{{ $item->category?->category_name ?? 'General' }}"
            data-available="{{ $availableStock[$item->id] ?? 0 }}">
            [{{ $item->category?->category_name ?? 'General' }}] {{ $item->item_name }}{{ $item->brand ? ' (' . $item->brand . ')' : '' }}
        </option>
    @endforeach
</template>
@endsection

@push('scripts')
<script>
let dispatchMode = 'per_shop';
let globalRowCounter = 0;
const USE_CARD_GRID = {{ $useCardGrid ? 'true' : 'false' }};

const SHOPS_CATALOG = @json($shops->map(fn($s) => ['id' => (int) $s->id, 'name' => $s->shop_name, 'location' => $s->location ?? ''])->values());
const SHOPS_MAP = {};
SHOPS_CATALOG.forEach(s => { SHOPS_MAP[String(s.id)] = s; });

const WAREHOUSE_STOCK_MAP = @json($availableStock->map(fn($q) => (int) $q));

function escapeHtmlSt(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getProductOptionsHtml() {
    const tpl = document.getElementById('productOptionsTemplate');
    return tpl ? tpl.innerHTML : '<option value="">— Choose product —</option>';
}

function getSelectedShops() {
    const vals = $('#shop_id').val() || [];
    const arr = Array.isArray(vals) ? vals : (vals ? [vals] : []);
    return arr.map(id => SHOPS_MAP[String(id)]).filter(Boolean);
}

// ── Select2 Formatters ─────────────────────────────────────────────────
function formatShopOption(item) {
    if (!item.id) return item.text;
    const location = $(item.element).data('location') || '';
    const name = $(item.element).data('name') || item.text.replace(/\s*\([^)]*\)\s*$/, '').trim();
    return $(`
        <div class="d-flex align-items-center justify-content-between py-1">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <i class="bi bi-shop text-primary fs-6"></i>
                <span class="fw-600 text-truncate">${escapeHtmlSt(name)}</span>
            </div>
            ${location ? `<span class="badge bg-secondary-subtle text-secondary small border ms-2 flex-shrink-0" style="font-size:0.7rem;"><i class="bi bi-geo-alt me-1"></i>${escapeHtmlSt(location)}</span>` : ''}
        </div>
    `);
}

function formatItemOption(item) {
    if (!item.id) return item.text;
    const avail = $(item.element).data('available');
    const cat = $(item.element).data('category') || 'General';
    const brand = $(item.element).data('brand') || '';
    const name = $(item.element).data('name') || item.text;
    const isOut = avail <= 0;

    const availBadge = isOut
        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.68rem;">Out of stock</span>'
        : `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">Avail: ${avail}</span>`;

    return $(`
        <div class="d-flex align-items-center justify-content-between py-1">
            <div class="min-w-0 pe-2">
                <div class="fw-600 text-truncate" style="font-size:0.83rem;">${escapeHtmlSt(name)}</div>
                <div class="text-muted small" style="font-size:0.72rem;">${escapeHtmlSt(cat)}${brand ? ' &bull; ' + escapeHtmlSt(brand) : ''}</div>
            </div>
            <div class="flex-shrink-0">${availBadge}</div>
        </div>
    `);
}

function formatItemSelection(item) {
    if (!item.id) return item.text;
    const avail = $(item.element).data('available');
    const name = $(item.element).data('name') || item.text;
    return $(`
        <span class="d-flex align-items-center gap-2 text-truncate">
            <i class="bi bi-box-seam text-primary"></i>
            <span class="fw-600">${escapeHtmlSt(name)}</span>
            <span class="badge bg-secondary-subtle text-secondary border small" style="font-size:0.68rem;">Stock: ${avail ?? 0}</span>
        </span>
    `);
}

function initItemSelect($select) {
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '— Search or select product —',
        allowClear: true,
        templateResult: formatItemOption,
        templateSelection: formatItemSelection
    });

    $select.on('change', function() {
        validateForm();
    });
}

// ── Mode Switcher ──────────────────────────────────────────────────────
function setDispatchMode(mode) {
    dispatchMode = mode;
    $('.st-mode-card').removeClass('is-active');
    $('.st-mode-card[data-mode="' + mode + '"]').addClass('is-active');

    if (mode === 'per_shop') {
        $('#perShopModePanel').removeClass('d-none');
        $('#sharedModePanel').addClass('d-none');
        $('#perShopModePanel').find('input, select').prop('disabled', false);
        $('#sharedModePanel').find('input, select').prop('disabled', true);
        syncPerShopCards();
    } else {
        $('#perShopModePanel').addClass('d-none');
        $('#sharedModePanel').removeClass('d-none');
        $('#perShopModePanel').find('input, select').prop('disabled', true);
        $('#sharedModePanel').find('input, select').prop('disabled', false);
        if ($('#shared-items-container .shared-item-row').length === 0) {
            addSharedItemRow();
        }
        syncAllSharedRowsForShops();
    }

    validateForm();
}

// ══════════════════════════════════════════════════════════════════════
// MODE 1: PER-SHOP DISPATCH CARDS
// ══════════════════════════════════════════════════════════════════════
function createShopItemRowHtml(shopId, rowIdx, preselectedItemId, preselectedQty) {
    const qtyVal = Math.max(1, parseInt(preselectedQty, 10) || 1);
    return `
        <div class="item-row-card per-shop-item-row" data-shop-id="${shopId}" data-row-idx="${rowIdx}">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-secondary-subtle text-secondary border small fw-600 shop-item-num-badge" style="font-size:0.7rem;">Product #1</span>
                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-shop-item" title="Remove Product" style="font-size:0.78rem;">
                    <i class="bi bi-trash me-1"></i>Remove
                </button>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-600 text-secondary mb-1">Product <span class="text-danger">*</span></label>
                    <select name="dispatches[${shopId}][items][${rowIdx}][id]" class="form-select item-select per-shop-item-select" required>
                        ${getProductOptionsHtml()}
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-600 text-secondary mb-1">Warehouse Stock</label>
                    <div class="available-pill st-stock-pill bg-light text-secondary border">—</div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-600 text-secondary mb-1">Qty to Dispatch <span class="text-danger">*</span></label>
                    <div class="input-group st-qty-group">
                        <button type="button" class="btn btn-outline-secondary btn-per-shop-step" data-delta="-1">−</button>
                        <input type="number" name="dispatches[${shopId}][items][${rowIdx}][qty]" class="form-control text-center fw-700 per-shop-qty-input" min="1" value="${qtyVal}" required>
                        <button type="button" class="btn btn-outline-secondary btn-per-shop-step" data-delta="1">+</button>
                    </div>
                </div>
            </div>
            <div class="qty-warning text-danger small mt-2 d-none" style="font-size:0.74rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><span class="qty-warning-text"></span>
            </div>
        </div>
    `;
}

function addProductToShopCard(shopId, preselectedItemId = '', preselectedQty = 1) {
    const $card = $(`.shop-dispatch-card[data-shop-id="${shopId}"]`);
    if (!$card.length) return;

    const rowIdx = globalRowCounter++;
    const $row = $(createShopItemRowHtml(shopId, rowIdx, preselectedItemId, preselectedQty));
    $card.find('.shop-card-items').append($row);

    const $sel = $row.find('.per-shop-item-select');
    if (preselectedItemId) {
        $sel.val(String(preselectedItemId));
    }
    initItemSelect($sel);

    if (dispatchMode !== 'per_shop') {
        $row.find('input, select').prop('disabled', true);
    }

    renumberShopCardRows($card);
    validateForm();
}

function renumberShopCardRows($card) {
    const $rows = $card.find('.per-shop-item-row');
    $rows.each(function(idx) {
        $(this).find('.shop-item-num-badge').text(`Product #${idx + 1}`);
        $(this).find('.btn-remove-shop-item').toggleClass('d-none', $rows.length === 1);
    });
}

function syncPerShopCards() {
    const selectedShops = getSelectedShops();
    const selectedIds = new Set(selectedShops.map(s => String(s.id)));
    const $container = $('#shopCardsContainer');

    $('#perShopEmptyState').toggleClass('d-none', selectedShops.length > 0);

    $container.find('.shop-dispatch-card').each(function() {
        const sid = String($(this).data('shop-id'));
        if (!selectedIds.has(sid)) {
            $(this).remove();
        }
    });

    selectedShops.forEach(function(shop) {
        if ($container.find(`.shop-dispatch-card[data-shop-id="${shop.id}"]`).length === 0) {
            const $card = $(`
                <div class="shop-dispatch-card" data-shop-id="${shop.id}">
                    <input type="hidden" name="dispatches[${shop.id}][shop_id]" value="${shop.id}">
                    <div class="shop-dispatch-head">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="shop-dispatch-icon"><i class="bi bi-shop"></i></div>
                            <div class="min-w-0">
                                <div class="fw-700 text-dark d-flex align-items-center gap-2 flex-wrap" style="font-size:0.9rem;">
                                    <span>${escapeHtmlSt(shop.name)}</span>
                                    ${shop.location ? `<span class="badge bg-white text-secondary border" style="font-size:0.66rem; font-weight:600;"><i class="bi bi-geo-alt me-1"></i>${escapeHtmlSt(shop.location)}</span>` : ''}
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle shop-card-stats" style="font-size:0.68rem;">0 items · 0 units</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="dropdown copy-from-dropdown d-none">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-1 px-2" type="button" data-bs-toggle="dropdown" style="font-size:0.74rem; border-radius:7px;" title="Copy products from another shop">
                                    <i class="bi bi-copy me-1"></i>Copy From
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end copy-from-menu" style="font-size:0.78rem;"></ul>
                            </div>
                            <button type="button" class="btn btn-sm btn-accent py-1 px-2 btn-add-shop-product" data-shop-id="${shop.id}" style="font-size:0.74rem; border-radius:7px;">
                                <i class="bi bi-plus-lg me-1"></i>Add Product
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-remove-shop-card" data-shop-id="${shop.id}" style="font-size:0.74rem; border-radius:7px;" title="Remove shop">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="shop-dispatch-body">
                        <div class="shop-card-items d-flex flex-column gap-3"></div>
                        <button type="button" class="btn btn-sm btn-outline-custom w-100 mt-3 py-2 d-flex align-items-center justify-content-center gap-2 btn-add-shop-product" data-shop-id="${shop.id}" style="border-style: dashed !important; border-radius: 9px; font-size:0.78rem;">
                            <i class="bi bi-plus-circle text-accent"></i>
                            <span class="fw-600">Add Another Product to ${escapeHtmlSt(shop.name)}</span>
                        </button>
                    </div>
                </div>
            `);
            $container.append($card);
            addProductToShopCard(shop.id);
        }
    });

    $container.find('.shop-dispatch-card').each(function() {
        const sid = String($(this).data('shop-id'));
        const others = selectedShops.filter(s => String(s.id) !== sid);
        const $drop = $(this).find('.copy-from-dropdown');
        const $menu = $(this).find('.copy-from-menu');

        if (others.length > 0) {
            let mHtml = '';
            others.forEach(o => {
                mHtml += `<li><a class="dropdown-item btn-copy-shop-items" href="#" data-from-shop="${o.id}" data-to-shop="${sid}"><i class="bi bi-shop me-1 text-primary"></i>${escapeHtmlSt(o.name)}</a></li>`;
            });
            $menu.html(mHtml);
            $drop.removeClass('d-none');
        } else {
            $drop.addClass('d-none');
        }
    });

    if (dispatchMode !== 'per_shop') {
        $('#perShopModePanel').find('input, select').prop('disabled', true);
    }
}

// ══════════════════════════════════════════════════════════════════════
// MODE 2: SHARED PRODUCTS SPLIT ACROSS SELECTED SHOPS
// ══════════════════════════════════════════════════════════════════════
function addSharedItemRow() {
    const rowIdx = globalRowCounter++;
    const $container = $('#shared-items-container');
    const $row = $(`
        <div class="item-row-card shared-item-row" data-row-index="${rowIdx}">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-secondary-subtle text-secondary border small fw-600 shared-item-num-badge" style="font-size:0.7rem;">Product #${$container.children().length + 1}</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle row-total-badge d-none" style="font-size:0.7rem;">Total: 0 units</span>
                </div>
                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-shared-item" title="Remove Product" style="font-size:0.78rem;">
                    <i class="bi bi-trash me-1"></i>Remove
                </button>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-600 text-secondary mb-1">Product <span class="text-danger">*</span></label>
                    <select name="items[${rowIdx}][id]" class="form-select item-select shared-item-select" required>
                        ${getProductOptionsHtml()}
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-600 text-secondary mb-1">Warehouse Stock</label>
                    <div class="available-pill st-stock-pill bg-light text-secondary border">—</div>
                </div>
                <div class="col-6 col-md-3 single-qty-col">
                    <label class="form-label small fw-600 text-secondary mb-1">Qty to Dispatch <span class="text-danger">*</span></label>
                    <div class="input-group st-qty-group">
                        <button type="button" class="btn btn-outline-secondary btn-shared-single-step" data-delta="-1">−</button>
                        <input type="number" name="items[${rowIdx}][qty]" class="form-control text-center fw-700 shared-single-qty" min="1" value="1" required>
                        <button type="button" class="btn btn-outline-secondary btn-shared-single-step" data-delta="1">+</button>
                    </div>
                </div>
            </div>
            <div class="multi-shop-alloc-box d-none">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <span class="small fw-700 text-secondary">
                        <i class="bi bi-diagram-3-fill text-primary me-1"></i>Quantity Per Selected Shop
                        <span class="fw-normal text-muted">(set 0 to skip a shop)</span>
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <input type="number" class="form-control form-control-sm text-center apply-all-qty-input" min="0" value="1" style="width:68px;">
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 apply-all-shops-btn" style="font-size:.72rem; white-space:nowrap;">
                            <i class="bi bi-copy me-1"></i>Apply to All
                        </button>
                    </div>
                </div>
                <div class="shop-alloc-grid"></div>
            </div>
            <div class="qty-warning text-danger small mt-2 d-none" style="font-size:0.74rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><span class="qty-warning-text"></span>
            </div>
        </div>
    `);
    $container.append($row);
    initItemSelect($row.find('.shared-item-select'));
    syncSharedRowShops($row[0]);
    renumberSharedRows();

    if (dispatchMode !== 'shared') {
        $row.find('input, select').prop('disabled', true);
    }

    validateForm();
}

function renumberSharedRows() {
    const $rows = $('#shared-items-container .shared-item-row');
    $rows.each(function(idx) {
        $(this).find('.shared-item-num-badge').text(`Product #${idx + 1}`);
        $(this).find('.btn-remove-shared-item').toggleClass('d-none', $rows.length === 1);
    });
}

function syncSharedRowShops(rowEl) {
    const $row = $(rowEl);
    const rowIdx = $row.attr('data-row-index');
    const selectedShops = getSelectedShops();
    const isMulti = selectedShops.length > 1;
    const isModeActive = dispatchMode === 'shared';

    const $singleQtyCol = $row.find('.single-qty-col');
    const $singleQtyInput = $row.find('.shared-single-qty');
    const $multiBox = $row.find('.multi-shop-alloc-box');
    const $grid = $multiBox.find('.shop-alloc-grid');
    const $rowTotalBadge = $row.find('.row-total-badge');

    const savedMap = {};
    $grid.find('.shop-qty-input').each(function() {
        savedMap[String($(this).data('shop-id'))] = $(this).val();
    });

    if (isMulti) {
        const defQty = Math.max(1, parseInt($singleQtyInput.val(), 10) || 1);
        $singleQtyCol.addClass('d-none');
        $singleQtyInput.prop('disabled', true).prop('required', false);
        $multiBox.removeClass('d-none');
        $rowTotalBadge.removeClass('d-none');

        let html = '';
        selectedShops.forEach(shop => {
            const val = savedMap[String(shop.id)] !== undefined ? savedMap[String(shop.id)] : defQty;
            html += `
                <div class="shop-alloc-cell">
                    <div class="shop-alloc-name" title="${escapeHtmlSt(shop.name)}">
                        <i class="bi bi-shop text-primary flex-shrink-0"></i>
                        <span class="text-truncate">${escapeHtmlSt(shop.name)}</span>
                    </div>
                    ${shop.location ? `<div class="shop-alloc-loc"><i class="bi bi-geo-alt me-1"></i>${escapeHtmlSt(shop.location)}</div>` : ''}
                    <div class="input-group input-group-sm">
                        <button type="button" class="btn btn-outline-secondary px-2 btn-shop-qty-step" data-delta="-1">−</button>
                        <input type="number" name="items[${rowIdx}][shop_qtys][${shop.id}]" class="form-control text-center fw-700 shop-qty-input" data-shop-id="${shop.id}" min="0" value="${val}" ${isModeActive ? '' : 'disabled'}>
                        <button type="button" class="btn btn-outline-secondary px-2 btn-shop-qty-step" data-delta="1">+</button>
                    </div>
                </div>
            `;
        });
        $grid.html(html);
    } else {
        $multiBox.addClass('d-none');
        $grid.empty();
        $rowTotalBadge.addClass('d-none');
        $singleQtyCol.removeClass('d-none');
        $singleQtyInput.prop('disabled', !isModeActive).prop('required', isModeActive);
    }
}

function syncAllSharedRowsForShops() {
    document.querySelectorAll('#shared-items-container .shared-item-row').forEach(syncSharedRowShops);
}

// ── Shop Selector Sync ─────────────────────────────────────────────────
function syncShopCardsUI() {
    const selectedIds = new Set(getSelectedShops().map(s => String(s.id)));
    $('#shopCardsPicker .st-shop-card').each(function() {
        const sid = String($(this).data('shop-id'));
        const isSel = selectedIds.has(sid);
        $(this).toggleClass('is-selected', isSel);
        $(this).find('.st-shop-check i').toggleClass('d-none', !isSel);
    });
}

function initShopSelect() {
    const $shopSelect = $('#shop_id');
    if (!USE_CARD_GRID) {
        if ($shopSelect.hasClass('select2-hidden-accessible')) {
            $shopSelect.select2('destroy');
        }
        $shopSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '— Type to search or select one or more target shops —',
            closeOnSelect: false,
            templateResult: formatShopOption
        });
    }

    $shopSelect.on('change', function() {
        const selected = getSelectedShops();
        syncShopCardsUI();

        $('#selectedShopCountPill').text(`${selected.length} selected`);

        if (selected.length === 1) {
            $('#summaryTargetShop').text(selected[0].name);
            $('#submitBtnLabel').text('Dispatch Stock Now');
        } else if (selected.length > 1) {
            $('#summaryTargetShop').text(`${selected.length} shops selected`);
            $('#submitBtnLabel').text(`Dispatch to ${selected.length} Shops Now`);
        } else {
            $('#summaryTargetShop').text('No shop selected');
            $('#submitBtnLabel').text('Dispatch Stock Now');
        }

        syncPerShopCards();
        syncAllSharedRowsForShops();
        validateForm();
    });

    $shopSelect.trigger('change');
}

// ── Live Cross-Shop Warehouse Stock Counter & Validation ───────────────
function updateStockPill($pill, avail, totalAllocatedForItem) {
    if (avail === null || avail === undefined) {
        $pill.text('—').attr('class', 'available-pill st-stock-pill bg-light text-secondary border');
        return;
    }
    const remaining = avail - totalAllocatedForItem;
    if (remaining < 0) {
        $pill.text(`${avail} avail (${Math.abs(remaining)} over)`).attr('class', 'available-pill st-stock-pill bg-danger-subtle text-danger border border-danger-subtle');
    } else if (remaining === 0) {
        $pill.text(`0 left of ${avail}`).attr('class', 'available-pill st-stock-pill bg-warning-subtle text-warning border border-warning-subtle');
    } else {
        $pill.text(`${remaining} left / ${avail}`).attr('class', 'available-pill st-stock-pill bg-success-subtle text-success border border-success-subtle');
    }
}

function validateForm() {
    const selectedShops = getSelectedShops();
    let allValid = selectedShops.length > 0;
    let totalLines = 0;
    let totalUnits = 0;
    const itemTotals = {};

    if (dispatchMode === 'per_shop') {
        $('#shopCardsContainer .per-shop-item-row').each(function() {
            const itemId = $(this).find('.per-shop-item-select').val();
            const qty = Math.max(0, parseInt($(this).find('.per-shop-qty-input').val(), 10) || 0);
            if (itemId) itemTotals[itemId] = (itemTotals[itemId] || 0) + qty;
        });

        $('#shopCardsContainer .shop-dispatch-card').each(function() {
            let shopLines = 0;
            let shopUnits = 0;

            $(this).find('.per-shop-item-row').each(function() {
                const $row = $(this);
                const itemId = $row.find('.per-shop-item-select').val();
                const $qtyInput = $row.find('.per-shop-qty-input');
                const qty = Math.max(0, parseInt($qtyInput.val(), 10) || 0);
                const $pill = $row.find('.available-pill');
                const $warn = $row.find('.qty-warning');
                const $warnText = $row.find('.qty-warning-text');

                if (!itemId) {
                    allValid = false;
                    updateStockPill($pill, null, 0);
                    $warn.addClass('d-none');
                    $qtyInput.removeClass('is-invalid');
                    return;
                }

                const avail = WAREHOUSE_STOCK_MAP[itemId] ?? 0;
                const aggQty = itemTotals[itemId] || 0;
                updateStockPill($pill, avail, aggQty);

                shopLines++;
                shopUnits += qty;
                totalLines++;
                totalUnits += qty;

                if (qty <= 0) {
                    allValid = false;
                    $warnText.text('Quantity must be at least 1.');
                    $warn.removeClass('d-none');
                    $qtyInput.addClass('is-invalid');
                } else if (aggQty > avail) {
                    allValid = false;
                    $warnText.text(`Combined total across shops (${aggQty}) exceeds Main Warehouse stock (${avail} available).`);
                    $warn.removeClass('d-none');
                    $qtyInput.addClass('is-invalid');
                } else {
                    $warn.addClass('d-none');
                    $qtyInput.removeClass('is-invalid');
                }
            });

            if (shopLines === 0) allValid = false;
            $(this).find('.shop-card-stats').text(`${shopLines} item${shopLines === 1 ? '' : 's'} · ${shopUnits} unit${shopUnits === 1 ? '' : 's'}`);
        });
    } else {
        $('#shared-items-container .shared-item-row').each(function() {
            const itemId = $(this).find('.shared-item-select').val();
            if (!itemId) return;
            const $shopInputs = $(this).find('.shop-qty-input');
            let rowQty = 0;
            if ($shopInputs.length > 0) {
                $shopInputs.each(function() { rowQty += Math.max(0, parseInt($(this).val(), 10) || 0); });
            } else {
                rowQty = Math.max(0, parseInt($(this).find('.shared-single-qty').val(), 10) || 0);
            }
            itemTotals[itemId] = (itemTotals[itemId] || 0) + rowQty;
        });

        $('#shared-items-container .shared-item-row').each(function() {
            const $row = $(this);
            const itemId = $row.find('.shared-item-select').val();
            const $pill = $row.find('.available-pill');
            const $warn = $row.find('.qty-warning');
            const $warnText = $row.find('.qty-warning-text');
            const $rowTotalBadge = $row.find('.row-total-badge');
            const $singleInput = $row.find('.shared-single-qty');
            const $shopInputs = $row.find('.shop-qty-input');

            if (!itemId) {
                allValid = false;
                updateStockPill($pill, null, 0);
                $warn.addClass('d-none');
                $singleInput.removeClass('is-invalid');
                $shopInputs.removeClass('is-invalid');
                return;
            }

            const avail = WAREHOUSE_STOCK_MAP[itemId] ?? 0;
            let rowQty = 0;
            if ($shopInputs.length > 0) {
                $shopInputs.each(function() { rowQty += Math.max(0, parseInt($(this).val(), 10) || 0); });
            } else {
                rowQty = Math.max(0, parseInt($singleInput.val(), 10) || 0);
            }
            const aggQty = itemTotals[itemId] || 0;
            updateStockPill($pill, avail, aggQty);

            totalLines++;
            totalUnits += rowQty;

            if ($rowTotalBadge.length) {
                $rowTotalBadge.text(`Row Total: ${rowQty} / ${avail} avail`);
                $rowTotalBadge.attr('class', (aggQty > avail || rowQty <= 0)
                    ? 'badge bg-danger-subtle text-danger border border-danger-subtle row-total-badge'
                    : 'badge bg-primary-subtle text-primary border border-primary-subtle row-total-badge');
                if (selectedShops.length <= 1) $rowTotalBadge.addClass('d-none');
            }

            if (rowQty <= 0) {
                allValid = false;
                $warnText.text('Enter at least 1 unit for at least one selected shop.');
                $warn.removeClass('d-none');
                $shopInputs.addClass('is-invalid');
                $singleInput.addClass('is-invalid');
            } else if (aggQty > avail) {
                allValid = false;
                $warnText.text(`Total requested (${aggQty}) exceeds Main Warehouse stock (${avail} available).`);
                $warn.removeClass('d-none');
                $shopInputs.addClass('is-invalid');
                $singleInput.addClass('is-invalid');
            } else {
                $warn.addClass('d-none');
                $shopInputs.removeClass('is-invalid');
                $singleInput.removeClass('is-invalid');
            }
        });
    }

    if (totalLines === 0 || totalUnits <= 0) {
        allValid = false;
    }

    $('#summaryTotalProducts').text(`${totalLines} item${totalLines === 1 ? '' : 's'}`);
    $('#summaryTotalUnits').text(`${totalUnits} unit${totalUnits === 1 ? '' : 's'}`);
    $('#btnSubmitDispatch').prop('disabled', !allValid);

    return allValid;
}

// ── Event Wiring ───────────────────────────────────────────────────────
$(document).ready(function() {
    initShopSelect();
    setDispatchMode('per_shop');

    // Mode toggle
    $(document).on('click', '.st-mode-card', function() {
        setDispatchMode($(this).data('mode'));
    });

    // Clickable branch cards
    $(document).on('click', '#shopCardsPicker .st-shop-card', function() {
        const sid = String($(this).data('shop-id'));
        const current = ($('#shop_id').val() || []).map(String);
        const idx = current.indexOf(sid);
        if (idx >= 0) current.splice(idx, 1);
        else current.push(sid);
        $('#shop_id').val(current).trigger('change');
    });

    // Optional live search filter for branch cards
    $('#shopCardSearch').on('input', function() {
        const q = $(this).val().trim().toLowerCase();
        $('#shopCardsPicker .st-shop-card').each(function() {
            const hay = $(this).attr('data-search') || '';
            $(this).toggleClass('d-none', q !== '' && hay.indexOf(q) === -1);
        });
    });

    $('#btnSelectAllShops').on('click', function() {
        $('#shop_id').val(SHOPS_CATALOG.map(s => String(s.id))).trigger('change');
    });

    $('#btnClearShops').on('click', function() {
        $('#shop_id').val([]).trigger('change');
    });

    // Per-shop card actions
    $(document).on('click', '.btn-add-shop-product', function() {
        addProductToShopCard($(this).data('shop-id'));
    });

    $(document).on('click', '.btn-remove-shop-card', function() {
        const sid = String($(this).data('shop-id'));
        const current = ($('#shop_id').val() || []).map(String).filter(id => id !== sid);
        $('#shop_id').val(current).trigger('change');
    });

    $(document).on('click', '.btn-remove-shop-item', function() {
        const $card = $(this).closest('.shop-dispatch-card');
        if ($card.find('.per-shop-item-row').length > 1) {
            $(this).closest('.per-shop-item-row').remove();
            renumberShopCardRows($card);
            validateForm();
        }
    });

    $(document).on('click', '.btn-per-shop-step', function() {
        const delta = parseInt($(this).data('delta'), 10) || 0;
        const $inp = $(this).siblings('.per-shop-qty-input');
        let next = (parseInt($inp.val(), 10) || 0) + delta;
        if (next < 1) next = 1;
        $inp.val(next);
        validateForm();
    });

    $(document).on('click', '.btn-copy-shop-items', function(e) {
        e.preventDefault();
        const fromShopId = $(this).data('from-shop');
        const toShopId = $(this).data('to-shop');
        const $fromCard = $(`.shop-dispatch-card[data-shop-id="${fromShopId}"]`);
        const $toCard = $(`.shop-dispatch-card[data-shop-id="${toShopId}"]`);
        if (!$fromCard.length || !$toCard.length) return;

        const copied = [];
        $fromCard.find('.per-shop-item-row').each(function() {
            const itemId = $(this).find('.per-shop-item-select').val();
            const qty = parseInt($(this).find('.per-shop-qty-input').val(), 10) || 1;
            if (itemId) copied.push({ itemId, qty });
        });

        if (copied.length === 0) return;

        $toCard.find('.shop-card-items').empty();
        copied.forEach(c => addProductToShopCard(toShopId, c.itemId, c.qty));
        validateForm();
    });

    // Shared mode actions
    $(document).on('click', '.btn-remove-shared-item', function() {
        if ($('#shared-items-container .shared-item-row').length > 1) {
            $(this).closest('.shared-item-row').remove();
            renumberSharedRows();
            validateForm();
        }
    });

    $(document).on('click', '.btn-shared-single-step', function() {
        const delta = parseInt($(this).data('delta'), 10) || 0;
        const $inp = $(this).siblings('.shared-single-qty');
        let next = (parseInt($inp.val(), 10) || 0) + delta;
        if (next < 1) next = 1;
        $inp.val(next);
        validateForm();
    });

    $(document).on('click', '.btn-shop-qty-step', function() {
        const delta = parseInt($(this).data('delta'), 10) || 0;
        const $inp = $(this).siblings('.shop-qty-input');
        let next = (parseInt($inp.val(), 10) || 0) + delta;
        if (next < 0) next = 0;
        $inp.val(next);
        validateForm();
    });

    $(document).on('click', '.apply-all-shops-btn', function() {
        const $box = $(this).closest('.multi-shop-alloc-box');
        const val = Math.max(0, parseInt($box.find('.apply-all-qty-input').val(), 10) || 0);
        $box.find('.shop-qty-input').val(val);
        validateForm();
    });

    $(document).on('input change', '.per-shop-qty-input, .shared-single-qty, .shop-qty-input', function() {
        validateForm();
    });

    $('#assignForm').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Please check your dispatch details',
                text: 'Ensure at least one shop and product are selected, and combined quantities do not exceed Main Warehouse stock.',
                background: 'var(--card-bg)',
                color: 'var(--text-primary)'
            });
            return false;
        }
        $('#btnSubmitDispatch').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Dispatching stock...');
    });

    $(window).on('resize', function() {
        $('.select2-container').css('width', '100%');
    });
});
</script>
@endpush
