@extends('layouts.app')
@section('title', 'Register Employee')
@section('page-title', 'Register Employee')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('users.index') }}">Employees</a></li>
<li class="breadcrumb-item active">Register</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus-fill me-2" style="color:#e94560;"></i>Employee Registration</div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="john@amstroom.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+255700000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">System Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="shop_admin" {{ old('role')==='shop_admin' ? 'selected' : '' }}>Shop Admin</option>
                                <option value="seller" {{ old('role')==='seller' ? 'selected' : '' }}>Seller</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign to Shop</label>
                            <select name="shop_id" class="form-select">
                                <option value="">None (Unassigned)</option>
                                @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id')==$shop->id ? 'selected' : '' }}>{{ $shop->shop_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="password_confirmation" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Register Employee</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
