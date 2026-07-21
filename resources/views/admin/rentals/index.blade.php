@extends('layouts.app')

@section('title', 'Rentals')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Rentals'],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-header mb-0">Rentals</h1>
    <a href="{{ route('dashboard') }}" class="clay-btn clay-btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2" id="rental-filter-form">
        <div class="col-md-3 search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="clay-input form-control" placeholder="Search boat #, worker name/ID..." value="{{ request('search') }}" id="rental-search" autocomplete="off">
        </div>
        <div class="col-md-2">
            <select name="status" class="clay-input form-select" id="rental-status">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
                <option value="awaiting_confirmation" @selected(request('status') === 'awaiting_confirmation')>Awaiting</option>
                <option value="overridden" @selected(request('status') === 'overridden')>Overridden</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="clay-input form-control" value="{{ request('date_from') }}" id="rental-date-from" placeholder="From">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="clay-input form-control" value="{{ request('date_to') }}" id="rental-date-to" placeholder="To">
        </div>
        <div class="col-md-3">
            <button type="submit" class="clay-btn clay-btn-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="{{ route('admin.rentals.index') }}" class="clay-btn clay-btn-sm ms-2">Clear</a>
            <div class="rows-per-page d-inline-flex ms-2">
                <small class="text-muted">Rows:</small>
                <select name="per_page" id="per-page-select" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $count)
                        <option value="{{ $count }}" @selected((request('per_page', 25)) == $count)>{{ $count }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

<div class="clay-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table clay-table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Boat #</th>
                    <th>Worker</th>
                    <th>Started</th>
                    <th>Expected End</th>
                    <th>Actual End</th>
                    <th>Ext/Red</th>
                    <th>Overtime</th>
                    <th>Status</th>
                    <th>Ended By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                    <tr>
                        <td>{{ $rental->id }}</td>
                        <td class="fw-semibold">{{ $rental->boat?->boat_number ?? '—' }}</td>
                        <td>
                            {{ $rental->worker?->name ?? '—' }}
                            <small class="text-muted d-block">#{{ $rental->worker_id }}</small>
                        </td>
                        <td><small>{{ $rental->started_at?->format('H:i:s') }}<br><span class="text-muted">{{ $rental->started_at?->format('m/d') }}</span></small></td>
                        <td><small>{{ $rental->effective_end_at?->format('H:i:s') }}</small></td>
                        <td><small>{{ $rental->actual_end_at?->format('H:i:s') ?? '—' }}</small></td>
                        <td>
                            @if($rental->extended_minutes > 0)
                                <small class="text-info">+{{ $rental->extended_minutes }}m</small><br>
                            @endif
                            @if($rental->reduced_minutes > 0)
                                <small class="text-warning">-{{ $rental->reduced_minutes }}m</small>
                            @endif
                            @if($rental->extended_minutes === 0 && $rental->reduced_minutes === 0)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($rental->overtime_seconds > 0)
                                <span class="text-danger fw-bold">+{{ gmdate('H:i:s', $rental->overtime_seconds) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge" style="background:{{ match($rental->status->value) {
                                'active' => '#0d6efd',
                                'completed' => '#28a745',
                                'overdue' => '#dc3545',
                                'awaiting_confirmation' => '#fd7e14',
                                'overridden' => '#6c757d',
                                default => '#6c757d'
                            } }}20;color:{{ match($rental->status->value) {
                                'active' => '#0d6efd',
                                'completed' => '#28a745',
                                'overdue' => '#dc3545',
                                'awaiting_confirmation' => '#fd7e14',
                                'overridden' => '#6c757d',
                                default => '#6c757d'
                            } }};font-size:0.7rem;">
                                {{ $rental->status->label() }}
                            </span>
                            @if($rental->admin_override)
                                <br><small class="text-muted"><i class="bi bi-shield"></i> Override</small>
                            @endif
                        </td>
                        <td><small>{{ $rental->endedBy?->name ?? '—' }}</small></td>
                        <td>
                            @if(in_array($rental->status->value, ['active', 'overdue']))
                                <div class="d-flex gap-1 flex-wrap">
                                    <button class="clay-btn clay-btn-sm clay-btn-info" style="padding:2px 8px;font-size:0.7rem;" onclick="openExtendModal({{ $rental->id }}, {{ $rental->boat?->boat_number }})">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                    <button class="clay-btn clay-btn-sm clay-btn-warning" style="padding:2px 8px;font-size:0.7rem;" onclick="openReduceModal({{ $rental->id }}, {{ $rental->boat?->boat_number }})">
                                        <i class="bi bi-dash-circle"></i>
                                    </button>
                                    <button class="clay-btn clay-btn-sm clay-btn-danger" style="padding:2px 8px;font-size:0.7rem;" onclick="forceEndRental({{ $rental->id }})">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </button>
                                    <button class="clay-btn clay-btn-sm clay-btn-success" style="padding:2px 8px;font-size:0.7rem;" onclick="completeRental({{ $rental->id }})">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-4">No rentals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        Showing {{ $rentals->firstItem() ?? 0 }}–{{ $rentals->lastItem() ?? 0 }} of {{ $rentals->total() }} entries
    </small>
    <nav aria-label="Page navigation">
        <ul class="clay-pagination pagination mb-0">
            <li class="page-item {{ $rentals->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $rentals->url(1) }}" aria-label="First">&laquo;&laquo;</a>
            </li>
            <li class="page-item {{ $rentals->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $rentals->previousPageUrl() }}" aria-label="Previous">&laquo;</a>
            </li>
            @php
                $start = max(1, $rentals->currentPage() - 2);
                $end = min($start + 4, $rentals->lastPage());
                if ($end - $start < 4) $start = max(1, $end - 4);
            @endphp
            @for ($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i === $rentals->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $rentals->url($i) }}">{{ $i }}</a>
                </li>
            @endfor
            <li class="page-item {{ !$rentals->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $rentals->nextPageUrl() }}" aria-label="Next">&raquo;</a>
            </li>
            <li class="page-item {{ !$rentals->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $rentals->url($rentals->lastPage()) }}" aria-label="Last">&raquo;&raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Prev / Next navigation -->
<div class="nav-prev-next">
    <a href="{{ route('admin.workers.index') }}" class="nav-btn">
        <i class="bi bi-arrow-left"></i> Workers
    </a>
    <a href="{{ route('admin.activity-logs.index') }}" class="nav-btn">
        Activity Logs <i class="bi bi-arrow-right"></i>
    </a>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Instant search with debounce
        const searchInput = document.getElementById('rental-search');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('rental-filter-form').submit();
                }, 400);
            });
        }

        // Auto-submit on status/date changes
        ['rental-status', 'rental-date-from', 'rental-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', function() {
                document.getElementById('rental-filter-form').submit();
            });
        });
    });
</script>
@endpush
