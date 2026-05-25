<!doctype html>
<html lang="en" data-theme="dark">
<head><meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>KejaPad Building Vibe</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body class="vibe-page">
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="index.html">
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
                    <div>
                        <div class="eyebrow">Anonymous community feedback</div>
                        <h1>Catch problems before fights.</h1>
                        <p class="lead">Tenants can quietly flag building-wide issues. When three or more tenants report the same thing, the landlord gets a high-priority alert with pattern evidence.</p>
                    </div>
                    <div class="trust-strip">
                        <div><strong>4</strong><span>Water flags</span></div>
                        <div><strong>3+</strong><span>Alert trigger</span></div>
                        <div><strong>Anon</strong><span>Protected</span></div>
                    </div>
                </section>

                <section class="page-panel">
                    <div class="panel-head">
                        <div>
                            <h2>Building Vibe</h2>
                            <p>Kilimani Heights - anonymous tenant pulse</p>
                        </div>
                        <div class="status-pill">1 urgent alert</div>
                    </div>

                    <div class="content-grid">
                        <article class="module wide">
                            <div class="vibe-grid">
                                <div class="vibe alert">
                                    <strong>Low water pressure</strong>
                                    <span>4 tenants flagged - urgent landlord alert sent</span>
                                </div>
                                <div class="vibe">
                                    <strong>Gate left open</strong>
                                    <span>2 tenants flagged - monitoring</span>
                                </div>
                                <div class="vibe">
                                    <strong>Generator noise</strong>
                                    <span>1 tenant flagged - no pattern yet</span>
                                </div>
                            </div>
                        </article>

                        <article class="module">
                            <div class="module-head">
                                <div><h3>Flag an issue</h3><p>Submit anonymously.</p></div></div>< divclass "list"><button class "mini-btn good" data-toast "Anonymous water-pressure flag submitted.">Low water pressure</button><button class "mini-btn" data-toast "Anonymous security flag submitted.">Security concern</button></ div ></ article >< articleclass "module">< divclass "module-head">< div >< h3 >Landlord alert< /h3 >< p >Only patterns become urgent.< /p ></ div ></ div >< divclass "item">< b >Water pressure< /b >< span >High priority< /span ></ div ></ article ></ div ></ section ></ main ></ div >< divclass "toast">< strong >KejaPad updated< /strong >< span >Action completed.< /span ></ div >
<script src="{{ asset('js/app.js') }}"></script >
</body >
</html >