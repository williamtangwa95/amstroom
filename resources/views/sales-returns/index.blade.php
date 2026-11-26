@extends('layouts.app')
@section('title', 'Sales Returns')
@section('page-title', 'Sales Returns & Refunds')

@section('breadcrumb')
<li class="breadcrumb-item active">Sales Returns</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       SALES RETURNS — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .ret-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .ret-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .ret-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .ret-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 50px;
        background: rgba(220, 38, 38, .08);
        color: #dc2626;
        font-size: .72rem;
        font-weight: 600;
    }

    .ret-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .ret-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .95rem 1.05rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        cursor: pointer;
        min-width: 0;
    }
    .ret-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .ret-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .ret-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .ret-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .ret-kpi-label {
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
    .ret-kpi-val {
        font-size: clamp(1.05rem, 1.4vw, 1.3rem);
        font-weight: 800;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }
    .ret-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ret-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .ret-toolbar {
        padding: .8rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #fff;
    }
    .ret-pills {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .ret-pill-btn {
        border: none;
        background: transparent;
        color: #475569;
        font-size: .74rem;
        font-weight: 600;
        padding: .3rem .68rem;
        border-radius: 7px;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }
    .ret-pill-btn:hover {
        color: #0f172a;
    }
    .ret-pill-btn.active {
        background: #fff;
        color: #0077b6;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }

    @media (max-width: 991.98px) {
        .ret-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }
    @media (max-width: 575.98px) {
        .ret-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .ret-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .ret-toolbar {
            padding: .75rem .9rem;
        }
        .ret-pills {
            width: 100%;
        }
        .ret-pill-btn {
            flex: 1;
            text-align: center;
            padding: .3rem .35rem;
        }
    }
</style>
@endpush

@section('content')
@php $canManage = auth()->user()->isShopAdmin() || auth()->user()->isOwner(); @endphp

{{-- Compact Header --}}
<div class="ret-header">
    <div class="ret-title-row">
        <h5 class="ret-title">
            <i class="bi bi-arrow-counterclockwise text-danger me-1"></i>Sales Returns
        </h5>
        <span class="ret-pill">
            <i class="bi bi-receipt"></i>Refunds &amp; Restock
        </span>
    </div>

    @if($canManage)
    <div>
        <button type="button" class="btn btn-sm btn-danger d-none" id="bulkDeleteBtn" onclick="submitBulkDelete()" style="height:34px;border-radius:9px;">
            <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
        </button>
        <form id="bulkActionForm" action="{{ route('sales-returns.bulk-destroy') }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    </div>
    @endif
</div>

{{-- Executive KPI Cards (Clickable Status Filters) --}}
<div class="ret-kpi-grid">
    <div class="ret-kpi-card is-active" data-status="" title="Show all returns">
        <div class="ret-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-arrow-counterclockwise"></i>
        </div>
        <div class="ret-kpi-body">
            <p class="ret-kpi-label">Total Returns</p>
            <div class="ret-kpi-val" style="color:#0077b6;">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="ret-kpi-sub">All return records</div>
        </div>
    </div>

    <div class="ret-kpi-card" data-status="pending" title="Filter pending returns">
        <div class="ret-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="ret-kpi-body">
            <p class="ret-kpi-label">Pending</p>
            <div class="ret-kpi-val" style="color:#d97706;">{{ number_format($stats['pending'] ?? 0) }}</div>
            <div class="ret-kpi-sub">Awaiting approval</div>
        </div>
    </div>

    <div class="ret-kpi-card" data-status="approved" title="Filter approved returns">
        <div class="ret-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="ret-kpi-body">
            <p class="ret-kpi-label">Approved</p>
            <div class="ret-kpi-val" style="color:#059669;">{{ number_format($stats['approved'] ?? 0) }}</div>
            <div class="ret-kpi-sub">Restocked / refunded</div>
        </div>
    </div>

    <div class="ret-kpi-card" data-status="rejected" title="Filter rejected returns">
        <div class="ret-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <i class="bi bi-x-circle-fill"></i>
        </div>
        <div class="ret-kpi-body">
            <p class="ret-kpi-label">Rejected</p>
            <div class="ret-kpi-val" style="color:#dc2626;">{{ number_format($stats['rejected'] ?? 0) }}</div>
            <div class="ret-kpi-sub">Declined requests</div>
        </div>
    </div>
</div>

{{-- Main Table Card --}}
<div class="ret-card">
    <div class="ret-toolbar">
        <div class="ret-pills" role="group">
            <button type="button" class="ret-pill-btn active" data-status="">All ({{ $stats['total'] ?? 0 }})</button>
            <button type="button" class="ret-pill-btn" data-status="pending">Pending ({{ $stats['pending'] ?? 0 }})</button>
            <button type="button" class="ret-pill-btn" data-status="approved">Approved ({{ $stats['approved'] ?? 0 }})</button>
            <button type="button" class="ret-pill-btn" data-status="rejected">Rejected ({{ $stats['rejected'] ?? 0 }})</button>
        </div>
    </div>

    <div class="p-3 p-md-4">
        @if(auth()->user()->isShopAdmin() && ($stats['pending'] ?? 0) > 0)
        <div class="alert alert-warning mb-3 border-0 rounded-3 d-flex align-items-center" style="font-size:.82rem;">
            <i class="bi bi-bell-fill text-warning me-2"></i>
            <div>
                <strong>{{ $stats['pending'] }} return request{{ $stats['pending'] > 1 ? 's' : '' }}</strong> awaiting approval to restock.
            </div>
        </div>
        @endif

        <table class="table table-hover align-middle w-100 dt-mobile-table {{ $canManage ? 'dt-has-checkbox' : '' }}" id="returnsTable">
            <thead>
                <tr>
                    @if($canManage)
                        <th data-priority="1000" style="width: 40px;" class="min-tablet-l text-center no-sort">
                            <input type="checkbox" class="form-check-input" id="selectAllCb">
                        </th>
                    @endif
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="3" class="min-tablet-l">Return Date</th>
                    <th data-priority="2" class="min-tablet-l">Sale ID</th>
                    <th data-priority="3" class="min-tablet-l">Shop Branch</th>
                    <th data-priority="1" class="all primary-col">Returned Items</th>
                    <th data-priority="4" class="min-tablet-l">Reason</th>
                    <th data-priority="4" class="min-tablet-l">Requested By</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
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
    let activeStatus = '';

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

    let table = null;
    if ($.fn.DataTable) {
        table = $('#returnsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('sales-returns.data') }}",
                data: function (d) {
                    d.status = activeStatus;
                }
            },
            columns: columns,
            order: [[canManage ? 2 : 1, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Search returns...',
                emptyTable: 'No sale returns found.'
            }
        });
    }

    function applyStatusFilter(status) {
        activeStatus = status;
        $('.ret-pill-btn').removeClass('active');
        $(`.ret-pill-btn[data-status="${status}"]`).addClass('active');
        $('.ret-kpi-card').removeClass('is-active');
        $(`.ret-kpi-card[data-status="${status}"]`).addClass('is-active');
        if (table) table.ajax.reload();
    }

    $('.ret-pill-btn').on('click', function() {
        applyStatusFilter($(this).data('status'));
    });

    $('.ret-kpi-card[data-status]').on('click', function() {
        const st = $(this).data('status');
        if ($(this).hasClass('is-active') && st !== '') {
            applyStatusFilter('');
        } else {
            applyStatusFilter(st);
        }
    });

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
