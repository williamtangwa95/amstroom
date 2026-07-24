@extends('layouts.app')
@section('title', 'Stock Details')
@section('page-title', 'Stock Batch Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">Details</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam-fill me-2" style="color:#58a6ff;"></i>Batch #{{ $mainStock->id }}</span>
                <a href="{{ route('main-stock.edit', $mainStock) }}" class="btn btn-sm btn-accent">Edit</a>
            </div>
            <div class="card-body">
                @if($mainStock->item->image_path)
                    <div class="text-center mb-3">
                        <img src="{{ asset('storage/' . $mainStock->item->image_path) }}" alt="{{ $mainStock->item->item_name }}" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;">
                    </div>
                @else
                    <div class="text-center mb-3 py-4 bg-light rounded border text-muted">
                        <i class="bi bi-image fs-1 d-block mb-1" style="color: var(--text-secondary);"></i>
                        <small class="text-secondary">No image uploaded</small>
                    </div>
                @endif

                <table class="table table-borderless" style="font-size:.85rem;">
                    <tr><th style="color:var(--text-secondary);width:40%;">Product</th><td><strong>{{ $mainStock->item->item_name }}</strong></td></tr>
                    <tr><th style="color:var(--text-secondary);">Category</th><td>{{ $mainStock->item->category->category_name }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Buying Price</th><td>TZS {{ number_format($mainStock->buying_price, 0) }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Selling Price</th><td>TZS {{ number_format($mainStock->selling_price, 0) }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Stocked Qty</th><td>{{ $mainStock->stocked_quantity }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Remaining</th>
                        <td><strong style="color:{{ $mainStock->remaining_quantity > 0 ? '#3fb950' : '#e94560' }};font-size:1.1rem;">{{ $mainStock->remaining_quantity }}</strong></td>
                    </tr>
                    <tr><th style="color:var(--text-secondary);">Date Received</th><td>{{ $mainStock->date_received->format('F d, Y') }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Total Value</th><td><strong style="color:#d29922;">TZS {{ number_format($mainStock->remaining_quantity * $mainStock->buying_price, 0) }}</strong></td></tr>
                </table>

                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('main-stock.index') }}" class="btn btn-sm btn-outline-custom">Back</a>
                </div>

                <form method="POST" action="{{ route('items.upload-image', $mainStock->item) }}" enctype="multipart/form-data" class="mt-3 pt-3 border-top">
                    @csrf
                    <label class="form-label fw-600 small">Upload/Change Product Photo (Max 1MB)</label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                        <button type="submit" class="btn btn-accent">Upload</button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">JPEG, PNG, GIF, WebP. Max 1MB.</small>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
