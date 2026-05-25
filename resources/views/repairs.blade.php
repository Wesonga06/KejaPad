<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>KejaPad Repairs</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body class="repairs-page">
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="{{ route('dashboard') }}">
                <div class="mark">K</div>
                <div><strong>KejaPad</strong><span>Rent peace, receipts included</span></div></a>
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
        <div><div class="eyebrow">Uber-style repair tracker</div>
        <h1>Repairs without silence.</h1>
        <p class="lead">Tenants see every step of a complaint instead of staring at a vague pending status. Landlords get a cleaner audit trail and contractor accountability.</p>
    </div>
    <div class="trust-strip">
        <div><strong>04</strong><span>Live stages</span></div>
        <div><strong>12m</strong><span>Arrival time</span></div>
        <div><strong>5.0</strong><span>Rating</span></div>
    </div></section>
    <section class="page-panel">
        <div class="panel-head">
            <div><h2>Kitchen sink leak</h2>
                <p>Ticket KP-2048 - Unit B12</p></div>
                <div class="status-pill">Plumber dispatched</div></div>
                <div class="content-grid"><article class="module wide">
                    <div class="progress-track"><div class="step active"><strong>Complaint received</strong><span>Tenant submitted photos at 08:12.</span></div>
                    <div class="step active"><strong>Landlord approved</strong><span>Repair budget confirmed at 08:36.</span></div>
                    <div class="step active"><strong>Plumber dispatched</strong><span>Juma Maintenance is on the way.</span></div>
                    <div class="step"><strong>Job completed</strong><span>Tenant signoff pending.</span></div></div>
                    <div class="contractor">
                        <div class="avatar-stack">
                            <div class="avatar">JM</div>
                            <div><strong>Juma Maintenance</strong><p>Licensed plumber - 12 minutes away</p></div></div>
                            <div class="stars">★★★★★</div></div></article>
                            <article class="module"><div class="module-head">
                                <div><h3>Tenant evidence</h3><p>Photos, timestamps, and notes.</p></div></div>
                                <div class="list"><div class="item"><b>3 photos attached</b><span>08:12</span></div>
                                <div class="item"><b>Water shutoff advised</b><span>08:18</span></div></div></article>
                                <article class="module"><div class="module-head">
                                    <div><h3>Actions</h3><p>Keep both sides informed.</p></div></div>
                                    <div class="list"><button class="mini-btn good" data-toast="Contractor rated 5 stars.">Rate contractor</button>
                                        <button class="mini-btn" data-toast="Repair timeline exported.">Export timeline</button></div></article></div></section></main></div>
                                        <div class="toast"><strong>KejaPad updated</strong><span>Action completed.</span></div>
                            <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>