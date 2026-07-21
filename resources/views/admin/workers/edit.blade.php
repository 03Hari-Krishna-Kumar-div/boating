@extends('layouts.app')

@section('title', 'Edit Worker')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="clay-card p-4">
            <h2 class="fw-bold mb-4">Edit Worker: {{ $worker->name }}</h2>
            <form method="POST" action="{{ route('admin.workers.update', $worker) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" class="clay-input form-control @error('name') is-invalid @enderror" value="{{ old('name', $worker->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="clay-input form-control @error('email') is-invalid @enderror" value="{{ old('email', $worker->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="clay-btn clay-btn-primary">Update Worker</button>
                    <a href="{{ route('admin.workers.index') }}" class="clay-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
