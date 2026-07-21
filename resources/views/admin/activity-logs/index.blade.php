@extends('layouts.app')

@section('title', 'Activity Logs')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Activity Logs'],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-header mb-0">Activity Logs</h1>
    <a href="{{ route('admin.activity-logs.export') }}?{{ http_build_query(request()->all()) }}"
       class="clay-btn clay-btn-success">
        <i class="bi bi-download"></i> Export
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2" id="log-filter-form">
        <div class="col-md-3 search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="clay-input form-control" placeholder="Instant search..." value="{{ request('search') }}" id="log-search" autocomplete="off">
        </div>
        <div class="col-md-2">
            <select name="action" class="clay-input form-select" id="log-action">
                <option value="">All Actions</option>
                <option value="login_success" @selected(request('action') === 'login_success')>Login Success</option>
                <option value="login_failed" @selected(request('action') === 'login_failed')>Login Failed</option>
                <option value="logout" @selected(request('action') === 'logout')>Logout</option>
                <option value="boat_started" @selected(request('action') === 'boat_started')>Boat Started</option>
                <option value="rental_ended" @selected(request('action') === 'rental_ended')>Rental Ended</option>
                <option value="boat_confirmed" @selected(request('action') === 'boat_confirmed')>Return Confirmed</option>
                <option value="boat_overdue" @selected(request('action') === 'boat_overdue')>Boat Overdue</option>
                <option value="time_extended" @selected(request('action') === 'time_extended')>Time Extended</option>
                <option value="time_reduced" @selected(request('action') === 'time_reduced')>Time Reduced</option>
                <option value="rental_overridden" @selected(request('action') === 'rental_overridden')>Force Ended</option>
                <option value="rental_completed" @selected(request('action') === 'rental_completed')>Admin Completed</option>
                <option value="boat_moved_to_maintenance" @selected(request('action') === 'boat_moved_to_maintenance')>Moved to Maintenance</option>
                <option value="boat_removed_from_maintenance" @selected(request('action') === 'boat_removed_from_maintenance')>Removed from Maintenance</option>
                <option value="settings_updated" @selected(request('action') === 'settings_updated')>Settings Updated</option>
                <option value="backup_created" @selected(request('action') === 'backup_created')>Backup Created</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="clay-input form-control" value="{{ request('date_from') }}" id="log-date-from">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="clay-input form-control" value="{{ request('date_to') }}" id="log-date-to">
        </div>
        <div class="col-md-3">
            <button type="submit" class="clay-btn clay-btn-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="{{ route('admin.activity-logs.index') }}" class="clay-btn clay-btn-sm ms-2">Clear</a>

            <!-- Rows per page -->
            <div class="rows-per-page d-inline-flex ms-3">
                <small class="text-muted">Rows:</small>
                <select name="per_page" id="per-page-select" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $count)
                        <option value="{{ $count }}" @selected((request('per_page', 50)) == $count)>{{ $count }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

<div class="clay-card p-0 overflow-hidden">
    <table class="table clay-table mb-0">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td><small>{{ $log->created_at?->format('Y-m-d H:i:s') }}</small></td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>
                        <span class="status-badge" style="background:var(--clay-primary)20;color:var(--clay-primary);font-size:0.7rem;">
                            {{ str_replace('_', ' ', $log->action) }}
                        </span>
                    </td>
                    <td><small>{{ Str::limit($log->details, 80) }}</small></td>
                    <td><small class="text-muted">{{ $log->ip_address ?? '—' }}</small></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">No activity logs found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination with Prev/Next, First/Last, page numbers -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
    </small>
    <nav aria-label="Page navigation">
        <ul class="clay-pagination pagination mb-0">
            {{-- First Page --}}
            <li class="page-item {{ $logs->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $logs->url(1) }}" aria-label="First">&laquo;&laquo;</a>
            </li>
            {{-- Prev Page --}}
            <li class="page-item {{ $logs->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $logs->previousPageUrl() }}" aria-label="Previous">&laquo;</a>
            </li>
            {{-- Page Numbers --}}
            @php
                $start = max(1, $logs->currentPage() - 2);
                $end = min($start + 4, $logs->lastPage());
                if ($end - $start < 4) $start = max(1, $end - 4);
            @endphp
            @for ($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i === $logs->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $logs->url($i) }}">{{ $i }}</a>
                </li>
            @endfor
            {{-- Next Page --}}
            <li class="page-item {{ !$logs->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $logs->nextPageUrl() }}" aria-label="Next">&raquo;</a>
            </li>
            {{-- Last Page --}}
            <li class="page-item {{ !$logs->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $logs->url($logs->lastPage()) }}" aria-label="Last">&raquo;&raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Auto-submit on filter change for instant feel -->
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Instant search with debounce
        const searchInput = document.getElementById('log-search');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('log-filter-form').submit();
                }, 400);
            });
        }

        // Auto-submit on action/date changes
        ['log-action', 'log-date-from', 'log-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', function() {
                document.getElementById('log-filter-form').submit();
            });
        });
    });
</script>
@endpush
@endsection
