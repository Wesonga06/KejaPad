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
    <a href="{{ route('landlord.turnovers.index') }}"
       class="nav-link {{ request()->routeIs('landlord.turnovers.*') ? 'active' : '' }}">
        <i class="bi bi-arrow-repeat"></i> Turnovers
    </a>
    <a href="{{ route('landlord.alerts.index') }}"
       class="nav-link {{ request()->routeIs('landlord.alerts.*') ? 'active' : '' }}">
        <i class="bi bi-bell"></i> Alerts
    </a>

    <div class="nav-section-title">Manage</div>
    <a href="{{ route('landlord.deposits.index') }}"
       class="nav-link {{ request()->routeIs('landlord.deposits.*') ? 'active' : '' }}">
        <i class="bi bi-shield-check"></i> Deposits
    </a>
    <a href="{{ route('landlord.settlements.index') }}"
       class="nav-link {{ request()->routeIs('landlord.settlements.*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i> Deposit Settlements
    </a>
    <a href="{{ route('landlord.mpesa-payments') }}"
       class="nav-link {{ request()->routeIs('landlord.mpesa-payments') ? 'active' : '' }}">
        <i class="bi bi-phone"></i> M-Pesa Payments
    </a>
    <a href="{{ route('landlord.maintenance.index') }}"
       class="nav-link {{ request()->routeIs('landlord.maintenance.*') ? 'active' : '' }}">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.caretakers.index') }}"
       class="nav-link {{ request()->routeIs('landlord.caretakers.*') ? 'active' : '' }}">
        <i class="bi bi-person-check"></i> Caretakers
    </a>
    <a href="{{ route('landlord.caretaker-invitations.index') }}"
       class="nav-link {{ request()->routeIs('landlord.caretaker-invitations.*') ? 'active' : '' }}">
        <i class="bi bi-person-plus"></i> Caretaker Invitations
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
    <a href="{{ route('tenant.passport.show') }}"
       class="nav-link {{ request()->routeIs('tenant.passport.*') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i> Property Passport
    </a>
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
    <a href="{{ route('caretaker.history.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.history.*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-data"></i> My Work History
    </a>
    <a href="{{ route('caretaker.inspections.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.inspections.*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check"></i> Move-out Inspections
    </a>
    <a href="{{ route('caretaker.turnovers.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.turnovers.*') ? 'active' : '' }}">
        <i class="bi bi-arrow-repeat"></i> Turnovers
    </a>
    <a href="{{ route('caretaker.passport.index') }}"
       class="nav-link {{ request()->routeIs('caretaker.passport.*') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i> Property Passports
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
    <a href="{{ route('super_admin.landlord-applications.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.landlord-applications.*') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i> Landlord Applications
    </a>

    <div class="nav-section-title">Configuration</div>
    <a href="{{ route('super_admin.settings.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.settings.*') ? 'active' : '' }}">
        <i class="bi bi-sliders"></i> System Configuration
    </a>
    <a href="{{ route('super_admin.payments.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.payments.*') ? 'active' : '' }}">
        <i class="bi bi-credit-card"></i> Payment Settings
    </a>
    <a href="{{ route('super_admin.sms.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.sms.*') ? 'active' : '' }}">
        <i class="bi bi-chat-dots"></i> SMS Settings
    </a>

    <div class="nav-section-title">Insights</div>
    <a href="{{ route('super_admin.reports.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.reports.*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart"></i> System Reports
    </a>
    <a href="{{ route('super_admin.passports.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.passports.*') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i> Property Passports
    </a>
    <a href="{{ route('super_admin.settlements.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.settlements.*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i> Deposit Settlements
    </a>
    <a href="{{ route('super_admin.turnovers.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.turnovers.*') ? 'active' : '' }}">
        <i class="bi bi-arrow-repeat"></i> Turnovers
    </a>
    <a href="{{ route('super_admin.alerts.index') }}"
       class="nav-link {{ request()->routeIs('super_admin.alerts.*') ? 'active' : '' }}">
        <i class="bi bi-bell"></i> Alerts
    </a>
@endif
