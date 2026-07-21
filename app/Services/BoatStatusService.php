<?php

namespace App\Services;

use App\Models\Boat;
use App\Enums\BoatStatus;
use InvalidArgumentException;

class BoatStatusService
{
    public function transitionTo(Boat $boat, BoatStatus $target): Boat
    {
        if (!$this->canTransitionTo($boat->status, $target)) {
            throw new InvalidArgumentException(
                "Cannot transition from {$boat->status->value} to {$target->value}"
            );
        }

        $boat->update(['status' => $target]);
        return $boat->fresh();
    }

    public function canTransitionTo(BoatStatus $current, BoatStatus $target): bool
    {
        return $current->canTransitionTo($target);
    }

    public function getValidTransitions(BoatStatus $status): array
    {
        return BoatStatus::validTransitions()[$status] ?? [];
    }

    public function getAvailableActions(Boat $boat): array
    {
        return match ($boat->status) {
            BoatStatus::AVAILABLE => ['start_rental', 'toggle_maintenance'],
            BoatStatus::OCCUPIED => ['end_rental', 'transfer'],
            BoatStatus::WARNING => ['end_rental', 'extend', 'reduce', 'transfer'],
            BoatStatus::TIME_UP => ['end_rental', 'extend', 'reduce'],
            BoatStatus::ENDED => ['mark_received'],
            BoatStatus::AWAITING_CONFIRMATION => ['confirm_return', 'mark_still_out'],
            BoatStatus::OVERDUE => ['end_rental'],
            BoatStatus::MAINTENANCE => ['toggle_maintenance'],
        };
    }
}
