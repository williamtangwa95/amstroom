@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'User Profile Management')

@section('breadcrumb')
<li class="breadcrumb-item active">My Profile</li>
@endsection

@section('content')
<div class="row g-4">
    {{-- Left Column: User Summary Card --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center p-4">
                @if($user->avatar_path)
                    <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="mx-auto mb-3 rounded-circle shadow-sm" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid var(--accent);">
                @else
                    <div class="avatar-circle mx-auto mb-3 text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                         style="width: 80px; height: 80px; font-size: 2.2rem; background: linear-gradient(135deg, #0088cc, #005f9e);">
                        <i class="bi bi-person-fill"></i>
                    </div>
                @endif
                <h5 class="fw-700 mb-1" style="color:var(--text-primary);">{{ $user->name }}</h5>
                <p class="text-muted small mb-2">{{ $user->email }}</p>

                <div class="mb-3">
                    <span class="role-pill role-{{ $user->role }}">
                        <i class="bi bi-shield-check me-1"></i> {{ str_replace('_', ' ', strtoupper($user->role)) }}
                    </span>
                </div>

                <div class="p-3 bg-light rounded-3 text-start small border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Assigned Branch:</span>
                        <strong class="text-dark">{{ $user->shop ? $user->shop->shop_name : 'Main Store (Owner)' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Phone Number:</span>
                        <strong class="text-dark">{{ $user->phone ?: 'Not provided' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Member Since:</span>
                        <strong class="text-dark">{{ $user->created_at->format('M d, Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Edit Profile & Change Password Forms --}}
    <div class="col-lg-8">
        {{-- Profile Info Card --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-700 text-primary">
                    <i class="bi bi-person-lines-fill me-2"></i>Personal Information
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-600">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-600">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="phone" class="form-label fw-600">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}" placeholder="+255...">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="avatar" class="form-label fw-600">Profile Picture (Optional, Max 1MB)</label>
                            <input type="file" name="avatar" id="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                            <small class="text-muted" style="font-size: .75rem;">Allowed formats: JPG, JPEG, PNG, GIF, WebP. Maximum file size: 1MB.</small>
                            @error('avatar')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-accent px-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change Password Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-700 text-danger">
                    <i class="bi bi-shield-lock-fill me-2"></i>Security & Password
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="current_password" class="form-label fw-600">Current Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror" required placeholder="••••••••">
                            @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label fw-600">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror" required placeholder="At least 6 characters">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-600">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required placeholder="Confirm new password">
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-danger px-4" style="border-radius: 8px;">
                            <i class="bi bi-key-fill me-1"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
