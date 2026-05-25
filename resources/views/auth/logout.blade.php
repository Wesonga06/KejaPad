<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KejaPad Logout</title>

  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
  <main class="auth-layout">
    <section class="auth-card">
      <a class="brand" href="{{ route('dashboard') }}">
        <div class="mark">K</div>
        <div>
          <strong>KejaPad</strong>
          <span>Secure sign out</span>
        </div>
      </a>

      <div class="eyebrow" style="margin-top: 24px;">Session control</div>

      <h1>Ready to logout?</h1>

      <p class="lead">
        This will clear your current KejaPad session and return you to the login screen.
      </p>

      <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button class="primary-btn" type="submit">
          Logout securely
        </button>
      </form>

      <div class="auth-links">
        <a href="{{ route('dashboard') }}">Stay on dashboard</a>
        <a href="{{ route('login') }}">Go to login</a>
      </div>
    </section>
  </main>

  <div class="toast">
    <strong>KejaPad updated</strong>
    <span>Action completed.</span>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>