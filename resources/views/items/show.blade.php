@extends('layouts.app')
@section('title', $item->item_name)
@section('page-title', $item->item_name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('items.index') }}">Products</a></li>
<li class="breadcrumb-item active">{{ $item->item_name }}</li>
@endsection
@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle-fill me-2" style="color:#58a6ff;"></i>Product Details</div>
            <div class="card-body">
                <table class="table table-borderless mb-0" style="font-size:.83rem;">
                    <tr><th style="color:var(--text-secondary);width:40%;">Category</th><td>{{ $item->category->category_name }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Brand</th><td>{{ $item->brand ?: '—' }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Model</th><td>{{ $item->model ?: '—' }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Warranty</th><td>{{ $item->warranty_period ?: '—' }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Specs</th><td style="white-space:pre-wrap;">{{ $item->specification ?: '—' }}</td></tr>
                </table>
                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-accent">Edit</a>
                    <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-custom">Back</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-building-fill me-2" style="color:#d29922;"></i>Main Store Stock</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Buy Price</th><th>Sell Price</th><th>Stocked</th><th>Remaining</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($item->mainStocks as $ms)
                    <tr>
                        <td style="font-size:.82rem;">TZS {{ number_format($ms->buying_price, 0) }}</td>
                        <td style="font-size:.82rem;">TZS {{ number_format($ms->selling_price, 0) }}</td>
                        <td style="font-size:.82rem;">{{ $ms->stocked_quantity }}</td>
                        <td><strong style="color:{{ $ms->remaining_quantity > 0 ? '#3fb950' : '#e94560' }};">{{ $ms->remaining_quantity }}</strong></td>
                        <td style="font-size:.75rem;color:var(--text-secondary);">{{ $ms->date_received->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-3" style="color:var(--text-secondary);">Not in main store</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="bi bi-layers-fill me-2" style="color:#3fb950;"></i>Shop Stock</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Shop</th><th>Qty</th><th>Remaining</th><th>Sell Price</th></tr></thead>
                    <tbody>
                    @forelse($item->shopStocks as $ss)
                    <tr>
                        <td style="font-size:.82rem;">{{ $ss->shop->shop_name }}</td>
                        <td style="font-size:.82rem;">{{ $ss->quantity }}</td>
                        <td><strong style="color:{{ $ss->isLowStock() ? '#e94560' : '#3fb950' }};">{{ $ss->remaining_quantity }}</strong></td>
                        <td style="font-size:.82rem;">TZS {{ number_format($ss->selling_price, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-3" style="color:var(--text-secondary);">Not in any shop yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
