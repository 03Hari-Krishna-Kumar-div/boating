<?php

namespace App\Console\Commands;

use App\Models\Boat;
use App\Models\Rental;
use App\Enums\BoatStatus;
use App\Enums\RentalStatus;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\TimerService;
use Illuminate\Console\Command;

class CheckOverdueRentals extends Command
{
    protected $signature = 'brms:check-overdue';
    protected $description = 'Check for overdue rentals, trigger warnings and time_up status';

    public function handle(
        TimerService $timerService,
        ActivityLogService $activityLogService,
        NotificationService $notificationService
    ): int {
        $this->info('Checking for overdue rentals...');

        $activeRentals = Rental::with('boat')
            ->whereIn('status', [RentalStatus::ACTIVE])
            ->get();

        $updated = 0;

        foreach ($activeRentals as $rental) {
            $remaining = $timerService->getRemainingSeconds($rental->effective_end_at);
            $boat = $rental->boat;

            // ── WARNING: < 5 minutes remaining ──
            if ($remaining > 0 && $remaining <= $timerService->getWarningThreshold()
                && in_array($boat->status->value, ['occupied', 'warning'])
                && $boat->status !== BoatStatus::WARNING) {
                $boat->update(['status' => BoatStatus::WARNING]);
                $activityLogService->log('boat_warning', null, $boat, $rental,
                    "Boat #{$boat->boat_number} in warning zone ({$remaining}s remaining)");

                $notificationService->send($rental->worker, 'warning',
                    "Boat #{$boat->boat_number} has {$remaining} seconds remaining!");
                $updated++;
            }

            // ── TIME UP: timer expired → set time_up status ──
            if ($remaining <= 0 && $boat->status !== BoatStatus::TIME_UP
                && $boat->status !== BoatStatus::ENDED
                && $boat->status !== BoatStatus::OVERDUE) {

                $rental->update(['status' => RentalStatus::OVERDUE]);
                $boat->update(['status' => BoatStatus::TIME_UP]);

                $activityLogService->log('time_up', null, $boat, $rental,
                    "Boat #{$boat->boat_number} timer expired — time up.");

                $notificationService->send($rental->worker, 'time_up',
                    "Boat #{$boat->boat_number} time is up! Please end the rental.");
                $updated++;
            }
        }

        $this->info("Updated {$updated} rentals.");
        return Command::SUCCESS;
    }
}
