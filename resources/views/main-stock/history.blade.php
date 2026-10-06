@extends('layouts.app')
@section('title', 'Stock History')
@section('page-title', 'Main Store History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">History</li>
@endsection
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center"><i class="bi bi-clock-history me-2 text-accent"></i><strong class="fw-700">Stock Movement History</strong></div>
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100" id="historyTable">
            <thead>
                <tr>
                    <th data-priority="1" class="text-center" style="width: 45px;">#</th>
                    <th data-priority="2">Date</th>
                    <th data-priority="1">Product</th>
                    <th data-priority="2">Type</th>
                    <th data-priority="3">From</th>
                    <th data-priority="3">To</th>
                    <th data-priority="1">Qty</th>
                    <th data-priority="4">By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $log->date->format('M d, Y') }}</td>
                    <td style="font-size:.82rem;font-weight:500;">{{ $log->item->item_name }}</td>
                    <td>
                        @php
                        $typeColors = [
                            'STOCK_RECEIVED' => ['#3fb950','rgba(63,185,80,.12)'],
                            'STOCK_TRANSFER' => ['#58a6ff','rgba(88,166,255,.12)'],
                            'SALE'           => ['#bc8cff','rgba(188,140,255,.12)'],
                            'DEFECT'         => ['#e94560','rgba(233,69,96,.12)'],
                            'ADJUSTMENT'     => ['#d29922','rgba(210,153,34,.12)'],
                        ];
                        $tc = $typeColors[$log->transaction_type] ?? ['#8b949e','rgba(139,148,158,.12)'];
                        @endphp
                        <span style="color:{{ $tc[0] }};background:{{ $tc[1] }};padding:.2rem .5rem;border-radius:6px;font-size:.7rem;font-weight:700;">
                            {{ $log->transaction_type }}
                        </span>
                    </td>
                    <td style="font-size:.78rem;">{{ $log->from_location }}</td>
                    <td style="font-size:.78rem;">{{ $log->to_location }}</td>
                    <td><strong style="font-size:.82rem;">{{ $log->quantity }}</strong></td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $log->performer->name }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#historyTable').DataTable({order:[[0,'desc']]}))</script>
@endpush
