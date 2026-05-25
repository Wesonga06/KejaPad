<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>KejaPad Escrow</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body class="escrow-page">
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
                    <div class="eyebrow">AI rent escrow for disputes</div>
                    <h1>A safer middle ground.</h1>
                    <p class="lead">When a serious repair is ignored, tenants can prove they have rent ready while funds are held safely until resolution, signoff, or mediation.</p>
                </div>
                <div class="trust-strip">
                    <div><strong>KSh</strong><span>Verified funds</span></div>
                    <div><strong>Audit</strong><span>Timeline</span></div>
                    <div><strong>Hold</strong><span>Until resolved</span></div>
                </div>
            </section>

            <section class="page-panel">
                <div class="panel-head">
                    <div>
                        <h2>Ceiling leak dispute</h2>
                        <p>Escrow case KP-E440 - 21 days unresolved</p>
                    </div>
                    <div class="status-pill">Legal trail active</div>
                </div>

                <div class "content-grid">
                    <articleclass "module wide">
                        < divclass "wallet">
                            < span >Escrow balance for unresolved ceiling leak< /span >
                            "< strong >KSh 48,000< /strong >
                            "< p >Funds are verified, time-stamped, and held until tenant signoff or mediation outcome.< /p >
                        </ div >
                    </ article >
                    "< articleclass "module">< divclass "module-head">< div >< h3 >Tenant actions< /h3 >< p >Resolve or escalate safely.< /p ></ div ></ div >< divclass "list">< buttonclass "mini-btn good" data-toast "Escrow release request queued with a signed resolution record.">Issue resolved - release rent< /button >< buttonclass "mini-btn warn" data-toast "Mediation packet opened with repair history and payment proof.">Request mediation< /button ></ div ></ article >< articleclass "module">< divclass "module-head">< div >< h3 >Evidence packet< /h3 >< p >Everything stays traceable.< /p ></ div ></ div >< divclass "list">< divclass "item">< b >Repair requests< /b >< span >5 entries< /span ></ div >< divclass "item">< b >Photos and notices< /b >< span >12 files< /span ></ div >< buttonclass "mini-btn" data-toast "Evidence packet exported.">Export packet< /button ></ div ></ article ></ div ></ section ></ main ></ div >< divclass "toast">< strong >KejaPad updated< /strong >< span >Action completed.< /span ></ div >
    <script src="{{ asset('js/app.js') }}"></script>
</body >
</html >