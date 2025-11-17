<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    // Redirect root to dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customers - accessible by all authenticated users
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    // Customer management - only admin and super_admin
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // Customer show - accessible by all (must be after /create route)
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // Payments - only super_admin can manage
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('payments', PaymentController::class)->except(['edit', 'update']);
    });

    // Users management - only super_admin
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
