@extends('layouts.app')
@section('title', 'Sales Cash Handover')
@section('page-title', 'Cash Handovers')
@section('breadcrumb')
<li class="breadcrumb-item active">Cash Handovers</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       CASH HANDOVERS — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .ho-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .ho-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .ho-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .ho-pill {
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
    .ho-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }
    .ho-select-wrap {
        display: inline-flex;
        align-items: center;
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 9px;
        padding: 0 .2rem 0 .6rem;
        height: 34px;
    }
    .ho-select-wrap i {
        color: #64748b;
        font-size: .8rem;
        margin-right: .35rem;
    }
    .ho-select-wrap select {
        border: none;
        background: transparent;
        font-size: .78rem;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
        padding: .2rem 1.65rem .2rem .1rem;
        cursor: pointer;
        box-shadow: none !important;
    }
    .ho-select-wrap select:focus {
        outline: none;
    }

    .ho-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.15rem;
    }
    .ho-kpi-card {
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
    .ho-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .ho-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .ho-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .ho-kpi-label {
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
    .ho-kpi-val {
        font-size: clamp(.9rem, 1.2vw, 1.15rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.015em;
        word-break: break-word;
    }
    .ho-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ho-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }

    @media (max-width: 991.98px) {
        .ho-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }
    @media (max-width: 575.98px) {
        .ho-controls {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
        }
        .ho-select-wrap,
        .ho-select-wrap select {
            width: 100%;
        }
        .ho-controls .btn {
            grid-column: 1 / -1;
            width: 100%;
            justify-content: center;
        }
        .ho-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .ho-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .ho-kpi-val {
            font-size: .9rem;
        }
    }
    @media (max-width: 420px) {
        .ho-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header & Filters --}}
<div class="ho-header">
    <div class="ho-title-row">
        <h5 class="ho-title">
            <i class="bi bi-cash-coin text-primary me-1"></i>Cash Handovers
        </h5>
        <span class="ho-pill">
            <i class="bi bi-shield-check"></i>Settlement Ledger
        </span>
    </div>

    @if(auth()->user()->isOwner())
    <form method="GET" action="{{ route('handovers.index') }}" class="ho-controls">
        <div class="ho-select-wrap">
            <i class="bi bi-shop"></i>
            <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter by Shop">
                <option value="">All Shops</option>
                @foreach($shops as $shop)
                <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->shop_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ho-select-wrap">
            <i class="bi bi-flag"></i>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter by Status">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
    </form>
    @elseif(auth()->user()->isShopAdmin())
    <div class="ho-controls">
        <a href="{{ route('handovers.create') }}" class="btn btn-sm btn-accent shadow-sm" style="height:34px;border-radius:9px;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-plus-circle"></i> New Handover
        </a>
    </div>
    @endif
</div>

{{-- Executive KPI Cards --}}
<div class="ho-kpi-grid">
    <div class="ho-kpi-card">
        <div class="ho-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="ho-kpi-body">
            <p class="ho-kpi-label">Pending</p>
            <div class="ho-kpi-val" style="color:#d97706;">{{ number_format($stats['pending'] + $stats['under_review']) }}</div>
            <div class="ho-kpi-sub">Awaiting settlement</div>
        </div>
    </div>

    <div class="ho-kpi-card">
        <div class="ho-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="ho-kpi-body">
            <p class="ho-kpi-label">Expected</p>
            <div class="ho-kpi-val" style="color:#0077b6;">TZS {{ number_format($stats['total_expected'], 0) }}</div>
            <div class="ho-kpi-sub">Expected cash total</div>
        </div>
    </div>

    <div class="ho-kpi-card">
        <div class="ho-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="ho-kpi-body">
            <p class="ho-kpi-label">Cash Received</p>
            <div class="ho-kpi-val" style="color:#059669;">TZS {{ number_format($stats['total_received'], 0) }}</div>
            <div class="ho-kpi-sub">Submitted amount</div>
        </div>
    </div>

    <div class="ho-kpi-card">
        <div class="ho-kpi-icon" style="background:rgba(225,29,72,.1);color:#e11d48;">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="ho-kpi-body">
            <p class="ho-kpi-label">Shortages</p>
            <div class="ho-kpi-val" style="color:#e11d48;">TZS {{ number_format(abs($stats['total_shortage']), 0) }}</div>
            <div class="ho-kpi-sub">Total variance</div>
        </div>
    </div>
</div>

<div class="ho-card">
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="handoversTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all compact-lead-col">Handover ID</th>
                    <th data-priority="1" class="all primary-col">Shop</th>
                    <th data-priority="6">Shop Admin</th>
                    <th data-priority="5">Period</th>
                    <th data-priority="7">Owner Sales</th>
                    <th data-priority="9">Admin Sales</th>
                    <th data-priority="8">Expenses</th>
                    <th data-priority="3">Expected</th>
                    <th data-priority="3">Actual Submitted</th>
                    <th data-priority="4">Difference</th>
                    <th data-priority="2">Status</th>
                    <th data-priority="6">Submitted At</th>
                    <th data-priority="1" class="min-tablet-l no-sort text-end" style="min-width: 145px;">Actions</th>
                </tr>
            </thead>
                <tbody>
                    @foreach($handovers as $ho)
                    <tr>
                        <td>
                            <a href="{{ route('handovers.show', $ho) }}" class="fw-bold text-accent text-decoration-none">
                                {{ $ho->handover_no }}
                            </a>
                        </td>
                        <td>{{ $ho->shop->shop_name }}</td>
                        <td>{{ $ho->shopAdmin->name }}</td>
                        <td class="small">{{ $ho->start_date->format('Y-m-d') }} to {{ $ho->end_date->format('Y-m-d') }}</td>
                        <td>TZS {{ number_format($ho->total_owner_sales, 0) }}</td>
                        <td class="text-muted">TZS {{ number_format($ho->total_admin_sales, 0) }}</td>
                        <td>TZS {{ number_format($ho->total_expenses, 0) }}</td>
                        <td><strong>TZS {{ number_format($ho->expected_amount, 0) }}</strong></td>
                        <td><strong>TZS {{ number_format($ho->actual_amount, 0) }}</strong></td>
                        <td>
                            @if($ho->difference_status === 'shortage')
                            <span class="text-danger fw-600" title="Shortage">TZS {{ number_format($ho->difference, 0) }}</span>
                            @elseif($ho->difference_status === 'excess')
                            <span class="text-success fw-600" title="Excess">+TZS {{ number_format($ho->difference, 0) }}</span>
                            @else
                            <span class="text-muted">Exact</span>
                            @endif
                        </td>
                        <td>
                            @if($ho->status === 'draft')
                            <span class="badge bg-secondary">Draft</span>
                            @elseif($ho->status === 'submitted')
                            <span class="badge bg-primary">Submitted</span>
                            @elseif($ho->status === 'approved')
                            <span class="badge bg-info text-dark">Approved</span>
                            @elseif($ho->status === 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                            @elseif($ho->status === 'returned')
                            <span class="badge bg-warning text-dark">Returned</span>
                            @elseif($ho->status === 'completed')
                            <span class="badge bg-success">Completed</span>
                            @else
                            <span class="badge bg-secondary">{{ ucfirst($ho->status) }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $ho->submitted_at ? $ho->submitted_at->format('Y-m-d H:i') : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <div class="d-inline-flex justify-content-end align-items-center gap-1 flex-nowrap">
                                <a href="{{ route('handovers.show', $ho) }}" class="btn btn-xs btn-outline-custom" title="View details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->isShopAdmin() && in_array($ho->status, ['draft', 'returned', 'submitted']))
                                <a href="{{ route('handovers.edit', $ho) }}" class="btn btn-xs btn-outline-warning" title="{{ $ho->status === 'submitted' ? 'Edit & Resubmit Report' : 'Edit Report' }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                <a href="{{ route('handovers.export-excel', $ho) }}" class="btn btn-xs btn-outline-success" title="Download Excel">
                                    <i class="bi bi-file-earmark-excel"></i>
                                </a>
                                <a href="{{ route('handovers.export-pdf', $ho) }}" class="btn btn-xs btn-outline-danger" target="_blank" title="Print/Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                @if(auth()->user()->isShopAdmin() && !in_array($ho->status, ['approved', 'completed']))
                                <form method="POST" action="{{ route('handovers.destroy', $ho) }}" class="d-inline" onsubmit="return confirm('Delete this handover report?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete Report"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </div>
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
$(() => {
    $('#handoversTable').DataTable({
        dom: '<"d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"lf>rtip',
        order: [[11, 'desc']], // Sort by Submitted At descending by default
        columnDefs: [
            { orderable: false, targets: [-1] } // Make actions column non-sortable
        ],
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search reports..."
        }
    });
});
</script>
@endpush
