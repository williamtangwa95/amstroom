@extends('layouts.app')
@section('title', 'Sales Cash Handover')
@section('page-title', 'Sales Cash Handover & Settlement')
@section('breadcrumb')
<li class="breadcrumb-item active">Cash Handovers</li>
@endsection

@section('content')
<!-- Dashboard Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #3498db !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Handovers</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['pending'] + $stats['under_review'] }}</h3>
                </div>
                <div class="fs-2" style="color: #cbd5e1;"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #f1c40f !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Expected Amount</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($stats['total_expected'], 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #cbd5e1;"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #0056b3 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cash Received</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($stats['total_received'], 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #0056b3; opacity: 0.25;"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #f39c12 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Shortages</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format(abs($stats['total_shortage']), 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #f39c12; opacity: 0.25;"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        @if(auth()->user()->isOwner())
        <!-- Owner Filtering -->
        <form method="GET" action="{{ route('handovers.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
        </form>
        @endif
    </div>
    @if(auth()->user()->isShopAdmin())
    <a href="{{ route('handovers.create') }}" class="btn btn-sm btn-accent">
        <i class="bi bi-plus-circle me-1"></i> New Handover Report
    </a>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-2" style="color:var(--accent);"></i>Handover History</div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="handoversTable">
                <thead>
                    <tr>
                        <th>Handover ID</th>
                        <th>Shop</th>
                        <th>Shop Admin</th>
                        <th>Period</th>
                        <th>Owner Sales</th>
                        <th>Admin Sales (Info)</th>
                        <th>Expenses</th>
                        <th>Expected</th>
                        <th>Actual Submitted</th>
                        <th>Difference</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th class="text-end">Actions</th>
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
                            @elseif($ho->status === 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                            @elseif($ho->status === 'completed')
                            <span class="badge bg-success">Completed</span>
                            @endif
                        </td>
                        <td class="small">{{ $ho->submitted_at ? $ho->submitted_at->format('Y-m-d H:i') : '—' }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('handovers.show', $ho) }}" class="btn btn-xs btn-outline-custom" title="View details">
                                    <i class="bi bi-eye"></i>
                                </a>
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
