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
                            <input type="number" name="buying_price" class="form-control" value="{{ old('buying_price', $mainStock->buying_price) }}" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (TZS) *</label>
                            <input type="number" name="selling_price" class="form-control" value="{{ old('selling_price', $mainStock->selling_price) }}" min="0" step="0.01" required>
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
