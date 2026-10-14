@php
    $role = auth()->user()->role;
@endphp

@if($role === 'landlord')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}"
       class="nav-link {{ request()->routeIs('landlord.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('landlord.properties.index') }}"
       class="nav-link {{ request()->routeIs('landlord.properties.*') ? 'active' : '' }}">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}"
       class="nav-link {{ request()->routeIs('landlord.tenants.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}"
       class="nav-link {{ request()->routeIs('landlord.rent.*') ? 'active' : '' }}">
        <i class="bi bi-cash-coin"></i> Rent Payments
    </a>
    <a href="{{ route('landlord.arrears.index') }}"
       class="nav-link {{ request()->routeIs('landlord.arrears.*') ? 'active' : '' }}">
        <i class="bi bi-exclamation-octagon"></i> Arrears
    </a>
    <a href="{{ route('landlord.notices.index') }}"
       class="nav-link {{ request()->routeIs('landlord.notices.*') ? 'active' : '' }}">
        <i class="bi bi-envelope-paper"></i> Notices
    </a>
    <a href="{{ route('landlord.move-outs.index') }}"
       class="nav-link {{ request()->routeIs('landlord.move-outs.*') ? 'active' : '' }}">
        <i class="bi bi-box-arrow-right"></i> Move-outs
    </a>

    <div class="nav-section-title">Manage</div>
    <a href="{{ route('landlord.deposits.index') }}"
       class="nav-link {{ request()->routeIs('landlord.deposits.*') ? 'active' : '' }}">
        <i class="bi bi-shield-check"></i> Deposits
    </a>
    <a href="{{ route('landlord.mpesa-payments') }}"
       class="nav-link {{ request()->routeIs('landlord.mpesa-payments') ? 'active' : '' }}">
        <i class="bi bi-phone"></i> M-Pesa Payments
    </a>
    <a href="{{ route('landlord.maintenance.index') }}"
       class="nav-link {{ request()->routeIs('landlord.maintenance.*') ? 'active' : '' }}">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}"
       class="nav-link {{ request()->routeIs('landlord.announcements.*') ? 'active' : '' }}">
        <i class="bi bi-megaphone"></i> Announcements
    </a>

    <div class="nav-section-title">Insights</div>
    <a href="{{ route('landlord.reports.index') }}"
       class="nav-link {{ request()->routeIs('landlord.reports.*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
    <a href="{{ route('profile.edit') }}"
       class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <i class="bi bi-gear"></i> Settings
    </a>
@elseif($role === 'tenant')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}"
       class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('tenant.payments') }}"
       class="nav-link {{ request()->routeIs('tenant.payments') || request()->routeIs('tenant.pay*') ? 'active' : '' }}">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.announcements.index') }}"
       class="nav-link {{ request()->routeIs('tenant.announcements.*') ? 'active' : '' }}">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('tenant.maintenance.index') }}"
       class="nav-link {{ request()->routeIs('tenant.maintenance.*') ? 'active' : '' }}">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.profile') }}"
       class="nav-link {{ request()->routeIs('tenant.profile') ? 'active' : '' }}">
        <i class="bi bi-person"></i> My Profile
    </a>
    <div class="nav-section-title">My Tenancy</div>
    <a href="{{ route('tenant.move-out.index') }}"
       class="nav-link {{ request()->routeIs('tenant.move-out.*') ? 'active' : '' }}">
        <i class="bi bi-box-arrow-right"></i> Notice to Vacate
    </a>
    <a href="{{ route('tenant.deposit.show') }}"
       class="nav-link {{ request()->routeIs('tenant.deposit.*') ? 'active' : '' }}">
        <i class="bi bi-shield-check"></i> My Deposit
    </a>
@elseif($role === 'caretaker')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('caretaker.dashboard') }}"
       class="nav-link {{ request()->routeIs('caretaker.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('caretaker.announcements.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.announcements.*') ? 'active' : '' }}">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('caretaker.maintenance.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.maintenance.*') ? 'active' : '' }}">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('caretaker.inspections.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.inspections.*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check"></i> Move-out Inspections
    </a>
@elseif($role === 'super_admin')
    <div class="nav-section-title">Administration</div>
    <a href="{{ route('super_admin.dashboard') }}"
       class="nav-link {{ request()->routeIs('super_admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('super_admin.users.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.users.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> Users
    </a>
@endif
