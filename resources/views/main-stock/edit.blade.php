@extends('layouts.app')
@section('title', 'Edit Stock')
@section('page-title', 'Edit Stock Batch')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2" style="color:#e94560;"></i>Edit Batch — {{ $mainStock->item->item_name }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('main-stock.update', $mainStock) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" value="{{ $mainStock->item->item_name }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Buying Price (TZS) *</label>
                            <input type="text" name="buying_price" id="main_buying_price" class="form-control currency-input" value="{{ old('buying_price', (int)$mainStock->buying_price) }}" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (TZS) *</label>
                            <input type="text" name="selling_price" id="main_selling_price" class="form-control currency-input" value="{{ old('selling_price', (int)$mainStock->selling_price) }}" min="0" required>
                            <div id="mainStockWarning" class="text-danger small mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date Received *</label>
                            <input type="date" name="date_received" class="form-control" value="{{ old('date_received', $mainStock->date_received->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Update</button>
                        <a href="{{ route('main-stock.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const buyingInput = $('#main_buying_price');
    const sellingInput = $('#main_selling_price');
    const warning = $('#mainStockWarning');
    const submitBtn = buyingInput.closest('form').find('button[type="submit"]');

    function validatePrices() {
        const buying = parseFloat(buyingInput.val().replace(/,/g, '') || 0);
        const selling = parseFloat(sellingInput.val().replace(/,/g, '') || 0);
        
        if (selling > 0 && buying > 0 && selling < buying) {
            warning.show();
            sellingInput.addClass('is-invalid');
            submitBtn.prop('disabled', true);
        } else {
            warning.hide();
            sellingInput.removeClass('is-invalid');
            submitBtn.prop('disabled', false);
        }
    }

    buyingInput.on('input', validatePrices);
    sellingInput.on('input', validatePrices);
});
</script>
@endpush
