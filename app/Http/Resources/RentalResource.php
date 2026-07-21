<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $effectiveEnd = $this->extended_until ?? $this->expected_end_at;
        $remainingSeconds = $effectiveEnd ? max(0, now()->diffInSeconds($effectiveEnd, false)) : 0;
        $overtimeSeconds = $effectiveEnd && now()->gt($effectiveEnd) ? abs(now()->diffInSeconds($effectiveEnd, false)) : 0;

        return [
            'id' => $this->id,
            'boat_id' => $this->boat_id,
            'boat_number' => $this->boat?->boat_number,
            'boat_name' => $this->boat?->name,
            'boat_status' => $this->boat?->status?->value,
            'worker_id' => $this->worker_id,
            'worker_name' => $this->worker?->name,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'started_at_time' => $this->started_at?->format('H:i:s'),
            'expected_end_at' => $this->expected_end_at?->format('Y-m-d H:i:s'),
            'expected_end_at_time' => $this->expected_end_at?->format('H:i:s'),
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'actual_end_at' => $this->actual_end_at?->format('Y-m-d H:i:s'),
            'extended_until' => $this->extended_until?->format('Y-m-d H:i:s'),
            'effective_end_at' => $effectiveEnd?->format('Y-m-d H:i:s'),
            'effective_end_at_time' => $effectiveEnd?->format('H:i:s'),
            'extended_minutes' => $this->extended_minutes ?? 0,
            'reduced_minutes' => $this->reduced_minutes ?? 0,
            'overtime_seconds' => $overtimeSeconds,
            'remaining_seconds' => $remainingSeconds,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'ended_by' => $this->ended_by,
            'ended_by_name' => $this->endedBy?->name,
            'end_reason' => $this->end_reason,
            'admin_override' => $this->admin_override ?? false,
            'customer_returned' => $this->customer_returned,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->started_at ? $this->started_at->diffInMinutes($this->actual_end_at ?? now()) : 0,
            'is_owned_by_current_user' => $request->user() ? $this->isOwnedBy($request->user()->id) : false,
        ];
    }
}
