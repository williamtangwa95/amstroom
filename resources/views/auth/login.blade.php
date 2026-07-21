<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ $appBranding['system_name'] ?? 'AMSTROOM' }}</title>
    <meta name="description" content="Login to {{ $appBranding['system_name'] ?? 'AMSTROOM' }} Management System">

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
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin: 0;
            padding: 1rem 0;
        }

        /* Ambient soft background glow orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: .12;
            animation: float 10s ease-in-out infinite;
            z-index: 1;
        }
        body::before {
            width: 500px; height: 500px;
            background: #0088cc;
            top: -120px; left: -100px;
            animation-delay: 0s;
        }
        body::after {
            width: 450px; height: 450px;
            background: #ffb700;
            bottom: -120px; right: -100px;
            animation-delay: 5s;
        }
        @keyframes float {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(25px, 20px) scale(1.05); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 430px;
            padding: 1.25rem;
            position: relative;
            z-index: 10;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-logo {
            width: 68px; height: 68px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 24px rgba(0, 136, 204, 0.12);
        }
        .brand-logo i { font-size: 2rem; color: var(--accent); }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -.02em;
            line-height: 1.2;
            margin-bottom: .25rem;
        }
        .brand-tagline {
            font-size: .8rem;
            color: #d97706;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.25rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .login-card h4 { font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: .25rem; }
        .login-card p.sub { font-size: .82rem; color: var(--text-secondary); margin-bottom: 1.75rem; }

        .form-label { font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 8px;
            font-size: .88rem;
            padding: .65rem .85rem;
            transition: all .15s ease;
        }
        .form-control:focus {
            background: var(--input-bg);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.15);
        }
        .form-control::placeholder { color: #94a3b8; }

        .input-group-text {
            background: #f8fafc;
            border: 1px solid var(--input-border);
            color: var(--accent);
        }

        .btn-login {
            background: linear-gradient(135deg, #0088cc, #006699);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: .9rem;
            padding: .75rem;
            border-radius: 10px;
            width: 100%;
            transition: all .2s ease;
            letter-spacing: .01em;
            box-shadow: 0 4px 14px rgba(0, 136, 204, 0.25);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #0077b6, #004d73);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 136, 204, 0.35);
            color: #ffffff;
        }
        .btn-login:active { transform: translateY(0); }

        .demo-accounts {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border: 1px solid var(--card-border);
            border-radius: 12px;
        }
        .demo-accounts h6 {
            font-size: .72rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .75rem;
        }
        .demo-item {
            font-size: .78rem;
            color: #334155;
            margin-bottom: .4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .25rem .35rem;
            border-radius: 6px;
            transition: background .15s ease;
        }
        .demo-item:hover {
            background: rgba(0, 136, 204, 0.06);
        }
        .demo-item:last-child { margin-bottom: 0; }
        .demo-item .role-chip {
            padding: .15rem .5rem;
            border-radius: 20px;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .chip-owner  { background: rgba(255, 183, 0, 0.15); color: #d97706; border: 1px solid rgba(255, 183, 0, 0.3); }
        .chip-admin  { background: rgba(0, 136, 204, 0.15); color: #0088cc; border: 1px solid rgba(0, 136, 204, 0.3); }
        .chip-seller { background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }

        .alert { border-radius: 10px; font-size: .83rem; border: none; }
        .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-brand">
        @if(!empty($appBranding['system_logo']))
            <img src="{{ $appBranding['system_logo'] }}" alt="System Logo" class="img-fluid mb-3 rounded-4 shadow-sm" style="max-height:85px;max-width:220px;object-fit:contain;background:#ffffff;padding:8px;border:1px solid var(--card-border);">
        @else
            <div class="brand-logo"><i class="bi bi-pc-display-horizontal"></i></div>
        @endif
        <div class="brand-name">{{ $appBranding['system_name'] ?? 'AMSTROOM' }}</div>
        <div class="brand-tagline">{{ $appBranding['system_slogan'] ?? 'Technology Innovations' }}</div>
    </div>

    <div class="login-card">
        <h4>Welcome back</h4>
        <p class="sub">Sign in to your account to continue</p>

        @if(session('success'))
            <div class="alert alert-success mb-3 p-2 border border-success-subtle bg-success-subtle text-success rounded-3" style="font-size:.82rem;">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-3" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;border-radius:10px;font-size:.83rem;">
                <i class="bi bi-exclamation-circle me-1"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" id="loginForm">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-fill" style="font-size:.85rem;"></i></span>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           placeholder="you@amstroom.com" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill" style="font-size:.85rem;"></i></span>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="input-group-text" onclick="togglePwd()">
                        <i id="pwdIcon" class="bi bi-eye" style="font-size:.85rem;cursor:pointer;color:#64748b;"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember"
                           style="background-color:var(--input-bg);border-color:var(--input-border);">
                    <label class="form-check-label" for="remember" style="font-size:.8rem;color:var(--text-secondary);">Remember me</label>
                </div>
                <a href="{{ route('password.request') }}" class="fw-600 text-decoration-none" style="font-size:.8rem;color:var(--accent);">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="demo-accounts">
            <h6>Demo Accounts (click to fill)</h6>
            <div class="demo-item">
                <span>owner@amstroom.com</span>
                <span class="role-chip chip-owner">Owner</span>
            </div>
            <div class="demo-item">
                <span>admin1@amstroom.com</span>
                <span class="role-chip chip-admin">Shop Admin</span>
            </div>
            <div class="demo-item">
                <span>seller1@amstroom.com</span>
                <span class="role-chip chip-seller">Seller</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePwd() {
        const inp = document.getElementById('password');
        const icon = document.getElementById('pwdIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    // Quick fill demo accounts
    document.querySelectorAll('.demo-item').forEach(item => {
        item.style.cursor = 'pointer';
        item.addEventListener('click', () => {
            document.getElementById('email').value = item.querySelector('span:first-child').textContent;
            document.getElementById('password').value = 'password';
        });
    });
</script>
</body>
</html>
