@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Stock Movement Audit Logs')
@section('breadcrumb')
<li class="breadcrumb-item active">Audit Logs</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('stock-logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Transaction Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100"><i class="bi bi-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center"><i class="bi bi-clock-history me-2 text-accent"></i><strong class="fw-700">Complete Audit Trail</strong></div>
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100" id="logsTable">
            <thead>
                <tr>
                    <th data-priority="1" class="text-center" style="width: 45px;">#</th>
                    <th data-priority="2">Date</th>
                    <th data-priority="1">Product</th>
                    <th data-priority="2">Transaction Type</th>
                    <th data-priority="3">From</th>
                    <th data-priority="3">To</th>
                    <th data-priority="2">Qty</th>
                    <th data-priority="4">Performed By</th>
                    <th data-priority="4">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $log->date->format('M d, Y') }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $log->item->item_name }}</td>
                    <td>
                        @php
                        $badgeMap = [
                            'STOCK_RECEIVED' => 'badge-approved',
                            'STOCK_TRANSFER' => 'badge-pending',
                            'SALE'           => 'badge-approved',
                            'DEFECT'         => 'badge-rejected',
                            'ADJUSTMENT'     => 'badge-pending',
                        ];
                        @endphp
                        <span class="status-badge {{ $badgeMap[$log->transaction_type] ?? 'badge-pending' }}">
                            {{ $log->transaction_type }}
                        </span>
                    </td>
                    <td style="font-size:.78rem;">{{ $log->from_location }}</td>
                    <td style="font-size:.78rem;">{{ $log->to_location }}</td>
                    <td><strong style="font-size:.85rem;">{{ $log->quantity }}</strong></td>
                    <td style="font-size:.78rem;">{{ $log->performer->name }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $log->notes ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#logsTable').DataTable())</script>
@endpush
