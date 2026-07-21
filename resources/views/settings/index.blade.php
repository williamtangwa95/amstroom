@extends('layouts.app')
@section('title', 'Branding & Settings')
@section('page-title', 'System Branding Settings')

@section('breadcrumb')
<li class="breadcrumb-item active">Settings</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary me-3">
                        <i class="bi bi-sliders fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-700">Global System Branding</h5>
                        <small class="text-muted">Configure the master company logo, name, and slogan displayed on the login page and owner portal.</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                {{-- Remove Logo Form (Outside main form to avoid HTML nesting) --}}
                @if($logo)
                <form method="POST" action="{{ route('settings.remove-logo') }}" id="removeLogoForm">
                    @csrf @method('DELETE')
                </form>
                @endif

                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Current Logo Preview --}}
                    <div class="mb-4 text-center">
                        <label class="form-label d-block fw-600 mb-2">Current System Logo</label>
                        <div class="d-inline-flex flex-column align-items-center p-3 rounded-4 bg-light border">
                            @if($logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo))
                                <img src="{{ asset('storage/' . $logo) }}" alt="System Logo" class="img-fluid rounded-3 mb-2" style="max-height: 80px; object-fit: contain;">
                            @else
                                <div class="brand-icon-preview rounded-3 d-flex align-items-center justify-content-center bg-white text-primary shadow-sm mb-2" style="width: 70px; height: 70px; font-size: 2rem;">
                                    <i class="bi bi-pc-display-horizontal"></i>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary mb-2">Default Monitor Icon</span>
                            @endif

                            @if($logo)
                                <button type="submit" form="removeLogoForm" class="btn btn-xs btn-outline-danger mt-1" onclick="return confirm('Remove custom logo and revert to default icon?')">
                                    <i class="bi bi-trash me-1"></i> Remove Custom Logo
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Upload New Logo --}}
                    <div class="mb-3">
                        <label for="logo" class="form-label fw-600">Upload New Logo</label>
                        <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                        <div class="form-text">Supported formats: PNG, JPG, WEBP, SVG. Max size: 2MB. Recommended: Transparent background.</div>
                        @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- System / Company Name --}}
                    <div class="mb-3">
                        <label for="system_name" class="form-label fw-600">System / Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="system_name" id="system_name" class="form-control @error('system_name') is-invalid @enderror" value="{{ old('system_name', $systemName) }}" required placeholder="e.g. AMSTROOM">
                        @error('system_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Slogan / Tagline --}}
                    <div class="mb-4">
                        <label for="slogan" class="form-label fw-600">Slogan / Tagline</label>
                        <input type="text" name="slogan" id="slogan" class="form-control @error('slogan') is-invalid @enderror" value="{{ old('slogan', $slogan) }}" placeholder="e.g. Technology Innovations">
                        <div class="form-text">This subtitle will appear below your company name across the system.</div>
                        @error('slogan')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-accent px-4 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Branding Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
