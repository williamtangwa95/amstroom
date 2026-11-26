@extends('layouts.app')
@section('title', 'Expenses')
@section('page-title', 'Expenses Ledger')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       EXPENSES — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .ex-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .ex-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .ex-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .ex-total-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .2rem .65rem;
        border-radius: 50px;
        background: rgba(225, 29, 72, .08);
        color: #e11d48;
        font-size: .74rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .ex-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem;
    }
    .ex-controls .btn {
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
    }

    .ex-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .ex-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .95rem 1.05rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .ex-kpi-card.is-clickable {
        cursor: pointer;
    }
    .ex-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .ex-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .ex-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .ex-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .ex-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ex-kpi-val {
        font-size: clamp(.9rem, 1.2vw, 1.15rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.015em;
        word-break: break-word;
    }
    .ex-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ex-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }
    .ex-filter-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary, #64748b);
        margin-bottom: .3rem;
        display: block;
    }

    @media (max-width: 991.98px) {
        .ex-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }
    @media (max-width: 575.98px) {
        .ex-controls {
            width: 100%;
        }
        .ex-controls .btn {
            flex: 1;
        }
        .ex-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .ex-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .ex-kpi-val {
            font-size: .9rem;
        }
    }
    @media (max-width: 420px) {
        .ex-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header --}}
<div class="ex-header">
    <div class="ex-title-row">
        <h5 class="ex-title">
            <i class="bi bi-wallet2 text-danger me-1"></i>Expenses Ledger
        </h5>
        <span class="ex-total-pill">
            <span id="headerTotalExpenses">TZS {{ number_format($totalExpenses, 0) }}</span>
        </span>
    </div>

    <div class="ex-controls">
        @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
        <a href="{{ route('expense-categories.index') }}" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-tags"></i> Categories
        </a>
        <button type="button" id="btn-bulk-approve" class="btn btn-sm btn-success d-none">
            <i class="bi bi-check-all"></i> Approve (<span id="selected-count">0</span>)
        </button>
        @endif
        <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-accent shadow-sm">
            <i class="bi bi-plus-circle"></i> Record Expense
        </a>
    </div>
</div>

{{-- Executive KPI Cards --}}
<div class="ex-kpi-grid">
    <div class="ex-kpi-card is-clickable" data-exp-status="" title="Show all expenses">
        <div class="ex-kpi-icon" style="background:rgba(225,29,72,.1);color:#e11d48;">
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="ex-kpi-body">
            <p class="ex-kpi-label">Total Expenses</p>
            <div class="ex-kpi-val" id="cardTotalExpenses" style="color:#e11d48;">TZS {{ number_format($totalExpenses, 0) }}</div>
            <div class="ex-kpi-sub" id="cardPeriodText">Selected period</div>
        </div>
    </div>

    <div class="ex-kpi-card is-clickable" data-exp-status="approved" title="Filter approved expenses">
        <div class="ex-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="ex-kpi-body">
            <p class="ex-kpi-label">Approved</p>
            <div class="ex-kpi-val" id="cardApprovedExpenses" style="color:#059669;">TZS {{ number_format($approvedExpenses, 0) }}</div>
            <div class="ex-kpi-sub">Approved total</div>
        </div>
    </div>

    <div class="ex-kpi-card is-clickable" data-exp-status="pending" title="Filter pending expenses">
        <div class="ex-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="ex-kpi-body">
            <p class="ex-kpi-label">Pending</p>
            <div class="ex-kpi-val" id="cardPendingExpenses" style="color:#d97706;">TZS {{ number_format($pendingExpenses, 0) }}</div>
            <div class="ex-kpi-sub">Awaiting approval</div>
        </div>
    </div>

    <div class="ex-kpi-card is-clickable" data-exp-status="" title="Show all entries">
        <div class="ex-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-receipt"></i>
        </div>
        <div class="ex-kpi-body">
            <p class="ex-kpi-label">Entries</p>
            <div class="ex-kpi-val" id="cardExpensesCount" style="color:#0077b6;">{{ number_format($expensesCount) }}</div>
            <div class="ex-kpi-sub">Total records</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="ex-card mb-3">
    <div class="p-3">
        <form id="expenseFilterForm" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="date_from" class="ex-filter-label">From</label>
                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="date_to" class="ex-filter-label">To</label>
                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="category_id" class="ex-filter-label">Category</label>
                <select name="category_id" id="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="ex-filter-label">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="review_requested">Review Requested</option>
                    <option value="editable">Editable</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-accent flex-grow-1" style="height:31px;" title="Apply Filter">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <button type="button" id="btn-reset-filter" class="btn btn-sm btn-outline-custom" style="height:31px;" title="Reset to This Month">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="ex-card">
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table {{ (auth()->user()->isOwner() || auth()->user()->isShopAdmin()) ? 'dt-has-checkbox' : '' }}" id="expensesTable">
            <thead>
                <tr>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th data-priority="1000" class="min-tablet-l no-sort text-center" style="width: 40px;"><input type="checkbox" id="select-all-expenses" class="form-check-input"></th>
                    @endif
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="3" class="min-tablet-l">Date</th>
                    <th data-priority="1" class="all primary-col">Category</th>
                    <th data-priority="3" class="min-tablet-l">Activity</th>
                    <th data-priority="4" class="min-tablet-l">Description</th>
                    <th data-priority="2" class="min-tablet-l">Amount</th>
                    <th data-priority="4" class="min-tablet-l">Recorded By</th>
                    <th data-priority="4" class="min-tablet-l">Approved By</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@include('partials.handover_lock_modals')
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
        $('.ex-kpi-card').removeClass('is-active');
        if (table) {
            table.ajax.reload();
        }
    });

    // Quick-filter via KPI cards
    $(document).on('click', '.ex-kpi-card[data-exp-status]', function() {
        const st = $(this).data('exp-status');
        $('#status').val(st);
        $('.ex-kpi-card').removeClass('is-active');
        if (st) $(this).addClass('is-active');
        if (table) table.ajax.reload();
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