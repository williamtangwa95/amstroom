@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('page-title', 'Stock Transfers & Dispatches')

@section('breadcrumb')
<li class="breadcrumb-item active">Stock Transfers</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-700">
            <i class="bi bi-truck text-primary me-2"></i>All Stock Transfers
        </h6>
        @if(auth()->user()->isOwner())
            <a href="{{ route('stock-transfers.create') }}" class="btn btn-sm btn-accent">
                <i class="bi bi-plus-circle-fill me-1"></i> Assign Stock to Shop
            </a>
        @endif
    </div>

    <div class="card-body p-0">
        @if(session('success'))
            <div class="alert alert-success m-3 border-0 rounded-3" style="font-size:.83rem;">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger m-3 border-0 rounded-3" style="font-size:.83rem;">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
            </div>
        @endif

        {{-- Pending notification banner for shop admin --}}
        @if(auth()->user()->isShopAdmin())
            @php
                $pendingCount = \App\Models\StockTransfer::where('to_shop', auth()->user()->shop_id)
                    ->whereIn('status', ['pending_receipt', 'partially_received'])->count();
            @endphp
            @if($pendingCount > 0)
                <div class="alert alert-warning m-3 border-0 rounded-3 d-flex align-items-center" style="font-size:.84rem;">
                    <i class="bi bi-bell-fill text-warning me-2" style="font-size:1.2rem;"></i>
                    <div>
                        <strong>{{ $pendingCount }} dispatch{{ $pendingCount > 1 ? 'es' : '' }}</strong> from Main Warehouse awaiting your receipt confirmation.
                        Please review and approve items below.
                    </div>
                </div>
            @endif
        @endif

        <table class="table table-hover mb-0" id="transfersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Transfer Date</th>
                    <th>Destination Shop</th>
                    <th>Items</th>
                    <th>Dispatched By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $transfer)
                <tr>
                    <td class="fw-600">{{ $transfer->id }}</td>
                    <td>{{ $transfer->transfer_date->format('M d, Y') }}</td>
                    <td>
                        <i class="bi bi-shop text-primary me-1"></i>
                        {{ $transfer->shop?->shop_name ?? 'N/A' }}
                    </td>
                    <td>
                        <span class="fw-600">{{ $transfer->items_count }}</span>
                        @if($transfer->pending_items_count > 0)
                            <span class="badge bg-warning text-dark ms-1" style="font-size:.68rem;">
                                {{ $transfer->pending_items_count }} pending
                            </span>
                        @endif
                    </td>
                    <td>{{ $transfer->approver?->name ?? 'System' }}</td>
                    <td>
                        @if($transfer->status === 'received')
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;">
                                <i class="bi bi-check-circle-fill me-1"></i>Received
                            </span>
                        @elseif($transfer->status === 'partially_received')
                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.72rem;">
                                <i class="bi bi-clock-fill me-1"></i>Partial
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.72rem;">
                                <i class="bi bi-hourglass-split me-1"></i>Pending Receipt
                            </span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('stock-transfers.show', $transfer) }}" class="btn btn-sm btn-outline-custom">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($transfers->hasPages())
        <div class="px-3 py-2 border-top">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    if ($.fn.DataTable) {
        $('#transfersTable').DataTable({
            paging: false,
            info: false,
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, targets: [3, 5, 6] }],
            language: {
                search: '',
                searchPlaceholder: 'Search transfers...',
                emptyTable: 'No stock transfers found.'
            }
        });
    }
});
</script>
@endpush
