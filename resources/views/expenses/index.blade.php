@extends('layouts.app')
@section('title', 'Expenses')
@section('page-title', 'Expenses Ledger')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-700">Expenses Ledger</h5>
        <small style="color:var(--text-secondary);">Total Expenses: <strong id="headerTotalExpenses" style="color:#e94560;">TZS {{ number_format($totalExpenses, 0) }}</strong></small>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        <a href="{{ route('expense-categories.index') }}" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-tags-fill me-1"></i> Categories
        </a>
        <button type="button" id="btn-bulk-approve" class="btn btn-sm btn-success d-none">
            <i class="bi bi-check-all me-1"></i> Approve Selected (<span id="selected-count">0</span>)
        </button>
        @endif
        <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-accent">
            <i class="bi bi-plus-circle me-1"></i> Record Expense
        </a>
    </div>
</div>

{{-- Expense Summary Cards --}}
<div class="row g-2 g-md-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Total Expenses</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(233,69,96,0.15); color:#e94560;">
                    <i class="bi bi-wallet2 fs-6"></i>
                </div>
            </div>
            <div class="fw-800 fs-5 mb-0 text-truncate" id="cardTotalExpenses" style="color:#e94560;">TZS {{ number_format($totalExpenses, 0) }}</div>
            <div class="small text-muted" style="font-size:0.68rem;" id="cardPeriodText">Selected Period Total</div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Approved</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(63,185,80,0.15); color:#3fb950;">
                    <i class="bi bi-check-circle-fill fs-6"></i>
                </div>
            </div>
            <div class="fw-800 fs-5 mb-0 text-truncate" id="cardApprovedExpenses" style="color:#3fb950;">TZS {{ number_format($approvedExpenses, 0) }}</div>
            <div class="small text-muted" style="font-size:0.68rem;">Approved Expenses</div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Pending</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(227,179,65,0.15); color:#e3b341;">
                    <i class="bi bi-hourglass-split fs-6"></i>
                </div>
            </div>
            <div class="fw-800 fs-5 mb-0 text-truncate" id="cardPendingExpenses" style="color:#e3b341;">TZS {{ number_format($pendingExpenses, 0) }}</div>
            <div class="small text-muted" style="font-size:0.68rem;">Awaiting Approval</div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.72rem;">Entries</span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; background:rgba(88,166,255,0.15); color:#58a6ff;">
                    <i class="bi bi-receipt fs-6"></i>
                </div>
            </div>
            <div class="fw-800 fs-5 mb-0" id="cardExpensesCount" style="color:#58a6ff;">{{ number_format($expensesCount) }}</div>
            <div class="small text-muted" style="font-size:0.68rem;">Total Entries</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card mb-4 border-0 shadow-sm" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
    <div class="card-body py-2.5 px-3">
        <form id="expenseFilterForm" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem; color:var(--text-secondary); font-weight:600;">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem; color:var(--text-secondary); font-weight:600;">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem; color:var(--text-secondary); font-weight:600;">Category</label>
                <select name="category_id" id="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem; color:var(--text-secondary); font-weight:600;">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="review_requested">Review Requested</option>
                    <option value="editable">Editable</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-sm btn-accent flex-grow-1" title="Apply Filter">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <button type="button" id="btn-reset-filter" class="btn btn-sm btn-outline-custom" title="Reset to This Month">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
        <i class="bi bi-wallet2 me-2 text-success"></i><strong class="fw-700">Expenses Ledger</strong>
    </div>
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100" id="expensesTable">
            <thead>
                <tr>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th data-priority="1000" class="no-sort" style="width: 40px;"><input type="checkbox" id="select-all-expenses" class="form-check-input"></th>
                    @endif
                    <th data-priority="1" class="text-center" style="width: 45px;">#</th>
                    <th data-priority="3">Date</th>
                    <th data-priority="1">Category</th>
                    <th data-priority="3">Activity</th>
                    <th data-priority="4">Description</th>
                    <th data-priority="2">Amount</th>
                    <th data-priority="4">Recorded By</th>
                    <th data-priority="4">Approved By</th>
                    <th data-priority="2">Status</th>
                    <th data-priority="3" class="no-sort text-end" style="min-width: 100px;">Actions</th>
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
    const defaultDateFrom = '{{ now()->startOfMonth()->toDateString() }}';
    const defaultDateTo = '{{ now()->endOfMonth()->toDateString() }}';

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

    let table = null;
    if ($.fn.DataTable) {
        table = $('#expensesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('expenses.data') }}",
                data: function(d) {
                    d.date_from = $('#date_from').val();
                    d.date_to = $('#date_to').val();
                    d.category_id = $('#category_id').val();
                    d.status = $('#status').val();
                }
            },
            columns: columns,
            order: [[isOwnerOrAdmin ? 2 : 1, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Search expenses...',
                emptyTable: 'No expenses found.'
            }
        });

        table.on('xhr', function() {
            var json = table.ajax.json();
            if (json) {
                if (json.formattedTotalExpenses !== undefined) {
                    $('#cardTotalExpenses').text(json.formattedTotalExpenses);
                    $('#headerTotalExpenses').text(json.formattedTotalExpenses);
                }
                if (json.approvedExpenses !== undefined) {
                    $('#cardApprovedExpenses').text(json.approvedExpenses);
                }
                if (json.pendingExpenses !== undefined) {
                    $('#cardPendingExpenses').text(json.pendingExpenses);
                }
                if (json.totalCount !== undefined) {
                    $('#cardExpensesCount').text(json.totalCount);
                }
            }
        });
    }

    // Apply Filter Form
    $('#expenseFilterForm').on('submit', function(e) {
        e.preventDefault();
        if (table) {
            table.ajax.reload();
        }
    });

    // Reset Filter Button
    $('#btn-reset-filter').on('click', function() {
        $('#date_from').val(defaultDateFrom);
        $('#date_to').val(defaultDateTo);
        $('#category_id').val('');
        $('#status').val('');
        if (table) {
            table.ajax.reload();
        }
    });

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
                    if (table) {
                        table.ajax.reload();
                    } else {
                        location.reload();
                    }
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