@extends('layouts.app')

@section('title', 'Workers')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Workers'],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-header mb-0">Workers</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.workers.create') }}" class="clay-btn clay-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Worker
        </a>
    </div>
</div>

<!-- Search & Filter -->
<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end" id="worker-filter-form">
        <div class="col-md-4 col-sm-6">
            <label class="form-label small text-muted mb-1">Search</label>
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="clay-input form-control" placeholder="Name, email, or ID..." value="{{ request('search') }}" id="worker-search" autocomplete="off">
            </div>
        </div>
        <div class="col-md-2 col-sm-3">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="clay-input form-select" id="worker-status">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-3">
            <label class="form-label small text-muted mb-1">&nbsp;</label>
            <div class="d-flex gap-1 align-items-center flex-wrap">
                <button type="submit" class="clay-btn clay-btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                <a href="{{ route('admin.workers.index') }}" class="clay-btn clay-btn-sm">Clear</a>
                <div class="rows-per-page d-inline-flex align-items-center ms-1">
                    <small class="text-muted me-1">Rows:</small>
                    <select name="per_page" onchange="this.form.submit()" class="form-select form-select-sm" style="width:auto;">
                        @foreach([10, 25, 50, 100] as $count)
                            <option value="{{ $count }}" @selected((request('per_page', 25)) == $count)>{{ $count }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Online</th>
                    <th>Last Activity</th>
                    <th>Active Rentals</th>
                    <th>Total Rentals</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $worker)
                    <tr>
                        <td>{{ $worker->id }}</td>
                        <td class="fw-semibold">{{ $worker->name }}</td>
                        <td>{{ $worker->email }}</td>
                        <td>
                            @if($worker->is_active)
                                <span class="status-badge" style="background:#28a74520;color:#28a745;">Active</span>
                            @else
                                <span class="status-badge" style="background:#dc354520;color:#dc3545;">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <span class="online-indicator {{ $worker->isOnline() ? 'online' : 'offline' }}"></span>
                            {{ $worker->isOnline() ? 'Online' : 'Offline' }}
                        </td>
                        <td>{{ $worker->last_activity_at ? $worker->last_activity_at->diffForHumans() : 'Never' }}</td>
                        <td>{{ $worker->current_rental_id ? 'Yes' : 'No' }}</td>
                        <td>{{ $worker->rentals()->count() }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-nowrap">
                                <a href="{{ route('admin.workers.edit', $worker) }}" class="clay-btn clay-btn-sm clay-btn-info" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.workers.toggle-status', $worker) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="clay-btn clay-btn-sm {{ $worker->is_active ? 'clay-btn-warning' : 'clay-btn-success' }}" title="{{ $worker->is_active ? 'Disable' : 'Enable' }}">
                                        <i class="bi {{ $worker->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                    </button>
                                </form>
                                <button class="clay-btn clay-btn-sm clay-btn-dark" onclick="resetPassword({{ $worker->id }})" title="Reset Password">
                                    <i class="bi bi-key"></i>
                                </button>
                                <form action="{{ route('admin.workers.destroy', $worker) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this worker?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="clay-btn clay-btn-sm clay-btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4">No workers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        Showing {{ $workers->firstItem() ?? 0 }}–{{ $workers->lastItem() ?? 0 }} of {{ $workers->total() }} entries
    </small>
    <nav aria-label="Page navigation">
        <ul class="clay-pagination pagination mb-0">
            <li class="page-item {{ $workers->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $workers->url(1) }}" aria-label="First">&laquo;&laquo;</a>
            </li>
            <li class="page-item {{ $workers->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $workers->previousPageUrl() }}" aria-label="Previous">&laquo;</a>
            </li>
            @php
                $start = max(1, $workers->currentPage() - 2);
                $end = min($start + 4, $workers->lastPage());
                if ($end - $start < 4) $start = max(1, $end - 4);
            @endphp
            @for ($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i === $workers->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $workers->url($i) }}">{{ $i }}</a>
                </li>
            @endfor
            <li class="page-item {{ !$workers->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $workers->nextPageUrl() }}" aria-label="Next">&raquo;</a>
            </li>
            <li class="page-item {{ !$workers->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $workers->url($workers->lastPage()) }}" aria-label="Last">&raquo;&raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Prev / Next -->
<div class="nav-prev-next">
    <a href="{{ route('admin.boats.index') }}" class="nav-btn">
        <i class="bi bi-arrow-left"></i> Boats
    </a>
    <a href="{{ route('admin.rentals.index') }}" class="nav-btn">
        Rentals <i class="bi bi-arrow-right"></i>
    </a>
</div>

<!-- Reset Password Modal -->
<div class="modal fade clay-modal" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="resetPasswordForm">
            @csrf @method('POST')
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="clay-input form-control" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="clay-input form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="clay-btn clay-btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="clay-btn clay-btn-primary clay-btn-sm">Reset Password</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function resetPassword(workerId) {
        document.getElementById('resetPasswordForm').action = '{{ url("admin/workers") }}/' + workerId + '/reset-password';
        new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('worker-search');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('worker-filter-form').submit();
                }, 400);
            });
        }

        const statusSelect = document.getElementById('worker-status');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                document.getElementById('worker-filter-form').submit();
            });
        }
    });
</script>
@endpush
