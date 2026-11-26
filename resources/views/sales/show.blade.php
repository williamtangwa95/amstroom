@extends('layouts.app')
@section('title', 'Sale #SL-' . $sale->id)
@section('page-title', 'Sale Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">#SL-{{ $sale->id }}</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       SALE DETAILS — Executive Responsive UI
       ══════════════════════════════════════════════ */
    .sd-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 58%, #003a6c 100%);
        border-radius: 16px;
        padding: 1.15rem 1.35rem;
        color: #ffffff;
        box-shadow: 0 8px 24px rgba(0, 86, 145, .2);
        margin-bottom: 1.15rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .sd-hero-title {
        font-size: 1.18rem;
        font-weight: 800;
        margin: 0;
        color: #ffffff;
        letter-spacing: -.015em;
    }
    .sd-hero-sub {
        font-size: .78rem;
        color: rgba(255, 255, 255, .84);
        margin: .24rem 0 0;
    }
    .sd-hero-status {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .62rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .sd-hero-status.is-completed {
        background: rgba(16, 185, 129, .22);
        color: #a7f3d0;
        border: 1px solid rgba(110, 231, 183, .38);
    }
    .sd-hero-status.is-proforma {
        background: rgba(251, 191, 36, .24);
        color: #fef08a;
        border: 1px solid rgba(253, 224, 71, .38);
    }
    .sd-hero-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .48rem .95rem;
        border-radius: 10px;
        background: #ffffff;
        color: #005691;
        text-decoration: none;
        font-size: .8rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .14);
        border: none;
        transition: all .15s ease;
        white-space: nowrap;
    }
    .sd-hero-btn:hover {
        background: #f0f9ff;
        color: #003a6c;
        transform: translateY(-1px);
    }

    .sd-card {
        background: #ffffff;
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 16px;
        box-shadow: 0 3px 14px rgba(15, 23, 42, .045);
        overflow: hidden;
    }

    /* Handover Lock Callout */
    .sd-lock-callout {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .85rem;
        flex-wrap: wrap;
        padding: .9rem 1.05rem;
        border-radius: 12px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid #d97706;
        margin-bottom: 1.15rem;
    }
    .sd-lock-callout.is-unlocked {
        background: #f0f9ff;
        border-color: #bae6fd;
        border-left-color: #0284c7;
    }
    .sd-lock-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: rgba(217, 119, 6, .14);
        color: #b45309;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .sd-lock-callout.is-unlocked .sd-lock-icon {
        background: rgba(2, 132, 199, .14);
        color: #0369a1;
    }

    /* Metadata Grid */
    .sd-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    .sd-meta-tile {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        padding: .7rem .85rem;
        min-width: 0;
    }
    .sd-meta-k {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: .3rem;
        margin-bottom: .18rem;
    }
    .sd-meta-k i { color: #0077b6; }
    .sd-meta-v {
        font-size: .86rem;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
        line-height: 1.3;
    }

    /* Section Header */
    .sd-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .8rem;
    }
    .sd-section-title {
        font-size: .92rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: .45rem;
    }

    /* Mobile Item Card */
    .sd-item-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 3.5px solid #0077b6;
        border-radius: 12px;
        padding: .8rem .9rem;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .035);
    }

    /* Summary Total Banner */
    .sd-total-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .82rem 1rem;
        border-radius: 12px;
        background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
        border: 1px solid #bbf7d0;
        margin-top: .85rem;
    }
    .sd-total-label {
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: #065f46;
    }
    .sd-total-val {
        font-size: 1.08rem;
        font-weight: 800;
        color: #059669;
        font-variant-numeric: tabular-nums;
    }

    /* Action Footer */
    .sd-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding-top: 1rem;
        margin-top: 1.15rem;
        border-top: 1px solid #f1f5f9;
    }

    @media (max-width: 575.98px) {
        .sd-hero { padding: 1rem; }
        .sd-hero-actions { width: 100%; }
        .sd-hero-btn { width: 100%; }
        .sd-meta-grid { grid-template-columns: 1fr 1fr; gap: .55rem; }
        .sd-meta-tile { padding: .6rem .72rem; }
        .sd-footer { flex-direction: column-reverse; align-items: stretch; }
        .sd-footer > a,
        .sd-footer > div,
        .sd-footer .btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
@php
    $currentUser = auth()->user();
    $isOwner = $currentUser && $currentUser->isOwner();
    $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
    $isHandoverLocked = $sale->isLockedByHandover();
    $handoverReport = $isHandoverLocked ? $sale->handoverReport : null;
    $activeSaleUnlockReq = $isHandoverLocked ? $sale->activeHandoverEditRequest() : null;
    $canEditSale = $currentUser && $sale->canMutateUnderHandover($currentUser, 'edit');
    $canReturnSale = $currentUser && $sale->canMutateUnderHandover($currentUser, 'return');

    $visibleItems = $sale->items->where('parent_id', null)->filter(function ($item) use ($isOwner) {
        return !($isOwner && $item->is_admin_stock);
    });
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">

        {{-- ── Executive Hero Header ── --}}
        <div class="sd-hero">
            <div style="flex:1; min-width:200px;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="sd-hero-title">Sale #SL-{{ $sale->id }}</h1>
                    @if($sale->status === 'draft_proforma')
                        <span class="sd-hero-status is-proforma"><i class="bi bi-file-earmark-ruled"></i> Proforma / Draft</span>
                    @else
                        <span class="sd-hero-status is-completed"><i class="bi bi-check-circle-fill"></i> Completed</span>
                    @endif
                </div>
                <p class="sd-hero-sub">
                    {{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }} &middot; {{ $sale->sale_date->format('M d, Y') }} &middot; TZS {{ number_format($sale->report_revenue, 0) }}
                </p>
            </div>
            <div class="sd-hero-actions">
                @if($sale->status === 'completed')
                    <a href="{{ route('sales.receipt', $sale) }}" class="sd-hero-btn" title="Print Receipt">
                        <i class="bi bi-receipt"></i>
                        <span>Print Receipt</span>
                    </a>
                @else
                    <button class="sd-hero-btn opacity-75" disabled title="Receipt is only available for completed sales.">
                        <i class="bi bi-receipt"></i>
                        <span>Receipt</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- ── Main Details Card ── --}}
        <div class="sd-card">
            <div class="p-3 p-md-4">

                {{-- Handover Lock Callout --}}
                @if($isHandoverLocked)
                    <div class="sd-lock-callout {{ $activeSaleUnlockReq && $activeSaleUnlockReq->status === 'approved' ? 'is-unlocked' : '' }}">
                        <div class="d-flex align-items-start gap-2 flex-grow-1" style="min-width:0;">
                            <div class="sd-lock-icon">
                                <i class="bi {{ $activeSaleUnlockReq && $activeSaleUnlockReq->status === 'approved' ? 'bi-unlock-fill' : 'bi-lock-fill' }}"></i>
                            </div>
                            <div class="small" style="min-width:0;">
                                <div class="fw-700 text-dark">
                                    Handover Locked
                                    <span class="badge bg-dark-subtle text-dark border ms-1" style="font-size:.66rem;">{{ $handoverReport?->handover_no }}</span>
                                </div>
                                <div class="text-secondary mt-1" style="line-height:1.45;">
                                    @if($activeSaleUnlockReq && $activeSaleUnlockReq->status === 'approved')
                                        Owner unlocked this sale for <strong>{{ $activeSaleUnlockReq->action_label }}</strong>. It will automatically re-lock after completion.
                                    @elseif($activeSaleUnlockReq && $activeSaleUnlockReq->status === 'pending')
                                        <strong>{{ $activeSaleUnlockReq->action_label }}</strong> requested by {{ $activeSaleUnlockReq->requester?->name ?? 'Staff' }} — awaiting Owner approval.
                                        <div class="text-muted mt-1">Reason: "{{ $activeSaleUnlockReq->reason }}"</div>
                                    @elseif($isOwner)
                                        Included in a submitted Cash Handover. Any direct modifications will automatically recalculate handover totals.
                                    @else
                                        Editing or returning items is locked because this sale has been submitted in Cash Handover. Request Owner approval to proceed.
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            @if($isOwner && $activeSaleUnlockReq && $activeSaleUnlockReq->status === 'pending')
                                <button type="button" class="btn btn-sm btn-success fw-600 owner-approve-unlock-btn"
                                        data-request-id="{{ $activeSaleUnlockReq->id }}"
                                        data-label="Sale #SL-{{ $sale->id }} ({{ $activeSaleUnlockReq->action_label }})"
                                        data-reason="{{ $activeSaleUnlockReq->reason }}">
                                    <i class="bi bi-unlock-fill me-1"></i>Approve Unlock
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-600 owner-reject-unlock-btn"
                                        data-request-id="{{ $activeSaleUnlockReq->id }}"
                                        data-label="Sale #SL-{{ $sale->id }}">
                                    <i class="bi bi-x-lg me-1"></i>Reject
                                </button>
                            @elseif($handoverReport && !$currentUser?->isSeller())
                                <a href="{{ route('handovers.show', $handoverReport) }}" class="btn btn-sm btn-outline-dark fw-600" style="border-radius:8px; font-size:.76rem;">
                                    <i class="bi bi-file-earmark-text me-1"></i>View Handover
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Transaction Metadata Grid --}}
                <div class="sd-meta-grid">
                    <div class="sd-meta-tile">
                        <div class="sd-meta-k"><i class="bi bi-shop"></i> Branch / Shop</div>
                        <div class="sd-meta-v">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</div>
                    </div>
                    <div class="sd-meta-tile">
                        <div class="sd-meta-k"><i class="bi bi-calendar3"></i> Sale Date</div>
                        <div class="sd-meta-v">{{ $sale->sale_date->format('M d, Y') }}</div>
                    </div>
                    <div class="sd-meta-tile">
                        <div class="sd-meta-k"><i class="bi bi-person-badge"></i> Sold By</div>
                        <div class="sd-meta-v">{{ $sale->seller->name }}</div>
                    </div>
                    <div class="sd-meta-tile">
                        <div class="sd-meta-k"><i class="bi bi-credit-card"></i> Payment</div>
                        <div class="sd-meta-v">{{ str_replace('_', ' ', ucfirst($sale->payment_method)) }}</div>
                    </div>
                    <div class="sd-meta-tile" style="grid-column: 1 / -1;">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="min-w-0">
                                <div class="sd-meta-k"><i class="bi bi-person"></i> Customer</div>
                                <div class="sd-meta-v">
                                    {{ $sale->customer_name ?: 'Walk-in Customer' }}
                                    @if($sale->customer_id)
                                        <span class="text-muted fw-500 small ms-1">(ID: {{ $sale->customer_id }})</span>
                                    @endif
                                </div>
                                @if($sale->customer_po_box || $sale->deliver_to || $sale->terms_of_payment)
                                    <div class="small text-muted mt-1">
                                        @if($sale->customer_po_box) P.O. Box: {{ $sale->customer_po_box }} @endif
                                        @if($sale->deliver_to) &middot; Deliver To: {{ $sale->deliver_to }} @endif
                                        @if($sale->terms_of_payment) &middot; Terms: {{ $sale->terms_of_payment }} @endif
                                    </div>
                                @endif
                            </div>
                            @if($canEditSale)
                                <button type="button" class="btn btn-sm btn-outline-primary edit-customer-btn flex-shrink-0" style="border-radius:8px; font-size:.74rem;" data-id="{{ $sale->id }}" data-name="{{ $sale->customer_name }}" title="Add / Edit Customer Name">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items Sold Section --}}
                <div class="sd-section-head">
                    <h6 class="sd-section-title">
                        <i class="bi bi-bag-check-fill text-primary"></i>
                        <span>Items Sold</span>
                    </h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size:.7rem;">
                        {{ $visibleItems->count() }} {{ $visibleItems->count() === 1 ? 'item' : 'items' }}
                    </span>
                </div>

                {{-- Desktop Table (>= 768px) --}}
                <div class="d-none d-md-block">
                    <table class="table table-hover align-middle mb-0" style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="ps-3 py-2">Product</th>
                                <th class="text-center py-2">Qty</th>
                                <th class="text-end py-2">Selling Price</th>
                                <th class="text-end py-2">Subtotal</th>
                                <th class="text-center pe-3 py-2" style="width: 95px;">Components</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($visibleItems as $item)
                            @php
                                $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                                $displaySubtotal = $displayPrice * $item->quantity;
                                $canAddComponents = $item->allowsComponents();
                            @endphp
                            <tr>
                                <td class="ps-3 py-2 fw-600 text-dark">
                                    {{ $item->display_name }}
                                    @if($canAddComponents)
                                        <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem; border:1px solid #cbd5e1;">Bundle Item</span>
                                    @endif
                                </td>
                                <td class="text-center py-2">{{ $item->quantity }} <small class="text-muted">{{ $item->unit_symbol }}</small></td>
                                <td class="text-end py-2">TZS {{ number_format($displayPrice, 0) }}</td>
                                <td class="text-end py-2"><strong style="color:#059669;">TZS {{ number_format($displaySubtotal, 0) }}</strong></td>
                                <td class="text-center pe-3 py-2">
                                    @if($canAddComponents && $canEditSale)
                                        <button type="button" class="btn btn-xs btn-outline-success open-add-component-modal-btn py-0 px-1"
                                            data-sale-id="{{ $sale->id }}"
                                            data-item-id="{{ $item->id }}"
                                            data-item-name="{{ $item->display_name }}"
                                            title="Add Component">
                                            <i class="bi bi-plus-circle"></i>
                                        </button>
                                    @elseif($canAddComponents)
                                        <span class="text-muted" title="Locked by submitted Cash Handover"><i class="bi bi-lock-fill"></i></span>
                                    @else
                                        <span class="text-muted" style="font-size:.72rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                            @if($item->components->isNotEmpty())
                                @foreach($item->components as $component)
                                    <tr style="background:#fafcff;">
                                        <td class="py-2 small text-secondary" style="padding-left:1.75rem;">
                                            <span class="text-muted">└─</span> {{ $component->display_name }}
                                        </td>
                                        <td class="text-center py-2 small text-secondary">
                                            {{ $component->quantity }} <small>{{ $component->unit_symbol }}</small>
                                        </td>
                                        <td class="text-end py-2 small text-secondary fst-italic">Included</td>
                                        <td class="text-end py-2 small text-secondary fst-italic">Included</td>
                                        <td class="text-center pe-3 py-2">
                                            @if($canAddComponents && $canEditSale)
                                                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 remove-component-btn"
                                                    data-sale-id="{{ $sale->id }}"
                                                    data-component-id="{{ $component->id }}"
                                                    data-component-name="{{ $component->display_name }}"
                                                    title="Remove this component">
                                                    <i class="bi bi-trash" style="font-size:.7rem;"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Zero-Scroll Stacked Cards (< 768px) --}}
                <div class="d-md-none d-flex flex-column gap-2">
                    @foreach($visibleItems as $item)
                        @php
                            $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                            $displaySubtotal = $displayPrice * $item->quantity;
                            $canAddComponents = $item->allowsComponents();
                        @endphp
                        <div class="sd-item-card">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="min-w-0">
                                    <div class="fw-700 text-dark" style="font-size:.86rem; line-height:1.3; word-break:break-word;">
                                        {{ $item->display_name }}
                                    </div>
                                    @if($canAddComponents)
                                        <span class="badge rounded-pill mt-1" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-size:.63rem; font-weight:600;">
                                            <i class="bi bi-boxes me-1"></i>Bundle Item
                                        </span>
                                    @endif
                                </div>
                                @if($canAddComponents && $canEditSale)
                                    <button type="button" class="btn btn-sm btn-outline-success open-add-component-modal-btn flex-shrink-0 d-inline-flex align-items-center justify-content-center"
                                        style="width:28px; height:28px; padding:0; border-radius:8px;"
                                        data-sale-id="{{ $sale->id }}"
                                        data-item-id="{{ $item->id }}"
                                        data-item-name="{{ $item->display_name }}"
                                        title="Add Component">
                                        <i class="bi bi-plus-lg" style="font-size:.8rem;"></i>
                                    </button>
                                @elseif($canAddComponents)
                                    <span class="d-inline-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                        style="width:26px; height:26px; border-radius:7px; background:#f1f5f9;"
                                        title="Locked by submitted Cash Handover">
                                        <i class="bi bi-lock-fill" style="font-size:.72rem;"></i>
                                    </span>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center gap-2 pt-2" style="border-top:1px dashed #e2e8f0; font-size:.78rem;">
                                <span class="text-muted">
                                    <span class="badge bg-light text-dark border me-1" style="font-size:.7rem; font-weight:700;">{{ $item->quantity }} {{ $item->unit_symbol }}</span>
                                    &times; TZS {{ number_format($displayPrice, 0) }}
                                </span>
                                <strong style="color:#059669; font-size:.88rem; font-variant-numeric:tabular-nums;">TZS {{ number_format($displaySubtotal, 0) }}</strong>
                            </div>

                            @if($item->components->isNotEmpty())
                                <div class="mt-2 pt-2 d-flex flex-column gap-2" style="border-top:1px solid #f1f5f9;">
                                    @foreach($item->components as $component)
                                        <div class="d-flex justify-content-between align-items-center gap-2 rounded-2 px-2 py-1" style="background:#f8fafc; border:1px solid #edf2f7; font-size:.74rem;">
                                            <div class="text-secondary" style="word-break:break-word;">
                                                <span class="text-muted">└─</span> <strong class="text-dark">{{ $component->display_name }}</strong>
                                                <span class="text-muted ms-1">({{ $component->quantity }} {{ $component->unit_symbol }} &middot; Included)</span>
                                            </div>
                                            @if($canAddComponents && $canEditSale)
                                                <button type="button" class="btn btn-xs btn-outline-danger remove-component-btn flex-shrink-0 d-inline-flex align-items-center justify-content-center"
                                                    style="width:24px; height:24px; padding:0; border-radius:6px;"
                                                    data-sale-id="{{ $sale->id }}"
                                                    data-component-id="{{ $component->id }}"
                                                    data-component-name="{{ $component->display_name }}"
                                                    title="Remove this component">
                                                    <i class="bi bi-trash" style="font-size:.68rem;"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Total Paid / Realized Banner --}}
                <div class="sd-total-bar">
                    <span class="sd-total-label">{{ $isOwner ? 'Total Revenue Realized' : 'Total Amount Paid' }}</span>
                    <span class="sd-total-val">TZS {{ number_format($sale->report_revenue, 0) }}</span>
                </div>

                {{-- Footer Actions --}}
                <div class="sd-footer">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-custom fw-600 px-3">
                        <i class="bi bi-arrow-left me-1"></i>Back to Sales
                    </a>

                    <div class="d-flex gap-2 flex-wrap">
                        @if($sale->status === 'draft_proforma')
                            <form method="POST" action="{{ route('sales.convert', $sale) }}"
                                  onsubmit="return confirm('Convert this proforma to a completed sale? This will deduct stock.')"
                                  class="w-100 w-sm-auto">
                                @csrf
                                <button type="submit" class="btn btn-success fw-700 w-100">
                                    <i class="bi bi-check2-circle me-1"></i>Convert to Completed Sale
                                </button>
                            </form>
                        @else
                            @php
                                $hasPendingReturn = \App\Models\SaleReturn::where('sale_id', $sale->id)->where('status', 'pending')->exists();
                            @endphp
                            @if($hasPendingReturn)
                                <span class="badge bg-warning text-dark p-2 w-100 text-center"><i class="bi bi-hourglass-split me-1"></i> Return Request Pending</span>
                            @elseif($canReturnSale)
                                <a href="{{ route('sales-returns.create', $sale) }}" class="btn btn-danger fw-600 px-3">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Return Items / Refund
                                </a>
                            @elseif($activeSaleUnlockReq && $activeSaleUnlockReq->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 text-center" style="font-size:.76rem;">
                                    <i class="bi bi-hourglass-split me-1"></i>{{ $activeSaleUnlockReq->action_label }} Request Pending Owner Approval
                                </span>
                            @else
                                <button type="button" class="btn btn-warning text-dark fw-700 px-3 shadow-sm request-handover-edit-btn"
                                        style="border-radius:10px;"
                                        data-record-type="sale"
                                        data-record-id="{{ $sale->id }}"
                                        data-record-label="Sale #SL-{{ $sale->id }}"
                                        data-handover-no="{{ $handoverReport?->handover_no }}"
                                        data-default-action="return">
                                    <i class="bi bi-shield-lock-fill me-1"></i>Request Edit / Return from Owner
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editCustomerForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-700" id="editCustomerModalLabel"><i class="bi bi-person-gear me-2" style="color:var(--accent);"></i>Update Customer Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalCustomerName" class="form-label fw-600">Customer Name</label>
                        <input type="text" name="customer_name" id="modalCustomerName" class="form-control" placeholder="e.g. John Doe / Company Name" autofocus>
                        <small class="text-muted">Enter or update the customer name for this sale transaction.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Component Modal -->
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addComponentForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-700" id="addComponentModalLabel">
                        <i class="bi bi-plus-circle-dotted me-2" style="color:var(--accent);"></i>Add Component to Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600 mb-1" style="font-size:.82rem;">Parent Product</label>
                        <input type="text" id="modalTargetItemName" class="form-control form-control-sm" readonly disabled style="background:var(--input-bg);">
                    </div>
                    <div class="mb-3">
                        <label for="modalComponentSelect" class="form-label fw-600 mb-1" style="font-size:.82rem;">Select Component Item *</label>
                        <select name="component_item_id" id="modalComponentSelect" class="form-select form-select-sm" required style="width:100%;">
                            <option value="" disabled selected>Loading available items...</option>
                        </select>
                        <div id="modalStockAlert" class="mt-1" style="font-size:.75rem; color:var(--text-secondary);"></div>
                    </div>
                    <div class="mb-3">
                        <label for="modalComponentQty" class="form-label fw-600 mb-1" style="font-size:.82rem;">Quantity *</label>
                        <input type="number" name="quantity" id="modalComponentQty" class="form-control form-control-sm" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm" id="modalSubmitComponentBtn">
                        <i class="bi bi-check-lg me-1"></i> Add Component
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('partials.handover_lock_modals')
@endsection

@push('scripts')
<script>
    $(() => {
        $(document).on('click', '.edit-customer-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('id');
            var customerName = $(this).data('name') || '';
            var actionUrl = "{{ url('sales') }}/" + saleId + "/customer";

            $('#editCustomerForm').attr('action', actionUrl);
            $('#modalCustomerName').val(customerName);
            $('#editCustomerModal').modal('show');
        });

        $(document).on('click', '.open-add-component-modal-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('sale-id');
            var itemId = $(this).data('item-id');
            var itemName = $(this).data('item-name');

            $('#modalTargetItemName').val(itemName);
            $('#modalComponentQty').val(1);
            $('#modalStockAlert').html('');
            $('#modalSubmitComponentBtn').prop('disabled', true);

            var actionUrl = "{{ url('sales') }}/" + saleId + "/items/" + itemId + "/components";
            $('#addComponentForm').attr('action', actionUrl);

            var select = $('#modalComponentSelect');
            select.html('<option value="" disabled selected>Loading available items...</option>');

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            $('#addComponentModal').modal('show');

            $.ajax({
                url: "{{ url('sales') }}/" + saleId + "/items/" + itemId + "/available-components",
                method: 'GET',
                success: function(res) {
                    if (res.success && res.items && res.items.length > 0) {
                        var options = '<option value="" disabled selected>Search / select item...</option>';
                        res.items.forEach(function(item) {
                            var escapedName = $('<div>').text(item.item_name).html();
                            options += '<option value="' + item.id + '" data-stock="' + item.stock + '">' + escapedName + ' (' + item.brand + ') [Stock: ' + item.stock + ']</option>';
                        });
                        select.html(options);
                        $('#modalSubmitComponentBtn').prop('disabled', false);
                    } else {
                        select.html('<option value="" disabled selected>No available items in stock</option>');
                        $('#modalStockAlert').html('<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>No stock available for components in this store.</span>');
                    }

                    select.select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $('#addComponentModal'),
                        width: '100%'
                    });
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load items.';
                    select.html('<option value="" disabled selected>Unavailable</option>');
                    $('#modalStockAlert').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + $('<div>').text(msg).html() + '</span>');
                }
            });
        });

        $('#modalComponentSelect').on('change', function() {
            var opt = $(this).find('option:selected');
            var stock = opt.data('stock');
            if (stock !== undefined) {
                $('#modalStockAlert').html('<span class="text-success"><i class="bi bi-box-seam me-1"></i>Available in stock: <strong>' + stock + '</strong></span>');
                $('#modalComponentQty').attr('max', stock);
            }
        });

        $('#addComponentForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = $('#modalSubmitComponentBtn');
            var origHtml = submitBtn.html();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Adding...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(res) {
                    submitBtn.prop('disabled', false).html(origHtml);
                    $('#addComponentModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Component Added',
                        text: res.message || 'Component added successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(origHtml);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add component.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        });

        $(document).on('click', '.remove-component-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('sale-id');
            var compId = $(this).data('component-id');
            var compName = $(this).data('component-name') || 'this component';

            Swal.fire({
                title: 'Remove Component?',
                text: 'Are you sure you want to remove "' + compName + '" from this sale? The quantity will be restored to inventory stock.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, remove it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/components/" + compId,
                        method: 'DELETE',
                        data: { _token: "{{ csrf_token() }}" },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed',
                                text: res.message || 'Component removed successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to remove component.';
                            Swal.fire({ icon: 'error', title: 'Error', text: msg });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
