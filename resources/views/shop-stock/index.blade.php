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
</style>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Shop Inventory</h5>
        <small style="color:var(--text-secondary);">Available products in retail shops</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        <button type="button" class="btn btn-outline-custom btn-sm" data-bs-toggle="modal" data-bs-target="#uploadShopStockModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Stock
        </button>
        @endif
        @if(auth()->user()->isShopAdmin())
            @if(auth()->user()->allow_stock_addition)
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addOwnerStockModal">
                <i class="bi bi-plus-circle me-1"></i> Add Owner Stock
            </button>
            @endif
            <button type="button" class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminStockModal">
                <i class="bi bi-plus-circle me-1"></i> Add Admin Stock
            </button>
        @endif
        @if($lowStockItems > 0)
        <div class="alert alert-warning py-1 px-3 mb-0" style="font-size:.8rem;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $lowStockItems }} item(s) low in stock!
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

<div class="d-flex align-items-center justify-content-between mb-3 px-3 py-2 rounded border d-none" id="bulkActionsBar" style="background: var(--card-bg) !important; border-color: var(--card-border) !important;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check2-square text-accent fs-5"></i>
        <span class="fw-600 small" id="selectedCountText" style="color:var(--text-primary);">0 items selected</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-xs btn-accent px-3 py-1" id="bulkEnableBtn" style="font-size: .75rem;">Enable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-danger px-3 py-1" id="bulkDisableBtn" style="font-size: .75rem;">Disable Custom Components</button>
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
                            <img src="{{ asset('storage/' . $firstSt->item->image_path) }}"
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
                        TZS {{ number_format($firstSt->buying_price, 0) }}
                    </td>
                    @endif
                    <td style="font-size:.82rem;font-weight:600;">
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
                            <img src="{{ asset('storage/' . $firstSt->item->image_path) }}"
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
                        TZS {{ number_format($firstSt->buying_price, 0) }}
                    </td>
                    @endif
                    <td style="font-size:.82rem;font-weight:600;">
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
<!-- Add Admin Stock Modal -->
<div class="modal fade" id="addAdminStockModal" tabindex="-1" aria-labelledby="addAdminStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addAdminStockModalLabel"><i class="bi bi-plus-circle text-accent me-2"></i>Add Admin Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.store-admin-stock') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="createNewProductToggle" name="create_new_product" value="1">
                        <label class="form-check-label small fw-600" for="createNewProductToggle">Create a new product instead</label>
                    </div>

                    <div id="existingProductGroup">
                        <div class="mb-3">
                            <label for="item_id" class="form-label" style="font-size:0.8rem;">Select Product *</label>
                            <select name="item_id" id="item_id" class="form-select form-select-sm" required>
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
                    </div>

                    <div id="newProductGroup" style="display: none;">
                        <div class="mb-3">
                            <label for="new_item_name" class="form-label" style="font-size:0.8rem;">Product Name *</label>
                            <input type="text" name="new_item_name" id="new_item_name" class="form-control form-control-sm" placeholder="e.g. Wireless Keyboard K120">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="createNewCategoryToggle" name="create_new_category" value="1">
                            <label class="form-check-label small fw-600" for="createNewCategoryToggle">Create a new category instead</label>
                        </div>

                        <div id="existingCategoryGroup" class="mb-3">
                            <label for="category_id" class="form-label" style="font-size:0.8rem;">Category *</label>
                            <select name="category_id" id="category_id" class="form-select form-select-sm">
                                <option value="">-- Choose Category --</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="newCategoryGroup" class="mb-3" style="display: none;">
                            <label for="new_category_name" class="form-label" style="font-size:0.8rem;">Category Name *</label>
                            <input type="text" name="new_category_name" id="new_category_name" class="form-control form-control-sm" placeholder="e.g. Gaming Gear">
                        </div>
                        <div class="mb-3">
                            <label for="brand" class="form-label" style="font-size:0.8rem;">Brand</label>
                            <input type="text" name="brand" id="brand" class="form-control form-control-sm" placeholder="e.g. Logitech">
                        </div>
                        <div class="mb-3">
                            <label for="model" class="form-label" style="font-size:0.8rem;">Model</label>
                            <input type="text" name="model" id="model" class="form-control form-control-sm" placeholder="e.g. K120">
                        </div>
                        <div class="mb-3">
                            <label for="specification" class="form-label" style="font-size:0.8rem;">Specification</label>
                            <input type="text" name="specification" id="specification" class="form-control form-control-sm" placeholder="e.g. USB connection, spill-resistant">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_display" class="form-label" style="font-size:0.8rem;">Quantity *</label>
                        <input type="text" id="quantity_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 10">
                        <input type="hidden" name="quantity" id="quantity">
                    </div>
                    <div class="mb-3">
                        <label for="buying_price_display" class="form-label" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                        <input type="text" id="buying_price_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 1,000">
                        <input type="hidden" name="buying_price" id="buying_price">
                    </div>
                    <div class="mb-3">
                        <label for="selling_price_display" class="form-label" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                        <input type="text" id="selling_price_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 1,500">
                        <input type="hidden" name="selling_price" id="selling_price">
                        <div id="adminStockPriceWarning" class="text-danger small mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                    </div>
                    <div class="mb-3">
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
<div class="modal fade" id="addOwnerStockModal" tabindex="-1" aria-labelledby="addOwnerStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addOwnerStockModalLabel"><i class="bi bi-plus-circle text-success me-2"></i>Add Owner Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.store-owner-stock') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="owner_createNewProductToggle" name="create_new_product" value="1">
                        <label class="form-check-label small fw-600" for="owner_createNewProductToggle">Create a new product instead</label>
                    </div>

                    <div id="owner_existingProductGroup">
                        <div class="mb-3">
                            <label for="owner_item_id" class="form-label" style="font-size:0.8rem;">Select Product *</label>
                            <select name="item_id" id="owner_item_id" class="form-select form-select-sm" required>
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
                    </div>

                    <div id="owner_newProductGroup" style="display: none;">
                        <div class="mb-3">
                            <label for="owner_new_item_name" class="form-label" style="font-size:0.8rem;">Product Name *</label>
                            <input type="text" name="new_item_name" id="owner_new_item_name" class="form-control form-control-sm" placeholder="e.g. Wireless Keyboard K120">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="owner_createNewCategoryToggle" name="create_new_category" value="1">
                            <label class="form-check-label small fw-600" for="owner_createNewCategoryToggle">Create a new category instead</label>
                        </div>

                        <div id="owner_existingCategoryGroup" class="mb-3">
                            <label for="owner_category_id" class="form-label" style="font-size:0.8rem;">Category *</label>
                            <select name="category_id" id="owner_category_id" class="form-select form-select-sm">
                                <option value="">-- Choose Category --</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="owner_newCategoryGroup" class="mb-3" style="display: none;">
                            <label for="owner_new_category_name" class="form-label" style="font-size:0.8rem;">Category Name *</label>
                            <input type="text" name="new_category_name" id="owner_new_category_name" class="form-control form-control-sm" placeholder="e.g. Gaming Gear">
                        </div>
                        <div class="mb-3">
                            <label for="owner_brand" class="form-label" style="font-size:0.8rem;">Brand</label>
                            <input type="text" name="brand" id="owner_brand" class="form-control form-control-sm" placeholder="e.g. Logitech">
                        </div>
                        <div class="mb-3">
                            <label for="owner_model" class="form-label" style="font-size:0.8rem;">Model</label>
                            <input type="text" name="model" id="owner_model" class="form-control form-control-sm" placeholder="e.g. K120">
                        </div>
                        <div class="mb-3">
                            <label for="owner_specification" class="form-label" style="font-size:0.8rem;">Specification</label>
                            <input type="text" name="specification" id="owner_specification" class="form-control form-control-sm" placeholder="e.g. USB connection, spill-resistant">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="owner_quantity_display" class="form-label" style="font-size:0.8rem;">Quantity *</label>
                        <input type="text" id="owner_quantity_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 10">
                        <input type="hidden" name="quantity" id="owner_quantity">
                    </div>
                    <div class="mb-3">
                        <label for="owner_buying_price_display" class="form-label" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                        <input type="text" id="owner_buying_price_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 1,000">
                        <input type="hidden" name="buying_price" id="owner_buying_price">
                    </div>
                    <div class="mb-3">
                        <label for="owner_selling_price_display" class="form-label" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                        <input type="text" id="owner_selling_price_display" class="form-control form-control-sm" required autocomplete="off" placeholder="e.g. 1,500">
                        <input type="hidden" name="selling_price" id="owner_selling_price">
                        <div id="ownerStockPriceWarning" class="text-danger small mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                    </div>
                    <div class="mb-3">
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

                    <div class="mb-4 text-center py-3 border border-dashed rounded bg-light" style="border-style: dashed !important; border-width: 2px !important; border-color: var(--accent) !important; background: rgba(0, 136, 204, 0.03) !important;">
                        <i class="bi bi-cloud-arrow-up text-accent" style="font-size: 3rem;"></i>
                        <p class="mt-2 small text-muted">Select an Excel or CSV file to import shop stocks.</p>
                        <div class="d-grid gap-2 px-4 mt-3">
                            <input type="file" name="excel_file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.8rem; background: rgba(0, 136, 204, 0.08); border-color: rgba(0, 136, 204, 0.15); color: #005f9e;">
                        <i class="bi bi-info-circle-fill me-2"></i><strong>Tip:</strong> Need a starting point?
                        <a href="{{ route('shop-stock.import-template') }}" class="fw-bold text-accent text-decoration-none ms-1"><i class="bi bi-download me-1"></i>Download Template (.xlsx)</a>
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
        $('#quantity_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9]/g, '');
            this.value = formatNumber(cleanVal);
            $('#quantity').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#buying_price_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $('#buying_price').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateAdminStockPrices();
        });

        $('#selling_price_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $('#selling_price').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateAdminStockPrices();
        });

        function validateAdminStockPrices() {
            const buying = parseFloat($('#buying_price').val() || 0);
            const selling = parseFloat($('#selling_price').val() || 0);
            const warning = $('#adminStockPriceWarning');
            const submitBtn = $('#addAdminStockModal').find('button[type="submit"]');

            if (selling > 0 && buying > 0 && selling < buying) {
                warning.show();
                $('#selling_price_display').addClass('is-invalid');
                submitBtn.prop('disabled', true);
            } else {
                warning.hide();
                $('#selling_price_display').removeClass('is-invalid');
                submitBtn.prop('disabled', false);
            }
        }

        $('#addAdminStockModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#quantity').val('');
            $('#buying_price').val('');
            $('#selling_price').val('');
            $('#adminStockPriceWarning').hide();
            $('#selling_price_display').removeClass('is-invalid');
            $(this).find('button[type="submit"]').prop('disabled', false);
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

        function updateCategoryFields() {
            const isNewProduct = $('#createNewProductToggle').is(':checked');
            const isNewCategory = $('#createNewCategoryToggle').is(':checked');

            if (isNewProduct) {
                if (isNewCategory) {
                    $('#existingCategoryGroup').hide();
                    $('#category_id').val('').prop('required', false);
                    $('#newCategoryGroup').show();
                    $('#new_category_name').prop('required', true);
                } else {
                    $('#newCategoryGroup').hide();
                    $('#new_category_name').val('').prop('required', false);
                    $('#existingCategoryGroup').show();
                    $('#category_id').prop('required', true);
                }
            } else {
                $('#category_id').prop('required', false);
                $('#new_category_name').prop('required', false);
            }
        }

        // Toggle new product inputs vs select product dropdown
        $('#createNewProductToggle').on('change', function() {
            if (this.checked) {
                $('#existingProductGroup').hide();
                $('#item_id').val('').prop('required', false);
                $('#newProductGroup').show();
                $('#new_item_name').prop('required', true);
                updateCategoryFields();
            } else {
                $('#newProductGroup').hide();
                $('#new_item_name').val('').prop('required', false);
                $('#existingProductGroup').show();
                $('#item_id').prop('required', true);
                updateCategoryFields();
            }
        });

        // Toggle new category input vs category select dropdown
        $('#createNewCategoryToggle').on('change', function() {
            updateCategoryFields();
        });

        // Auto-suggest values when selecting a product (Admin Stock modal)
        $('#item_id').on('change', function() {
            const selectedOpt = $(this).find('option:selected');
            if (!selectedOpt.val()) {
                $('#buying_price_display').val('');
                $('#buying_price').val('');
                $('#selling_price_display').val('');
                $('#selling_price').val('');
                return;
            }

            const shopBuying = selectedOpt.data('admin-buying');
            const shopSelling = selectedOpt.data('admin-selling');
            const mainSelling = selectedOpt.data('main-selling');

            if (shopBuying && shopSelling) {
                $('#buying_price').val(shopBuying);
                $('#buying_price_display').val(formatNumber(shopBuying));
                $('#selling_price').val(shopSelling);
                $('#selling_price_display').val(formatNumber(shopSelling));
            } else if (mainSelling) {
                $('#buying_price').val(mainSelling);
                $('#buying_price_display').val(formatNumber(mainSelling));
                $('#selling_price').val('');
                $('#selling_price_display').val('');
            } else {
                $('#buying_price_display').val('');
                $('#buying_price').val('');
                $('#selling_price_display').val('');
                $('#selling_price').val('');
            }
            validateAdminStockPrices();
        });

        // Add Admin Stock Form validation
        $('#addAdminStockModal form').on('submit', function(e) {
            const buyingPrice = parseFloat($('#buying_price').val()) || 0;
            const sellingPrice = parseFloat($('#selling_price').val()) || 0;

            if (sellingPrice < buyingPrice) {
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
        // Toggle shop components POS visibility per product
        $('.toggle-components-btn').on('change', function() {
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

        function updateBulkActionsBar() {
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });

            $('.stock-checkbox-parent:checked').each(function() {
                const ids = $(this).data('ids');
                if (Array.isArray(ids)) {
                    ids.forEach(id => {
                        if (!checkedIds.includes(id)) {
                            checkedIds.push(id);
                        }
                    });
                }
            });

            const count = checkedIds.length;
            if (count > 0) {
                $('#selectedCountText').html(`<strong>${count}</strong> item(s) selected`);
                $('#bulkActionsBar').removeClass('d-none');
            } else {
                $('#bulkActionsBar').addClass('d-none');
            }
        }

        $('#checkAllStocks').on('change', function() {
            const isChecked = $(this).is(':checked');
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
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });

            $('.stock-checkbox-parent:checked').each(function() {
                const ids = $(this).data('ids');
                if (Array.isArray(ids)) {
                    ids.forEach(id => {
                        if (!checkedIds.includes(id)) {
                            checkedIds.push(id);
                        }
                    });
                }
            });

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

        @if(auth()->user()->allow_stock_addition)
        // Auto-comma formatter for Add Owner Stock Modal
        $('#owner_quantity_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9]/g, '');
            this.value = formatNumber(cleanVal);
            $('#owner_quantity').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('#owner_buying_price_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $('#owner_buying_price').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateOwnerStockPrices();
        });

        $('#owner_selling_price_display').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $('#owner_selling_price').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateOwnerStockPrices();
        });

        function validateOwnerStockPrices() {
            const buying = parseFloat($('#owner_buying_price').val() || 0);
            const selling = parseFloat($('#owner_selling_price').val() || 0);
            const warning = $('#ownerStockPriceWarning');
            const submitBtn = $('#addOwnerStockModal').find('button[type="submit"]');

            if (selling > 0 && buying > 0 && selling < buying) {
                warning.show();
                $('#owner_selling_price_display').addClass('is-invalid');
                submitBtn.prop('disabled', true);
            } else {
                warning.hide();
                $('#owner_selling_price_display').removeClass('is-invalid');
                submitBtn.prop('disabled', false);
            }
        }

        $('#addOwnerStockModal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#owner_quantity').val('');
            $('#owner_buying_price').val('');
            $('#owner_selling_price').val('');
            $('#ownerStockPriceWarning').hide();
            $('#owner_selling_price_display').removeClass('is-invalid');
            $(this).find('button[type="submit"]').prop('disabled', false);
        });

        function updateOwnerCategoryFields() {
            const isNewProduct = $('#owner_createNewProductToggle').is(':checked');
            const isNewCategory = $('#owner_createNewCategoryToggle').is(':checked');

            if (isNewProduct) {
                if (isNewCategory) {
                    $('#owner_existingCategoryGroup').hide();
                    $('#owner_category_id').val('').prop('required', false);
                    $('#owner_newCategoryGroup').show();
                    $('#owner_new_category_name').prop('required', true);
                } else {
                    $('#owner_newCategoryGroup').hide();
                    $('#owner_new_category_name').val('').prop('required', false);
                    $('#owner_existingCategoryGroup').show();
                    $('#owner_category_id').prop('required', true);
                }
            } else {
                $('#owner_category_id').prop('required', false);
                $('#owner_new_category_name').prop('required', false);
            }
        }

        // Toggle new product inputs vs select product dropdown
        $('#owner_createNewProductToggle').on('change', function() {
            if (this.checked) {
                $('#owner_existingProductGroup').hide();
                $('#owner_item_id').val('').prop('required', false);
                $('#owner_newProductGroup').show();
                $('#owner_new_item_name').prop('required', true);
                updateOwnerCategoryFields();
            } else {
                $('#owner_newProductGroup').hide();
                $('#owner_new_item_name').val('').prop('required', false);
                $('#owner_existingProductGroup').show();
                $('#owner_item_id').prop('required', true);
                updateOwnerCategoryFields();
            }
        });

        // Toggle new category input vs category select dropdown
        $('#owner_createNewCategoryToggle').on('change', function() {
            updateOwnerCategoryFields();
        });

        // Auto-suggest values when selecting a product (Owner Stock modal)
        $('#owner_item_id').on('change', function() {
            const selectedOpt = $(this).find('option:selected');
            if (!selectedOpt.val()) {
                $('#owner_buying_price_display').val('');
                $('#owner_buying_price').val('');
                $('#owner_selling_price_display').val('');
                $('#owner_selling_price').val('');
                return;
            }

            const shopBuying = selectedOpt.data('owner-buying');
            const shopSelling = selectedOpt.data('owner-selling');
            const mainSelling = selectedOpt.data('main-selling');

            if (shopBuying && shopSelling) {
                $('#owner_buying_price').val(shopBuying);
                $('#owner_buying_price_display').val(formatNumber(shopBuying));
                $('#owner_selling_price').val(shopSelling);
                $('#owner_selling_price_display').val(formatNumber(shopSelling));
            } else if (mainSelling) {
                $('#owner_buying_price').val(mainSelling);
                $('#owner_buying_price_display').val(formatNumber(mainSelling));
                $('#owner_selling_price').val('');
                $('#owner_selling_price_display').val('');
            } else {
                $('#owner_buying_price_display').val('');
                $('#owner_buying_price').val('');
                $('#owner_selling_price_display').val('');
                $('#owner_selling_price').val('');
            }
            validateOwnerStockPrices();
        });

        // Add Owner Stock Form validation
        $('#addOwnerStockModal form').on('submit', function(e) {
            const buyingPrice = parseFloat($('#owner_buying_price').val()) || 0;
            const sellingPrice = parseFloat($('#owner_selling_price').val()) || 0;

            if (sellingPrice < buyingPrice) {
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
        @endif

        $('#bulkEnableBtn').on('click', () => sendBulkUpdate(true));
        $('#bulkDisableBtn').on('click', () => sendBulkUpdate(false));
        @endif

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