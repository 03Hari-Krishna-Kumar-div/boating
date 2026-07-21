<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RentalController;
use App\Http\Controllers\Api\TimerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\BoatController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Authenticated API routes (automatically prefixed with 'api/' by bootstrap)
Route::middleware(['web', 'auth', 'active', 'check.session'])->group(function () {
    // Dashboard (polled every 1 second)
    Route::get('dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    // Rentals
    Route::post('rentals/start', [RentalController::class, 'start'])->name('api.rentals.start');
    Route::post('rentals/{rental}/end', [RentalController::class, 'end'])->name('api.rentals.end');
    Route::post('rentals/{rental}/confirm-return', [RentalController::class, 'confirmReturn'])->name('api.rentals.confirm-return');
    Route::post('rentals/{rental}/mark-still-out', [RentalController::class, 'markStillOut'])->name('api.rentals.mark-still-out');

    // Mark Received (after end rental)
    Route::post('rentals/{rental}/receive', [RentalController::class, 'markReceived'])->name('api.rentals.receive');

    // Transfer Ownership (admin only)
    Route::post('rentals/{rental}/transfer', [RentalController::class, 'transfer'])->name('api.rentals.transfer');

    // Extend, Reduce, Force-End
    Route::post('rentals/{rental}/extend', [RentalController::class, 'extend'])->name('api.rentals.extend');
    Route::post('rentals/{rental}/reduce', [RentalController::class, 'reduce'])->name('api.rentals.reduce');
    Route::post('rentals/{rental}/force-end', [RentalController::class, 'forceEnd'])->name('api.rentals.force-end');
    Route::post('rentals/{rental}/complete', [RentalController::class, 'complete'])->name('api.rentals.complete');

    // Get all active rentals
    Route::get('rentals/active', [RentalController::class, 'allActive'])->name('api.rentals.active');

    // Boats
    Route::get('boats', [BoatController::class, 'index'])->name('api.boats.index');
    Route::get('boats/{boat}', [BoatController::class, 'show'])->name('api.boats.show');
    Route::post('boats/{boat}/maintenance', [BoatController::class, 'moveToMaintenance'])->name('api.boats.maintenance');
    Route::post('boats/{boat}/available', [BoatController::class, 'removeFromMaintenance'])->name('api.boats.available');

    // Workers
    Route::get('workers', [WorkerController::class, 'index'])->name('api.workers.index');
    Route::get('workers/online', [WorkerController::class, 'online'])->name('api.workers.online');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('api.reports.index');
    Route::get('reports/rentals', [ReportController::class, 'rentals'])->name('api.reports.rentals');
    Route::get('reports/utilization', [ReportController::class, 'utilization'])->name('api.reports.utilization');

    // Notifications
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->name('api.notifications.unread');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('api.notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('api.notifications.read-all');

    // Connection / Timer / Server Time
    Route::get('ping', [ConnectionController::class, 'ping'])->name('api.ping');
    Route::get('server-time', [ConnectionController::class, 'ping'])->name('api.server-time');
    Route::get('timer/sync', [TimerController::class, 'sync'])->name('api.timer.sync');

    // User
    Route::get('user/rentals', [RentalController::class, 'myRentals'])->name('api.user.rentals');
});
