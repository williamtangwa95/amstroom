@extends('layouts.app')
@section('title', 'Stock Report')
@section('page-title', 'Stock Valuation & Inventory Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Stock Report</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('reports.stock') }}?type=main" class="btn {{ $type==='main' ? 'btn-accent' : 'btn-outline-custom' }}">Main Store Stock</a>
            <a href="{{ route('reports.stock') }}?type=shop" class="btn {{ $type==='shop' ? 'btn-accent' : 'btn-outline-custom' }}">Shop Stock Distribution</a>
        </div>
    </div>
</div>

@if($type === 'main')
<div class="card">
    <div class="card-header"><i class="bi bi-building-fill me-2" style="color:#d29922;"></i>Main Warehouse Stock Summary</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsMainStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Total Remaining Qty</th>
                    <th>Stock Value (Cost)</th>
                    <th>Expected Sales Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mainStocks as $ms)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $ms->item->item_name }}</td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $ms->item->category->category_name }}</span></td>
                    <td><strong style="color:{{ $ms->qty > 0 ? '#3fb950' : '#e94560' }}">{{ $ms->qty }}</strong></td>
                    <td>TZS {{ number_format($ms->value, 0) }}</td>
                    <td><strong style="color:#58a6ff;">TZS {{ number_format($ms->sell_value, 0) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-header"><i class="bi bi-shop me-2" style="color:#3fb950;"></i>Shop Stocks Inventory</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsShopStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Remaining Qty</th>
                    <th>Selling Price</th>
                    <th>Total Valuation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shopStocks as $ss)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $ss->shop->shop_name }}</td>
                    <td>{{ $ss->item->item_name }}</td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $ss->item->category->category_name }}</span></td>
                    <td><strong style="color:{{ $ss->isLowStock() ? '#e94560' : '#3fb950' }}">{{ $ss->remaining_quantity }}</strong></td>
                    <td>TZS {{ number_format($ss->selling_price, 0) }}</td>
                    <td><strong style="color:#58a6ff;">TZS {{ number_format($ss->remaining_quantity * $ss->selling_price, 0) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(() => {
    if ($('#reportsMainStockTable').length) {
        $('#reportsMainStockTable').DataTable();
    }
    if ($('#reportsShopStockTable').length) {
        $('#reportsShopStockTable').DataTable();
    }
});
</script>
@endpush
