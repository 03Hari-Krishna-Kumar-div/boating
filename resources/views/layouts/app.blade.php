<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Dhanalakshmi Boating')) - Dhanalakshmi Boating</title>

    <!-- SEO -->
    <meta name="description" content="Boat Rental Management System">
    <meta name="robots" content="noindex, nofollow">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --clay-bg: #f5f7fb;
            --clay-card: #ffffff;
            --clay-shadow: 0 2px 12px rgba(0,0,0,0.08);
            --clay-shadow-sm: 0 1px 4px rgba(0,0,0,0.06);
            --clay-shadow-lg: 0 4px 20px rgba(0,0,0,0.10);
            --clay-inset: inset 0 2px 4px rgba(0,0,0,0.04);
            --clay-inset-sm: inset 0 1px 2px rgba(0,0,0,0.03);
            --clay-radius: 16px;
            --clay-radius-sm: 10px;
            --clay-radius-lg: 24px;
            --clay-primary: #6c5ce7;
            --clay-success: #198754;
            --clay-warning: #ffc107;
            --clay-danger: #dc3545;
            --clay-info: #0d6efd;
            --clay-dark: #212529;
            --clay-text: #212529;
            --clay-text-light: #6c757d;
            --clay-nav: #212529;
            --transition-base: all 0.2s ease;
        }

        [data-bs-theme="dark"] {
            --clay-bg: #1a1a2e;
            --clay-card: #16213e;
            --clay-shadow: 0 2px 12px rgba(0,0,0,0.3);
            --clay-shadow-sm: 0 1px 4px rgba(0,0,0,0.2);
            --clay-shadow-lg: 0 4px 20px rgba(0,0,0,0.4);
            --clay-inset: inset 0 2px 4px rgba(0,0,0,0.2);
            --clay-inset-sm: inset 0 1px 2px rgba(0,0,0,0.15);
            --clay-text: #e9ecef;
            --clay-text-light: #adb5bd;
            --clay-nav: #0f0f1a;
        }

        * { font-family: 'Inter', sans-serif; }

        body {
            background: var(--clay-bg);
            color: var(--clay-text);
            min-height: 100vh;
        }

        /* Claymorphism Cards */
        .clay-card {
            background: var(--clay-card);
            border-radius: var(--clay-radius);
            box-shadow: var(--clay-shadow);
            border: none;
            transition: var(--transition-base);
        }

        .clay-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--clay-shadow-lg);
        }

        .clay-card-sm {
            background: var(--clay-card);
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            border: none;
            transition: var(--transition-base);
        }

        /* Clay Buttons */
        .clay-btn {
            border: none;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            transition: var(--transition-base);
            font-weight: 600;
            padding: 10px 24px;
        }

        .clay-btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            line-height: 1.4;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            border: none;
            transition: var(--transition-base);
            font-weight: 600;
        }

        .clay-btn:hover,
        .clay-btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: var(--clay-shadow);
        }

        .clay-btn:active,
        .clay-btn-sm:active {
            box-shadow: var(--clay-inset-sm);
            transform: translateY(1px);
        }

        .clay-btn-primary {
            background: #6c5ce7;
            color: #fff;
        }
        .clay-btn-primary:hover { background: #5a4bd1; color: #fff; }

        .clay-btn-success {
            background: #198754;
            color: #fff;
        }
        .clay-btn-success:hover { background: #157347; color: #fff; }

        .clay-btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .clay-btn-warning:hover { background: #e0a800; color: #212529; }

        .clay-btn-danger {
            background: #dc3545;
            color: #fff;
        }
        .clay-btn-danger:hover { background: #bb2d3b; color: #fff; }

        .clay-btn-info {
            background: #0d6efd;
            color: #fff;
        }
        .clay-btn-info:hover { background: #0b5ed7; color: #fff; }

        .clay-btn-dark {
            background: #212529;
            color: #fff;
        }
        .clay-btn-dark:hover { background: #1a1e21; color: #fff; }

        /* Clay Inputs */
        .clay-input {
            background: var(--clay-bg);
            border: none;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-inset-sm);
            padding: 12px 16px;
            color: var(--clay-text);
            transition: var(--transition-base);
        }

        .clay-input:focus {
            box-shadow: var(--clay-inset);
            outline: none;
        }

        /* Clay Navbar */
        .clay-navbar {
            background: var(--clay-nav);
            box-shadow: var(--clay-shadow);
            border-radius: 0 0 var(--clay-radius-lg) var(--clay-radius-lg);
            padding: 12px 24px;
        }

        .clay-navbar .nav-link {
            color: #dfe6e9 !important;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: var(--clay-radius-sm);
            transition: var(--transition-base);
        }

        .clay-navbar .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }

        .clay-navbar .nav-link.active {
            box-shadow: var(--clay-inset-sm);
        }

        /* Status badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: var(--clay-shadow-sm);
        }

        /* Animations */
        @keyframes breathe-warning {
            0%, 100% {
                opacity: 0.85;
                box-shadow: 0 0 8px rgba(253, 203, 110, 0.3);
            }
            50% {
                opacity: 1;
                box-shadow: 0 0 20px rgba(253, 203, 110, 0.6);
            }
        }

        @keyframes pulse-alarm {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .boat-card-warning {
            animation: breathe-warning 3s ease-in-out infinite;
            border: 3px solid #fdcb6e !important;
        }

        .boat-card-overdue {
            animation: pulse-alarm 0.5s ease-in-out infinite;
            border: 3px solid #e17055 !important;
        }

        .boat-card-awaiting {
            border: 3px solid #fd7e14 !important;
        }

        .boat-card-time-up {
            animation: pulse-alarm 0.5s ease-in-out infinite;
            border: 3px solid #dc3545 !important;
            background: rgba(220,53,69,0.08) !important;
        }

        .boat-card-ended {
            border: 3px solid #6c5ce7 !important;
            opacity: 0.85;
        }

        /* Overtime counter */
        .overtime-counter {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--clay-danger);
        }

        /* Timer display */
        .timer-display {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        /* Dashboard stat cards */
        .stat-card {
            text-align: center;
            padding: 20px;
        }

        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 0.85rem;
            color: var(--clay-text-light);
            margin-top: 8px;
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast-clay {
            background: var(--clay-card);
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow);
            border: none;
            color: var(--clay-text);
        }

        /* Loading indicator */
        .clay-spinner {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: var(--clay-shadow);
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Connection indicator */
        .connection-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: var(--clay-shadow-sm);
        }

        .connection-dot.connected { background: var(--clay-success); }
        .connection-dot.disconnected { background: var(--clay-danger); }

        /* Search and filter */
        .filter-bar {
            padding: 16px;
            border-radius: var(--clay-radius);
            background: var(--clay-card);
            box-shadow: var(--clay-shadow-sm);
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .timer-display { font-size: 1.5rem; }
            .stat-card .stat-number { font-size: 1.8rem; }
            .clay-card { border-radius: var(--clay-radius-sm); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--clay-bg); }
        ::-webkit-scrollbar-thumb {
            background: var(--clay-text-light);
            border-radius: 10px;
        }

        /* Table styling */
        .clay-table {
            border-radius: var(--clay-radius);
            overflow: hidden;
        }

        .clay-table th {
            background: var(--clay-nav);
            color: #dfe6e9;
            font-weight: 600;
            border: none;
        }

        .clay-table td {
            background: var(--clay-card);
            border-color: rgba(0,0,0,0.05);
            vertical-align: middle;
        }

        /* Override modal */
        .clay-modal .modal-content {
            background: var(--clay-card);
            border-radius: var(--clay-radius-lg);
            box-shadow: var(--clay-shadow-lg);
            border: none;
        }

        .clay-modal .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .clay-modal .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* Popup for return confirmation */
        .return-popup {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .return-popup .popup-content {
            background: var(--clay-card);
            border-radius: var(--clay-radius-lg);
            box-shadow: var(--clay-shadow-lg);
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .return-popup .popup-content h3 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .return-popup .popup-content .timer-ended {
            font-size: 3rem;
            font-weight: 700;
            color: var(--clay-danger);
            margin: 20px 0;
        }

        /* Online indicator */
        .online-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .online-indicator.online { background: var(--clay-success); }
        .online-indicator.offline { background: #b2bec3; }

        /* Section headers */
        .section-header {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--clay-text);
        }

        /* Audio alarm hidden */
        #alarm-audio { display: none; }

        /* Extend/Reduce Modal styles */
        .time-modal {
            display: none;
        }
        .time-modal .preset-btn {
            background: var(--clay-card);
            color: var(--clay-text);
            border: 2px solid transparent;
            box-shadow: var(--clay-shadow-sm);
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-base);
        }
        .time-modal .preset-btn:hover {
            box-shadow: var(--clay-shadow);
            transform: translateY(-1px);
        }
        .time-modal .preset-btn.active {
            border-color: var(--clay-primary);
            box-shadow: var(--clay-shadow);
        }
        .time-modal .close-modal {
            cursor: pointer;
        }

        /* Quick Action Panel (FAB) */
        .quick-actions-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9990;
        }
        .quick-actions-fab .fab-toggle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--clay-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 16px rgba(108,92,231,0.4);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quick-actions-fab .fab-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 24px rgba(108,92,231,0.5);
        }
        .quick-actions-fab .fab-menu {
            position: absolute;
            bottom: 68px;
            right: 0;
            min-width: 220px;
            background: var(--clay-card);
            border-radius: var(--clay-radius);
            box-shadow: var(--clay-shadow-lg);
            padding: 12px;
            display: none;
            transform-origin: bottom right;
            animation: fab-in 0.2s ease-out;
        }
        .quick-actions-fab .fab-menu.open {
            display: block;
        }
        @keyframes fab-in {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .quick-actions-fab .fab-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--clay-radius-sm);
            color: var(--clay-text);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition-base);
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .quick-actions-fab .fab-menu-item:hover {
            background: rgba(108,92,231,0.1);
            color: var(--clay-primary);
        }
        .quick-actions-fab .fab-menu-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        /* Breadcrumbs */
        .breadcrumb-clay {
            background: var(--clay-card);
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            padding: 10px 16px;
            margin-bottom: 20px;
        }
        .breadcrumb-clay .breadcrumb-item a {
            color: var(--clay-text-light);
            text-decoration: none;
            font-weight: 500;
        }
        .breadcrumb-clay .breadcrumb-item a:hover {
            color: var(--clay-primary);
        }
        .breadcrumb-clay .breadcrumb-item.active {
            color: var(--clay-text);
            font-weight: 600;
        }

        /* Pagination */
        .clay-pagination {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .clay-pagination .page-item {
            list-style: none;
        }
        .clay-pagination .page-link {
            background: var(--clay-card);
            color: var(--clay-text);
            border: none;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            padding: 8px 14px;
            font-weight: 500;
            transition: var(--transition-base);
            text-decoration: none;
            display: block;
        }
        .clay-pagination .page-link:hover {
            box-shadow: var(--clay-shadow);
            transform: translateY(-1px);
        }
        .clay-pagination .page-item.active .page-link {
            background: var(--clay-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(108,92,231,0.3);
        }
        .clay-pagination .page-item.disabled .page-link {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Rows per page */
        .rows-per-page {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .rows-per-page select {
            background: var(--clay-card);
            border: none;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-inset-sm);
            padding: 6px 12px;
            color: var(--clay-text);
            font-weight: 500;
        }

        /* Instant search */
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--clay-text-light);
        }
        .search-wrapper .clay-input {
            padding-left: 40px;
        }

        /* Nav previous/next */
        .nav-prev-next {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .nav-prev-next .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            background: var(--clay-card);
            color: var(--clay-text);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-base);
        }
        .nav-prev-next .nav-btn:hover {
            box-shadow: var(--clay-shadow);
            transform: translateY(-1px);
        }
    </style>

    @stack('styles')
</head>
<body>
    <audio id="alarm-audio" preload="auto" loop>
        <source src="/sounds/6_notification.mp3" type="audio/mpeg">
    </audio>
    <audio id="five-min-audio" preload="auto">
        <source src="/sounds/6_notification.mp3" type="audio/mpeg">
    </audio>
    <audio id="worker-timer-up-audio" preload="auto">
        <source src="/sounds/1_notification.mp3" type="audio/mpeg">
    </audio>

    <div id="app">
        <!-- Navbar -->
        @include('partials.navbar')

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar for admin -->
                @if(auth()->user()?->isAdmin())
                    @include('partials.sidebar')
                @endif

                <!-- Main Content -->
                <main class="col {{ auth()->user()?->isAdmin() ? 'ms-3' : '' }} py-4">
                    <!-- Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show clay-card-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show clay-card-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Breadcrumbs -->
                    @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
                        <nav aria-label="breadcrumb" class="breadcrumb-clay">
                            <ol class="breadcrumb mb-0">
                                @foreach($breadcrumbs as $crumb)
                                    @if($crumb['url'] ?? false)
                                        <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                                    @else
                                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                                    @endif
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Quick Action Panel (Admin only) -->
    @if(auth()->user()?->isAdmin())
    <div class="quick-actions-fab">
        <button class="fab-toggle" id="fab-toggle" onclick="toggleFabMenu()" aria-label="Quick Actions">
            <i class="bi bi-plus-lg" id="fab-icon"></i>
        </button>
        <div class="fab-menu" id="fab-menu">
            <a href="{{ route('dashboard') }}" class="fab-menu-item">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('admin.boats.create') }}" class="fab-menu-item">
                <i class="bi bi-boat"></i> Add Boat
            </a>
            <a href="{{ route('admin.workers.create') }}" class="fab-menu-item">
                <i class="bi bi-person-plus"></i> Add Worker
            </a>
            <a href="{{ route('admin.rentals.index') }}" class="fab-menu-item">
                <i class="bi bi-clock-history"></i> View Rentals
            </a>
            <a href="{{ route('admin.reports.index') }}" class="fab-menu-item">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="{{ route('admin.settings.index') }}" class="fab-menu-item">
                <i class="bi bi-gear"></i> Settings
            </a>
            <a href="{{ route('admin.activity-logs.index') }}" class="fab-menu-item">
                <i class="bi bi-journal-text"></i> Activity Logs
            </a>
            <hr class="my-1" style="opacity:0.1;">
            <button class="fab-menu-item" onclick="document.getElementById('fab-menu').classList.remove('open');document.getElementById('fab-icon').className='bi bi-plus-lg';" style="color:var(--clay-text-light);">
                <i class="bi bi-x-lg"></i> Close
            </button>
        </div>
    </div>
    @endif

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Return Confirmation Popup -->
    <div id="return-popup" class="return-popup" style="display:none;">
        <div class="popup-content clay-card">
            <div class="timer-ended" id="popup-timer">00:00</div>
            <h3>Has the customer returned?</h3>
            <p class="text-muted" id="popup-boat-info">Boat #<span id="popup-boat-number"></span></p>
            <div class="d-flex gap-3 justify-content-center mt-4">
                <button class="clay-btn clay-btn-success btn-lg" onclick="confirmReturn(true)" id="btn-return-yes">
                    <i class="bi bi-check-lg"></i> YES - Returned
                </button>
                <button class="clay-btn clay-btn-danger btn-lg" onclick="confirmReturn(false)" id="btn-return-no">
                    <i class="bi bi-x-lg"></i> NO - Still Out
                </button>
            </div>
        </div>
    </div>

    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('scripts')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            // Notifications are initialized by notifications.js (loaded via Vite bundle).
            // RealtimeSync.init() is called by dashboard.js (loaded in bundle).
            // dashboard.js's onDashboardData() handles boat cards, stats, clock, etc.
            // It is safe on all pages — all DOM lookups have null guards.
        });

        // Quick Action FAB toggle
        window.toggleFabMenu = function() {
            const menu = document.getElementById('fab-menu');
            const icon = document.getElementById('fab-icon');
            if (menu) {
                const isOpen = menu.classList.toggle('open');
                if (icon) icon.className = isOpen ? 'bi bi-x-lg' : 'bi bi-plus-lg';
            }
        };

        // Close FAB on click outside
        document.addEventListener('click', function(e) {
            const fab = document.querySelector('.quick-actions-fab');
            const menu = document.getElementById('fab-menu');
            if (fab && menu && menu.classList.contains('open')) {
                if (!fab.contains(e.target)) {
                    menu.classList.remove('open');
                    document.getElementById('fab-icon').className = 'bi bi-plus-lg';
                }
            }
        });
    </script>
</body>
</html>
