@extends('layouts.app')
@section('title', 'Stock Requests')
@section('page-title', 'Stock Requests')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Stock &amp; Warehouse</a></li>
<li class="breadcrumb-item active">Stock Requests</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       STOCK REQUESTS — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .sr-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .sr-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .sr-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .sr-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 50px;
        background: rgba(0, 119, 182, .08);
        color: #0077b6;
        font-size: .72rem;
        font-weight: 600;
    }
    .sr-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .sr-select-wrap {
        display: inline-flex;
        align-items: center;
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 9px;
        padding: 0 .2rem 0 .6rem;
        height: 34px;
    }
    .sr-select-wrap i {
        color: #64748b;
        font-size: .8rem;
        margin-right: .35rem;
    }
    .sr-select-wrap select {
        border: none;
        background: transparent;
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
        padding: .2rem 1.65rem .2rem .1rem;
        cursor: pointer;
        box-shadow: none !important;
    }
    .sr-select-wrap select:focus {
        outline: none;
    }

    /* ── Executive KPI Grid (Matches /stock-reorder) ── */
    .sr-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .sr-kpi-card {
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
    .sr-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .sr-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .sr-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .sr-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .sr-kpi-label {
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
    .sr-kpi-val {
        font-size: clamp(1.05rem, 1.4vw, 1.3rem);
        font-weight: 800;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }
    .sr-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Main Card & Segmented Pills ── */
    .sr-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .sr-toolbar {
        padding: .8rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #fff;
    }
    .sr-pills {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .sr-pill-btn {
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
    .sr-pill-btn:hover {
        color: #0f172a;
    }
    .sr-pill-btn.active {
        background: #fff;
        color: #0077b6;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }

    @media (max-width: 991.98px) {
        .sr-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }
    @media (max-width: 575.98px) {
        .sr-controls {
            width: 100%;
        }
        .sr-select-wrap,
        .sr-select-wrap select,
        .sr-controls .btn {
            width: 100%;
            justify-content: center;
        }
        .sr-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .sr-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .sr-toolbar {
            padding: .75rem .9rem;
        }
        .sr-pills {
            width: 100%;
        }
        .sr-pill-btn {
            flex: 1;
            text-align: center;
            padding: .3rem .35rem;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header --}}
<div class="sr-header">
    <div class="sr-title-row">
        <h5 class="sr-title">
            <i class="bi bi-clipboard-check text-primary me-1"></i>Stock Requests
        </h5>
        <span class="sr-pill">
            @if(auth()->user()->isOwner())
                <i class="bi bi-building"></i>Central Warehouse
            @else
                <i class="bi bi-shop"></i>{{ auth()->user()->shop?->shop_name ?? 'Branch Requests' }}
            @endif
        </span>
    </div>

    <div class="sr-controls">
        @if(auth()->user()->isOwner() && $shops->count() > 0)
        <div class="sr-select-wrap">
            <i class="bi bi-shop"></i>
            <select id="filterRequestShop" class="form-select form-select-sm" aria-label="Filter by Branch">
                <option value="">All Branches</option>
                @foreach($shops as $s)
                <option value="{{ $s->id }}">{{ $s->shop_name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if(!auth()->user()->isOwner())
        <a href="{{ route('stock-requests.create') }}" class="btn btn-sm btn-accent shadow-sm" style="height:34px;border-radius:9px;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-circle"></i> New Request
        </a>
        @endif
    </div>
</div>

{{-- Executive KPI Cards (Clickable Status Filters) --}}
<div class="sr-kpi-grid">
    <div class="sr-kpi-card is-active" data-status="" title="Show all requests">
        <div class="sr-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-clipboard-data"></i>
        </div>
        <div class="sr-kpi-body">
            <p class="sr-kpi-label">Total</p>
            <div class="sr-kpi-val" style="color:#0077b6;">{{ number_format($stats['total']) }}</div>
            <div class="sr-kpi-sub">All requests</div>
        </div>
    </div>

    <div class="sr-kpi-card" data-status="pending" title="Filter pending requests">
        <div class="sr-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="sr-kpi-body">
            <p class="sr-kpi-label">Pending</p>
            <div class="sr-kpi-val" style="color:#d97706;">{{ number_format($stats['pending']) }}</div>
            <div class="sr-kpi-sub">Awaiting review</div>
        </div>
    </div>

    <div class="sr-kpi-card" data-status="approved" title="Filter approved requests">
        <div class="sr-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="sr-kpi-body">
            <p class="sr-kpi-label">Approved</p>
            <div class="sr-kpi-val" style="color:#059669;">{{ number_format($stats['approved']) }}</div>
            <div class="sr-kpi-sub">Dispatched / fulfilled</div>
        </div>
    </div>

    <div class="sr-kpi-card" data-status="rejected" title="Filter rejected requests">
        <div class="sr-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <i class="bi bi-x-circle-fill"></i>
        </div>
        <div class="sr-kpi-body">
            <p class="sr-kpi-label">Rejected</p>
            <div class="sr-kpi-val" style="color:#dc2626;">{{ number_format($stats['rejected']) }}</div>
            <div class="sr-kpi-sub">Declined requests</div>
        </div>
    </div>
</div>

{{-- Main Table Card --}}
<div class="sr-card">
    <div class="sr-toolbar">
        <div class="sr-pills" role="group">
            <button type="button" class="sr-pill-btn active" data-status="">All ({{ $stats['total'] }})</button>
            <button type="button" class="sr-pill-btn" data-status="pending">Pending ({{ $stats['pending'] }})</button>
            <button type="button" class="sr-pill-btn" data-status="approved">Approved ({{ $stats['approved'] }})</button>
            <button type="button" class="sr-pill-btn" data-status="rejected">Rejected ({{ $stats['rejected'] }})</button>
        </div>
    </div>

    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="requestsTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="2" class="min-tablet-l">Shop</th>
                    <th data-priority="3" class="min-tablet-l">Requester</th>
                    <th data-priority="3" class="min-tablet-l">Date</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="1" class="all primary-col">Items</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(() => {
        let activeStatus = '';

        var table = $('#requestsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('stock-requests.data') }}",
                data: function (d) {
                    d.status = activeStatus;
                    if ($('#filterRequestShop').length) {
                        d.shop_id = $('#filterRequestShop').val();
                    }
                }
            },
            columns: [
                { data: 'iteration', name: 'iteration', orderable: false, searchable: false },
                { data: 'shop', name: 'shop' },
                { data: 'requester', name: 'requester' },
                { data: 'date', name: 'date' },
                { data: 'status', name: 'status' },
                { data: 'items', name: 'items' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[3, 'desc']]
        });

        function applyStatusFilter(status) {
            activeStatus = status;
            $('.sr-pill-btn').removeClass('active');
            $(`.sr-pill-btn[data-status="${status}"]`).addClass('active');
            $('.sr-kpi-card').removeClass('is-active');
            $(`.sr-kpi-card[data-status="${status}"]`).addClass('is-active');
            table.ajax.reload();
        }

        $('.sr-pill-btn').on('click', function () {
            applyStatusFilter($(this).data('status'));
        });

        $('.sr-kpi-card[data-status]').on('click', function () {
            const st = $(this).data('status');
            if ($(this).hasClass('is-active') && st !== '') {
                applyStatusFilter('');
            } else {
                applyStatusFilter(st);
            }
        });

        $('#filterRequestShop').on('change', function () {
            table.ajax.reload();
        });

        var detailsCache = {};

        $('#requestsTable tbody').on('click', '.toggle-details', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var btn = $(this);
            var tr = btn.closest('tr');
            var reqId = btn.data('id');
            var icon = btn.find('i');
            var isInsideChildRow = tr.hasClass('child') || tr.hasClass('dtr-child') || btn.closest('.dtr-details').length > 0;

            // Mobile mode: button lives inside DataTables Responsive tr.child drawer
            if (isInsideChildRow) {
                var childCell = btn.closest('td.child');
                if (!childCell.length) {
                    childCell = tr.find('td.child').first();
                }
                var container = childCell.find('.mobile-inline-details[data-details-id="' + reqId + '"]');

                if (container.length && container.is(':visible')) {
                    container.slideUp(180);
                    icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                    return;
                }

                if (!container.length) {
                    container = $('<div class="mobile-inline-details mt-2 w-100" data-details-id="' + reqId + '" style="display:none;"></div>');
                    childCell.append(container);
                }

                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

                if (detailsCache[reqId]) {
                    container.html(detailsCache[reqId]).slideDown(200);
                } else {
                    container.html('<div class="p-3 text-center text-muted small"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading requested items...</div>').slideDown(180);

                    $.ajax({
                        url: "{{ url('stock-requests') }}/" + reqId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[reqId] = html;
                            container.html(html);
                        },
                        error: function() {
                            container.html('<div class="p-3 text-center text-danger small"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load request details.</div>');
                        }
                    });
                }
                return;
            }

            // Desktop mode: standard DataTables row.child()
            var row = table.row(tr);
            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                if (detailsCache[reqId]) {
                    row.child(detailsCache[reqId]).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                } else {
                    var loadingHtml = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading details...</div>';
                    row.child(loadingHtml).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

                    $.ajax({
                        url: "{{ url('stock-requests') }}/" + reqId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[reqId] = html;
                            if (row.child.isShown()) {
                                row.child(html).show();
                            }
                        },
                        error: function() {
                            var errorHtml = '<div class="p-3 text-center text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load request details.</div>';
                            row.child(errorHtml).show();
                        }
                    });
                }
            }
        });
    });
</script>
@endpush
