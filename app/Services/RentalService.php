<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\Rental;
use App\Models\User;
use App\Enums\BoatStatus;
use App\Enums\RentalStatus;
use App\Exceptions\BoatNotAvailableException;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalService
{
    public function __construct(
        private TimerService $timerService,
        private BoatStatusService $boatStatusService,
        private ActivityLogService $activityLogService,
        private NotificationService $notificationService,
    ) {}

    public function startRental(Boat $boat, User $worker, ?int $durationMinutes = null): Rental
    {
        $durationMinutes = $durationMinutes ?? config('brms.rental_duration_minutes', 45);

        // Validate availability
        if ($boat->status !== BoatStatus::AVAILABLE) {
            throw new BoatNotAvailableException(
                'This boat has already been assigned.'
            );
        }

        $expectedEnd = $this->timerService->calculateExpectedEnd(now(), $durationMinutes);

        // Step 1: Create rental (no explicit transaction — PgBouncer pooler
        // struggles with multi-statement transactions. Each query auto-commits.)
        Log::info('Attempting Rental::create', [
            'boat_id' => $boat->id,
            'worker_id' => $worker->id,
            'expected_end_at' => $expectedEnd->toDateTimeString(),
        ]);

        $rental = Rental::create([
            'boat_id' => $boat->id,
            'worker_id' => $worker->id,
            'started_at' => now(),
            'expected_end_at' => $expectedEnd,
            'status' => RentalStatus::ACTIVE,
        ]);

        Log::info('Rental created OK, id=' . $rental->id);

        // Step 2: Update boat
        Log::info('Attempting Boat::update', [
            'boat_id' => $boat->id,
            'rental_id' => $rental->id,
        ]);

        $boat->update([
            'status' => BoatStatus::OCCUPIED,
            'current_rental_id' => $rental->id,
        ]);

        Log::info('Boat updated OK');

        // $this->activityLogService->log('boat_started', $worker, $boat, $rental,
        //     "Rental started on boat {$boat->boat_number} by {$worker->name}");
        // $this->notificationService->send($worker, 'rental_started', "...");
        // $this->notificationService->sendToAllAdmins('rental_started', "...");

        return $rental;
    }

    public function endRental(Rental $rental, User $endedBy, ?string $notes = null): Rental
    {
        return DB::transaction(function () use ($rental, $endedBy, $notes) {
            $boat = $rental->boat;

            $overtimeSeconds = $this->timerService->getOvertimeSeconds(
                $rental->started_at, $rental->effective_end_at
            );

            $rental->update([
                'ended_at' => now(),
                'actual_end_at' => now(),
                'ended_by' => $endedBy->id,
                'overtime_seconds' => $overtimeSeconds,
                'status' => RentalStatus::ENDED,
                'customer_returned' => false,
                'notes' => $notes,
                'end_reason' => $endedBy->isAdmin() ? 'admin_ended' : 'worker_ended',
            ]);

            // Boat goes to ENDED state — NOT available yet.
            // Worker must physically receive the boat first.
            $boat->update([
                'status' => BoatStatus::ENDED,
                // Keep current_rental_id until received
            ]);

            // $this->activityLogService->log('rental_ended', $endedBy, $boat, $rental, "...");
            // $this->notificationService->send($endedBy, 'rental_ended', "...");
            // $this->notificationService->sendToAllAdmins('rental_ended', "...");

            return $rental->fresh();
        });
    }

    /**
     * Mark a boat as received after end rental.
     * This is the final step that makes the boat AVAILABLE again.
     */
    public function markReceived(Rental $rental, User $user): Rental
    {
        return DB::transaction(function () use ($rental, $user) {
            $boat = $rental->boat;

            if ($rental->status !== RentalStatus::ENDED) {
                throw new \RuntimeException('Rental must be in ENDED status to receive boat.');
            }

            $rental->update([
                'received_at' => now(),
                'received_by_worker_id' => $user->id,
                'status' => RentalStatus::COMPLETED,
                'customer_returned' => true,
            ]);

            $boat->update([
                'status' => BoatStatus::AVAILABLE,
                'current_rental_id' => null,
            ]);

            // $this->activityLogService->log('boat_received', $user, $boat, $rental, "...");
            // $this->notificationService->send($rental->worker, 'boat_received', "...");
            // $this->notificationService->sendToAllAdmins('boat_received', "...");

            return $rental->fresh();
        });
    }

    /**
     * Transfer ownership of an active rental to another worker (admin only).
     */
    public function transferOwnership(Rental $rental, User $newWorker, User $admin): Rental
    {
        return DB::transaction(function () use ($rental, $newWorker, $admin) {
            $boat = $rental->boat;

            $oldWorkerId = $rental->worker_id;

            $rental->update([
                'worker_id' => $newWorker->id,
            ]);

            // $this->activityLogService->log('ownership_transferred', $admin, $boat, $rental, "...");
            // $this->notificationService->send($newWorker, 'ownership_transferred', "...");
            // $this->notificationService->send(User::find($oldWorkerId), 'ownership_transferred', "...");
            // $this->notificationService->sendToAllAdmins('ownership_transferred', "...");

            return $rental->fresh();
        });
    }

    /**
     * Mark boat as TIME_UP when timer reaches 00:00.
     */
    public function markTimeUp(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            $boat = $rental->boat;

            $rental->update([
                'status' => RentalStatus::OVERDUE,
            ]);

            $boat->update([
                'status' => BoatStatus::TIME_UP,
            ]);

            // $this->activityLogService->log('time_up', null, $boat, $rental, "...");
            // $this->notificationService->send($rental->worker, 'time_up', "...");
            // $this->notificationService->sendToAllAdmins('time_up', "...");

            return $rental->fresh();
        });
    }

    public function confirmReturn(Rental $rental, User $worker): Rental
    {
        return DB::transaction(function () use ($rental, $worker) {
            $boat = $rental->boat;

            $overtimeSeconds = $this->timerService->getOvertimeSeconds(
                $rental->started_at, $rental->effective_end_at
            );

            $rental->update([
                'ended_at' => now(),
                'actual_end_at' => now(),
                'ended_by' => $worker->id,
                'overtime_seconds' => $overtimeSeconds,
                'status' => RentalStatus::COMPLETED,
                'customer_returned' => true,
                'end_reason' => 'customer_returned',
            ]);

            $boat->update([
                'status' => BoatStatus::AVAILABLE,
                'current_rental_id' => null,
            ]);

            // $this->activityLogService->log('boat_confirmed', $worker, $boat, $rental, "...");
            // $this->notificationService->send($worker, 'return_confirmed', "...");

            return $rental->fresh();
        });
    }

    public function markStillOut(Rental $rental, User $worker): Rental
    {
        return DB::transaction(function () use ($rental, $worker) {
            $boat = $rental->boat;

            $overtimeSeconds = $this->timerService->getOvertimeSeconds(
                $rental->started_at, $rental->effective_end_at
            );

            $rental->update([
                'overtime_seconds' => $overtimeSeconds,
                'status' => RentalStatus::OVERDUE,
                'customer_returned' => false,
            ]);

            $boat->update([
                'status' => BoatStatus::OVERDUE,
            ]);

            // $this->activityLogService->log('boat_overdue', $worker, $boat, $rental, "...");
            // $this->notificationService->send($worker, 'overdue', "...");

            return $rental->fresh();
        });
    }

    public function forceEnd(Rental $rental, User $admin, ?string $notes = null): Rental
    {
        return DB::transaction(function () use ($rental, $admin, $notes) {
            $boat = $rental->boat;

            $overtimeSeconds = $this->timerService->getOvertimeSeconds(
                $rental->started_at, $rental->effective_end_at
            );

            $rental->update([
                'ended_at' => now(),
                'actual_end_at' => now(),
                'ended_by' => $admin->id,
                'overtime_seconds' => $overtimeSeconds,
                'status' => RentalStatus::OVERRIDDEN,
                'notes' => $notes,
                'admin_override' => true,
                'end_reason' => 'admin_force_end',
            ]);

            $boat->update([
                'status' => BoatStatus::AVAILABLE,
                'current_rental_id' => null,
            ]);

            // $this->activityLogService->log('rental_overridden', $admin, $boat, $rental, "...");
            // $this->notificationService->send($rental->worker, 'rental_ended', "...");
            // $this->notificationService->sendToAllAdmins('rental_overridden', "...");

            return $rental->fresh();
        });
    }

    /**
     * ========================================================================
     *  CORRECT TIMER ADJUSTMENT (shared by extend + reduce)
     * ========================================================================
     *
     * Algorithm — always operates on REMAINING TIME, never on absolute end:
     *
     *   1. remainingSeconds = max(0, rental.end_time - server_now)
     *   2. newRemaining     = max(0, remainingSeconds + deltaSeconds)
     *                         where deltaSeconds > 0  for extend
     *                               deltaSeconds < 0  for reduce
     *   3. newEndTime       = server_now + newRemaining
     *   4. Save: rental.extended_until = newEndTime
     *
     *  If deltaSeconds < 0 (reduce) AND newRemaining === 0:
     *    → rental is COMPLETED (not moved to awaiting_confirmation)
     *
     *  NEVER sets extended_until = now() directly.
     *  NEVER pushes end_time into the past without completing.
     * ========================================================================
     */
    public function adjustTime(Rental $rental, User $admin, int $deltaSeconds): Rental
    {
        return DB::transaction(function () use ($rental, $admin, $deltaSeconds) {
            $boat = $rental->boat;
            $now = now();

            // ── 1. Authoritative remaining time ──────────────────────
            $endTime = $rental->extended_until ?? $rental->expected_end_at;
            $remainingSeconds = $endTime ? max(0, $now->diffInSeconds($endTime, false)) : 0;

            $isReduction = $deltaSeconds < 0;
            $adjustmentMinutes = (int) ceil(abs($deltaSeconds) / 60);

            // ── 2. Compute new remaining ─────────────────────────────
            $newRemaining = max(0, $remainingSeconds + $deltaSeconds);

            // ── 3. If reduction fully consumes remaining → COMPLETE ──
            if ($isReduction && $newRemaining === 0) {
                return $this->completeRentalFromReduction(
                    $rental, $boat, $admin, $adjustmentMinutes, $remainingSeconds
                );
            }

            // ── 4. Otherwise: calculate new end time and save ────────
            $newEndTime = $now->copy()->addSeconds($newRemaining);

            if ($isReduction) {
                $rental->update([
                    'reduced_minutes' => ($rental->reduced_minutes ?? 0) + $adjustmentMinutes,
                    'extended_until' => $newEndTime,
                ]);

                // $this->activityLogService->log('time_reduced', $admin, $boat, $rental, "...");
                // $this->notificationService->send($rental->worker, 'time_reduced', "...");
            } else {
                $data = [
                    'extended_minutes' => ($rental->extended_minutes ?? 0) + $adjustmentMinutes,
                    'extended_until' => $newEndTime,
                ];

                // If boat was in TIME_UP, OVERDUE or WARNING, reset both boat and rental status
                if (in_array($boat->status, [BoatStatus::TIME_UP, BoatStatus::OVERDUE, BoatStatus::WARNING])) {
                    $data['status'] = RentalStatus::ACTIVE;
                    $boat->update(['status' => BoatStatus::OCCUPIED]);
                }

                $rental->update($data);

                // $this->activityLogService->log('time_extended', $admin, $boat, $rental, "...");
                // $this->notificationService->send($rental->worker, 'time_extended', "...");
            }

            return $rental->fresh();
        });
    }

    /**
     * Convenience: extend by whole minutes (positive delta).
     */
    public function extendTime(Rental $rental, User $admin, int $minutes): Rental
    {
        return $this->adjustTime($rental, $admin, $minutes * 60);
    }

    /**
     * Convenience: reduce by whole minutes (negative delta).
     */
    public function reduceTime(Rental $rental, User $admin, int $minutes): Rental
    {
        return $this->adjustTime($rental, $admin, -($minutes * 60));
    }

    /**
     * Called when a reduction fully consumes the remaining time.
     * Completes the rental immediately — never moves to AWAITING_CONFIRMATION.
     */
    private function completeRentalFromReduction(
        Rental $rental, Boat $boat, User $admin, int $reductionMinutes, int $remainingSeconds
    ): Rental {
        $overtimeSeconds = $this->timerService->getOvertimeSeconds(
            $rental->started_at, $rental->effective_end_at
        );

        $rental->update([
            'ended_at' => now(),
            'actual_end_at' => now(),
            'ended_by' => $admin->id,
            'reduced_minutes' => ($rental->reduced_minutes ?? 0) + $reductionMinutes,
            'overtime_seconds' => $overtimeSeconds,
            'status' => RentalStatus::COMPLETED,
            'customer_returned' => false,
            'end_reason' => 'timer_expired',
            'notes' => $rental->notes
                ? $rental->notes . ' | Timer fully reduced by admin.'
                : 'Timer fully reduced by admin.',
        ]);

        $boat->update([
            'status' => BoatStatus::AVAILABLE,
            'current_rental_id' => null,
        ]);

        // $this->activityLogService->log('time_reduced_completed', $admin, $boat, $rental, "...");
        // $this->notificationService->send($rental->worker, 'rental_completed', "...");

        return $rental->fresh();
    }

    /**
     * Check if a worker is authorized to perform actions on a rental.
     */
    public function checkWorkerOwnership(Rental $rental, User $user): bool
    {
        // Admin can do anything
        if ($user->isAdmin()) {
            return true;
        }
        // Worker can only act on their own rentals
        return $rental->isOwnedBy($user->id);
    }

    /**
     * Get all active rentals with rich data for the dashboard.
     */
    public function getActiveRentals()
    {
        return Rental::with(['boat', 'worker', 'endedBy'])
            ->whereIn('status', [RentalStatus::ACTIVE, RentalStatus::OVERDUE])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get active rentals for a specific worker.
     */
    public function getWorkerActiveRentals(int $workerId)
    {
        return Rental::with(['boat', 'worker'])
            ->where('worker_id', $workerId)
            ->whereIn('status', [RentalStatus::ACTIVE, RentalStatus::OVERDUE])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Admin override: mark rental as completed directly.
     */
    public function adminCompleteRental(Rental $rental, User $admin): Rental
    {
        return DB::transaction(function () use ($rental, $admin) {
            $boat = $rental->boat;

            $rental->update([
                'ended_at' => now(),
                'actual_end_at' => now(),
                'ended_by' => $admin->id,
                'status' => RentalStatus::COMPLETED,
                'admin_override' => true,
                'customer_returned' => true,
                'end_reason' => 'admin_completed',
            ]);

            $boat->update([
                'status' => BoatStatus::AVAILABLE,
                'current_rental_id' => null,
            ]);

            // $this->activityLogService->log('rental_completed', $admin, $boat, $rental, "...");

            return $rental->fresh();
        });
    }

    /**
     * Move boat to maintenance.
     */
    public function moveToMaintenance(Boat $boat, User $admin): Boat
    {
        $boat->update([
            'status' => BoatStatus::MAINTENANCE,
            'current_rental_id' => null,
        ]);

        // $this->activityLogService->log('boat_moved_to_maintenance', $admin, $boat, null, "...");

        return $boat->fresh();
    }

    /**
     * Remove boat from maintenance.
     */
    public function removeFromMaintenance(Boat $boat, User $admin): Boat
    {
        $boat->update([
            'status' => BoatStatus::AVAILABLE,
        ]);

        // $this->activityLogService->log('boat_removed_from_maintenance', $admin, $boat, null, "...");

        return $boat->fresh();
    }
}
