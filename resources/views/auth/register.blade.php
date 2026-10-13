<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KejaPad — Create Account</title>

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
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }

        /* Left Panel */
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #4c1d95 0%, #2e1065 60%, #1a0b2e 100%);
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
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(212,175,55,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(212,175,55,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            position: relative;
            z-index: 1;
        }

        .brand span { color: #d4af37; }

        .auth-left-content {
            position: relative;
            z-index: 1;
        }

        .auth-left-content h2 {
            font-size: 1.7rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .auth-left-content p {
            color: rgba(255,255,255,0.65);
            font-size: 0.88rem;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .step-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .step-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: rgba(255,255,255,0.75);
            font-size: 0.84rem;
            margin-bottom: 16px;
        }

        .step-num {
            width: 26px;
            height: 26px;
            background: rgba(212,175,55,0.2);
            border: 1px solid rgba(212,175,55,0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4af37;
            font-weight: 700;
            font-size: 0.75rem;
            flex-shrink: 0;
            margin-top: 1px;
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
            width: 440px;
            background: #fff;
            padding: 45px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-right h4 {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1a0b2e;
            margin-bottom: 4px;
        }

        .auth-right .subtitle {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.82rem;
            color: #1a0b2e;
            margin-bottom: 5px;
        }

        .form-control {
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.88rem;
            color: #1a0b2e;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.12);
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
        }

        .input-group:focus-within .form-control,
        .input-group:focus-within .btn-toggle {
            border-color: #d4af37;
        }

        .role-select {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
        }

        .role-option {
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-option:hover {
            border-color: #d4af37;
            background: #f8fcff;
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-option.selected {
            border-color: #4c1d95;
            background: #efe7fb;
        }

        .role-option .role-icon {
            font-size: 1.1rem;
        }

        .role-option .role-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #1a0b2e;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #4c1d95, #1a5276);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #d4af37, #4c1d95);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(212,175,55,0.3);
        }

        .login-link {
            text-align: center;
            font-size: 0.84rem;
            color: #6c757d;
            margin-top: 16px;
        }

        .login-link a {
            color: #4c1d95;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover { color: #a67c00; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6c757d;
            font-size: 0.82rem;
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.2s;
        }

        .back-link:hover { color: #4c1d95; }

        @media (max-width: 768px) {
            .auth-left { display: none; }
            .auth-right {
                width: 100%;
                padding: 35px 25px;
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
            <h2>Start Managing Your Properties Today</h2>
            <p>
                Create your KejaPad account and get full access to
                Kenya's most complete property management platform.
            </p>

            <ul class="step-list">
                <li>
                    <div class="step-num">1</div>
                    <div>
                        <strong style="color:#fff;">Create your account</strong><br>
                        Register with your name, email, and password.
                    </div>
                </li>
                <li>
                    <div class="step-num">2</div>
                    <div>
                        <strong style="color:#fff;">Add your properties</strong><br>
                        Register your properties, blocks, and units.
                    </div>
                </li>
                <li>
                    <div class="step-num">3</div>
                    <div>
                        <strong style="color:#fff;">Add your tenants</strong><br>
                        Onboard tenants and assign them to units.
                    </div>
                </li>
                <li>
                    <div class="step-num">4</div>
                    <div>
                        <strong style="color:#fff;">Collect rent via M-Pesa</strong><br>
                        Generate records and receive payments instantly.
                    </div>
                </li>
            </ul>
        </div>

        <div class="auth-left-footer">
            <p>© {{ date('Y') }} KejaPad. All rights reserved.</p>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-right">

        <a href="{{ route('landing') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <h4>Create Account</h4>
        <p class="subtitle">Fill in the details below to get started.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}"
                       placeholder="e.g. John Kamau"
                       required autofocus autocomplete="name">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}"
                       placeholder="your@email.com"
                       required autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Role Selection -->
            <div class="mb-3">
                <label class="form-label">I am registering as</label>
                <div class="role-select">
                    <label class="role-option {{ old('role') === 'landlord' ? 'selected' : '' }}"
                           id="label-landlord">
                        <input type="radio" name="role" value="landlord"
                               {{ old('role') === 'landlord' ? 'checked' : '' }}
                               onchange="selectRole('landlord')">
                        <span class="role-icon">🏢</span>
                        <span class="role-label">Landlord</span>
                    </label>

                    <label class="role-option {{ old('role') === 'caretaker' ? 'selected' : '' }}"
                           id="label-caretaker">
                        <input type="radio" name="role" value="caretaker"
                               {{ old('role') === 'caretaker' ? 'checked' : '' }}
                               onchange="selectRole('caretaker')">
                        <span class="role-icon">🔧</span>
                        <span class="role-label">Caretaker</span>
                    </label>

                    <label class="role-option {{ old('role') === 'tenant' ? 'selected' : '' }}"
                           id="label-tenant">
                        <input type="radio" name="role" value="tenant"
                               {{ old('role') === 'tenant' ? 'checked' : '' }}
                               onchange="selectRole('tenant')">
                        <span class="role-icon">🏠</span>
                        <span class="role-label">Tenant</span>
                    </label>
                    
                </div>
                @error('role')
                    <div class="text-danger" style="font-size:0.82rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordField"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Min 8 characters"
                           required autocomplete="new-password">
                    <button type="button" class="btn-toggle"
                            onclick="togglePassword('passwordField', 'eyeIcon1')">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation"
                           id="confirmField"
                           class="form-control"
                           placeholder="Repeat password"
                           required autocomplete="new-password">
                    <button type="button" class="btn-toggle"
                            onclick="togglePassword('confirmField', 'eyeIcon2')">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-person-plus me-2"></i>
                Create My Account
            </button>
        </form>

        <div class="login-link">
            Already have an account?
            <a href="{{ route('login') }}">Sign in here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(fieldId, iconId) {
        const field  = document.getElementById(fieldId);
        const icon   = document.getElementById(iconId);
        const isText = field.type === 'text';
        field.type   = isText ? 'password' : 'text';
        icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    function selectRole(role) {
        const roles = ['landlord', 'caretaker', 'tenant'];
        roles.forEach(r => {
            const el = document.getElementById('label-' + r);
            if (el) el.classList.remove('selected');
        });
        const selected = document.getElementById('label-' + role);
        if (selected) selected.classList.add('selected');
    }
</script>
</body>
</html>
