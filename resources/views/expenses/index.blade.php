@extends('layouts.app')
@section('title', 'Expenses')
@section('page-title', 'Expenses Ledger')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        <a href="{{ route('expense-categories.index') }}" class="btn btn-sm btn-outline-custom me-2">
            <i class="bi bi-tags-fill me-1"></i> Categories
        </a>
        <button type="button" id="btn-bulk-approve" class="btn btn-sm btn-success d-none">
            <i class="bi bi-check-all me-1"></i> Approve Selected (<span id="selected-count">0</span>)
        </button>
        @endif
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-accent">
        <i class="bi bi-plus-circle me-1"></i> Record Expense
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><i class="bi bi-wallet2 me-2" style="color:#3fb950;"></i>Expenses Ledger</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="expensesTable">
            <thead>
                <tr>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th style="width: 40px;"><input type="checkbox" id="select-all-expenses" class="form-check-input"></th>
                    @endif
                    <th>No</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Recorded By</th>
                    <th>Approved By</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
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
$(function() {
    const isOwnerOrAdmin = {{ (auth()->user()->isOwner() || auth()->user()->isShopAdmin()) ? 'true' : 'false' }};

    var columns = [];
    if (isOwnerOrAdmin) {
        columns.push({ data: 'checkbox', name: 'checkbox', orderable: false, searchable: false });
    }
    columns.push({ data: 'iteration', name: 'iteration' });
    columns.push({ data: 'date', name: 'date' });
    columns.push({ data: 'category', name: 'category' });
    columns.push({ data: 'activity', name: 'activity' });
    columns.push({ data: 'description', name: 'description' });
    columns.push({ data: 'amount', name: 'amount' });
    columns.push({ data: 'recorder', name: 'recorder' });
    columns.push({ data: 'approver', name: 'approver' });
    columns.push({ data: 'status', name: 'status' });
    columns.push({ data: 'actions', name: 'actions', orderable: false, searchable: false });

    if ($.fn.DataTable) {
        $('#expensesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: "{{ route('expenses.data') }}",
            columns: columns,
            order: [[isOwnerOrAdmin ? 2 : 1, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Search expenses...',
                emptyTable: 'No expenses found.'
            }
        });
    }

    // Select All
    $('#select-all-expenses').on('change', function() {
        $('.expense-checkbox').prop('checked', this.checked).trigger('change');
    });

    // Checkbox counter
    $(document).on('change', '.expense-checkbox', function() {
        const count = $('.expense-checkbox:checked').length;
        $('#selected-count').text(count);
        if (count > 0) {
            $('#btn-bulk-approve').removeClass('d-none');
        } else {
            $('#btn-bulk-approve').addClass('d-none');
        }
    });

    // Bulk Approve action
    $('#btn-bulk-approve').on('click', function() {
        const ids = $('.expense-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (ids.length === 0) return;

        if (confirm('Approve ' + ids.length + ' selected expense(s)?')) {
            $.ajax({
                url: "{{ route('expenses.bulk-approve') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids
                },
                success: function(res) {
                    location.reload();
                },
                error: function(err) {
                    alert('Failed to approve selected expenses.');
                }
            });
        }
    });
});
</script>
@endpush