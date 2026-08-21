@extends('layouts.app')
@section('title', 'Transfer Report')
@section('page-title', 'Stock Transfer Requests Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Transfer Report</li>
@endsection
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #f39c12 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Requests</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['pending'] }}</h3>
                </div>
                <div class="fs-2" style="color: #f39c12; opacity: 0.25;"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Approved & Transferred</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['approved'] }}</h3>
                </div>
                <div class="fs-2" style="color: #10b981; opacity: 0.25;"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Rejected Requests</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['rejected'] }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-arrow-left-right me-2" style="color:#58a6ff;"></i>Request & Transfer Log</span>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('reports.transfer') }}?status=all" class="btn {{ $status==='all' ? 'btn-accent' : 'btn-outline-custom' }}">All</a>
            <a href="{{ route('reports.transfer') }}?status=pending" class="btn {{ $status==='pending' ? 'btn-accent' : 'btn-outline-custom' }}">Pending</a>
            <a href="{{ route('reports.transfer') }}?status=approved" class="btn {{ $status==='approved' ? 'btn-accent' : 'btn-outline-custom' }}">Approved</a>
            <a href="{{ route('reports.transfer') }}?status=rejected" class="btn {{ $status==='rejected' ? 'btn-accent' : 'btn-outline-custom' }}">Rejected</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsTransferTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Request ID</th>
                    <th>Shop</th>
                    <th>Requester</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Items</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.78rem;color:var(--text-secondary);">#{{ $req->id }}</td>
                    <td style="font-weight:600;">{{ $req->shop->shop_name }}</td>
                    <td>{{ $req->requester->name }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $req->request_date->format('M d, Y') }}</td>
                    <td><span class="status-badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                    <td>{{ $req->items->count() }} item(s)</td>
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
    $('#reportsTransferTable').DataTable({
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'btn btn-sm btn-accent me-2',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                title: 'Stock Transfer Requests Report'
            },
            {
                extend: 'pdfHtml5',
                className: 'btn btn-sm btn-outline-custom',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                title: 'Stock Transfer Requests Report'
            }
        ]
    });
});
</script>
@endpush
