@props(['boatId', 'remaining' => 0, 'overtime' => 0, 'status' => 'available'])

<div class="text-center my-3">
    <div class="timer-display" id="timer-{{ $boatId }}"
         data-remaining="{{ $remaining }}"
         data-overtime="{{ $overtime }}"
         data-status="{{ $status }}">
        @if(in_array($status, ['available', 'maintenance']))
            --
        @elseif($status === 'overdue')
            <span class="overtime-counter" id="overtime-{{ $boatId }}">+{{ sprintf('%02d:%02d', floor($overtime / 60), $overtime % 60) }}</span>
        @else
            {{ sprintf('%02d:%02d', floor($remaining / 60), $remaining % 60) }}
        @endif
    </div>
</div>
