@extends('layouts.app')
@section('title', 'Edit Shop')
@section('page-title', 'Edit Shop')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Shops</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2" style="color:#0088cc;"></i>Edit: {{ $shop->shop_name }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('shops.update', $shop) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Shop Name *</label>
                            <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name', $shop->shop_name) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slogan / Tagline (Optional)</label>
                            <input type="text" name="slogan" class="form-control" value="{{ old('slogan', $shop->slogan) }}" placeholder="e.g. City Centre Branch - Innovations (Leave blank to fallback to owner slogan)">
                            <small class="text-muted" style="font-size:.73rem;">If left empty, main owner slogan will be displayed when logged in to this shop.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Shop Logo (Optional)</label>
                            @if($shop->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($shop->logo))
                            <div class="mb-2 p-2 border rounded bg-light d-inline-block">
                                <img src="{{ asset('media/' . $shop->logo) }}" alt="Shop Logo" style="max-height: 50px; object-fit: contain;">
                                <div class="text-muted" style="font-size:.7rem;">Current Shop Logo</div>
                            </div>
                            @endif
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted" style="font-size:.73rem;">If left empty or null, main owner logo will be displayed.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $shop->location) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $shop->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $shop->email) }}">
                        </div>
                        @if(auth()->user()->isOwner())
                        <div class="col-12">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $shop->status)==='active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $shop->status)==='inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        @endif
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Update Shop</button>
                        <a href="{{ route('shops.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
