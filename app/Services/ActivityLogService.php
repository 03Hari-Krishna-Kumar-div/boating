<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Boat;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(
        string $action,
        ?User $user = null,
        ?Boat $boat = null,
        ?Rental $rental = null,
        ?string $details = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'    => $user?->id,
            'boat_id'    => $boat?->id,
            'rental_id'  => $rental?->id,
            'action'     => $action,
            'details'    => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    public function getLogs(array $filters = [], int $perPage = 50)
    {
        $query = ActivityLog::with(['user', 'boat'])->latest('created_at');

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['boat_id'])) {
            $query->where('boat_id', $filters['boat_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('boat', function ($b) use ($search) {
                      $b->where('boat_number', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->paginate($perPage);
    }
}
