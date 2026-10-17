<?php

use App\Http\Controllers\ArrearsController;
use App\Http\Controllers\CaretakerAnnouncementController;
use App\Http\Controllers\CaretakerController;
use App\Http\Controllers\CaretakerHistoryController;
use App\Http\Controllers\CaretakerInspectionController;
use App\Http\Controllers\CaretakerMaintenanceController;
use App\Http\Controllers\CaretakerPassportController;
use App\Http\Controllers\CaretakerTurnoverController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LandlordAlertController;
use App\Http\Controllers\LandlordAnnouncementController;
use App\Http\Controllers\LandlordApplicationController;
use App\Http\Controllers\LandlordCaretakerAccountabilityController;
use App\Http\Controllers\LandlordCaretakerInvitationController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\LandlordDepositController;
use App\Http\Controllers\LandlordMaintenanceController;
use App\Http\Controllers\LandlordMoveOutController;
use App\Http\Controllers\LandlordSettlementController;
use App\Http\Controllers\LandlordTenantController;
use App\Http\Controllers\LandlordTurnoverController;
use App\Http\Controllers\MaintenanceEvidenceController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\PassportFileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyAlertSettingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyPassportController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\RentNoticeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuperAdmin\AlertController as SuperAdminAlertController;
use App\Http\Controllers\SuperAdmin\PassportController as SuperAdminPassportController;
use App\Http\Controllers\SuperAdmin\PaymentSettingController;
use App\Http\Controllers\SuperAdmin\SettlementController as SuperAdminSettlementController;
use App\Http\Controllers\SuperAdmin\SmsSettingController;
use App\Http\Controllers\SuperAdmin\SystemReportController;
use App\Http\Controllers\SuperAdmin\SystemSettingController;
use App\Http\Controllers\SuperAdmin\TurnoverController as SuperAdminTurnoverController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TenantAnnouncementController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantDepositController;
use App\Http\Controllers\TenantMaintenanceController;
use App\Http\Controllers\TenantMoveOutController;
use App\Http\Controllers\TenantPassportController;
use App\Http\Controllers\TenantSettlementController;
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
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // System configuration
    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SystemSettingController::class, 'update'])->name('settings.update');

    // Payment settings + transaction monitoring
    Route::get('/payments', [PaymentSettingController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentSettingController::class, 'update'])->name('payments.update');
    Route::get('/payments/transactions', [PaymentSettingController::class, 'transactions'])->name('payments.transactions');
    Route::get('/payments/transactions/export', [PaymentSettingController::class, 'export'])->name('payments.export');

    // SMS settings
    Route::get('/sms', [SmsSettingController::class, 'index'])->name('sms.index');
    Route::post('/sms', [SmsSettingController::class, 'update'])->name('sms.update');
    Route::post('/sms/templates', [SmsSettingController::class, 'updateTemplates'])->name('sms.templates.update');
    Route::post('/sms/test', [SmsSettingController::class, 'sendTest'])->name('sms.test');
    Route::get('/sms/log', [SmsSettingController::class, 'log'])->name('sms.log');

    // System reports
    Route::get('/reports', [SystemReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [SystemReportController::class, 'export'])->name('reports.export');

    // Property Passport oversight (read-only)
    Route::get('/passports', [SuperAdminPassportController::class, 'index'])->name('passports.index');
    Route::get('/passports/{unit}', [SuperAdminPassportController::class, 'show'])->name('passports.show');

    // Deposit settlement (read-only oversight)
    Route::get('/settlements', [SuperAdminSettlementController::class, 'index'])->name('settlements.index');
    Route::get('/settlements/{settlement}', [SuperAdminSettlementController::class, 'show'])->name('settlements.show');

    // Tenant turnover oversight (read-only)
    Route::get('/turnovers', [SuperAdminTurnoverController::class, 'index'])->name('turnovers.index');
    Route::get('/turnovers/{turnover}', [SuperAdminTurnoverController::class, 'show'])->name('turnovers.show');

    // Early-warning alert oversight (read-only)
    Route::get('/alerts', [SuperAdminAlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [SuperAdminAlertController::class, 'show'])->name('alerts.show');

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

    // Property Passport (unit history, inspections, documents, condition)
    Route::get('/properties/{property}/passport', [PropertyPassportController::class, 'index'])->name('passport.index');
    Route::get('/properties/{property}/units/{unit}/passport', [PropertyPassportController::class, 'show'])->name('passport.show');
    Route::post('/properties/{property}/units/{unit}/passport/inspections', [PropertyPassportController::class, 'storeInspection'])->name('passport.inspections.store');
    Route::post('/properties/{property}/units/{unit}/passport/conditions', [PropertyPassportController::class, 'storeCondition'])->name('passport.conditions.store');
    Route::post('/properties/{property}/units/{unit}/passport/documents', [PropertyPassportController::class, 'storeDocument'])->name('passport.documents.store');
    Route::get('/passport/inspections/{inspection}', [PropertyPassportController::class, 'showInspection'])->name('passport.inspections.show');
    Route::post('/passport/inspections/{inspection}/draft', [PropertyPassportController::class, 'updateInspection'])->name('passport.inspections.draft');
    Route::post('/passport/inspections/{inspection}/submit', [PropertyPassportController::class, 'submitInspection'])->name('passport.inspections.submit');
    Route::delete('/passport/documents/{document}', [PropertyPassportController::class, 'destroyDocument'])->name('passport.documents.destroy');

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

    // Deposit settlements (move-out resolution)
    Route::get('/settlements', [LandlordSettlementController::class, 'index'])->name('settlements.index');
    Route::post('/move-outs/{moveOut}/settlement', [LandlordSettlementController::class, 'store'])->name('settlements.store');
    Route::get('/settlements/{settlement}', [LandlordSettlementController::class, 'show'])->name('settlements.show');
    Route::post('/settlements/{settlement}/send', [LandlordSettlementController::class, 'send'])->name('settlements.send');
    Route::post('/settlements/{settlement}/decision', [LandlordSettlementController::class, 'decide'])->name('settlements.decision');
    Route::post('/settlements/{settlement}/settle', [LandlordSettlementController::class, 'settle'])->name('settlements.settle');

    // Turnover workspace / vacancy recovery
    Route::get('/turnovers', [LandlordTurnoverController::class, 'index'])->name('turnovers.index');
    Route::get('/turnovers/{turnover}', [LandlordTurnoverController::class, 'show'])->name('turnovers.show');
    Route::post('/turnovers/{turnover}/tasks', [LandlordTurnoverController::class, 'addTask'])->name('turnovers.tasks.store');
    Route::patch('/turnovers/{turnover}/tasks/{task}/assign', [LandlordTurnoverController::class, 'assignTask'])->name('turnovers.tasks.assign');
    Route::patch('/turnovers/{turnover}/tasks/{task}/verify', [LandlordTurnoverController::class, 'verifyTask'])->name('turnovers.tasks.verify');
    Route::post('/turnovers/{turnover}/handover', [LandlordTurnoverController::class, 'recordHandover'])->name('turnovers.handover');
    Route::post('/turnovers/{turnover}/ready', [LandlordTurnoverController::class, 'markReady'])->name('turnovers.ready');
    Route::post('/turnovers/{turnover}/onboarding', [LandlordTurnoverController::class, 'beginOnboarding'])->name('turnovers.onboarding');
    Route::post('/turnovers/{turnover}/incoming-inspection', [LandlordTurnoverController::class, 'incomingInspection'])->name('turnovers.incoming-inspection');

    // Early-warning alerts
    Route::get('/alerts', [LandlordAlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [LandlordAlertController::class, 'show'])->name('alerts.show');
    Route::post('/alerts/{alert}/acknowledge', [LandlordAlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::post('/alerts/{alert}/dismiss', [LandlordAlertController::class, 'dismiss'])->name('alerts.dismiss');
    Route::post('/alerts/{alert}/resolve', [LandlordAlertController::class, 'resolve'])->name('alerts.resolve');

    // Per-property early-warning thresholds
    Route::get('/properties/{property}/alerts/settings', [PropertyAlertSettingController::class, 'edit'])->name('properties.alerts.settings');
    Route::post('/properties/{property}/alerts/settings', [PropertyAlertSettingController::class, 'update'])->name('properties.alerts.settings.update');

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
    Route::patch('/maintenance/{maintenance}/verify', [LandlordMaintenanceController::class, 'verify'])->name('maintenance.verify');
    Route::patch('/maintenance/{maintenance}/return', [LandlordMaintenanceController::class, 'return'])->name('maintenance.return');
    Route::patch('/maintenance/{maintenance}/deadline', [LandlordMaintenanceController::class, 'changeDeadline'])->name('maintenance.deadline');
    Route::post('/maintenance/{maintenance}/evidence', [LandlordMaintenanceController::class, 'addEvidence'])->name('maintenance.evidence.store');

    // Caretaker accountability (work quality oversight)
    Route::get('/caretakers', [LandlordCaretakerAccountabilityController::class, 'index'])->name('caretakers.index');
    Route::get('/caretakers/{caretaker}', [LandlordCaretakerAccountabilityController::class, 'show'])->name('caretakers.show');

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
    Route::post('/maintenance/{maintenance}/complete', [CaretakerMaintenanceController::class, 'submitCompletion'])->name('maintenance.complete');
    Route::post('/maintenance/{maintenance}/evidence', [CaretakerMaintenanceController::class, 'addEvidence'])->name('maintenance.evidence.store');
    Route::post('/maintenance/{maintenance}/correction', [CaretakerMaintenanceController::class, 'requestCorrection'])->name('maintenance.correction');

    // Own work history and metrics
    Route::get('/history', [CaretakerHistoryController::class, 'index'])->name('history.index');

    // Move-out inspections
    Route::get('/inspections', [CaretakerInspectionController::class, 'index'])->name('inspections.index');
    Route::get('/inspections/{inspection}', [CaretakerInspectionController::class, 'show'])->name('inspections.show');
    Route::post('/inspections/{inspection}/submit', [CaretakerInspectionController::class, 'submit'])->name('inspections.submit');

    // Turnover workspaces (assigned / authorised properties)
    Route::get('/turnovers', [CaretakerTurnoverController::class, 'index'])->name('turnovers.index');
    Route::get('/turnovers/{turnover}', [CaretakerTurnoverController::class, 'show'])->name('turnovers.show');

    // Property Passports
    Route::get('/passports', [CaretakerPassportController::class, 'index'])->name('passport.index');
    Route::get('/units/{unit}/passport', [CaretakerPassportController::class, 'show'])->name('passport.show');
    Route::post('/units/{unit}/passport/inspections', [CaretakerPassportController::class, 'storeInspection'])->name('passport.inspections.store');
    Route::post('/units/{unit}/passport/documents', [CaretakerPassportController::class, 'storeDocument'])->name('passport.documents.store');
    Route::get('/passport/inspections/{inspection}', [CaretakerPassportController::class, 'showInspection'])->name('passport.inspections.show');
    Route::post('/passport/inspections/{inspection}/draft', [CaretakerPassportController::class, 'updateInspection'])->name('passport.inspections.draft');
    Route::post('/passport/inspections/{inspection}/submit', [CaretakerPassportController::class, 'submitInspection'])->name('passport.inspections.submit');
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

    // Deposit settlement review
    Route::get('/settlement/{settlement}', [TenantSettlementController::class, 'show'])->name('settlement.show');
    Route::post('/settlement/{settlement}/respond', [TenantSettlementController::class, 'respond'])->name('settlement.respond');

    // Security deposit
    Route::get('/deposit', [TenantDepositController::class, 'show'])->name('deposit.show');

    // Property Passport (own unit, tenancy-related information only)
    Route::get('/passport', [TenantPassportController::class, 'show'])->name('passport.show');
    Route::post('/passport/inspections/{inspection}/acknowledge', [TenantPassportController::class, 'acknowledge'])->name('passport.inspections.acknowledge');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Property Passport private files (documents + inspection photographs).
// Access is enforced by policy, not by role, so any authorized viewer may stream.
Route::middleware('auth')->group(function () {
    Route::get('/passport/documents/{document}', [PassportFileController::class, 'download'])->name('passport.documents.download');
    Route::get('/passport/inspections/{inspection}/photos/{index}', [PassportFileController::class, 'photo'])->name('passport.photos.show');

    // Maintenance task evidence (private; access enforced by policy).
    Route::get('/maintenance/evidence/{evidence}', [MaintenanceEvidenceController::class, 'show'])->name('maintenance.evidence.show');
});

// M-Pesa Callback (public - called by Safaricom)
Route::post('/api/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

require __DIR__.'/auth.php';
