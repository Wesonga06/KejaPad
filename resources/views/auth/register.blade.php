<!doctype html>
<html lang="en" data-theme="dark">
<head><meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>KejaPad Sign Up</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <main class="auth-layout">
        <section class="auth-card">
            <a class="brand" href="{{ route('dashboard') }}">
                <div class="mark">K</div><div><strong>KejaPad</strong><span>Create your rental workspace</span></div></a>
                <div class="eyebrow" style="margin-top:24px">Tenant or landlord onboarding</div>
                <h1>Create account.</h1>
                <p class="lead">Start with a verified profile, role selection, and secure access setup for your KejaPad workspace.</p>
                <form data-signup-form>
                    <div class="role-tabs">
                        <button class="active" data-role="Tenant" type="button">Tenant</button>
                        <button data-role="Landlord" type="button">Landlord</button>
                    </div>
                    <div class="field">
                        <label for="name">Full name</label>
                        <div class="input-wrap"><span>ID</span><input id="name" value="Amina Wanjiku" autocomplete="name" /></div></div>
                        <div class="field"><label for="email">Email address</label>
                            <div class="input-wrap"><span>@</span><input id="email" type="email" value="amina@kejapad.app" autocomplete="email" /></div></div>
                            <div class="field"><label for="password">Create password</label><div class="input-wrap"><span>*</span><input id="password" type="password" value="kejapad-secure" autocomplete="new-password" />
                            </div></div>
                            <div class="secure-row">
                                <div><b>KYC</b> Ready</div>
                                <div><b>OTP</b> Enabled</div>
                                <div><b>Vault</b> Secure</div></div>
                                <button class="primary-btn" type="submit">Create KejaPad account</button></form>
                                <div class="auth-links"><a href="{{ route('login') }}">Already have an account?</a>
                    <a href="{{ route('dashboard') }}">Back to dashboard</a></div></section></main>
                    <div class="toast">
    <strong>KejaPad updated</strong>
    <span>Action completed.</span>
</div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>