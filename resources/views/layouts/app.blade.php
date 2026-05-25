<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'KejaPad')</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="@yield('body_class')">
  <div class="shell">
    <header class="topbar">
      <a class="brand" href="{{ route('dashboard') }}" aria-label="KejaPad home">
        <div class="mark">K</div>
        <div><strong>KejaPad</strong><span>{{ auth()->user()->role === 'landlord' ? 'Landlord command center' : 'Tenant command center' }}</span></div>
      </a>
      <nav class="nav" aria-label="Workspace">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('repairs') ? 'active' : '' }}" href="{{ route('repairs') }}">Repairs</a>
        <a class="{{ request()->routeIs('rent-pot') ? 'active' : '' }}" href="{{ route('rent-pot') }}">Rent Pot</a>
        <a class="{{ request()->routeIs('building-vibe') ? 'active' : '' }}" href="{{ route('building-vibe') }}">Building Vibe</a>
        <a class="{{ request()->routeIs('escrow') ? 'active' : '' }}" href="{{ route('escrow') }}">Escrow</a>
      </nav>
      <div class="actions">
        <button class="icon-btn" data-theme-toggle type="button" aria-label="Toggle theme">Moon</button>
        <span class="ghost-btn">{{ auth()->user()->name }}</span>
        <a class="primary-btn" href="{{ route('logout.confirm') }}">Logout</a>
      </div>
    </header>

    @if (session('status'))
      <div class="flash">{{ session('status') }}</div>
    @endif

    @yield('content')
  </div>

  <div class="toast"><strong>KejaPad updated</strong><span>Action completed.</span></div>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
