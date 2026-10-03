<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KejaPad — Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-wrapper {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 580px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        /* Left Panel */
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(76,201,240,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(76,201,240,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            position: relative;
            z-index: 1;
        }

        .brand span { color: #4cc9f0; }

        .auth-left-content {
            position: relative;
            z-index: 1;
        }

        .auth-left-content h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .auth-left-content p {
            color: rgba(255,255,255,0.65);
            font-size: 0.9rem;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.75);
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .feature-list li .icon {
            width: 30px;
            height: 30px;
            background: rgba(76,201,240,0.15);
            border: 1px solid rgba(76,201,240,0.25);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4cc9f0;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .auth-left-footer {
            position: relative;
            z-index: 1;
        }

        .auth-left-footer p {
            color: rgba(255,255,255,0.35);
            font-size: 0.78rem;
            margin: 0;
        }

        /* Right Panel */
        .auth-right {
            width: 420px;
            background: #fff;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-right h4 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .auth-right .subtitle {
            color: #6c757d;
            font-size: 0.88rem;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .form-control {
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.9rem;
            color: #1a1a2e;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #4cc9f0;
            box-shadow: 0 0 0 3px rgba(76,201,240,0.12);
        }

        .input-group .form-control {
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .btn-toggle {
            border: 1.5px solid #e9ecef;
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: #fff;
            color: #6c757d;
            padding: 0 14px;
            transition: all 0.2s;
        }

        .input-group .btn-toggle:hover {
            color: #0f3460;
        }

        .input-group:focus-within .form-control,
        .input-group:focus-within .btn-toggle {
            border-color: #4cc9f0;
        }

        .input-group:focus-within .btn-toggle {
            box-shadow: 0 0 0 3px rgba(76,201,240,0.12);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #0f3460, #1a5276);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #4cc9f0, #0096c7);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(76,201,240,0.3);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: #adb5bd;
            font-size: 0.8rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9ecef;
        }

        .register-link {
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .register-link a {
            color: #0f3460;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            color: #4cc9f0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6c757d;
            font-size: 0.82rem;
            text-decoration: none;
            margin-bottom: 25px;
            transition: color 0.2s;
        }

        .back-link:hover { color: #0f3460; }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-left { display: none; }
            .auth-right {
                width: 100%;
                padding: 40px 30px;
                border-radius: 24px;
            }
            .auth-wrapper {
                border-radius: 24px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <!-- Left Panel -->
    <div class="auth-left">
        <div class="brand"><span>Keja</span>Pad</div>

        <div class="auth-left-content">
            <h2>Welcome Back to KejaPad</h2>
            <p>
                Kenya's smartest property management platform.
                Manage your properties, collect rent, and keep
                tenants happy — all from one dashboard.
            </p>

            <ul class="feature-list">
                <li>
                    <div class="icon"><i class="bi bi-buildings"></i></div>
                    Manage multiple properties with ease
                </li>
                <li>
                    <div class="icon"><i class="bi bi-phone"></i></div>
                    Collect rent via M-Pesa STK Push
                </li>
                <li>
                    <div class="icon"><i class="bi bi-people"></i></div>
                    Track tenants, units, and payments
                </li>
                <li>
                    <div class="icon"><i class="bi bi-bar-chart"></i></div>
                    Generate PDF and Excel reports
                </li>
                <li>
                    <div class="icon"><i class="bi bi-tools"></i></div>
                    Handle maintenance requests instantly
                </li>
            </ul>
        </div>

        <div class="auth-left-footer">
            <p>© {{ date('Y') }} KejaPad. Built for Kenya's Property Market.</p>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">

        <a href="{{ route('landing') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <h4>Sign In</h4>
        <p class="subtitle">Enter your credentials to access your dashboard.</p>

        <!-- Session Status -->
        @if (session('status'))
            <div class="alert alert-success alert-sm mb-3" style="font-size:0.85rem;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="your@email.com"
                       required autofocus autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           style="font-size:0.8rem; color:#4cc9f0; text-decoration:none;">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="passwordField"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter password"
                           required autocomplete="current-password">
                    <button type="button" class="btn-toggle"
                            onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Remember Me -->
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox"
                       name="remember" id="remember">
                <label class="form-check-label"
                       for="remember"
                       style="font-size:0.85rem; color:#6c757d;">
                    Keep me signed in
                </label>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Sign In to KejaPad
            </button>
        </form>

        <div class="divider">or</div>

        <div class="register-link">
            Don't have an account?
            <a href="{{ route('register') }}">Create one here</a>
        </div>

        <!-- Role hint badges -->
        <div class="text-center mt-4">
            <p style="font-size:0.75rem; color:#adb5bd; margin-bottom:8px;">
                Platform roles
            </p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <span class="badge bg-primary" style="font-size:0.7rem;">Landlord</span>
                <span class="badge bg-success" style="font-size:0.7rem;">Caretaker</span>
                <span class="badge" style="font-size:0.7rem; background:#4cc9f0; color:#000;">Tenant</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword() {
        const field   = document.getElementById('passwordField');
        const icon    = document.getElementById('eyeIcon');
        const isText  = field.type === 'text';
        field.type    = isText ? 'password' : 'text';
        icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
</script>
</body>
</html>