@extends('layouts.app')

@section('title', 'Reports')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Reports'],
    ];
@endphp

@section('content')
<h1 class="section-header">Reports</h1>

<!-- Report Type Selector -->
<div class="filter-bar">
    <form method="GET" class="row g-2 g-md-3 align-items-end">
        <div class="col-12 col-sm-6 col-md-2">
            <label class="form-label small fw-semibold">Report Type</label>
            <select name="type" class="clay-input form-select" onchange="this.form.submit()">
                <option value="daily" @selected(($type ?? 'daily') === 'daily')>Daily</option>
                <option value="weekly" @selected(($type ?? '') === 'weekly')>Weekly</option>
                <option value="monthly" @selected(($type ?? '') === 'monthly')>Monthly</option>
                <option value="utilization" @selected(($type ?? '') === 'utilization')>Boat Utilization</option>
                <option value="worker_performance" @selected(($type ?? '') === 'worker_performance')>Worker Performance</option>
                <option value="maintenance" @selected(($type ?? '') === 'maintenance')>Maintenance</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <label class="form-label small fw-semibold">Date From</label>
            <input type="date" name="date_from" class="clay-input form-control" value="{{ $date_from ?? date('Y-m-01') }}">
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <label class="form-label small fw-semibold">Date To</label>
            <input type="date" name="date_to" class="clay-input form-control" value="{{ $date_to ?? date('Y-m-d') }}">
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <button type="submit" class="clay-btn clay-btn-primary w-100">Generate</button>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                <a href="{{ route('admin.reports.export', ['type' => $type ?? 'daily', 'format' => 'pdf']) }}?{{ http_build_query(request()->except(['type', 'format'])) }}"
                   class="clay-btn clay-btn-sm clay-btn-danger d-inline-flex align-items-center gap-1">
                    <i class="bi bi-filetype-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.reports.export', ['type' => $type ?? 'daily', 'format' => 'excel']) }}?{{ http_build_query(request()->except(['type', 'format'])) }}"
                   class="clay-btn clay-btn-sm clay-btn-success d-inline-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a href="{{ route('admin.reports.export', ['type' => $type ?? 'daily', 'format' => 'csv']) }}?{{ http_build_query(request()->except(['type', 'format'])) }}"
                   class="clay-btn clay-btn-sm clay-btn-info d-inline-flex align-items-center gap-1">
                    <i class="bi bi-filetype-csv"></i> CSV
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Report Content -->
<div class="clay-card p-4">
    @if($type === 'daily' && isset($rentalsStarted))
        <div class="row g-2 g-md-3 text-center mb-4">
            <div class="col-6 col-md-3">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-primary)">{{ $rentalsStarted }}</div>
                    <small class="text-muted">Rentals Started</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-success)">{{ $rentalsEnded }}</div>
                    <small class="text-muted">Rentals Ended</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-info)">{{ $activeNow }}</div>
                    <small class="text-muted">Active Now</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-danger)">{{ $overdue }}</div>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>

        @if(isset($workerActivity) && count($workerActivity) > 0)
            <h5 class="fw-bold mb-3">Worker Activity</h5>
            <div class="table-responsive">
                <table class="table clay-table">
                    <thead><tr><th>Worker</th><th>Rentals</th></tr></thead>
                    <tbody>
                        @foreach($workerActivity as $wa)
                            <tr><td>{{ $wa['name'] }}</td><td>{{ $wa['rentals'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    @elseif($type === 'weekly' && isset($dailyAggregates))
        <h5 class="fw-bold mb-3">Weekly Summary</h5>
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="clay-card-sm p-3 text-center">
                    <div class="fw-bold fs-3" style="color:var(--clay-info)">{{ round($avgDuration ?? 0) }}</div>
                    <small class="text-muted">Avg Duration (min)</small>
                </div>
            </div>
        </div>

        @if(isset($mostUsed) && count($mostUsed) > 0)
            <h5 class="fw-bold mb-3 mt-4">Most Used Boats</h5>
            <div class="table-responsive">
                <table class="table clay-table">
                    <thead><tr><th>Boat #</th><th>Rentals</th></tr></thead>
                    <tbody>
                        @foreach($mostUsed as $boat)
                            <tr><td>{{ $boat->boat_number }}</td><td>{{ $boat->rentals_count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    @elseif($type === 'monthly' && isset($totalRentals))
        <div class="row g-2 g-md-3 text-center mb-4">
            <div class="col-6 col-md-4">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-primary)">{{ $totalRentals }}</div>
                    <small class="text-muted">Total Rentals</small>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-success)">{{ $totalCompleted }}</div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="clay-card-sm p-3">
                    <div class="fw-bold fs-3" style="color:var(--clay-danger)">{{ $totalOverdue }}</div>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>

        @if(isset($utilization) && count($utilization) > 0)
            <h5 class="fw-bold mb-3 mt-4">Boat Utilization</h5>
            <div class="table-responsive">
                <table class="table clay-table">
                    <thead><tr><th>Boat #</th><th>Name</th><th>Rentals</th><th>Utilization %</th></tr></thead>
                    <tbody>
                        @foreach($utilization as $u)
                            <tr>
                                <td class="text-nowrap">{{ $u['boat_number'] }}</td>
                                <td class="text-nowrap">{{ $u['name'] ?? '—' }}</td>
                                <td>{{ $u['rentals'] }}</td>
                                <td style="min-width:120px;">
                                    <div class="progress" style="height:20px;border-radius:10px;">
                                        <div class="progress-bar" style="width:{{ $u['utilization_pct'] }}%;background:var(--clay-primary);">
                                            {{ $u['utilization_pct'] }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    @elseif($type === 'utilization' && isset($utilization))
        <h5 class="fw-bold mb-3">Boat Utilization ({{ $date_from ?? '' }} to {{ $date_to ?? '' }})</h5>
        <div class="table-responsive">
            <table class="table clay-table">
                <thead><tr><th>Boat #</th><th>Name</th><th>Rented Hours</th><th>Utilization %</th></tr></thead>
                <tbody>
                    @foreach($utilization as $u)
                        <tr>
                            <td class="text-nowrap">{{ $u['boat_number'] }}</td>
                            <td class="text-nowrap">{{ $u['name'] ?? '—' }}</td>
                            <td>{{ $u['rented_hours'] }}</td>
                            <td style="min-width:120px;">
                                <div class="progress" style="height:20px;border-radius:10px;">
                                    <div class="progress-bar" style="width:{{ $u['utilization_pct'] }}%;background:var(--clay-primary);">
                                        {{ $u['utilization_pct'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @elseif($type === 'worker_performance' && isset($performance))
        <h5 class="fw-bold mb-3">Worker Performance ({{ $date_from ?? '' }} to {{ $date_to ?? '' }})</h5>
        <div class="table-responsive">
            <table class="table clay-table">
                <thead><tr><th>Worker</th><th>Total</th><th>Completed</th><th>Overdue</th><th>Avg Duration</th><th>Reliability</th></tr></thead>
                <tbody>
                    @foreach($performance as $p)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $p['name'] }}</td>
                            <td>{{ $p['total_rentals'] }}</td>
                            <td>{{ $p['completed'] }}</td>
                            <td>{{ $p['overdue'] }}</td>
                            <td class="text-nowrap">{{ $p['avg_duration_minutes'] }} min</td>
                            <td style="min-width:120px;">
                                <div class="progress" style="height:20px;border-radius:10px;">
                                    <div class="progress-bar" style="width:{{ $p['reliability_score'] }}%;background:var(--clay-success);">
                                        {{ $p['reliability_score'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @elseif($type === 'maintenance' && isset($maintenance))
        <h5 class="fw-bold mb-3">Maintenance History ({{ $date_from ?? '' }} to {{ $date_to ?? '' }})</h5>
        <div class="table-responsive">
            <table class="table clay-table">
                <thead><tr><th>Boat #</th><th>Admin</th><th>Started</th><th>Ended</th><th>Duration</th><th>Notes</th></tr></thead>
                <tbody>
                    @forelse($maintenance as $m)
                        <tr>
                            <td class="text-nowrap">{{ $m['boat_number'] }}</td>
                            <td class="text-nowrap">{{ $m['admin'] }}</td>
                            <td class="text-nowrap">{{ $m['started_at'] }}</td>
                            <td class="text-nowrap">{{ $m['ended_at'] }}</td>
                            <td class="text-nowrap">{{ $m['duration_hours'] ? $m['duration_hours'] . ' hrs' : '—' }}</td>
                            <td><small>{{ $m['notes'] ?? '—' }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-3">No maintenance records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p class="text-center text-muted py-4">Select a report type and date range to generate data.</p>
    @endif
</div>

<!-- Prev / Next -->
<div class="nav-prev-next">
    <a href="{{ route('admin.activity-logs.index') }}" class="nav-btn">
        <i class="bi bi-arrow-left"></i> Activity Logs
    </a>
    <a href="{{ route('admin.maintenance.index') }}" class="nav-btn">
        Maintenance <i class="bi bi-arrow-right"></i>
    </a>
</div>

@push('styles')
<style>
    /* ── Mobile responsive stat cards ── */
    @media (max-width: 575.98px) {
        .clay-card-sm.p-3 .fs-3 {
            font-size: 1.5rem !important;
        }
        .clay-card-sm.p-3 {
            padding: 0.75rem !important;
        }
        .clay-card-sm.p-3 small {
            font-size: 0.7rem;
        }
    }

    /* ── Table cell no-wrap for mobile readability ── */
    .table-responsive .text-nowrap {
        white-space: nowrap;
    }

    /* ── Filter bar spacing on mobile ── */
    @media (max-width: 767.98px) {
        .filter-bar form .clay-btn.w-100 {
            margin-top: 0;
        }
        .filter-bar form .d-flex.flex-wrap {
            margin-top: 0.25rem;
        }
    }
</style>
@endpush
@endsection
