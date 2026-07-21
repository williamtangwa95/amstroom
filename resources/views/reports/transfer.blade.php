@extends('layouts.app')
@section('title', 'Transfer Report')
@section('page-title', 'Stock Transfer Requests Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Transfer Report</li>
@endsection
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;"><i class="bi bi-clock-history"></i></div>
            <div class="stat-value" style="color:#d29922;">{{ $stats['pending'] }}</div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value" style="color:#3fb950;">{{ $stats['approved'] }}</div>
            <div class="stat-label">Approved & Transferred</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-value" style="color:#e94560;">{{ $stats['rejected'] }}</div>
            <div class="stat-label">Rejected Requests</div>
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
        <table class="table table-hover mb-0">
            <thead>
                <tr>
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
    <div class="card-body border-top" style="border-color:var(--card-border) !important;">
        {{ $requests->links() }}
    </div>
</div>
@endsection
