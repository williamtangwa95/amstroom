@extends('layouts.app')
@section('title', 'Inter-Shop Product Transfers')
@section('page-title', 'Product Transfers')

@section('breadcrumb')
<li class="breadcrumb-item active">Product Transfers</li>
@endsection

@section('content')
<style>
    .pt-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        border-radius: 14px;
        padding: 1rem 1.35rem;
        color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 86, 145, .18);
        margin-bottom: 1.15rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .pt-hero-title { font-size: 1.08rem; font-weight: 700; margin: 0; color: #ffffff; }
    .pt-hero-sub { font-size: .76rem; color: rgba(255,255,255,.82); margin: .2rem 0 0; }
    .pt-hero-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .16rem .58rem; border-radius: 999px;
        background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.24);
        color: #ffffff; font-size: .64rem; font-weight: 600;
    }
    .pt-hero-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
        padding: .46rem .95rem; border-radius: 9px; font-size: .78rem; font-weight: 700;
        background: #ffffff; color: #005691; text-decoration: none;
        box-shadow: 0 3px 10px rgba(0,0,0,.14); transition: all .15s ease;
    }
    .pt-hero-btn:hover { background: #f0f9ff; color: #003a6c; transform: translateY(-1px); }

    .pt-kpi-card {
        background: #ffffff; border: 1px solid var(--card-border); border-radius: 13px;
        padding: .85rem 1rem; box-shadow: 0 2px 8px rgba(15,23,42,.035);
        display: flex; align-items: center; gap: .75rem; height: 100%;
        text-decoration: none; color: inherit; transition: all .16s ease;
    }
    .pt-kpi-card:hover { border-color: rgba(0,119,182,.35); transform: translateY(-1px); color: inherit; }
    .pt-kpi-card.is-active { border-color: #0077b6; background: #f8fcff; box-shadow: 0 0 0 3px rgba(0,119,182,.12); }
    .pt-kpi-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0;
    }
    .pt-kpi-label { font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin: 0; }
    .pt-kpi-val { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; margin-top: .1rem; }
    .pt-kpi-sub { font-size: .68rem; color: #64748b; margin-top: .06rem; }

    .pt-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15,23,42,.04);
        overflow: hidden;
    }
    .pt-toolbar {
        padding: .8rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #ffffff;
    }
    .pt-pills {
        display: inline-flex;
        flex-wrap: wrap;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .pt-pill-btn {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .3rem .68rem; border-radius: 7px; border: none;
        background: transparent; color: #475569; font-size: .74rem; font-weight: 600;
        text-decoration: none; transition: all .15s ease; white-space: nowrap;
    }
    .pt-pill-btn:hover { color: #0f172a; }
    .pt-pill-btn.active { background: #ffffff; color: #0077b6; box-shadow: 0 1px 3px rgba(15,23,42,.08); }
    .pt-select-wrap {
        display: inline-flex;
        align-items: center;
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 9px;
        padding: 0 .2rem 0 .6rem;
        height: 34px;
    }
    .pt-select-wrap i { color: #64748b; font-size: .8rem; margin-right: .35rem; }
    .pt-select-wrap select {
        border: none; background: transparent; font-size: .78rem; font-weight: 600;
        color: var(--text-primary, #0f172a); padding: .2rem 1.65rem .2rem .1rem;
        cursor: pointer; box-shadow: none !important;
    }

    @media (max-width: 767.98px) {
        .pt-hero { padding: .9rem 1rem; }
        .pt-hero-btn { width: 100%; }
        .pt-kpi-card { padding: .7rem .75rem; gap: .55rem; }
        .pt-kpi-icon { width: 34px; height: 34px; font-size: .92rem; }
        .pt-toolbar { padding: .75rem .9rem; }
        .pt-pills { width: 100%; overflow-x: auto; flex-wrap: nowrap; }
        .pt-pill-btn { flex: 1 0 auto; justify-content: center; }
    }
</style>

@php
    $user = auth()->user();
    $isOwner = $user->isOwner();
    $isShopAdmin = $user->isShopAdmin();
@endphp

{{-- ── Hero Header ── --}}
<div class="pt-hero">
    <div style="flex:1; min-width:220px;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="pt-hero-title">Inter-Shop Product Transfers</h1>
            <span class="pt-hero-pill">
                <i class="bi bi-shop"></i>
                {{ $isOwner ? 'Owner Transparency Overview (Read-Only)' : ($user->shop?->shop_name ?? 'My Branch') }}
            </span>
        </div>
        <p class="pt-hero-sub">
            {{ $isOwner
                ? 'Full live transparency across all shop-to-shop product transfers: products, quantities, amounts, source & destination shops, and live status.'
                : 'Request products directly from another branch. Stock moves atomically once the supplying shop confirms.' }}
        </p>
    </div>
    @if($isShopAdmin)
    <div>
        <a href="{{ route('product-transfers.create') }}" class="pt-hero-btn">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Request Product Transfer</span>
        </a>
    </div>
    @endif
</div>

{{-- ── KPI Cards ── --}}
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'all'])) }}" class="pt-kpi-card {{ $tab === 'all' ? 'is-active' : '' }}">
            <div class="pt-kpi-icon" style="background: rgba(0,119,182,.1); color: #0077b6;"><i class="bi bi-box-arrow-in-right"></i></div>
            <div>
                <p class="pt-kpi-label">All Transfers</p>
                <div class="pt-kpi-val">{{ number_format($counts['all']) }}</div>
                <div class="pt-kpi-sub">Total requests</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'action_required'])) }}" class="pt-kpi-card {{ $tab === 'action_required' ? 'is-active' : '' }}">
            <div class="pt-kpi-icon" style="background: rgba(217,119,6,.12); color: #d97706;"><i class="bi bi-bell-fill"></i></div>
            <div>
                <p class="pt-kpi-label">{{ $isOwner ? 'Pending Approval' : 'Action Required' }}</p>
                <div class="pt-kpi-val" style="color:#d97706;">{{ number_format($counts['action_required']) }}</div>
                <div class="pt-kpi-sub">{{ $isOwner ? 'Awaiting shop confirm' : 'Incoming to confirm' }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'confirmed'])) }}" class="pt-kpi-card {{ $tab === 'confirmed' ? 'is-active' : '' }}">
            <div class="pt-kpi-icon" style="background: rgba(16,185,129,.12); color: #059669;"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <p class="pt-kpi-label">Confirmed</p>
                <div class="pt-kpi-val text-success">{{ number_format($counts['confirmed']) }}</div>
                <div class="pt-kpi-sub">Stock moved</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => $isOwner ? 'history' : 'outgoing'])) }}" class="pt-kpi-card {{ ($isOwner ? $tab === 'history' : $tab === 'outgoing') ? 'is-active' : '' }}">
            <div class="pt-kpi-icon" style="background: rgba(99,102,241,.12); color: #4f46e5;"><i class="bi {{ $isOwner ? 'bi-clock-history' : 'bi-send-fill' }}"></i></div>
            <div>
                <p class="pt-kpi-label">{{ $isOwner ? 'Rejected / Reverted' : 'My Outgoing' }}</p>
                <div class="pt-kpi-val" style="color:#4f46e5;">{{ number_format($isOwner ? $counts['history'] : $counts['outgoing']) }}</div>
                <div class="pt-kpi-sub">{{ $isOwner ? 'Closed records' : 'Waiting on other shop' }}</div>
            </div>
        </a>
    </div>
</div>

{{-- ── Main DataTable Card ── --}}
<div class="pt-card">
    <div class="pt-toolbar">
        <div class="pt-pills" role="group">
            <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'all'])) }}" class="pt-pill-btn {{ $tab === 'all' ? 'active' : '' }}">All ({{ $counts['all'] }})</a>
            <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'action_required'])) }}" class="pt-pill-btn {{ $tab === 'action_required' ? 'active' : '' }}">
                {{ $isOwner ? 'Pending' : 'Incoming to Confirm' }} ({{ $counts['action_required'] }})
            </a>
            @if(!$isOwner)
            <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'outgoing'])) }}" class="pt-pill-btn {{ $tab === 'outgoing' ? 'active' : '' }}">
                My Outgoing ({{ $counts['outgoing'] }})
            </a>
            @endif
            <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'confirmed'])) }}" class="pt-pill-btn {{ $tab === 'confirmed' ? 'active' : '' }}">Confirmed ({{ $counts['confirmed'] }})</a>
            <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'history'])) }}" class="pt-pill-btn {{ $tab === 'history' ? 'active' : '' }}">History ({{ $counts['history'] }})</a>
        </div>

        @if($isOwner && $shops->count() > 0)
        <form method="GET" action="{{ route('product-transfers.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="pt-select-wrap">
                <i class="bi bi-shop"></i>
                <select name="shop_id" class="form-select form-select-sm no-select2" onchange="this.form.submit()" aria-label="Filter by Branch">
                    <option value="">All Branches</option>
                    @foreach($shops as $s)
                        <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        @endif
    </div>

    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="productTransfersTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center no-sort" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Transfer #</th>
                    <th data-priority="2" class="min-tablet-l">From Shop</th>
                    <th data-priority="2" class="min-tablet-l">To Shop</th>
                    <th data-priority="3" class="min-tablet-l">Products</th>
                    <th data-priority="4" class="min-tablet-l text-center">Qty</th>
                    <th data-priority="3" class="min-tablet-l text-end">Amount (TZS)</th>
                    <th data-priority="4" class="min-tablet-l">Date</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="1" class="min-tablet-l no-sort text-end" style="min-width: 105px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $tr)
                    @php
                        $needsMyConfirm = $tr->canConfirm($user);
                        $statusBadge = match ($tr->status) {
                            'pending'   => '<span class="badge-soft-warning"><i class="bi bi-hourglass-split"></i> Pending Confirmation</span>',
                            'confirmed' => '<span class="badge-soft-success"><i class="bi bi-check-circle-fill"></i> Confirmed</span>',
                            'rejected'  => '<span class="badge-soft-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>',
                            'cancelled' => '<span class="badge-soft-secondary"><i class="bi bi-slash-circle"></i> Cancelled</span>',
                            'reverted'  => '<span class="badge-soft-dark"><i class="bi bi-arrow-counterclockwise"></i> Reverted</span>',
                            default     => '<span class="badge-soft-secondary">' . e(ucfirst($tr->status)) . '</span>',
                        };
                    @endphp
                    <tr>
                        <td class="text-center iter-col">{{ $loop->iteration }}</td>
                        <td class="primary-col">
                            <a href="{{ route('product-transfers.show', $tr) }}" class="fw-700 text-primary text-decoration-none">{{ $tr->transfer_number }}</a>
                            @if($needsMyConfirm)
                                <div><span class="badge bg-warning text-dark mt-1" style="font-size:.64rem;">Needs Your Confirmation</span></div>
                            @endif
                        </td>
                        <td class="fw-600 text-dark">
                            <i class="bi bi-box-arrow-up text-danger me-1"></i>{{ $tr->fromShop?->shop_name ?? '—' }}
                        </td>
                        <td class="fw-600 text-dark">
                            <i class="bi bi-box-arrow-in-down text-success me-1"></i>{{ $tr->toShop?->shop_name ?? '—' }}
                        </td>
                        <td>
                            @foreach($tr->items->take(3) as $ti)
                                <div class="small fw-600">
                                    {{ $ti->item?->item_name ?? 'Item' }}
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size:.68rem;">{{ $ti->quantity }} {{ $ti->item?->unit_symbol ?? 'pc' }}</span>
                                </div>
                            @endforeach
                            @if($tr->items->count() > 3)
                                <div class="small text-muted">+{{ $tr->items->count() - 3 }} more product(s)</div>
                            @endif
                        </td>
                        <td class="text-center" data-order="{{ $tr->total_quantity }}">
                            <span class="fw-700 text-dark">{{ number_format($tr->total_quantity) }}</span>
                            <span class="small text-muted">pc</span>
                        </td>
                        <td class="text-end" data-order="{{ $tr->total_amount }}">
                            <strong class="text-dark">TZS {{ number_format($tr->total_amount, 0) }}</strong>
                        </td>
                        <td class="small text-muted" data-order="{{ $tr->created_at?->timestamp ?? 0 }}">{{ $tr->created_at?->format('M d, Y · H:i') }}</td>
                        <td>{!! $statusBadge !!}</td>
                        <td class="text-end">
                            <a href="{{ route('product-transfers.show', $tr) }}" class="btn btn-sm {{ $needsMyConfirm ? 'btn-warning text-dark fw-700' : 'btn-outline-primary' }} text-nowrap">
                                <i class="bi {{ $needsMyConfirm ? 'bi-check2-circle' : 'bi-eye' }} me-1"></i>{{ $needsMyConfirm ? 'Review & Confirm' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#productTransfersTable').DataTable({
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[7, 'desc']],
        columnDefs: [
            { orderable: false, searchable: false, targets: [0, 9] }
        ],
        language: {
            searchPlaceholder: 'Search PT #, product, shop...',
            emptyTable: '<div class="py-4 text-muted"><i class="bi bi-arrow-left-right fs-3 d-block mb-2 opacity-50"></i><div class="fw-700 text-dark mb-1">No inter-shop product transfers found</div><small>When a shop requests stock from another branch, it will appear here.</small></div>'
        }
    });
});
</script>
@endpush
