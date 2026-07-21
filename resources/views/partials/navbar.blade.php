<nav class="clay-navbar navbar navbar-expand-lg mb-4">
    <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold" href="{{ route('dashboard') }}">
            <i class="bi bi-boat-fill me-2"></i>Dhanalakshmi Boating
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                @if(auth()->user()?->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.workers.*') ? 'active' : '' }}" href="{{ route('admin.workers.index') }}">
                            <i class="bi bi-people me-1"></i> Workers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.boats.*') ? 'active' : '' }}" href="{{ route('admin.boats.index') }}">
                            <i class="bi bi-boat me-1"></i> Boats
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                            <i class="bi bi-graph-up me-1"></i> Reports
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard.tv') }}" target="_blank">
                        <i class="bi bi-tv me-1"></i> TV Mode
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav">
                <!-- Notifications -->
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <span id="notif-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;display:none;">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end clay-card p-0" style="width:350px;max-height:400px;overflow-y:auto;">
                        <div class="p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Notifications</h6>
                        </div>
                        <div id="notif-list">
                            <div class="p-3 text-center text-muted">No notifications</div>
                        </div>
                        <div class="p-2 border-top text-center">
                            <button class="clay-btn clay-btn-sm w-100" onclick="markAllNotifRead()">
                                Mark All as Read
                            </button>
                        </div>
                    </div>
                </li>

                <!-- Theme Toggle -->
                <li class="nav-item me-2">
                    <a class="nav-link" href="#" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                        <i class="bi bi-moon-fill" id="theme-icon"></i>
                    </a>
                </li>

                <!-- Connection Status -->
                <li class="nav-item me-2 d-flex align-items-center">
                    <span class="connection-dot connected" id="connection-dot"></span>
                    <span id="connection-text" class="text-white-50 ms-1" style="font-size:0.75rem;">Connected</span>
                </li>

                <!-- User -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end clay-card-sm">
                        <li><span class="dropdown-item-text text-muted">{{ auth()->user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
