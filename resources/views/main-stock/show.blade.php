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
                <a href="{{ route('main-stock.index') }}" class="btn btn-outline-custom">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
