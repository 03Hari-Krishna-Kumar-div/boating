@extends('layouts.app')

@section('title', 'Backups')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Backups'],
    ];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-header mb-0">Database Backups</h1>
    <form method="POST" action="{{ route('admin.backups.run') }}" class="d-inline">
        @csrf
        <button type="submit" class="clay-btn clay-btn-primary">
            <i class="bi bi-cloud-arrow-up"></i> Create Backup
        </button>
    </form>
</div>

<div class="clay-card p-4">
    @if(count($backups) > 0)
        <table class="table clay-table">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $backup)
                    <tr>
                        <td class="fw-semibold"><i class="bi bi-file-earmark-zip me-2"></i>{{ $backup['filename'] }}</td>
                        <td>{{ round($backup['size'] / 1024, 2) }} KB</td>
                        <td>{{ $backup['created_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-5">
            <i class="bi bi-cloud-arrow-down" style="font-size:3rem;color:var(--clay-text-light);"></i>
            <p class="text-muted mt-2">No backups created yet.</p>
        </div>
    @endif
</div>

<!-- Prev / Next -->
<div class="nav-prev-next">
    <a href="{{ route('dashboard') }}" class="nav-btn">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
    <a href="{{ route('admin.activity-logs.index') }}" class="nav-btn">
        Activity Logs <i class="bi bi-arrow-right"></i>
    </a>
</div>
@endsection
