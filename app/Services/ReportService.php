<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\Rental;
use App\Models\User;
use App\Enums\RentalStatus;
use Carbon\Carbon;

class ReportService
{
    public function daily(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $rentalsStarted = Rental::whereBetween('started_at', [$start, $end])->count();
        $rentalsEnded = Rental::whereBetween('ended_at', [$start, $end])->count();
        $activeNow = Rental::where('status', RentalStatus::ACTIVE)->count();
        $overdue = Rental::where('status', RentalStatus::OVERDUE)->count();

        $workerActivity = User::workers()
            ->withCount(['rentals' => fn($q) => $q->whereBetween('started_at', [$start, $end])])
            ->get()
            ->map(fn($w) => ['name' => $w->name, 'rentals' => $w->rentals_count]);

        return compact('rentalsStarted', 'rentalsEnded', 'activeNow', 'overdue', 'workerActivity', 'date');
    }

    public function weekly(Carbon $start): array
    {
        $end = $start->copy()->addDays(6)->endOfDay();

        $dailyAggregates = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $dailyAggregates[] = $this->daily($day);
        }

        $mostUsed = Boat::withCount(['rentals' => fn($q) => $q->whereBetween('started_at', [$start, $end])])
            ->orderBy('rentals_count', 'desc')
            ->take(5)
            ->get();

        $workerRanking = User::workers()
            ->withCount(['rentals' => fn($q) => $q->whereBetween('started_at', [$start, $end])])
            ->orderBy('rentals_count', 'desc')
            ->get();

        $avgDuration = Rental::whereBetween('started_at', [$start, $end])
            ->whereNotNull('actual_end_at')
            ->get()
            ->avg(fn($r) => $r->started_at->diffInMinutes($r->actual_end_at));

        return compact('dailyAggregates', 'mostUsed', 'workerRanking', 'avgDuration', 'start', 'end');
    }

    public function monthly(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $totalRentals = Rental::whereBetween('started_at', [$start, $end])->count();
        $totalCompleted = Rental::whereBetween('started_at', [$start, $end])
            ->where('status', RentalStatus::COMPLETED)->count();
        $totalOverdue = Rental::whereBetween('started_at', [$start, $end])
            ->where('status', RentalStatus::OVERDUE)->count();

        $utilization = Boat::withCount(['rentals' => fn($q) => $q->whereBetween('started_at', [$start, $end])])
            ->orderBy('rentals_count', 'desc')
            ->get()
            ->map(fn($b) => [
                'boat_number' => $b->boat_number,
                'name' => $b->name,
                'rentals' => $b->rentals_count,
                'utilization_pct' => $b->rentals_count > 0
                    ? round(($b->rentals_count / $totalRentals) * 100, 1) : 0,
            ]);

        $workerPerformance = User::workers()
            ->withCount(['rentals' => fn($q) => $q->whereBetween('started_at', [$start, $end])])
            ->get()
            ->map(fn($w) => [
                'name' => $w->name,
                'total_rentals' => $w->rentals_count,
                'overdue_incidents' => $w->rentals()
                    ->whereBetween('started_at', [$start, $end])
                    ->where('status', RentalStatus::OVERDUE)->count(),
            ]);

        return compact('year', 'month', 'totalRentals', 'totalCompleted', 'totalOverdue', 'utilization', 'workerPerformance');
    }

    public function utilization(Carbon $start, Carbon $end)
    {
        $totalHours = $start->diffInHours($end);

        return Boat::withCount(['rentals' => fn($q) => $q->whereBetween('started_at', [$start, $end])])
            ->get()
            ->map(function ($boat) use ($totalHours, $start, $end) {
                $rentedHours = Rental::where('boat_id', $boat->id)
                    ->whereBetween('started_at', [$start, $end])
                    ->whereNotNull('actual_end_at')
                    ->get()
                    ->sum(fn($r) => $r->started_at->diffInHours($r->actual_end_at));

                return [
                    'boat_number' => $boat->boat_number,
                    'name' => $boat->name,
                    'rented_hours' => $rentedHours,
                    'total_hours' => $totalHours,
                    'utilization_pct' => $totalHours > 0 ? round(($rentedHours / $totalHours) * 100, 1) : 0,
                ];
            })->sortByDesc('utilization_pct');
    }

    public function workerPerformance(Carbon $start, Carbon $end)
    {
        return User::workers()
            ->get()
            ->map(function ($worker) use ($start, $end) {
                $rentals = $worker->rentals()->whereBetween('started_at', [$start, $end]);
                $totalRentals = (clone $rentals)->count();
                $completed = (clone $rentals)->where('status', RentalStatus::COMPLETED)->count();
                $overdue = (clone $rentals)->where('status', RentalStatus::OVERDUE)->count();

                $completedRentals = (clone $rentals)->whereNotNull('actual_end_at')->get();
                $avgDuration = $completedRentals->avg(fn($r) => $r->started_at->diffInMinutes($r->actual_end_at));

                return [
                    'name' => $worker->name,
                    'email' => $worker->email,
                    'total_rentals' => $totalRentals,
                    'completed' => $completed,
                    'overdue' => $overdue,
                    'avg_duration_minutes' => round($avgDuration ?? 0),
                    'reliability_score' => $totalRentals > 0
                        ? round(($completed / $totalRentals) * 100, 1) : 0,
                ];
            })->sortByDesc('reliability_score');
    }

    public function maintenanceHistory(Carbon $start, Carbon $end)
    {
        return \App\Models\MaintenanceRecord::with(['boat', 'admin'])
            ->whereBetween('started_at', [$start, $end])
            ->latest('started_at')
            ->get()
            ->map(fn($m) => [
                'boat_number' => $m->boat?->boat_number,
                'admin' => $m->admin?->name,
                'started_at' => $m->started_at->format('Y-m-d H:i'),
                'ended_at' => $m->ended_at?->format('Y-m-d H:i') ?? 'Ongoing',
                'duration_hours' => $m->ended_at
                    ? round($m->started_at->diffInHours($m->ended_at), 1) : null,
                'notes' => $m->notes,
            ]);
    }
}
