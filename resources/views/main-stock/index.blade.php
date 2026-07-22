@extends('layouts.app')
@section('title', 'Main Store Stock')
@section('page-title', 'Main Warehouse')
@section('breadcrumb')
<li class="breadcrumb-item active">Main Store Stock</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Main Store Inventory</h5>
        <small style="color:var(--text-secondary);">Central warehouse stock management</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('main-stock.history') }}" class="btn btn-outline-custom"><i class="bi bi-clock-history me-1"></i>History</a>
        <a href="{{ route('main-stock.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i>Add Stock</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="color:#d29922;font-size:1.1rem;">TZS {{ number_format($totalValue, 0) }}</div>
            <div class="stat-label">Total Cost Value</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value" style="color:#3fb950;font-size:1.1rem;">TZS {{ number_format($totalSellValue, 0) }}</div>
            <div class="stat-label">Total Sell Value</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-value" style="color:#58a6ff;">{{ $stocks->count() }}</div>
            <div class="stat-label">Stock Batches</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="mainStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Buy Price</th>
                    <th>Sell Price</th>
                    <th>Stocked</th>
                    <th>Remaining</th>
                    <th>Date</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $stock)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td>
                        <div style="font-weight:600;font-size:.83rem;">{{ $stock->item->item_name }}</div>
                        <div style="font-size:.7rem;color:var(--text-secondary);">{{ $stock->item->brand }}</div>
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $stock->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">TZS {{ number_format($stock->buying_price, 0) }}</td>
                    <td style="font-size:.82rem;">TZS {{ number_format($stock->selling_price, 0) }}</td>
                    <td style="font-size:.82rem;">{{ $stock->stocked_quantity }}</td>
                    <td>
                        <strong style="color:{{ $stock->remaining_quantity > 0 ? '#3fb950' : '#e94560' }};">
                            {{ $stock->remaining_quantity }}
                        </strong>
                    </td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $stock->date_received->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('main-stock.show', $stock) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('main-stock.edit', $stock) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-pencil"></i></a>
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
<script>$(()=>$('#mainStockTable').DataTable())</script>
@endpush
