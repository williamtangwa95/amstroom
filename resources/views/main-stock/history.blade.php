@extends('layouts.app')
@section('title', 'Stock History')
@section('page-title', 'Main Store History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">History</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2" style="color:#58a6ff;"></i>Stock Movement History</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="historyTable">
            <thead>
                <tr><th>Date</th><th>Product</th><th>Type</th><th>From</th><th>To</th><th>Qty</th><th>By</th></tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
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
    <div class="card-body border-top" style="border-color:var(--card-border) !important;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#historyTable').DataTable({paging:false,order:[[0,'desc']]}))</script>
@endpush
