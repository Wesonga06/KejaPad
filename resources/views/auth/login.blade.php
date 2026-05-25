<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KejaPad Login</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <main class="auth-layout">
    <section class="auth-card">
      <a class="brand" href="{{ route('login') }}">
        <div class="mark">K</div>
        <div><strong>KejaPad</strong><span>Secure tenant and landlord login</span></div>
      </a>

      <div class="eyebrow" style="margin-top:24px">Over-the-top authentication</div>
      <h1>Welcome back.</h1>
      <p class="lead">Demo accounts: tenant@kejapad.test or landlord@kejapad.test. Password: password.</p>

      @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="flash error">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
          <label for="email">Verified email</label>
          <div class="input-wrap"><span>@</span><input id="email" name="email" type="email" value="{{ old('email', 'tenant@kejapad.test') }}" autocomplete="email" required autofocus></div>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap"><span>*</span><input id="password" name="password" type="password" value="password" autocomplete="current-password" required></div>
        </div>
        <label class="check-row"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
        <div class="secure-row"><div><b>2FA</b> Ready</div><div><b>ID</b> Verified</div><div><b>Audit</b> Logged</div></div>
        <button class="primary-btn" type="submit">Unlock KejaPad</button>
      </form>

      <div class="auth-links"><a href="{{ route('signup') }}">Create account</a></div>
    </section>
  </main>
  <div class="toast"><strong>KejaPad updated</strong><span>Action completed.</span></div>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>