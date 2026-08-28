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
        <div class="d-flex align-items-center gap-2">
            @if(auth()->user()->isOwner())
                <button type="button" id="btnBulkDeleteTransfers" class="btn btn-sm btn-outline-danger d-none">
                    <i class="bi bi-trash-fill me-1"></i> Delete Selected (<span id="selectedTransfersCount">0</span>)
                </button>
                <a href="{{ route('stock-transfers.create') }}" class="btn btn-sm btn-accent">
                    <i class="bi bi-plus-circle-fill me-1"></i> Assign Stock to Shop
                </a>
            @endif
        </div>
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
                    @if(auth()->user()->isOwner())
                        <th style="width: 40px;" class="no-sort">
                            <input type="checkbox" id="selectAllTransfers" style="cursor:pointer;">
                        </th>
                    @endif
                    <th>No</th>
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
                    @if(auth()->user()->isOwner())
                        <td>
                            <input type="checkbox" class="transfer-checkbox" value="{{ $transfer->id }}" style="cursor:pointer;">
                        </td>
                    @endif
                    <td class="fw-600">{{ $loop->iteration }}</td>
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
                        @if($transfer->rejected_items_count > 0)
                            <span class="badge bg-danger text-white ms-1" style="font-size:.68rem;">
                                {{ $transfer->rejected_items_count }} rejected
                            </span>
                        @endif
                    </td>
                    <td>{{ $transfer->approver?->name ?? 'System' }}</td>
                    <td>
                        @if($transfer->status === 'received')
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;">
                                <i class="bi bi-check-circle-fill me-1"></i>Received
                            </span>
                        @elseif($transfer->status === 'rejected')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.72rem;">
                                <i class="bi bi-x-circle-fill me-1"></i>Rejected
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
                        @if(auth()->user()->isOwner())
                        <button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-delete-transfer"
                            data-url="{{ route('stock-transfers.destroy', $transfer) }}"
                            data-id="{{ $transfer->id }}"
                            title="Delete transfer and return stock to Main Warehouse">
                            <i class="bi bi-trash-fill me-1"></i> Delete
                        </button>
                        @endif
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
    const isOwner = {{ auth()->user()->isOwner() ? 'true' : 'false' }};
    const nonSortableTargets = isOwner ? [0, 4, 6, 7] : [3, 5, 6];

    if ($.fn.DataTable) {
        $('#transfersTable').DataTable({
            order: [[isOwner ? 1 : 0, 'desc']],
            columnDefs: [{ orderable: false, targets: nonSortableTargets }],
            language: {
                search: '',
                searchPlaceholder: 'Search transfers...',
                emptyTable: 'No stock transfers found.'
            }
        });
    }

    // Hidden forms (avoid nested form issues)
    $('body').append('<form id="deleteTransferForm" method="POST" style="display:none;"><input name="_token" value="{{ csrf_token() }}"><input name="_method" value="DELETE"></form>');
    $('body').append('<form id="bulkDeleteTransfersForm" action="{{ route("stock-transfers.bulk-destroy") }}" method="POST" style="display:none;"><input name="_token" value="{{ csrf_token() }}"><input name="_method" value="DELETE"></form>');

    // Handle Select All Transfers Checkbox
    $(document).on('change', '#selectAllTransfers', function() {
        const isChecked = $(this).is(':checked');
        $('.transfer-checkbox').prop('checked', isChecked).trigger('change');
    });

    // Handle Individual Transfer Checkbox Change
    $(document).on('change', '.transfer-checkbox', function() {
        const totalCheckboxes = $('.transfer-checkbox').length;
        const checkedCheckboxes = $('.transfer-checkbox:checked').length;

        $('#selectAllTransfers').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
        $('#selectedTransfersCount').text(checkedCheckboxes);

        if (checkedCheckboxes > 0) {
            $('#btnBulkDeleteTransfers').removeClass('d-none');
        } else {
            $('#btnBulkDeleteTransfers').addClass('d-none');
        }
    });

    // Handle Single Transfer Delete
    $(document).on('click', '.btn-delete-transfer', function() {
        const url = $(this).data('url');
        const id  = $(this).data('id');
        Swal.fire({
            title: 'Delete Transfer #' + id + '?',
            html: 'All transfer items (including received stock) will be removed and stock <strong>returned to the Main Warehouse</strong>.<br>This action cannot be undone.',
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

    // Handle Bulk Delete Transfers Action
    $(document).on('click', '#btnBulkDeleteTransfers', function() {
        const selectedIds = $('.transfer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Delete ' + selectedIds.length + ' Transfer(s)?',
            html: 'All transfer items for the ' + selectedIds.length + ' selected transfer(s) will be removed and their stock <strong>returned to the Main Warehouse</strong>.<br>This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete selected transfers!',
            cancelButtonText: 'Cancel',
            background: '#161b22',
            color: '#e6edf3'
        }).then(function(result) {
            if (result.isConfirmed) {
                const $form = $('#bulkDeleteTransfersForm');
                $form.find('input[name="ids[]"]').remove();
                selectedIds.forEach(function(id) {
                    $form.append('<input type="hidden" name="ids[]" value="' + id + '">');
                });
                $form.submit();
            }
        });
    });
});
</script>
@endpush
