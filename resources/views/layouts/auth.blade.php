<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KejaPad')</title>

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

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border: 1px solid var(--kp-border);
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(50, 20, 92, 0.14);
            padding: 40px 36px;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .auth-brand .mark {
            width: 40px; height: 40px; flex-shrink: 0;
            background: var(--kp-deep);
            border-radius: 11px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .auth-brand .word { font-weight: 800; font-size: 1.3rem; color: var(--kp-deep); line-height: 1; }
        .auth-brand .word span { color: var(--kp-royal); }

        .auth-card h1,
        .auth-card h4 {
            font-weight: 800;
            color: var(--kp-deep);
            font-size: 1.4rem;
            margin-bottom: 6px;
            text-align: center;
        }
        .auth-card .subtitle {
            color: var(--kp-muted);
            font-size: 0.88rem;
            text-align: center;
            margin-bottom: 26px;
        }

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
            box-shadow: 0 0 0 3px rgba(89, 38, 200, 0.14);
        }
        .form-control::placeholder { color: #b3aec0; }
        .form-control.is-invalid { border-color: var(--kp-danger); }

        .btn-auth {
            background: var(--kp-royal);
            color: #fff; border: none; border-radius: 10px;
            padding: 13px; font-weight: 700; font-size: 0.95rem;
            width: 100%; transition: all 0.2s;
            box-shadow: 0 8px 20px rgba(89, 38, 200, 0.24);
        }
        .btn-auth:hover {
            background: #4a1ea8; color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 26px rgba(89, 38, 200, 0.32);
        }

        .auth-links { text-align: center; font-size: 0.85rem; color: var(--kp-muted); margin-top: 18px; }
        .auth-links a { color: var(--kp-royal); font-weight: 600; text-decoration: none; }
        .auth-links a:hover { color: var(--kp-deep); }
    </style>
    <link href="{{ asset('css/kejapad-design.css') }}" rel="stylesheet">
</head>
<body>

<main class="auth-card">

    <a href="{{ route('landing') }}" class="d-block text-center mb-3"
       style="color:var(--kp-muted); font-size:0.8rem; text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="auth-brand">
        <span class="mark">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M3 10.5 12 3l9 7.5" stroke="#F4C542" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 9.8V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.8" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="10" y="14" width="4" height="7" rx="0.6" fill="#F4C542"/>
            </svg>
        </span>
        <span class="word">Keja<span>Pad</span></span>
    </div>

    <h1>@yield('heading')</h1>
    <p class="subtitle">@yield('subtitle')</p>

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
