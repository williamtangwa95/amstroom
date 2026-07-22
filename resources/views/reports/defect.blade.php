@extends('layouts.app')
@section('title', 'Defect Report')
@section('page-title', 'Defective Items Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Defect Report</li>
@endsection
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value" style="color:#e94560;">{{ number_format($totalDefective) }}</div>
            <div class="stat-label">Total Defective Units Reported</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-shield-exclamation"></i></div>
            <div class="stat-value" style="color:#58a6ff;">{{ $defects->count() }}</div>
            <div class="stat-label">Incidents Reported</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-exclamation-octagon me-2" style="color:#e94560;"></i>Defective Items Audit Log</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsDefectTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Shop / Warehouse</th>
                    <th>Product</th>
                    <th>Qty Defective</th>
                    <th>Reason</th>
                    <th>Reported By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($defects as $def)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $def->date->format('M d, Y') }}</td>
                    <td style="font-weight:600;">{{ $def->shop ? $def->shop->shop_name : 'Main Warehouse' }}</td>
                    <td style="font-weight:600;">{{ $def->item->item_name }}</td>
                    <td><strong style="color:#e94560;">{{ $def->quantity }}</strong></td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">{{ $def->reason }}</td>
                    <td style="font-size:.78rem;">{{ $def->reporter->name }}</td>
                    <td><span class="status-badge badge-{{ $def->status==='resolved' ? 'approved' : 'rejected' }}">{{ ucfirst($def->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>$(()=>$('#reportsDefectTable').DataTable())</script>
@endpush
