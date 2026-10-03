<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KejaPad - {{ $title ?? 'Dashboard' }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand h4 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 1.4rem;
        }

        .sidebar-brand span {
            color: #4cc9f0;
        }

        .sidebar-brand small {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            display: block;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 15px 0;
        }

        .nav-section-title {
            color: rgba(255,255,255,0.4);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 20px 5px;
        }

        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left-color: #4cc9f0;
        }

        .sidebar-nav .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Topbar */
        .topbar {
            background: #fff;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .topbar .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }

        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4cc9f0, #0f3460);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .topbar .user-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #1a1a2e;
        }

        .topbar .user-role {
            font-size: 0.75rem;
            color: #6c757d;
        }

        /* Page Content */
        .page-content {
            padding: 25px;
        }

        /* Cards */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 8px 0 2px;
        }

        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }

        /* Sidebar Toggle Button */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: #1a1a2e;
            cursor: pointer;
        }

        /* Mobile Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                left: -260px;
            }

            .sidebar.open {
                left: 0;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }
        }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        /* Badge role colors */
        .badge-super_admin { background-color: #dc3545; }
        .badge-landlord { background-color: #0f3460; }
        .badge-caretaker { background-color: #198754; }
        .badge-tenant { background-color: #4cc9f0; color: #000; }

                /* Dark Mode */
        body.dark-mode {
            background-color: #0f0f1a;
        }

        body.dark-mode .main-content {
            background-color: #0f0f1a;
        }

        body.dark-mode .topbar {
            background: #1a1a2e;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        body.dark-mode .topbar .page-title {
            color: #fff;
        }

        body.dark-mode .topbar .user-name {
            color: #fff;
        }

        body.dark-mode .topbar .user-role {
            color: rgba(255,255,255,0.5);
        }

        body.dark-mode .stat-card {
            background: #1e1e2e;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        body.dark-mode .stat-card .stat-value {
            color: #fff;
        }

        body.dark-mode .stat-card .stat-label {
            color: rgba(255,255,255,0.5);
        }

        body.dark-mode .stat-card h6,
        body.dark-mode .stat-card h5 {
            color: #fff;
        }

        body.dark-mode .table {
            color: #ccc;
        }

        body.dark-mode .table-light th {
            background: #2a2a3e;
            color: #ccc;
            border-color: rgba(255,255,255,0.06);
        }

        body.dark-mode .table-hover tbody tr:hover {
            background: rgba(255,255,255,0.04);
            color: #fff;
        }

        body.dark-mode .table td,
        body.dark-mode .table th {
            border-color: rgba(255,255,255,0.06);
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: #2a2a3e;
            border-color: rgba(255,255,255,0.1);
            color: #fff;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background: #2a2a3e;
            border-color: #4cc9f0;
            color: #fff;
        }

        body.dark-mode .form-control::placeholder {
            color: rgba(255,255,255,0.3);
        }

        body.dark-mode .input-group-text {
            background: #2a2a3e;
            border-color: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
        }

        body.dark-mode .alert-light {
            background: #2a2a3e;
            border-color: rgba(255,255,255,0.08);
            color: #ccc;
        }

        body.dark-mode .alert-info {
            background: rgba(76,201,240,0.1);
            border-color: rgba(76,201,240,0.2);
            color: #4cc9f0;
        }

        body.dark-mode .alert-warning {
            background: rgba(253,126,20,0.1);
            border-color: rgba(253,126,20,0.2);
            color: #fd7e14;
        }

        body.dark-mode .alert-success {
            background: rgba(25,135,84,0.1);
            border-color: rgba(25,135,84,0.2);
            color: #4cff9f;
        }

        body.dark-mode .alert-danger {
            background: rgba(220,53,69,0.1);
            border-color: rgba(220,53,69,0.2);
            color: #ff6b7a;
        }

        body.dark-mode .border-bottom {
            border-color: rgba(255,255,255,0.08) !important;
        }

        body.dark-mode .border-top {
            border-color: rgba(255,255,255,0.08) !important;
        }

        body.dark-mode .breadcrumb {
            background: transparent;
        }

        body.dark-mode .breadcrumb-item a {
            color: #4cc9f0;
        }

        body.dark-mode .breadcrumb-item.active {
            color: rgba(255,255,255,0.5);
        }

        body.dark-mode .text-muted {
            color: rgba(255,255,255,0.4) !important;
        }

        body.dark-mode .text-dark {
            color: #fff !important;
        }

        body.dark-mode .bg-light {
            background: #2a2a3e !important;
        }

        body.dark-mode .badge.bg-light {
            background: #2a2a3e !important;
            color: #ccc !important;
        }

        body.dark-mode .btn-outline-secondary {
            color: #aaa;
            border-color: rgba(255,255,255,0.2);
        }

        body.dark-mode .btn-outline-secondary:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        body.dark-mode .btn-outline-primary {
            color: #4cc9f0;
            border-color: #4cc9f0;
        }

        body.dark-mode .btn-outline-danger {
            color: #ff6b7a;
            border-color: #ff6b7a;
        }

        body.dark-mode .btn-outline-warning {
            color: #fd7e14;
            border-color: #fd7e14;
        }

        body.dark-mode .btn-outline-success {
            color: #4cff9f;
            border-color: #4cff9f;
        }

        body.dark-mode .btn-outline-info {
            color: #4cc9f0;
            border-color: #4cc9f0;
        }

        body.dark-mode .page-content h5,
        body.dark-mode .page-content h6,
        body.dark-mode .page-content p:not(.text-muted),
        body.dark-mode .page-content span:not(.badge):not(.text-muted) {
            color: inherit;
        }

        body.dark-mode .nav-section-title {
            color: rgba(255,255,255,0.25);
        }

        /* Theme toggle button */
        .theme-toggle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1.5px solid #e9ecef;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            color: #6c757d;
            transition: all 0.3s;
        }

        .theme-toggle:hover {
            background: #f0f2f5;
            color: #0f3460;
        }

        body.dark-mode .theme-toggle {
            border-color: rgba(255,255,255,0.15);
            color: #4cc9f0;
        }

        body.dark-mode .theme-toggle:hover {
            background: rgba(255,255,255,0.08);
        }
    </style>

    @stack('styles')
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h4><span>Keja</span>Pad</h4>
        <small>Property Management</small>
    </div>

    <nav class="sidebar-nav">
        @yield('sidebar-links')
    </nav>

    <!-- Logout at bottom -->
    <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link w-100 text-start" style="background:none; border:none; color:rgba(255,255,255,0.7); padding: 10px 0; display:flex; align-items:center; gap:10px;">
                <i class="bi bi-box-arrow-left"></i> Logout
            </button>
        </form>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="page-title">@yield('page-title', 'Dashboard')</h5>
        </div>

        <div class="user-info">
            <!-- Theme Toggle -->
            <button class="theme-toggle" id="themeToggle" title="Toggle dark/light mode">
                <i class="bi bi-moon-fill" id="themeIcon"></i>
            </button>

            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="d-none d-md-block">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const body        = document.body;

    // Load saved theme
    const savedTheme = localStorage.getItem('kejapad-theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        themeIcon.className = 'bi bi-sun-fill';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');

        if (body.classList.contains('dark-mode')) {
            themeIcon.className = 'bi bi-sun-fill';
            localStorage.setItem('kejapad-theme', 'dark');
        } else {
            themeIcon.className = 'bi bi-moon-fill';
            localStorage.setItem('kejapad-theme', 'light');
        }
    });
</script>

@stack('scripts')
</body>
</html>