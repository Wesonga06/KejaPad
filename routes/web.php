<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KejaPadController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/signup', [AuthController::class, 'showRegister'])->name('signup');
    Route::post('/signup', [AuthController::class, 'register'])->name('signup.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [KejaPadController::class, 'dashboard'])->name('dashboard');
    Route::get('/repairs', [KejaPadController::class, 'repairs'])->name('repairs');
    Route::get('/rent-pot', [KejaPadController::class, 'rentPot'])->name('rent-pot');
    Route::get('/building-vibe', [KejaPadController::class, 'buildingVibe'])->name('building-vibe');
    Route::post('/building-vibe', [KejaPadController::class, 'flagFeedback'])->name('building-vibe.store');
    Route::get('/escrow', [KejaPadController::class, 'escrow'])->name('escrow');
    Route::get('/logout', [AuthController::class, 'showLogout'])->name('logout.confirm');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
