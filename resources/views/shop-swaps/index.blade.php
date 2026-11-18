@extends('layouts.app')
@section('title', 'Product Swaps — Inter-Shop Exchange')
@section('page-title', 'Inter-Shop Product Swaps')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Stock & Warehouse</a></li>
<li class="breadcrumb-item active">Product Swaps</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h5 class="mb-0 fw-700">
            <i class="bi bi-arrow-left-right text-info me-2"></i>Inter-Shop Product Swaps (Stock Exchange)
        </h5>
        <small style="color:var(--text-secondary);">
            Exchange items between shops for customer preferences or unit replacements with 3-party approval.
        </small>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        @if(auth()->user()->isOwner() && $shops->count() > 0)
        <form method="GET" action="{{ route('shop-swaps.index') }}" class="d-inline-flex align-items-center">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-custom border-custom text-muted"><i class="bi bi-shop"></i></span>
                <select name="shop_id" class="form-select form-select-sm border-custom" onchange="this.form.submit()">
                    <option value="">All Shop Branches</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        @endif

        <a href="{{ route('shop-swaps.create') }}" class="btn btn-sm btn-accent shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Propose Product Swap
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success border-0 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="font-size:.85rem;">
    <div><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger border-0 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="font-size:.85rem;">
    <div><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Quick Summary Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'pending_destination'])) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-600">Pending Destination</span>
                        <span class="badge bg-warning-subtle text-warning rounded-pill"><i class="bi bi-shop"></i></span>
                    </div>
                    <h3 class="fw-800 mb-0 text-warning">{{ $counts['pending_destination'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">Awaiting Destination Shop</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'pending_owner'])) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #8b5cf6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-600">Pending Owner Confirmation</span>
                        <span class="badge bg-purple-subtle text-purple rounded-pill" style="background:#ede9fe;color:#8b5cf6;"><i class="bi bi-shield-lock"></i></span>
                    </div>
                    <h3 class="fw-800 mb-0" style="color:#8b5cf6;">{{ $counts['pending_owner'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">Awaiting Owner Execution</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'confirmed'])) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-600">Confirmed & Swapped</span>
                        <span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-check2-circle"></i></span>
                    </div>
                    <h3 class="fw-800 mb-0 text-success">{{ $counts['confirmed'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">Stock Adopted in Shops</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <a href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'history'])) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #6b7280 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-600">Cancelled / Reverted</span>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill"><i class="bi bi-archive"></i></span>
                    </div>
                    <h3 class="fw-800 mb-0 text-secondary">{{ $counts['history'] }}</h3>
                    <small class="text-muted" style="font-size:.72rem;">Past Archived Swaps</small>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Navigation Tabs & Filter --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-custom p-2 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
        <ul class="nav nav-pills card-header-pills m-0" style="font-size:.82rem;">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'all'])) }}">
                    All Swaps ({{ $counts['all'] }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'pending_destination' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'pending_destination'])) }}">
                    Pending Destination ({{ $counts['pending_destination'] }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'pending_owner' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'pending_owner'])) }}">
                    Pending Owner ({{ $counts['pending_owner'] }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'confirmed' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'confirmed'])) }}">
                    Confirmed ({{ $counts['confirmed'] }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('shop-swaps.index', array_merge(request()->query(), ['tab' => 'history'])) }}">
                    History ({{ $counts['history'] }})
                </a>
            </li>
        </ul>

        <form method="GET" action="{{ route('shop-swaps.index') }}" class="d-flex align-items-center gap-1">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if(request('shop_id'))<input type="hidden" name="shop_id" value="{{ request('shop_id') }}">@endif
            <div class="input-group input-group-sm" style="width: 220px;">
                <input type="text" name="search" class="form-control form-control-sm border-custom" placeholder="Search swap / item..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-custom"><i class="bi bi-search"></i></button>
            </div>
            @if(request('search'))
            <a href="{{ route('shop-swaps.index', ['tab' => $tab, 'shop_id' => request('shop_id')]) }}" class="btn btn-sm btn-outline-custom" title="Clear Search"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
            <thead class="table-light">
                <tr>
                    <th style="width: 130px;">Swap #</th>
                    <th style="min-width: 170px;">From (Source Shop)</th>
                    <th style="min-width: 190px;">Offered Product</th>
                    <th style="min-width: 170px;">To (Destination Shop)</th>
                    <th style="min-width: 190px;">Requested Product</th>
                    <th style="min-width: 140px;">Reason</th>
                    <th style="min-width: 130px;" class="text-center">Status</th>
                    <th style="width: 100px;" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($swaps as $swap)
                @php
                    $isMySource = auth()->user()->shop_id == $swap->source_shop_id;
                    $isMyDest = auth()->user()->shop_id == $swap->destination_shop_id;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('shop-swaps.show', $swap) }}" class="fw-700 text-decoration-none" style="color:var(--accent-color);">
                            {{ $swap->swap_number }}
                        </a>
                        <div class="text-muted" style="font-size:.72rem;">{{ $swap->created_at->format('M d, Y') }}</div>
                    </td>

                    {{-- Source Shop --}}
                    <td>
                        <div class="fw-700">
                            <i class="bi bi-shop me-1 text-primary"></i>{{ $swap->sourceShop->shop_name }}
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">By: {{ $swap->initiator->name ?? 'User' }}</div>
                    </td>

                    {{-- Offered Item --}}
                    <td>
                        <div class="fw-700 text-dark">
                            {{ $swap->sourceItem->item_name }}
                        </div>
                        <div class="d-flex align-items-center gap-1 mt-0.5">
                            <span class="badge bg-secondary-subtle text-secondary py-0 px-1" style="font-size:.68rem;">
                                {{ $swap->source_quantity }} {{ $swap->sourceItem->unit_symbol }}
                            </span>
                            @if($swap->sourceItem->brand)<span class="text-muted" style="font-size:.7rem;">{{ $swap->sourceItem->brand }}</span>@endif
                        </div>
                    </td>

                    {{-- Destination Shop --}}
                    <td>
                        <div class="fw-700">
                            <i class="bi bi-shop me-1 text-info"></i>{{ $swap->destinationShop->shop_name }}
                        </div>
                        @if($swap->destinationApprover)
                        <div class="text-muted" style="font-size:.72rem;">Approved by: {{ $swap->destinationApprover->name }}</div>
                        @else
                        <div class="text-muted" style="font-size:.72rem;">Awaiting Shop Admin</div>
                        @endif
                    </td>

                    {{-- Requested Item --}}
                    <td>
                        <div class="fw-700 text-dark">
                            {{ $swap->destinationItem->item_name }}
                        </div>
                        <div class="d-flex align-items-center gap-1 mt-0.5">
                            <span class="badge bg-secondary-subtle text-secondary py-0 px-1" style="font-size:.68rem;">
                                {{ $swap->destination_quantity }} {{ $swap->destinationItem->unit_symbol }}
                            </span>
                            @if($swap->destinationItem->brand)<span class="text-muted" style="font-size:.7rem;">{{ $swap->destinationItem->brand }}</span>@endif
                        </div>
                    </td>

                    {{-- Reason --}}
                    <td>
                        <div class="fw-600" style="font-size:.78rem;">{{ $swap->reason_label }}</div>
                        @if($swap->notes)
                        <div class="text-muted text-truncate" style="max-width: 140px; font-size:.72rem;" title="{{ $swap->notes }}">
                            {{ $swap->notes }}
                        </div>
                        @endif
                    </td>

                    {{-- Status Badge --}}
                    <td class="text-center">
                        @if($swap->status === 'pending_destination')
                            <span class="badge bg-warning-subtle text-warning py-1 px-2 fw-700" style="font-size:.72rem;">
                                <i class="bi bi-clock-history me-1"></i> Awaiting Shop B
                            </span>
                        @elseif($swap->status === 'pending_owner')
                            <span class="badge bg-purple-subtle text-purple py-1 px-2 fw-700" style="font-size:.72rem;background:#ede9fe;color:#8b5cf6;">
                                <i class="bi bi-shield-check me-1"></i> Awaiting Owner
                            </span>
                        @elseif($swap->status === 'confirmed')
                            <span class="badge bg-success-subtle text-success py-1 px-2 fw-700" style="font-size:.72rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Swapped (Confirmed)
                            </span>
                        @elseif($swap->status === 'cancelled')
                            <span class="badge bg-danger-subtle text-danger py-1 px-2 fw-700" style="font-size:.72rem;">
                                <i class="bi bi-x-circle me-1"></i> Cancelled
                            </span>
                        @elseif($swap->status === 'reverted')
                            <span class="badge bg-secondary-subtle text-secondary py-1 px-2 fw-700" style="font-size:.72rem;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reverted
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="text-end">
                        <a href="{{ route('shop-swaps.show', $swap) }}" class="btn btn-xs btn-outline-custom" title="View Swap Details">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-muted" style="opacity:0.4;"></i>
                        <h6 class="fw-700 mb-1">No Product Swaps Found</h6>
                        <small>No product exchange requests match the selected criteria.</small>
                        <div class="mt-3">
                            <a href="{{ route('shop-swaps.create') }}" class="btn btn-sm btn-accent">
                                <i class="bi bi-plus-circle me-1"></i> Propose a New Swap
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($swaps->hasPages())
    <div class="card-footer bg-custom p-2 border-top">
        {{ $swaps->links() }}
    </div>
    @endif
</div>
@endsection
