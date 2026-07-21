<div class="col-auto d-none d-lg-block">
    <div class="clay-card-sm p-3" style="width:220px;position:sticky;top:20px;">
        <h6 class="fw-bold mb-3 px-2" style="color:var(--clay-text-light);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;">
            Admin Panel
        </h6>
        <nav class="nav flex-column">
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('dashboard') ? 'active fw-bold' : '' }}"
               href="{{ route('dashboard') }}" style="color:var(--clay-text);">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.workers.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.workers.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-people me-2"></i> Workers
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.boats.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.boats.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-boat me-2"></i> Boats
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.rentals.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.rentals.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-clock-history me-2"></i> Rentals
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.settings.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.settings.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-gear me-2"></i> Settings
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.reports.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.reports.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-graph-up me-2"></i> Reports
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.activity-logs.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.activity-logs.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-journal-text me-2"></i> Activity Logs
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.maintenance.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.maintenance.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-wrench me-2"></i> Maintenance
            </a>
            <a class="nav-link py-2 px-3 mb-1 rounded-3 {{ request()->routeIs('admin.backups.*') ? 'active fw-bold' : '' }}"
               href="{{ route('admin.backups.index') }}" style="color:var(--clay-text);">
                <i class="bi bi-cloud-arrow-down me-2"></i> Backups
            </a>
        </nav>
    </div>
</div>
