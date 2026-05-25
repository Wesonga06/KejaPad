<!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>KejaPad Dashboard</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="dashboard-page">
  <div class="shell">
    <header class="topbar">
      <nav class="nav" aria-label="Workspace">
  <a href="{{ route('dashboard') }}">Dashboard</a>
  <a href="{{ route('repairs') }}">Repairs</a>
  <a href="{{ route('rent-pot') }}">Rent Pot</a>
  <a href="{{ route('building-vibe') }}">Building Vibe</a>
  <a href="{{ route('escrow') }}">Escrow</a>
</nav>

<div class="actions">
  <button class="icon-btn" data-theme-toggle type="button" aria-label="Toggle theme">
    Moon
  </button>

  <a class="ghost-btn" href="{{ route('login') }}">Login</a>
  <a class="primary-btn" href="{{ route('signup') }}">Sign up</a>
  <a class="ghost-btn" href="{{ route('logout') }}">Logout</a>
</div>
    </header>

    <main class="page">
      <section class="hero-copy">
        <div><div class="eyebrow">Landlord and tenant command center</div><h1>KejaPad</h1><p class="lead">A premium rental workspace where rent, repairs, disputes, roommates, and building feedback are handled with receipts, fairness, and less shouting.</p></div>
        <div class="trust-strip"><div><strong>842</strong><span>Rent score</span></div><div><strong>8m</strong><span>Avg response</span></div><div><strong>4.8</strong><span>Service rating</span></div></div>
      </section>

      <section class="page-panel">
        <div class="panel-head"><div><h2>Tenant cockpit</h2><p>House B12, Kilimani Heights - May rent cycle</p></div><div class="status-pill" data-user-badge>Guest mode</div></div>
        <div class="content-grid">
          <article class="module wide"><div class="module-head"><div><h3>Good Tenant Score</h3><p>On-time rent becomes visible rental reputation.</p></div><div class="status-pill">+18 this month</div></div><div class="score-wrap"><div class="score-ring"><div><strong>842</strong><span>Excellent</span></div></div><div class="list"><div class="item"><b>Free carpet cleaning</b><span>Unlocked</span></div><div class="item"><b>Priority parking</b><span>32 pts away</span></div><div class="item"><b>Certified reference</b><span>Ready</span></div></div></div></article>
          <article class="module"><div class="module-head"><div><h3>Next rent</h3><p>Due in 5 days</p></div><div class="status-pill">75% funded</div></div><div class="list"><div class="item"><b>KSh 72,000 collected</b><span>of 96,000</span></div><div class="item"><b>One roommate pending</b><span>No house penalty</span></div></div></article>
          <article class="module"><div class="module-head"><div><h3>Open repair</h3><p>Kitchen sink leak</p></div><div class="status-pill">Dispatched</div></div><div class="list"><div class="item"><b>Juma Maintenance</b><span>12 min away</span></div><div class="item"><b>Contractor rating</b><span>5 stars</span></div></div></article>
        </div>
      </section>
    </main>
  </div>
  <div class="toast"><strong>KejaPad updated</strong><span>Action completed.</span></div>
  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>