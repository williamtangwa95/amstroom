@extends('layouts.app')
@section('title', 'Sales Returns')
@section('page-title', 'Sales Returns & Refunds')

@section('breadcrumb')
<li class="breadcrumb-item active">Sales Returns</li>
@endsection

@section('content')
@php $canManage = auth()->user()->isShopAdmin() || auth()->user()->isOwner(); @endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-700">
            <i class="bi bi-arrow-counterclockwise text-danger me-2"></i>Customer Sales Returns
        </h6>
        @if($canManage)
            <button type="button" class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn" onclick="submitBulkDelete()">
                <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
            </button>
            <form id="bulkActionForm" action="{{ route('sales-returns.bulk-destroy') }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
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
                $pendingReturns = \App\Models\SaleReturn::whereHas('sale', function ($q) {
                    $q->where('shop_id', auth()->user()->shop_id);
                })->where('status', 'pending')->count();
            @endphp
            @if($pendingReturns > 0)
                <div class="alert alert-warning m-3 border-0 rounded-3 d-flex align-items-center" style="font-size:.84rem;">
                    <i class="bi bi-bell-fill text-warning me-2" style="font-size:1.2rem;"></i>
                    <div>
                        <strong>{{ $pendingReturns }} return request{{ $pendingReturns > 1 ? 's' : '' }}</strong> awaiting your approval to restock.
                    </div>
                </div>
            @endif
        @endif

        <table class="table table-hover mb-0" id="returnsTable">
            <thead>
                <tr>
                    @if($canManage)
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="selectAllCb">
                        </th>
                    @endif
                    <th>No</th>
                    <th>Return Date</th>
                    <th>Sale ID</th>
                    <th>Shop Branch</th>
                    <th>Returned Items</th>
                    <th>Reason</th>
                    <th>Requested By</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function submitBulkDelete() {
    const selectedIds = [];
    $('.return-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one record.');
        return;
    }
    
    if (confirm('Are you sure you want to delete the selected ' + selectedIds.length + ' record(s)?')) {
        const form = $('#bulkActionForm');
        form.find('input[name="ids[]"]').remove();
        selectedIds.forEach(id => {
            form.append('<input type="hidden" name="ids[]" value="' + id + '">');
        });
        form.submit();
    }
}

$(function() {
    const canManage = {{ $canManage ? 'true' : 'false' }};

    var columns = [];
    if (canManage) {
        columns.push({ data: 'checkbox', name: 'checkbox', orderable: false, searchable: false });
    }
    columns.push({ data: 'iteration', name: 'iteration' });
    columns.push({ data: 'return_date', name: 'return_date' });
    columns.push({ data: 'sale_id', name: 'sale_id' });
    columns.push({ data: 'shop', name: 'shop' });
    columns.push({ data: 'items', name: 'items', orderable: false, searchable: false });
    columns.push({ data: 'reason', name: 'reason' });
    columns.push({ data: 'requester', name: 'requester' });
    columns.push({ data: 'status', name: 'status' });
    columns.push({ data: 'actions', name: 'actions', orderable: false, searchable: false });

    if ($.fn.DataTable) {
        $('#returnsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: "{{ route('sales-returns.data') }}",
            columns: columns,
            order: [[canManage ? 2 : 1, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Search returns...',
                emptyTable: 'No sale returns found.'
            }
        });
    }

    $('#selectAllCb').on('change', function() {
        const checked = this.checked;
        $('.return-checkbox').prop('checked', checked).trigger('change');
    });

    $(document).on('change', '.return-checkbox', function() {
        const checkedCount = $('.return-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkDeleteBtn').removeClass('d-none');
            $('#selectedCount').text(checkedCount);
        } else {
            $('#bulkDeleteBtn').addClass('d-none');
        }
        
        $('#selectAllCb').prop('checked', checkedCount === $('.return-checkbox').length);
    });
});
</script>
@endpush
