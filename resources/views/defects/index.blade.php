@extends('layouts.app')
@section('title', 'Defective Items')
@section('page-title', 'Defective Items')
@section('breadcrumb')
<li class="breadcrumb-item active">Defects</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       DEFECTS — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .df-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .df-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .df-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .df-pill {
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
    .df-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem;
    }
    .df-controls .btn {
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
    }

    .df-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .df-kpi-card {
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
    .df-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .df-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .df-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .df-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .df-kpi-label {
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
    .df-kpi-val {
        font-size: clamp(1.05rem, 1.4vw, 1.3rem);
        font-weight: 800;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }
    .df-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .df-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .df-toolbar {
        padding: .8rem 1.15rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        background: #fff;
    }
    .df-pills {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 9px;
        gap: 2px;
    }
    .df-pill-btn {
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
    .df-pill-btn:hover {
        color: #0f172a;
    }
    .df-pill-btn.active {
        background: #fff;
        color: #0077b6;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
    }

    @media (max-width: 991.98px) {
        .df-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }
    @media (max-width: 575.98px) {
        .df-controls {
            width: 100%;
        }
        .df-controls .btn {
            flex: 1;
        }
        .df-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .df-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .df-toolbar {
            padding: .75rem .9rem;
        }
        .df-pills {
            width: 100%;
        }
        .df-pill-btn {
            flex: 1;
            text-align: center;
            padding: .3rem .35rem;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header --}}
<div class="df-header">
    <div class="df-title-row">
        <h5 class="df-title">
            <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Defective Items
        </h5>
        <span class="df-pill">
            <i class="bi bi-shield-exclamation"></i>Damage Log
        </span>
    </div>

    <div class="df-controls">
        @if(auth()->user()->isOwner())
        <a href="{{ route('defects.create') }}?main_store=1" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-building"></i> Main Store Defect
        </a>
        @endif
        <a href="{{ route('defects.create') }}" class="btn btn-sm btn-accent shadow-sm">
            <i class="bi bi-plus-circle"></i> Report Defect
        </a>
    </div>
</div>

{{-- Executive KPI Cards (Clickable Status Filters) --}}
<div class="df-kpi-grid">
    <div class="df-kpi-card is-active" data-status="" title="Show all defects">
        <div class="df-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-shield-exclamation"></i>
        </div>
        <div class="df-kpi-body">
            <p class="df-kpi-label">Total Reports</p>
            <div class="df-kpi-val" style="color:#0077b6;">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="df-kpi-sub">{{ number_format($stats['total_units'] ?? 0) }} units affected</div>
        </div>
    </div>

    <div class="df-kpi-card" data-status="reported" title="Filter reported defects">
        <div class="df-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <i class="bi bi-exclamation-octagon"></i>
        </div>
        <div class="df-kpi-body">
            <p class="df-kpi-label">Reported</p>
            <div class="df-kpi-val" style="color:#dc2626;">{{ number_format($stats['reported'] ?? 0) }}</div>
            <div class="df-kpi-sub">Open issues</div>
        </div>
    </div>

    <div class="df-kpi-card" data-status="reviewed" title="Filter reviewed defects">
        <div class="df-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-search"></i>
        </div>
        <div class="df-kpi-body">
            <p class="df-kpi-label">Reviewed</p>
            <div class="df-kpi-val" style="color:#d97706;">{{ number_format($stats['reviewed'] ?? 0) }}</div>
            <div class="df-kpi-sub">Under inspection</div>
        </div>
    </div>

    <div class="df-kpi-card" data-status="resolved" title="Filter resolved defects">
        <div class="df-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="df-kpi-body">
            <p class="df-kpi-label">Resolved</p>
            <div class="df-kpi-val" style="color:#059669;">{{ number_format($stats['resolved'] ?? 0) }}</div>
            <div class="df-kpi-sub">Closed cases</div>
        </div>
    </div>
</div>

{{-- Main Table Card --}}
<div class="df-card">
    <div class="df-toolbar">
        <div class="df-pills" role="group">
            <button type="button" class="df-pill-btn active" data-status="">All ({{ $stats['total'] ?? 0 }})</button>
            <button type="button" class="df-pill-btn" data-status="reported">Reported ({{ $stats['reported'] ?? 0 }})</button>
            <button type="button" class="df-pill-btn" data-status="reviewed">Reviewed ({{ $stats['reviewed'] ?? 0 }})</button>
            <button type="button" class="df-pill-btn" data-status="resolved">Resolved ({{ $stats['resolved'] ?? 0 }})</button>
        </div>
    </div>

    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="defectsTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="3" class="min-tablet-l">Date</th>
                    <th data-priority="3" class="min-tablet-l">Location</th>
                    <th data-priority="1" class="all primary-col">Product</th>
                    <th data-priority="4" class="min-tablet-l">Category</th>
                    <th data-priority="2" class="min-tablet-l">Qty</th>
                    <th data-priority="4" class="min-tablet-l">Reason</th>
                    <th data-priority="4" class="min-tablet-l">Reported By</th>
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
    $(() => {
        let activeStatus = '';

        var table = $('#defectsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('defects.data') }}",
                data: function (d) {
                    d.status = activeStatus;
                }
            },
            columns: [
                { data: 'iteration', name: 'iteration' },
                { data: 'date', name: 'date' },
                { data: 'location', name: 'location' },
                { data: 'product', name: 'product' },
                { data: 'category', name: 'category' },
                { data: 'quantity', name: 'quantity' },
                { data: 'reason', name: 'reason' },
                { data: 'reporter', name: 'reporter' },
                { data: 'status', name: 'status' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']]
        });

        function applyStatusFilter(status) {
            activeStatus = status;
            $('.df-pill-btn').removeClass('active');
            $(`.df-pill-btn[data-status="${status}"]`).addClass('active');
            $('.df-kpi-card').removeClass('is-active');
            $(`.df-kpi-card[data-status="${status}"]`).addClass('is-active');
            table.ajax.reload();
        }

        $('.df-pill-btn').on('click', function () {
            applyStatusFilter($(this).data('status'));
        });

        $('.df-kpi-card[data-status]').on('click', function () {
            const st = $(this).data('status');
            if ($(this).hasClass('is-active') && st !== '') {
                applyStatusFilter('');
            } else {
                applyStatusFilter(st);
            }
        });
    });
</script>
@endpush
