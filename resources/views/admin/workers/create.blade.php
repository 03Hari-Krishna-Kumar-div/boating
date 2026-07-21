@extends('layouts.app')

@section('title', 'Add Worker')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="clay-card p-4">
            <h2 class="fw-bold mb-4">Add New Worker</h2>
            <form method="POST" action="{{ route('admin.workers.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" class="clay-input form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="clay-input form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="clay-input form-control @error('password') is-invalid @enderror" required minlength="8">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="clay-input form-control" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="clay-btn clay-btn-primary">Create Worker</button>
                    <a href="{{ route('admin.workers.index') }}" class="clay-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
