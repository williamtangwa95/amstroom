@extends('layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Add Product')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('items.index') }}">Products</a></li>
<li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam-fill me-2" style="color:#e94560;"></i>New Product</div>
            <div class="card-body">
                <form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="item_name" class="form-control" value="{{ old('item_name') }}" placeholder="e.g. HP EliteBook 840 G8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id')==$cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ old('brand') }}" placeholder="e.g. HP, Dell, Logitech">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="e.g. 840 G8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warranty Period</label>
                            <input type="text" name="warranty_period" class="form-control" value="{{ old('warranty_period') }}" placeholder="e.g. 1 Year, 6 Months">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Specifications</label>
                            <textarea name="specification" class="form-control" rows="4" placeholder="Core i7, 16GB RAM, 512GB SSD...">{{ old('specification') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Product Image (Optional, Max 1MB)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF, WebP. Maximum file size: 1MB.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Add Product</button>
                        <a href="{{ route('items.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
