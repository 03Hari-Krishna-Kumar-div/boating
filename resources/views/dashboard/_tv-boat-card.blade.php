@php
    $currentRental = $boat->currentRental;
    $status = $boat->status->value;
    $remainingSeconds = $currentRental ? max(0, now()->diffInSeconds($currentRental->effective_end_at, false)) : 0;
    $overtimeSeconds = $currentRental && now()->gt($currentRental->effective_end_at) ? abs(now()->diffInSeconds($currentRental->effective_end_at, false)) : 0;
@endphp

<div class="tv-boat-card status-{{ $status }}" data-tv-boat-id="{{ $boat->id }}">
    <!-- Header -->
    <div class="tv-boat-header">
        <div>
            <span class="tv-boat-number">#{{ $boat->boat_number }}</span>
            @if($boat->name)
                <span class="tv-boat-name">{{ $boat->name }}</span>
            @endif
        </div>
        <span class="tv-status-badge" style="background:{{ $boat->status->color() }}15;color:{{ $boat->status->color() }};border:1px solid {{ $boat->status->color() }}30;">
            {{ $boat->status->label() }}
        </span>
    </div>

    @if($currentRental)
        <!-- Timer -->
        <div class="tv-timer {{ $status === 'overdue' ? 'overtime' : '' }}"
             data-remaining="{{ $remainingSeconds }}"
             data-overtime="{{ $overtimeSeconds }}"
             data-status="{{ $status }}">
            @if($status === 'overdue')
                +{{ sprintf('%02d:%02d', floor($overtimeSeconds / 60), $overtimeSeconds % 60) }}
            @else
                {{ sprintf('%02d:%02d', floor($remainingSeconds / 60), $remainingSeconds % 60) }}
            @endif
        </div>

        <!-- Worker -->
        <div class="tv-worker-info">
            <span class="worker-name"><i class="bi bi-person"></i> {{ $currentRental->worker->name ?? 'Unknown' }}</span>
        </div>

        <!-- Times -->
        <div class="tv-time-info">
            Start: <span class="tv-start-time">{{ $currentRental->started_at?->format('H:i:s') }}</span><br>
            End: <span class="tv-end-time">{{ $currentRental->effective_end_at?->format('H:i:s') }}</span>
            @if($currentRental->extended_minutes > 0)
                <br><span style="color:#0d6efd;">+{{ $currentRental->extended_minutes }}m</span>
            @endif
            @if($currentRental->reduced_minutes > 0)
                <br><span style="color:#dc3545;">-{{ $currentRental->reduced_minutes }}m</span>
            @endif
        </div>
    @else
        <div class="tv-timer" style="color:#adb5bd;">--:--</div>
        @if($status === 'maintenance')
            <div class="tv-worker-info" style="color:#6c757d;">Under Maintenance</div>
        @else
            <div class="tv-worker-info" style="color:#28a745;">Available</div>
        @endif
    @endif
</div>
