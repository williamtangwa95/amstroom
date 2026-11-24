@extends('layouts.app')
@section('title', 'Transfer #' . $stockTransfer->id)
@section('page-title', 'Transfer Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
<li class="breadcrumb-item active">Transfer #{{ $stockTransfer->id }}</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       STOCK TRANSFER DETAILS — Executive Responsive UI
       ══════════════════════════════════════════════ */
    .st-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        border-radius: 14px;
        padding: 1.05rem 1.35rem;
        color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 86, 145, .2);
        margin-bottom: 1.25rem;
    }
    .st-hero-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .st-back-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .65rem;
        border-radius: 8px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .18);
        color: #ffffff;
        text-decoration: none;
        font-size: .74rem;
        font-weight: 600;
        transition: background .15s ease;
        flex-shrink: 0;
    }
    .st-back-link:hover {
        background: rgba(255, 255, 255, .22);
        color: #ffffff;
    }
    .st-hero-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -.01em;
        line-height: 1.25;
    }
    .st-route-flow {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .4rem;
        margin-top: .32rem;
        font-size: .76rem;
        color: rgba(255, 255, 255, .86);
    }
    .st-route-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .14rem .5rem;
        border-radius: 6px;
        background: rgba(255, 255, 255, .12);
        color: #ffffff;
        font-weight: 600;
    }
    .st-hero-status {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .62rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .st-hero-status.is-received { background: rgba(16, 185, 129, .22); color: #a7f3d0; border: 1px solid rgba(110, 231, 183, .35); }
    .st-hero-status.is-rejected { background: rgba(239, 68, 68, .25);  color: #fecaca; border: 1px solid rgba(252, 165, 165, .35); }
    .st-hero-status.is-partial  { background: rgba(56, 189, 248, .22); color: #bae6fd; border: 1px solid rgba(125, 211, 252, .35); }
    .st-hero-status.is-pending  { background: rgba(245, 158, 11, .24); color: #fef08a; border: 1px solid rgba(253, 224, 71, .35); }

    .st-hero-date-box {
        text-align: right;
        padding-left: 1rem;
        border-left: 1px solid rgba(255, 255, 255, .18);
        flex-shrink: 0;
    }
    .st-hero-date-label {
        font-size: .65rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: rgba(255, 255, 255, .68);
        font-weight: 600;
    }
    .st-hero-date-val {
        font-size: .88rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.25;
    }
    .st-hero-wait {
        font-size: .69rem;
        color: #fde68a;
        font-weight: 600;
        margin-top: .12rem;
    }

    /* ── KPI Strip ── */
    .st-kpi-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 13px;
        padding: .85rem .95rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
        display: flex;
        align-items: center;
        gap: .7rem;
        height: 100%;
        overflow: hidden;
    }
    .st-kpi-body {
        flex: 1;
        min-width: 0;
    }
    .st-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .st-kpi-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin: 0;
        line-height: 1.25;
    }
    .st-kpi-val {
        font-size: 1rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
        margin-top: .12rem;
        overflow-wrap: break-word;
        word-break: break-word;
    }
    .st-kpi-val-money {
        font-size: clamp(.82rem, 2.5vw, .96rem);
        letter-spacing: -.01em;
    }
    .st-kpi-sub {
        font-size: .68rem;
        color: #64748b;
        margin-top: .08rem;
        line-height: 1.25;
    }

    /* ── Rejection Callout Banner ── */
    .st-rejection-banner {
        background: #ffffff;
        border: 1px solid #fecaca;
        border-left: 4px solid #dc2626;
        border-radius: 12px;
        padding: .9rem 1.15rem;
        box-shadow: 0 2px 10px rgba(220, 38, 38, .06);
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        margin-bottom: 1.25rem;
    }
    .st-rejection-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    /* ── Standard Section Cards ── */
    .st-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .st-card-header {
        padding: .9rem 1.2rem;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .st-card-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        font-size: .9rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    .st-card-title-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(0, 119, 182, .1);
        color: #0077b6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        flex-shrink: 0;
    }

    /* Summary Meta Rows */
    .st-meta-list {
        padding: .25rem 1.2rem .75rem;
    }
    .st-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .62rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .8rem;
    }
    .st-meta-row:last-child {
        border-bottom: none;
    }
    .st-meta-key {
        display: inline-flex;
        align-items: center;
        gap: .48rem;
        color: #64748b;
        font-weight: 500;
        white-space: nowrap;
    }
    .st-meta-key i {
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
    .st-meta-val {
        font-weight: 600;
        color: var(--text-primary);
        text-align: right;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Progress Breakdown */
    .st-progress-body {
        padding: 1rem 1.2rem 1.15rem;
    }
    .st-progress-track {
        height: 9px;
        border-radius: 999px;
        background: #f1f5f9;
        overflow: hidden;
        display: flex;
    }
    .st-progress-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .5rem;
        margin-top: .85rem;
    }
    .st-prog-pill {
        padding: .48rem .55rem;
        border-radius: 9px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-align: center;
    }
    .st-prog-pill-num {
        font-size: .92rem;
        font-weight: 800;
        line-height: 1.1;
    }
    .st-prog-pill-lbl {
        font-size: .64rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        margin-top: .15rem;
    }

    /* ── Items Table ── */
    .st-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .st-table thead th {
        font-size: .68rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b !important;
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: .72rem .9rem !important;
        white-space: nowrap;
    }
    .st-table tbody td {
        padding: .85rem .9rem !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: .82rem;
        color: var(--text-primary);
    }
    .st-table tbody tr {
        transition: background-color .15s ease;
    }
    .st-table tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .st-row-pending td:first-child {
        box-shadow: inset 3px 0 0 #f59e0b;
    }
    .st-row-rejected td:first-child {
        box-shadow: inset 3px 0 0 #dc2626;
    }
    .st-row-received td:first-child {
        box-shadow: inset 3px 0 0 #10b981;
    }
    .st-row-pending {
        background-color: rgba(254, 243, 199, .24) !important;
    }
    .st-row-rejected {
        background-color: rgba(254, 226, 226, .26) !important;
    }

    .st-prod-cell {
        display: flex;
        align-items: flex-start;
        gap: .68rem;
        min-width: 185px;
    }
    .st-prod-thumb {
        width: 40px;
        height: 40px;
        border-radius: 9px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
        color: #64748b;
        font-size: 1rem;
    }
    .st-prod-thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 3px;
    }
    .st-prod-name {
        font-weight: 700;
        color: #0f172a;
        font-size: .84rem;
        line-height: 1.25;
    }
    .st-prod-meta {
        font-size: .72rem;
        color: #64748b;
        margin-top: .12rem;
    }
    .st-cat-badge {
        display: inline-flex;
        align-items: center;
        padding: .18rem .52rem;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        font-size: .71rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .st-qty-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        padding: .22rem .58rem;
        border-radius: 8px;
        background: rgba(0, 119, 182, .09);
        color: #005691;
        font-weight: 800;
        font-size: .82rem;
    }
    .st-price-main {
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }
    .st-price-sub {
        font-size: .69rem;
        color: #64748b;
        white-space: nowrap;
    }
    .st-reject-box {
        margin-top: .5rem;
        padding: .5rem .68rem;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #fecaca;
        font-size: .74rem;
    }

    /* Action Buttons */
    .st-act-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .28rem;
        padding: .32rem .62rem;
        border-radius: 8px;
        font-size: .74rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;
    }
    .st-act-approve {
        background: #10b981;
        color: #ffffff;
    }
    .st-act-approve:hover {
        background: #059669;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .st-act-reject {
        background: #ffffff;
        border-color: #fecaca;
        color: #dc2626;
    }
    .st-act-reject:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }
    .st-act-edit {
        background: #ffffff;
        border-color: #bae6fd;
        color: #0077b6;
    }
    .st-act-edit:hover {
        background: #0077b6;
        border-color: #0077b6;
        color: #ffffff;
    }
    .st-act-delete {
        background: #ffffff;
        border-color: #fecaca;
        color: #dc2626;
    }
    .st-act-delete:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }

    .st-table tfoot td {
        padding: .85rem .9rem !important;
        background: #f8fafc !important;
        border-top: 1px solid #e2e8f0;
        font-size: .82rem;
    }

    /* ── Modals Polish ── */
    .st-modal .modal-content {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .18);
        overflow: hidden;
    }
    .st-modal .modal-header {
        padding: 1rem 1.3rem;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
    }
    .st-modal-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .st-modal .modal-body {
        padding: 1.25rem 1.3rem;
    }
    .st-modal .modal-footer {
        padding: .85rem 1.3rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }

    @media (max-width: 767.98px) {
        .st-hero {
            padding: .9rem 1rem;
        }
        .st-hero-date-box {
            width: 100%;
            text-align: left;
            padding-left: 0;
            padding-top: .65rem;
            border-left: none;
            border-top: 1px solid rgba(255, 255, 255, .16);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            flex-wrap: wrap;
        }
        .st-card-header {
            padding: .8rem 1rem;
        }
        .st-toolbar-actions {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .st-toolbar-actions .btn {
            flex: 1 1 auto;
            justify-content: center;
        }
    }

    @media (max-width: 575.98px) {
        .st-kpi-card {
            padding: .7rem .75rem;
            gap: .55rem;
        }
        .st-kpi-icon {
            width: 34px;
            height: 34px;
            font-size: .92rem;
            border-radius: 8px;
        }
        .st-kpi-val {
            font-size: .9rem;
        }
        .st-kpi-val-money {
            font-size: .8rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $totalItems         = $stockTransfer->items->count();
    $receivedItems      = $stockTransfer->items->where('status', 'received')->count();
    $rejectedCount      = $stockTransfer->items->where('status', 'rejected')->count();
    $pendingItems       = $totalItems - $receivedItems - $rejectedCount;
    $totalQuantity      = (int) $stockTransfer->items->sum('quantity');
    $totalValue         = $stockTransfer->items->sum(fn($ti) => (float) $ti->quantity * (float) $ti->selling_price);
    $pctReceived        = $totalItems > 0 ? round(($receivedItems / $totalItems) * 100) : 0;
    $pctRejected        = $totalItems > 0 ? round(($rejectedCount / $totalItems) * 100) : 0;
    $isOwner            = auth()->user()->isOwner();
    $isShopAdmin        = auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop;

    $statusClass = match($stockTransfer->status) {
        'received'           => 'is-received',
        'rejected'           => 'is-rejected',
        'partially_received' => 'is-partial',
        default              => 'is-pending',
    };
    $statusLabel = match($stockTransfer->status) {
        'received'           => 'Fully Received',
        'rejected'           => 'Has Rejections',
        'partially_received' => 'Partially Received',
        default              => 'Pending Receipt',
    };
    $statusIcon = match($stockTransfer->status) {
        'received'           => 'bi-check-circle-fill',
        'rejected'           => 'bi-exclamation-triangle-fill',
        'partially_received' => 'bi-pie-chart-fill',
        default              => 'bi-hourglass-split',
    };

    // Pre-fetch existing shop stock prices in one query when Shop Admin has pending items
    $existingShopPrices = collect();
    if ($isShopAdmin && $pendingItems > 0) {
        $pendingItemIds = $stockTransfer->items->where('status', 'pending')->pluck('item_id')->unique()->values();
        $existingShopPrices = \App\Models\ShopStock::whereIn('item_id', $pendingItemIds)
            ->where('shop_id', $stockTransfer->to_shop)
            ->where('remaining_quantity', '>', 0)
            ->orderByDesc('date_received')
            ->get()
            ->unique('item_id')
            ->pluck('selling_price', 'item_id');
    }
@endphp

{{-- ── Executive Header Banner ── --}}
<div class="st-hero">
    <div class="st-hero-top">
        <div class="d-flex align-items-start gap-3 min-w-0 flex-grow-1 flex-wrap">
            <a href="{{ route('stock-transfers.index') }}" class="st-back-link">
                <i class="bi bi-arrow-left"></i>
                <span>All Transfers</span>
            </a>
            <div class="min-w-0 flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="st-hero-title">Stock Transfer #{{ $stockTransfer->id }}</h1>
                    <span class="st-hero-status {{ $statusClass }}">
                        <i class="bi {{ $statusIcon }}"></i> {{ $statusLabel }}
                    </span>
                </div>
                <div class="st-route-flow">
                    <span class="st-route-chip">
                        <i class="bi bi-building"></i> {{ $stockTransfer->from_store ?: 'Main Store' }}
                    </span>
                    <i class="bi bi-arrow-right"></i>
                    <span class="st-route-chip">
                        <i class="bi bi-shop"></i> {{ $stockTransfer->shop?->shop_name ?? 'N/A' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="st-hero-date-box">
            <div>
                <div class="st-hero-date-label">Dispatched On</div>
                <div class="st-hero-date-val">{{ $stockTransfer->transfer_date->format('M d, Y') }}</div>
            </div>
            @if($pendingItems > 0 && $stockTransfer->created_at)
                <div class="st-hero-wait">
                    <i class="bi bi-clock me-1"></i>Submitted {{ $stockTransfer->created_at->diffForHumans() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── KPI Summary Strip ── --}}
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="st-kpi-card">
            <div class="st-kpi-icon" style="background:rgba(0,119,182,.1); color:#0077b6;">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="st-kpi-body">
                <p class="st-kpi-label">Dispatched Items</p>
                <div class="st-kpi-val">{{ $totalItems }} {{ $totalItems === 1 ? 'Product' : 'Products' }}</div>
                <div class="st-kpi-sub">{{ number_format($totalQuantity) }} total units</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="st-kpi-card">
            <div class="st-kpi-icon" style="background:rgba(16,185,129,.12); color:#059669;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="st-kpi-body">
                <p class="st-kpi-label">Confirmed Receipt</p>
                <div class="st-kpi-val">{{ $receivedItems }} / {{ $totalItems }}</div>
                <div class="st-kpi-sub">{{ $pctReceived }}% completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="st-kpi-card">
            <div class="st-kpi-icon" style="background:rgba(217,119,6,.12); color:#d97706;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="st-kpi-body">
                <p class="st-kpi-label">Awaiting Action</p>
                <div class="st-kpi-val">{{ $pendingItems }} {{ $pendingItems === 1 ? 'Item' : 'Items' }}</div>
                <div class="st-kpi-sub">{{ $rejectedCount }} rejected</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="st-kpi-card">
            <div class="st-kpi-icon" style="background:rgba(99,102,241,.11); color:#4f46e5;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="st-kpi-body">
                <p class="st-kpi-label">Dispatch Valuation</p>
                <div class="st-kpi-val st-kpi-val-money" title="TZS {{ number_format($totalValue, 0) }}">TZS {{ number_format($totalValue, 0) }}</div>
                <div class="st-kpi-sub">Based on dispatch prices</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Rejection Attention Callout ── --}}
@if($rejectedCount > 0)
<div class="st-rejection-banner">
    <div class="st-rejection-icon">
        <i class="bi bi-exclamation-octagon-fill"></i>
    </div>
    <div class="min-w-0">
        <h6 class="fw-700 text-danger mb-1" style="font-size:.88rem;">Action Required: {{ $rejectedCount }} Rejected {{ $rejectedCount === 1 ? 'Item' : 'Items' }} Detected</h6>
        <p class="mb-0 text-secondary" style="font-size:.8rem; line-height:1.45;">
            <strong>{{ $rejectedCount }} item(s)</strong> in this transfer were rejected due to mismatch or stock discrepancy.
            @if($isOwner)
                As the <strong>Owner</strong>, you can edit item details, update quantities, delete rejected items, or add replacement items to update this dispatch.
            @else
                The Owner has been notified to review and adjust the rejected item(s).
            @endif
        </p>
    </div>
</div>
@endif

<div class="row g-4">
    {{-- ── Left Column: Summary & Receipt Progress ── --}}
    <div class="col-lg-4">
        {{-- Transfer Summary Card --}}
        <div class="st-card mb-3">
            <div class="st-card-header">
                <h6 class="st-card-title">
                    <span class="st-card-title-icon"><i class="bi bi-info-circle-fill"></i></span>
                    <span>Transfer Summary</span>
                </h6>
            </div>
            <div class="st-meta-list">
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-hash"></i>Transfer ID</span>
                    <span class="st-meta-val">#{{ $stockTransfer->id }}</span>
                </div>
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-building"></i>Source Store</span>
                    <span class="st-meta-val">{{ $stockTransfer->from_store ?: 'Main Store' }}</span>
                </div>
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-shop"></i>Destination Shop</span>
                    <span class="st-meta-val" style="color:#0077b6;">{{ $stockTransfer->shop?->shop_name ?? 'N/A' }}</span>
                </div>
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-person-badge"></i>Dispatched By</span>
                    <span class="st-meta-val">{{ $stockTransfer->approver?->name ?? 'System' }}</span>
                </div>
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-calendar3"></i>Transfer Date</span>
                    <span class="st-meta-val">{{ $stockTransfer->transfer_date->format('M d, Y') }}</span>
                </div>
                @if($stockTransfer->created_at)
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-clock"></i>Submitted At</span>
                    <span class="st-meta-val">{{ $stockTransfer->created_at->format('M d, Y · h:i A') }}</span>
                </div>
                @endif
                @if($stockTransfer->request)
                <div class="st-meta-row">
                    <span class="st-meta-key"><i class="bi bi-link-45deg"></i>From Request</span>
                    <span class="st-meta-val">
                        <a href="{{ route('stock-requests.show', $stockTransfer->request) }}" class="text-decoration-none fw-700" style="color:#0077b6;">
                            Request #{{ $stockTransfer->request_id }} <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.68rem;"></i>
                        </a>
                    </span>
                </div>
                @endif
            </div>
        </div>

        {{-- Receipt Progress Card --}}
        <div class="st-card">
            <div class="st-card-header">
                <h6 class="st-card-title">
                    <span class="st-card-title-icon" style="background:rgba(16,185,129,.12); color:#059669;"><i class="bi bi-bar-chart-steps"></i></span>
                    <span>Receipt Progress</span>
                </h6>
                <span class="fw-700" style="font-size:.8rem; color:{{ $pctReceived == 100 ? '#059669' : '#0077b6' }};">{{ $pctReceived }}%</span>
            </div>
            <div class="st-progress-body">
                <div class="st-progress-track">
                    @if($pctReceived > 0)
                        <div style="width:{{ $pctReceived }}%; background:#10b981; transition:width .3s ease;"></div>
                    @endif
                    @if($pctRejected > 0)
                        <div style="width:{{ $pctRejected }}%; background:#ef4444; transition:width .3s ease;"></div>
                    @endif
                </div>

                <div class="st-progress-stats">
                    <div class="st-prog-pill">
                        <div class="st-prog-pill-num text-success">{{ $receivedItems }}</div>
                        <div class="st-prog-pill-lbl">Received</div>
                    </div>
                    <div class="st-prog-pill">
                        <div class="st-prog-pill-num" style="color:#d97706;">{{ $pendingItems }}</div>
                        <div class="st-prog-pill-lbl">Pending</div>
                    </div>
                    <div class="st-prog-pill">
                        <div class="st-prog-pill-num text-danger">{{ $rejectedCount }}</div>
                        <div class="st-prog-pill-lbl">Rejected</div>
                    </div>
                </div>

                @if($isShopAdmin && $pendingItems > 0)
                <div class="mt-3 p-2 rounded-3" style="background:#f0f9ff; border:1px solid #bae6fd; font-size:.74rem; color:#0369a1; line-height:1.4;">
                    <i class="bi bi-lightbulb-fill me-1"></i>
                    Verify physical quantities, then approve items individually or check multiple rows to <strong>Approve Selected</strong> at once.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Right Column: Transfer Items Table ── --}}
    <div class="col-lg-8">
        <div class="st-card">
            <div class="st-card-header">
                <h6 class="st-card-title">
                    <span class="st-card-title-icon"><i class="bi bi-box-seam-fill"></i></span>
                    <span>Transfer Items</span>
                    <span class="badge bg-light text-secondary border rounded-pill" style="font-size:.68rem;">{{ $totalItems }}</span>
                </h6>

                <div class="d-flex align-items-center gap-2 flex-wrap st-toolbar-actions">
                    @if($isOwner)
                        <button type="button" class="btn btn-sm btn-accent d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="bi bi-plus-lg"></i> <span>Add Item</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" id="deleteTransferBtn"
                            data-url="{{ route('stock-transfers.destroy', $stockTransfer) }}"
                            data-id="{{ $stockTransfer->id }}"
                            title="Delete this transfer and return stock to warehouse">
                            <i class="bi bi-trash-fill"></i> <span>Delete Transfer</span>
                        </button>
                    @endif

                    @if($isShopAdmin && $pendingItems > 0)
                        <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" onclick="submitBulkApprove()" id="bulkApproveBtn" disabled style="border-radius:8px; font-weight:600;">
                            <i class="bi bi-check2-all"></i>
                            <span>Approve Selected (<span id="selectedCount">0</span>)</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="card-body p-0">
                <form method="POST" action="{{ route('stock-transfers.approve-bulk', $stockTransfer) }}" id="bulkForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    @if($isShopAdmin && $pendingItems > 0)
                                        <th style="width:42px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllCheckboxes(this)" title="Select all pending items">
                                        </th>
                                    @endif
                                    <th style="width:36px;">#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price (TZS)</th>
                                    <th>Status</th>
                                    @if($isShopAdmin || $isOwner)
                                        <th class="text-end">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockTransfer->items as $idx => $ti)
                                @php
                                    $rowStatusClass = $ti->status === 'pending'
                                        ? 'st-row-pending'
                                        : ($ti->status === 'rejected' ? 'st-row-rejected' : 'st-row-received');
                                    $lineTotal = (float) $ti->quantity * (float) $ti->selling_price;
                                    $existingPrice = $existingShopPrices->get($ti->item_id);
                                @endphp
                                <tr class="{{ $rowStatusClass }}">
                                    @if($isShopAdmin && $pendingItems > 0)
                                        <td class="text-center">
                                            @if($ti->status === 'pending')
                                                <input type="checkbox" class="form-check-input item-checkbox"
                                                       name="item_ids[]" value="{{ $ti->id }}"
                                                       data-name="{{ $ti->item?->item_name }}"
                                                       data-qty="{{ $ti->quantity }}"
                                                       data-price="{{ $ti->selling_price }}"
                                                       data-suggested-price="{{ $existingPrice ?? $ti->selling_price }}"
                                                       data-has-existing="{{ $existingPrice ? 'true' : 'false' }}"
                                                       onchange="updateSelectedCount()">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="fw-600 text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="st-prod-cell">
                                            <div class="st-prod-thumb">
                                                @if($ti->item?->image_path)
                                                    <img src="{{ asset('media/' . $ti->item->image_path) }}"
                                                         alt="{{ $ti->item->item_name }}"
                                                         class="img-lightbox"
                                                         onclick="openLightbox('{{ asset('media/' . $ti->item->image_path) }}', '{{ e($ti->item->item_name) }}')">
                                                @else
                                                    <i class="bi bi-box-seam"></i>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="st-prod-name">{{ $ti->item?->item_name ?? 'Unknown Product' }}</div>
                                                @if($ti->item?->brand || $ti->item?->model)
                                                    <div class="st-prod-meta">{{ trim(($ti->item->brand ?? '') . ' ' . ($ti->item->model ?? '')) }}</div>
                                                @endif

                                                @if($ti->status === 'rejected' && $ti->rejection_reason)
                                                    <div class="st-reject-box">
                                                        <div class="text-danger fw-700 mb-1">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Rejection Reason
                                                        </div>
                                                        <div class="text-dark">{{ $ti->rejection_reason }}</div>
                                                        @if($ti->rejecter)
                                                            <div class="text-muted mt-1" style="font-size:.69rem;">
                                                                Rejected by <strong>{{ $ti->rejecter->name }}</strong> on {{ $ti->rejected_at?->format('M d, Y H:i') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="st-cat-badge">{{ $ti->item?->category?->category_name ?? 'Uncategorized' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="st-qty-pill">{{ number_format($ti->quantity) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="st-price-main">{{ number_format($ti->selling_price, 0) }}</div>
                                        <div class="st-price-sub">Total: {{ number_format($lineTotal, 0) }}</div>
                                    </td>
                                    <td>
                                        @if($ti->status === 'received')
                                            <span class="badge-soft-success" style="font-size:.69rem;">
                                                <i class="bi bi-check-circle-fill"></i> Received
                                            </span>
                                            @if($ti->receiver)
                                                <div class="text-muted mt-1" style="font-size:.68rem; line-height:1.3;">
                                                    by {{ $ti->receiver->name }}<br>
                                                    {{ $ti->received_at?->format('M d, H:i') }}
                                                </div>
                                            @endif
                                        @elseif($ti->status === 'rejected')
                                            <span class="badge-soft-danger" style="font-size:.69rem;">
                                                <i class="bi bi-x-circle-fill"></i> Rejected
                                            </span>
                                        @else
                                            <span class="badge-soft-warning" style="font-size:.69rem;">
                                                <i class="bi bi-hourglass-split"></i> Pending
                                            </span>
                                        @endif
                                    </td>

                                    @if($isShopAdmin || $isOwner)
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1 align-items-center justify-content-end">
                                                @if($isShopAdmin && $ti->status === 'pending')
                                                    <button type="button" class="st-act-btn st-act-approve" title="Approve & Receive Item"
                                                            data-bs-toggle="modal" data-bs-target="#approveModal{{ $ti->id }}">
                                                        <i class="bi bi-check-lg"></i>
                                                        <span class="d-none d-xl-inline">Approve</span>
                                                    </button>
                                                    <button type="button" class="st-act-btn st-act-reject" title="Reject Item"
                                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $ti->id }}">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                @endif

                                                @if($isOwner)
                                                    @if(in_array($ti->status, ['pending', 'rejected']))
                                                    <button type="button" class="st-act-btn st-act-edit" title="Edit Item"
                                                            data-bs-toggle="modal" data-bs-target="#editItemModal{{ $ti->id }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    @endif
                                                    <button type="button" class="st-act-btn st-act-delete" title="Delete Item"
                                                            onclick="submitDeleteItem('{{ route('stock-transfers.delete-item', $ti) }}')">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                @endif

                                                @if($ti->status === 'received' && !$isOwner)
                                                    <span class="text-success" title="Confirmed"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        No items in this stock transfer.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($totalItems > 0)
                            <tfoot>
                                <tr class="fw-700">
                                    @if($isShopAdmin && $pendingItems > 0)
                                        <td></td>
                                    @endif
                                    <td></td>
                                    <td colspan="2" class="text-end text-secondary">Totals</td>
                                    <td class="text-center">
                                        <span class="st-qty-pill">{{ number_format($totalQuantity) }}</span>
                                    </td>
                                    <td class="text-end text-dark" style="white-space:nowrap;">TZS {{ number_format($totalValue, 0) }}</td>
                                    <td colspan="{{ ($isShopAdmin || $isOwner) ? 2 : 1 }}"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    {{-- Bulk Approve Modal --}}
                    @if($isShopAdmin && $pendingItems > 0)
                    <div class="modal fade st-modal" id="bulkApproveModal" tabindex="-1" aria-labelledby="bulkApproveModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content text-start">
                                <div class="modal-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="st-modal-icon" style="background:rgba(16,185,129,.12); color:#059669;">
                                            <i class="bi bi-check2-all"></i>
                                        </span>
                                        <div>
                                            <h6 class="modal-title fw-700 mb-0" id="bulkApproveModalLabel" style="font-size:.95rem;">Bulk Receive Selected Items</h6>
                                            <div class="text-muted" style="font-size:.73rem;">Confirm selling prices for each item before adding to shop stock</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert py-2 px-3 mb-3 rounded-3 border-0" style="font-size:.78rem; background:#f0f9ff; color:#0369a1;">
                                        <i class="bi bi-info-circle-fill me-1"></i>
                                        Set the branch selling price for each product below. Your shop's buying price is automatically matched to the dispatch price from Main Store.
                                    </div>
                                    <div id="bulkApproveModalBody" class="table-responsive">
                                        <!-- Dynamically generated via JS -->
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-sm btn-success px-3" style="border-radius:8px; font-weight:600;">
                                        <i class="bi bi-check2-all me-1"></i> Confirm &amp; Receive Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════
     MODALS
     ══════════════════════════════════ --}}

{{-- 1. Owner: Add Item Modal --}}
@if($isOwner)
<div class="modal fade st-modal" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('stock-transfers.add-item', $stockTransfer) }}">
                @csrf
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="st-modal-icon" style="background:rgba(0,119,182,.1); color:#0077b6;">
                            <i class="bi bi-plus-circle-fill"></i>
                        </span>
                        <div>
                            <h6 class="modal-title fw-700 mb-0" id="addItemModalLabel" style="font-size:.94rem;">Add Item to Transfer #{{ $stockTransfer->id }}</h6>
                            <div class="text-muted" style="font-size:.72rem;">Dispatch additional stock from Main Warehouse</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Select Product</label>
                        <select name="item_id" class="form-select" required>
                            <option value="" disabled selected>-- Choose Product from Main Warehouse --</option>
                            @foreach($items as $itm)
                                @php $avail = $availableStock[$itm->id] ?? 0; @endphp
                                <option value="{{ $itm->id }}" {{ $avail <= 0 ? 'disabled' : '' }}>
                                    {{ $itm->item_name }} ({{ $itm->category?->category_name ?? 'General' }}) — Available: {{ $avail }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-600 small">Transfer Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent px-3">
                        <i class="bi bi-check-circle me-1"></i> Add &amp; Dispatch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Item-Level Modals --}}
@foreach($stockTransfer->items as $ti)
    {{-- Edit Item Modal (Owner) --}}
    @if($isOwner && in_array($ti->status, ['pending', 'rejected']))
    <div class="modal fade st-modal" id="editItemModal{{ $ti->id }}" tabindex="-1" aria-labelledby="editItemModalLabel{{ $ti->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('stock-transfers.update-item', $ti) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="st-modal-icon" style="background:rgba(15,23,42,.08); color:#0f172a;">
                                <i class="bi bi-pencil-square"></i>
                            </span>
                            <div>
                                <h6 class="modal-title fw-700 mb-0" id="editItemModalLabel{{ $ti->id }}" style="font-size:.94rem;">Edit Transfer Item #{{ $ti->id }}</h6>
                                <div class="text-muted" style="font-size:.72rem;">Modify product selection or dispatched quantity</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-600 small">Product Item</label>
                            <select name="item_id" class="form-select" required>
                                @foreach($items as $itm)
                                    @php $avail = ($availableStock[$itm->id] ?? 0) + ($itm->id == $ti->item_id ? $ti->quantity : 0); @endphp
                                    <option value="{{ $itm->id }}" {{ $itm->id == $ti->item_id ? 'selected' : '' }}>
                                        {{ $itm->item_name }} ({{ $itm->category?->category_name ?? 'General' }}) — Available: {{ $avail }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600 small">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" value="{{ $ti->quantity }}" required>
                        </div>
                        @if($ti->status === 'rejected')
                            <div class="alert alert-warning border-0 small mb-0 rounded-3" style="background:#fffbeb; color:#92400e;">
                                <i class="bi bi-info-circle-fill me-1"></i> Saving updates will clear the rejection status and reset this item to <strong>Pending</strong> for re-inspection.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-accent px-3">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Item Modal (Shop Admin) --}}
    @if($isShopAdmin && $ti->status === 'pending')
    <div class="modal fade st-modal" id="rejectModal{{ $ti->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $ti->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('stock-transfers.reject-item', $ti) }}">
                    @csrf
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="st-modal-icon" style="background:rgba(220,38,38,.1); color:#dc2626;">
                                <i class="bi bi-x-circle-fill"></i>
                            </span>
                            <div class="min-w-0">
                                <h6 class="modal-title fw-700 mb-0 text-truncate" id="rejectModalLabel{{ $ti->id }}" style="font-size:.94rem;">Reject: {{ $ti->item?->item_name }}</h6>
                                <div class="text-muted" style="font-size:.72rem;">Notify the Owner of quantity or product discrepancy</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">
                            Please describe the exact reason for rejecting this item (e.g. quantity mismatch, wrong model, or damaged stock).
                        </p>
                        <div>
                            <label class="form-label fw-600 small text-danger">Rejection Reason *</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Enter reason for discrepancy..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger px-3" style="border-radius:8px; font-weight:600;">
                            <i class="bi bi-x-circle me-1"></i> Reject Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Receive/Approve Item Modal (Shop Admin) --}}
    @if($isShopAdmin && $ti->status === 'pending')
    @php
        $existingShopPrice = $existingShopPrices->get($ti->item_id);
        $defaultSellingPrice = $existingShopPrice ?? $ti->selling_price;
    @endphp
    <div class="modal fade st-modal" id="approveModal{{ $ti->id }}" tabindex="-1" aria-labelledby="approveModalLabel{{ $ti->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('stock-transfers.approve-item', $ti) }}">
                    @csrf
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="st-modal-icon" style="background:rgba(16,185,129,.12); color:#059669;">
                                <i class="bi bi-check-circle-fill"></i>
                            </span>
                            <div class="min-w-0">
                                <h6 class="modal-title fw-700 mb-0 text-truncate" id="approveModalLabel{{ $ti->id }}" style="font-size:.94rem;">Receive: {{ $ti->item?->item_name }}</h6>
                                <div class="text-muted" style="font-size:.72rem;">Confirm receipt and set your branch selling price</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        @if($existingShopPrice)
                        <div class="alert py-2 px-3 mb-3 rounded-3 d-flex align-items-center gap-2 border-0" style="font-size:.78rem; background:#f0f9ff; color:#0369a1;">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                            <span>This product already exists in your shop stock. Your current selling price <strong>TZS {{ number_format($existingShopPrice, 0) }}</strong> is pre-filled.</span>
                        </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Qty</th>
                                        <th>Buying Price (TZS)</th>
                                        <th style="min-width: 200px;">Selling Price (TZS) *</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="fw-700 text-dark">{{ $ti->item?->item_name }}</div>
                                            @if($ti->item?->brand)
                                                <small class="text-muted">{{ $ti->item->brand }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center"><span class="st-qty-pill">{{ number_format($ti->quantity) }}</span></td>
                                        <td class="fw-600" style="white-space:nowrap;">TZS {{ number_format($ti->selling_price, 0) }}</td>
                                        <td>
                                            <input type="text" inputmode="decimal" name="selling_price"
                                                   class="form-control form-control-sm currency-input approve-item-selling-price"
                                                   value="{{ $defaultSellingPrice }}"
                                                   data-buying-price="{{ $ti->selling_price }}"
                                                   required>
                                            <div class="text-danger small price-warning mt-1" style="display:none;">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!
                                            </div>
                                            @if($existingShopPrice)
                                            <span class="text-primary d-block mt-1" style="font-size:.71rem;">
                                                <i class="bi bi-tag-fill me-1"></i>Current shop price: <strong>TZS {{ number_format($existingShopPrice, 0) }}</strong>
                                            </span>
                                            @else
                                            <span class="text-muted d-block mt-1" style="font-size:.71rem;">
                                                <i class="bi bi-tag me-1"></i>New item — no existing shop price.
                                            </span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success px-3" style="border-radius:8px; font-weight:600;">
                            <i class="bi bi-check-lg me-1"></i> Receive &amp; Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

{{-- Hidden Forms --}}
<form id="deleteItemForm" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="deleteTransferForm" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="approveItemForm" method="POST" action="" style="display:none;">
    @csrf
</form>
@endsection

@push('scripts')
<script>
function toggleAllCheckboxes(source) {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = source.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const allBoxes = document.querySelectorAll('.item-checkbox');
    const checked = document.querySelectorAll('.item-checkbox:checked').length;
    const countEl = document.getElementById('selectedCount');
    const btn = document.getElementById('bulkApproveBtn');
    const selectAll = document.getElementById('selectAll');
    if (countEl) countEl.textContent = checked;
    if (btn) btn.disabled = (checked === 0);
    if (selectAll) selectAll.checked = (allBoxes.length > 0 && checked === allBoxes.length);
}

function escapeTransferHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function submitBulkApprove() {
    const checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) {
        alert('Please select at least one item to approve.');
        return;
    }

    const modalBody = document.getElementById('bulkApproveModalBody');
    let html = `
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th>Buying Price (TZS)</th>
                    <th style="min-width: 200px;">Selling Price (TZS) *</th>
                </tr>
            </thead>
            <tbody>
    `;

    document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const itemId      = cb.value;
        const name        = cb.dataset.name;
        const qty         = cb.dataset.qty;
        const price       = cb.dataset.price;
        const suggested   = cb.dataset.suggestedPrice;
        const hasExisting = cb.dataset.hasExisting === 'true';

        html += `
            <tr>
                <td><div class="fw-700 text-dark">${escapeTransferHtml(name)}</div></td>
                <td class="text-center"><span class="st-qty-pill">${parseInt(qty, 10).toLocaleString()}</span></td>
                <td class="fw-600" style="white-space:nowrap;">TZS ${parseInt(price, 10).toLocaleString()}</td>
                <td>
                    <input type="text" inputmode="decimal" name="selling_prices[${itemId}]" class="form-control form-control-sm currency-input bulk-selling-price" data-buying-price="${price}" value="${suggested}" required>
                    <div class="text-danger small price-warning mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                    ${hasExisting
                        ? `<span class="text-primary d-block mt-1" style="font-size:.71rem;"><i class="bi bi-tag-fill me-1"></i>Current shop price: <strong>TZS ${parseInt(suggested, 10).toLocaleString()}</strong></span>`
                        : `<span class="text-muted d-block mt-1" style="font-size:.71rem;"><i class="bi bi-tag me-1"></i>New item — no existing shop price.</span>`
                    }
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;
    modalBody.innerHTML = html;

    const myModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkApproveModal'));
    myModal.show();

    $('#bulkApproveModal').find('.currency-input').each(function() {
        this.value = window.formatCurrencyValue(this.value);
    });
}

function submitDeleteItem(url) {
    Swal.fire({
        title: 'Remove this item?',
        text: 'Its stock quantity will be restored back to the Main Warehouse.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteItemForm');
            form.action = url;
            form.submit();
        }
    });
}

$(document).ready(function() {
    $('#deleteTransferBtn').on('click', function() {
        const url = $(this).data('url');
        const id  = $(this).data('id');
        Swal.fire({
            title: 'Delete Transfer #' + id + '?',
            html: 'All transfer items (including received stock) will be removed and stock <strong>returned to the Main Warehouse</strong>.<br>This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete transfer',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteTransferForm');
                form.action = url;
                form.submit();
            }
        });
    });
});

// Instant price validation
$(document).on('input', '.approve-item-selling-price, .bulk-selling-price', function() {
    const input = $(this);
    const buyingPrice = parseFloat(input.data('buying-price') || 0);
    const currentVal = parseFloat(input.val().replace(/,/g, '') || 0);

    const warning = input.closest('td').find('.price-warning');
    const form = input.closest('form');
    const submitBtn = form.find('button[type="submit"]');

    if (currentVal < buyingPrice) {
        warning.show();
        input.addClass('is-invalid');
    } else {
        warning.hide();
        input.removeClass('is-invalid');
    }

    const hasInvalid = form.find('.is-invalid').length > 0;
    submitBtn.prop('disabled', hasInvalid);
});
</script>
@endpush
