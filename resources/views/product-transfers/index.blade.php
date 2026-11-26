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

    .pt-filter-card {
        background: #ffffff; border: 1px solid var(--card-border); border-radius: 14px;
        padding: .95rem 1.15rem; box-shadow: 0 2px 10px rgba(15,23,42,.04); margin-bottom: 1.15rem;
    }
    .pt-pill-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .34rem .78rem; border-radius: 999px; border: 1px solid #e2e8f0;
        background: #f8fafc; color: #475569; font-size: .75rem; font-weight: 600;
        text-decoration: none; transition: all .15s ease;
    }
    .pt-pill-btn:hover { background: #e2e8f0; color: #0f172a; }
    .pt-pill-btn.active { background: #0077b6; border-color: #0077b6; color: #ffffff; }

    .pt-mobile-card {
        background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #0077b6;
        border-radius: 11px; padding: .85rem; margin-bottom: .75rem;
        box-shadow: 0 2px 6px rgba(15,23,42,.04);
    }

    @media (max-width: 767.98px) {
        .pt-hero { padding: .9rem 1rem; }
        .pt-hero-btn { width: 100%; }
        .pt-kpi-card { padding: .7rem .75rem; gap: .55rem; }
        .pt-kpi-icon { width: 34px; height: 34px; font-size: .92rem; }
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

{{-- ── Filter Bar ── --}}
<div class="pt-filter-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2">
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
            <a href="{{ route('product-transfers.index', array_merge(request()->except('tab', 'page'), ['tab' => 'history'])) }}" class="pt-pill-btn {{ $tab === 'history' ? 'active' : '' }}">Rejected / Cancelled / Reverted ({{ $counts['history'] }})</a>
        </div>
    </div>

    <form method="GET" action="{{ route('product-transfers.index') }}" class="row g-2 align-items-end">
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if($isOwner)
        <div class="col-12 col-sm-4">
            <label class="form-label small fw-600 text-secondary mb-1">Filter by Branch</label>
            <select name="shop_id" class="form-select form-select-sm no-select2" onchange="this.form.submit()">
                <option value="">All Branches</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-12 {{ $isOwner ? 'col-sm-5' : 'col-sm-8' }}">
            <label class="form-label small fw-600 text-secondary mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Search PT #, product name, or shop...">
        </div>
        <div class="col-12 {{ $isOwner ? 'col-sm-3' : 'col-sm-4' }} d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-accent flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="{{ route('product-transfers.index') }}" class="btn btn-sm btn-outline-custom">Reset</a>
        </div>
    </form>
</div>

{{-- ── Transfers Listing ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
        @if($transfers->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-left-right fs-1 d-block mb-2 opacity-50"></i>
                <div class="fw-700 text-dark mb-1">No inter-shop product transfers found</div>
                <div class="small mb-3">When a shop requests stock from another branch, it will appear here.</div>
                @if($isShopAdmin)
                    <a href="{{ route('product-transfers.create') }}" class="btn btn-sm btn-accent">
                        <i class="bi bi-plus-circle me-1"></i>Request Product Transfer
                    </a>
                @endif
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="d-none d-md-block">
                <table class="table table-hover align-middle w-100 mb-0">
                    <thead>
                        <tr>
                            <th>Transfer #</th>
                            <th>From Shop</th>
                            <th>To Shop</th>
                            <th>Products</th>
                            <th class="text-center">Total Qty</th>
                            <th class="text-end">Amount (TZS)</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
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
                                <td>
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
                                <td class="text-center">
                                    <span class="fw-700 text-dark">{{ number_format($tr->total_quantity) }}</span>
                                    <span class="small text-muted">pc</span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-dark">TZS {{ number_format($tr->total_amount, 0) }}</strong>
                                </td>
                                <td class="small text-muted">{{ $tr->created_at?->format('M d, Y · H:i') }}</td>
                                <td>{!! $statusBadge !!}</td>
                                <td class="text-end">
                                    <a href="{{ route('product-transfers.show', $tr) }}" class="btn btn-sm {{ $needsMyConfirm ? 'btn-warning text-dark fw-700' : 'btn-outline-primary' }}">
                                        <i class="bi {{ $needsMyConfirm ? 'bi-check2-circle' : 'bi-eye' }} me-1"></i>{{ $needsMyConfirm ? 'Review & Confirm' : 'View' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Zero-Scroll Cards --}}
            <div class="d-md-none">
                @foreach($transfers as $tr)
                    @php
                        $needsMyConfirm = $tr->canConfirm($user);
                        $statusBadge = match ($tr->status) {
                            'pending'   => '<span class="badge-soft-warning"><i class="bi bi-hourglass-split"></i> Pending</span>',
                            'confirmed' => '<span class="badge-soft-success"><i class="bi bi-check-circle-fill"></i> Confirmed</span>',
                            'rejected'  => '<span class="badge-soft-danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>',
                            'cancelled' => '<span class="badge-soft-secondary"><i class="bi bi-slash-circle"></i> Cancelled</span>',
                            'reverted'  => '<span class="badge-soft-dark"><i class="bi bi-arrow-counterclockwise"></i> Reverted</span>',
                            default     => '<span class="badge-soft-secondary">' . e(ucfirst($tr->status)) . '</span>',
                        };
                    @endphp
                    <div class="pt-mobile-card">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <a href="{{ route('product-transfers.show', $tr) }}" class="fw-700 text-primary text-decoration-none">{{ $tr->transfer_number }}</a>
                            {!! $statusBadge !!}
                        </div>
                        <div class="small mb-2">
                            <div><span class="text-muted">From:</span> <strong class="text-dark">{{ $tr->fromShop?->shop_name ?? '—' }}</strong></div>
                            <div><span class="text-muted">To:</span> <strong class="text-dark">{{ $tr->toShop?->shop_name ?? '—' }}</strong></div>
                            <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                                <span><span class="text-muted">Qty:</span> <strong>{{ number_format($tr->total_quantity) }} pc</strong></span>
                                <strong class="text-success">TZS {{ number_format($tr->total_amount, 0) }}</strong>
                            </div>
                            <div class="text-muted mt-1" style="font-size:.72rem;">{{ $tr->created_at?->format('M d, Y · H:i') }}</div>
                        </div>
                        <div class="p-2 rounded bg-light border mb-2">
                            @foreach($tr->items as $ti)
                                @php $linePrice = (float) ($ti->selling_price > 0 ? $ti->selling_price : $ti->buying_price); @endphp
                                <div class="d-flex justify-content-between small fw-600">
                                    <span>{{ $ti->item?->item_name ?? 'Item' }} ({{ $ti->quantity }} {{ $ti->item?->unit_symbol ?? 'pc' }})</span>
                                    <span class="text-dark">TZS {{ number_format($ti->quantity * $linePrice, 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('product-transfers.show', $tr) }}" class="btn btn-sm {{ $needsMyConfirm ? 'btn-warning text-dark fw-700' : 'btn-outline-primary' }} w-100">
                            <i class="bi {{ $needsMyConfirm ? 'bi-check2-circle' : 'bi-eye' }} me-1"></i>{{ $needsMyConfirm ? 'Review & Confirm Transfer' : 'View Details' }}
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
