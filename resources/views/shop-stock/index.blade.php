@extends('layouts.app')
@section('title', 'Shop Inventory')
@section('page-title', 'Shop Stock')
@section('breadcrumb')
<li class="breadcrumb-item active">Shop Stock</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Shop Inventory</h5>
        <small style="color:var(--text-secondary);">Available products in retail shops</small>
    </div>
    @if($lowStockItems > 0)
    <div class="alert alert-warning py-1 px-3 mb-0" style="font-size:.8rem;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $lowStockItems }} item(s) low in stock!
    </div>
    @endif
</div>

@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('shop-stock.index') }}" class="row align-items-center g-2">
            <div class="col-auto"><label class="form-label mb-0">Filter by Shop:</label></div>
            <div class="col-auto">
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="shopStockTable">
            <thead>
                <tr>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Initial Qty</th>
                    <th>Remaining Qty</th>
                    <th>Alert Threshold</th>
                    <th>Selling Price</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $st)
                <tr class="{{ $st->isLowStock() ? 'low-stock-row' : '' }}">
                    <td style="font-size:.82rem;font-weight:600;">{{ $st->shop->shop_name }}</td>
                    <td>
                        <div style="font-weight:600;font-size:.83rem;">{{ $st->item->item_name }}</div>
                        <div style="font-size:.7rem;color:var(--text-secondary);">{{ $st->item->brand }}</div>
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $st->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">{{ $st->quantity }}</td>
                    <td>
                        <strong style="color:{{ $st->isLowStock() ? '#e94560' : '#3fb950' }};font-size:.9rem;">
                            {{ $st->remaining_quantity }}
                        </strong>
                        @if($st->isLowStock())
                        <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#e94560;font-size:.75rem;" title="Low Stock!"></i>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">{{ $st->low_stock_alert }} units</td>
                    <td style="font-size:.82rem;font-weight:600;">TZS {{ number_format($st->selling_price, 0) }}</td>
                    <td>
                        <a href="{{ route('shop-stock.show', $st) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-body border-top" style="border-color:var(--card-border) !important;">
        {{ $stocks->links() }}
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#shopStockTable').DataTable({paging:false}))</script>
@endpush
