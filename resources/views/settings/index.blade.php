@extends('layouts.app')
@section('title', auth()->user()->isSeller() ? 'Printer Settings' : 'Branding & Settings')
@section('page-title', auth()->user()->isSeller() ? 'Printer Settings' : 'Branding & Settings')

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
                        <i class="bi {{ auth()->user()->isSeller() ? 'bi-printer-fill' : 'bi-sliders' }} fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-700">
                            @if(auth()->user()->isOwner())
                                Global System Branding
                            @elseif(auth()->user()->isShopAdmin())
                                Shop Branding Settings
                            @else
                                Personal Printer Settings
                            @endif
                        </h5>
                        <small class="text-muted">
                            @if(auth()->user()->isOwner())
                                Configure the master company logo, name, and slogan displayed on the login page and owner portal.
                            @elseif(auth()->user()->isShopAdmin())
                                Configure your branch logo, name, and slogan displayed to your customers.
                            @else
                                Configure your printer preference after completing sales transactions.
                            @endif
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                {{-- Remove Logo Form (Outside main form to avoid HTML nesting) --}}
                @if($logo && !auth()->user()->isSeller())
                <form method="POST" action="{{ route('settings.remove-logo') }}" id="removeLogoForm">
                    @csrf @method('DELETE')
                </form>
                @endif

                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                    @csrf

                    @if(!auth()->user()->isSeller())
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
                            <label for="system_name" class="form-label fw-600">
                                {{ auth()->user()->isOwner() ? 'System / Company Name' : 'Shop Name' }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="system_name" id="system_name" class="form-control @error('system_name') is-invalid @enderror" value="{{ old('system_name', $systemName) }}" required placeholder="e.g. AMSTROOM">
                            @error('system_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slogan / Tagline --}}
                        <div class="mb-3">
                            <label for="slogan" class="form-label fw-600">Slogan / Tagline</label>
                            <input type="text" name="slogan" id="slogan" class="form-control @error('slogan') is-invalid @enderror" value="{{ old('slogan', $slogan) }}" placeholder="e.g. Technology Innovations">
                            <div class="form-text">This subtitle will appear below your brand name.</div>
                            @error('slogan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    {{-- Printer Setting (Enable / Disable) --}}
                    <div class="mb-4">
                        <label class="form-label fw-600 d-block mb-2">Printer Status</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="printer_enabled" id="printer_enabled_yes" value="1" {{ old('printer_enabled', $printerEnabled) == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-600 text-dark" for="printer_enabled_yes">
                                    <i class="bi bi-printer text-success me-1"></i> Enabled (Show print receipt screen after checkout)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="printer_enabled" id="printer_enabled_no" value="0" {{ old('printer_enabled', $printerEnabled) == '0' ? 'checked' : '' }}>
                                <label class="form-check-label fw-600 text-dark" for="printer_enabled_no">
                                    <i class="bi bi-printer-fill text-danger me-1"></i> Disabled (Skip print receipt screen after checkout)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-accent px-4 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
