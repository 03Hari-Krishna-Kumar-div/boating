@props(['type' => 'info', 'message' => '', 'id' => null])

<div class="toast-clay toast show mb-2" role="alert" @if($id) id="{{ $id }}" @endif>
    <div class="toast-body d-flex align-items-center gap-2">
        <i class="bi bi-{{ $type === 'success' ? 'check-circle-fill' : ($type === 'error' ? 'exclamation-triangle-fill' : ($type === 'warning' ? 'exclamation-circle-fill' : 'info-circle-fill')) }}"
           style="color:{{ $type === 'success' ? '#28a745' : ($type === 'error' ? '#dc3545' : ($type === 'warning' ? '#ffc107' : '#0d6efd')) }};font-size:1.2rem;"></i>
        <span class="flex-grow-1">{{ $message }}</span>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="toast"></button>
    </div>
</div>
