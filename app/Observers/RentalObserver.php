<?php

namespace App\Observers;

use App\Models\Rental;
use App\Services\ActivityLogService;

class RentalObserver
{
    public function __construct(private ActivityLogService $activityLogService) {}

    /**
     * Observer is kept for future expansion.
     * Logging of boat_started is handled in RentalService::startRental()
     * to avoid duplicate entries and ensure proper context (worker info).
     */
}
