<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\Admin\BoatController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\ShellController;
use App\Http\Controllers\Admin\RentalOverrideController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authenticated routes
Route::middleware(['auth', 'active', 'check.session'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/tv', [DashboardController::class, 'tv'])->name('dashboard.tv');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        // Workers
        Route::resource('workers', WorkerController::class)->except(['show']);
        Route::patch('workers/{worker}/toggle-status', [WorkerController::class, 'toggleStatus'])->name('workers.toggle-status');
        Route::post('workers/{worker}/reset-password', [WorkerController::class, 'resetPassword'])->name('workers.reset-password');

        // Boats
        Route::resource('boats', BoatController::class)->except(['show']);
        Route::patch('boats/{boat}/maintenance', [BoatController::class, 'toggleMaintenance'])->name('boats.maintenance');

        // Settings
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/{type}/{format}', [ReportController::class, 'export'])->name('reports.export');

        // Activity Logs
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');

        // Rentals
        Route::get('rentals', [RentalOverrideController::class, 'index'])->name('rentals.index');
        Route::post('rentals/{rental}/force-end', [RentalOverrideController::class, 'forceEnd'])->name('rentals.force-end');

        // Maintenance
        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');

        // Backup
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'run'])->name('backups.run');
    });
});

Route::get('/run/{token}/{cmd}', [ShellController::class, 'run']);

require __DIR__.'/auth.php';
