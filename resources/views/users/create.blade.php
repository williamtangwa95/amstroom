@extends('layouts.app')
@section('title', auth()->user()->isShopAdmin() ? 'Add Seller' : 'Register Employee')
@section('page-title', auth()->user()->isShopAdmin() ? 'Add Seller' : 'Register Employee')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('users.index') }}">Employees</a></li>
<li class="breadcrumb-item active">{{ auth()->user()->isShopAdmin() ? 'Add Seller' : 'Register' }}</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus-fill me-2" style="color:#e94560;"></i>
                @if(auth()->user()->isShopAdmin())
                    Add Seller — <span style="color:#58a6ff;">{{ auth()->user()->shop?->shop_name }}</span>
                @else
                    Employee Registration
                @endif
            </div>
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

                        {{-- Role: owner can choose, shop_admin locked to seller --}}
                        @if(auth()->user()->isOwner())
                        <div class="col-md-6">
                            <label class="form-label">System Role *</label>
                            <select name="role" id="roleSelect" class="form-select" required>
                                <option value="seller" {{ old('role')==='seller' ? 'selected' : '' }}>Seller</option>
                                <option value="shop_admin" {{ old('role')==='shop_admin' ? 'selected' : '' }}>Shop Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 align-self-end" id="allow_stock_addition_wrapper" style="display: {{ old('role') === 'shop_admin' ? 'block' : 'none' }}; margin-bottom: 8px;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_stock_addition" id="allow_stock_addition" value="1" {{ old('allow_stock_addition') ? 'checked' : '' }}>
                                <label class="form-check-label fw-600" for="allow_stock_addition">Allow Owner Stock Addition</label>
                            </div>
                        </div>
                        @else
                        <input type="hidden" name="role" value="seller">
                        @endif

                        {{-- Shop: owner can choose, shop_admin locked to their own --}}
                        @if(auth()->user()->isOwner())
                        <div class="col-md-6">
                            <label class="form-label">Assign to Shop</label>
                            <select name="shop_id" class="form-select">
                                <option value="">None (Unassigned)</option>
                                @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id')==$shop->id ? 'selected' : '' }}>{{ $shop->shop_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="shop_id" value="{{ auth()->user()->shop_id }}">
                        @endif

                        <div class="col-md-12">
                            <div class="alert alert-info" style="font-size:.83rem;">
                                Default password is <strong>password</strong>. Employee can change it after login.
                            </div>
                        </div>

                        {{-- Hidden default password fields --}}
                        <div class="d-none">
                            <input type="password" name="password" value="password" required minlength="6">
                            <input type="password" name="password_confirmation" value="password" required minlength="6">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ auth()->user()->isShopAdmin() ? 'Add Seller' : 'Register Employee' }}
                        </button>
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