@extends('layouts.app')

@section('title', 'Boats')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Boats'],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-header mb-0">Boats</h1>
    <a href="{{ route('admin.boats.create') }}" class="clay-btn clay-btn-primary">
        <i class="bi bi-plus-lg"></i> Add Boat
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2" id="boat-filter-form">
        <div class="col-md-4 search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="clay-input form-control" placeholder="Search boat number or name..." value="{{ request('search') }}" id="boat-search" autocomplete="off">
        </div>
        <div class="col-md-2">
            <select name="status" class="clay-input form-select" id="boat-status">
                <option value="">All Status</option>
                <option value="available" @selected(request('status') === 'available')>Available</option>
                <option value="occupied" @selected(request('status') === 'occupied')>Occupied</option>
                <option value="warning" @selected(request('status') === 'warning')>Warning</option>
                <option value="awaiting_confirmation" @selected(request('status') === 'awaiting_confirmation')>Awaiting</option>
                <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
                <option value="maintenance" @selected(request('status') === 'maintenance')>Maintenance</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="clay-btn clay-btn-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="{{ route('admin.boats.index') }}" class="clay-btn clay-btn-sm ms-2">Clear</a>
            <div class="rows-per-page d-inline-flex ms-2">
                <small class="text-muted">Rows:</small>
                <select name="per_page" onchange="this.form.submit()">
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
                    <th>#</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Current Worker</th>
                    <th>Started / Ends</th>
                    <th>Timer</th>
                    <th>Total Rentals</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($boats as $boat)
                    @php
                        $rental = $boat->currentRental;
                        $remaining = $rental ? max(0, now()->diffInSeconds($rental->effective_end_at, false)) : 0;
                        $isOverdue = $rental && now()->gt($rental->effective_end_at);
                    @endphp
                    <tr>
                        <td class="fw-bold">{{ $boat->boat_number }}</td>
                        <td>{{ $boat->name ?? '—' }}</td>
                        <td>
                            <span class="status-badge" style="background:{{ $boat->status->color() }}20;color:{{ $boat->status->color() }};font-size:0.7rem;">
                                {{ $boat->status->label() }}
                            </span>
                        </td>
                        <td>
                            @if($rental)
                                {{ $rental->worker?->name ?? '—' }}
                                <small class="text-muted d-block">#{{ $rental->worker_id }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($rental)
                                <small>{{ $rental->started_at?->format('H:i') }} / {{ $rental->effective_end_at?->format('H:i') }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($rental)
                                @if($isOverdue)
                                    <span class="text-danger fw-bold">+{{ sprintf('%02d:%02d', floor(abs(now()->diffInSeconds($rental->effective_end_at, false)) / 60), abs(now()->diffInSeconds($rental->effective_end_at, false)) % 60) }}</span>
                                @else
                                    <span>{{ sprintf('%02d:%02d', floor($remaining / 60), $remaining % 60) }}</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $boat->rentals_count }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.boats.edit', $boat) }}" class="clay-btn clay-btn-sm clay-btn-info" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($rental && ($boat->status->value === 'occupied' || $boat->status->value === 'warning' || $boat->status->value === 'overdue'))
                                    <button class="clay-btn clay-btn-sm clay-btn-info" style="padding:2px 8px;font-size:0.7rem;" onclick="openExtendModal({{ $rental->id }}, {{ $boat->boat_number }})" title="Extend">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                    <button class="clay-btn clay-btn-sm clay-btn-warning" style="padding:2px 8px;font-size:0.7rem;" onclick="openReduceModal({{ $rental->id }}, {{ $boat->boat_number }})" title="Reduce">
                                        <i class="bi bi-dash-circle"></i>
                                    </button>
                                    <button class="clay-btn clay-btn-sm clay-btn-danger" style="padding:2px 8px;font-size:0.7rem;" onclick="forceEndRental({{ $rental->id }})" title="Force End">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </button>
                                @endif
                                @if($boat->status->value === 'maintenance')
                                    <button class="clay-btn clay-btn-sm clay-btn-success" onclick="removeFromMaintenance({{ $boat->id }})" title="Make Available">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                @else
                                    <form action="{{ route('admin.boats.maintenance', $boat) }}" method="POST" class="d-inline" onsubmit="return confirm('Move this boat to maintenance?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="clay-btn clay-btn-sm clay-btn-warning" title="Move to Maintenance">
                                            <i class="bi bi-wrench"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.boats.destroy', $boat) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this boat?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="clay-btn clay-btn-sm clay-btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4">No boats found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        Showing {{ $boats->firstItem() ?? 0 }}–{{ $boats->lastItem() ?? 0 }} of {{ $boats->total() }} entries
    </small>
    <nav aria-label="Page navigation">
        <ul class="clay-pagination pagination mb-0">
            <li class="page-item {{ $boats->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $boats->url(1) }}" aria-label="First">&laquo;&laquo;</a>
            </li>
            <li class="page-item {{ $boats->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $boats->previousPageUrl() }}" aria-label="Previous">&laquo;</a>
            </li>
            @php
                $start = max(1, $boats->currentPage() - 2);
                $end = min($start + 4, $boats->lastPage());
                if ($end - $start < 4) $start = max(1, $end - 4);
            @endphp
            @for ($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i === $boats->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $boats->url($i) }}">{{ $i }}</a>
                </li>
            @endfor
            <li class="page-item {{ !$boats->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $boats->nextPageUrl() }}" aria-label="Next">&raquo;</a>
            </li>
            <li class="page-item {{ !$boats->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $boats->url($boats->lastPage()) }}" aria-label="Last">&raquo;&raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Prev / Next -->
<div class="nav-prev-next">
    <a href="{{ route('admin.settings.index') }}" class="nav-btn">
        <i class="bi bi-arrow-left"></i> Settings
    </a>
    <a href="{{ route('admin.workers.index') }}" class="nav-btn">
        Workers <i class="bi bi-arrow-right"></i>
    </a>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('boat-search');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('boat-filter-form').submit();
                }, 400);
            });
        }

        const statusSelect = document.getElementById('boat-status');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                document.getElementById('boat-filter-form').submit();
            });
        }
    });
</script>
@endpush
