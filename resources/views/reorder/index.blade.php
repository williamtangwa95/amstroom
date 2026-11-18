@extends('layouts.app')
@section('title', 'Reorder Suggestions — ' . $currentShop->shop_name)
@section('page-title', 'Stock Reorder Suggestions')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Stock & Warehouse</a></li>
<li class="breadcrumb-item active">Reorder Suggestions</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h5 class="mb-0 fw-700">
            <i class="bi bi-magic text-warning me-2"></i>Stock Reorder & Replenishment Suggestions
        </h5>
        <small style="color:var(--text-secondary);">
            Automated recommendations based on sales velocity and finished stock to prevent customer loss at <strong>{{ $currentShop->shop_name }}</strong>.
        </small>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('reorder.index') }}" id="filterForm" class="d-flex flex-wrap align-items-center gap-2">
        @if(auth()->user()->isOwner())
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text bg-custom border-custom text-muted"><i class="bi bi-shop"></i></span>
            <select name="shop_id" class="form-select form-select-sm border-custom auto-submit">
                @foreach($shops as $s)
                <option value="{{ $s->id }}" {{ $s->id == $shopId ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                @endforeach
            </select>
        </div>
        @else
        <input type="hidden" name="shop_id" value="{{ $shopId }}">
        @endif

        {{-- Velocity Window --}}
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text bg-custom border-custom text-muted" title="Sales rate calculation period"><i class="bi bi-speedometer2"></i></span>
            <select name="velocity_days" class="form-select form-select-sm border-custom auto-submit" title="Sales Rate Window">
                <option value="14" {{ $velocityDays == 14 ? 'selected' : '' }}>Last 14 Days Rate</option>
                <option value="30" {{ $velocityDays == 30 ? 'selected' : '' }}>Last 30 Days Rate (Recommended)</option>
                <option value="60" {{ $velocityDays == 60 ? 'selected' : '' }}>Last 60 Days Rate</option>
                <option value="90" {{ $velocityDays == 90 ? 'selected' : '' }}>Last 90 Days Rate</option>
            </select>
        </div>

        {{-- Target Cover Days --}}
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text bg-custom border-custom text-muted" title="Desired inventory buffer in days"><i class="bi bi-shield-check"></i></span>
            <select name="target_cover_days" class="form-select form-select-sm border-custom auto-submit" title="Target Cover Buffer">
                <option value="7" {{ $targetCoverDays == 7 ? 'selected' : '' }}>7 Days Cover</option>
                <option value="14" {{ $targetCoverDays == 14 ? 'selected' : '' }}>14 Days Cover (Recommended)</option>
                <option value="21" {{ $targetCoverDays == 21 ? 'selected' : '' }}>21 Days Cover</option>
                <option value="30" {{ $targetCoverDays == 30 ? 'selected' : '' }}>30 Days Cover</option>
            </select>
        </div>

        <button type="button" class="btn btn-sm btn-outline-custom" data-bs-toggle="modal" data-bs-target="#addExtraProductModal">
            <i class="bi bi-plus-circle me-1"></i> Add Other Item
        </button>
    </form>
</div>

@if(session('success'))
<div class="alert alert-success border-0 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="font-size:.85rem;">
    <div><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.85rem;">
    <div class="fw-700 mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please check the following issues:</div>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- KPI Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card" style="border-left: 4px solid #e94560 !important; cursor:pointer;" data-filter="out_of_stock">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-600">Out of Stock</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill"><i class="bi bi-x-octagon"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-800 mb-0 text-danger">{{ $kpis['out_of_stock'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">0 remaining in shop</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card" style="border-left: 4px solid #f59e0b !important; cursor:pointer;" data-filter="critical">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-600">Critical & Low Stock</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill"><i class="bi bi-exclamation-triangle"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-800 mb-0 text-warning">{{ $kpis['critical'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">&le; Alert threshold</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card" style="border-left: 4px solid #8b5cf6 !important; cursor:pointer;" data-filter="fast_movers">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-600">Fast Movers at Risk</span>
                    <span class="badge bg-purple-subtle text-purple rounded-pill" style="background:#ede9fe;color:#8b5cf6;"><i class="bi bi-lightning-charge"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-800 mb-0" style="color:#8b5cf6;">{{ $kpis['fast_movers_at_risk'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">High velocity demand</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-600">Warehouse Fulfillable</span>
                    <span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-building-check"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h3 class="fw-800 mb-0 text-success">{{ $kpis['warehouse_fulfillable'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">Ready in Main Store</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Reorder Container --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-custom d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
        <div class="d-flex align-items-center gap-2">
            <div class="form-check m-0">
                <input class="form-check-input" type="checkbox" id="selectAllCheckbox" title="Select / Deselect All">
            </div>
            <span class="fw-700" style="font-size:.9rem;">
                <span id="selectedCountBadge" class="badge bg-primary rounded-pill me-1">0</span> items selected
                (<span id="selectedUnitsBadge" class="fw-700 text-accent">0</span> units)
            </span>
        </div>

        {{-- Urgency Filter Buttons & Search --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-custom border-custom text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="tableSearchInput" class="form-control form-control-sm border-custom" placeholder="Search product...">
            </div>

            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-custom active filter-btn" data-filter="all">All ({{ $suggestions->count() }})</button>
                <button type="button" class="btn btn-outline-custom filter-btn" data-filter="out_of_stock">Out of Stock</button>
                <button type="button" class="btn btn-outline-custom filter-btn" data-filter="critical">Critical</button>
                <button type="button" class="btn btn-outline-custom filter-btn" data-filter="high_risk">High Risk</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="reorderTable">
            <thead class="table-light small">
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th style="min-width: 220px;">Product & Category</th>
                    <th style="width: 80px;" class="text-center">Unit</th>
                    <th style="min-width: 110px;">Shop Stock</th>
                    <th style="min-width: 140px;">Sales Rate ({{ $velocityDays }}d)</th>
                    <th style="min-width: 120px;">Days of Cover</th>
                    <th style="min-width: 130px;">Warehouse Stock</th>
                    <th style="min-width: 150px;" class="text-center">Suggested Order</th>
                    <th style="min-width: 110px;" class="text-center">Status / In Transit</th>
                </tr>
            </thead>
            <tbody id="reorderTableBody">
                @forelse($suggestions as $item)
                @php
                    $isOut = $item->current_stock <= 0;
                    $isWhLow = $item->main_store_available < $item->suggested_reorder_qty;
                    $isWhZero = $item->main_store_available <= 0;
                @endphp
                <tr class="reorder-row" 
                    data-item-id="{{ $item->item_id }}" 
                    data-urgency="{{ $item->urgency }}"
                    data-fast-mover="{{ $item->daily_sales_rate >= 0.5 ? '1' : '0' }}">
                    <td class="text-center">
                        <input class="form-check-input item-checkbox" type="checkbox" 
                            data-item-id="{{ $item->item_id }}" 
                            data-item-name="{{ $item->item_name }}"
                            data-wh-stock="{{ $item->main_store_available }}"
                            data-unit="{{ $item->unit_symbol }}">
                    </td>
                    <td>
                        <div class="fw-700" style="font-size:.87rem; color:var(--text-color);">
                            {{ $item->item_name }}
                        </div>
                        <div class="d-flex flex-wrap gap-1 align-items-center mt-0.5" style="font-size:.72rem;">
                            <span class="badge bg-secondary-subtle text-secondary py-0 px-1">{{ $item->category_name }}</span>
                            @if($item->brand)<span class="text-muted">{{ $item->brand }}</span>@endif
                            @if($item->model)<span class="text-muted">/ {{ $item->model }}</span>@endif
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border py-1 px-1.5" style="font-size:.75rem;">
                            {{ $item->unit_symbol }}
                        </span>
                    </td>
                    <td>
                        @if($isOut)
                        <span class="badge bg-danger-subtle text-danger py-1 px-2 fw-700">
                            <i class="bi bi-x-circle me-1"></i> 0 {{ $item->unit_symbol }}
                        </span>
                        @else
                        <span class="badge bg-warning-subtle text-warning py-1 px-2 fw-700">
                            {{ $item->current_stock }} {{ $item->unit_symbol }}
                        </span>
                        <div class="text-muted mt-0.5" style="font-size:.68rem;">Alert at &le; {{ $item->alert_threshold }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-700" style="font-size:.82rem;">
                            {{ $item->total_sold }} {{ $item->unit_symbol }} sold
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">
                            &sim; {{ number_format($item->daily_sales_rate, 2) }} {{ $item->unit_symbol }}/day
                        </div>
                    </td>
                    <td>
                        @if($item->days_remaining === 0)
                        <span class="badge bg-danger text-white py-1 px-2" style="font-size:.72rem;">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i> Stockout (0d)
                        </span>
                        @elseif($item->days_remaining !== null)
                            @if($item->days_remaining <= 3)
                            <span class="badge bg-danger-subtle text-danger py-1 px-2 fw-700" style="font-size:.72rem;">
                                {{ $item->days_remaining }} days left
                            </span>
                            @elseif($item->days_remaining <= 7)
                            <span class="badge bg-warning-subtle text-warning py-1 px-2 fw-700" style="font-size:.72rem;">
                                {{ $item->days_remaining }} days left
                            </span>
                            @else
                            <span class="badge bg-info-subtle text-info py-1 px-2 fw-700" style="font-size:.72rem;">
                                {{ $item->days_remaining }} days cover
                            </span>
                            @endif
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($isWhZero)
                        <span class="badge bg-danger-subtle text-danger py-1 px-2 fw-700" style="font-size:.75rem;" title="Central Warehouse is also out of stock!">
                            <i class="bi bi-building-x me-1"></i> 0 {{ $item->unit_symbol }}
                        </span>
                        @else
                        <span class="badge bg-success-subtle text-success py-1 px-2 fw-700" style="font-size:.75rem;">
                            <i class="bi bi-building-check me-1"></i> {{ $item->main_store_available }} {{ $item->unit_symbol }}
                        </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm d-inline-flex align-items-center" style="width: 120px;">
                            <input type="number" min="1" 
                                class="form-control form-control-sm text-center fw-700 qty-input" 
                                value="{{ $item->suggested_reorder_qty }}" 
                                data-item-id="{{ $item->item_id }}"
                                data-wh-stock="{{ $item->main_store_available }}"
                                style="font-size:.85rem;">
                            <span class="input-group-text bg-light text-muted p-1" style="font-size:.7rem;">{{ $item->unit_symbol }}</span>
                        </div>
                        <div class="wh-warning text-danger mt-1 d-none" style="font-size:.65rem;">
                            <i class="bi bi-exclamation-triangle"></i> Exceeds warehouse
                        </div>
                    </td>
                    <td class="text-center">
                        @if($item->pending_request_qty > 0)
                        <span class="badge bg-primary-subtle text-primary py-1 px-1.5 rounded" style="font-size:.68rem;" title="Pending Stock Request already sent">
                            <i class="bi bi-clock-history me-1"></i> {{ $item->pending_request_qty }} req
                        </span>
                        @endif
                        @if($item->incoming_transfer_qty > 0)
                        <span class="badge bg-purple-subtle text-purple py-1 px-1.5 rounded mt-0.5 d-block" style="font-size:.68rem;background:#ede9fe;color:#8b5cf6;" title="Stock Transfer dispatched and in transit">
                            <i class="bi bi-truck me-1"></i> {{ $item->incoming_transfer_qty }} in transit
                        </span>
                        @endif
                        @if($item->pending_request_qty == 0 && $item->incoming_transfer_qty == 0)
                        <span class="badge bg-light text-muted py-1 px-1.5" style="font-size:.68rem;">Ready to order</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr id="emptyRow">
                    <td colspan="9" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            <h6 class="fw-700 mb-1">All Stock Levels Healthy!</h6>
                            <small>No items currently require urgent reordering for {{ $currentShop->shop_name }}.</small>
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-outline-custom" data-bs-toggle="modal" data-bs-target="#addExtraProductModal">
                                    <i class="bi bi-plus-circle me-1"></i> Add Custom Product to Order
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bottom Action Footer Bar --}}
    <div class="card-footer bg-custom d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 border-top">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Need an item not listed in suggestions?</span>
            <button type="button" class="btn btn-xs btn-outline-custom" data-bs-toggle="modal" data-bs-target="#addExtraProductModal">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            {{-- Shop Admin / General Action: Submit Stock Request --}}
            <button type="button" class="btn btn-accent btn-sm shadow-sm" id="btnOpenRequestModal" disabled>
                <i class="bi bi-cart-plus me-1"></i> Submit Stock Order to Main Store
            </button>

            {{-- Owner / System Admin Exclusive Action: Direct Dispatch / Transfer --}}
            @if(auth()->user()->isOwner())
            <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnOpenTransferModal" disabled>
                <i class="bi bi-truck me-1"></i> Direct Transfer to {{ $currentShop->shop_name }}
            </button>
            @endif
        </div>
    </div>
</div>

{{-- MODAL 1: Add Extra Non-Suggested Product --}}
<div class="modal fade" id="addExtraProductModal" tabindex="-1" aria-labelledby="addExtraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700" id="addExtraModalLabel">
                    <i class="bi bi-plus-circle text-accent me-2"></i>Add Custom Product to Reorder
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">
                    Select any catalog item to include in this replenishment order for <strong>{{ $currentShop->shop_name }}</strong>.
                </p>

                <div class="mb-3">
                    <label class="form-label small fw-700">Select Product *</label>
                    <select id="extraProductSelect" class="form-select form-select-sm">
                        <option value="">Choose product from catalog...</option>
                        @foreach($catalogItems as $cItem)
                        @php $whAvail = (int) ($warehouseStockMap->get($cItem->id) ?? 0); @endphp
                        <option value="{{ $cItem->id }}" 
                            data-name="{{ $cItem->item_name }}"
                            data-cat="{{ $cItem->category?->category_name ?? 'General' }}"
                            data-unit="{{ $cItem->unit_symbol }}"
                            data-wh-stock="{{ $whAvail }}">
                            {{ $cItem->item_name }} ({{ $cItem->category?->category_name ?? 'General' }}) — Main Wh: {{ $whAvail }} {{ $cItem->unit_symbol }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-700">Order Quantity *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="extraProductQty" class="form-control form-control-sm text-center fw-700" value="10" min="1">
                            <span class="input-group-text bg-light text-muted" id="extraProductUnitSpan">pc</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-700 text-muted">Warehouse Stock</label>
                        <div class="form-control form-control-sm bg-light text-muted" id="extraProductWhDisplay">
                            —
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-custom p-2">
                <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-accent" id="btnConfirmAddExtra">
                    <i class="bi bi-plus-lg me-1"></i> Add to Order List
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2: Confirm Stock Request Submission --}}
<div class="modal fade" id="confirmRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700">
                    <i class="bi bi-cart-check text-accent me-2"></i>Confirm Stock Order to Main Store
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reorder.request') }}" id="reorderRequestForm">
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shopId }}">
                <div class="modal-body p-3">
                    <div class="alert alert-info border-0 py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:.82rem;">
                        <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                        <div>The following items will be sent as an official <strong>Stock Request</strong> to the Main Store for approval and dispatch.</div>
                    </div>

                    <div class="table-responsive mb-3" style="max-height: 280px;">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" style="width: 100px;">Quantity</th>
                                    <th class="text-center" style="width: 120px;">Warehouse Stock</th>
                                </tr>
                            </thead>
                            <tbody id="requestSummaryBody"></tbody>
                        </table>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-700">Notes / Instructions (Optional)</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Urgent customer replenishment for weekend sales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-custom p-2">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">
                        <i class="bi bi-check-circle me-1"></i> Submit Stock Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(auth()->user()->isOwner())
{{-- MODAL 3: Confirm Direct Stock Transfer (Owner Exclusive) --}}
<div class="modal fade" id="confirmTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700">
                    <i class="bi bi-truck text-primary me-2"></i>Confirm Direct Stock Transfer to {{ $currentShop->shop_name }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reorder.transfer') }}" id="reorderTransferForm">
                @csrf
                <input type="hidden" name="shop_id" value="{{ $shopId }}">
                <div class="modal-body p-3">
                    <div class="alert alert-warning border-0 py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:.82rem;">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 text-warning"></i>
                        <div>This will directly deduct stock from the <strong>Main Warehouse (FIFO)</strong> and create an in-transit dispatch awaiting shop receipt confirmation.</div>
                    </div>

                    <div class="table-responsive mb-3" style="max-height: 280px;">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" style="width: 100px;">Transfer Qty</th>
                                    <th class="text-center" style="width: 120px;">Warehouse Avail</th>
                                    <th class="text-center" style="width: 90px;">Status</th>
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
    // Auto-submit filter changes
    $('.auto-submit').on('change', function() {
        $('#filterForm').submit();
    });

    // Update selection summary and button states
    function updateSelectionSummary() {
        let count = 0;
        let totalUnits = 0;

        $('.item-checkbox:checked').each(function() {
            count++;
            const row = $(this).closest('tr');
            const qty = parseInt(row.find('.qty-input').val()) || 0;
            totalUnits += qty;
        });

        $('#selectedCountBadge').text(count);
        $('#selectedUnitsBadge').text(totalUnits.toLocaleString());

        if (count > 0) {
            $('#btnOpenRequestModal').prop('disabled', false);
            $('#btnOpenTransferModal').prop('disabled', false);
        } else {
            $('#btnOpenRequestModal').prop('disabled', true);
            $('#btnOpenTransferModal').prop('disabled', true);
        }
    }

    // Select all toggle
    $('#selectAllCheckbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.reorder-row:visible .item-checkbox').prop('checked', isChecked);
        updateSelectionSummary();
    });

    // Individual checkbox click
    $(document).on('change', '.item-checkbox', function() {
        updateSelectionSummary();
        const totalVisible = $('.reorder-row:visible .item-checkbox').length;
        const totalChecked = $('.reorder-row:visible .item-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', totalVisible > 0 && totalVisible === totalChecked);
    });

    // Qty input change
    $(document).on('input change', '.qty-input', function() {
        const input = $(this);
        const qty = parseInt(input.val()) || 0;
        const whStock = parseInt(input.data('wh-stock')) || 0;
        const warning = input.closest('td').find('.wh-warning');

        if (qty > whStock && whStock >= 0) {
            warning.removeClass('d-none');
        } else {
            warning.addClass('d-none');
        }

        updateSelectionSummary();
    });

    // Trigger initial check for inputs exceeding warehouse stock
    $('.qty-input').trigger('change');

    // Search filter across the table
    $('#tableSearchInput').on('keyup', function() {
        const val = $(this).val().toLowerCase().trim();
        $('.reorder-row').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(val) > -1);
        });
        updateSelectionSummary();
    });

    // Urgency tab buttons filter
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');

        const filter = $(this).data('filter');
        applyFilter(filter);
    });

    // KPI card click filter
    $('.kpi-card[data-filter]').on('click', function() {
        const filter = $(this).data('filter');
        $('.filter-btn').removeClass('active');
        $(`.filter-btn[data-filter="${filter}"]`).addClass('active');
        applyFilter(filter);
    });

    function applyFilter(filter) {
        if (filter === 'all') {
            $('.reorder-row').show();
        } else if (filter === 'fast_movers') {
            $('.reorder-row').each(function() {
                $(this).toggle($(this).data('fast-mover') == '1');
            });
        } else {
            $('.reorder-row').each(function() {
                $(this).toggle($(this).data('urgency') === filter);
            });
        }
        updateSelectionSummary();
    }

    // Modal Add Extra Product logic
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
            alert('Please select a product from the list.');
            return;
        }

        const itemName = opt.data('name');
        const catName = opt.data('cat') || 'General';
        const unit = opt.data('unit') || 'pc';
        const whStock = parseInt(opt.data('wh-stock')) || 0;
        const qty = parseInt($('#extraProductQty').val()) || 1;

        // Check if row already exists
        const existingRow = $(`#reorderTableBody tr[data-item-id="${itemId}"]`);
        if (existingRow.length > 0) {
            existingRow.find('.item-checkbox').prop('checked', true);
            existingRow.find('.qty-input').val(qty);
            existingRow.show();
            $('html, body').animate({ scrollTop: existingRow.offset().top - 150 }, 300);
            $('#addExtraProductModal').modal('hide');
            updateSelectionSummary();
            return;
        }

        // Remove empty state row if present
        $('#emptyRow').remove();

        // Append new custom row
        const newRowHtml = `
            <tr class="reorder-row table-info" data-item-id="${itemId}" data-urgency="custom" data-fast-mover="0">
                <td class="text-center">
                    <input class="form-check-input item-checkbox" type="checkbox" checked
                        data-item-id="${itemId}" 
                        data-item-name="${itemName}"
                        data-wh-stock="${whStock}"
                        data-unit="${unit}">
                </td>
                <td>
                    <div class="fw-700" style="font-size:.87rem; color:var(--text-color);">
                        ${itemName}
                        <span class="badge bg-primary-subtle text-primary py-0 px-1 ms-1" style="font-size:.65rem;">Custom Extra</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-0.5" style="font-size:.72rem;">
                        <span class="badge bg-secondary-subtle text-secondary py-0 px-1">${catName}</span>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border py-1 px-1.5" style="font-size:.75rem;">${unit}</span>
                </td>
                <td>
                    <span class="badge bg-secondary-subtle text-secondary py-1 px-2 fw-700">Catalog Item</span>
                </td>
                <td>
                    <span class="text-muted small">Ad-hoc addition</span>
                </td>
                <td>
                    <span class="text-muted small">—</span>
                </td>
                <td>
                    <span class="badge ${whStock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} py-1 px-2 fw-700" style="font-size:.75rem;">
                        <i class="bi ${whStock > 0 ? 'bi-building-check' : 'bi-building-x'} me-1"></i> ${whStock} ${unit}
                    </span>
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm d-inline-flex align-items-center" style="width: 120px;">
                        <input type="number" min="1" 
                            class="form-control form-control-sm text-center fw-700 qty-input" 
                            value="${qty}" 
                            data-item-id="${itemId}"
                            data-wh-stock="${whStock}"
                            style="font-size:.85rem;">
                        <span class="input-group-text bg-light text-muted p-1" style="font-size:.7rem;">${unit}</span>
                    </div>
                    <div class="wh-warning text-danger mt-1 ${qty > whStock ? '' : 'd-none'}" style="font-size:.65rem;">
                        <i class="bi bi-exclamation-triangle"></i> Exceeds warehouse
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge bg-info-subtle text-info py-1 px-1.5" style="font-size:.68rem;">Added</span>
                </td>
            </tr>
        `;

        $('#reorderTableBody').prepend(newRowHtml);
        $('#addExtraProductModal').modal('hide');
        $('#extraProductSelect').val('');
        $('#extraProductWhDisplay').text('—');
        $('#extraProductQty').val(10);
        updateSelectionSummary();
    });

    // Populate Request Modal
    $('#btnOpenRequestModal').on('click', function() {
        const tbody = $('#requestSummaryBody');
        tbody.empty();

        let index = 0;
        $('.item-checkbox:checked').each(function() {
            const checkbox = $(this);
            const row = checkbox.closest('tr');
            const itemId = checkbox.data('item-id');
            const itemName = checkbox.data('item-name');
            const unit = checkbox.data('unit');
            const qty = parseInt(row.find('.qty-input').val()) || 1;
            const whStock = parseInt(checkbox.data('wh-stock')) || 0;

            const tr = `
                <tr>
                    <td>
                        <span class="fw-700">${itemName}</span>
                        <input type="hidden" name="items[${index}][item_id]" value="${itemId}">
                    </td>
                    <td class="text-center fw-700">
                        ${qty} ${unit}
                        <input type="hidden" name="items[${index}][quantity]" value="${qty}">
                    </td>
                    <td class="text-center">
                        <span class="badge ${whStock >= qty ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${whStock} ${unit}
                        </span>
                    </td>
                </tr>
            `;
            tbody.append(tr);
            index++;
        });

        $('#confirmRequestModal').modal('show');
    });

    // Populate Transfer Modal (Owner only)
    $('#btnOpenTransferModal').on('click', function() {
        const tbody = $('#transferSummaryBody');
        tbody.empty();

        let index = 0;
        let hasShortage = false;

        $('.item-checkbox:checked').each(function() {
            const checkbox = $(this);
            const row = checkbox.closest('tr');
            const itemId = checkbox.data('item-id');
            const itemName = checkbox.data('item-name');
            const unit = checkbox.data('unit');
            const qty = parseInt(row.find('.qty-input').val()) || 1;
            const whStock = parseInt(checkbox.data('wh-stock')) || 0;
            const isShort = whStock < qty;

            if (isShort) {
                hasShortage = true;
            }

            const tr = `
                <tr class="${isShort ? 'table-danger' : ''}">
                    <td>
                        <span class="fw-700">${itemName}</span>
                        <input type="hidden" name="items[${index}][item_id]" value="${itemId}">
                    </td>
                    <td class="text-center fw-700">
                        ${qty} ${unit}
                        <input type="hidden" name="items[${index}][quantity]" value="${qty}">
                    </td>
                    <td class="text-center">
                        <span class="badge ${whStock >= qty ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${whStock} ${unit}
                        </span>
                    </td>
                    <td class="text-center">
                        ${isShort ? '<span class="text-danger small fw-700"><i class="bi bi-x-circle me-1"></i>Short</span>' : '<span class="text-success small fw-700"><i class="bi bi-check-circle me-1"></i>OK</span>'}
                    </td>
                </tr>
            `;
            tbody.append(tr);
            index++;
        });

        if (hasShortage) {
            $('#btnSubmitTransferFinal').prop('disabled', true).text('Cannot Transfer (Shortage in Warehouse)');
        } else {
            $('#btnSubmitTransferFinal').prop('disabled', false).html('<i class="bi bi-box-arrow-right me-1"></i> Dispatch Transfer');
        }

        $('#confirmTransferModal').modal('show');
    });
});
</script>
@endpush
