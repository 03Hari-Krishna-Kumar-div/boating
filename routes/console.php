<?php

use App\Console\Commands\BackupDatabase;
use App\Console\Commands\CheckOverdueRentals;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register Dhanalakshmi Boating commands
Artisan::command('brms:backup', function () {
    $this->call(BackupDatabase::class);
})->purpose('Create database backup');

Artisan::command('brms:check-overdue', function () {
    $this->call(CheckOverdueRentals::class);
})->purpose('Check for overdue rentals');

// Schedule
Schedule::command('brms:backup')->dailyAt('02:00');
Schedule::command('brms:check-overdue')->everyMinute();
