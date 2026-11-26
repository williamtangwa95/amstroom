@extends('layouts.app')
@section('title', 'Stock Request #' . $stockRequest->id)
@section('page-title', 'Stock Request #' . $stockRequest->id)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Stock Requests</a></li>
<li class="breadcrumb-item active">Request #{{ $stockRequest->id }}</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       STOCK REQUEST SHOW & DIRECT DISPATCH — Executive UI
       ══════════════════════════════════════════════ */
    .srs-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 58%, #003a6c 100%);
        border-radius: 16px;
        padding: 1.2rem 1.45rem;
        color: #ffffff;
        box-shadow: 0 8px 24px rgba(0, 86, 145, .2);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .srs-hero-title {
        font-size: 1.18rem;
        font-weight: 800;
        margin: 0;
        color: #ffffff;
    }
    .srs-hero-sub {
        font-size: .8rem;
        color: rgba(255, 255, 255, .86);
        margin: .25rem 0 0;
    }
    .srs-hero-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .46rem .9rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .25);
        color: #ffffff;
        text-decoration: none;
        font-size: .78rem;
        font-weight: 600;
        transition: all .15s ease;
    }
    .srs-hero-back:hover {
        background: rgba(255, 255, 255, .24);
        color: #ffffff;
    }

    .srs-card {
        background: #ffffff;
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 16px;
        box-shadow: 0 3px 14px rgba(15, 23, 42, .045);
        overflow: hidden;
    }
    .srs-meta-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        padding: 1rem 1.35rem;
        background: #fafcff;
        border-bottom: 1px solid #f1f5f9;
    }
    .srs-meta-k {
        font-size: .66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: #64748b;
    }
    .srs-meta-v {
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: .15rem;
    }

    /* Owner Selective Dispatch Toolbar */
    .srs-toolbar {
        padding: .9rem 1.35rem;
        border-bottom: 1px solid #f1f5f9;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
    }

    /* Unified Responsive Item Rows (Single DOM — Zero Duplicate Inputs) */
    .srs-grid-header {
        display: grid;
        grid-template-columns: 44px minmax(0, 1.8fr) 115px 155px 175px 135px;
        gap: .75rem;
        align-items: center;
        padding: .65rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: .75rem;
    }
    .srs-item-card {
        display: grid;
        grid-template-columns: 44px minmax(0, 1.8fr) 115px 155px 175px 135px;
        gap: .75rem;
        align-items: center;
        padding: .95rem 1rem;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-left: 4px solid #0077b6;
        border-radius: 13px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .03);
        transition: all .18s ease;
    }
    .srs-item-card.is-selected {
        border-color: #93c5fd;
        border-left-color: #059669;
        background: #ffffff;
    }
    .srs-item-card.is-excluded {
        background: #f8fafc;
        border-color: #e2e8f0;
        border-left-color: #cbd5e1;
        opacity: .72;
    }
    .srs-item-card.is-unavailable {
        background: #fff5f5;
        border-color: #fecaca;
        border-left-color: #dc2626;
        opacity: .84;
    }

    .srs-check-lg {
        width: 21px;
        height: 21px;
        cursor: pointer;
        border-radius: 6px;
        border: 2px solid #94a3b8;
    }
    .srs-check-lg:checked {
        background-color: #059669;
        border-color: #059669;
    }

    /* Stepper */
    .srs-qty-stepper {
        display: flex;
        align-items: stretch;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        overflow: hidden;
        background: #ffffff;
        height: 36px;
        max-width: 155px;
    }
    .srs-qty-stepper button {
        width: 34px;
        border: none;
        background: #f8fafc;
        color: #334155;
        font-weight: 800;
        font-size: .95rem;
        cursor: pointer;
        transition: background .15s ease;
    }
    .srs-qty-stepper button:hover:not(:disabled) {
        background: #e2e8f0;
        color: #0f172a;
    }
    .srs-qty-stepper button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }
    .srs-qty-stepper input {
        flex: 1;
        min-width: 0;
        border: none;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        text-align: center;
        font-weight: 800;
        font-size: .88rem;
        color: #0f172a;
        background: #ffffff;
        outline: none;
    }
    .srs-qty-stepper input:disabled {
        background: #f1f5f9;
        color: #94a3b8;
    }

    .srs-mobile-label {
        display: none;
        font-size: .66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-bottom: .22rem;
    }

    /* Dispatch Action Footer */
    .srs-dispatch-bar {
        background: linear-gradient(135deg, #f0fdf4 0%, #f0f9ff 100%);
        border: 1px solid #bbf7d0;
        border-radius: 14px;
        padding: 1.05rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .srs-dispatch-stats {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.25rem;
    }
    .srs-stat-k {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: #64748b;
    }
    .srs-stat-v {
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.2;
    }

    @media (max-width: 991.98px) {
        .srs-meta-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .srs-grid-header {
            display: none;
        }
        .srs-item-card {
            grid-template-columns: 32px minmax(0, 1fr);
            gap: .65rem;
            padding: .9rem;
        }
        .srs-col-check {
            grid-row: 1;
            grid-column: 1;
            align-self: start;
            padding-top: .15rem;
        }
        .srs-col-prod {
            grid-row: 1;
            grid-column: 2;
        }
        .srs-col-req,
        .srs-col-wh,
        .srs-col-qty,
        .srs-col-status {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding-top: .45rem;
            border-top: 1px dashed #e2e8f0;
        }
        .srs-mobile-label {
            display: block;
            margin-bottom: 0;
        }
        .srs-qty-stepper {
            max-width: 145px;
        }
    }

    @media (max-width: 575.98px) {
        .srs-hero { padding: 1rem; }
        .srs-hero-back { width: 100%; justify-content: center; }
        .srs-dispatch-actions { width: 100%; display: flex; flex-direction: column; gap: .5rem; }
        .srs-dispatch-actions .btn { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $isOwner = $user->isOwner();
    $isPending = $stockRequest->isPending();
    $isApproved = $stockRequest->status === 'approved';
    $isRejected = $stockRequest->status === 'rejected';

    $statusMeta = match ($stockRequest->status) {
        'pending'  => ['bg' => '#fef3c7', 'color' => '#b45309', 'icon' => 'bi-hourglass-split', 'label' => 'Pending Owner Review'],
        'approved' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'bi-check-circle-fill', 'label' => 'Approved & Dispatched'],
        'rejected' => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'icon' => 'bi-x-circle-fill', 'label' => 'Rejected'],
        default    => ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'bi-info-circle', 'label' => ucfirst($stockRequest->status)],
    };
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-xl-11">

        {{-- ── Executive Hero Header ── --}}
        <div class="srs-hero">
            <div style="flex:1; min-width:220px;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="srs-hero-title">Stock Request #{{ $stockRequest->id }}</h1>
                    <span class="badge px-2 py-1" style="background:{{ $statusMeta['bg'] }}; color:{{ $statusMeta['color'] }}; font-size:.72rem;">
                        <i class="bi {{ $statusMeta['icon'] }} me-1"></i>{{ $statusMeta['label'] }}
                    </span>
                </div>
                <p class="srs-hero-sub">
                    @if($isOwner && $isPending)
                        Review products requested by <strong>{{ $stockRequest->shop->shop_name }}</strong>, regulate quantities as needed, select items to include, and dispatch directly to the branch.
                    @else
                        Replenishment request for <strong>{{ $stockRequest->shop->shop_name }}</strong> submitted by {{ $stockRequest->requester->name }}.
                    @endif
                </p>
            </div>
            <a href="{{ route('stock-requests.index') }}" class="srs-hero-back">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Requests</span>
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:12px;">
                <div class="fw-700 mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Could not dispatch:</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="srs-card mb-4">
            {{-- Request Metadata Strip --}}
            <div class="srs-meta-strip">
                <div>
                    <div class="srs-meta-k">Destination Shop</div>
                    <div class="srs-meta-v"><i class="bi bi-shop text-primary me-1"></i>{{ $stockRequest->shop->shop_name }}</div>
                </div>
                <div>
                    <div class="srs-meta-k">Requested By</div>
                    <div class="srs-meta-v"><i class="bi bi-person-badge text-primary me-1"></i>{{ $stockRequest->requester->name }}</div>
                </div>
                <div>
                    <div class="srs-meta-k">Request Date</div>
                    <div class="srs-meta-v"><i class="bi bi-calendar3 text-primary me-1"></i>{{ $stockRequest->request_date->format('M d, Y') }}</div>
                </div>
                <div>
                    <div class="srs-meta-k">Total Requested</div>
                    <div class="srs-meta-v text-primary">{{ $stockRequest->items->count() }} product(s) &middot; {{ number_format($stockRequest->items->sum('quantity')) }} units</div>
                </div>
            </div>

            @if($stockRequest->notes)
                <div class="px-3 px-md-4 pt-3">
                    <div class="p-3 rounded-3 bg-light border small">
                        <strong class="text-dark"><i class="bi bi-chat-left-text text-primary me-1"></i>{{ $isRejected ? 'Notes / Rejection Reason:' : 'Request Notes:' }}</strong>
                        <span class="text-secondary ms-1">{{ $stockRequest->notes }}</span>
                    </div>
                </div>
            @endif

            @if($isApproved && $stockRequest->transfer)
                <div class="px-3 px-md-4 pt-3">
                    <div class="alert alert-success border-0 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2 small" style="border-radius:12px;">
                        <div>
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <strong>Dispatched via Stock Transfer #{{ $stockRequest->transfer->id }}</strong>
                            ({{ $stockRequest->transfer->items->count() }} product(s) &middot; {{ number_format($stockRequest->transfer->items->sum('quantity')) }} units dispatched to {{ $stockRequest->shop->shop_name }}).
                        </div>
                        <a href="{{ route('stock-transfers.show', $stockRequest->transfer) }}" class="btn btn-sm btn-success fw-600">
                            <i class="bi bi-truck me-1"></i>View Stock Transfer #{{ $stockRequest->transfer->id }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- ══════════════════════════════════════════════════════════════════
                 OWNER INTERACTIVE SELECTIVE & REGULATED DIRECT DISPATCH MODE
                 ══════════════════════════════════════════════════════════════════ --}}
            @if($isOwner && $isPending)
                <form method="POST" action="{{ route('stock-requests.approve', $stockRequest) }}" id="selectiveDispatchForm">
                    @csrf
                    <input type="hidden" name="selective_dispatch" value="1">

                    {{-- Toolbar --}}
                    <div class="srs-toolbar">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="form-check mb-0 d-flex align-items-center gap-2">
                                <input class="form-check-input srs-check-lg mt-0" type="checkbox" id="masterSelectAvailable">
                                <label class="form-check-label fw-700 small text-dark" for="masterSelectAvailable" style="cursor:pointer;">
                                    Select All Available
                                </label>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" id="selectedCounterBadge">
                                0 of {{ $stockRequest->items->count() }} selected
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-primary fw-600" id="btnSelectAvailableOnly">
                                <i class="bi bi-check2-all me-1"></i>Select Available
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary fw-600" id="btnClearSelection">
                                <i class="bi bi-x-lg me-1"></i>Clear All
                            </button>
                        </div>
                    </div>

                    <div class="p-3 p-md-4">
                        <div class="alert alert-info border-0 py-2 px-3 mb-3 small d-flex align-items-center gap-2" style="background:#f0f9ff; color:#0369a1; border-left:4px solid #0284c7 !important; border-radius:10px;">
                            <i class="bi bi-sliders fs-5 flex-shrink-0"></i>
                            <div>
                                Check the products you want to dispatch, regulate each quantity up to available Main Store stock, and click <strong>Dispatch Selected Products</strong>. Unchecked products will <strong>not</strong> be included in the dispatch.
                            </div>
                        </div>

                        {{-- Desktop Grid Header --}}
                        <div class="srs-grid-header">
                            <div class="text-center">Pick</div>
                            <div>Requested Product</div>
                            <div class="text-center">Requested</div>
                            <div class="text-center">Main Store Stock</div>
                            <div class="text-center">Regulated Dispatch Qty</div>
                            <div class="text-end">Dispatch Status</div>
                        </div>

                        {{-- Unified Responsive Item Rows --}}
                        <div class="d-flex flex-column gap-3" id="dispatchItemsList">
                            @foreach($stockRequest->items as $reqItem)
                                @php
                                    $item = $reqItem->item;
                                    $unit = $item?->unit_symbol ?? 'pc';
                                    $avail = $item ? (int) $item->getTotalMainStock() : 0;
                                    $requested = (int) $reqItem->quantity;
                                    $hasStock = $avail > 0;
                                    $defaultDispatchQty = $hasStock ? min($requested, $avail) : 0;
                                    $isPartialAvail = $hasStock && $avail < $requested;
                                @endphp
                                <div class="srs-item-card {{ $hasStock ? 'is-selected' : 'is-unavailable' }}"
                                     data-item-row="1"
                                     data-req-item-id="{{ $reqItem->id }}"
                                     data-item-name="{{ $item?->item_name ?? 'Item' }}"
                                     data-unit="{{ $unit }}"
                                     data-requested="{{ $requested }}"
                                     data-available="{{ $avail }}">

                                    {{-- Checkbox --}}
                                    <div class="srs-col-check text-md-center">
                                        <input type="checkbox"
                                               class="form-check-input srs-check-lg item-dispatch-check"
                                               name="selected_items[]"
                                               value="{{ $reqItem->id }}"
                                               {{ $hasStock ? 'checked' : 'disabled' }}>
                                    </div>

                                    {{-- Product Details --}}
                                    <div class="srs-col-prod">
                                        <div class="fw-700 text-dark" style="font-size:.92rem;">{{ $item?->item_name ?? 'Item' }}</div>
                                        <div class="small text-muted">
                                            {{ $item?->category?->category_name ?? 'General' }}
                                            @if($item?->brand) &middot; {{ $item->brand }} @endif
                                        </div>
                                    </div>

                                    {{-- Shop Admin Requested Qty --}}
                                    <div class="srs-col-req text-md-center">
                                        <span class="srs-mobile-label">Requested Qty</span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-700" style="font-size:.78rem;">
                                            {{ number_format($requested) }} {{ $unit }}
                                        </span>
                                    </div>

                                    {{-- Main Store Available --}}
                                    <div class="srs-col-wh text-md-center">
                                        <span class="srs-mobile-label">Main Store Stock</span>
                                        @if($avail >= $requested)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-700" style="font-size:.76rem;">
                                                <i class="bi bi-check-circle-fill me-1"></i>{{ number_format($avail) }} {{ $unit }}
                                            </span>
                                        @elseif($avail > 0)
                                            <div>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 fw-700" style="font-size:.76rem;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ number_format($avail) }} {{ $unit }}
                                                </span>
                                                <div class="text-warning-emphasis mt-1" style="font-size:.66rem; font-weight:600;">Less than requested</div>
                                            </div>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-700" style="font-size:.75rem;">
                                                <i class="bi bi-x-circle-fill me-1"></i>0 {{ $unit }} (Finished)
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Regulated Quantity Input --}}
                                    <div class="srs-col-qty">
                                        <span class="srs-mobile-label">Dispatch Qty</span>
                                        @if($hasStock)
                                            <div class="d-flex flex-column align-items-end align-items-md-center">
                                                <div class="srs-qty-stepper">
                                                    <button type="button" class="btn-reg-minus" aria-label="Decrease">&minus;</button>
                                                    <input type="number"
                                                           name="quantities[{{ $reqItem->id }}]"
                                                           class="regulated-qty-input"
                                                           min="1"
                                                           max="{{ $avail }}"
                                                           value="{{ old('quantities.' . $reqItem->id, $defaultDispatchQty) }}"
                                                           required>
                                                    <button type="button" class="btn-reg-plus" aria-label="Increase">+</button>
                                                </div>
                                                @if($isPartialAvail)
                                                    <div class="text-muted mt-1" style="font-size:.66rem;">Auto-capped to {{ $avail }} {{ $unit }}</div>
                                                @elseif($avail > $requested)
                                                    <div class="text-muted mt-1" style="font-size:.66rem;">Max: {{ number_format($avail) }} {{ $unit }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="small text-danger fw-600">Unavailable</span>
                                        @endif
                                    </div>

                                    {{-- Live Row Inclusion Status --}}
                                    <div class="srs-col-status text-md-end">
                                        <span class="srs-mobile-label">Status</span>
                                        <span class="row-status-pill">
                                            @if($hasStock)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-truck me-1"></i>Will Dispatch
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    Out of Stock
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Live Dispatch Summary & Direct Action Bar --}}
                        <div class="srs-dispatch-bar mt-4">
                            <div class="srs-dispatch-stats">
                                <div>
                                    <div class="srs-stat-k">Selected to Dispatch</div>
                                    <div class="srs-stat-v text-success" id="dispatchSelectedCount">0 products</div>
                                </div>
                                <div class="border-start ps-3">
                                    <div class="srs-stat-k">Total Units to Send</div>
                                    <div class="srs-stat-v text-primary" id="dispatchTotalUnits">0 units</div>
                                </div>
                                <div class="border-start ps-3">
                                    <div class="srs-stat-k">Not Selected (Excluded)</div>
                                    <div class="srs-stat-v text-secondary" id="dispatchExcludedCount">0 products</div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap srs-dispatch-actions">
                                <button type="button" class="btn btn-outline-danger fw-600" data-bs-toggle="collapse" data-bs-target="#rejectCollapse">
                                    <i class="bi bi-x-circle me-1"></i>Reject Request
                                </button>
                                <button type="button" class="btn btn-success fw-700 px-4 shadow-sm" id="btnDispatchSelected">
                                    <i class="bi bi-send-check-fill me-1"></i>Dispatch Selected to {{ $stockRequest->shop->shop_name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Reject Request Collapse --}}
                <div class="collapse px-3 px-md-4 pb-4" id="rejectCollapse">
                    <div class="p-3 rounded-3 border border-danger-subtle" style="background:#fff5f5;">
                        <form method="POST" action="{{ route('stock-requests.reject', $stockRequest) }}">
                            @csrf
                            <label class="form-label fw-700 small text-danger">Reason for Rejecting Entire Request <span class="text-danger">*</span></label>
                            <textarea name="reject_reason" class="form-control mb-2" rows="2" placeholder="Explain why none of the requested products can be dispatched right now..." required></textarea>
                            <button type="submit" class="btn btn-danger btn-sm fw-600">
                                <i class="bi bi-x-circle me-1"></i>Confirm Rejection
                            </button>
                        </form>
                    </div>
                </div>

            @else
                {{-- ══════════════════════════════════════════════════════════════════
                     READ-ONLY VIEW (SHOP ADMIN, OR ALREADY APPROVED / REJECTED REQUEST)
                     Note: Main Warehouse stock is NEVER shown to Shop Admins.
                     ══════════════════════════════════════════════════════════════════ --}}
                <div class="p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <h6 class="fw-700 mb-0 text-dark">
                            <i class="bi bi-boxes text-primary me-1"></i>Requested Products
                        </h6>
                        <span class="badge bg-light text-secondary border">{{ $stockRequest->items->count() }} product(s)</span>
                    </div>

                    {{-- Desktop Table --}}
                    <div class="d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-center">Requested Qty</th>
                                    @if($isApproved && $stockRequest->transfer)
                                        <th class="text-center">Dispatched Qty</th>
                                        <th class="text-end">Dispatch Result</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockRequest->items as $idx => $reqItem)
                                    @php
                                        $unit = $reqItem->item?->unit_symbol ?? 'pc';
                                        $dispatchedQty = (int) ($dispatchedByItem[$reqItem->item_id] ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="text-muted fw-600">{{ $idx + 1 }}</td>
                                        <td class="fw-700 text-dark">{{ $reqItem->item?->item_name ?? 'Item' }}</td>
                                        <td class="small text-muted">{{ $reqItem->item?->category?->category_name ?? 'General' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-700">
                                                {{ number_format($reqItem->quantity) }} {{ $unit }}
                                            </span>
                                        </td>
                                        @if($isApproved && $stockRequest->transfer)
                                            <td class="text-center">
                                                @if($dispatchedQty > 0)
                                                    <strong class="text-success">{{ number_format($dispatchedQty) }} {{ $unit }}</strong>
                                                @else
                                                    <span class="text-muted">0 {{ $unit }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($dispatchedQty === 0)
                                                    <span class="badge bg-secondary-subtle text-secondary border">Not Included in Dispatch</span>
                                                @elseif($dispatchedQty === (int) $reqItem->quantity)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Dispatched (Full)
                                                    </span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                        <i class="bi bi-sliders me-1"></i>Dispatched (Regulated)
                                                    </span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Zero-Scroll Cards --}}
                    <div class="d-md-none d-flex flex-column gap-2">
                        @foreach($stockRequest->items as $reqItem)
                            @php
                                $unit = $reqItem->item?->unit_symbol ?? 'pc';
                                $dispatchedQty = (int) ($dispatchedByItem[$reqItem->item_id] ?? 0);
                            @endphp
                            <div class="p-3 rounded-3 border bg-light">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <div>
                                        <div class="fw-700 text-dark">{{ $reqItem->item?->item_name ?? 'Item' }}</div>
                                        <div class="small text-muted">{{ $reqItem->item?->category?->category_name ?? 'General' }}</div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        Req: {{ number_format($reqItem->quantity) }} {{ $unit }}
                                    </span>
                                </div>
                                @if($isApproved && $stockRequest->transfer)
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small">
                                        <span>Dispatched: <strong class="{{ $dispatchedQty > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($dispatchedQty) }} {{ $unit }}</strong></span>
                                        @if($dispatchedQty === 0)
                                            <span class="badge bg-secondary-subtle text-secondary border">Not Included</span>
                                        @elseif($dispatchedQty === (int) $reqItem->quantity)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Dispatched</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Regulated</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@if($isOwner && $isPending)
@push('scripts')
<script>
(function() {
    const form = document.getElementById('selectiveDispatchForm');
    if (!form) return;

    const listEl = document.getElementById('dispatchItemsList');
    const masterCheck = document.getElementById('masterSelectAvailable');
    const btnDispatch = document.getElementById('btnDispatchSelected');
    const totalRowsCount = listEl.querySelectorAll('[data-item-row="1"]').length;

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function syncRowState(row) {
        const avail = parseInt(row.dataset.available || '0', 10);
        const requested = parseInt(row.dataset.requested || '0', 10);
        const unit = row.dataset.unit || 'pc';
        const cb = row.querySelector('.item-dispatch-check');
        const qtyInput = row.querySelector('.regulated-qty-input');
        const minusBtn = row.querySelector('.btn-reg-minus');
        const plusBtn = row.querySelector('.btn-reg-plus');
        const statusPill = row.querySelector('.row-status-pill');

        if (avail <= 0) {
            row.classList.remove('is-selected', 'is-excluded');
            row.classList.add('is-unavailable');
            return;
        }

        const isChecked = cb && cb.checked;

        if (isChecked) {
            row.classList.add('is-selected');
            row.classList.remove('is-excluded');
            if (qtyInput) {
                qtyInput.disabled = false;
                let val = parseInt(qtyInput.value, 10);
                if (isNaN(val) || val < 1) val = 1;
                if (val > avail) val = avail;
                qtyInput.value = val;

                if (statusPill) {
                    if (val !== requested) {
                        statusPill.innerHTML = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-sliders me-1"></i>Regulated (${val} ${escapeHtml(unit)})</span>`;
                    } else {
                        statusPill.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-truck me-1"></i>Will Dispatch</span>`;
                    }
                }
            }
            if (minusBtn) minusBtn.disabled = false;
            if (plusBtn) plusBtn.disabled = false;
        } else {
            row.classList.remove('is-selected');
            row.classList.add('is-excluded');
            if (qtyInput) qtyInput.disabled = true;
            if (minusBtn) minusBtn.disabled = true;
            if (plusBtn) plusBtn.disabled = true;
            if (statusPill) {
                statusPill.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-slash-circle me-1"></i>Not Included</span>`;
            }
        }
    }

    function updateSummary() {
        const rows = listEl.querySelectorAll('[data-item-row="1"]');
        let selectedCount = 0;
        let availableCount = 0;
        let totalUnits = 0;

        rows.forEach(function(row) {
            const avail = parseInt(row.dataset.available || '0', 10);
            const cb = row.querySelector('.item-dispatch-check');
            const qtyInput = row.querySelector('.regulated-qty-input');

            if (avail > 0) availableCount++;
            if (cb && cb.checked && !cb.disabled) {
                selectedCount++;
                totalUnits += Math.max(0, parseInt(qtyInput ? qtyInput.value : '0', 10));
            }
        });

        const excludedCount = Math.max(0, totalRowsCount - selectedCount);

        document.getElementById('selectedCounterBadge').textContent = `${selectedCount} of ${totalRowsCount} selected`;
        document.getElementById('dispatchSelectedCount').textContent = `${selectedCount} ${selectedCount === 1 ? 'product' : 'products'}`;
        document.getElementById('dispatchTotalUnits').textContent = `${totalUnits.toLocaleString()} ${totalUnits === 1 ? 'unit' : 'units'}`;
        document.getElementById('dispatchExcludedCount').textContent = `${excludedCount} ${excludedCount === 1 ? 'product' : 'products'}`;

        if (masterCheck) {
            masterCheck.checked = availableCount > 0 && selectedCount === availableCount;
            masterCheck.indeterminate = selectedCount > 0 && selectedCount < availableCount;
        }

        if (btnDispatch) {
            btnDispatch.disabled = selectedCount === 0;
        }
    }

    function syncAll() {
        listEl.querySelectorAll('[data-item-row="1"]').forEach(syncRowState);
        updateSummary();
    }

    // Master checkbox
    if (masterCheck) {
        masterCheck.addEventListener('change', function() {
            const checked = this.checked;
            listEl.querySelectorAll('.item-dispatch-check:not(:disabled)').forEach(function(cb) {
                cb.checked = checked;
            });
            syncAll();
        });
    }

    document.getElementById('btnSelectAvailableOnly')?.addEventListener('click', function() {
        listEl.querySelectorAll('.item-dispatch-check:not(:disabled)').forEach(function(cb) {
            cb.checked = true;
        });
        syncAll();
    });

    document.getElementById('btnClearSelection')?.addEventListener('click', function() {
        listEl.querySelectorAll('.item-dispatch-check:not(:disabled)').forEach(function(cb) {
            cb.checked = false;
        });
        syncAll();
    });

    // Individual row checkbox & stepper events
    $(listEl).on('change', '.item-dispatch-check', function() {
        syncRowState(this.closest('[data-item-row="1"]'));
        updateSummary();
    });

    $(listEl).on('click', '.btn-reg-minus', function() {
        const row = this.closest('[data-item-row="1"]');
        const input = row.querySelector('.regulated-qty-input');
        if (!input || input.disabled) return;
        const cur = parseInt(input.value, 10) || 1;
        if (cur > 1) {
            input.value = cur - 1;
            syncRowState(row);
            updateSummary();
        }
    });

    $(listEl).on('click', '.btn-reg-plus', function() {
        const row = this.closest('[data-item-row="1"]');
        const input = row.querySelector('.regulated-qty-input');
        if (!input || input.disabled) return;
        const avail = parseInt(row.dataset.available || '0', 10);
        const cur = parseInt(input.value, 10) || 0;
        if (cur < avail) {
            input.value = cur + 1;
            syncRowState(row);
            updateSummary();
        }
    });

    $(listEl).on('input change', '.regulated-qty-input', function() {
        const row = this.closest('[data-item-row="1"]');
        const avail = parseInt(row.dataset.available || '0', 10);
        let val = parseInt(this.value, 10);
        if (!isNaN(val)) {
            if (val > avail) this.value = avail;
            if (val < 1) this.value = 1;
        }
        syncRowState(row);
        updateSummary();
    });

    // Confirm & Dispatch directly from the current request form
    btnDispatch?.addEventListener('click', function() {
        const selectedRows = [];
        let excludedCount = 0;

        listEl.querySelectorAll('[data-item-row="1"]').forEach(function(row) {
            const cb = row.querySelector('.item-dispatch-check');
            const input = row.querySelector('.regulated-qty-input');
            if (cb && cb.checked && !cb.disabled && input) {
                selectedRows.push({
                    name: row.dataset.itemName,
                    qty: parseInt(input.value, 10) || 0,
                    requested: parseInt(row.dataset.requested, 10) || 0,
                    unit: row.dataset.unit || 'pc',
                });
            } else {
                excludedCount++;
            }
        });

        if (selectedRows.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Products Selected',
                    text: 'Please select at least one available product to dispatch.',
                    confirmButtonColor: '#0077b6'
                });
            }
            return;
        }

        const itemsHtml = selectedRows.map(function(r) {
            const regTag = r.qty !== r.requested ? ` <span class="text-primary">(regulated from ${r.requested})</span>` : '';
            return `<li class="text-start mb-1"><strong>${escapeHtml(r.name)}</strong>: <span class="text-success fw-bold">${r.qty} ${escapeHtml(r.unit)}</span>${regTag}</li>`;
        }).join('');

        const excludedNote = excludedCount > 0
            ? `<div class="mt-2 p-2 rounded bg-light border small text-muted">${excludedCount} unselected product(s) will <strong>not</strong> be included in this dispatch.</div>`
            : '';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Dispatch Selected Products?',
                html: `<div class="small text-muted mb-2">Dispatching directly to <strong>{{ e($stockRequest->shop->shop_name) }}</strong>:</div>
                       <ul class="small ps-3 mb-0">${itemsHtml}</ul>${excludedNote}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Dispatch Now',
                cancelButtonText: 'Cancel'
            }).then(function(res) {
                if (res.isConfirmed) {
                    form.submit();
                }
            });
        } else {
            form.submit();
        }
    });

    syncAll();
})();
</script>
@endpush
@endif
