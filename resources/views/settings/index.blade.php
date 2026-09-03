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

                {{-- Remove Ringtone Form (Outside main form to avoid HTML nesting) --}}
                @if($notificationRingtone)
                <form method="POST" action="{{ route('settings.remove-ringtone') }}" id="removeRingtoneForm">
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
                                    <img src="{{ asset('media/' . $logo) }}" alt="System Logo" class="img-fluid rounded-3 mb-2" style="max-height: 80px; object-fit: contain;">
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
                            <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,image/gif">
                            <div class="form-text">Supported formats: PNG, JPG, WEBP, GIF (SVG uploads are restricted). Max size: {{ $maxUploadSizeMb ?? \App\Models\Setting::get('max_upload_size_mb', 5) }}MB. Recommended: Transparent background.</div>


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

                        {{-- Company Slogan / Tagline --}}
                        <div class="mb-3">
                            <label for="slogan" class="form-label fw-600">Company Slogan / Tagline</label>
                            <input type="text" name="slogan" id="slogan" class="form-control @error('slogan') is-invalid @enderror" value="{{ old('slogan', $slogan) }}" placeholder="e.g. Technology Innovations / Quality Products">
                            <div class="form-text">Company slogan displayed on login screens, receipts, and official documents.</div>
                            @error('slogan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Shop / Store Location --}}
                        <div class="mb-3">
                            <label for="company_address" class="form-label fw-600">
                                <i class="bi bi-geo-alt-fill me-1" style="color:#ffb700;"></i> {{ auth()->user()->isOwner() ? 'HQ / Store Location' : 'Shop / Branch Location' }}
                            </label>
                            <input type="text" name="company_address" id="company_address" class="form-control @error('company_address') is-invalid @enderror" value="{{ old('company_address', $companyAddress) }}" placeholder="e.g. Kariakoo, Dar es Salaam / Main Street HQ">
                            <div class="form-text">This location is displayed on the sidebar navigation, receipts, invoices, and documents.</div>
                            @error('company_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Store Pricing Mode --}}
                        @if(auth()->user()->isOwner())
                        <div class="mb-3">
                            <label for="store_pricing_mode" class="form-label fw-600">Store Pricing Mode <span class="text-danger">*</span></label>
                            <select name="store_pricing_mode" id="store_pricing_mode" class="form-select @error('store_pricing_mode') is-invalid @enderror" required>
                                <option value="DEPENDENT" {{ old('store_pricing_mode', $storePricingMode) === 'DEPENDENT' ? 'selected' : '' }}>Dependent Pricing Mode (Standard Transfer)</option>
                                <option value="INDEPENDENT" {{ old('store_pricing_mode', $storePricingMode) === 'INDEPENDENT' ? 'selected' : '' }}>Independent Pricing Mode (Role-Based Ledger & Approval Lock)</option>
                            </select>
                            <div class="form-text">
                                In <strong>Independent Mode</strong>, Main Store Selling Price acts as the Sub-Store's internal Buying Price. Sub-stores set their Selling Prices independently.
                            </div>
                            @error('store_pricing_mode')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Maximum File Upload Size (MB) --}}
                        <div class="mb-3">
                            <label for="max_upload_size_mb" class="form-label fw-600">
                                <i class="bi bi-hdd-fill me-1" style="color:#0088cc;"></i> Maximum File Upload Size (MB) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="max_upload_size_mb" id="max_upload_size_mb" class="form-control @error('max_upload_size_mb') is-invalid @enderror" value="{{ old('max_upload_size_mb', $maxUploadSizeMb ?? 5) }}" min="1" max="100" required placeholder="5">
                            <div class="form-text">Maximum allowed size in Megabytes (MB) for image, photo, and file uploads across the system (Default: <strong>5 MB</strong>).</div>
                            @error('max_upload_size_mb')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    @endif


                    {{-- Invoice & Document Settings --}}
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <div class="mb-4 border-top pt-4">
                        <h6 class="fw-700 mb-1"><i class="bi bi-file-earmark-text me-2" style="color:#0088cc;"></i>Invoice & Bank Details</h6>
                        <small class="text-muted d-block mb-3">Additional company details displayed on printed invoices and receipts.</small>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="company_tin" class="form-label fw-600 small">TIN Number</label>
                                <input type="text" name="company_tin" id="company_tin" class="form-control" value="{{ old('company_tin', $companyTin) }}" placeholder="e.g. 100-123-456">
                            </div>
                            <div class="col-md-6">
                                <label for="company_bank_name" class="form-label fw-600 small">Bank Name</label>
                                <input type="text" name="company_bank_name" id="company_bank_name" class="form-control" value="{{ old('company_bank_name', $companyBankName) }}" placeholder="e.g. CRDB Bank PLC">
                            </div>
                            <div class="col-md-12">
                                <label for="company_bank_account" class="form-label fw-600 small">Bank Account Number</label>
                                <input type="text" name="company_bank_account" id="company_bank_account" class="form-control" value="{{ old('company_bank_account', $companyBankAccount) }}" placeholder="e.g. 0150123456789">
                            </div>
                        </div>
                    </div>
                    @endif


                    {{-- Email Summary Settings --}}
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <div class="mb-4 border-top pt-4">
                        <h6 class="fw-700 mb-1"><i class="bi bi-envelope-at me-2" style="color:#e94560;"></i>Email Summary Settings</h6>
                        <small class="text-muted d-block mb-3">Configure which email addresses will receive reports and summaries of sales, expenses, and stock. Your own email is mandatory and must be included in the list.</small>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="summary_emails" class="form-label fw-600 small">Recipients Email Addresses (Comma-separated) <span class="text-danger">*</span></label>
                                <textarea name="summary_emails" id="summary_emails" class="form-control @error('summary_emails') is-invalid @enderror" rows="2" placeholder="e.g. {{ auth()->user()->email }}, partner@example.com" required>{{ old('summary_emails', $summaryEmails) }}</textarea>
                                @error('summary_emails')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Multiple email addresses must be separated by commas. Your email address <strong>({{ auth()->user()->email }})</strong> must be present.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="summary_time" class="form-label fw-600 small">Daily Send Time <span class="text-danger">*</span></label>
                                <input type="time" name="summary_time" id="summary_time" class="form-control @error('summary_time') is-invalid @enderror" value="{{ old('summary_time', $summaryTime) }}" required>
                                @error('summary_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    The time of day (EAT / local) when this report summary will be emailed daily.
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="button" id="btn-send-summary" class="btn btn-outline-custom btn-sm">
                                    <i class="bi bi-send me-1"></i> Send Summary Report Now
                                </button>
                                <span id="summary-send-status" class="ms-2 small fw-600"></span>
                            </div>
                        </div>
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

                    {{-- Notification Sound Settings --}}
                    <div class="mb-4 border-top pt-4">
                        <label class="form-label fw-600 mb-2">Notification Ringtone</label>
                        <div class="d-flex flex-column gap-2 p-3 rounded-4 bg-light border mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 p-2 bg-info-subtle text-primary">
                                    <i class="bi bi-bell-fill fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-600">Ringtone Preference</h6>
                                    @if($notificationRingtone && \Illuminate\Support\Facades\Storage::disk('public')->exists($notificationRingtone))
                                        <span class="badge bg-success text-white">Custom Sound Active</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Default System Sound Active</span>
                                    @endif
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTestSound">
                                        <i class="bi bi-play-fill me-1"></i> Test Play
                                    </button>
                                </div>
                                
                            </div>

                            @if($notificationRingtone)
                                <div class="text-start mt-2">
                                    <button type="submit" form="removeRingtoneForm" class="btn btn-xs btn-outline-danger" onclick="return confirm('Remove custom ringtone and revert to default?')">
                                        <i class="bi bi-trash me-1"></i> Remove Custom Ringtone
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="notification_ringtone" class="form-label fw-600">Upload New Ringtone</label>
                            <input type="file" name="notification_ringtone" id="notification_ringtone" class="form-control @error('notification_ringtone') is-invalid @enderror" accept="audio/mp3,audio/wav,audio/ogg,audio/mpeg">
                            <div class="form-text">Supported formats: MP3, WAV, OGG. Max size: 5MB.</div>
                            @error('notification_ringtone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- SMS Gateway Settings --}}
                    @if(auth()->user()->isOwner())
                    <div class="mb-4 border-top pt-4">
                        <h6 class="fw-700 mb-1"><i class="bi bi-chat-left-text me-2" style="color:var(--accent-purple);"></i>SMS Gateway Settings</h6>
                        <small class="text-muted d-block mb-3">Configure your SMS API gateway for sending customer notifications and announcements. If disabled or unconfigured, messages will be logged in the database for sandbox testing.</small>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="sms_enabled" class="form-label fw-600 small">SMS Broadcasting Status <span class="text-danger">*</span></label>
                                <select name="sms_enabled" id="sms_enabled" class="form-select" required>
                                    <option value="0" {{ old('sms_enabled', $smsEnabled) == '0' ? 'selected' : '' }}>Disabled (Sandbox Mode / Log to Database)</option>
                                    <option value="1" {{ old('sms_enabled', $smsEnabled) == '1' ? 'selected' : '' }}>Enabled (Dispatch HTTP SMS)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="sms_provider" class="form-label fw-600 small">SMS Gateway Provider <span class="text-danger">*</span></label>
                                <select name="sms_provider" id="sms_provider" class="form-select" required>
                                    <option value="generic_http" {{ old('sms_provider', $smsProvider) == 'generic_http' ? 'selected' : '' }}>Generic HTTP API (POST/GET)</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="sms_api_url" class="form-label fw-600 small">API Endpoint URL</label>
                                <input type="text" name="sms_api_url" id="sms_api_url" class="form-control @error('sms_api_url') is-invalid @enderror" value="{{ old('sms_api_url', $smsApiUrl) }}" placeholder="e.g. https://api.smsprovider.com/v1/send">
                                @error('sms_api_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="sms_api_key" class="form-label fw-600 small">API Secret / Authorization Token</label>
                                <input type="password" name="sms_api_key" id="sms_api_key" class="form-control" value="{{ old('sms_api_key', $smsApiKey) }}" placeholder="Your API token or password">
                            </div>
                            <div class="col-md-6">
                                <label for="sms_sender_id" class="form-label fw-600 small">Sender ID / Mask Name</label>
                                <input type="text" name="sms_sender_id" id="sms_sender_id" class="form-control" value="{{ old('sms_sender_id', $smsSenderId) }}" placeholder="e.g. AMSTROOM">
                            </div>
                            <div class="col-md-6">
                                <label for="sms_phone_field" class="form-label fw-600 small">Phone Parameter Name</label>
                                <input type="text" name="sms_phone_field" id="sms_phone_field" class="form-control" value="{{ old('sms_phone_field', $smsPhoneField) }}" placeholder="e.g. to, recipient, phone">
                                <div class="form-text text-muted small">Field name expected by your SMS API for recipient.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="sms_message_field" class="form-label fw-600 small">Message Parameter Name</label>
                                <input type="text" name="sms_message_field" id="sms_message_field" class="form-control" value="{{ old('sms_message_field', $smsMessageField) }}" placeholder="e.g. text, message, msg">
                                <div class="form-text text-muted small">Field name expected by your SMS API for message text.</div>
                            </div>
                            <div class="col-md-12">
                                <label for="sms_extra_params" class="form-label fw-600 small">Extra Payload Parameters (JSON Format)</label>
                                <textarea name="sms_extra_params" id="sms_extra_params" class="form-control @error('sms_extra_params') is-invalid @enderror" rows="2" placeholder='e.g. {"username": "my_user", "route": "premium"}'>{{ old('sms_extra_params', $smsExtraParams) }}</textarea>
                                @error('sms_extra_params')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted small">Specify any additional static query/body params expected by the provider API.</div>
                            </div>
                        </div>
                    </div>
                    @endif

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

@push('scripts')
<script>
    document.getElementById('btnTestSound')?.addEventListener('click', function() {
        let audio = null;
        @if($notificationRingtone && \Illuminate\Support\Facades\Storage::disk('public')->exists($notificationRingtone))
            audio = new Audio("{{ asset('media/' . $notificationRingtone) }}");
        @endif
        
        playChime(audio);
    });

    function playChime(audioObj) {
        if (audioObj) {
            audioObj.play().catch(e => {
                console.warn('Custom audio playback failed, falling back to synthesizer: ', e);
                playSynthChime();
            });
        } else {
            playSynthChime();
        }
    }

    function playSynthChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            
            // First note (chime sound)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(880, ctx.currentTime); // A5
            gain1.gain.setValueAtTime(0.3, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.4);

            // Second note slightly delayed and higher pitch
            setTimeout(() => {
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(1320, ctx.currentTime); // E6
                gain2.gain.setValueAtTime(0.3, ctx.currentTime);
                gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                osc2.start(ctx.currentTime);
                osc2.stop(ctx.currentTime + 0.5);
            }, 100);
        } catch (e) {
            console.error('Web Audio API error: ', e);
        }
    }

    document.getElementById('btn-send-summary')?.addEventListener('click', function() {
        const btn = this;
        const status = document.getElementById('summary-send-status');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...';
        status.textContent = '';
        status.className = 'ms-2 small fw-600';
        
        fetch("{{ route('settings.send-summary') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        })
        .then(response => {
            return response.json()
                .then(data => ({ status: response.status, body: data }))
                .catch(() => response.text().then(text => ({ status: response.status, body: { message: text.substring(0, 100) + '...' } })));
        })
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i> Send Summary Report Now';
            if (res.status === 200) {
                status.classList.add('text-success');
                status.textContent = 'Summary email sent successfully!';
            } else {
                status.classList.add('text-danger');
                status.textContent = res.body.message || 'Error occurred while sending.';
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i> Send Summary Report Now';
            status.classList.add('text-danger');
            status.textContent = 'Failed to make request: ' + error.message;
        });
    });
</script>
@endpush
@endsection
