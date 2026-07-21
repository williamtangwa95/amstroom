@extends('layouts.app')
@section('title', 'Sale #SL-' . $sale->id)
@section('page-title', 'Sale Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">#SL-{{ $sale->id }}</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart-check me-2" style="color:#3fb950;"></i>Sale #SL-{{ $sale->id }}</span>
                <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-sm btn-accent"><i class="bi bi-receipt me-1"></i> View Receipt</a>
            </div>
            <div class="card-body">
                <div class="row mb-3" style="font-size:.85rem;">
                    <div class="col-6">
                        <p class="mb-1" style="color:var(--text-secondary);">Shop: <strong style="color:var(--text-primary);">{{ $sale->shop->shop_name }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">Seller: <strong style="color:var(--text-primary);">{{ $sale->seller->name }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">Customer: <strong style="color:var(--text-primary);">{{ $sale->customer_name ?: 'Walk-in' }}</strong></p>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1" style="color:var(--text-secondary);">Date: <strong style="color:var(--text-primary);">{{ $sale->sale_date->format('F d, Y') }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">Payment Method: <strong style="color:var(--text-primary);">{{ str_replace('_',' ',ucfirst($sale->payment_method)) }}</strong></p>
                    </div>
                </div>

                <h6 class="fw-700 mb-2 mt-4">Items Sold</h6>
                <table class="table mb-4">
                    <thead><tr><th>Product</th><th>Qty</th><th>Selling Price</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    @foreach($sale->items as $item)
                    <tr>
                        <td style="font-weight:600;">{{ $item->item->item_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>TZS {{ number_format($item->selling_price, 0) }}</td>
                        <td><strong style="color:#3fb950;">TZS {{ number_format($item->subtotal, 0) }}</strong></td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-700">Total Amount Paid:</td>
                            <td><strong style="color:#3fb950;font-size:1.1rem;">TZS {{ number_format($sale->total_amount, 0) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-custom">Back to Sales</a>
                    
                    @php
                        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)->where('status', 'approved')->exists();
                    @endphp
                    @if(!$alreadyReturned)
                        <a href="{{ route('sales-returns.create', $sale) }}" class="btn btn-danger">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Return Items / Refund
                        </a>
                    @else
                        <span class="badge bg-success p-2"><i class="bi bi-check-circle-fill me-1"></i> Returned / Refunded</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
