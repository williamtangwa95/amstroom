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
                @if($shopStock->item->image_path)
                    <div class="text-center mb-3">
                        <img src="{{ asset('media/' . $shopStock->item->image_path) }}" alt="{{ $shopStock->item->item_name }}" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;">
                    </div>
                @else
                    <div class="text-center mb-3 py-4 bg-light rounded border text-muted">
                        <i class="bi bi-image fs-1 d-block mb-1" style="color: var(--text-secondary);"></i>
                        <small class="text-secondary">No image uploaded</small>
                    </div>
                @endif

                <table class="table table-borderless" style="font-size:.85rem;">
                    <tr><th style="color:var(--text-secondary);width:40%;">Shop</th><td><strong>{{ $shopStock->shop->shop_name }}</strong></td></tr>
                    <tr><th style="color:var(--text-secondary);">Category</th><td>{{ $shopStock->item->category->category_name }}</td></tr>
                    @php
                        $msStock = \App\Models\MainStock::where('item_id', $shopStock->item_id)->orderByDesc('date_received')->first();
                        $displayBp = (auth()->user()->isOwner() && $msStock) ? $msStock->buying_price : $shopStock->buying_price;
                        $displaySp = (auth()->user()->isOwner() && $msStock) ? $msStock->selling_price : $shopStock->selling_price;
                    @endphp
                    @if(auth()->user()->isOwner())
                    <tr><th style="color:var(--text-secondary);">Main Store Buying Price (BP)</th><td><strong style="color:var(--text-secondary);">TZS {{ number_format($displayBp, 0) }}</strong></td></tr>
                    <tr><th style="color:var(--text-secondary);">Main Store Selling Price (SP)</th><td><strong style="color:#3fb950;font-size:1.05rem;">TZS {{ number_format($displaySp, 0) }}</strong></td></tr>
                    <tr><th style="color:var(--text-secondary);">Shop Retail Price</th><td><span class="text-info">TZS {{ number_format($shopStock->selling_price, 0) }}</span></td></tr>
                    @else
                    <tr><th style="color:var(--text-secondary);">Selling Price</th><td><strong style="color:#3fb950;font-size:1.05rem;">TZS {{ number_format($shopStock->selling_price, 0) }}</strong></td></tr>
                    @endif
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

                <form method="POST" action="{{ route('items.upload-image', $shopStock->item) }}" enctype="multipart/form-data" class="mt-3 pt-3 border-top">
                    @csrf
                    <label class="form-label fw-600 small">Upload/Change Product Photo (Max 1MB)</label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                        <button type="submit" class="btn btn-accent">Upload</button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">JPEG, PNG, GIF, WebP. Max 1MB.</small>
                </form>

                <div class="p-3 rounded mt-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                    <form method="POST" action="{{ route('shop-stock.update-alert', $shopStock) }}">
                        @csrf @method('PATCH')
                        <label class="form-label fw-600">Update Low Stock Alert Threshold</label>
                        <div class="input-group">
                            <input type="number" name="low_stock_alert" class="form-control" value="{{ $shopStock->low_stock_alert }}" min="1" required>
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
                            <input type="text" name="selling_price" class="form-control currency-input update-selling-price-input @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', (int)$shopStock->selling_price) }}" min="{{ (int)$shopStock->buying_price }}" required>
                            <button type="submit" class="btn btn-accent">Update Price</button>
                        </div>
                        <div id="shopStockShowWarning" class="text-danger small mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
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

@push('scripts')
<script>
$(document).ready(function() {
    const input = $('.update-selling-price-input');
    const buyingPrice = parseFloat('{{ (int)$shopStock->buying_price }}');
    const warning = $('#shopStockShowWarning');
    const submitBtn = input.closest('form').find('button[type="submit"]');

    input.on('input', function() {
        const currentVal = parseFloat(input.val().replace(/,/g, '') || 0);
        if (currentVal < buyingPrice) {
            warning.show();
            input.addClass('is-invalid');
            submitBtn.prop('disabled', true);
        } else {
            warning.hide();
            input.removeClass('is-invalid');
            submitBtn.prop('disabled', false);
        }
    });
});
</script>
@endpush
