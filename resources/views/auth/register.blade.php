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
        :root {
            --kp-deep: #32145C;
            --kp-royal: #5926C8;
            --kp-gold: #F4C542;
            --kp-bg: #F7F6FB;
            --kp-text: #241A33;
            --kp-muted: #777387;
            --kp-border: #EAE6F2;
            --kp-success: #168653;
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
            max-width: 980px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(50, 20, 92, 0.18);
            background: #fff;
        }

        /* ===== Left panel ===== */
        .auth-left {
            flex: 1 1 46%;
            background: var(--kp-deep);
            padding: 46px 40px;
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
            width: 300px; height: 300px;
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
            color: #fff; font-size: 1.6rem; font-weight: 800;
            line-height: 1.3; margin-bottom: 12px;
        }
        .auth-left-content p {
            color: rgba(255,255,255,0.66); font-size: 0.88rem;
            line-height: 1.7; margin-bottom: 24px;
        }

        .step-list { list-style: none; padding: 0; margin: 0; }
        .step-list li {
            display: flex; align-items: flex-start; gap: 12px;
            color: rgba(255,255,255,0.75); font-size: 0.83rem; margin-bottom: 16px;
        }
        .step-num {
            width: 26px; height: 26px; flex-shrink: 0; margin-top: 1px;
            background: rgba(244,197,66,0.16);
            border: 1px solid rgba(244,197,66,0.34);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--kp-gold); font-weight: 700; font-size: 0.75rem;
        }
        .step-list strong { color: #fff; }

        .auth-left-footer { position: relative; z-index: 1; }
        .auth-left-footer p { color: rgba(255,255,255,0.35); font-size: 0.76rem; margin: 0; }

        /* ===== Right panel ===== */
        .auth-right {
            flex: 1 1 54%;
            background: #fff;
            padding: 42px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-right h4 { font-size: 1.5rem; font-weight: 800; color: var(--kp-deep); margin-bottom: 6px; }
        .auth-right .subtitle { color: var(--kp-muted); font-size: 0.88rem; margin-bottom: 24px; }

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

        .role-select {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .role-option {
            border: 1.5px solid var(--kp-border);
            border-radius: 10px;
            padding: 10px 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
        }
        .role-option:hover { border-color: var(--kp-gold); background: var(--kp-bg); }
        .role-option input[type="radio"] { display: none; }
        .role-option.selected { border-color: var(--kp-royal); background: rgba(89,38,200,0.07); }
        .role-option .role-icon { font-size: 1.1rem; }
        .role-option .role-label { font-size: 0.78rem; font-weight: 600; color: var(--kp-text); }

        .btn-primary-custom {
            background: var(--kp-royal);
            color: #fff; border: none;
            border-radius: 10px;
            padding: 13px;
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 8px 20px rgba(89,38,200,0.24);
        }
        .btn-primary-custom:hover {
            background: #4a1ea8; color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 26px rgba(89,38,200,0.32);
        }

        .login-link { text-align: center; font-size: 0.85rem; color: var(--kp-muted); margin-top: 18px; }
        .login-link a { color: var(--kp-royal); font-weight: 600; text-decoration: none; }
        .login-link a:hover { color: var(--kp-deep); }

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--kp-muted); font-size: 0.82rem; text-decoration: none;
            margin-bottom: 20px; transition: color 0.18s;
        }
        .back-link:hover { color: var(--kp-royal); }

        @media (max-width: 820px) {
            .auth-left { display: none; }
            .auth-right { flex: 1 1 100%; padding: 38px 28px; }
            .auth-wrapper { border-radius: 20px; }
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
            <h2>Start managing your properties today.</h2>
            <p>
                Create your KejaPad account and get full access to
                Kenya's most complete property management platform.
            </p>

            <ul class="step-list">
                <li>
                    <div class="step-num">1</div>
                    <div><strong>Create your account</strong><br>Register with your name, email, and password.</div>
                </li>
                <li>
                    <div class="step-num">2</div>
                    <div><strong>Add your properties</strong><br>Register your properties, blocks, and units.</div>
                </li>
                <li>
                    <div class="step-num">3</div>
                    <div><strong>Add your tenants</strong><br>Onboard tenants and assign them to units.</div>
                </li>
                <li>
                    <div class="step-num">4</div>
                    <div><strong>Collect rent via M-Pesa</strong><br>Generate records and receive payments instantly.</div>
                </li>
            </ul>
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

        <h4>Create Account</h4>
        <p class="subtitle">Fill in the details below to get started.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div class="mb-3">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" name="name" id="name"
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
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email"
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
                        <span class="role-icon">&#127970;</span>
                        <span class="role-label">Landlord</span>
                    </label>

                    <label class="role-option {{ old('role') === 'caretaker' ? 'selected' : '' }}"
                           id="label-caretaker">
                        <input type="radio" name="role" value="caretaker"
                               {{ old('role') === 'caretaker' ? 'checked' : '' }}
                               onchange="selectRole('caretaker')">
                        <span class="role-icon">&#128295;</span>
                        <span class="role-label">Caretaker</span>
                    </label>

                    <label class="role-option {{ old('role') === 'tenant' ? 'selected' : '' }}"
                           id="label-tenant">
                        <input type="radio" name="role" value="tenant"
                               {{ old('role') === 'tenant' ? 'checked' : '' }}
                               onchange="selectRole('tenant')">
                        <span class="role-icon">&#127968;</span>
                        <span class="role-label">Tenant</span>
                    </label>
                </div>
                @error('role')
                    <div class="text-danger mt-1" style="font-size:0.82rem;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Caretaker invitation code (only for caretaker registrations) -->
            <div class="mb-3 d-none" id="caretakerInvitationBlock">
                <label class="form-label" for="invitationCode">Caretaker Invitation Code</label>
                <input type="text" name="invitation_code" id="invitationCode"
                       class="form-control @error('invitation_code') is-invalid @enderror"
                       value="{{ old('invitation_code', $prefillCode) }}"
                       placeholder="Paste the code your landlord sent you"
                       autocomplete="off">
                @error('invitation_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <button type="button"
                        class="btn btn-sm mt-2"
                        style="border:1.5px solid var(--kp-royal); color:var(--kp-royal); border-radius:8px; font-weight:600;"
                        onclick="verifyInvitationCode()">
                    <i class="bi bi-patch-check me-1"></i> Verify Code
                </button>
                <div id="invitationResult" class="small mt-2"></div>
                <div class="form-text" style="font-size:0.76rem;">
                    A caretaker account can only be created with a valid invitation from an active landlord.
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label" for="passwordField">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordField"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Min 8 characters"
                           required autocomplete="new-password">
                    <button type="button" class="btn-toggle"
                            onclick="togglePassword('passwordField', 'eyeIcon1')" title="Show / hide password">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label class="form-label" for="confirmField">Confirm Password</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation" id="confirmField"
                           class="form-control"
                           placeholder="Repeat password"
                           required autocomplete="new-password">
                    <button type="button" class="btn-toggle"
                            onclick="togglePassword('confirmField', 'eyeIcon2')" title="Show / hide password">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-person-plus me-2"></i> Create My Account
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
        ['landlord', 'caretaker', 'tenant'].forEach(function (r) {
            const el = document.getElementById('label-' + r);
            if (el) el.classList.remove('selected');
        });
        const selected = document.getElementById('label-' + role);
        if (selected) selected.classList.add('selected');

        const block = document.getElementById('caretakerInvitationBlock');
        if (block) block.classList.toggle('d-none', role !== 'caretaker');
    }

    function verifyInvitationCode() {
        const input  = document.getElementById('invitationCode');
        const email  = document.getElementById('email');
        const result = document.getElementById('invitationResult');

        result.style.color = 'var(--kp-muted)';
        result.textContent = 'Checking invitation…';

        fetch("{{ route('register.verify-invitation-code') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                invitation_code: input ? input.value : '',
                email: email ? email.value : '',
            }),
        })
        .then(function (response) {
            return response.json().then(function (body) {
                return { ok: response.ok, body: body };
            });
        })
        .then(function (res) {
            const body = res.body || {};
            if (res.ok && body.valid) {
                result.style.color = 'var(--kp-success)';
                result.textContent = 'Valid invitation'
                    + (body.property ? ' for ' + body.property : '')
                    + (body.landlord ? ' (landlord: ' + body.landlord + ')' : '') + '.';
            } else {
                result.style.color = 'var(--kp-danger)';
                result.textContent = body.error
                    || (body.errors && body.errors.invitation_code && body.errors.invitation_code[0])
                    || 'This invitation code is not valid.';
            }
        })
        .catch(function () {
            result.style.color = 'var(--kp-danger)';
            result.textContent = 'Could not verify the code. Please try again.';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        let checked = document.querySelector('input[name="role"]:checked');
        const codeField = document.getElementById('invitationCode');

        if (!checked && codeField && codeField.value) {
            const caretaker = document.querySelector('input[name="role"][value="caretaker"]');
            if (caretaker) caretaker.checked = true;
            checked = caretaker;
        }

        if (checked) selectRole(checked.value);
    });
</script>
</body>
</html>
