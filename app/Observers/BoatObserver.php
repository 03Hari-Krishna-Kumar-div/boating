<?php

namespace App\Observers;

use App\Models\Boat;
use App\Services\ActivityLogService;

class BoatObserver
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function created(Boat $boat): void
    {
        $this->activityLogService->log('boat_created', auth()->user(), $boat,
            null, "Boat #{$boat->boat_number} created");
    }

    public function updated(Boat $boat): void
    {
        if ($boat->isDirty('status')) {
            $action = $boat->status->value === 'maintenance'
                ? 'boat_maintenance_on'
                : ($boat->getOriginal('status')?->value === 'maintenance' ? 'boat_maintenance_off' : 'boat_updated');

            $this->activityLogService->log($action, auth()->user(), $boat,
                null, "Boat #{$boat->boat_number} status changed to {$boat->status->label()}");
        }
    }

    public function deleted(Boat $boat): void
    {
        $this->activityLogService->log('boat_deleted', auth()->user(), $boat,
            null, "Boat #{$boat->boat_number} deleted");
    }
}
