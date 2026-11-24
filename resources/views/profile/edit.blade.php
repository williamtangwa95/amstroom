@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'Account Settings')

@section('breadcrumb')
<li class="breadcrumb-item active">My Profile</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       PROFILE & SECURITY SETTINGS
       ══════════════════════════════════════════════ */
    .profile-card {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }

    /* ── Left Identity Card ── */
    .profile-cover {
        height: 76px;
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        position: relative;
    }
    .profile-avatar-wrap {
        margin-top: -44px;
        position: relative;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .profile-avatar-img,
    .profile-avatar-fallback {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .12);
    }
    .profile-avatar-img {
        object-fit: cover;
        background: #fff;
    }
    .profile-avatar-fallback {
        background: linear-gradient(135deg, #0077b6, #004e89);
        color: #fff;
        font-size: 1.9rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .profile-user-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: .65rem 0 .15rem;
        letter-spacing: -.01em;
    }
    .profile-user-email {
        font-size: .78rem;
        color: var(--text-secondary);
        margin-bottom: .65rem;
        word-break: break-all;
    }

    .profile-meta-list {
        border-top: 1px solid #f1f5f9;
        margin-top: 1.15rem;
        padding-top: .35rem;
        text-align: left;
    }
    .profile-meta-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .65rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .8rem;
    }
    .profile-meta-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .profile-meta-label {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: var(--text-secondary);
        font-weight: 500;
        white-space: nowrap;
    }
    .profile-meta-label i {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .74rem;
    }
    .profile-meta-value {
        color: var(--text-primary);
        font-weight: 600;
        text-align: right;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Quick Section Navigation */
    .profile-nav {
        padding: .6rem;
        display: flex;
        flex-direction: column;
        gap: .25rem;
    }
    .profile-nav-link {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .6rem .8rem;
        border-radius: 9px;
        text-decoration: none;
        color: #475569;
        font-size: .82rem;
        font-weight: 600;
        transition: background .15s, color .15s;
    }
    .profile-nav-link:hover {
        background: #f8fafc;
        color: var(--text-primary);
    }
    .profile-nav-link.active {
        background: rgba(0, 119, 182, .08);
        color: #0077b6;
    }
    .profile-nav-link i {
        font-size: .95rem;
        opacity: .85;
    }

    /* ── Section Cards (Right Column) ── */
    .settings-section {
        scroll-margin-top: 86px;
        transition: box-shadow .3s ease, border-color .3s ease;
    }
    .settings-section:target {
        border-color: rgba(0, 119, 182, .45);
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .08), 0 4px 16px rgba(15, 23, 42, .06);
    }
    .settings-header {
        padding: 1rem 1.4rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        background: #fff;
    }
    .settings-header-left {
        display: flex;
        align-items: center;
        gap: .8rem;
        min-width: 0;
    }
    .settings-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .settings-icon-primary {
        background: rgba(0, 119, 182, .1);
        color: #0077b6;
    }
    .settings-icon-security {
        background: rgba(15, 23, 42, .07);
        color: #0f172a;
    }
    .settings-title {
        font-size: .94rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.25;
    }
    .settings-subtitle {
        font-size: .75rem;
        color: var(--text-secondary);
        margin: .12rem 0 0;
        line-height: 1.3;
    }
    .settings-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .24rem .62rem;
        border-radius: 50px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
        font-size: .68rem;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .settings-body {
        padding: 1.35rem 1.4rem;
    }
    .settings-footer {
        padding: .9rem 1.4rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .settings-footer-note {
        font-size: .74rem;
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }

    /* ── Form Controls ── */
    .prof-label {
        font-size: .77rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: .38rem;
        display: block;
    }
    .prof-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .prof-input-icon {
        position: absolute;
        left: .85rem;
        color: #94a3b8;
        font-size: .88rem;
        pointer-events: none;
        z-index: 2;
        transition: color .15s;
    }
    .prof-control {
        width: 100%;
        padding: .56rem .85rem;
        font-size: .85rem;
        color: var(--text-primary);
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .prof-control.has-left-icon {
        padding-left: 2.35rem;
    }
    .prof-control.has-right-btn {
        padding-right: 2.5rem;
    }
    .prof-control::placeholder {
        color: #94a3b8;
    }
    .prof-control:focus {
        outline: none;
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .12);
    }
    .prof-input-wrap:focus-within .prof-input-icon {
        color: #0077b6;
    }
    .prof-control.is-invalid {
        border-color: #dc2626;
    }
    .prof-control.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
    }
    .prof-eye-btn {
        position: absolute;
        right: .4rem;
        background: transparent;
        border: none;
        color: #64748b;
        width: 32px;
        height: 32px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background .15s, color .15s;
        z-index: 2;
    }
    .prof-eye-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    /* Avatar Upload Box */
    .avatar-upload-box {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .85rem 1rem;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        transition: border-color .15s, background .15s;
    }
    .avatar-upload-box:hover {
        border-color: #0077b6;
        background: #f0f9ff;
    }
    .avatar-upload-preview {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, .1);
    }
    .avatar-upload-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .avatar-upload-info {
        flex: 1;
        min-width: 0;
    }
    .avatar-upload-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .36rem .75rem;
        font-size: .76rem;
        font-weight: 600;
        color: #0f172a;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }
    .avatar-upload-btn:hover {
        border-color: #0077b6;
        color: #0077b6;
    }

    /* ── Password Strength Meter & Checklist ── */
    .pwd-strength-wrap {
        margin-top: .55rem;
    }
    .pwd-bars {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 5px;
        margin-bottom: .35rem;
    }
    .pwd-bar {
        height: 4px;
        border-radius: 99px;
        background: #e2e8f0;
        transition: background-color .2s ease;
    }
    .pwd-strength-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .71rem;
        color: var(--text-secondary);
    }
    .pwd-strength-label {
        font-weight: 600;
    }
    .pwd-checklist {
        margin-top: .95rem;
        padding: .8rem 1rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .pwd-checklist-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        margin-bottom: .5rem;
    }
    .pwd-rules {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .38rem .75rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pwd-rule {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .75rem;
        color: #64748b;
        transition: color .15s;
    }
    .pwd-rule i {
        font-size: .78rem;
        color: #cbd5e1;
        transition: color .15s;
    }
    .pwd-rule.met {
        color: #15803d;
        font-weight: 500;
    }
    .pwd-rule.met i {
        color: #16a34a;
    }
    .pwd-match-msg {
        margin-top: .38rem;
        font-size: .72rem;
        font-weight: 500;
        display: none;
        align-items: center;
        gap: .32rem;
    }
    .pwd-match-msg.match {
        display: inline-flex;
        color: #15803d;
    }
    .pwd-match-msg.mismatch {
        display: inline-flex;
        color: #dc2626;
    }

    /* Action Buttons */
    .btn-prof-primary {
        background: linear-gradient(135deg, #0077b6, #005691);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: .52rem 1.2rem;
        font-size: .82rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        box-shadow: 0 2px 8px rgba(0, 119, 182, .22);
        transition: all .18s ease;
    }
    .btn-prof-primary:hover {
        background: linear-gradient(135deg, #00669e, #004473);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 119, 182, .3);
    }
    .btn-prof-security {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: .52rem 1.2rem;
        font-size: .82rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .18);
        transition: all .18s ease;
    }
    .btn-prof-security:hover {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, .26);
    }

    @media (max-width: 575.98px) {
        .settings-header,
        .settings-body,
        .settings-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .pwd-rules {
            grid-template-columns: 1fr;
        }
        .avatar-upload-box {
            flex-wrap: wrap;
        }
    }
</style>
@endpush

@section('content')
@php
    $maxUploadMb = \App\Models\Setting::get('max_upload_size_mb', 5);
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="row g-4">
    {{-- Left Column: Identity Summary & Section Navigation --}}
    <div class="col-lg-4">
        <div class="profile-card mb-3">
            <div class="profile-cover"></div>
            <div class="px-4 pb-4 text-center">
                <div class="profile-avatar-wrap">
                    @if($user->avatar_path)
                        <img src="{{ asset('media/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="profile-avatar-img" id="sidebarAvatarImg">
                    @else
                        <div class="profile-avatar-fallback" id="sidebarAvatarFallback">
                            {{ $initials ?: 'U' }}
                        </div>
                        <img src="" alt="{{ $user->name }}" class="profile-avatar-img d-none" id="sidebarAvatarImg">
                    @endif
                </div>

                <h5 class="profile-user-name">{{ $user->name }}</h5>
                <p class="profile-user-email">{{ $user->email }}</p>

                <div>
                    <span class="role-pill role-{{ $user->role }}">
                        <i class="bi bi-shield-check me-1"></i>{{ str_replace('_', ' ', strtoupper($user->role)) }}
                    </span>
                </div>

                <div class="profile-meta-list">
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">
                            <i class="bi bi-shop"></i>Branch
                        </span>
                        <span class="profile-meta-value">{{ $user->shop ? $user->shop->shop_name : 'Main Store (Owner)' }}</span>
                    </div>
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">
                            <i class="bi bi-telephone"></i>Phone
                        </span>
                        <span class="profile-meta-value">{{ $user->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">
                            <i class="bi bi-calendar3"></i>Member Since
                        </span>
                        <span class="profile-meta-value">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Jump Navigation --}}
        <div class="profile-card d-none d-lg-block">
            <div class="profile-nav">
                <a href="#profileSection" class="profile-nav-link" data-section="profileSection">
                    <i class="bi bi-person-vcard"></i>
                    <span>Personal Information</span>
                </a>
                <a href="#passwordSection" class="profile-nav-link" data-section="passwordSection">
                    <i class="bi bi-shield-lock"></i>
                    <span>Security &amp; Password</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Right Column: Personal Info & Security Settings --}}
    <div class="col-lg-8">
        {{-- Personal Information Card --}}
        <div class="profile-card settings-section mb-4" id="profileSection">
            <div class="settings-header">
                <div class="settings-header-left">
                    <div class="settings-icon settings-icon-primary">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <h6 class="settings-title">Personal Information</h6>
                        <p class="settings-subtitle">Keep your contact details and profile photo up to date.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="settings-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="prof-label">Full Name <span class="text-danger">*</span></label>
                            <div class="prof-input-wrap">
                                <i class="bi bi-person prof-input-icon"></i>
                                <input type="text" name="name" id="name"
                                       class="prof-control has-left-icon @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}" required placeholder="Enter your full name">
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="prof-label">Email Address <span class="text-danger">*</span></label>
                            <div class="prof-input-wrap">
                                <i class="bi bi-envelope prof-input-icon"></i>
                                <input type="email" name="email" id="email"
                                       class="prof-control has-left-icon @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required placeholder="name@company.com">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="phone" class="prof-label">Phone Number</label>
                            <div class="prof-input-wrap">
                                <i class="bi bi-telephone prof-input-icon"></i>
                                <input type="text" name="phone" id="phone"
                                       class="prof-control has-left-icon @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}" placeholder="+255 7XX XXX XXX">
                            </div>
                            @error('phone')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="avatar" class="prof-label">Profile Picture</label>
                            <div class="avatar-upload-box">
                                @if($user->avatar_path)
                                    <img src="{{ asset('media/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="avatar-upload-preview" id="formAvatarPreview">
                                @else
                                    <div class="avatar-upload-placeholder" id="formAvatarPlaceholder">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                    <img src="" alt="Preview" class="avatar-upload-preview d-none" id="formAvatarPreview">
                                @endif

                                <div class="avatar-upload-info">
                                    <div class="fw-600" style="font-size:.81rem; color:var(--text-primary);" id="avatarFileName">
                                        Upload a new photo (optional)
                                    </div>
                                    <div style="font-size:.72rem; color:var(--text-secondary);">
                                        JPG, PNG, GIF, or WebP &middot; Max {{ $maxUploadMb }}MB
                                    </div>
                                </div>

                                <label for="avatar" class="avatar-upload-btn mb-0">
                                    <i class="bi bi-upload"></i> Choose File
                                </label>
                                <input type="file" name="avatar" id="avatar" class="d-none @error('avatar') is-invalid @enderror"
                                       accept="image/png,image/jpeg,image/webp,image/gif">
                            </div>
                            @error('avatar')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="settings-footer">
                    <span class="settings-footer-note">
                        <i class="bi bi-info-circle"></i> Changes reflect across your workspace immediately.
                    </span>
                    <button type="submit" class="btn-prof-primary">
                        <i class="bi bi-check2-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Security & Password Card --}}
        <div class="profile-card settings-section" id="passwordSection">
            <div class="settings-header">
                <div class="settings-header-left">
                    <div class="settings-icon settings-icon-security">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <h6 class="settings-title">Security &amp; Password</h6>
                        <p class="settings-subtitle">Update your password regularly to keep your account protected.</p>
                    </div>
                </div>
                <span class="settings-status-pill d-none d-sm-inline-flex">
                    <i class="bi bi-lock-fill"></i> Protected
                </span>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" id="passwordUpdateForm">
                @csrf
                @method('PUT')

                <div class="settings-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="current_password" class="prof-label">Current Password <span class="text-danger">*</span></label>
                            <div class="prof-input-wrap">
                                <i class="bi bi-key prof-input-icon"></i>
                                <input type="password" name="current_password" id="current_password"
                                       class="prof-control has-left-icon has-right-btn @error('current_password') is-invalid @enderror"
                                       required autocomplete="current-password" placeholder="Enter your current password">
                                <button class="prof-eye-btn toggle-password-visibility" type="button"
                                        data-target="current_password" aria-label="Show or hide current password" title="Show/hide password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="prof-label">New Password <span class="text-danger">*</span></label>
                            <div class="prof-input-wrap">
                                <i class="bi bi-lock prof-input-icon"></i>
                                <input type="password" name="password" id="password"
                                       class="prof-control has-left-icon has-right-btn @error('password') is-invalid @enderror"
                                       required autocomplete="new-password" placeholder="Minimum 6 characters">
                                <button class="prof-eye-btn toggle-password-visibility" type="button"
                                        data-target="password" aria-label="Show or hide new password" title="Show/hide password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror

                            <div class="pwd-strength-wrap" id="pwdStrengthWrap">
                                <div class="pwd-bars">
                                    <div class="pwd-bar" data-bar="1"></div>
                                    <div class="pwd-bar" data-bar="2"></div>
                                    <div class="pwd-bar" data-bar="3"></div>
                                    <div class="pwd-bar" data-bar="4"></div>
                                </div>
                                <div class="pwd-strength-meta">
                                    <span>Password strength</span>
                                    <span class="pwd-strength-label" id="pwdStrengthLabel">—</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="prof-label">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="prof-input-wrap">
                                <i class="bi bi-check2-square prof-input-icon"></i>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="prof-control has-left-icon has-right-btn"
                                       required autocomplete="new-password" placeholder="Re-enter new password">
                                <button class="prof-eye-btn toggle-password-visibility" type="button"
                                        data-target="password_confirmation" aria-label="Show or hide confirm password" title="Show/hide password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="pwd-match-msg" id="pwdMatchMsg"></div>
                        </div>
                    </div>

                    <div class="pwd-checklist">
                        <div class="pwd-checklist-title">Password Guidelines</div>
                        <ul class="pwd-rules">
                            <li class="pwd-rule" id="ruleLength">
                                <i class="bi bi-check-circle-fill"></i> At least 6 characters (required)
                            </li>
                            <li class="pwd-rule" id="ruleCase">
                                <i class="bi bi-check-circle-fill"></i> Uppercase &amp; lowercase letters
                            </li>
                            <li class="pwd-rule" id="ruleNumber">
                                <i class="bi bi-check-circle-fill"></i> At least one number
                            </li>
                            <li class="pwd-rule" id="ruleSpecial">
                                <i class="bi bi-check-circle-fill"></i> Special character (e.g. !@#$)
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="settings-footer">
                    <span class="settings-footer-note">
                        <i class="bi bi-shield-check"></i> Use a unique password not shared with other accounts.
                    </span>
                    <button type="submit" class="btn-prof-security">
                        <i class="bi bi-shield-lock"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        // Password show / hide toggle
        document.querySelectorAll('.toggle-password-visibility').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(btn.getAttribute('data-target'));
                const icon = btn.querySelector('i');
                if (!input || !icon) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Live avatar upload preview
        const avatarInput = document.getElementById('avatar');
        const avatarFileName = document.getElementById('avatarFileName');
        const formPreview = document.getElementById('formAvatarPreview');
        const formPlaceholder = document.getElementById('formAvatarPlaceholder');
        const sidebarImg = document.getElementById('sidebarAvatarImg');
        const sidebarFallback = document.getElementById('sidebarAvatarFallback');

        if (avatarInput) {
            avatarInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;
                if (avatarFileName) avatarFileName.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function (e) {
                    if (formPreview) {
                        formPreview.src = e.target.result;
                        formPreview.classList.remove('d-none');
                    }
                    if (formPlaceholder) formPlaceholder.classList.add('d-none');
                    if (sidebarImg) {
                        sidebarImg.src = e.target.result;
                        sidebarImg.classList.remove('d-none');
                    }
                    if (sidebarFallback) sidebarFallback.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        }

        // Password strength meter & match indicator
        const pwdInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const bars = document.querySelectorAll('#pwdStrengthWrap .pwd-bar');
        const strengthLabel = document.getElementById('pwdStrengthLabel');
        const matchMsg = document.getElementById('pwdMatchMsg');

        const ruleLength = document.getElementById('ruleLength');
        const ruleCase = document.getElementById('ruleCase');
        const ruleNumber = document.getElementById('ruleNumber');
        const ruleSpecial = document.getElementById('ruleSpecial');

        const strengthLevels = [
            { label: 'Weak',   color: '#ef4444' },
            { label: 'Fair',   color: '#f59e0b' },
            { label: 'Good',   color: '#0284c7' },
            { label: 'Strong', color: '#16a34a' },
        ];

        function evaluatePassword() {
            if (!pwdInput) return;
            const val = pwdInput.value;

            const hasLength  = val.length >= 6;
            const hasCase    = /[a-z]/.test(val) && /[A-Z]/.test(val);
            const hasNumber  = /\d/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);

            ruleLength?.classList.toggle('met', hasLength);
            ruleCase?.classList.toggle('met', hasCase);
            ruleNumber?.classList.toggle('met', hasNumber);
            ruleSpecial?.classList.toggle('met', hasSpecial);

            let score = 0;
            if (val.length > 0) {
                if (hasLength) score++;
                if (hasCase) score++;
                if (hasNumber) score++;
                if (hasSpecial) score++;
                if (val.length >= 10 && score < 4) score++;
                score = Math.min(Math.max(score, 1), 4);
            }

            bars.forEach(function (bar, idx) {
                if (score > 0 && idx < score) {
                    bar.style.backgroundColor = strengthLevels[score - 1].color;
                } else {
                    bar.style.backgroundColor = '#e2e8f0';
                }
            });

            if (strengthLabel) {
                if (score === 0) {
                    strengthLabel.textContent = '—';
                    strengthLabel.style.color = '';
                } else {
                    strengthLabel.textContent = strengthLevels[score - 1].label;
                    strengthLabel.style.color = strengthLevels[score - 1].color;
                }
            }

            checkMatch();
        }

        function checkMatch() {
            if (!pwdInput || !confirmInput || !matchMsg) return;
            const pwd = pwdInput.value;
            const conf = confirmInput.value;

            if (!conf) {
                matchMsg.className = 'pwd-match-msg';
                matchMsg.innerHTML = '';
                return;
            }
            if (pwd === conf) {
                matchMsg.className = 'pwd-match-msg match';
                matchMsg.innerHTML = '<i class="bi bi-check-circle-fill"></i> Passwords match';
            } else {
                matchMsg.className = 'pwd-match-msg mismatch';
                matchMsg.innerHTML = '<i class="bi bi-x-circle-fill"></i> Passwords do not match';
            }
        }

        pwdInput?.addEventListener('input', evaluatePassword);
        confirmInput?.addEventListener('input', checkMatch);

        // Highlight active quick-nav link based on URL hash
        const navLinks = document.querySelectorAll('.profile-nav-link');
        function syncActiveNav() {
            const hash = window.location.hash.replace('#', '') || 'profileSection';
            navLinks.forEach(function (link) {
                link.classList.toggle('active', link.getAttribute('data-section') === hash);
            });
        }
        syncActiveNav();
        window.addEventListener('hashchange', syncActiveNav);
    })();
</script>
@endpush
