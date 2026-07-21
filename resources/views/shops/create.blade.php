@extends('layouts.app')
@section('title', 'Add Shop')
@section('page-title', 'Add New Shop')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Shops</a></li>
<li class="breadcrumb-item active">Add Shop</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-shop me-2" style="color:#0088cc;"></i>Shop Details</div>
            <div class="card-body">
                <form method="POST" action="{{ route('shops.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Shop Name *</label>
                            <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" placeholder="e.g. AmstRoom City Branch" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slogan / Tagline (Optional)</label>
                            <input type="text" name="slogan" class="form-control" value="{{ old('slogan') }}" placeholder="e.g. City Centre Branch - Innovations (Leave blank to use main slogan)">
                            <small class="text-muted" style="font-size:.73rem;">If left empty, main owner slogan will be used for this shop.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Shop Logo (Optional)</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted" style="font-size:.73rem;">If left empty, main owner logo will be used for this shop.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g. Dar es Salaam, Tanzania" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+255...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="shop@amstroom.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status','active')==='active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Create Shop</button>
                        <a href="{{ route('shops.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
