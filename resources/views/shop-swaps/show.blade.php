@extends('layouts.app')
@section('title', 'Swap Details — ' . $shopProductSwap->swap_number)
@section('page-title', 'Product Swap Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-swaps.index') }}">Product Swaps</a></li>
<li class="breadcrumb-item active">{{ $shopProductSwap->swap_number }}</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0 fw-800">
            <i class="bi bi-arrow-left-right text-info me-2"></i>{{ $shopProductSwap->swap_number }}
        </h5>
        @if($shopProductSwap->status === 'pending_destination')
            <span class="badge bg-warning-subtle text-warning py-1 px-2 fw-700" style="font-size:.78rem;">
                <i class="bi bi-clock-history me-1"></i> Awaiting Destination Shop Admin Approval
            </span>
        @elseif($shopProductSwap->status === 'confirmed')
            <span class="badge bg-success-subtle text-success py-1 px-2 fw-700" style="font-size:.78rem;">
                <i class="bi bi-check-circle-fill me-1"></i> Confirmed & Swapped
            </span>
        @elseif($shopProductSwap->status === 'cancelled')
            <span class="badge bg-danger-subtle text-danger py-1 px-2 fw-700" style="font-size:.78rem;">
                <i class="bi bi-x-circle me-1"></i> Cancelled
            </span>
        @elseif($shopProductSwap->status === 'reverted')
            <span class="badge bg-secondary-subtle text-secondary py-1 px-2 fw-700" style="font-size:.78rem;">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reverted
            </span>
        @endif
    </div>

    <a href="{{ route('shop-swaps.index') }}" class="btn btn-sm btn-outline-custom">
        <i class="bi bi-arrow-left me-1"></i> Back to Swaps
    </a>
</div>

{{-- Side-by-Side Comparative Cards --}}
<div class="row g-3 mb-4">
    {{-- Shop A (Source) --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid #0284c7 !important;">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary py-1 px-2 fw-700">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Source Shop Gives
                    </span>
                    <span class="fw-700 text-dark small"><i class="bi bi-shop me-1 text-primary"></i>{{ $shopProductSwap->sourceShop->shop_name }}</span>
                </div>

                <h5 class="fw-800 mb-1" style="color:var(--text-color);">
                    {{ $shopProductSwap->sourceItem->item_name }}
                </h5>
                <div class="text-muted small mb-3">
                    Category: <strong>{{ $shopProductSwap->sourceItem->category?->category_name ?? 'General' }}</strong>
                    @if($shopProductSwap->sourceItem->brand) &bull; Brand: <strong>{{ $shopProductSwap->sourceItem->brand }}</strong>@endif
                    @if($shopProductSwap->sourceItem->model) &bull; Model: <strong>{{ $shopProductSwap->sourceItem->model }}</strong>@endif
                </div>

                <div class="p-3 rounded-3 border bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Quantity to Swap Out:</span>
                        <span class="fw-800 text-primary fs-5">
                            {{ $shopProductSwap->source_quantity }} {{ $shopProductSwap->sourceItem->unit_symbol }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="font-size:.78rem;">
                        <span class="text-muted">Current Live Stock in {{ $shopProductSwap->sourceShop->shop_name }}:</span>
                        <span class="fw-700 text-dark">{{ $sourceItemInSourceShop }} {{ $shopProductSwap->sourceItem->unit_symbol }}</span>
                    </div>
                </div>

                @if($shopProductSwap->sourceItem->specification)
                <div class="small text-muted">
                    <span class="fw-700">Specifications:</span> {{ $shopProductSwap->sourceItem->specification }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Center Arrow Icon --}}
    <div class="col-md-2 d-flex flex-column align-items-center justify-content-center text-center py-2">
        <div class="rounded-circle bg-custom border shadow-sm d-flex align-items-center justify-content-center mb-2" style="width: 54px; height: 54px;">
            <i class="bi bi-arrow-left-right fs-3 text-accent"></i>
        </div>
        <span class="badge bg-light text-muted border px-2 py-1" style="font-size:.7rem;">Product Swap</span>
    </div>

    {{-- Shop B (Destination) --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid #06b6d4 !important;">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-info-subtle text-info py-1 px-2 fw-700">
                        <i class="bi bi-box-arrow-in-down-left me-1"></i> Destination Shop Gives
                    </span>
                    <span class="fw-700 text-dark small"><i class="bi bi-shop me-1 text-info"></i>{{ $shopProductSwap->destinationShop->shop_name }}</span>
                </div>

                <h5 class="fw-800 mb-1" style="color:var(--text-color);">
                    {{ $shopProductSwap->destinationItem->item_name }}
                </h5>
                <div class="text-muted small mb-3">
                    Category: <strong>{{ $shopProductSwap->destinationItem->category?->category_name ?? 'General' }}</strong>
                    @if($shopProductSwap->destinationItem->brand) &bull; Brand: <strong>{{ $shopProductSwap->destinationItem->brand }}</strong>@endif
                    @if($shopProductSwap->destinationItem->model) &bull; Model: <strong>{{ $shopProductSwap->destinationItem->model }}</strong>@endif
                </div>

                <div class="p-3 rounded-3 border bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Quantity to Receive:</span>
                        <span class="fw-800 text-info fs-5">
                            {{ $shopProductSwap->destination_quantity }} {{ $shopProductSwap->destinationItem->unit_symbol }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="font-size:.78rem;">
                        <span class="text-muted">Current Live Stock in {{ $shopProductSwap->destinationShop->shop_name }}:</span>
                        <span class="fw-700 text-dark">{{ $destItemInDestShop }} {{ $shopProductSwap->destinationItem->unit_symbol }}</span>
                    </div>
                </div>

                @if($shopProductSwap->destinationItem->specification)
                <div class="small text-muted">
                    <span class="fw-700">Specifications:</span> {{ $shopProductSwap->destinationItem->specification }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Workflow Progress Stepper & Governance --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-custom p-3 border-bottom">
        <h6 class="mb-0 fw-700"><i class="bi bi-diagram-3 me-2 text-primary"></i>Swap Workflow Progress</h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 text-center position-relative">
            {{-- Step 1: Source Shop Admin Request --}}
            <div class="col-md-4">
                <div class="p-3 rounded-3 border h-100 bg-success-subtle border-success">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <div class="fw-700 small">1. Requested by Source Shop</div>
                    <small class="text-muted d-block" style="font-size:.72rem;">{{ $shopProductSwap->sourceShop->shop_name }}</small>
                    <small class="text-dark fw-600 d-block mt-1" style="font-size:.7rem;">By {{ $shopProductSwap->initiator->name ?? 'Shop Admin' }}</small>
                    <small class="text-muted d-block" style="font-size:.68rem;">{{ $shopProductSwap->created_at->format('M d, Y H:i') }}</small>
                </div>
            </div>

            {{-- Step 2: Destination Shop Admin Approval & Confirmation --}}
            <div class="col-md-4">
                @if($shopProductSwap->destination_approved_at)
                <div class="p-3 rounded-3 border h-100 bg-success-subtle border-success">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <div class="fw-700 small">2. Approved & Confirmed</div>
                    <small class="text-muted d-block" style="font-size:.72rem;">{{ $shopProductSwap->destinationShop->shop_name }}</small>
                    <small class="text-dark fw-600 d-block mt-1" style="font-size:.7rem;">By {{ $shopProductSwap->destinationApprover->name ?? 'Shop Admin' }}</small>
                    <small class="text-muted d-block" style="font-size:.68rem;">{{ $shopProductSwap->destination_approved_at->format('M d, Y H:i') }}</small>
                </div>
                @else
                <div class="p-3 rounded-3 border h-100 {{ $shopProductSwap->status === 'cancelled' ? 'bg-light' : 'bg-warning-subtle border-warning' }}">
                    <div class="rounded-circle {{ $shopProductSwap->status === 'cancelled' ? 'bg-secondary text-white' : 'bg-warning text-dark' }} d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="fw-700 small">2. Destination Shop Approval</div>
                    <small class="text-muted d-block" style="font-size:.72rem;">{{ $shopProductSwap->destinationShop->shop_name }}</small>
                    <small class="text-muted d-block mt-1" style="font-size:.7rem;">{{ $shopProductSwap->status === 'cancelled' ? 'Terminated' : 'Awaiting Destination Shop Admin' }}</small>
                </div>
                @endif
            </div>

            {{-- Step 3: Completion & Owner Visibility --}}
            <div class="col-md-4">
                @if($shopProductSwap->status === 'confirmed')
                <div class="p-3 rounded-3 border h-100 bg-success text-white">
                    <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-check2-all fs-5"></i>
                    </div>
                    <div class="fw-800 small">3. Completed & Logged</div>
                    <small class="text-white-50 d-block" style="font-size:.72rem;">Stock Exchanged & Visible to Owner</small>
                </div>
                @elseif($shopProductSwap->status === 'reverted')
                <div class="p-3 rounded-3 border h-100 bg-secondary text-white">
                    <div class="rounded-circle bg-white text-secondary d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
                    </div>
                    <div class="fw-800 small">Reverted</div>
                    <small class="text-white-50 d-block" style="font-size:.72rem;">Original Stock Restored by {{ $shopProductSwap->reverter->name ?? 'Owner' }}</small>
                    <small class="d-block mt-1 text-white-50" style="font-size:.68rem;">{{ $shopProductSwap->reverted_at?->format('M d, Y H:i') }}</small>
                </div>
                @elseif($shopProductSwap->status === 'cancelled')
                <div class="p-3 rounded-3 border h-100 bg-danger text-white">
                    <div class="rounded-circle bg-white text-danger d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-x-lg fs-5"></i>
                    </div>
                    <div class="fw-800 small">Cancelled</div>
                    <small class="text-white-50 d-block" style="font-size:.72rem;">By {{ $shopProductSwap->canceller->name ?? 'User' }}</small>
                    <small class="d-block mt-1 text-white-50" style="font-size:.68rem;">{{ $shopProductSwap->cancelled_at?->format('M d, Y H:i') }}</small>
                </div>
                @else
                <div class="p-3 rounded-3 border h-100 bg-light">
                    <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-2" style="width:36px; height:36px;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="fw-700 small">3. Pending Completion</div>
                    <small class="text-muted d-block" style="font-size:.72rem;">Completes on Destination Approval</small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Reason & Notes Callout --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-lg-4">
        <div class="row g-3">
            <div class="col-md-4">
                <span class="text-muted small d-block">Swap Reason Category:</span>
                <span class="badge bg-secondary-subtle text-secondary py-1 px-2 fw-700 mt-1" style="font-size:.82rem;">
                    {{ $shopProductSwap->reason_label }}
                </span>
            </div>
            <div class="col-md-8">
                <span class="text-muted small d-block">Proposal Notes / Customer Request:</span>
                <div class="fw-600 mt-1" style="font-size:.85rem;">
                    {{ $shopProductSwap->notes ?: 'No additional notes provided.' }}
                </div>
            </div>
        </div>

        @if($shopProductSwap->status === 'cancelled')
        <div class="alert alert-danger border-0 rounded-3 mt-3 mb-0" style="font-size:.82rem;">
            <strong><i class="bi bi-x-circle-fill me-1"></i> Cancellation Reason (by {{ $shopProductSwap->canceller->name ?? 'User' }}):</strong>
            <div>{{ $shopProductSwap->cancellation_reason ?: 'No specific reason entered.' }}</div>
        </div>
        @endif

        @if($shopProductSwap->status === 'reverted')
        <div class="alert alert-secondary border-0 rounded-3 mt-3 mb-0" style="font-size:.82rem;">
            <strong><i class="bi bi-arrow-counterclockwise me-1"></i> Reversion Reason (by {{ $shopProductSwap->reverter->name ?? 'Owner' }}):</strong>
            <div>{{ $shopProductSwap->revert_reason ?: 'No specific reason entered.' }}</div>
        </div>
        @endif
    </div>

    {{-- Contextual Action Bar --}}
    @php
        $canApproveDest = $shopProductSwap->canApproveDestination(auth()->user());
        $canCancel = $shopProductSwap->canCancel(auth()->user());
        $canRevert = $shopProductSwap->canRevert(auth()->user());
    @endphp

    @if($canApproveDest || $canCancel || $canRevert)
    <div class="card-footer bg-custom p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="text-muted small">
            @if($canApproveDest)
                <i class="bi bi-info-circle me-1 text-primary"></i> As Shop Admin for <strong>{{ $shopProductSwap->destinationShop->shop_name }}</strong>, approving will immediately confirm and execute the stock exchange across both branches.
            @elseif($canCancel)
                <i class="bi bi-clock-history me-1 text-warning"></i> Awaiting approval from <strong>{{ $shopProductSwap->destinationShop->shop_name }}</strong>. You may cancel this request before it is approved.
            @elseif($canRevert)
                <i class="bi bi-shield-exclamation me-1 text-secondary"></i> This swap was confirmed by <strong>{{ $shopProductSwap->destinationApprover->name ?? 'Destination Shop Admin' }}</strong>. As Shop Owner, you can safely reverse it if needed.
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            {{-- Cancel button --}}
            @if($canCancel)
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelSwapModal">
                <i class="bi bi-x-circle me-1"></i> {{ $canApproveDest ? 'Decline / Cancel' : 'Cancel Request' }}
            </button>
            @endif

            {{-- Destination Shop Admin Approval & Confirmation --}}
            @if($canApproveDest)
            <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#approveDestinationModal">
                <i class="bi bi-check2-circle me-1"></i> Approve & Confirm Swap
            </button>
            @endif

            {{-- Owner Revert --}}
            @if($canRevert)
            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#revertSwapModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Revert Swap
            </button>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- MODAL 1: Approve & Confirm Swap (Destination Shop Admin) --}}
@if($canApproveDest)
<div class="modal fade" id="approveDestinationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700"><i class="bi bi-check2-circle text-accent me-2"></i>Approve & Confirm Product Swap</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('shop-swaps.approve-destination', $shopProductSwap) }}">
                @csrf
                <div class="modal-body p-3">
                    <p class="small mb-2">
                        Approving this swap will <strong>immediately confirm and execute the stock exchange</strong> between both branches:
                    </p>
                    <ul class="small mb-3 ps-3">
                        <li><strong>{{ $shopProductSwap->sourceShop->shop_name }}</strong>: -{{ $shopProductSwap->source_quantity }} {{ $shopProductSwap->sourceItem->item_name }}, +{{ $shopProductSwap->destination_quantity }} {{ $shopProductSwap->destinationItem->item_name }}.</li>
                        <li><strong>{{ $shopProductSwap->destinationShop->shop_name }}</strong>: -{{ $shopProductSwap->destination_quantity }} {{ $shopProductSwap->destinationItem->item_name }}, +{{ $shopProductSwap->source_quantity }} {{ $shopProductSwap->sourceItem->item_name }}.</li>
                    </ul>
                    <div class="alert alert-warning border-0 py-2 px-3 mb-0" style="font-size:.8rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i> Inventory balances in both shops will be updated immediately, recorded in Stock Logs, and reported to the Shop Owner.
                    </div>
                </div>
                <div class="modal-footer bg-custom p-2">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent"><i class="bi bi-check2-circle me-1"></i> Yes, Approve & Execute Swap</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- MODAL 3: Cancel Proposal --}}
@if($canCancel)
<div class="modal fade" id="cancelSwapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700"><i class="bi bi-x-circle text-danger me-2"></i>Cancel Swap Proposal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('shop-swaps.cancel', $shopProductSwap) }}">
                @csrf
                <div class="modal-body p-3">
                    <p class="small text-muted mb-2">Please state the reason for cancelling this product swap proposal:</p>
                    <textarea name="cancellation_reason" class="form-control form-control-sm" rows="3" placeholder="e.g. Item reserved for another customer, customer changed mind..." required></textarea>
                </div>
                <div class="modal-footer bg-custom p-2">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-circle me-1"></i> Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- MODAL 4: Revert Swap (Owner Only) --}}
@if($canRevert)
<div class="modal fade" id="revertSwapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-custom">
                <h6 class="modal-title fw-700"><i class="bi bi-arrow-counterclockwise text-secondary me-2"></i>Revert Confirmed Swap</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('shop-swaps.revert', $shopProductSwap) }}">
                @csrf
                <div class="modal-body p-3">
                    <p class="small text-muted mb-2">
                        Reverting will <strong>reverse the stock exchange</strong> and restore original products to both shops.
                    </p>
                    <div class="alert alert-info border-0 py-2 px-3 mb-3" style="font-size:.8rem;">
                        The system will verify that both shops still have the swapped items in stock before executing the reversal.
                    </div>
                    <label class="form-label small fw-700">Reason for Reversal *</label>
                    <textarea name="revert_reason" class="form-control form-control-sm" rows="3" placeholder="e.g. Customer returned product, swap made by mistake..." required></textarea>
                </div>
                <div class="modal-footer bg-custom p-2">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i> Revert & Restore Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
