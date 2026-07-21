<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLogService;

class UserObserver
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function created(User $user): void
    {
        if ($user->role->value === 'worker') {
            $this->activityLogService->log('worker_created', auth()->user(), null,
                null, "Worker {$user->name} created");
        }
    }

    public function updated(User $user): void
    {
        if ($user->isDirty('is_active')) {
            $action = $user->is_active ? 'worker_enabled' : 'worker_disabled';
            $this->activityLogService->log($action, auth()->user(), null,
                null, "Worker {$user->name} " . ($user->is_active ? 'enabled' : 'disabled'));
        }
    }

    public function deleted(User $user): void
    {
        if ($user->role->value === 'worker') {
            $this->activityLogService->log('worker_deleted', auth()->user(), null,
                null, "Worker {$user->name} deleted");
        }
    }
}
