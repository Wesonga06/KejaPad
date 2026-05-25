<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KejaPad Sign Up</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <main class="auth-layout">
    <section class="auth-card">
      <a class="brand" href="{{ route('login') }}">
        <div class="mark">K</div>
        <div><strong>KejaPad</strong><span>Create your rental workspace</span></div>
      </a>
      <div class="eyebrow" style="margin-top:24px">Tenant or landlord onboarding</div>
      <h1>Create account.</h1>
      <p class="lead">Start with a verified profile, role selection, and secure access setup for your KejaPad workspace.</p>

      @if ($errors->any())
        <div class="flash error">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('signup.store') }}">
        @csrf
        <div class="role-tabs">
          <label><input type="radio" name="role" value="tenant" checked> Tenant</label>
          <label><input type="radio" name="role" value="landlord"> Landlord</label>
        </div>
        <div class="field"><label for="name">Full name</label><div class="input-wrap"><span>ID</span><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required></div></div>
        <div class="field"><label for="email">Email address</label><div class="input-wrap"><span>@</span><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div></div>
        <div class="field"><label for="phone">Phone number</label><div class="input-wrap"><span>+254</span><input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"></div></div>
        <div class="field"><label for="password">Create password</label><div class="input-wrap"><span>*</span><input id="password" name="password" type="password" autocomplete="new-password" required></div></div>
        <div class="field"><label for="password_confirmation">Confirm password</label><div class="input-wrap"><span>*</span><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div></div>
        <div class="secure-row"><div><b>KYC</b> Ready</div><div><b>OTP</b> Enabled</div><div><b>Vault</b> Secure</div></div>
        <button class="primary-btn" type="submit">Create KejaPad account</button>
      </form>
      <div class="auth-links"><a href="{{ route('login') }}">Already have an account?</a></div>
    </section>
  </main>
  <div class="toast"><strong>KejaPad updated</strong><span>Action completed.</span></div>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>