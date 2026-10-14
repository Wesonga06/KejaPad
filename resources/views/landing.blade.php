<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KejaPad — Property Management System</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 20px rgba(0,0,0,0.08);
            padding: 12px 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: #32145C !important;
        }

        .navbar-brand span {
            color: #b7791f;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #32145C 0%, #2A114E 40%, #5926C8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(244,197,66,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(244,197,66,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(244,197,66,0.15);
            color: #F4C542;
            border: 1px solid rgba(244,197,66,0.3);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }

        .hero h1 {
            font-size: 3.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .hero h1 span {
            color: #F4C542;
        }

        .hero p {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .hero-btns {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: #F4C542;
            color: #32145C;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-hero-primary:hover {
            background: #fff;
            color: #5926C8;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(244,197,66,0.3);
        }

        .btn-hero-outline {
            background: transparent;
            color: #fff;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-hero-outline:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-color: rgba(255,255,255,0.6);
        }

        /* Hero Dashboard Card */
        .hero-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }

        .mini-stat {
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .mini-stat h4 {
            color: #F4C542;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .mini-stat small {
            color: rgba(255,255,255,0.6);
            font-size: 0.78rem;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: #f8f9ff;
        }

        .section-badge {
            display: inline-block;
            background: rgba(89,38,200,0.08);
            color: #5926C8;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #32145C;
            margin-bottom: 12px;
        }

        .section-subtitle {
            color: #6c757d;
            font-size: 1rem;
            max-width: 500px;
        }

        .feature-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #f0f0f0;
            transition: all 0.3s;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            border-color: rgba(89,38,200,0.08);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 18px;
        }

        .feature-card h5 {
            font-weight: 700;
            color: #32145C;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #6c757d;
            font-size: 0.88rem;
            line-height: 1.6;
            margin: 0;
        }

        /* Roles Section */
        .roles-section {
            padding: 100px 0;
            background: #fff;
        }

        .role-card {
            border-radius: 20px;
            padding: 35px;
            color: white;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }

        .role-card h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .role-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .role-card ul li {
            padding: 5px 0;
            font-size: 0.87rem;
            opacity: 0.85;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-card ul li::before {
            content: '✓';
            font-weight: 700;
            opacity: 1;
            flex-shrink: 0;
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background: #f8f9ff;
        }

        .step-card {
            text-align: center;
            padding: 20px;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #5926C8, #F4C542);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 800;
            margin: 0 auto 20px;
        }

        .step-card h5 {
            font-weight: 700;
            color: #32145C;
            margin-bottom: 10px;
        }

        .step-card p {
            color: #6c757d;
            font-size: 0.88rem;
        }

        /* QR Section */
        .qr-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #32145C, #5926C8);
            color: white;
        }

        .qr-box {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: inline-block;
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: #fff;
        }

        .cta-box {
            background: linear-gradient(135deg, #5926C8, #F4C542);
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            color: white;
        }

        .cta-box h2 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .cta-box p {
            opacity: 0.85;
            margin-bottom: 30px;
        }

        /* Footer */
        footer {
            background: #32145C;
            color: rgba(255,255,255,0.6);
            padding: 40px 0 20px;
        }

        footer .brand {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
        }

        footer .brand span {
            color: #F4C542;
        }

        footer a {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.88rem;
        }

        footer a:hover {
            color: #F4C542;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero {
                padding: 100px 0 60px;
            }

            .section-title {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('landing') }}">
                <span>Keja</span>Pad
            </a>
            <button class="navbar-toggler border-0" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto gap-1">
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="#roles">Who It's For</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="#qrcode">QR Access</a>
                    </li>
                </ul>
                <div class="d-flex gap-2 mt-3 mt-lg-0">
                    <a href="{{ route('login') }}"
                       class="btn btn-outline-dark btn-sm px-4">
                        Login
                    </a>
                    <a href="{{ route('register') }}"
                       class="btn btn-dark btn-sm px-4">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" style="padding-top: 80px;">
        <div class="container position-relative" style="z-index:1;">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="hero-badge">
                        <i class="bi bi-buildings me-2"></i>
                        Built for Kenya's Property Market
                    </div>
                    <h1>
                        Manage Your<br>
                        Properties <span>Smarter</span><br>
                        & Faster
                    </h1>
                    <p>
                        KejaPad is the all-in-one property and tenant management
                        platform for landlords, caretakers, and tenants in Kenya.
                        Collect rent via M-Pesa, track maintenance, and manage
                        everything from one dashboard.
                    </p>
                    <div class="hero-btns">
                        <a href="{{ route('register') }}" class="btn-hero-primary">
                            <i class="bi bi-rocket-takeoff me-2"></i>
                            Get Started Free
                        </a>
                        <a href="{{ route('login') }}" class="btn-hero-outline">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In
                        </a>
                    </div>

                    <!-- Stats Row -->
                    <div class="row g-3 mt-4">
                        <div class="col-4">
                            <div class="text-white">
                                <div style="font-size:1.6rem; font-weight:800; color:#F4C542;">
                                    100%
                                </div>
                                <div style="font-size:0.78rem; opacity:0.6;">
                                    Mobile Ready
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-white">
                                <div style="font-size:1.6rem; font-weight:800; color:#F4C542;">
                                    M-Pesa
                                </div>
                                <div style="font-size:0.78rem; opacity:0.6;">
                                    Integrated
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-white">
                                <div style="font-size:1.6rem; font-weight:800; color:#F4C542;">
                                    4 Roles
                                </div>
                                <div style="font-size:0.78rem; opacity:0.6;">
                                    Supported
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Preview Card -->
                <div class="col-lg-6">
                    <div class="hero-card">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <div style="width:10px; height:10px; border-radius:50%; background:#ff5f57;"></div>
                            <div style="width:10px; height:10px; border-radius:50%; background:#febc2e;"></div>
                            <div style="width:10px; height:10px; border-radius:50%; background:#28c840;"></div>
                            <span style="color:rgba(255,255,255,0.4); font-size:0.8rem; margin-left:8px;">
                                KejaPad Dashboard
                            </span>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="mini-stat">
                                    <h4>24</h4>
                                    <small>Total Units</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <h4>18</h4>
                                    <small>Occupied</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <h4>KES 540K</h4>
                                    <small>Rent Collected</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <h4>KES 60K</h4>
                                    <small>Outstanding</small>
                                </div>
                            </div>
                        </div>

                        <!-- Mini progress bar -->
                        <div style="background:rgba(255,255,255,0.1); border-radius:8px; padding:15px;">
                            <div class="d-flex justify-content-between mb-2">
                                <span style="color:rgba(255,255,255,0.7); font-size:0.82rem;">
                                    Occupancy Rate
                                </span>
                                <span style="color:#F4C542; font-weight:600; font-size:0.82rem;">
                                    75%
                                </span>
                            </div>
                            <div style="background:rgba(255,255,255,0.1); border-radius:10px; height:8px;">
                                <div style="background:linear-gradient(90deg,#F4C542,#5926C8); width:75%; height:100%; border-radius:10px;"></div>
                            </div>
                        </div>

                        <!-- Recent activity -->
                        <div class="mt-3">
                            @foreach([
                                ['John Kamau', 'Paid KES 15,000', 'success'],
                                ['Mary Wanjiku', 'Maintenance Request', 'warning'],
                                ['Peter Odhiambo', 'Payment Pending', 'secondary'],
                            ] as $item)
                            <div class="d-flex align-items-center justify-content-between py-2"
                                 style="border-bottom:1px solid rgba(255,255,255,0.06);">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:28px; height:28px; border-radius:50%; background:rgba(244,197,66,0.2); display:flex; align-items:center; justify-content:center; color:#F4C542; font-size:0.7rem; font-weight:700;">
                                        {{ substr($item[0], 0, 1) }}
                                    </div>
                                    <span style="color:rgba(255,255,255,0.8); font-size:0.82rem;">
                                        {{ $item[0] }}
                                    </span>
                                </div>
                                <span class="badge bg-{{ $item[2] }}" style="font-size:0.72rem;">
                                    {{ $item[1] }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-badge">Features</div>
                <h2 class="section-title">Everything You Need</h2>
                <p class="section-subtitle mx-auto">
                    A complete suite of tools to manage your properties
                    professionally from anywhere.
                </p>
            </div>

            <div class="row g-4">
                @php
                    $features = [
                        ['bi-buildings', 'rgba(89,38,200,0.08)', '#5926C8', 'Property Management', 'Add and manage multiple properties with photos, locations, and status tracking.'],
                        ['bi-door-open', 'rgba(22,134,83,0.12)', '#168653', 'Unit Management', 'Create units, set rent amounts, and track vacancy status in real time.'],
                        ['bi-people', 'rgba(233,155,32,0.14)', '#E99B20', 'Tenant Management', 'Add tenants, manage profiles, emergency contacts, and tenancy history.'],
                        ['bi-phone', 'rgba(89,38,200,0.08)', '#5926C8', 'M-Pesa Payments', 'Collect rent via Safaricom M-Pesa STK Push with automatic confirmation.'],
                        ['bi-tools', 'rgba(244,197,66,0.18)', '#b7791f', 'Maintenance Requests', 'Tenants report issues, caretakers get assigned, landlords track progress.'],
                        ['bi-megaphone', 'rgba(233,155,32,0.14)', '#E99B20', 'Announcements', 'Post water, electricity, security, and maintenance notices instantly.'],
                        ['bi-bar-chart', 'rgba(22,134,83,0.12)', '#168653', 'Reports & Analytics', 'Export rent collection, occupancy, and maintenance reports to PDF or Excel.'],
                        ['bi-bell', 'rgba(89,38,200,0.08)', '#5926C8', 'SMS & Email Alerts', 'Automated reminders for rent due, overdue payments, and complaint updates.'],
                    ];
                @endphp

                @foreach($features as $f)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="feature-card">
                        <div class="feature-icon"
                             style="background:{{ $f[1] }};">
                            <i class="bi {{ $f[0] }}"
                               style="color:{{ $f[2] }};"></i>
                        </div>
                        <h5>{{ $f[3] }}</h5>
                        <p>{{ $f[4] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Roles Section -->
    <section class="roles-section" id="roles">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-badge">Who It's For</div>
                <h2 class="section-title">Built for Everyone</h2>
                <p class="section-subtitle mx-auto">
                    KejaPad serves four key roles, each with their own
                    tailored dashboard and features.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="role-card"
                         style="background:linear-gradient(135deg,#DC4545,#c82333);">
                        <div class="mb-3">
                            <i class="bi bi-shield-check" style="font-size:2rem;"></i>
                        </div>
                        <h5>Super Admin</h5>
                        <ul>
                            <li>Manage all users</li>
                            <li>System configuration</li>
                            <li>Payment settings</li>
                            <li>SMS settings</li>
                            <li>Full system reports</li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="role-card"
                         style="background:linear-gradient(135deg,#5926C8,#4a1ea8);">
                        <div class="mb-3">
                            <i class="bi bi-person-badge" style="font-size:2rem;"></i>
                        </div>
                        <h5>Landlord</h5>
                        <ul>
                            <li>Manage properties</li>
                            <li>Add & manage tenants</li>
                            <li>Track rent payments</li>
                            <li>Assign caretakers</li>
                            <li>Post announcements</li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="role-card"
                         style="background:linear-gradient(135deg,#168653,#157347);">
                        <div class="mb-3">
                            <i class="bi bi-person-gear" style="font-size:2rem;"></i>
                        </div>
                        <h5>Caretaker</h5>
                        <ul>
                            <li>View assigned tasks</li>
                            <li>Update job progress</li>
                            <li>Mark issues resolved</li>
                            <li>View announcements</li>
                            <li>Track completed jobs</li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="role-card"
                         style="background:linear-gradient(135deg,#F4C542,#5926C8);">
                        <div class="mb-3">
                            <i class="bi bi-house-heart" style="font-size:2rem;"></i>
                        </div>
                        <h5>Tenant</h5>
                        <ul>
                            <li>View rent balance</li>
                            <li>Pay via M-Pesa</li>
                            <li>Payment history</li>
                            <li>Report maintenance</li>
                            <li>View announcements</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-badge">How It Works</div>
                <h2 class="section-title">Get Started in Minutes</h2>
                <p class="section-subtitle mx-auto">
                    Set up your entire property management system in four simple steps.
                </p>
            </div>

            <div class="row g-4">
                @php
                    $steps = [
                        ['1', 'Create Account', 'Register as a landlord and set up your KejaPad account in seconds.'],
                        ['2', 'Add Properties', 'Add your properties, create units, and set rent amounts for each unit.'],
                        ['3', 'Add Tenants', 'Register your tenants, assign them to units, and give them portal access.'],
                        ['4', 'Collect Rent', 'Generate monthly rent records and collect payments via M-Pesa automatically.'],
                    ];
                @endphp

                @foreach($steps as $step)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">{{ $step[0] }}</div>
                        <h5>{{ $step[1] }}</h5>
                        <p>{{ $step[2] }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- M-Pesa Flow -->
            <div class="mt-5 p-4 rounded-4 text-center"
                 style="background:#fff; border: 1px solid rgba(89,38,200,0.08);">
                <h6 class="fw-bold mb-4" style="color:#5926C8;">
                    <i class="bi bi-phone me-2"></i>M-Pesa Payment Flow
                </h6>
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">
                    @php
                        $flow = [
                            ['bi-cursor-fill', 'Tenant clicks Pay Rent'],
                            ['bi-arrow-right', ''],
                            ['bi-phone', 'STK Push sent'],
                            ['bi-arrow-right', ''],
                            ['bi-lock', 'Tenant enters PIN'],
                            ['bi-arrow-right', ''],
                            ['bi-check-circle', 'Payment confirmed'],
                            ['bi-arrow-right', ''],
                            ['bi-receipt', 'Receipt generated'],
                        ];
                    @endphp

                    @foreach($flow as $f)
                        @if($f[1] === '')
                            <i class="bi {{ $f[0] }} text-muted d-none d-md-block"></i>
                        @else
                            <div class="text-center">
                                <div style="width:48px; height:48px; background:rgba(89,38,200,0.08); border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-size:1.2rem; color:#5926C8;">
                                    <i class="bi {{ $f[0] }}"></i>
                                </div>
                                <small style="color:#555; font-size:0.75rem;">
                                    {{ $f[1] }}
                                </small>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- QR Code Section -->
    <section class="qr-section" id="qrcode">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 text-center text-lg-start">
                    <div class="section-badge"
                         style="background:rgba(244,197,66,0.15);
                                color:#F4C542; border:1px solid rgba(244,197,66,0.3);">
                        Quick Access
                    </div>
                    <h2 style="font-size:2.2rem; font-weight:800; color:#fff; margin-bottom:15px;">
                        Access KejaPad<br>Anywhere
                    </h2>
                    <p style="color:rgba(255,255,255,0.7); margin-bottom:25px;">
                        Scan the QR code with your phone camera to instantly
                        access the KejaPad login and registration pages.
                        Share it with your tenants for easy onboarding.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="{{ route('login') }}" class="btn-hero-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Login
                        </a>
                        <a href="{{ route('register') }}" class="btn-hero-outline">
                            <i class="bi bi-person-plus me-2"></i> Register
                        </a>
                        <a href="{{ route('qrcode') }}"
                           class="btn-hero-outline" target="_blank">
                            <i class="bi bi-qr-code me-2"></i> Full QR Page
                        </a>
                    </div>
                </div>

                <div class="col-lg-6 text-center">
                    <div class="qr-box d-inline-block">
                        {!! QrCode::format('svg')
                            ->size(200)
                            ->color(15, 52, 96)
                            ->margin(1)
                            ->generate(config('app.url')) !!}
                        <p class="mt-3 mb-0"
                           style="color:#5926C8; font-weight:600; font-size:0.88rem;">
                            <i class="bi bi-qr-code me-1"></i> Scan to access KejaPad
                        </p>
                        <p style="color:#6c757d; font-size:0.78rem; margin-top:4px;">
                            kejapad.co.ke
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-box">
                <h2>Ready to Simplify Property Management?</h2>
                <p>
                    Join landlords across Kenya who use KejaPad to manage
                    their properties, collect rent, and keep tenants happy.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="{{ route('register') }}"
                       class="btn btn-light fw-600 px-5 py-3 rounded-3"
                       style="font-weight:600; color:#5926C8;">
                        <i class="bi bi-rocket-takeoff me-2"></i>
                        Get Started Free
                    </a>
                    <a href="{{ route('login') }}"
                       class="btn btn-outline-light fw-600 px-5 py-3 rounded-3"
                       style="font-weight:600;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="brand mb-3">
                        <span>Keja</span>Pad
                    </div>
                    <p style="font-size:0.85rem; color:rgba(255,255,255,0.5);">
                        Kenya's modern property and tenant management platform.
                        Built for landlords, caretakers, and tenants.
                    </p>
                </div>
                <div class="col-md-2">
                    <h6 class="text-white fw-600 mb-3">Platform</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features">Features</a></li>
                        <li class="mb-2"><a href="#roles">Roles</a></li>
                        <li class="mb-2"><a href="#how-it-works">How It Works</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6 class="text-white fw-600 mb-3">Account</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('register') }}">Register</a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('qrcode') }}">QR Code</a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white fw-600 mb-3">Contact</h6>
                    <p style="font-size:0.85rem;">
                        <i class="bi bi-envelope me-2"></i>
                        info@kejapad.co.ke
                    </p>
                    <p style="font-size:0.85rem;">
                        <i class="bi bi-geo-alt me-2"></i>
                        Nairobi, Kenya
                    </p>
                </div>
            </div>
            <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center gap-2"
                 style="border-color:rgba(255,255,255,0.1) !important;">
                <small>© {{ date('Y') }} KejaPad. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Navbar shadow on scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.12)';
            } else {
                navbar.style.boxShadow = '0 1px 20px rgba(0,0,0,0.08)';
            }
        });
    </script>
</body>
</html>