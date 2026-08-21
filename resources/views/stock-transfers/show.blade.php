@extends('layouts.app')
@section('title', 'Transfer #' . $stockTransfer->id)
@section('page-title', 'Transfer Details')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
<li class="breadcrumb-item active">Transfer #{{ $stockTransfer->id }}</li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 rounded-3 mb-3" style="font-size:.83rem;">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.83rem;">
        <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
    </div>
@endif

{{-- Rejection Attention Banner for Owner & Shop Admin --}}
@php
    $rejectedCount = $stockTransfer->items->where('status', 'rejected')->count();
    $isOwner = auth()->user()->isOwner();
    $isShopAdmin = auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop;
@endphp

@if($rejectedCount > 0)
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3 d-flex align-items-center">
        <div class="me-3 fs-3 text-danger"><i class="bi bi-exclamation-octagon-fill"></i></div>
        <div>
            <h6 class="fw-700 text-danger mb-1"><i class="bi bi-shield-exclamation me-1"></i> Action Required: Rejected Items Detected</h6>
            <p class="mb-0 small text-dark">
                <strong>{{ $rejectedCount }} item(s)</strong> in this transfer were rejected due to mismatch or stock discrepancy.
                @if($isOwner)
                    As the <strong>Owner</strong>, you can edit item details, update quantities, delete rejected items, or add replacement items to update this dispatch.
                @else
                    The owner has been notified to modify the rejected item(s).
                @endif
            </p>
        </div>
    </div>
@endif

<div class="row g-4">
    {{-- Transfer Summary Card --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-700"><i class="bi bi-info-circle text-primary me-2"></i>Transfer Summary</h6>
            </div>
            <div class="card-body p-3">
                <div class="mb-2 d-flex justify-content-between small">
                    <span class="text-muted">Transfer ID:</span>
                    <strong>#{{ $stockTransfer->id }}</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between small">
                    <span class="text-muted">From:</span>
                    <strong>{{ $stockTransfer->from_store }}</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between small">
                    <span class="text-muted">To Shop:</span>
                    <strong class="text-primary">{{ $stockTransfer->shop?->shop_name ?? 'N/A' }}</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between small">
                    <span class="text-muted">Dispatched By:</span>
                    <strong>{{ $stockTransfer->approver?->name ?? 'System' }}</strong>
                </div>
                <div class="mb-2 d-flex justify-content-between small">
                    <span class="text-muted">Transfer Date:</span>
                    <strong>{{ $stockTransfer->transfer_date->format('M d, Y') }}</strong>
                </div>
                @if($stockTransfer->request)
                <div class="mb-2 d-flex justify-content-between small">
                    <span class="text-muted">From Request:</span>
                    <a href="{{ route('stock-requests.show', $stockTransfer->request) }}" class="fw-600">
                        #{{ $stockTransfer->request_id }}
                    </a>
                </div>
                @endif
                <div class="mb-0 d-flex justify-content-between small">
                    <span class="text-muted">Status:</span>
                    @if($stockTransfer->status === 'received')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i>Fully Received
                        </span>
                    @elseif($stockTransfer->status === 'rejected')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Has Rejections
                        </span>
                    @elseif($stockTransfer->status === 'partially_received')
                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                            <i class="bi bi-clock-fill me-1"></i>Partially Received
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-hourglass-split me-1"></i>Pending Receipt
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Receipt Progress --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body p-3">
                @php
                    $totalItems = $stockTransfer->items->count();
                    $receivedItems = $stockTransfer->items->where('status', 'received')->count();
                    $rejectedItemsCount = $stockTransfer->items->where('status', 'rejected')->count();
                    $pendingItems = $totalItems - $receivedItems - $rejectedItemsCount;
                    $pct = $totalItems > 0 ? round(($receivedItems / $totalItems) * 100) : 0;
                @endphp
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-600">Receipt Progress</span>
                    <span class="fw-700">{{ $receivedItems }}/{{ $totalItems }} items received</span>
                </div>
                <div class="progress" style="height:8px;border-radius:6px;">
                    <div class="progress-bar {{ $pct == 100 ? 'bg-success' : 'bg-primary' }}"
                         style="width:{{ $pct }}%;border-radius:6px;"></div>
                </div>
                <div class="text-muted mt-2 d-flex justify-content-between align-items-center" style="font-size:.75rem;">
                    <span>Pending: <strong>{{ $pendingItems }}</strong></span>
                    <span>Rejected: <strong class="text-danger">{{ $rejectedItemsCount }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Transfer Items List --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700"><i class="bi bi-box-seam-fill text-primary me-2"></i>Transfer Items</h6>

                <div class="d-flex align-items-center gap-2">
                    {{-- Add Item button for Owner --}}
                    @if($isOwner)
                        <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Another Item
                        </button>
                    @endif

                    {{-- Delete Transfer button (owner only, no received items) --}}
                    @if($isOwner && $stockTransfer->items->where('status','received')->count() === 0)
                        <button type="button" class="btn btn-sm btn-outline-danger" id="deleteTransferBtn"
                            data-url="{{ route('stock-transfers.destroy', $stockTransfer) }}"
                            data-id="{{ $stockTransfer->id }}"
                            title="Delete this transfer and return stock to warehouse">
                            <i class="bi bi-trash-fill me-1"></i> Delete Transfer
                        </button>
                    @endif

                    {{-- Bulk approve button for shop admin --}}
                    @if($isShopAdmin && $pendingItems > 0)
                        <button type="button" class="btn btn-sm btn-success" onclick="submitBulkApprove()" id="bulkApproveBtn" disabled>
                            <i class="bi bi-check2-all me-1"></i> Approve Selected (<span id="selectedCount">0</span>)
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                {{-- Bulk approve form wraps table --}}
                <form method="POST" action="{{ route('stock-transfers.approve-bulk', $stockTransfer) }}" id="bulkForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    @if($isShopAdmin && $pendingItems > 0)
                                        <th style="width:42px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllCheckboxes(this)">
                                        </th>
                                    @endif
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-center">Qty</th>
                                    <!-- <th>Buying Price</th> -->
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                    @if($isShopAdmin || $isOwner)
                                        <th class="text-center">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockTransfer->items as $idx => $ti)
                                <tr class="{{ $ti->status === 'pending' ? 'table-warning' : ($ti->status === 'rejected' ? 'table-danger' : '') }}">
                                    @if($isShopAdmin && $pendingItems > 0)
                                        <td class="text-center">
                                            @if($ti->status === 'pending')
                                                @php
                                                    $existingPrice = \App\Models\ShopStock::where('item_id', $ti->item_id)
                                                        ->where('shop_id', $stockTransfer->to_shop)
                                                        ->where('remaining_quantity', '>', 0)
                                                        ->orderByDesc('date_received')
                                                        ->value('selling_price');
                                                @endphp
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
                                    <td class="fw-600">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-600 text-dark">{{ $ti->item?->item_name }}</div>
                                        @if($ti->item?->brand)
                                            <small class="text-muted">{{ $ti->item->brand }} {{ $ti->item->model }}</small>
                                        @endif

                                        {{-- Display Rejection Reason if Rejected --}}
                                        @if($ti->status === 'rejected' && $ti->rejection_reason)
                                            <div class="mt-2 p-2 bg-white rounded border border-danger-subtle shadow-sm" style="font-size:.78rem;">
                                                <div class="text-danger fw-700 mb-1">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Rejection Reason:
                                                </div>
                                                <div class="text-dark">{{ $ti->rejection_reason }}</div>
                                                @if($ti->rejecter)
                                                    <div class="text-muted mt-1" style="font-size:.7rem;">
                                                        Rejected by <strong>{{ $ti->rejecter->name }}</strong> on {{ $ti->rejected_at?->format('M d, Y H:i') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $ti->item?->category?->category_name ?? '—' }}</td>
                                    <td class="text-center fw-700 fs-6">{{ number_format($ti->quantity) }}</td>
                                    <!-- <td>{{ number_format($ti->buying_price, 2) }}</td> -->
                                    <td>{{ number_format($ti->selling_price, 2) }}</td>
                                    <td>
                                        @if($ti->status === 'received')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.7rem;">
                                                <i class="bi bi-check-circle-fill me-1"></i>Received
                                            </span>
                                            @if($ti->receiver)
                                                <div class="text-muted mt-1" style="font-size:.68rem;">
                                                    by {{ $ti->receiver->name }}<br>
                                                    {{ $ti->received_at?->format('M d, H:i') }}
                                                </div>
                                            @endif
                                        @elseif($ti->status === 'rejected')
                                            <span class="badge bg-danger text-white" style="font-size:.7rem;">
                                                <i class="bi bi-x-circle-fill me-1"></i>Rejected
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.7rem;">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Actions Column --}}
                                    @if($isShopAdmin || $isOwner)
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                {{-- Shop Admin Actions (Approve / Reject) --}}
                                                @if($isShopAdmin && $ti->status === 'pending')
                                                    <button type="button" class="btn btn-sm btn-success px-2 py-1" title="Approve Item"
                                                            data-bs-toggle="modal" data-bs-target="#approveModal{{ $ti->id }}">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Reject Item"
                                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $ti->id }}">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                @endif

                                                {{-- Owner Actions (Edit / Delete for pending or rejected items) --}}
                                                @if($isOwner && in_array($ti->status, ['pending', 'rejected']))
                                                    <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" title="Edit Item"
                                                            data-bs-toggle="modal" data-bs-target="#editItemModal{{ $ti->id }}">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Item"
                                                            onclick="submitDeleteItem('{{ route('stock-transfers.delete-item', $ti) }}')">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                @endif

                                                @if($ti->status === 'received' && !$isOwner)
                                                    <span class="text-success small"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        No items in this stock transfer.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="fw-600 bg-light">
                                    @if($isShopAdmin && $pendingItems > 0)
                                        <td></td>
                                    @endif
                                    <td></td>
                                    <td colspan="2" class="text-end">Total Items Quantity:</td>
                                    <td class="text-center fw-700 fs-6">{{ number_format($stockTransfer->items->sum('quantity')) }}</td>
                                    <td colspan="{{ ($isShopAdmin || $isOwner) ? 3 : 2 }}"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Bulk Approve Modal --}}
                    @if($isShopAdmin && $pendingItems > 0)
                    <div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-labelledby="bulkApproveModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 shadow text-start">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title fw-700" id="bulkApproveModalLabel">
                                        <i class="bi bi-check2-all me-2"></i>Bulk Receive Items
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <p class="small text-muted mb-3">
                                        You are receiving multiple items. For each item, please determine the selling price to be used in your shop. The buying price for your shop is set to the owner's selling price.
                                    </p>
                                    <div id="bulkApproveModalBody">
                                        <!-- Dynamically generated via JS -->
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-all me-1"></i> Confirm & Receive Selected</button>
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

{{-- MODALS --}}

{{-- 1. Owner: Add Item Modal --}}
@if($isOwner)
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('stock-transfers.add-item', $stockTransfer) }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-700" id="addItemModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Item to Transfer #{{ $stockTransfer->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Select Item</label>
                        <select name="item_id" class="form-select" required>
                            <option value="" disabled selected>-- Choose Product from Main Warehouse --</option>
                            @foreach($items as $itm)
                                @php
                                    $avail = $availableStock[$itm->id] ?? 0;
                                @endphp
                                <option value="{{ $itm->id }}" {{ $avail <= 0 ? 'disabled' : '' }}>
                                    {{ $itm->item_name }} ({{ $itm->category?->category_name ?? 'General' }}) — Available Stock: {{ $avail }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Transfer Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i> Add & Dispatch Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Item Level Modals (Edit Modal for Owner & Reject Modal for Admin) --}}
@foreach($stockTransfer->items as $ti)
    {{-- Edit Item Modal (Owner) --}}
    @if($isOwner && in_array($ti->status, ['pending', 'rejected']))
    <div class="modal fade" id="editItemModal{{ $ti->id }}" tabindex="-1" aria-labelledby="editItemModalLabel{{ $ti->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('stock-transfers.update-item', $ti) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-700" id="editItemModalLabel{{ $ti->id }}">
                            <i class="bi bi-pencil-square me-2"></i>Edit Transfer Item #{{ $ti->id }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-600 small">Product Item</label>
                            <select name="item_id" class="form-select" required>
                                @foreach($items as $itm)
                                    @php
                                        $avail = ($availableStock[$itm->id] ?? 0) + ($itm->id == $ti->item_id ? $ti->quantity : 0);
                                    @endphp
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
                            <div class="alert alert-warning border-0 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Saving updates will clear the rejection status and set item back to <strong>Pending</strong> for re-inspection.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Item Modal (Shop Admin / Admin) --}}
    @if($isShopAdmin && $ti->status === 'pending')
    <div class="modal fade" id="rejectModal{{ $ti->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $ti->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('stock-transfers.reject-item', $ti) }}">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-700" id="rejectModalLabel{{ $ti->id }}">
                            <i class="bi bi-x-circle me-2"></i>Reject Transfer Item: {{ $ti->item?->item_name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="small text-muted mb-3">
                            Please state the exact reason for rejecting this item (e.g. quantity mismatch, wrong model, damaged stock). This reason will notify the Owner for modification.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-600 small text-danger">Rejection Reason *</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Enter reason for mismatch..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-circle me-1"></i> Reject Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Receive/Approve Item Modal (Shop Admin / Admin) --}}
    @if($isShopAdmin && $ti->status === 'pending')
    @php
        // Look up existing shop stock selling price for this item
        $existingShopPrice = \App\Models\ShopStock::where('item_id', $ti->item_id)
            ->where('shop_id', $stockTransfer->to_shop)
            ->where('remaining_quantity', '>', 0)
            ->orderByDesc('date_received')
            ->value('selling_price');
        $defaultSellingPrice = $existingShopPrice ?? $ti->selling_price;
    @endphp
    <div class="modal fade" id="approveModal{{ $ti->id }}" tabindex="-1" aria-labelledby="approveModalLabel{{ $ti->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('stock-transfers.approve-item', $ti) }}">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-700" id="approveModalLabel{{ $ti->id }}">
                            <i class="bi bi-check-circle me-2"></i>Receive Item: {{ $ti->item?->item_name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-muted mb-3">
                            You are receiving this item. Please confirm or adjust the selling price for your shop. The buying price is the owner's selling price.
                        </p>
                        @if($existingShopPrice)
                        <div class="alert py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:.8rem;background:rgba(57,178,255,.08);border:1px solid rgba(57,178,255,.22);color:#39b2ff;">
                            <i class="bi bi-info-circle-fill fs-6"></i>
                            <span>This item already exists in your shop stock. The current selling price <strong>TZS {{ number_format($existingShopPrice, 0) }}</strong> has been pre-filled.</span>
                        </div>
                        @endif
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Qty</th>
                                    <th>Buying Price (TZS)</th>
                                    <th style="width: 220px;">Selling Price (TZS) *</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-600">{{ $ti->item?->item_name }}</div>
                                        @if($ti->item?->brand)
                                            <small class="text-muted">{{ $ti->item->brand }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $ti->quantity }}</td>
                                    <td>TZS {{ number_format($ti->selling_price, 0) }}</td>
                                    <td>
                                        <input type="text" name="selling_price"
                                               class="form-control form-control-sm currency-input approve-item-selling-price"
                                               value="{{ $defaultSellingPrice }}"
                                               data-buying-price="{{ $ti->selling_price }}"
                                               required>
                                        <div class="text-danger small price-warning mt-1" style="display:none;">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!
                                        </div>
                                        @if($existingShopPrice)
                                        <span class="text-info" style="font-size:.72rem;">
                                            <i class="bi bi-tag-fill me-1"></i>Current shop price: <strong>TZS {{ number_format($existingShopPrice, 0) }}</strong>
                                        </span>
                                        @else
                                        <span class="text-muted" style="font-size:.72rem;">
                                            <i class="bi bi-tag me-1"></i>New item — no existing shop price.
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i> Receive & Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

{{-- Hidden Forms to Avoid Nested Form Issues in HTML --}}
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
    const checked = document.querySelectorAll('.item-checkbox:checked').length;
    const countEl = document.getElementById('selectedCount');
    const btn = document.getElementById('bulkApproveBtn');
    if (countEl) countEl.textContent = checked;
    if (btn) btn.disabled = (checked === 0);
}

function submitBulkApprove() {
    const checked = document.querySelectorAll('.item-checkbox:checked').length;
    if (checked === 0) {
        alert('Please select at least one item to approve.');
        return;
    }
    
    const modalBody = document.getElementById('bulkApproveModalBody');
    let html = `
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Qty</th>
                    <th>Buying Price (TZS)</th>
                    <th style="width: 200px;">Selling Price (TZS) *</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const itemId  = cb.value;
        const name    = cb.dataset.name;
        const qty     = cb.dataset.qty;
        const price   = cb.dataset.price;          // buying price (owner's selling price)
        const suggested = cb.dataset.suggestedPrice; // existing shop price (or buying price)
        const hasExisting = cb.dataset.hasExisting === 'true';

        html += `
            <tr>
                <td>
                    <div class="fw-600">${name}</div>
                </td>
                <td>${qty}</td>
                <td>TZS ${parseInt(price).toLocaleString()}</td>
                <td>
                    <input type="text" name="selling_prices[${itemId}]" class="form-control form-control-sm currency-input bulk-selling-price" data-buying-price="${price}" value="${suggested}" required>
                    <div class="text-danger small price-warning mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                    ${hasExisting
                        ? `<span class="text-info" style="font-size:.72rem;"><i class="bi bi-tag-fill me-1"></i>Current shop price: <strong>TZS ${parseInt(suggested).toLocaleString()}</strong></span>`
                        : `<span class="text-muted" style="font-size:.72rem;"><i class="bi bi-tag me-1"></i>New item — no existing shop price.</span>`
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
    
    const myModal = new bootstrap.Modal(document.getElementById('bulkApproveModal'));
    myModal.show();

    // Format currency inputs on show
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
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'Cancel',
        background: '#161b22',
        color: '#e6edf3'
    }).then(function(result) {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteItemForm');
            form.action = url;
            form.submit();
        }
    });
}

// Delete entire transfer handler
$(document).ready(function() {
    $('#deleteTransferBtn').on('click', function() {
        const url = $(this).data('url');
        const id  = $(this).data('id');
        Swal.fire({
            title: 'Delete Transfer #' + id + '?',
            html: 'All pending/rejected items will have their stock <strong>returned to the Main Warehouse</strong>.<br>This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete transfer!',
            cancelButtonText: 'Cancel',
            background: '#161b22',
            color: '#e6edf3'
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
    
    // Check if there are any invalid inputs in this form
    const hasInvalid = form.find('.is-invalid').length > 0;
    submitBtn.prop('disabled', hasInvalid);
});
</script>
@endpush
