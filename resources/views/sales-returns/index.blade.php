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
                @foreach($returns as $ret)
                <tr>
                    @if($canManage)
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input return-checkbox" value="{{ $ret->id }}">
                        </td>
                    @endif
                    <td class="fw-600">{{ $loop->iteration }}</td>
                    <td>{{ $ret->return_date->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('sales.show', $ret->sale_id) }}" class="fw-600">
                            #SL-{{ $ret->sale_id }}
                        </a>
                    </td>
                    <td>{{ $ret->sale->shop?->shop_name ?? 'Main Store (Owner)' }}</td>
                    <td>
                        @foreach($ret->items as $ri)
                            <div class="small fw-600">
                                {{ $ri->item->item_name }}
                                <span class="badge bg-secondary ms-1">Qty: {{ $ri->quantity }}</span>
                            </div>
                        @endforeach
                    </td>
                    <td class="small text-muted" style="max-width:200px;word-wrap:break-word;">
                        {{ $ret->reason }}
                    </td>
                    <td>{{ $ret->requester?->name ?? 'System' }}</td>
                    <td>
                        @if($ret->status === 'approved')
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;">
                                <i class="bi bi-check-circle-fill me-1"></i>Approved
                            </span>
                            @if($ret->approver)
                                <div class="text-muted mt-1" style="font-size:.68rem;">by {{ $ret->approver->name }}</div>
                            @endif
                        @elseif($ret->status === 'reverted')
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.72rem;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reverted
                            </span>
                        @elseif($ret->status === 'rejected')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.72rem;">
                                <i class="bi bi-x-circle-fill me-1"></i>Rejected
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.72rem;">
                                <i class="bi bi-hourglass-split me-1"></i>Pending Approval
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($canManage)
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                @if($ret->status === 'pending')
                                    <form method="POST" action="{{ route('sales-returns.approve', $ret) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success px-2 py-1"
                                                onclick="return confirm('Confirm approval? Items will be restocked.')">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('sales-returns.reject', $ret) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1"
                                                onclick="return confirm('Confirm rejection?')">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('sales-returns.destroy', $ret) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1"
                                            onclick="return confirm('Are you sure you want to delete this return record?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-muted small">—</span>
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
    if ($.fn.DataTable) {
        $('#returnsTable').DataTable({
            order: [[{{ $canManage ? 1 : 0 }}, 'desc']],
            columnDefs: [{ orderable: false, targets: [{{ $canManage ? '0, 5, 9' : '4, 8' }}] }],
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
