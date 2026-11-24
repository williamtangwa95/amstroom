@extends('layouts.app')
@section('title', 'Shop Stock Item')
@section('page-title', 'Shop Stock Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Shop Stock</a></li>
<li class="breadcrumb-item active">Details</li>
@endsection

@push('styles')
<style>
    .ss-detail-card {
        overflow: hidden;
        border-radius: 14px;
    }
    .ss-detail-header {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        flex-wrap: wrap;
        line-height: 1.35;
        word-break: break-word;
    }
    .ss-meta-list {
        border: 1px solid var(--card-border);
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
    }
    .ss-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .7rem .95rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: .85rem;
    }
    .ss-meta-row:last-child {
        border-bottom: none;
    }
    .ss-meta-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary);
        flex-shrink: 0;
        max-width: 48%;
        line-height: 1.3;
    }
    .ss-meta-value {
        flex: 1;
        min-width: 0;
        text-align: right;
        color: var(--text-primary);
        font-weight: 600;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    @media (max-width: 479.98px) {
        .ss-meta-row {
            flex-direction: column;
            align-items: flex-start;
            gap: .2rem;
            padding: .65rem .85rem;
        }
        .ss-meta-label {
            max-width: 100%;
        }
        .ss-meta-value {
            width: 100%;
            text-align: left;
        }
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7 col-xl-6">
        <div class="card mb-4 ss-detail-card">
            <div class="card-header ss-detail-header">
                <i class="bi bi-layers-fill flex-shrink-0" style="color:#3fb950; margin-top:2px;"></i>
                <span class="min-w-0">{{ $shopStock->item->item_name }} &commat; {{ $shopStock->shop->shop_name }}</span>
            </div>
            <div class="card-body p-3 p-md-4">
                @if($shopStock->item->image_path)
                    <div class="text-center mb-3">
                        <div class="product-img-wrapper overflow-hidden rounded position-relative d-inline-block" style="max-height: 200px; max-width: 100%; cursor: pointer; border: 1px solid var(--card-border);" onclick="zoomProductImage('{{ asset('media/' . $shopStock->item->image_path) }}', '{{ e($shopStock->item->item_name) }}')" title="Click to zoom image">
                            <img src="{{ asset('media/' . $shopStock->item->image_path) }}" alt="{{ $shopStock->item->item_name }}" class="img-fluid rounded product-img-thumb p-1" style="max-height: 190px; object-fit: contain; transition: transform 0.25s ease;">
                            <div class="img-zoom-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.4); opacity: 0; transition: opacity 0.2s ease;">
                                <i class="bi bi-zoom-in text-white fs-3"></i>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center mb-3 py-4 bg-light rounded border text-muted">
                        <i class="bi bi-image fs-1 d-block mb-1" style="color: var(--text-secondary);"></i>
                        <small class="text-secondary">No image uploaded</small>
                    </div>
                @endif

                @php
                    $msStock = \App\Models\MainStock::where('item_id', $shopStock->item_id)->orderByDesc('date_received')->first();
                    $displayBp = (auth()->user()->isOwner() && $msStock) ? $msStock->buying_price : $shopStock->buying_price;
                    $displaySp = (auth()->user()->isOwner() && $msStock) ? $msStock->selling_price : $shopStock->selling_price;
                @endphp

                <div class="ss-meta-list">
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Shop</span>
                        <span class="ss-meta-value fw-700">{{ $shopStock->shop->shop_name }}</span>
                    </div>
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Category</span>
                        <span class="ss-meta-value">{{ $shopStock->item->category->category_name }}</span>
                    </div>
                    @if(auth()->user()->isOwner())
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Main Store Buying Price (BP)</span>
                        <span class="ss-meta-value" style="color:var(--text-secondary);">TZS {{ number_format($displayBp, 0) }}</span>
                    </div>
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Main Store Selling Price (SP)</span>
                        <span class="ss-meta-value" style="color:#059669; font-size:1rem; font-weight:700;">TZS {{ number_format($displaySp, 0) }}</span>
                    </div>
                    @else
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Selling Price</span>
                        <span class="ss-meta-value" style="color:#059669; font-size:1rem; font-weight:700;">TZS {{ number_format($shopStock->selling_price, 0) }}</span>
                    </div>
                    @endif
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Initial Qty</span>
                        <span class="ss-meta-value">{{ number_format($shopStock->quantity) }}</span>
                    </div>
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Remaining Qty</span>
                        <span class="ss-meta-value">
                            <strong style="color:{{ $shopStock->isLowStock() ? '#e94560' : '#059669' }}; font-size:1.05rem;">{{ number_format($shopStock->remaining_quantity) }}</strong>
                            @if($shopStock->isLowStock())
                            <span class="badge-soft-danger ms-1" style="font-size:.68rem;">Low Stock Alert</span>
                            @endif
                        </span>
                    </div>
                    <div class="ss-meta-row">
                        <span class="ss-meta-label">Date Received</span>
                        <span class="ss-meta-value">{{ $shopStock->date_received ? $shopStock->date_received->format('F d, Y') : '—' }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('items.upload-image', $shopStock->item) }}" enctype="multipart/form-data" class="mt-3 pt-3 border-top">
                    @csrf
                    <label class="form-label fw-600 small">Upload/Change Product Photo (Max {{ \App\Models\Setting::get('max_upload_size_mb', 5) }}MB)</label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif" required>

                        <button type="submit" class="btn btn-accent">Upload</button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">JPEG, PNG, GIF, WebP. Max {{ \App\Models\Setting::get('max_upload_size_mb', 5) }}MB.</small>

                </form>

                @if(!auth()->user()->isOwner())
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

                @if(auth()->user()->isShopAdmin())
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

                <div class="p-3 rounded mt-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div style="flex:1; min-width:0;">
                            <label class="form-label fw-600 mb-0">Custom Components on Sell</label>
                            <small class="form-text text-muted d-block" style="font-size:0.75rem;">Allow adding/customizing component items when selling this stock at POS.</small>
                        </div>
                        <div class="form-check form-switch mb-0 flex-shrink-0">
                            <input class="form-check-input toggle-components-btn" type="checkbox" data-id="{{ $shopStock->id }}" style="cursor:pointer; width: 34px; height: 18px;" {{ $shopStock->allow_components ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->isShopAdmin() && $shopStock->is_admin_stock)
                <div class="p-3 rounded mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);">
                    <div style="flex:1; min-width:180px;">
                        <strong class="text-danger d-block" style="font-size:0.85rem;">Delete Stock Batch</strong>
                        <small class="text-muted d-block" style="font-size:0.75rem;">
                            Submit a request to the owner to delete this stock batch.
                        </small>
                    </div>
                    <form method="POST" action="{{ route('shop-stock.destroy', $shopStock) }}">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm btn-outline-danger confirm-delete-btn">
                            <i class="bi bi-trash me-1"></i> Delete Stock
                        </button>
                    </form>
                </div>
                @endif
                @endif
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
    if (input.length) {
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
    }

    $(document).on('change', '.toggle-components-btn', function() {
        const isChecked = $(this).is(':checked');
        const stockId = $(this).data('id');
        $.post("{{ route('settings.toggle-components') }}", {
            _token: "{{ csrf_token() }}",
            shop_stock_id: stockId,
            enabled: isChecked ? 1 : 0
        }, function(res) {
            if (res.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: isChecked ? 'Manual components enabled for this product.' : 'Manual components disabled for this product.',
                    showConfirmButton: false,
                    timer: 2500,
                    background: '#161b22',
                    color: '#e6edf3'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Blocked',
                    text: res.message || 'Could not toggle components for this product.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });
    });

    $(document).on('click', '.confirm-delete-btn', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Delete Stock Batch?',
            text: "Are you sure you want to delete this stock batch? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: '#161b22',
            color: '#e6edf3'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
