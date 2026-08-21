@extends('layouts.app')
@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('users.index') }}">Employees</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2" style="color:#e94560;"></i>Edit: {{ $user->name }}</div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.83rem;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>

                        {{-- Role --}}
                        @if(auth()->user()->isOwner())
                        <div class="col-md-6">
                            <label class="form-label">System Role *</label>
                            <select name="role" id="roleSelect" class="form-select" required>
                                <option value="seller" {{ old('role', $user->role)==='seller' ? 'selected' : '' }}>Seller</option>
                                <option value="shop_admin" {{ old('role', $user->role)==='shop_admin' ? 'selected' : '' }}>Shop Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 align-self-end" id="allow_stock_addition_wrapper" style="display: {{ old('role', $user->role) === 'shop_admin' ? 'block' : 'none' }}; margin-bottom: 8px;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_stock_addition" id="allow_stock_addition" value="1" {{ old('allow_stock_addition', $user->allow_stock_addition) ? 'checked' : '' }}>
                                <label class="form-check-label fw-600" for="allow_stock_addition">Allow Owner Stock Addition</label>
                            </div>
                        </div>
                        @else
                        {{-- Shop admin: role is always seller, show as read-only badge --}}
                        <input type="hidden" name="role" value="seller">
                        <div class="col-md-6">
                            <label class="form-label">System Role</label>
                            <div class="form-control bg-light" style="font-size:.85rem;">
                                <span class="role-pill role-seller">Seller</span>
                                <small class="text-muted ms-1">(locked)</small>
                            </div>
                        </div>
                        @endif

                        {{-- Shop --}}
                        @if(auth()->user()->isOwner())
                        <div class="col-md-6">
                            <label class="form-label">Assign to Shop</label>
                            <select name="shop_id" class="form-select">
                                <option value="">None (Unassigned)</option>
                                @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id', $user->shop_id)==$shop->id ? 'selected' : '' }}>{{ $shop->shop_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="shop_id" value="{{ auth()->user()->shop_id }}">
                        <div class="col-md-6">
                            <label class="form-label">Assigned Shop</label>
                            <div class="form-control bg-light" style="font-size:.85rem;">
                                <i class="bi bi-shop me-1" style="color:#58a6ff;"></i>
                                {{ auth()->user()->shop?->shop_name ?? '—' }}
                                <small class="text-muted ms-1">(locked)</small>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                            <input type="password" name="password" class="form-control" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" minlength="6">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Update Employee</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(() => {
    $('#roleSelect').on('change', function() {
        const wrapper = $('#allow_stock_addition_wrapper');
        if ($(this).val() === 'shop_admin') {
            wrapper.show();
        } else {
            wrapper.hide();
            $('#allow_stock_addition').prop('checked', false);
        }
    });
});
</script>
@endpush
