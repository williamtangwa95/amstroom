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
                    $pendingItems = $totalItems - $receivedItems;
                    $pct = $totalItems > 0 ? round(($receivedItems / $totalItems) * 100) : 0;
                @endphp
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-600">Receipt Progress</span>
                    <span class="fw-700">{{ $receivedItems }}/{{ $totalItems }} items</span>
                </div>
                <div class="progress" style="height:8px;border-radius:6px;">
                    <div class="progress-bar {{ $pct == 100 ? 'bg-success' : 'bg-primary' }}"
                         style="width:{{ $pct }}%;border-radius:6px;"></div>
                </div>
                <div class="text-muted mt-1" style="font-size:.72rem;">
                    @if($pendingItems > 0)
                        {{ $pendingItems }} item(s) still pending receipt
                    @else
                        All items received ✓
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Transfer Items --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700"><i class="bi bi-box-seam-fill text-primary me-2"></i>Transfer Items</h6>

                {{-- Bulk approve button for shop admin --}}
                @if(auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop && $pendingItems > 0)
                    <button type="button" class="btn btn-sm btn-accent" onclick="submitBulkApprove()" id="bulkApproveBtn" disabled>
                        <i class="bi bi-check2-all me-1"></i> Approve Selected (<span id="selectedCount">0</span>)
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                {{-- Bulk approve form wraps the table --}}
                <form method="POST" action="{{ route('stock-transfers.approve-bulk', $stockTransfer) }}" id="bulkForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    @if(auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop && $pendingItems > 0)
                                        <th style="width:42px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllCheckboxes(this)">
                                        </th>
                                    @endif
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-center">Qty</th>
                                    <th>Buying Price</th>
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                    @if(auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop)
                                        <th class="text-center">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockTransfer->items as $idx => $ti)
                                <tr class="{{ $ti->status === 'pending' ? 'table-warning' : '' }}">
                                    @if(auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop && $pendingItems > 0)
                                        <td class="text-center">
                                            @if($ti->status === 'pending')
                                                <input type="checkbox" class="form-check-input item-checkbox"
                                                       name="item_ids[]" value="{{ $ti->id }}"
                                                       onchange="updateSelectedCount()">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="fw-600">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-600">{{ $ti->item?->item_name }}</div>
                                        @if($ti->item?->brand)
                                            <small class="text-muted">{{ $ti->item->brand }} {{ $ti->item->model }}</small>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $ti->item?->category?->category_name ?? '—' }}</td>
                                    <td class="text-center fw-700">{{ number_format($ti->quantity) }}</td>
                                    <td>{{ number_format($ti->buying_price, 2) }}</td>
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
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.7rem;">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending
                                            </span>
                                        @endif
                                    </td>
                                    @if(auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop)
                                        <td class="text-center">
                                            @if($ti->status === 'pending')
                                                <form method="POST" action="{{ route('stock-transfers.approve-item', $ti) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve & Receive this item"
                                                            onclick="return confirm('Confirm receipt of this item?')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-600 bg-light">
                                    @if(auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop && $pendingItems > 0)
                                        <td></td>
                                    @endif
                                    <td></td>
                                    <td colspan="2" class="text-end">Total:</td>
                                    <td class="text-center fw-700">{{ number_format($stockTransfer->items->sum('quantity')) }}</td>
                                    <td colspan="{{ (auth()->user()->isShopAdmin() && auth()->user()->shop_id === $stockTransfer->to_shop) ? 3 : 2 }}"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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
    if (confirm(`Confirm receipt of ${checked} selected item(s)?`)) {
        document.getElementById('bulkForm').submit();
    }
}
</script>
@endpush
