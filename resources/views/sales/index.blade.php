@extends('layouts.app')
@section('title', 'Sales History')
@section('page-title', 'Sales Transactions')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Sales Transactions</h5>
        <small style="color:var(--text-secondary);">Total Revenue: <strong style="color:#3fb950;">TZS {{ number_format($totalRevenue, 0) }}</strong></small>
    </div>
    <a href="{{ route('sales.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> New Sale</a>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
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
            <div class="col-md-2">
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-custom w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="salesTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Sale ID</th>
                    <th>Shop</th>
                    <th>Seller</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Payment Method</th>
                    <th>Total Amount</th>
                    <th>Date</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.78rem;color:var(--text-secondary);">#SL-{{ $sale->id }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</td>
                    <td style="font-size:.82rem;">{{ $sale->seller->name }}</td>
                    <td style="font-size:.82rem;">{{ $sale->customer_name ?: 'Walk-in' }}</td>
                    <td><span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;">{{ $sale->items->count() }} item(s)</span></td>
                    <td style="font-size:.78rem;">{{ str_replace('_', ' ', ucfirst($sale->payment_method)) }}</td>
                    <td><strong style="color:#3fb950;font-size:.9rem;">TZS {{ number_format($sale->total_amount, 0) }}</strong></td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $sale->sale_date->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-xs btn-outline-custom" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-xs btn-accent" title="Receipt"><i class="bi bi-receipt"></i></a>
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
<script>$(()=>$('#salesTable').DataTable())</script>
@endpush
