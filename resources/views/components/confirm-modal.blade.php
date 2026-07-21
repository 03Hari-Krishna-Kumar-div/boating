@props(['id' => 'confirm-modal', 'title' => 'Confirm Action', 'action' => '', 'method' => 'POST'])

<div class="modal fade clay-modal" id="{{ $id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ $action }}" class="modal-content">
            @csrf
            @method($method)
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <button type="button" class="clay-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="clay-btn clay-btn-danger">Confirm</button>
            </div>
        </form>
    </div>
</div>
