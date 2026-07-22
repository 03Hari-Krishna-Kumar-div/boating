@php
    $isAdmin = auth()->user()->isAdmin();
    $userId = auth()->id();
    $currentRental = $boat->currentRental;
    $isOwner = $currentRental && $currentRental->worker_id === $userId;
    $canAct = $isAdmin || $isOwner;
    $status = $boat->status->value;
    $remainingSeconds = $currentRental ? max(0, now()->diffInSeconds($currentRental->effective_end_at, false)) : 0;
    $overtimeSeconds = $currentRental && now()->gt($currentRental->effective_end_at) ? abs(now()->diffInSeconds($currentRental->effective_end_at, false)) : 0;
    $totalMinutes = $currentRental ? max(1, $currentRental->started_at->diffInMinutes($currentRental->effective_end_at)) : 1;
    $elapsedMinutes = $currentRental ? max(0, $currentRental->started_at->diffInMinutes(now())) : 0;
    $progressPct = $currentRental ? min(100, round(($elapsedMinutes / $totalMinutes) * 100)) : 0;
@endphp

<div class="col-12 col-sm-6 col-md-4 col-lg-3 boat-card-wrapper"
     data-status="{{ $status }}"
     data-boat-number="{{ $boat->boat_number }}"
     data-boat-id="{{ $boat->id }}"
     data-worker-id="{{ $currentRental?->worker_id ?? '' }}">
    <div class="clay-card p-3 boat-card h-100 position-relative
        @if($status === 'warning') boat-card-warning
        @elseif($status === 'time_up') boat-card-time-up
        @elseif($status === 'overdue') boat-card-overdue
        @elseif($status === 'ended') boat-card-ended
        @elseif($status === 'awaiting_confirmation') boat-card-awaiting
        @endif">

        <!-- Boat Number, Name & Status -->
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="fw-bold mb-0" style="font-size:1.1rem;">
                #{{ $boat->boat_number }}
                @if($boat->name)
                    <small class="text-muted d-block" style="font-size:0.65rem;font-weight:400;">{{ $boat->name }}</small>
                @endif
            </h5>
            <span class="status-badge" style="background:{{ $boat->status->color() }}20;color:{{ $boat->status->color() }};border:2px solid {{ $boat->status->color() }};font-size:0.7rem;padding:4px 10px;">
                {{ $boat->status->label() }}
            </span>
        </div>

        <div class="rental-section" style="display: {{ $currentRental ? '' : 'none' }};">
            <!-- Timer Display -->
            <div class="text-center my-2">
                <div class="timer-display" id="timer-{{ $boat->id }}"
                     data-remaining="{{ $remainingSeconds }}"
                     data-overtime="{{ $overtimeSeconds }}"
                     data-status="{{ $status }}">
                    @if($status === 'overdue' || $status === 'time_up')
                        <span class="overtime-counter" id="overtime-{{ $boat->id }}">+{{ sprintf('%02d:%02d', floor($overtimeSeconds / 60), $overtimeSeconds % 60) }}</span>
                    @elseif($status === 'ended')
                        <span style="color:var(--clay-text-light);font-size:0.8rem;">— Ended —</span>
                    @else
                        {{ sprintf('%02d:%02d', floor($remainingSeconds / 60), $remainingSeconds % 60) }}
                    @endif
                </div>
            </div>

            <!-- Progress Bar (hidden for ended) -->
            @if($status !== 'ended')
            <div class="progress mb-2" style="height:4px;border-radius:2px;background:#e9ecef;">
                <div class="progress-bar" role="progressbar"
                     style="width:{{ $progressPct }}%;background:{{ $status === 'overdue' || $status === 'time_up' ? '#dc3545' : ($progressPct > 80 ? '#ffc107' : '#198754') }};"
                     aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
            @endif

            <!-- Worker Info (shown on ALL occupied cards) -->
            <div class="mb-2 text-center" style="font-size:0.8rem;">
                <div class="fw-semibold">
                    <i class="bi bi-person"></i> <span id="worker-name-{{ $boat->id }}">{{ $currentRental?->worker?->name ?? 'Unknown' }}</span>
                    <small class="text-muted">(#{{ $currentRental?->worker_id }})</small>
                </div>
                <div class="text-muted mt-1">
                    <small><i class="bi bi-clock-start"></i> Start: <span id="started-{{ $boat->id }}">{{ $currentRental?->started_at?->format('H:i:s') }}</span></small>
                    <br>
                    <small><i class="bi bi-clock-end"></i> End: <span id="expected-end-{{ $boat->id }}">{{ $currentRental?->effective_end_at?->format('H:i:s') }}</span></small>
                </div>
                @if($currentRental?->extended_minutes > 0)
                    <small class="text-info"><i class="bi bi-plus-circle"></i> +{{ $currentRental?->extended_minutes }}m</small>
                @endif
                @if($currentRental?->reduced_minutes > 0)
                    <small class="text-warning"><i class="bi bi-dash-circle"></i> -{{ $currentRental?->reduced_minutes }}m</small>
                @endif
                @if($currentRental?->received_at)
                    <br><small class="text-success"><i class="bi bi-check-circle"></i> Received {{ $currentRental?->received_at?->format('H:i') }}</small>
                @endif
            </div>

            <!-- Overtime Display -->
            @if($status === 'overdue' || $status === 'time_up')
            <div id="overtime-container-{{ $boat->id }}" class="text-center mb-2">
                <span class="overtime-counter" id="overtime-display-{{ $boat->id }}">+{{ sprintf('%02d:%02d', floor($overtimeSeconds / 60), $overtimeSeconds % 60) }}</span>
            </div>
            @endif

            <!-- DIVIDER -->
            @if($canAct)
            <hr class="my-2" style="opacity:0.15;">

            <!-- ADMIN: Time Controls (Extend / Reduce) -->
            @if($isAdmin && in_array($status, ['occupied', 'warning', 'time_up']))
                <div class="d-flex gap-1 mb-1 justify-content-center flex-wrap">
                    <div class="btn-group btn-group-sm" role="group">
                        @foreach([5, 10, 15, 30] as $preset)
                            <button class="clay-btn clay-btn-info btn-sm" style="padding:2px 6px;font-size:0.65rem;min-width:32px;" onclick="extendRental({{ $currentRental?->id ?? 'null' }}, {{ $preset }})" title="+{{ $preset }} min">
                                +{{ $preset }}
                            </button>
                        @endforeach
                        <button class="clay-btn clay-btn-info btn-sm" style="padding:2px 8px;font-size:0.65rem;" onclick="openExtendModal({{ $currentRental?->id ?? 'null' }}, {{ $boat->boat_number }})" title="Custom extend">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        @foreach([5, 10] as $preset)
                            <button class="clay-btn clay-btn-warning btn-sm" style="padding:2px 6px;font-size:0.65rem;min-width:32px;" onclick="reduceRental({{ $currentRental?->id ?? 'null' }}, {{ $preset }}, {{ $boat->boat_number }})" title="-{{ $preset }} min">
                                -{{ $preset }}
                            </button>
                        @endforeach
                        <button class="clay-btn clay-btn-warning btn-sm" style="padding:2px 8px;font-size:0.65rem;" onclick="openReduceModal({{ $currentRental?->id ?? 'null' }}, {{ $boat->boat_number }})" title="Custom reduce">
                            <i class="bi bi-dash-circle"></i>
                        </button>
                    </div>
                </div>
            @endif

            <!-- END / RECEIVE / FORCE END buttons -->
            <div class="d-flex gap-1 justify-content-center flex-wrap">
                @if($isAdmin || $isOwner)
                <!-- OWNER: End Rental button (visible for occupied/warning/time_up/overdue) -->
                <button class="clay-btn clay-btn-danger btn-sm action-btn"
                        data-boat-action="end-rental"
                        style="padding:4px 8px;font-size:0.7rem;{{ in_array($status, ['occupied', 'warning', 'time_up', 'overdue']) ? '' : 'display:none;' }}"
                        onclick="endRental({{ $currentRental?->id ?? 'null' }})">
                    <i class="bi bi-stop-fill"></i> End Rental
                </button>

                <!-- OWNER: Mark Received button (visible for ended status) -->
                <button class="clay-btn clay-btn-success btn-sm action-btn"
                        data-boat-action="mark-received"
                        style="padding:4px 8px;font-size:0.7rem;{{ $status === 'ended' ? '' : 'display:none;' }}"
                        onclick="markReceived({{ $currentRental?->id ?? 'null' }})">
                    <i class="bi bi-check2-all"></i> Mark Received
                </button>
                @endif

                <!-- ADMIN: Force End / Complete / Transfer -->
                @if($isAdmin)
                    <button class="clay-btn clay-btn-dark btn-sm" style="padding:2px 8px;font-size:0.65rem;{{ $currentRental ? '' : 'display:none;' }}" onclick="forceEndRental({{ $currentRental?->id ?? 'null' }}, {{ $boat->boat_number }})">
                        <i class="bi bi-shield-exclamation"></i> Force End
                    </button>
                    @if(in_array($status, ['occupied', 'warning', 'time_up', 'ended']))
                    <button class="clay-btn clay-btn-info btn-sm" style="padding:2px 8px;font-size:0.65rem;" onclick="openTransferModal({{ $currentRental?->id ?? 'null' }}, {{ $boat->boat_number }})">
                        <i class="bi bi-arrow-left-right"></i> Transfer
                    </button>
                    @endif
                @endif
            </div>
            @endif

        </div>
        <div class="available-section" style="display: {{ $currentRental ? 'none' : '' }};">
            <!-- Available / Maintenance -->
            <div class="text-center my-3 py-3">
                <div class="timer-display text-muted">--</div>
            </div>

            <div class="d-flex gap-2 mt-2 justify-content-center flex-wrap available-actions">
                <button class="clay-btn clay-btn-success btn-sm action-btn"
                        data-available-action="start-rental"
                        style="display:{{ $status === 'available' ? '' : 'none' }}"
                        onclick="startRental({{ $boat->id }})">
                    <i class="bi bi-play-fill"></i> Start Rental
                </button>
                @if($isAdmin)
                    <button class="clay-btn clay-btn-dark btn-sm action-btn"
                            data-available-action="move-to-maintenance"
                            style="display:{{ $status === 'available' ? '' : 'none' }}"
                            onclick="moveToMaintenance({{ $boat->id }})">
                        <i class="bi bi-wrench"></i> Maintenance
                    </button>
                @endif
                <div class="maintenance-info text-center"
                     style="display:{{ $status === 'maintenance' ? '' : 'none' }}">
                    <small class="text-muted"><i class="bi bi-wrench"></i> Under Maintenance</small>
                    @if($isAdmin)
                        <div class="d-flex gap-2 mt-2 justify-content-center">
                            <button class="clay-btn clay-btn-success btn-sm" onclick="removeFromMaintenance({{ $boat->id }})">
                                <i class="bi bi-check-lg"></i> Mark Available
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
