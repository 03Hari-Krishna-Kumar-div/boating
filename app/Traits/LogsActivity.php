<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        // Trait is used for manual logging via ActivityLogService
    }

    public function logActivity(string $action, ?string $details = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id'    => $this->id ?? null,
            'boat_id'    => null,
            'rental_id'  => null,
            'action'     => $action,
            'details'    => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
