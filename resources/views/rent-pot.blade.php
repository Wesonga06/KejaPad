<!doctype html>
<html lang="en" data-theme="dark">
<head><meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>KejaPad Rent Pot</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="rent-page">
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="{{ route('dashboard') }}">
                <div class="mark">K</div>
                <div><strong>KejaPad</strong><span>Rent peace, receipts included</span></div>
            </a>
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
                <div>
                    <div class="eyebrow">Chama-style shared rent pots</div>
                    <h1>Split rent without drama.</h1>
                    <p class="lead">Roommates pay their own share into one smart rent pot. KejaPad shows who has paid and protects responsible tenants from another person's late payment.</p>
                </div>
                <div class="trust-strip">
                    <div><strong>75%</strong><span>Funded</span></div>
                    <div><strong>3/4</strong><span>Paid</span></div>
                    <div><strong>0</strong><span>Group penalties</span></div>
                </div>
            </section>

            <section class="page-panel">
                <div class="panel-head">
                    <div>
                        <h2>May rent pot</h2>
                        <p>Total rent: KSh 96,000 - Due in 5 days</p>
                    </div>
                    <div class="status-pill">KSh 72,000 ready</div>
                </div>

                <div class="content-grid">
                    <article class="module wide">
                        <div class="list">
                            <div class="roommate">
                                <strong>Amina</strong>
                                <div class="bar"><span style="width:100%"></span></div>
                                <small>Paid</small>
                            </div>
                            <div class="roommate">
                                <strong>Brian</strong>
                                <div class="bar"><span style("width:100%"></span></div>
                                <small>Paid</small>
                            </div>
                            <div class="roommate">
                                <strong>Chebet</strong>
                                <div class="bar"><span style("width:100%"></span></div>
                                <small>Paid</small>
                            </div>
                            <div class="roommate">
                                <strong>David</strong>
                                <div class="bar"><span style("width:18%"></span></div>
                                <small>Due</small>
                            </div>
                        </div>
                    </article>

                    <article class "module">
                        < divclass "module-head">
                           <div><h3>Smart reminders</h3><p>Private nudges, not public shame.</p></ div >
                        </ div >
                        <
script src="{{ asset('js/app.js') }}"></script >
</body >
</html >