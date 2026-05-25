<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>KejaPad Login</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body>
    <main class="auth-layout">
        <section class="auth-card">
            <a class="brand" href="index.html">
                <div class="mark">K</div>
                <div><strong>KejaPad</strong><span>Secure tenant and landlord login</span></div>
            </a>
            <div class="eyebrow" style="margin-top:24px">Over-the-top authentication</div>
            <h1>Welcome back.</h1>
            <p class="lead">Use the demo credentials or your own details. This prototype stores the signed-in name in your browser only.</p>
            <form data-login-form>
                <div class="role-tabs">
                    <button class="active" data-role="Tenant" type="button">Tenant</button>
                    <button data-role="Landlord" type="button">Landlord</button>
                </div>
                <div class="field">
                    <label for="email">Verified email</label>
                    <div class="input-wrap">
                        <span>@</span>
                        <input id="email" type="email" value="amina@kejapad.app" autocomplete="email" />
                    </div>
                </div>
                <div class="field">
                    <label for="password">Passkey vault</label>
                    <div class="input-wrap">
                        <span>*</span>
                        <input id="password" type="password" value="kejapad-secure" autocomplete="current-password" />
                    </div>
                </div>
                <div class="secure-row">
                    <div><b>2FA</b> Passkey</div>
                    <div><b>ID</b> Verified</div>
                    <div><b>256</b> Audit log</div>
                </div>
                <button class="primary-btn" type="submit">Unlock KejaPad</button>
            </form>
            <div class="auth-links">
                <a href="{{ route('dashboard') }}">Create Account</a>
        <a href="{{ route('login') }}">Back to login</a>
        </div>
    </section>
</main>
    <div> class("toast"):
        <strong>KejaPad updated</strong>
        <span>Action completed.</span>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
    </body>
</html>