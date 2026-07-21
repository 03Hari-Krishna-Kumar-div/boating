@extends('layouts.app')

@section('title', 'Settings')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Settings'],
    ];
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="section-header">System Settings</h1>
        <div class="clay-card p-4">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label class="form-label fw-bold">Rental Duration (minutes)</label>
                    <input type="number" name="rental_duration_minutes" class="clay-input form-control"
                           value="{{ $settings['rental_duration_minutes'] ?? config('brms.rental_duration_minutes') }}" min="1" max="480">
                    <small class="text-muted">Default duration for each rental in minutes.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Warning Time (minutes)</label>
                    <input type="number" name="warning_minutes" class="clay-input form-control"
                           value="{{ $settings['warning_minutes'] ?? config('brms.warning_minutes') }}" min="1" max="60">
                    <small class="text-muted">Time before rental expires to start warning alerts.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Alarm Interval (seconds)</label>
                    <input type="number" name="alarm_interval_seconds" class="clay-input form-control"
                           value="{{ $settings['alarm_interval_seconds'] ?? config('brms.alarm_interval_seconds') }}" min="1" max="10">
                    <small class="text-muted">Interval for alarm sounds during warning period.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Session Timeout (minutes)</label>
                    <input type="number" name="session_timeout_minutes" class="clay-input form-control"
                           value="{{ $settings['session_timeout_minutes'] ?? config('brms.session_timeout_minutes') }}" min="5" max="480">
                    <small class="text-muted">Inactivity timeout before automatic logout.</small>
                </div>

                <button type="submit" class="clay-btn clay-btn-primary">
                    <i class="bi bi-save"></i> Save Settings
                </button>
            </form>
        </div>

        <!-- Prev / Next -->
        <div class="nav-prev-next mt-4">
            <a href="{{ route('admin.maintenance.index') }}" class="nav-btn">
                <i class="bi bi-arrow-left"></i> Maintenance
            </a>
            <a href="{{ route('admin.boats.index') }}" class="nav-btn">
                Boats <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection
