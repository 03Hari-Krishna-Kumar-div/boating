<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\User;
use App\Models\Rental;
use App\Enums\BoatStatus;
use App\Enums\RentalStatus;
use App\Http\Resources\BoatResource;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private TimerService $timerService,
    ) {}

    public function getDashboardData(User $user): array
    {
        if ($user->isAdmin()) {
            // Admin sees all boats
            $boats = Boat::with(['currentRental.worker'])->withCount('rentals')->get();
        } else {
            // Worker sees:
            // 1. Their own active/ended boats (where they are the current worker)
            // 2. All available boats
            // 3. Their own maintenance boats if they have any
            $boats = Boat::with(['currentRental.worker'])->withCount('rentals')
                ->where(function ($q) use ($user) {
                    $q->where('status', BoatStatus::AVAILABLE)
                      ->orWhere('status', BoatStatus::MAINTENANCE)
                      ->orWhereHas('currentRental', function ($r) use ($user) {
                          $r->where('worker_id', $user->id);
                      });
                })
                ->get();
        }

        $stats = $this->getStats();
        $notifications = $user->notifications()->unread()->latest()->take(10)->get();

        return [
            'boats' => BoatResource::collection($boats),
            'stats' => $stats,
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'message' => $n->message,
                'created_at' => $n->created_at?->diffForHumans(),
            ]),
        ];
    }

    public function getStats(): array
    {
        $boatCounts = Boat::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as warning,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as maintenance,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as awaiting
            ", [
                BoatStatus::AVAILABLE->value,
                BoatStatus::OVERDUE->value,
                BoatStatus::WARNING->value,
                BoatStatus::MAINTENANCE->value,
                BoatStatus::AWAITING_CONFIRMATION->value,
            ])->first();

        $activeRentals = Rental::where('status', RentalStatus::ACTIVE)->count();
        $onlineWorkers = User::workers()->online()->count();

        return [
            'total_boats' => (int) ($boatCounts->total ?? 0),
            'available_boats' => (int) ($boatCounts->available ?? 0),
            'active_rentals' => $activeRentals,
            'overdue_boats' => (int) ($boatCounts->overdue ?? 0),
            'warning_boats' => (int) ($boatCounts->warning ?? 0),
            'awaiting_boats' => (int) ($boatCounts->awaiting ?? 0),
            'online_workers' => $onlineWorkers,
            'maintenance_boats' => (int) ($boatCounts->maintenance ?? 0),
        ];
    }

    public function getBoats(): array
    {
        return BoatResource::collection(
            Boat::with(['currentRental.worker'])->withCount('rentals')->get()
        )->resolve();
    }

    public function getActiveRentals()
    {
        return Rental::with(['boat', 'worker'])
            ->where('status', RentalStatus::ACTIVE)
            ->latest()
            ->get();
    }
}
