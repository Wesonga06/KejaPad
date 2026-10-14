<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KejaPad — Sign In</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --kp-deep: #32145C;
            --kp-royal: #5926C8;
            --kp-gold: #F4C542;
            --kp-bg: #F7F6FB;
            --kp-text: #241A33;
            --kp-muted: #777387;
            --kp-border: #EAE6F2;
            --kp-danger: #DC4545;
        }

        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            background: var(--kp-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            margin: 0;
        }

        .auth-wrapper {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 600px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(50, 20, 92, 0.18);
            background: #fff;
        }

        /* ===== Left panel ===== */
        .auth-left {
            flex: 1 1 52%;
            background: var(--kp-deep);
            padding: 46px 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(244,197,66,0.16) 0%, transparent 68%);
            border-radius: 50%;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -50px;
            width: 240px; height: 240px;
            background: radial-gradient(circle, rgba(89,38,200,0.35) 0%, transparent 70%);
            border-radius: 50%;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .auth-brand .brand-text h1 {
            color: #fff; font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1;
            letter-spacing: .3px;
        }
        .auth-brand .brand-text h1 span { color: var(--kp-gold); }
        .auth-brand .brand-text small {
            color: rgba(255,255,255,0.55); font-size: 0.72rem; letter-spacing: .4px;
        }

        .auth-left-content { position: relative; z-index: 1; }
        .auth-left-content h2 {
            color: #fff; font-size: 1.75rem; font-weight: 800;
            line-height: 1.28; margin-bottom: 14px;
        }
        .auth-left-content p {
            color: rgba(255,255,255,0.66); font-size: 0.9rem;
            line-height: 1.7; margin-bottom: 26px; max-width: 40ch;
        }

        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li {
            display: flex; align-items: center; gap: 11px;
            color: rgba(255,255,255,0.78); font-size: 0.85rem; margin-bottom: 13px;
        }
        .feature-list li .icon {
            width: 30px; height: 30px; flex-shrink: 0;
            background: rgba(244,197,66,0.14);
            border: 1px solid rgba(244,197,66,0.28);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--kp-gold); font-size: 0.85rem;
        }

        .auth-illustration {
            position: relative; z-index: 1;
            margin-top: 22px; opacity: 0.9;
        }

        .auth-left-footer { position: relative; z-index: 1; }
        .auth-left-footer p { color: rgba(255,255,255,0.35); font-size: 0.76rem; margin: 0; }

        /* ===== Right panel ===== */
        .auth-right {
            flex: 1 1 48%;
            background: #fff;
            padding: 46px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-right h4 { font-size: 1.5rem; font-weight: 800; color: var(--kp-deep); margin-bottom: 6px; }
        .auth-right .subtitle { color: var(--kp-muted); font-size: 0.88rem; margin-bottom: 26px; }

        .form-label { font-weight: 600; font-size: 0.83rem; color: var(--kp-text); margin-bottom: 6px; }

        .form-control {
            border: 1.5px solid var(--kp-border);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.9rem;
            color: var(--kp-text);
            transition: all 0.18s;
        }
        .form-control:focus {
            border-color: var(--kp-royal);
            box-shadow: 0 0 0 3px rgba(89,38,200,0.14);
        }
        .form-control::placeholder { color: #b3aec0; }
        .form-control.is-invalid { border-color: var(--kp-danger); }

        .input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }
        .input-group .btn-toggle {
            border: 1.5px solid var(--kp-border);
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: #fff; color: var(--kp-muted);
            padding: 0 14px; transition: all 0.18s;
        }
        .input-group .btn-toggle:hover { color: var(--kp-royal); }
        .input-group:focus-within .form-control,
        .input-group:focus-within .btn-toggle { border-color: var(--kp-royal); }

        .btn-signin {
            background: var(--kp-royal);
            color: #fff; border: none; border-radius: 10px;
            padding: 13px; font-weight: 700; font-size: 0.95rem;
            width: 100%; transition: all 0.2s;
            box-shadow: 0 8px 20px rgba(89,38,200,0.24);
        }
        .btn-signin:hover {
            background: #4a1ea8; color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 26px rgba(89,38,200,0.32);
        }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0; color: var(--kp-muted); font-size: 0.8rem;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--kp-border); }

        .register-link { text-align: center; font-size: 0.85rem; color: var(--kp-muted); }
        .register-link a { color: var(--kp-royal); font-weight: 600; text-decoration: none; }
        .register-link a:hover { color: var(--kp-deep); }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--kp-muted); font-size: 0.82rem; text-decoration: none;
            margin-bottom: 22px; transition: color 0.18s;
        }
        .back-link:hover { color: var(--kp-royal); }

        .role-badge { font-size: 0.7rem; font-weight: 600; padding: 0.35em 0.7em; border-radius: 8px; }
        .role-badge.landlord { background: rgba(89,38,200,0.1); color: var(--kp-royal); }
        .role-badge.caretaker { background: rgba(22,134,83,0.12); color: #168653; }
        .role-badge.tenant { background: rgba(244,197,66,0.25); color: #8a6a00; }

        @media (max-width: 820px) {
            .auth-left { display: none; }
            .auth-right { flex: 1 1 100%; padding: 40px 30px; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <!-- Left Panel -->
    <div class="auth-left">
        <div class="auth-brand">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M3 10.5 12 3l9 7.5" stroke="#F4C542" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 9.8V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.8" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="10" y="14" width="4" height="7" rx="0.6" fill="#F4C542"/>
            </svg>
            <div class="brand-text">
                <h1>Keja<span>Pad</span></h1>
                <small>Manage &bull; Track &bull; Grow</small>
            </div>
        </div>

        <div class="auth-left-content">
            <h2>Smart property management for modern landlords.</h2>
            <p>
                Manage properties, track rent, collect payments via M-Pesa,
                and keep tenants happy — all from one clean dashboard.
            </p>

            <ul class="feature-list">
                <li><span class="icon"><i class="bi bi-buildings"></i></span> Manage multiple properties with ease</li>
                <li><span class="icon"><i class="bi bi-phone"></i></span> Collect rent via M-Pesa STK Push</li>
                <li><span class="icon"><i class="bi bi-people"></i></span> Track tenants, units and payments</li>
                <li><span class="icon"><i class="bi bi-bar-chart"></i></span> Generate PDF and Excel reports</li>
            </ul>

            <div class="auth-illustration">
                <svg width="100%" height="82" viewBox="0 0 320 82" fill="none" preserveAspectRatio="none" aria-hidden="true">
                    <rect x="8"   y="34" width="54" height="48" rx="4" fill="#43206e"/>
                    <rect x="76"  y="14" width="70" height="68" rx="4" fill="#3b1c63"/>
                    <rect x="160" y="28" width="60" height="54" rx="4" fill="#43206e"/>
                    <rect x="234" y="44" width="78" height="38" rx="4" fill="#3b1c63"/>
                    <g fill="#F4C542" opacity="0.85">
                        <rect x="88"  y="24" width="10" height="10" rx="2"/>
                        <rect x="108" y="24" width="10" height="10" rx="2"/>
                        <rect x="88"  y="42" width="10" height="10" rx="2"/>
                        <rect x="108" y="42" width="10" height="10" rx="2"/>
                        <rect x="172" y="38" width="9" height="9" rx="2"/>
                        <rect x="190" y="38" width="9" height="9" rx="2"/>
                        <rect x="172" y="56" width="9" height="9" rx="2"/>
                        <rect x="190" y="56" width="9" height="9" rx="2"/>
                        <rect x="18"  y="44" width="9" height="9" rx="2"/>
                        <rect x="36"  y="44" width="9" height="9" rx="2"/>
                        <rect x="18"  y="62" width="9" height="9" rx="2"/>
                        <rect x="248" y="54" width="9" height="9" rx="2"/>
                        <rect x="268" y="54" width="9" height="9" rx="2"/>
                    </g>
                </svg>
            </div>
        </div>

        <div class="auth-left-footer">
            <p>&copy; {{ date('Y') }} KejaPad. Built for Kenya's property market.</p>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">
        <a href="{{ route('landing') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <h4>Welcome Back &#128075;</h4>
        <p class="subtitle">Sign in to continue to your dashboard.</p>

        @if (session('status'))
            <div class="alert alert-success mb-3" style="font-size:0.85rem;">
                <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="your@email.com"
                       required autofocus autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0" for="passwordField">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size:0.8rem; color:var(--kp-royal);">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="passwordField"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter password"
                           required autocomplete="current-password">
                    <button type="button" class="btn-toggle" onclick="togglePassword()" title="Show / hide password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember" style="font-size:0.85rem; color:var(--kp-muted);">
                    Keep me signed in
                </label>
            </div>

            <button type="submit" class="btn-signin">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="divider">or</div>

        <div class="register-link">
            Don't have an account?
            <a href="{{ route('register') }}">Create one here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword() {
        const field = document.getElementById('passwordField');
        const icon = document.getElementById('eyeIcon');
        const isText = field.type === 'text';
        field.type = isText ? 'password' : 'text';
        icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
</script>
</body>
</html>
