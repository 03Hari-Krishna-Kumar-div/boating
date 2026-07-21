@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-0">
    <!-- Stats Cards -->
    <div class="row g-3 mb-4" id="stats-container">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="clay-card stat-card">
                <div class="stat-number" style="color:var(--clay-success)" id="stat-available">0</div>
                <div class="stat-label">Available</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="clay-card stat-card">
                <div class="stat-number" style="color:var(--clay-primary)" id="stat-active">0</div>
                <div class="stat-label">Active Rentals</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="clay-card stat-card">
                <div class="stat-number" style="color:var(--clay-warning)" id="stat-warning">0</div>
                <div class="stat-label">Warning</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="clay-card stat-card">
                <div class="stat-number" style="color:var(--clay-danger)" id="stat-overdue">0</div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="clay-card stat-card">
                <div class="stat-number" style="color:var(--clay-info)" id="stat-online">0</div>
                <div class="stat-label">Online Workers</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="clay-card stat-card">
                <div class="stat-number" style="color:var(--clay-text)" id="stat-total">0</div>
                <div class="stat-label">Total Boats</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" class="clay-input form-control" id="search-input" placeholder="Search boat number...">
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 flex-wrap" id="status-filters">
                    <button class="clay-btn clay-btn-sm filter-btn active" data-status="all">All</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="available" style="background:#28a745;color:white;">Available</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="occupied" style="background:#0d6efd;color:white;">Active</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="warning" style="background:#ffc107;color:#2d3436;">Warning</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="time_up" style="background:#dc3545;color:white;">Time Up</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="ended" style="background:#6c5ce7;color:white;">Ended</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="overdue" style="background:#dc3545;color:white;">Overdue</button>
                    <button class="clay-btn clay-btn-sm filter-btn" data-status="maintenance" style="background:#6c757d;color:white;">Maintenance</button>
                </div>
            </div>
            <div class="col-md-3 text-md-end">
                <span class="text-muted small" id="last-update">Updating...</span>
            </div>
        </div>
    </div>

    <!-- Server Time -->
    <div class="text-center mb-3">
        <span class="badge bg-dark" id="server-time-display" data-server-clock>--:--:--</span>
    </div>

    @if(auth()->user()->isAdmin())
        <!-- ADMIN: Single grid -->
        <div id="boat-cards-container" class="row g-3">
            @foreach($boats as $boat)
                @include('dashboard._boat-card', ['boat' => $boat])
            @endforeach
        </div>
    @else
        <!-- WORKER: My Active / Available / Other sections -->
        @php
            $myBoats = $boats->filter(fn($b) => $b->currentRental && $b->currentRental->worker_id === auth()->id());
            $availableBoats = $boats->filter(fn($b) => $b->status->value === 'available');
            $otherBoats = $boats->filter(fn($b) => !$myBoats->contains('id', $b->id) && !$availableBoats->contains('id', $b->id));
        @endphp

        @if($myBoats->isNotEmpty())
        <div class="mb-4" id="worker-my-section">
            <h4 class="section-header mb-3"><i class="bi bi-boat"></i> My Active Boats</h4>
            <div class="row g-3" id="worker-my-boats">
                @foreach($myBoats as $boat)
                    @include('dashboard._boat-card', ['boat' => $boat])
                @endforeach
            </div>
        </div>
        @endif

        @if($availableBoats->isNotEmpty())
        <div class="mb-4" id="worker-available-section">
            <h4 class="section-header mb-3"><i class="bi bi-compass"></i> Available Boats</h4>
            <div class="row g-3" id="worker-available-boats">
                @foreach($availableBoats as $boat)
                    @include('dashboard._boat-card', ['boat' => $boat])
                @endforeach
            </div>
        </div>
        @endif

        @if($otherBoats->isNotEmpty())
        <div class="mb-4" id="worker-other-section">
            <h4 class="section-header mb-3 text-muted"><i class="bi bi-lock"></i> Other Boats</h4>
            <p class="text-muted small mb-3">Boats currently in use by other workers.</p>
            <div class="row g-3" id="worker-other-boats">
                @foreach($otherBoats as $boat)
                    @include('dashboard._boat-card', ['boat' => $boat])
                @endforeach
            </div>
        </div>
        @endif
    @endif
</div>

<!-- Extend Time Modal -->
<div id="extend-modal" class="time-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;">
    <div class="clay-card p-4" style="max-width:400px;width:90%;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Extend Time</h5>
            <button class="btn-close close-modal"></button>
        </div>
        <p class="text-muted" id="extend-boat-info"></p>
        <input type="hidden" id="extend-rental-id">

        <div class="mb-3">
            <label class="form-label fw-semibold">Quick Select</label>
            <div class="d-flex gap-2 flex-wrap" id="extend-presets">
                <button class="clay-btn clay-btn-sm preset-btn" data-minutes="5">+5 min</button>
                <button class="clay-btn clay-btn-sm preset-btn" data-minutes="10">+10 min</button>
                <button class="clay-btn clay-btn-sm preset-btn" data-minutes="15">+15 min</button>
                <button class="clay-btn clay-btn-sm preset-btn" data-minutes="30">+30 min</button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Custom Minutes</label>
            <input type="number" class="clay-input form-control" id="extend-custom-minutes" min="1" max="120" placeholder="Enter minutes...">
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button class="clay-btn clay-btn-secondary close-modal">Cancel</button>
            <button class="clay-btn clay-btn-info" id="confirm-extend">Confirm Extend</button>
        </div>
    </div>
</div>

<!-- Reduce Time Modal -->
<div id="reduce-modal" class="time-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;">
    <div class="clay-card p-4" style="max-width:400px;width:90%;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Reduce Time</h5>
            <button class="btn-close close-modal"></button>
        </div>
        <p class="text-muted" id="reduce-boat-info"></p>
        <input type="hidden" id="reduce-rental-id">

        <div class="mb-3">
            <label class="form-label fw-semibold">Quick Select</label>
            <div class="d-flex gap-2 flex-wrap" id="reduce-presets">
                <button class="clay-btn clay-btn-sm preset-btn" data-minutes="5">-5 min</button>
                <button class="clay-btn clay-btn-sm preset-btn" data-minutes="10">-10 min</button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Custom Minutes</label>
            <input type="number" class="clay-input form-control" id="reduce-custom-minutes" min="1" max="120" placeholder="Enter minutes...">
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button class="clay-btn clay-btn-secondary close-modal">Cancel</button>
            <button class="clay-btn clay-btn-warning" id="confirm-reduce">Confirm Reduce</button>
        </div>
    </div>
</div>

<!-- Transfer Ownership Modal -->
<div id="transfer-modal" class="time-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;">
    <div class="clay-card p-4" style="max-width:400px;width:90%;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Transfer Ownership</h5>
            <button class="btn-close close-modal"></button>
        </div>
        <p class="text-muted" id="transfer-boat-info"></p>
        <input type="hidden" id="transfer-rental-id">

        <div class="mb-3">
            <label class="form-label fw-semibold">Select Worker</label>
            <select class="clay-input form-select" id="transfer-worker-id">
                <option value="">Choose worker...</option>
                @foreach(\App\Models\User::workers()->active()->get() as $worker)
                    <option value="{{ $worker->id }}">{{ $worker->name }} (#{{ $worker->id }})</option>
                @endforeach
            </select>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button class="clay-btn clay-btn-secondary close-modal">Cancel</button>
            <button class="clay-btn clay-btn-info" id="confirm-transfer">Transfer</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.currentWorkerId = {{ auth()->id() }};
    window.userRole = '{{ auth()->user()->role->value }}';
    window.isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
</script>
@endpush
