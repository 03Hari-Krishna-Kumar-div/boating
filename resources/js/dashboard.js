/**
 * Dashboard Manager
 * ==================
 * Updates boat cards, stats, filters, search, return confirmation popup.
 * Integrated with RealtimeSync engine — called on each sync cycle.
 */

const FIVE_MIN_THRESHOLD = 300; // 300 seconds = 5 minutes (shared with timer.js)

let currentFilter = 'all';
let currentSearch = '';

/**
 * Called by RealtimeSync on each data refresh.
 * Replaces the old `updateDashboard()` that was called by ajax-polling.js.
 */
function onDashboardData(data) {
    // Sync timer drift correction — reset interpolation reference
    if (window.syncTimerFromServer) {
        window.syncTimerFromServer();
    }

    // Update server time displays
    const serverTimeDisplay = document.getElementById('server-time-display');
    const tvServerTime = document.getElementById('tv-server-time');
    if (data.server_time) {
        const d = new Date(data.server_time);
        const timeStr = d.toLocaleTimeString('en-US', { hour12: false });
        if (serverTimeDisplay) serverTimeDisplay.textContent = timeStr;
        if (tvServerTime) tvServerTime.textContent = timeStr;
    }

    // Update stats
    if (data.stats) {
        updateStats(data.stats);
    }

    // Update boats (DOM diff — only update changed cards)
    if (data.boats) {
        updateBoatCards(data.boats);
    }

    // Show toast notifications for new items
    if (data.notifications && data.notifications.length > 0) {
        data.notifications.forEach(n => {
            if (window.showToast) window.showToast(n.type, n.message);
        });
    }

    // Refresh notification dropdown count from server
    if (window.fetchUnreadNotifications) {
        window.fetchUnreadNotifications();
    }

    // Check if current worker needs to confirm return
    if (data.boats) {
        checkReturnConfirmations(data.boats);
    }

    // Update last-update timestamp
    const lastUpdate = document.getElementById('last-update');
    if (lastUpdate) {
        lastUpdate.textContent = 'Updated: ' + new Date().toLocaleTimeString();
    }
}

/**
 * Update stat counters (both dashboard and TV elements).
 */
function updateStats(stats) {
    const statMap = {
        'stat-available': stats.available_boats,
        'stat-active': stats.active_rentals,
        'stat-warning': stats.warning_boats,
        'stat-overdue': stats.overdue_boats,
        'stat-awaiting': stats.awaiting_boats,
        'stat-online': stats.online_workers,
        'stat-maintenance': stats.maintenance_boats,
        'stat-total': stats.total_boats
    };

    Object.entries(statMap).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? 0;

        // Also update TV-styled stats (used in some dashboards)
        const tvEl = document.getElementById('tv-' + id);
        if (tvEl) tvEl.textContent = value ?? 0;
    });
}

/**
 * Update boat cards using targeted DOM manipulation.
 * Only updates changed data — never rebuilds entire HTML.
 * Supports multiple containers (Admin single grid / Worker sections).
 */
function updateBoatCards(boats) {
    // Collect all boat card wrappers from any container
    const allCards = document.querySelectorAll('.boat-card-wrapper');
    const existingCards = new Map();
    allCards.forEach(card => {
        const id = card.dataset.boatId;
        if (id) existingCards.set(parseInt(id), card);
    });

    boats.forEach(boat => {
        const existing = existingCards.get(boat.id);
        if (existing) {
            updateBoatCard(existing, boat);
        }
    });

    // ── Worker: move cards between sections in real-time ──
    const mySection = document.getElementById('worker-my-boats');
    if (mySection) {
        const userId = window.currentWorkerId;
        boats.forEach(boat => {
            const card = existingCards.get(boat.id);
            if (!card) return;

            let targetId;
            if (boat.current_rental && boat.current_rental.worker_id === userId) {
                targetId = 'worker-my-boats';
            } else if (boat.status === 'available') {
                targetId = 'worker-available-boats';
            } else {
                targetId = 'worker-other-boats';
            }

            const target = document.getElementById(targetId);
            if (target && card.parentNode !== target) {
                target.appendChild(card);
            }
        });

        // Show/hide empty sections
        ['worker-my-boats', 'worker-available-boats', 'worker-other-boats'].forEach(id => {
            const container = document.getElementById(id);
            if (container) {
                const section = container.closest('.mb-4');
                if (section) {
                    section.style.display = container.children.length > 0 ? '' : 'none';
                }
            }
        });
    }

    // Re-apply filters without resetting them
    applyFilters();
}

/**
 * Update a single boat card element with new data.
 * Does NOT rebuild the card — only patches changed attributes/text.
 * Allows client-side countdown interpolation to continue smoothly.
 */
function updateBoatCard(cardElement, boat) {
    // ── Status badge ──
    const statusBadge = cardElement.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.textContent = boat.status_label;
        statusBadge.style.background = boat.status_color + '20';
        statusBadge.style.color = boat.status_color;
        statusBadge.style.borderColor = boat.status_color;
    }

    // ── Card status class ──
    cardElement.dataset.status = boat.status;
    cardElement.dataset.workerId = boat.current_rental?.worker_id ?? '';
    const card = cardElement.querySelector('.boat-card');
    if (card) {
        card.classList.remove('boat-card-warning', 'boat-card-overdue', 'boat-card-awaiting', 'boat-card-time-up', 'boat-card-ended');
        // Server-driven status classes
        if (boat.status === 'overdue') card.classList.add('boat-card-overdue');
        if (boat.status === 'time_up') card.classList.add('boat-card-time-up');
        if (boat.status === 'ended') card.classList.add('boat-card-ended');
        if (boat.status === 'awaiting_confirmation') card.classList.add('boat-card-awaiting');

        // ── 5-minute orange breathing animation ──
        // Apply when remaining_seconds <= 300 for any active rental
        // This covers both server "warning" status and "occupied" with <5min remaining
        const currentRental = boat.current_rental;
        const remaining = currentRental?.remaining_seconds ?? 0;
        if (remaining > 0 && remaining <= FIVE_MIN_THRESHOLD) {
            card.classList.add('boat-card-warning');
        }
    }
    // Store rental ID for 5-minute notification tracking
    const prevRentalId = cardElement.dataset.rentalId;
    const newRentalId = boat.current_rental?.id || 0;
    if (newRentalId && prevRentalId && prevRentalId !== String(newRentalId)) {
        // Rental changed — reset the 5-minute warning flag for this rental
        if (window.resetFiveMinWarningForRental) {
            window.resetFiveMinWarningForRental(newRentalId);
        }
        // Also reset the timer-up notification flag (so it fires again for the new rental)
        if (window.resetTimerUpNotifForRental) {
            window.resetTimerUpNotifForRental(newRentalId);
        }
    }
    cardElement.dataset.rentalId = newRentalId;

    // ── Timer data attributes (authoritative server values for interpolation) ──
    const timerDisplay = cardElement.querySelector('.timer-display');
    if (timerDisplay) {
        const currentRental = boat.current_rental;
        const remaining = currentRental?.remaining_seconds ?? 0;
        const overtime = currentRental?.overtime_seconds ?? 0;
        timerDisplay.dataset.remaining = remaining;
        timerDisplay.dataset.overtime = overtime;
        timerDisplay.dataset.status = boat.status;
    }

    // ── Overtime container visibility ──
    const overtimeContainer = cardElement.querySelector('[id^="overtime-container-"]');
    if (overtimeContainer) {
        overtimeContainer.style.display = ['overdue', 'time_up'].includes(boat.status) ? 'block' : 'none';
    }

    // ── Toggle rental-section vs available-section ──
    const rentalSection = cardElement.querySelector('.rental-section');
    const availableSection = cardElement.querySelector('.available-section');
    if (rentalSection && availableSection) {
        if (boat.current_rental) {
            rentalSection.style.display = '';
            availableSection.style.display = 'none';
        } else {
            rentalSection.style.display = 'none';
            availableSection.style.display = '';
        }
    }

    // ── Start and End times ──
    const boatId = cardElement.dataset.boatId;

    // ── Worker name and ID ──
    const workerNameSpan = document.getElementById(`worker-name-${boatId}`);
    if (workerNameSpan) {
        workerNameSpan.textContent = boat.current_rental?.worker_name ?? 'Unknown';
    }
    const workerIdSmall = cardElement.querySelector('.fw-semibold small.text-muted');
    if (workerIdSmall) {
        workerIdSmall.textContent = `(#${boat.current_rental?.worker_id})`;
    }
    const rental = boat.current_rental;
    if (rental) {
        const startEl = document.getElementById(`started-${boatId}`);
        if (startEl) startEl.textContent = rental.started_at ?? '';
        const endEl = document.getElementById(`expected-end-${boatId}`);
        if (endEl) endEl.textContent = rental.effective_end_at ?? '';

        // Extended minutes indicator
        const extIndicator = cardElement.querySelector('.text-info i.bi-plus-circle');
        if (extIndicator) {
            const extParent = extIndicator.closest('small');
            if (extParent) {
                extParent.style.display = rental.extended_minutes > 0 ? '' : 'none';
                if (rental.extended_minutes > 0) {
                    extParent.innerHTML = `<i class="bi bi-plus-circle"></i> +${rental.extended_minutes}m`;
                }
            }
        }

        // Reduced minutes indicator
        const redIndicator = cardElement.querySelector('.text-warning i.bi-dash-circle');
        if (redIndicator) {
            const redParent = redIndicator.closest('small');
            if (redParent) {
                redParent.style.display = rental.reduced_minutes > 0 ? '' : 'none';
                if (rental.reduced_minutes > 0) {
                    redParent.innerHTML = `<i class="bi bi-dash-circle"></i> -${rental.reduced_minutes}m`;
                }
            }
        }

        // ── Progress bar ──
        const progressBar = cardElement.querySelector('.progress-bar');
        if (progressBar) {
            const expectedEnd = rental.effective_end_at_full ? new Date(rental.effective_end_at_full).getTime() : null;
            const startedAt = rental.started_at_full ? new Date(rental.started_at_full).getTime() : null;
            if (expectedEnd && startedAt && expectedEnd > startedAt) {
                const now = Date.now();
                const total = expectedEnd - startedAt;
                const elapsed = Math.max(0, Math.min(total, now - startedAt));
                const pct = Math.min(100, Math.round((elapsed / total) * 100));
                progressBar.style.width = pct + '%';
                progressBar.style.background = pct > 80 ? '#ffc107' : '#198754';
                progressBar.setAttribute('aria-valuenow', pct);
            }
        }
    }

    // ── Admin Time Controls (Extend / Reduce) ──
    const timeControls = cardElement.querySelector('.admin-time-controls');
    const isAdmin = cardElement.dataset.isAdmin === '1';
    if (timeControls) {
        const showTimeControls = isAdmin && ['occupied', 'warning', 'time_up'].includes(boat.status);
        timeControls.style.display = showTimeControls ? '' : 'none';

        // Update rental ID on all extend/reduce buttons (they were rendered with stale IDs)
        if (showTimeControls && rental) {
            const presetBtns = timeControls.querySelectorAll('[onclick^="extendRental"], [onclick^="reduceRental"]');
            presetBtns.forEach(btn => {
                const match = btn.getAttribute('onclick')?.match(/^(extendRental|reduceRental)\((?:null|\d+),\s*(\d+)/);
                if (match) {
                    btn.setAttribute('onclick', `${match[1]}(${rental.id}, ${match[2]})`);
                }
            });
            // Also update the modal openers and End/Force End/Transfer buttons
            const extendModalBtn = timeControls.querySelector('[onclick^="openExtendModal"]');
            if (extendModalBtn) {
                extendModalBtn.setAttribute('onclick', `openExtendModal(${rental.id}, ${boat.boat_number})`);
            }
            const reduceModalBtn = timeControls.querySelector('[onclick^="openReduceModal"]');
            if (reduceModalBtn) {
                reduceModalBtn.setAttribute('onclick', `openReduceModal(${rental.id}, ${boat.boat_number})`);
            }
        }
    }

    // ── Action buttons visibility ──
    const endBtn = cardElement.querySelector('[data-boat-action="end-rental"]');
    if (endBtn) {
        endBtn.style.display = ['occupied', 'warning', 'time_up', 'overdue'].includes(boat.status) ? '' : 'none';
        if (rental) {
            endBtn.setAttribute('onclick', `endRental(${rental.id})`);
        }
    }
    const receiveBtn = cardElement.querySelector('[data-boat-action="mark-received"]');
    if (receiveBtn) {
        receiveBtn.style.display = boat.status === 'ended' ? '' : 'none';
        if (rental) {
            receiveBtn.setAttribute('onclick', `markReceived(${rental.id})`);
        }
    }
    // Force End button — visible only for admin AND when there's a current rental
    const forceEndBtn = cardElement.querySelector('.admin-force-end-btn');
    if (forceEndBtn) {
        forceEndBtn.style.display = (isAdmin && rental) ? '' : 'none';
        if (rental) {
            forceEndBtn.setAttribute('onclick', `forceEndRental(${rental.id}, ${boat.boat_number})`);
        }
    }
    // Transfer button — visible only for admin AND rental in active/ended states
    const transferBtn = cardElement.querySelector('.admin-transfer-btn');
    if (transferBtn) {
        const showTransfer = isAdmin && rental && ['occupied', 'warning', 'time_up', 'ended'].includes(boat.status);
        transferBtn.style.display = showTransfer ? '' : 'none';
        if (showTransfer) {
            transferBtn.setAttribute('onclick', `openTransferModal(${rental.id}, ${boat.boat_number})`);
        }
    }

    // ── Available section action buttons ──
    const startBtn = cardElement.querySelector('[data-available-action="start-rental"]');
    const maintBtn = cardElement.querySelector('[data-available-action="move-to-maintenance"]');
    const maintInfo = cardElement.querySelector('.maintenance-info');
    if (boat.status === 'available') {
        if (startBtn) startBtn.style.display = '';
        if (maintBtn) maintBtn.style.display = '';
        if (maintInfo) maintInfo.style.display = 'none';
    } else if (boat.status === 'maintenance') {
        if (startBtn) startBtn.style.display = 'none';
        if (maintBtn) maintBtn.style.display = 'none';
        if (maintInfo) maintInfo.style.display = '';
    } else {
        if (startBtn) startBtn.style.display = 'none';
        if (maintBtn) maintBtn.style.display = 'none';
        if (maintInfo) maintInfo.style.display = 'none';
    }
}

/**
 * Apply current filters and search to the boat cards.
 * Preserves existing filter/search state.
 */
function applyFilters() {
    const cards = document.querySelectorAll('.boat-card-wrapper');
    cards.forEach(card => {
        const status = card.dataset.status;
        const boatNumber = card.dataset.boatNumber || '';

        let show = true;

        if (currentFilter !== 'all' && status !== currentFilter) {
            show = false;
        }

        if (currentSearch && !boatNumber.includes(currentSearch)) {
            show = false;
        }

        card.style.display = show ? '' : 'none';
    });
}

/**
 * Check if any boat is awaiting confirmation and belongs to the current worker.
 * Shows the return confirmation popup if needed.
 */
function checkReturnConfirmations(boats) {
    let hasPopup = false;

    boats.forEach(boat => {
        if (boat.status === 'awaiting_confirmation' && boat.is_current_worker) {
            showReturnPopup(boat);
            hasPopup = true;
        }
    });

    if (!hasPopup) {
        const popup = document.getElementById('return-popup');
        if (popup) popup.style.display = 'none';
        if (window.stopAlarm) window.stopAlarm();
    }
}

/**
 * Display the return confirmation popup for a boat.
 */
function showReturnPopup(boat) {
    const popup = document.getElementById('return-popup');
    const boatNum = document.getElementById('popup-boat-number');
    const timer = document.getElementById('popup-timer');

    if (popup) popup.style.display = 'flex';
    if (boatNum) boatNum.textContent = boat.boat_number;
    if (timer) timer.textContent = '00:00';

    // Store the rental ID for the popup confirm/deny actions
    if (boat.current_rental_id) {
        window.currentAwaitingRentalId = boat.current_rental_id;
    }

    if (window.playAlarm) window.playAlarm();
}

// ─── Expose to global scope ───────────────────────────────
window.onDashboardData = onDashboardData;
window.applyFilters = applyFilters;
window.checkReturnConfirmations = checkReturnConfirmations;

// ─── Init: search, filters, and RealtimeSync registration ──
document.addEventListener('DOMContentLoaded', function() {
    // Search input with 300ms debounce
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = this.value;
                applyFilters();
            }, 300);
        });
    }

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.status;
            applyFilters();
        });
    });

    // Register with RealtimeSync engine
    if (window.RealtimeSync) {
        RealtimeSync.init(onDashboardData);
    }
});
