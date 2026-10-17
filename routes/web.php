<?php

use App\Http\Controllers\ArrearsController;
use App\Http\Controllers\CaretakerAnnouncementController;
use App\Http\Controllers\CaretakerController;
use App\Http\Controllers\CaretakerInspectionController;
use App\Http\Controllers\CaretakerMaintenanceController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandlordAnnouncementController;
use App\Http\Controllers\LandlordApplicationController;
use App\Http\Controllers\LandlordCaretakerInvitationController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\LandlordDepositController;
use App\Http\Controllers\LandlordMaintenanceController;
use App\Http\Controllers\LandlordMoveOutController;
use App\Http\Controllers\LandlordTenantController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\RentNoticeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TenantAnnouncementController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantDepositController;
use App\Http\Controllers\TenantMaintenanceController;
use App\Http\Controllers\TenantMoveOutController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/qrcode', [LandingController::class, 'qrcode'])->name('qrcode');

// Redirect after login based on role
Route::middleware('auth')->get('/dashboard', function () {
    $role = auth()->user()->role;

    return match ($role) {
        'super_admin' => redirect()->route('super_admin.dashboard'),
        'landlord' => redirect()->route('landlord.dashboard'),
        'caretaker' => redirect()->route('caretaker.dashboard'),
        'tenant' => redirect()->route('tenant.dashboard'),
        default => redirect()->route('login'),
    };
})->name('dashboard');

$verified = config('app.email_verification') ? ['verified'] : [];

// Super Admin Routes
Route::middleware(array_merge(['auth', 'role:super_admin'], $verified))->prefix('super-admin')->name('super_admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Landlord applications (approval workflow)
    Route::get('/landlord-applications', [LandlordApplicationController::class, 'index'])->name('landlord-applications.index');
    Route::get('/landlord-applications/{landlord}', [LandlordApplicationController::class, 'show'])->name('landlord-applications.show');
    Route::post('/landlord-applications/{landlord}/approve', [LandlordApplicationController::class, 'approve'])->name('landlord-applications.approve');
    Route::post('/landlord-applications/{landlord}/reject', [LandlordApplicationController::class, 'reject'])->name('landlord-applications.reject');
    Route::post('/landlord-applications/{landlord}/suspend', [LandlordApplicationController::class, 'suspend'])->name('landlord-applications.suspend');
    Route::post('/landlord-applications/{landlord}/reinstate', [LandlordApplicationController::class, 'reinstate'])->name('landlord-applications.reinstate');
});

// Landlord approval status page (reachable while awaiting review).
Route::middleware(array_merge(['auth', 'role:landlord'], $verified))->prefix('landlord')->name('landlord.')->group(function () {
    Route::get('/pending-approval', [LandlordController::class, 'pending'])->name('pending');
});

// Landlord Routes
Route::middleware(array_merge(['auth', 'role:landlord', 'landlord.approved'], $verified))->prefix('landlord')->name('landlord.')->group(function () {
    Route::get('/dashboard', [LandlordController::class, 'dashboard'])->name('dashboard');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/rent-collection', [ReportController::class, 'rentCollection'])->name('reports.rent-collection');
    Route::get('/reports/outstanding', [ReportController::class, 'outstandingBalances'])->name('reports.outstanding');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');
    Route::get('/reports/maintenance', [ReportController::class, 'maintenance'])->name('reports.maintenance');
    Route::get('/reports/export/excel/{type}', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf/{type}', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Property Management
    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    // Unit Management
    Route::get('/properties/{property}/units', [UnitController::class, 'index'])->name('units.index');
    Route::get('/properties/{property}/units/create', [UnitController::class, 'create'])->name('units.create');
    Route::post('/properties/{property}/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/properties/{property}/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::put('/properties/{property}/units/{unit}', [UnitController::class, 'update'])->name('units.update');
    Route::delete('/properties/{property}/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

    // Tenant Management
    Route::get('/tenants', [LandlordTenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [LandlordTenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [LandlordTenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [LandlordTenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [LandlordTenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [LandlordTenantController::class, 'update'])->name('tenants.update');
    Route::patch('/tenants/{tenant}/deactivate', [LandlordTenantController::class, 'deactivate'])->name('tenants.deactivate');
    Route::get('/properties/{property}/get-units', [LandlordTenantController::class, 'getUnits'])->name('properties.get-units');

    // Tenant rent account: payments + notices
    Route::post('/tenants/{tenant}/payments', [LandlordTenantController::class, 'recordPayment'])->name('tenants.payments.store');
    Route::post('/tenants/{tenant}/notices', [RentNoticeController::class, 'store'])->name('tenants.notices.store');
    Route::get('/notices', [RentNoticeController::class, 'index'])->name('notices.index');
    Route::get('/notices/create', [RentNoticeController::class, 'create'])->name('notices.create');
    Route::patch('/notices/{notice}', [RentNoticeController::class, 'update'])->name('notices.update');

    // Arrears overview
    Route::get('/arrears', [ArrearsController::class, 'index'])->name('arrears.index');

    // Move-out / Notice to Vacate management
    Route::get('/move-outs', [LandlordMoveOutController::class, 'index'])->name('move-outs.index');
    Route::get('/move-outs/{moveOut}', [LandlordMoveOutController::class, 'show'])->name('move-outs.show');
    Route::patch('/move-outs/{moveOut}/review', [LandlordMoveOutController::class, 'review'])->name('move-outs.review');
    Route::post('/move-outs/{moveOut}/inspection', [LandlordMoveOutController::class, 'assignInspection'])->name('move-outs.inspection.assign');
    Route::patch('/move-outs/{moveOut}/clear', [LandlordMoveOutController::class, 'clear'])->name('move-outs.clear');

    // Security deposits
    Route::get('/deposits', [LandlordDepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits/sync', [LandlordDepositController::class, 'sync'])->name('deposits.sync');
    Route::get('/deposits/{deposit}', [LandlordDepositController::class, 'show'])->name('deposits.show');
    Route::post('/deposits/{deposit}/receipts', [LandlordDepositController::class, 'recordReceipt'])->name('deposits.receipts.store');
    Route::post('/deposits/{deposit}/receipts/{transaction}/verify', [LandlordDepositController::class, 'verifyReceipt'])->name('deposits.receipts.verify');
    Route::post('/deposits/{deposit}/verify', [LandlordDepositController::class, 'verify'])->name('deposits.verify');
    Route::post('/deposits/{deposit}/deductions', [LandlordDepositController::class, 'proposeDeduction'])->name('deposits.deductions.store');
    Route::post('/deposits/{deposit}/deductions/{transaction}/approve', [LandlordDepositController::class, 'approveDeduction'])->name('deposits.deductions.approve');
    Route::post('/deposits/{deposit}/refunds', [LandlordDepositController::class, 'refund'])->name('deposits.refunds.store');
    Route::post('/deposits/{deposit}/exception', [LandlordDepositController::class, 'exception'])->name('deposits.exception');

    // Caretaker invitations & assignments
    Route::get('/caretaker-invitations', [LandlordCaretakerInvitationController::class, 'index'])->name('caretaker-invitations.index');
    Route::get('/caretaker-invitations/create', [LandlordCaretakerInvitationController::class, 'create'])->name('caretaker-invitations.create');
    Route::post('/caretaker-invitations', [LandlordCaretakerInvitationController::class, 'store'])->name('caretaker-invitations.store');
    Route::post('/caretaker-invitations/{invitation}/revoke', [LandlordCaretakerInvitationController::class, 'revoke'])->name('caretaker-invitations.revoke');
    Route::delete('/caretakers/{caretaker}/properties/{property}', [LandlordCaretakerInvitationController::class, 'revokeCaretakerAccess'])->name('caretakers.revoke-access');

    // M-Pesa Payments
    Route::get('/mpesa-payments', [MpesaController::class, 'landlordPayments'])->name('mpesa-payments');
    Route::post('/mpesa-payments/{payment}/confirm', [MpesaController::class, 'landlordConfirm'])->name('mpesa-payments.confirm');
    Route::post('/mpesa-payments/{payment}/reject', [MpesaController::class, 'landlordReject'])->name('mpesa-payments.reject');

    // Rent Management
    Route::get('/rent', [RentController::class, 'index'])->name('rent.index');
    Route::get('/rent/create', [RentController::class, 'create'])->name('rent.create');
    Route::post('/rent', [RentController::class, 'store'])->name('rent.store');
    Route::get('/rent/{rent}', [RentController::class, 'show'])->name('rent.show');
    Route::post('/rent/{rent}/payment', [RentController::class, 'recordPayment'])->name('rent.payment');
    Route::patch('/rent/{rent}/overdue', [RentController::class, 'markOverdue'])->name('rent.overdue');
    Route::post('/rent/generate-bulk', [RentController::class, 'generateBulk'])->name('rent.generate-bulk');

    // Maintenance
    Route::get('/maintenance', [LandlordMaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/{maintenance}', [LandlordMaintenanceController::class, 'show'])->name('maintenance.show');
    Route::post('/maintenance/{maintenance}/assign', [LandlordMaintenanceController::class, 'assign'])->name('maintenance.assign');
    Route::patch('/maintenance/{maintenance}/status', [LandlordMaintenanceController::class, 'updateStatus'])->name('maintenance.status');

    // Announcements
    Route::get('/announcements', [LandlordAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [LandlordAnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [LandlordAnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [LandlordAnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [LandlordAnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [LandlordAnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::patch('/announcements/{announcement}/toggle', [LandlordAnnouncementController::class, 'togglePublish'])->name('announcements.toggle');
});

// Caretaker Routes
Route::middleware(array_merge(['auth', 'role:caretaker'], $verified))->prefix('caretaker')->name('caretaker.')->group(function () {
    Route::get('/dashboard', [CaretakerController::class, 'dashboard'])->name('dashboard');

    // Announcements
    Route::get('/announcements', [CaretakerAnnouncementController::class, 'index'])->name('announcements.index');

    // Maintenance
    Route::get('/maintenance', [CaretakerMaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/{maintenance}', [CaretakerMaintenanceController::class, 'show'])->name('maintenance.show');
    Route::patch('/maintenance/{maintenance}/status', [CaretakerMaintenanceController::class, 'updateStatus'])->name('maintenance.status');

    // Move-out inspections
    Route::get('/inspections', [CaretakerInspectionController::class, 'index'])->name('inspections.index');
    Route::get('/inspections/{inspection}', [CaretakerInspectionController::class, 'show'])->name('inspections.show');
    Route::post('/inspections/{inspection}/submit', [CaretakerInspectionController::class, 'submit'])->name('inspections.submit');
});

// Tenant Routes
Route::middleware(array_merge(['auth', 'role:tenant'], $verified))->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/dashboard', [TenantController::class, 'dashboard'])->name('dashboard');
    Route::get('/payments', [TenantController::class, 'paymentHistory'])->name('payments');
    Route::get('/profile', [TenantController::class, 'profile'])->name('profile');
    Route::put('/profile', [TenantController::class, 'updateProfile'])->name('profile.update');

    // Announcements
    Route::get('/announcements', [TenantAnnouncementController::class, 'index'])->name('announcements.index');

    // Maintenance
    Route::get('/maintenance', [TenantMaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/create', [TenantMaintenanceController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [TenantMaintenanceController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenance}', [TenantMaintenanceController::class, 'show'])->name('maintenance.show');

    // M-Pesa Payments
    Route::get('/pay/{rent}', [MpesaController::class, 'payPage'])->name('pay');
    Route::post('/pay/{rent}/initiate', [MpesaController::class, 'initiate'])->name('pay.initiate');
    Route::get('/pay/{rent}/pending', [MpesaController::class, 'pending'])->name('payment.pending');
    Route::get('/payment/{payment}/status', [MpesaController::class, 'checkStatus'])->name('payment.status');

    // Notice to Vacate
    Route::get('/move-out', [TenantMoveOutController::class, 'index'])->name('move-out.index');
    Route::get('/move-out/create', [TenantMoveOutController::class, 'create'])->name('move-out.create');
    Route::post('/move-out', [TenantMoveOutController::class, 'store'])->name('move-out.store');
    Route::get('/move-out/{moveOut}', [TenantMoveOutController::class, 'show'])->name('move-out.show');
    Route::patch('/move-out/{moveOut}/cancel', [TenantMoveOutController::class, 'cancel'])->name('move-out.cancel');

    // Security deposit
    Route::get('/deposit', [TenantDepositController::class, 'show'])->name('deposit.show');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// M-Pesa Callback (public - called by Safaricom)
Route::post('/api/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

require __DIR__.'/auth.php';
