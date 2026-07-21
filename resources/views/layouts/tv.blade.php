<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TV Dashboard') - Boat Rental Management</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* TV MODE - ALWAYS LIGHT THEME - IGNORES USER PREFERENCES */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #ffffff !important;
            color: #212529 !important;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Full screen layout */
        .tv-wrapper {
            padding: 16px;
            max-width: 1920px;
            margin: 0 auto;
        }

        /* Top bar with time and stats */
        .tv-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e9ecef;
        }

        .tv-clock {
            font-size: 2.2rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: #212529;
            letter-spacing: 2px;
        }

        .tv-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Stats bar */
        .tv-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .tv-stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 14px 10px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .tv-stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .tv-stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        /* Boat cards grid - auto responsive */
        .tv-boats-grid {
            display: grid;
            gap: 12px;
        }

        /* Desktop: 4-6 per row */
        @media (min-width: 1200px) {
            .tv-boats-grid { grid-template-columns: repeat(5, 1fr); }
            .tv-wrapper { padding: 20px 24px; }
        }

        /* Large TV / 4K: 6-8 per row */
        @media (min-width: 1800px) {
            .tv-boats-grid { grid-template-columns: repeat(7, 1fr); }
        }

        /* Tablet: 3 per row */
        @media (min-width: 768px) and (max-width: 1199px) {
            .tv-boats-grid { grid-template-columns: repeat(3, 1fr); }
        }

        /* Mobile: 2 per row */
        @media (max-width: 767px) {
            .tv-boats-grid { grid-template-columns: repeat(2, 1fr); }
            .tv-clock { font-size: 1.5rem; }
        }

        /* Individual boat card */
        .tv-boat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 2px solid #e9ecef;
            display: flex;
            flex-direction: column;
            min-height: 180px;
            transition: box-shadow 0.2s;
        }

        .tv-boat-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        /* Status border colors */
        .tv-boat-card.status-available { border-color: #28a745; }
        .tv-boat-card.status-occupied { border-color: #0d6efd; }
        .tv-boat-card.status-warning { border-color: #ffc107; }
        .tv-boat-card.status-overdue { border-color: #dc3545; }
        .tv-boat-card.status-awaiting_confirmation { border-color: #fd7e14; }
        .tv-boat-card.status-maintenance { border-color: #6c757d; }

        .tv-boat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .tv-boat-number {
            font-size: 1.3rem;
            font-weight: 800;
            color: #212529;
        }

        .tv-boat-name {
            font-size: 0.65rem;
            font-weight: 400;
            color: #6c757d;
            display: block;
        }

        .tv-status-badge {
            font-size: 0.6rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        /* Timer */
        .tv-timer {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            margin: 6px 0;
            color: #212529;
        }

        .tv-timer.overdue {
            color: #dc3545;
        }

        .tv-timer.overtime {
            color: #dc3545;
        }

        /* Worker info */
        .tv-worker-info {
            text-align: center;
            font-size: 0.75rem;
            color: #495057;
            margin-bottom: 4px;
        }

        .tv-worker-info .worker-name {
            font-weight: 600;
        }

        .tv-time-info {
            text-align: center;
            font-size: 0.65rem;
            color: #6c757d;
            line-height: 1.4;
        }

        /* No interactions in TV mode */
        button, .btn, a, input, select, textarea, form, .fab, .quick-actions {
            display: none !important;
        }

        /* Connection indicator */
        .tv-connection {
            font-size: 0.7rem;
            color: #6c757d;
        }
        .tv-connection .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .tv-connection .dot.online { background: #28a745; }
        .tv-connection .dot.offline { background: #dc3545; }

        /* Animations */
        @keyframes tv-flash-warning {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        @keyframes tv-pulse-overdue {
            0%, 100% { box-shadow: 0 2px 8px rgba(220,53,69,0.2); }
            50% { box-shadow: 0 2px 16px rgba(220,53,69,0.4); }
        }

        .tv-boat-card.status-warning {
            animation: tv-flash-warning 1s ease-in-out infinite;
        }
        .tv-boat-card.status-overdue {
            animation: tv-pulse-overdue 0.8s ease-in-out infinite;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="tv-wrapper">
        <!-- Top Bar -->
        <div class="tv-top-bar">
            <div>
                <div class="tv-clock" id="tv-clock">--:--:--</div>
                <div class="tv-title">Boat Rental Monitor</div>
            </div>
            <div class="text-end">
                <div class="tv-connection">
                    <span class="dot online" id="tv-connection-dot"></span>
                    <span id="tv-connection-text">Connected</span>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="tv-stats" id="tv-stats">
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#28a745;" id="tv-stat-available">0</div>
                <div class="tv-stat-label">Available</div>
            </div>
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#0d6efd;" id="tv-stat-active">0</div>
                <div class="tv-stat-label">Occupied</div>
            </div>
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#ffc107;" id="tv-stat-warning">0</div>
                <div class="tv-stat-label">Warning</div>
            </div>
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#dc3545;" id="tv-stat-overdue">0</div>
                <div class="tv-stat-label">Overdue</div>
            </div>
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#fd7e14;" id="tv-stat-awaiting">0</div>
                <div class="tv-stat-label">Awaiting</div>
            </div>
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#6c757d;" id="tv-stat-maintenance">0</div>
                <div class="tv-stat-label">Maintenance</div>
            </div>
            <div class="tv-stat-card">
                <div class="tv-stat-number" style="color:#212529;" id="tv-stat-total">0</div>
                <div class="tv-stat-label">Total Boats</div>
            </div>
        </div>

        <!-- Boat Cards -->
        <div class="tv-boats-grid" id="tv-boats-grid">
            @foreach($boats as $boat)
                @include('dashboard._tv-boat-card', ['boat' => $boat])
            @endforeach
        </div>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // ─── TV Mode Realtime Sync Engine ─────────────────────────
        // Smart polling, server-authoritative timer, network handling
        // No page reloads - pure DOM updates

        // ─── State ────────────────────────────────────────────────
        const tvState = {
            pollTimer: null,
            countdownTimer: null,
            isPolling: false,
            retryCount: 0,
            maxRetries: 3,
            interval: 1000,
            lastServerTime: null,
            serverTimeOffset: 0,
            isConnected: true,
            lastUpdateTime: 0
        };

        // ─── Configuration ────────────────────────────────────────
        const TV_CONFIG = {
            intervals: {
                active: 1000,
                inactive: 5000,
                hidden: 10000
            }
        };

        // ─── Smart Polling ────────────────────────────────────────

        function getTvOptimalInterval() {
            if (document.hidden) return TV_CONFIG.intervals.hidden;
            if (!document.hasFocus()) return TV_CONFIG.intervals.inactive;
            return TV_CONFIG.intervals.active;
        }

        function adjustTvInterval() {
            const newInterval = getTvOptimalInterval();
            if (newInterval !== tvState.interval) {
                tvState.interval = newInterval;
                restartTvPolling();
            }
        }

        function restartTvPolling() {
            if (tvState.pollTimer) {
                clearInterval(tvState.pollTimer);
                tvState.pollTimer = null;
            }
            startTvPolling();
        }

        function startTvPolling() {
            function poll() {
                if (tvState.isPolling) return;
                tvState.isPolling = true;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                fetch('/api/dashboard', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    credentials: 'same-origin',
                    cache: 'no-cache'
                })
                .then(res => {
                    if (res.status === 429) return null; // rate limit
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if (!data) return;
                    tvState.retryCount = 0;
                    tvState.isConnected = true;
                    tvState.lastUpdateTime = Date.now();
                    updateTvConnection(true);

                    // Store server time for drift correction
                    if (data.server_time) {
                        const serverMs = new Date(data.server_time).getTime();
                        if (!isNaN(serverMs)) {
                            tvState.lastServerTime = serverMs;
                            tvState.serverTimeOffset = serverMs - Date.now();
                        }
                        const d = new Date(data.server_time);
                        updateTvClock(d.toLocaleTimeString('en-US', { hour12: false }));
                    }

                    if (data.stats) updateTvStats(data.stats);
                    if (data.boats) updateTvBoats(data.boats);
                })
                .catch(err => {
                    tvState.retryCount++;
                    tvState.isConnected = false;
                    updateTvConnection(false);
                })
                .finally(() => {
                    tvState.isPolling = false;
                });
            }

            // Immediate first poll
            poll();
            tvState.pollTimer = setInterval(poll, tvState.interval);

            // Listen for cross-tab sync
            window.addEventListener('storage', function(e) {
                if (e.key === 'brms-action-complete') {
                    poll();
                }
            });
        }

        // ─── Connection Indicator ─────────────────────────────────

        function updateTvConnection(connected) {
            const dot = document.getElementById('tv-connection-dot');
            const txt = document.getElementById('tv-connection-text');
            if (dot) dot.className = 'dot ' + (connected ? 'online' : 'offline');
            if (txt) txt.textContent = connected ? 'Connected' : 'Reconnecting...';
        }

        // ─── Clock ────────────────────────────────────────────────

        function updateTvClock(timeStr) {
            const el = document.getElementById('tv-clock');
            if (el && timeStr) el.textContent = timeStr;
        }

        // ─── Stats ────────────────────────────────────────────────

        function updateTvStats(stats) {
            const map = {
                'tv-stat-available': 'available_boats',
                'tv-stat-active': 'active_rentals',
                'tv-stat-warning': 'warning_boats',
                'tv-stat-overdue': 'overdue_boats',
                'tv-stat-awaiting': 'awaiting_boats',
                'tv-stat-maintenance': 'maintenance_boats',
                'tv-stat-total': 'total_boats'
            };
            Object.entries(map).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el && key) el.textContent = stats[key] ?? 0;
            });
        }

        // ─── Boat Cards ───────────────────────────────────────────

        function updateTvBoats(boats) {
            boats.forEach(boat => {
                const card = document.querySelector(`[data-tv-boat-id="${boat.id}"]`);
                if (!card) return;

                const status = boat.status;
                const currentRental = boat.current_rental;

                // Update status class (only card-level border color)
                card.className = 'tv-boat-card';
                if (status === 'warning') card.classList.add('status-warning');
                else if (status === 'overdue') card.classList.add('status-overdue');
                else if (status === 'awaiting_confirmation') card.classList.add('status-awaiting_confirmation');
                else if (status === 'maintenance') card.classList.add('status-maintenance');
                else if (status === 'occupied' || status === 'active') card.classList.add('status-occupied');
                else if (status === 'available') card.classList.add('status-available');

                // Update status badge
                const badge = card.querySelector('.tv-status-badge');
                if (badge) {
                    badge.textContent = boat.status_label || status;
                }

                // Update timer data attributes (authoritative server values)
                const timer = card.querySelector('.tv-timer');
                if (timer) {
                    const remaining = currentRental?.remaining_seconds ?? 0;
                    const overtime = currentRental?.overtime_seconds ?? 0;
                    // Only update if changed (prevents flicker during countdown)
                    if (parseInt(timer.dataset.remaining) !== remaining || parseInt(timer.dataset.overtime) !== overtime) {
                        timer.dataset.remaining = remaining;
                        timer.dataset.overtime = overtime;
                        timer.dataset.status = status;
                        // Reset elapsed counter for smoother interpolation
                        timer.dataset.syncedAt = Date.now();
                    }
                }

                // Update worker info
                const workerEl = card.querySelector('.tv-worker-name');
                if (workerEl && currentRental) {
                    workerEl.textContent = currentRental.worker_name || 'Unknown';
                }

                // Update time info
                const startEl = card.querySelector('.tv-start-time');
                if (startEl && currentRental) {
                    startEl.textContent = currentRental.started_at_time || '--:--:--';
                }
                const endEl = card.querySelector('.tv-end-time');
                if (endEl && currentRental) {
                    endEl.textContent = currentRental.effective_end_at_time || '--:--:--';
                }
            });
        }

        // ─── Client-Side Countdown with Drift Correction ──────────

        function startTvCountdown() {
            function tick() {
                const now = Date.now();
                document.querySelectorAll('.tv-timer').forEach(el => {
                    const status = el.dataset.status;
                    const syncedAt = parseInt(el.dataset.syncedAt) || now;
                    const elapsed = Math.floor((now - syncedAt) / 1000);

                    let remaining = parseInt(el.dataset.remaining) || 0;
                    let overtime = parseInt(el.dataset.overtime) || 0;

                    if (status === 'available' || status === 'maintenance') {
                        el.textContent = '--:--';
                        el.className = 'tv-timer';
                        return;
                    }

                    if (status === 'overdue') {
                        // Overtime counts up from server value
                        const ov = overtime + elapsed;
                        const mins = Math.floor(ov / 60);
                        const secs = ov % 60;
                        el.textContent = `+${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                        el.className = 'tv-timer overtime';
                    } else {
                        // Countdown decrements from server value
                        const rem = Math.max(0, remaining - elapsed);
                        const mins = Math.floor(rem / 60);
                        const secs = rem % 60;
                        el.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                        el.className = 'tv-timer' + (rem < 60 ? ' overdue' : '');
                    }
                });
            }

            tick(); // Immediate first tick
            tvState.countdownTimer = setInterval(tick, 1000);
        }

        // ─── Visibility / Focus Handling ──────────────────────────

        document.addEventListener('visibilitychange', function() {
            adjustTvInterval();
            if (!document.hidden) {
                // Tab became visible: sync immediately + reset timer interpolation
                tvState.lastUpdateTime = 0; // Force full refresh on next poll
                const pollFn = tvState.pollTimer ? null : null;
                // Next poll will happen almost immediately since we restart
                restartTvPolling();
            }
        });

        window.addEventListener('focus', function() {
            adjustTvInterval();
            // Force immediate sync by making next poll fire quickly
            if (tvState.pollTimer) {
                clearInterval(tvState.pollTimer);
                tvState.pollTimer = null;
                startTvPolling();
            }
        });

        window.addEventListener('blur', function() {
            adjustTvInterval();
        });

        // ─── Initialize ──────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', function() {
            startTvPolling();
            startTvCountdown();

            // Request fullscreen
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
            }

            console.log('[TV-RealtimeSync] Engine started');
        });
    </script>
</body>
</html>
