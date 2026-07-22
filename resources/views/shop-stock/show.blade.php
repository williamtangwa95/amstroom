@extends('layouts.app')
@section('title', 'Shop Stock Item')
@section('page-title', 'Shop Stock Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Shop Stock</a></li>
<li class="breadcrumb-item active">Details</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-layers-fill me-2" style="color:#3fb950;"></i>{{ $shopStock->item->item_name }} @ {{ $shopStock->shop->shop_name }}</div>
            <div class="card-body">
                <table class="table table-borderless" style="font-size:.85rem;">
                    <tr><th style="color:var(--text-secondary);width:40%;">Shop</th><td><strong>{{ $shopStock->shop->shop_name }}</strong></td></tr>
                    <tr><th style="color:var(--text-secondary);">Category</th><td>{{ $shopStock->item->category->category_name }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Selling Price</th><td><strong style="color:#3fb950;font-size:1.05rem;">TZS {{ number_format($shopStock->selling_price, 0) }}</strong></td></tr>
                    <tr><th style="color:var(--text-secondary);">Initial Qty</th><td>{{ $shopStock->quantity }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Remaining Qty</th>
                        <td>
                            <strong style="color:{{ $shopStock->isLowStock() ? '#e94560' : '#3fb950' }};font-size:1.1rem;">{{ $shopStock->remaining_quantity }}</strong>
                            @if($shopStock->isLowStock())
                            <span class="badge badge-rejected ms-2">Low Stock Alert</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th style="color:var(--text-secondary);">Date Received</th><td>{{ $shopStock->date_received ? $shopStock->date_received->format('F d, Y') : '—' }}</td></tr>
                </table>

                <div class="p-3 rounded mt-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                    <form method="POST" action="{{ route('shop-stock.update-alert', $shopStock) }}">
                        @csrf @method('PATCH')
                        <label class="form-label fw-600">Update Low Stock Alert Threshold</label>
                        <div class="input-group">
                            <input type="number" name="low_stock_alert" class="form-control" value="{{ $shopStock->low_stock_alert }}" min="0" required>
                            <button type="submit" class="btn btn-accent">Save</button>
                        </div>
                    </form>
                </div>

                @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                <div class="p-3 rounded mt-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                    <form method="POST" action="{{ route('shop-stock.update-price', $shopStock) }}">
                        @csrf @method('PATCH')
                        <label class="form-label fw-600">Update Item Selling Price</label>
                        <div class="input-group">
                            <input type="number" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', (int)$shopStock->selling_price) }}" min="{{ (int)$shopStock->buying_price }}" required>
                            <button type="submit" class="btn btn-accent">Update Price</button>
                        </div>
                        <small class="form-text text-muted mt-1 d-block">Minimum allowed price: TZS {{ number_format($shopStock->buying_price, 0) }} (equal to buying price)</small>
                        @error('selling_price')
                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                        @enderror
                    </form>
                </div>
                @endif

                <a href="{{ route('shop-stock.index') }}" class="btn btn-outline-custom mt-3">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
