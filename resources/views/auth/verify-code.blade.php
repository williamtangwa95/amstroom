<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Recovery Code — {{ $appBranding['system_name'] ?? 'AMSTROOM' }}</title>
    
    <!-- Dynamic Favicon -->
    @if(!empty($appBranding['system_logo']))
        <link rel="icon" href="{{ $appBranding['system_logo'] }}">
        <link rel="apple-touch-icon" href="{{ $appBranding['system_logo'] }}">
    @else
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%230088cc'/><text x='50%' y='68%' font-size='55' text-anchor='middle' fill='%23ffffff' font-family='Arial, sans-serif' font-weight='900'>A</text></svg>">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --accent: #0088cc;
            --accent-gold: #ffb700;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --body-bg: #f4f6f9;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem 0;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 430px;
            padding: 1.25rem;
            position: relative;
            z-index: 10;
        }
        .auth-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .brand-name { font-size: 1.4rem; font-weight: 800; color: var(--text-primary); }
        .brand-tagline { font-size: .78rem; color: #d97706; font-weight: 700; }
        
        .auth-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.25rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        .auth-card h4 { font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: .25rem; }
        .auth-card p.sub { font-size: .82rem; color: var(--text-secondary); margin-bottom: 1.5rem; }

        .form-label { font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
        .form-control {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: .88rem;
            padding: .65rem .85rem;
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.15);
        }
        .input-group-text { background: #f8fafc; border: 1px solid #cbd5e1; color: var(--accent); }

        .code-input {
            letter-spacing: .4em;
            font-size: 1.5rem !important;
            font-weight: 700;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0088cc, #006699);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: .9rem;
            padding: .75rem;
            border-radius: 10px;
            width: 100%;
            transition: all .2s ease;
            box-shadow: 0 4px 14px rgba(0, 136, 204, 0.25);
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #0077b6, #004d73);
            transform: translateY(-1px);
            color: #fff;
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-brand">
        @if(!empty($appBranding['system_logo']))
            <img src="{{ $appBranding['system_logo'] }}" alt="System Logo" class="img-fluid mb-2 rounded-4 shadow-sm" style="max-height:75px;max-width:200px;object-fit:contain;background:#ffffff;padding:6px;border:1px solid var(--card-border);">
        @endif
        <div class="brand-name">{{ $appBranding['system_name'] ?? 'AMSTROOM' }}</div>
        <div class="brand-tagline">{{ $appBranding['system_slogan'] ?? 'Technology Innovations' }}</div>
    </div>

    <div class="auth-card">
        <h4>Enter Recovery Code</h4>
        <p class="sub">Check your email. Enter the 6-digit verification code sent to <strong>{{ $email }}</strong>.</p>

        @if(session('success'))
            <div class="alert alert-success mb-3 p-2 border border-success-subtle bg-success-subtle text-success rounded-3" style="font-size:.82rem;">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('demo_code'))
            <div class="alert alert-info mb-3 p-2 border border-info-subtle bg-info-subtle text-info-emphasis rounded-3" style="font-size:.82rem;">
                <i class="bi bi-key-fill me-1"></i> Demo Code: <strong style="font-size:1.1rem;letter-spacing:.1em;" class="ms-1">{{ session('demo_code') }}</strong>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-3 p-2 text-danger bg-danger-subtle border border-danger-subtle rounded-3" style="font-size:.82rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.verify') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="mb-4">
                <label for="code" class="form-label text-center d-block">6-Digit Code</label>
                <input type="text" id="code" name="code" class="form-control code-input @error('code') is-invalid @enderror"
                       maxlength="6" placeholder="000000" required autofocus autocomplete="off">
            </div>

            <button type="submit" class="btn-submit mb-3">
                <i class="bi bi-shield-check me-2"></i>Verify Code & Continue
            </button>

            <div class="d-flex justify-content-between align-items-center" style="font-size:.8rem;">
                <a href="{{ route('password.request') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-arrow-left me-1"></i>Resend Code
                </a>
                <a href="{{ route('login') }}" class="text-decoration-none text-muted">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
