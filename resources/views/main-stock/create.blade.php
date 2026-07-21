@extends('layouts.app')
@section('title', 'Add Stock')
@section('page-title', 'Add to Main Store')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">Add Stock</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-building-fill me-2" style="color:#e94560;"></i>Receive Stock to Main Warehouse</div>
            <div class="card-body">
                <form method="POST" action="{{ route('main-stock.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product *</label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select product...</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}" {{ old('item_id')==$item->id ? 'selected' : '' }}>
                                    [{{ $item->category->category_name }}] {{ $item->item_name }} {{ $item->brand ? "($item->brand)" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Buying Price (TZS) *</label>
                            <input type="number" name="buying_price" class="form-control" value="{{ old('buying_price') }}" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (TZS) *</label>
                            <input type="number" name="selling_price" class="form-control" value="{{ old('selling_price') }}" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity *</label>
                            <input type="number" name="stocked_quantity" class="form-control" value="{{ old('stocked_quantity') }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Received *</label>
                            <input type="date" name="date_received" class="form-control" value="{{ old('date_received', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Add to Warehouse</button>
                        <a href="{{ route('main-stock.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
