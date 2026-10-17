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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --kp-deep: #32145C;
            --kp-royal: #5926C8;
            --kp-gold: #F4C542;
            --kp-white: #FFFFFF;
            --kp-bg: #F7F6FB;
            --kp-text: #241A33;
            --kp-muted: #777387;
            --kp-border: #EAE6F2;
            --kp-success: #168653;
            --kp-warning: #E99B20;
            --kp-danger: #DC4545;

            --kp-sidebar-w: 264px;
            --kp-radius: 12px;
            --kp-radius-sm: 10px;
            --kp-shadow: 0 4px 18px rgba(50, 20, 92, 0.06);
            --kp-shadow-hover: 0 10px 28px rgba(50, 20, 92, 0.12);
        }

        * { font-family: 'Inter', sans-serif; }

        html, body { min-height: 100%; }

        body {
            background-color: var(--kp-bg);
            color: var(--kp-text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, h6 { color: var(--kp-text); font-weight: 700; }
        a { color: var(--kp-royal); text-decoration: none; }
        a:hover { color: var(--kp-deep); }
        .text-muted { color: var(--kp-muted) !important; }

        /* ============ Sidebar ============ */
        .sidebar {
            width: var(--kp-sidebar-w);
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            background: var(--kp-deep);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transition: left 0.3s ease;
            box-shadow: 4px 0 24px rgba(50, 20, 92, 0.15);
        }

        .sidebar-brand {
            padding: 22px 20px;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand .brand-text h4 {
            color: #fff;
            font-weight: 800;
            margin: 0;
            font-size: 1.3rem;
            line-height: 1.1;
            letter-spacing: .3px;
        }

        .sidebar-brand .brand-text h4 span { color: var(--kp-gold); }

        .sidebar-brand .brand-text small {
            color: rgba(255,255,255,0.55);
            font-size: 0.68rem;
            letter-spacing: .4px;
            display: block;
            margin-top: 1px;
        }

        .sidebar-nav {
            padding: 14px 12px 24px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .nav-section-title {
            color: rgba(255,255,255,0.38);
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 16px 12px 6px;
        }

        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.72);
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: var(--kp-radius-sm);
            margin-bottom: 2px;
            transition: all 0.16s ease;
            position: relative;
        }

        .sidebar-nav .nav-link i { font-size: 1.05rem; width: 20px; text-align: center; }

        .sidebar-nav .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.07);
        }

        .sidebar-nav .nav-link.active {
            background: var(--kp-gold);
            color: var(--kp-deep);
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(244, 197, 66, 0.25);
        }

        .sidebar-nav .nav-link.active i { color: var(--kp-deep); }

        .sidebar-user {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 14px 16px;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-user .kp-avatar { background: var(--kp-gold); color: var(--kp-deep); }

        .sidebar-user .su-meta { flex: 1 1 auto; min-width: 0; }
        .sidebar-user .su-name {
            color: #fff; font-size: 0.85rem; font-weight: 600;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user .su-role { color: rgba(255,255,255,0.5); font-size: 0.72rem; }

        .sidebar-user .su-logout {
            background: rgba(255,255,255,0.06);
            border: none;
            color: rgba(255,255,255,0.75);
            width: 34px; height: 34px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            transition: all .16s ease;
        }
        .sidebar-user .su-logout:hover { background: var(--kp-danger); color: #fff; }

        .sidebar::-webkit-scrollbar,
        .sidebar-nav::-webkit-scrollbar { width: 6px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 8px; }

        /* ============ Main / Topbar ============ */
        .main-content {
            margin-left: var(--kp-sidebar-w);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--kp-white);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid var(--kp-border);
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        .topbar .page-title { font-size: 1.05rem; font-weight: 700; color: var(--kp-deep); margin: 0; }

        .topbar-search {
            flex: 1 1 auto;
            max-width: 420px;
            position: relative;
        }
        .topbar-search .bi-search {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--kp-muted); font-size: .9rem;
        }
        .topbar-search input {
            width: 100%;
            border: 1px solid var(--kp-border);
            background: var(--kp-bg);
            border-radius: 10px;
            padding: 9px 14px 9px 38px;
            font-size: 0.88rem;
            color: var(--kp-text);
            outline: none;
            transition: all .16s ease;
        }
        .topbar-search input::placeholder { color: var(--kp-muted); }
        .topbar-search input:focus {
            background: #fff;
            border-color: var(--kp-royal);
            box-shadow: 0 0 0 3px rgba(89, 38, 200, 0.12);
        }

        .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }

        .icon-btn {
            width: 40px; height: 40px;
            border-radius: 10px;
            border: 1px solid var(--kp-border);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: var(--kp-muted);
            font-size: 1.05rem;
            position: relative;
            transition: all .16s ease;
        }
        .icon-btn:hover { background: var(--kp-bg); color: var(--kp-royal); border-color: #ddd6ef; }
        .icon-btn .kp-dot {
            position: absolute; top: 6px; right: 6px;
            min-width: 16px; height: 16px; padding: 0 4px;
            background: var(--kp-danger); color: #fff;
            font-size: 0.62rem; font-weight: 700;
            border-radius: 999px; display: flex; align-items: center; justify-content: center;
        }

        .topbar-profile {
            display: flex; align-items: center; gap: 10px;
            padding: 5px 8px 5px 5px;
            border-radius: 999px;
            border: 1px solid var(--kp-border);
            background: #fff;
            cursor: pointer;
            transition: all .16s ease;
        }
        .topbar-profile:hover { background: var(--kp-bg); }
        .topbar-profile .tp-meta { line-height: 1.15; text-align: left; }
        .topbar-profile .tp-name { font-size: 0.83rem; font-weight: 600; color: var(--kp-text); }
        .topbar-profile .tp-role { font-size: 0.7rem; color: var(--kp-muted); }

        .kp-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.82rem;
            background: linear-gradient(135deg, var(--kp-royal), var(--kp-deep));
            color: #fff;
            flex-shrink: 0;
        }
        .kp-avatar.sm { width: 34px; height: 34px; font-size: 0.75rem; }

        .dropdown-menu.kp-menu {
            border: 1px solid var(--kp-border);
            border-radius: var(--kp-radius);
            box-shadow: var(--kp-shadow-hover);
            padding: 6px;
            min-width: 260px;
        }
        .kp-menu .dropdown-item {
            border-radius: 8px;
            font-size: 0.86rem;
            padding: 8px 10px;
            color: var(--kp-text);
        }
        .kp-menu .dropdown-item:hover { background: var(--kp-bg); color: var(--kp-royal); }
        .kp-menu .dropdown-item.text-danger:hover { background: rgba(220,69,69,0.08); color: var(--kp-danger); }
        .kp-menu-header {
            padding: 8px 10px 6px;
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: var(--kp-muted);
        }
        .kp-notif {
            display: flex; gap: 10px; padding: 9px 10px; border-radius: 8px;
            white-space: normal;
        }
        .kp-notif:hover { background: var(--kp-bg); }
        .kp-notif .nf-icon {
            width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(89,38,200,0.1); color: var(--kp-royal);
        }
        .kp-notif .nf-text { font-size: 0.82rem; color: var(--kp-text); line-height: 1.3; }
        .kp-notif .nf-time { font-size: 0.72rem; color: var(--kp-muted); }

        /* ============ Page content ============ */
        .page-content { padding: 26px; flex: 1 1 auto; }

        .kp-page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 22px;
        }
        .kp-page-title { font-size: 1.4rem; font-weight: 800; margin: 0 0 2px; color: var(--kp-deep); }
        .kp-page-subtitle { font-size: 0.88rem; color: var(--kp-muted); margin: 0; }
        .kp-page-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* ============ Cards ============ */
        .kp-card,
        .stat-card {
            background: var(--kp-white);
            border: 1px solid var(--kp-border);
            border-radius: var(--kp-radius);
            box-shadow: var(--kp-shadow);
            padding: 20px;
        }
        .stat-card { transition: transform .18s ease, box-shadow .18s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--kp-shadow-hover); }

        .kp-card-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 16px;
        }
        .kp-card-title { font-size: 1rem; font-weight: 700; margin: 0; color: var(--kp-deep); }
        .kp-card-subtitle { font-size: 0.8rem; color: var(--kp-muted); margin: 0; }

        /* KPI cards */
        .kp-kpi { position: relative; overflow: hidden; }
        .kp-kpi-top { display: flex; align-items: center; justify-content: space-between; }
        .kp-kpi-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .kp-kpi-icon.purple { background: rgba(89,38,200,0.1); color: var(--kp-royal); }
        .kp-kpi-icon.gold { background: rgba(244,197,66,0.22); color: #8a6a00; }
        .kp-kpi-icon.green { background: rgba(22,134,83,0.12); color: var(--kp-success); }
        .kp-kpi-icon.red { background: rgba(220,69,69,0.12); color: var(--kp-danger); }
        .kp-kpi-icon.amber { background: rgba(233,155,32,0.14); color: #b7791f; }
        .kp-kpi-value { font-size: 1.7rem; font-weight: 800; color: var(--kp-deep); margin: 14px 0 2px; line-height: 1; }
        .kp-kpi-label { font-size: 0.82rem; color: var(--kp-muted); margin: 0; font-weight: 500; }
        .kp-kpi-trend { font-size: 0.75rem; font-weight: 600; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px; }
        .kp-kpi-trend.up { color: var(--kp-success); }
        .kp-kpi-trend.down { color: var(--kp-danger); }
        .kp-kpi-trend.flat { color: var(--kp-muted); }

        /* legacy stat-icon helper */
        .stat-card .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--kp-deep); margin: 8px 0 2px; }
        .stat-card .stat-label { font-size: 0.85rem; color: var(--kp-muted); margin: 0; }

        /* ============ Badges ============ */
        .badge { font-weight: 600; font-size: 0.72rem; padding: 0.4em 0.7em; border-radius: 8px; }
        .kp-badge { display: inline-flex; align-items: center; gap: 5px; }
        .badge.bg-success, .kp-badge-success { background: rgba(22,134,83,0.12) !important; color: var(--kp-success) !important; }
        .badge.bg-danger, .kp-badge-danger { background: rgba(220,69,69,0.12) !important; color: var(--kp-danger) !important; }
        .badge.bg-warning, .kp-badge-warning { background: rgba(233,155,32,0.16) !important; color: #b7791f !important; }
        .badge.bg-secondary, .kp-badge-muted { background: #eeeaf5 !important; color: var(--kp-muted) !important; }
        .kp-badge-purple { background: rgba(89,38,200,0.1) !important; color: var(--kp-royal) !important; }
        .kp-badge-gold { background: rgba(244,197,66,0.25) !important; color: #8a6a00 !important; }
        .badge.bg-primary { background: var(--kp-royal) !important; }
        .badge.bg-info { background: rgba(89,38,200,0.12) !important; color: var(--kp-royal) !important; }
        .badge.bg-light { background: #f4f2fa !important; color: var(--kp-text) !important; }

        .badge-super_admin { background: var(--kp-danger) !important; color: #fff !important; }
        .badge-landlord { background: var(--kp-royal) !important; color: #fff !important; }
        .badge-caretaker { background: var(--kp-success) !important; color: #fff !important; }
        .badge-tenant { background: var(--kp-gold) !important; color: var(--kp-deep) !important; }

        /* ============ Buttons ============ */
        .btn { border-radius: 10px; font-weight: 600; font-size: 0.87rem; padding: 0.5rem 0.95rem; transition: all .16s ease; }
        .btn-sm { border-radius: 8px; font-size: 0.8rem; padding: 0.35rem 0.65rem; }

        .btn-primary {
            background: var(--kp-royal);
            border-color: var(--kp-royal);
            box-shadow: 0 4px 14px rgba(89, 38, 200, 0.22);
            color: #fff;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: #4a1ea8; border-color: #4a1ea8;
            box-shadow: 0 6px 18px rgba(89, 38, 200, 0.32);
            color: #fff;
        }
        .btn-gold { background: var(--kp-gold); border: none; color: var(--kp-deep); font-weight: 700; }
        .btn-gold:hover { background: #e6b52f; color: var(--kp-deep); }

        .btn-outline-primary { color: var(--kp-royal); border-color: #cbb9f0; background: transparent; }
        .btn-outline-primary:hover { background: var(--kp-royal); border-color: var(--kp-royal); color: #fff; }
        .btn-outline-secondary { color: var(--kp-muted); border-color: var(--kp-border); background: #fff; }
        .btn-outline-secondary:hover { background: var(--kp-bg); color: var(--kp-text); border-color: #ddd6ef; }
        .btn-outline-danger { color: var(--kp-danger); border-color: #f2bcbc; }
        .btn-outline-danger:hover { background: var(--kp-danger); border-color: var(--kp-danger); color: #fff; }
        .btn-outline-success { color: var(--kp-success); border-color: #acdcc4; }
        .btn-outline-success:hover { background: var(--kp-success); border-color: var(--kp-success); color: #fff; }
        .btn-outline-info { color: var(--kp-royal); border-color: #cbb9f0; }
        .btn-outline-info:hover { background: var(--kp-royal); border-color: var(--kp-royal); color: #fff; }
        .btn-outline-warning { color: #b7791f; border-color: #f2ddb0; }
        .btn-outline-warning:hover { background: var(--kp-warning); border-color: var(--kp-warning); color: #fff; }
        .btn-success { background: var(--kp-success); border-color: var(--kp-success); }
        .btn-danger { background: var(--kp-danger); border-color: var(--kp-danger); }

        /* ============ Tables ============ */
        .table { --bs-table-bg: transparent; margin-bottom: 0; color: var(--kp-text); }
        .table thead.table-light th,
        .table > thead th {
            background: #faf9fe;
            color: var(--kp-muted);
            border-bottom: 1px solid var(--kp-border);
            font-weight: 600;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: .6px;
            padding: 12px 14px;
            white-space: nowrap;
        }
        .table > tbody > tr > td { padding: 13px 14px; vertical-align: middle; border-color: var(--kp-border); }
        .table-hover > tbody > tr:hover > * { background: #faf9fe; }

        /* ============ Forms ============ */
        .form-label { font-size: 0.82rem; font-weight: 600; color: var(--kp-text); margin-bottom: 5px; }
        .form-control, .form-select {
            border: 1px solid var(--kp-border);
            border-radius: 10px;
            padding: 0.55rem 0.8rem;
            font-size: 0.88rem;
            color: var(--kp-text);
            background-color: #fff;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--kp-royal);
            box-shadow: 0 0 0 3px rgba(89, 38, 200, 0.12);
        }
        .form-control::placeholder { color: #b3aec0; }
        .input-group-text { background: var(--kp-bg); border-color: var(--kp-border); color: var(--kp-muted); border-radius: 10px; }
        .form-check-input:checked { background-color: var(--kp-royal); border-color: var(--kp-royal); }

        /* ============ Alerts ============ */
        .alert { border-radius: var(--kp-radius-sm); border: 1px solid transparent; font-size: 0.88rem; }
        .alert-success { background: rgba(22,134,83,0.08); border-color: rgba(22,134,83,0.2); color: var(--kp-success); }
        .alert-danger { background: rgba(220,69,69,0.08); border-color: rgba(220,69,69,0.2); color: var(--kp-danger); }
        .alert-warning { background: rgba(233,155,32,0.1); border-color: rgba(233,155,32,0.24); color: #b7791f; }
        .alert-info { background: rgba(89,38,200,0.07); border-color: rgba(89,38,200,0.18); color: var(--kp-royal); }
        .alert-light { background: var(--kp-bg); border-color: var(--kp-border); color: var(--kp-text); }

        /* ============ Empty state ============ */
        .kp-empty { text-align: center; padding: 56px 20px; }
        .kp-empty i { font-size: 3rem; color: #cfc7e4; }
        .kp-empty p { color: var(--kp-muted); margin-top: 14px; }

        /* ============ Pagination ============ */
        .pagination { --bs-pagination-color: var(--kp-royal); --bs-pagination-border-radius: 8px; gap: 4px; }
        .pagination .page-item .page-link { border: 1px solid var(--kp-border); color: var(--kp-royal); border-radius: 8px; }
        .pagination .page-item.active .page-link { background: var(--kp-royal); border-color: var(--kp-royal); color: #fff; }
        .pagination .page-link:hover { background: var(--kp-bg); color: var(--kp-deep); }

        /* Section titles inside pages */
        .kp-section-title {
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: var(--kp-muted); margin: 0 0 12px;
        }

        /* Progress */
        .kp-progress { height: 9px; border-radius: 999px; background: var(--kp-bg); overflow: hidden; }
        .kp-progress > span { display: block; height: 100%; border-radius: 999px; }

        /* Bars (dependency-free chart) */
        .kp-bars { display: flex; align-items: flex-end; gap: 14px; height: 180px; padding-top: 8px; }
        .kp-bar-col { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: 8px; height: 100%; justify-content: flex-end; }
        .kp-bar {
            width: 100%; max-width: 46px; border-radius: 8px 8px 0 0;
            background: linear-gradient(180deg, var(--kp-royal), var(--kp-deep));
            transition: height .4s ease; position: relative;
        }
        .kp-bar.gold { background: linear-gradient(180deg, var(--kp-gold), #e0ac1f); }
        .kp-bar-label { font-size: 0.72rem; color: var(--kp-muted); font-weight: 500; }
        .kp-bar-value { font-size: 0.72rem; font-weight: 700; color: var(--kp-deep); }

        /* Sidebar toggle + overlay */
        .sidebar-toggle {
            display: none; background: none; border: none;
            font-size: 1.35rem; color: var(--kp-deep); cursor: pointer;
        }
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(36, 26, 51, 0.5); z-index: 1045;
        }

        /* Toast */
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }

        /* Theme toggle */
        .theme-toggle {
            width: 40px; height: 40px; border-radius: 10px;
            border: 1px solid var(--kp-border); background: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1rem; color: var(--kp-muted);
            transition: all .16s ease;
        }
        .theme-toggle:hover { background: var(--kp-bg); color: var(--kp-royal); }

        /* ============ Responsive ============ */
        @media (max-width: 991px) {
            .sidebar { left: calc(-1 * var(--kp-sidebar-w)); }
            .sidebar.open { left: 0; }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .sidebar-toggle { display: flex; }
            .topbar-search { display: none; }
        }
        @media (max-width: 575px) {
            .page-content { padding: 18px 14px; }
            .topbar { padding: 10px 14px; }
            .topbar-profile .tp-meta { display: none; }
        }

        /* ============ Dark mode ============ */
        body.dark-mode { background-color: #140a24; }
        body.dark-mode .topbar { background: #1d1035; border-bottom-color: rgba(255,255,255,0.06); }
        body.dark-mode .topbar .page-title { color: #fff; }
        body.dark-mode .topbar-search input { background: #251645; border-color: rgba(255,255,255,0.08); color: #fff; }
        body.dark-mode .icon-btn, body.dark-mode .theme-toggle, body.dark-mode .topbar-profile {
            background: #251645; border-color: rgba(255,255,255,0.09); color: #cfc7e4;
        }
        body.dark-mode .topbar-profile .tp-name { color: #fff; }
        body.dark-mode .kp-card, body.dark-mode .stat-card { background: #1d1035; border-color: rgba(255,255,255,0.07); }
        body.dark-mode .kp-kpi-value, body.dark-mode .stat-card .stat-value,
        body.dark-mode .kp-page-title, body.dark-mode .kp-card-title,
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4, body.dark-mode h5, body.dark-mode h6 { color: #fff; }
        body.dark-mode .table > thead th { background: #251645; color: #b6aed0; border-color: rgba(255,255,255,0.07); }
        body.dark-mode .table > tbody > tr > td { border-color: rgba(255,255,255,0.07); color: #ddd6ef; }
        body.dark-mode .table-hover > tbody > tr:hover > * { background: rgba(255,255,255,0.03); }
        body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode .input-group-text {
            background: #251645; border-color: rgba(255,255,255,0.1); color: #fff;
        }
        body.dark-mode .dropdown-menu.kp-menu { background: #1d1035; border-color: rgba(255,255,255,0.08); }
        body.dark-mode .kp-menu .dropdown-item { color: #ddd6ef; }
        body.dark-mode .kp-menu .dropdown-item:hover { background: #251645; }
        body.dark-mode .text-muted { color: rgba(255,255,255,0.45) !important; }
        body.dark-mode .bg-light { background: #251645 !important; }
        body.dark-mode .border-bottom { border-color: rgba(255,255,255,0.08) !important; }
        body.dark-mode .border-top { border-color: rgba(255,255,255,0.08) !important; }
        body.dark-mode .kp-empty i { color: rgba(255,255,255,0.2); }
    </style>

    @stack('styles')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 10.5 12 3l9 7.5" stroke="#F4C542" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 9.8V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.8" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="10" y="14" width="4" height="7" rx="0.6" fill="#F4C542"/>
        </svg>
        <div class="brand-text">
            <h4>Keja<span>Pad</span></h4>
            <small>Manage &bull; Track &bull; Grow</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        @include('partials.sidebar-nav')
    </nav>

    <div class="sidebar-user">
        <div class="kp-avatar sm">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
        <div class="su-meta">
            <div class="su-name">{{ auth()->user()->name }}</div>
            <div class="su-role">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="su-logout" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    @php
        $kpUser = auth()->user();
        $kpNotifications = $kpUser->notifications()->latest()->take(5)->get();
        $kpUnread = $kpUser->unreadNotifications()->count();
    @endphp

    <!-- Topbar -->
    <div class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="page-title">@yield('page-title', 'Dashboard')</h5>

        @if($kpUser->isLandlord())
            <form class="topbar-search" method="GET" action="{{ route('landlord.tenants.index') }}" role="search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Search tenants by name or phone...">
            </form>
        @endif

        <div class="topbar-actions">
            <button class="theme-toggle" id="themeToggle" title="Toggle dark / light mode">
                <i class="bi bi-moon-fill" id="themeIcon"></i>
            </button>

            <div class="dropdown">
                <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell"></i>
                    @if($kpUnread > 0)
                        <span class="kp-dot">{{ $kpUnread > 9 ? '9+' : $kpUnread }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end kp-menu">
                    <div class="kp-menu-header">Notifications</div>
                    @forelse($kpNotifications as $notification)
                        <div class="kp-notif">
                            <div class="nf-icon"><i class="bi bi-bell-fill"></i></div>
                            <div>
                                <div class="nf-text">
                                    {{ $notification->data['message']
                                        ?? $notification->data['title']
                                        ?? 'You have a new notification.' }}
                                </div>
                                <div class="nf-time">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-3 py-4 text-center text-muted" style="font-size:0.83rem;">
                            <i class="bi bi-bell-slash d-block mb-1" style="font-size:1.3rem;"></i>
                            No notifications yet
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="dropdown">
                <div class="topbar-profile" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="kp-avatar sm">{{ strtoupper(substr($kpUser->name, 0, 2)) }}</div>
                    <div class="tp-meta d-none d-md-block">
                        <div class="tp-name">{{ $kpUser->name }}</div>
                        <div class="tp-role">{{ ucfirst(str_replace('_', ' ', $kpUser->role)) }}</div>
                    </div>
                    <i class="bi bi-chevron-down text-muted d-none d-md-block" style="font-size:0.7rem;"></i>
                </div>
                <div class="dropdown-menu dropdown-menu-end kp-menu">
                    <div class="kp-menu-header">{{ $kpUser->email }}</div>
                    @if($kpUser->isTenant())
                        <a class="dropdown-item" href="{{ route('tenant.profile') }}">
                            <i class="bi bi-person me-2"></i> My Profile
                        </a>
                    @else
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            });
        }

        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;
        const savedTheme = localStorage.getItem('kejapad-theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            themeIcon.className = 'bi bi-sun-fill';
        }
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    themeIcon.className = 'bi bi-sun-fill';
                    localStorage.setItem('kejapad-theme', 'dark');
                } else {
                    themeIcon.className = 'bi bi-moon-fill';
                    localStorage.setItem('kejapad-theme', 'light');
                }
            });
        }
    })();
</script>

@stack('scripts')
</body>
</html>
