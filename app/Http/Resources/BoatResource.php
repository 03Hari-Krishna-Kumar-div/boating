<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentRental = $this->currentRental;
        $user = $request->user();
        $isAdmin = $user && $user->isAdmin();
        $isOwner = $currentRental && $user && $currentRental->worker_id === $user->id;

        // Calculate remaining time
        $remainingSeconds = 0;
        $overtimeSeconds = 0;
        if ($currentRental) {
            $endAt = $currentRental->extended_until ?? $currentRental->expected_end_at;
            if (now()->lt($endAt)) {
                $remainingSeconds = now()->diffInSeconds($endAt, false);
            } else {
                $overtimeSeconds = abs(now()->diffInSeconds($endAt, false));
            }
        }

        return [
            'id' => $this->id,
            'boat_number' => $this->boat_number,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'color_hex' => $this->color_hex,
            'notes' => $this->notes,
            'current_rental_id' => $this->current_rental_id,
            'is_current_worker' => $currentRental && $user && $currentRental->worker_id === $user->id,
            'total_rentals' => $this->rentals_count,

            // Current rental details (if occupied)
            'current_rental' => $currentRental ? [
                'id' => $currentRental->id,
                'worker_id' => $currentRental->worker_id,
                'worker_name' => $currentRental->worker?->name ?? 'Unknown',
                'started_at' => $currentRental->started_at?->format('H:i:s'),
                'started_at_full' => $currentRental->started_at?->toDateTimeString(),
                'started_at_time' => $currentRental->started_at?->format('H:i:s'),
                'expected_end_at' => $currentRental->expected_end_at?->format('H:i:s'),
                'expected_end_at_full' => $currentRental->expected_end_at?->toDateTimeString(),
                'extended_until' => $currentRental->extended_until?->format('H:i:s'),
                'extended_until_full' => $currentRental->extended_until?->toDateTimeString(),
                'effective_end_at' => $currentRental->effective_end_at?->format('H:i:s'),
                'effective_end_at_full' => $currentRental->effective_end_at?->toDateTimeString(),
                'effective_end_at_time' => $currentRental->effective_end_at?->format('H:i:s'),
                'extended_minutes' => $currentRental->extended_minutes ?? 0,
                'reduced_minutes' => $currentRental->reduced_minutes ?? 0,
                'remaining_seconds' => max(0, $remainingSeconds),
                'overtime_seconds' => $overtimeSeconds,
                'status' => $currentRental->status->value,
                'is_overdue' => $currentRental->status->value === 'overdue',
                'notes' => $currentRental->notes,
            ] : null,

            // Permission flags
            'is_owner' => $isOwner,
            'is_admin' => $isAdmin,
            'can_end' => $isAdmin || $isOwner,
            'can_receive' => $isAdmin || $isOwner,
            'can_extend' => $isAdmin && $currentRental !== null,
            'can_reduce' => $isAdmin && $currentRental !== null,
            'can_force_end' => $isAdmin && $currentRental !== null,
            'can_confirm' => $isAdmin || $isOwner,
            'can_start' => $this->status->value === 'available' && ($isAdmin || true),
            'can_transfer' => $isAdmin && $currentRental !== null,

            // Actions visibility
            'actions' => [
                'show_end' => ($isAdmin || $isOwner) && $currentRental !== null && in_array($currentRental->status->value, ['active', 'overdue']),
                'show_receive' => ($isAdmin || $isOwner) && $currentRental !== null && $currentRental->status->value === 'ended',
                'show_extend' => $isAdmin && $currentRental !== null && in_array($currentRental->status->value, ['active', 'occupied', 'warning', 'time_up']),
                'show_reduce' => $isAdmin && $currentRental !== null && in_array($currentRental->status->value, ['active', 'occupied', 'warning', 'time_up']),
                'show_force_end' => $isAdmin && $currentRental !== null,
                'show_confirm' => $currentRental && ($isAdmin || $isOwner) && $currentRental->status->value === 'awaiting_confirmation',
                'show_start' => $this->status->value === 'available',
                'show_maintenance' => $isAdmin,
                'show_transfer' => $isAdmin && $currentRental !== null,
            ],

            // Worker info (for display on other worker's screens)
            'worker' => $currentRental ? [
                'id' => $currentRental->worker_id,
                'name' => $currentRental->worker?->name,
            ] : null,

        ];
    }
}
