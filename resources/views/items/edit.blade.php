@extends('layouts.app')
@section('title', 'Edit Product')
@section('page-title', 'Edit Product')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('items.index') }}">Products</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2" style="color:#e94560;"></i>Edit: {{ $item->item_name }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('items.update', $item) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="item_name" class="form-control" value="{{ old('item_name', $item->item_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id)==$cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ old('brand', $item->brand) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" value="{{ old('model', $item->model) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warranty Period</label>
                            <input type="text" name="warranty_period" class="form-control" value="{{ old('warranty_period', $item->warranty_period) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Specifications</label>
                            <textarea name="specification" class="form-control" rows="4">{{ old('specification', $item->specification) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Product Image (Optional, Max 1MB)</label>
                            @if($item->image_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->item_name }}" class="rounded img-thumbnail" style="max-height: 100px;">
                            </div>
                            @endif
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF, WebP. Maximum file size: 1MB.</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Update Product</button>
                        <a href="{{ route('items.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
