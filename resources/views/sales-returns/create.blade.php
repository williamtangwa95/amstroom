@extends('layouts.app')
@section('title', 'Return Sale #SL-' . $sale->id)
@section('page-title', 'Request Sales Return')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item"><a href="{{ route('sales.show', $sale) }}">#SL-{{ $sale->id }}</a></li>
<li class="breadcrumb-item active">Return</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700 text-danger">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Return items from Sale #SL-{{ $sale->id }}
                </h6>
                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-arrow-left me-1"></i> Cancel
                </a>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 mb-4" style="font-size:.83rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="p-3 bg-light rounded-3 mb-4 small border">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <span class="text-muted">Customer Name:</span>
                            <strong class="text-dark d-block">{{ $sale->customer_name ?: 'Walk-in Customer' }}</strong>
                        </div>
                        <div class="col-6 mb-2 text-end">
                            <span class="text-muted">Sale Date:</span>
                            <strong class="text-dark d-block">{{ $sale->sale_date->format('M d, Y') }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Shop Branch:</span>
                            <strong class="text-dark d-block">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</strong>
                        </div>
                        <div class="col-6 text-end">
                            <span class="text-muted">Total Paid:</span>
                            <strong class="text-success d-block" style="font-size:1.1rem;">TZS {{ number_format($sale->total_amount, 0) }}</strong>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('sales-returns.store', $sale) }}" id="returnForm">
                    @csrf

                    <h6 class="fw-700 mb-2 mt-4 text-dark"><i class="bi bi-list-check text-primary me-2"></i>Select Items to Return</h6>
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">Select</th>
                                <th>Product</th>
                                <th class="text-center">Sold Qty</th>
                                <th style="width: 150px;">Return Qty</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $idx => $item)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input item-select-cb" 
                                           name="items[{{ $idx }}][selected]" value="1" 
                                           onchange="toggleRowInputs(this, {{ $idx }})">
                                    <input type="hidden" name="items[{{ $idx }}][sale_item_id]" value="{{ $item->id }}" disabled id="sale_item_id_{{ $idx }}">
                                </td>
                                <td>
                                    <div class="fw-600">{{ $item->item->item_name }}</div>
                                    <small class="text-muted">{{ $item->item->brand }} {{ $item->item->model }}</small>
                                </td>
                                <td class="text-center fw-700 text-muted">{{ $item->quantity }}</td>
                                <td>
                                    <input type="number" name="items[{{ $idx }}][qty]" id="qty_input_{{ $idx }}" 
                                           class="form-control form-control-sm text-center" 
                                           min="1" max="{{ $item->quantity }}" value="{{ $item->quantity }}" disabled required>
                                </td>
                                <td class="text-end fw-600 text-dark">TZS {{ number_format($item->selling_price, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mb-4 mt-4">
                        <label for="reason" class="form-label fw-600">Reason for Return <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Explain why this item is being returned (e.g. Defective, Wrong item, customer changed mind)..." required></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-danger px-4" id="submitBtn" disabled>
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Submit Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleRowInputs(cb, idx) {
    const saleItemId = document.getElementById('sale_item_id_' + idx);
    const qtyInput = document.getElementById('qty_input_' + idx);
    
    saleItemId.disabled = !cb.checked;
    qtyInput.disabled = !cb.checked;

    // Highlight row
    cb.closest('tr').classList.toggle('table-warning', cb.checked);

    // Update submit button disabled status
    const anyChecked = document.querySelectorAll('.item-select-cb:checked').length > 0;
    document.getElementById('submitBtn').disabled = !anyChecked;
}
</script>
@endpush
