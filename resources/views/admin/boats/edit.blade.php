@extends('layouts.app')

@section('title', 'Edit Boat')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="clay-card p-4">
            <h2 class="fw-bold mb-4">Edit Boat #{{ $boat->boat_number }}</h2>
            <form method="POST" action="{{ route('admin.boats.update', $boat) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-semibold">Boat Number</label>
                    <input type="number" name="boat_number" class="clay-input form-control @error('boat_number') is-invalid @enderror" value="{{ old('boat_number', $boat->boat_number) }}" required min="1">
                    @error('boat_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Boat Name</label>
                    <input type="text" name="name" class="clay-input form-control" value="{{ old('name', $boat->name) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color</label>
                    <input type="color" name="color_hex" class="form-control form-control-color" value="{{ old('color_hex', $boat->color_hex ?? '#0d6efd') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Admin Notes</label>
                    <textarea name="notes" class="clay-input form-control" rows="3">{{ old('notes', $boat->notes) }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="clay-btn clay-btn-primary">Update Boat</button>
                    <a href="{{ route('admin.boats.index') }}" class="clay-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
