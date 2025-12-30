<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Redirect to role-specific dashboard after login
Route::get('dashboard', function () {
    $user = auth()->user();
    
    return match($user->role) {
        Role::ADMINISTRATOR => redirect()->route('admin.dashboard'),
        Role::TECHNICIAN => redirect()->route('technician.dashboard'),
        Role::COUNTER_STAFF => redirect()->route('counter.dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

// Administrator routes
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('dashboard', 'admin.dashboard')->name('dashboard');
    Volt::route('parts-inventory', 'admin.parts-inventory')->name('parts-inventory');
    Volt::route('suppliers', 'admin.suppliers')->name('suppliers');
    Volt::route('services', 'admin.services')->name('services');
});

// Technician routes
Route::middleware(['auth', 'role:technician'])->prefix('technician')->name('technician.')->group(function () {
    Volt::route('dashboard', 'technician.dashboard')->name('dashboard');
});

// Counter Staff routes
Route::middleware(['auth', 'role:counter_staff'])->prefix('counter')->name('counter.')->group(function () {
    Volt::route('dashboard', 'counter.dashboard')->name('dashboard');
    Volt::route('quote-requests', 'counter.quote-requests')->name('quote-requests');
    Volt::route('job-orders', 'counter.job-orders')->name('job-orders');
    Volt::route('job-orders/create', 'counter.job-orders-create')->name('job-orders.create');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    // Staff Management (Administrator only)
    Route::middleware('role:administrator')->prefix('staff')->name('staff.')->group(function () {
        Volt::route('/', 'staff.index')->name('index');
        Volt::route('/create', 'staff.create')->name('create');
        Volt::route('/{user}/edit', 'staff.edit')->name('edit');
    });
});
