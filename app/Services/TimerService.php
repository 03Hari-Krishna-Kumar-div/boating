<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\Rental;
use App\Enums\BoatStatus;
use App\Enums\RentalStatus;
use Carbon\Carbon;

class TimerService
{
    public function getRemainingSeconds(Carbon $effectiveEnd): int
    {
        return max(0, now()->diffInSeconds($effectiveEnd, false));
    }

    public function getOvertimeSeconds(Carbon $startedAt, Carbon $effectiveEnd): int
    {
        if (now()->gt($effectiveEnd)) {
            return (int) $effectiveEnd->diffInSeconds(now());
        }
        return 0;
    }

    public function isInWarning(Carbon $effectiveEnd): bool
    {
        $remaining = $this->getRemainingSeconds($effectiveEnd);
        $warningThreshold = config('brms.warning_minutes', 5) * 60;
        return $remaining > 0 && $remaining <= $warningThreshold;
    }

    public function isExpired(Carbon $effectiveEnd): bool
    {
        return now()->gte($effectiveEnd);
    }

    public function calculateExpectedEnd(Carbon $startedAt, int $durationMinutes): Carbon
    {
        return $startedAt->copy()->addMinutes($durationMinutes);
    }

    public function determineBoatStatus(Boat $boat, Rental $rental): BoatStatus
    {
        if ($boat->status === BoatStatus::MAINTENANCE) {
            return BoatStatus::MAINTENANCE;
        }

        // Use the effective end time (extended_until if present) for status determination
        $effectiveEnd = $rental->effective_end_at;
        $remaining = $this->getRemainingSeconds($effectiveEnd);
        $warningThreshold = config('brms.warning_minutes', 5) * 60;

        if ($remaining > 0 && $remaining <= $warningThreshold) {
            return BoatStatus::WARNING;
        }

        // AWAITING_CONFIRMATION status is no longer used for timer logic; keep handling for legacy cases
        if ($remaining <= 0 && $boat->status === BoatStatus::AWAITING_CONFIRMATION) {
            return BoatStatus::AWAITING_CONFIRMATION;
        }

        if ($remaining <= 0 && $rental->status === RentalStatus::ACTIVE) {
            return BoatStatus::OVERDUE;
        }

        return $boat->status;
    }

    public function getWarningThreshold(): int
    {
        return config('brms.warning_minutes', 5) * 60;
    }
}
