@extends('layouts.app')

@section('title', 'Maintenance Records')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Maintenance'],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-header mb-0">Maintenance Records</h1>
    <a href="{{ route('admin.boats.index') }}" class="clay-btn clay-btn-sm">
        <i class="bi bi-boat"></i> Manage Boats
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2" id="maintenance-filter-form">
        <div class="col-md-4 search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="clay-input form-control" placeholder="Search boat number..." value="{{ request('search') }}" id="maintenance-search" autocomplete="off">
        </div>
        <div class="col-md-3">
            <button type="submit" class="clay-btn clay-btn-primary"><i class="bi bi-search"></i> Search</button>
            <a href="{{ route('admin.maintenance.index') }}" class="clay-btn clay-btn-sm ms-2">Clear</a>
        </div>
    </form>
</div>

<div class="clay-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table clay-table mb-0">
            <thead>
                <tr>
                    <th>Boat #</th>
                    <th>Admin</th>
                    <th>Started</th>
                    <th>Ended</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="fw-semibold">{{ $record->boat?->boat_number ?? '—' }}</td>
                        <td>{{ $record->admin?->name ?? '—' }}</td>
                        <td>{{ $record->started_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $record->ended_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @if($record->ended_at)
                                {{ round($record->started_at->diffInHours($record->ended_at), 1) }} hrs
                            @else
                                <span class="text-warning">Ongoing</span>
                            @endif
                        </td>
                        <td>
                            @if($record->ended_at)
                                <span class="status-badge" style="background:#28a74520;color:#28a745;font-size:0.7rem;">Completed</span>
                            @else
                                <span class="status-badge" style="background:#ffc10720;color:#ffc107;font-size:0.7rem;">Active</span>
                            @endif
                        </td>
                        <td><small>{{ Str::limit($record->notes, 50) ?? '—' }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4">No maintenance records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ $records->total() }} entries
    </small>
    <nav aria-label="Page navigation">
        <ul class="clay-pagination pagination mb-0">
            <li class="page-item {{ $records->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $records->url(1) }}" aria-label="First">&laquo;&laquo;</a>
            </li>
            <li class="page-item {{ $records->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $records->previousPageUrl() }}" aria-label="Previous">&laquo;</a>
            </li>
            @php
                $start = max(1, $records->currentPage() - 2);
                $end = min($start + 4, $records->lastPage());
                if ($end - $start < 4) $start = max(1, $end - 4);
            @endphp
            @for ($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i === $records->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $records->url($i) }}">{{ $i }}</a>
                </li>
            @endfor
            <li class="page-item {{ !$records->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $records->nextPageUrl() }}" aria-label="Next">&raquo;</a>
            </li>
            <li class="page-item {{ !$records->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $records->url($records->lastPage()) }}" aria-label="Last">&raquo;&raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Prev / Next -->
<div class="nav-prev-next">
    <a href="{{ route('admin.reports.index') }}" class="nav-btn">
        <i class="bi bi-arrow-left"></i> Reports
    </a>
    <a href="{{ route('admin.settings.index') }}" class="nav-btn">
        Settings <i class="bi bi-arrow-right"></i>
    </a>
</div>
@endsection
