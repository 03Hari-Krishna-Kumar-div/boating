/**
 * RealtimeSync Engine
 * ====================
 * Global synchronization engine for Dhanalakshmi Boating.
 * 
 * Architecture:
 * - Single service replaces ajax-polling.js
 * - Smart polling: 1s active, 5s inactive, 10s hidden
 * - Immediate sync after any CRUD action via 'action-complete' event
 * - Network failure detection with auto-reconnect
 * - Server-authoritative time tracking
 * - Prevents duplicate in-flight requests
 * - No page reloads, no location.reload(), no F5 needed
 * 
 * Usage:
 *   RealtimeSync.init(dataCallback)  // start engine
 *   RealtimeSync.syncNow()            // force immediate sync
 *   document.dispatchEvent(new CustomEvent('action-complete'))  // after actions
 */

const RealtimeSync = (function() {
    'use strict';

    // ─── Configuration ─────────────────────────────────────────
    const CONFIG = {
        intervals: {
            active: 1000,     // 1s when tab focused and visible
            inactive: 5000,   // 5s when tab visible but not focused
            hidden: 10000     // 10s when tab hidden/minimized
        },
        maxRetries: 3,
        apiEndpoint: '/api/dashboard',
        retryBaseDelay: 1000 // 1s base delay for exponential backoff
    };

    // ─── State ─────────────────────────────────────────────────
    let state = {
        pollTimer: null,
        isPolling: false,
        retryCount: 0,
        currentInterval: CONFIG.intervals.active,
        lastSyncTime: 0,
        serverTimeAtSync: null,
        serverTimeOffset: 0,  // server_ms - client_ms
        isConnected: true,
        isDestroyed: false,
        connectionLostShown: false,
        dataCallback: null,     // main data handler
        syncPromise: null       // prevent duplicate in-flight requests
    };

    // ─── Smart Polling ─────────────────────────────────────────

    function getOptimalInterval() {
        if (document.hidden) return CONFIG.intervals.hidden;
        if (!document.hasFocus()) return CONFIG.intervals.inactive;
        return CONFIG.intervals.active;
    }

    function adjustInterval() {
        const newInterval = getOptimalInterval();
        if (newInterval !== state.currentInterval) {
            state.currentInterval = newInterval;
            restartPolling();
        }
    }

    function restartPolling() {
        stopPolling();
        startPolling();
    }

    function startPolling() {
        if (state.isDestroyed) return;
        stopPolling();
        state.pollTimer = setInterval(doSync, state.currentInterval);
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    // ─── Visibility / Focus Handling ───────────────────────────

    function onVisibilityChange() {
        adjustInterval();
        if (!document.hidden) {
            // Tab became visible → sync immediately
            doSync();
        }
    }

    function onWindowFocus() {
        adjustInterval();
        doSync(); // Immediate sync on focus
    }

    function onWindowBlur() {
        adjustInterval();
    }

    // ─── Connection Status ─────────────────────────────────────

    function setConnected(connected) {
        if (state.isConnected === connected) return;
        state.isConnected = connected;

        // Update DOM connection indicators
        const dots = document.querySelectorAll('.connection-dot');
        dots.forEach(dot => {
            dot.className = 'connection-dot ' + (connected ? 'connected' : 'disconnected');
        });

        const texts = document.querySelectorAll('.connection-text');
        texts.forEach(el => {
            el.textContent = connected ? 'Connected' : 'Disconnected';
        });

        if (!connected) {
            // Only show toast if retries exhausted
            if (state.retryCount >= CONFIG.maxRetries) {
                showConnectionLost();
            }
        } else {
            hideConnectionLost();
            // Reset retry count
            state.retryCount = 0;
        }
    }

    function showConnectionLost() {
        if (state.connectionLostShown) return;
        state.connectionLostShown = true;

        const container = document.getElementById('toast-container');
        if (!container) return;

        // Remove existing connection toast if any
        hideConnectionLost();

        const toast = document.createElement('div');
        toast.id = 'connection-toast';
        toast.className = 'toast-clay toast show mb-2 connection-lost-toast';
        toast.innerHTML = `
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-wifi-off" style="color:#dc3545;font-size:1.2rem;"></i>
                <span class="flex-grow-1"><strong>Connection Lost</strong> — Retrying...</span>
                <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
            </div>
        `;
        container.prepend(toast);
    }

    function hideConnectionLost() {
        state.connectionLostShown = false;
        const toast = document.getElementById('connection-toast');
        if (toast) toast.remove();
    }

    // ─── Server Time Tracking ──────────────────────────────────

    function updateServerTime(serverTimeStr) {
        if (!serverTimeStr) return;
        const serverMs = new Date(serverTimeStr).getTime();
        if (isNaN(serverMs)) return;
        state.serverTimeAtSync = serverMs;
        state.serverTimeOffset = serverMs - Date.now();

        // Update clock displays
        const d = new Date(serverMs);
        const timeStr = d.toLocaleTimeString('en-US', { hour12: false });

        const clockEls = document.querySelectorAll('[data-server-clock]');
        clockEls.forEach(el => el.textContent = timeStr);

        // Also update any TV clock
        const tvClock = document.getElementById('tv-clock');
        if (tvClock) tvClock.textContent = timeStr;
    }

    /**
     * Get current server time (approximate, based on last sync + offset).
     */
    function getServerTime() {
        if (!state.serverTimeAtSync) return new Date();
        return new Date(Date.now() + state.serverTimeOffset);
    }

    // ─── Main Sync Engine ──────────────────────────────────────

    async function doSync() {
        // Prevent duplicate in-flight requests
        if (state.isPolling || state.isDestroyed) return;

        // If a previous sync is still in-flight, wait for it
        if (state.syncPromise) {
            try {
                await state.syncPromise;
            } catch (e) {
                // ignore
            }
            return;
        }

        state.isPolling = true;

        const fetchPromise = (async () => {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(CONFIG.apiEndpoint, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    credentials: 'same-origin',
                    cache: 'no-cache'
                });

                if (response.status === 429) {
                    // Rate limited — skip this poll cycle silently
                    return;
                }

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                // Reset retry state on success
                state.retryCount = 0;
                setConnected(true);
                state.lastSyncTime = Date.now();

                // Store server time for drift correction
                updateServerTime(data.server_time);

                // Call registered data callback
                if (typeof state.dataCallback === 'function') {
                    state.dataCallback(data);
                }

            } catch (err) {
                state.retryCount++;
                setConnected(false);

                if (state.retryCount >= CONFIG.maxRetries) {
                    showConnectionLost();
                }
            } finally {
                state.isPolling = false;
                state.syncPromise = null;
            }
        })();

        state.syncPromise = fetchPromise;
        await fetchPromise;
    }

    // ─── Public API ────────────────────────────────────────────

    return {
        /**
         * Initialize the sync engine.
         * @param {Function} dataCallback - Called with { server_time, boats, stats, notifications }
         */
        init(dataCallback) {
            if (state.isDestroyed) return;
            if (state.pollTimer) {
                // Already initialized, just update callback
                state.dataCallback = dataCallback;
                return;
            }

            state.dataCallback = dataCallback;

            // Immediate first sync
            doSync();

            // Start polling
            startPolling();

            // Listen for visibility changes
            document.addEventListener('visibilitychange', onVisibilityChange);
            window.addEventListener('focus', onWindowFocus);
            window.addEventListener('blur', onWindowBlur);

            // Listen for custom action-complete events (fired after any CRUD)
            document.addEventListener('action-complete', () => {
                doSync();
            });

            // Listen for action-complete on the window as well (for cross-tab awareness)
            window.addEventListener('storage', (e) => {
                if (e.key === 'brms-action-complete') {
                    doSync();
                }
            });

            console.log('[RealtimeSync] ✓ Engine started');
        },

        /**
         * Force an immediate sync (used after any CRUD action).
         * Dispatches 'action-complete' event for notifications and other listeners.
         * Broadcasts to other tabs via localStorage for cross-tab synchronization.
         */
        syncNow() {
            // Dispatch event for notification dropdown and other listeners
            document.dispatchEvent(new CustomEvent('action-complete'));

            // Force-sync: bypass the isPolling guard so actions are reflected immediately
            state.isPolling = false;
            state.syncPromise = null;

            doSync();

            // Broadcast to other tabs via localStorage
            try {
                localStorage.setItem('brms-action-complete', Date.now().toString());
                // Clean up immediately to allow future triggers
                setTimeout(() => localStorage.removeItem('brms-action-complete'), 100);
            } catch (e) {
                // localStorage may be unavailable
            }
        },

        /**
         * Stop the engine and clean up.
         */
        destroy() {
            state.isDestroyed = true;
            stopPolling();
            document.removeEventListener('visibilitychange', onVisibilityChange);
            window.removeEventListener('focus', onWindowFocus);
            window.removeEventListener('blur', onWindowBlur);
            state.dataCallback = null;
            state.connectionLostShown = false;
            console.log('[RealtimeSync] Engine stopped');
        },

        /**
         * Get approximate server time.
         */
        getServerTime() {
            return getServerTime();
        },

        /**
         * Get the offset between server and client time (server_ms - client_ms).
         */
        getServerTimeOffset() {
            return state.serverTimeOffset;
        },

        /**
         * Check if engine is connected.
         */
        isConnected() {
            return state.isConnected;
        }
    };
})();

// Expose globally
window.RealtimeSync = RealtimeSync;
