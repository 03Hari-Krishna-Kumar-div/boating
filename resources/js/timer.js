/**
 * Timer Engine - Client-side countdown with server drift correction
 * 
 * Architecture:
 * - Server is AUTHORITATIVE for time values
 * - AJAX poll updates remaining_seconds and overtime_seconds each second
 * - Client interpolates between polls using a 200ms interval
 * - On each poll, drift is corrected by resetting to server values
 * - Timer never shows negative values
 * - No duplicate countdowns
 * - No timer jumps (smooth interpolation)
 */

let countdownInterval = null;
let lastServerSync = 0;
let alarmInterval = null;
let audioCtx = null;
let audioUnlocked = false;

// ── 5-Minute Warning System ───────────────────────────────────
const FIVE_MIN_THRESHOLD = 300; // 300 seconds = 5 minutes
let fiveMinWarningFired = new Map(); // rentalId -> boolean
let fiveMinAudio = null; // Single shared Audio object
let fiveMinAudioPlaying = false; // Debounce: prevent overlapping plays

// ── Timer-Up Notification (1_notification.mp3) for worker ──────
let timerUpNotifFired = new Map(); // rentalId -> boolean

function startCountdownInterpolation() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    
    countdownInterval = setInterval(() => {
        const now = Date.now();
        const elapsedSinceSync = lastServerSync ? Math.floor((now - lastServerSync) / 1000) : 0;
        
        document.querySelectorAll('.timer-display').forEach(el => {
            const status = el.dataset.status || '';
            
            // Available/Maintenance boats: show dashes
            if (status === 'available' || status === 'maintenance') {
                el.textContent = '--';
                el.style.color = '';
                return;
            }
            
            // Ended boats: show static message
            if (status === 'ended') {
                el.innerHTML = '<span style="color:var(--clay-text-light);font-size:0.8rem;">— Ended —</span>';
                return;
            }
            
            // Get authoritative values from server (set during AJAX poll)
            let remaining = parseInt(el.dataset.remaining) || 0;
            let overtime = parseInt(el.dataset.overtime) || 0;
            
            if (status === 'overdue' || status === 'time_up') {
                // Overtime: increment each second
                const displayOvertime = Math.max(0, overtime + elapsedSinceSync);
                
                const mins = Math.floor(displayOvertime / 60);
                const secs = displayOvertime % 60;
                el.innerHTML = `<span class="overtime-counter">+${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}</span>`;
                el.style.color = '#dc3545';

                // Play alarm for time_up or overdue status
                if (status === 'time_up' || status === 'overdue') {
                    playAlarm();
                }
            } else {
                // Countdown: decrement each second, never below 0
                const displayRemaining = Math.max(0, remaining - elapsedSinceSync);
                
                const mins = Math.floor(displayRemaining / 60);
                const secs = displayRemaining % 60;
                el.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                
                // Color: warning when < 60 seconds
                if (displayRemaining < 60 && displayRemaining > 0) {
                    el.style.color = '#ffc107';
                } else if (displayRemaining <= 0) {
                    el.style.color = '#dc3545';
                } else {
                    el.style.color = '';
                }
            }
        });
        
        // ── Check 5-minute warning threshold ──
        checkFiveMinWarning();

        // ── Play 1_notification.mp3 to worker whose timer hit 0 ──
        playTimerUpNotification();

    }, 200); // 200ms for smooth countdown
}

/**
 * Called by dashboard.js updateBoatCard() on each AJAX poll.
 * Resets server time reference to eliminate drift.
 * This is the ONLY place lastServerSync should be reset,
 * so the 200ms interval can properly interpolate between polls.
 */
function syncTimerFromServer() {
    lastServerSync = Date.now();
}

/**
 * Get or create the single shared Audio object for the 5-minute warning.
 * Prevents memory leaks from recreating Audio objects.
 */
function getFiveMinAudio() {
    if (!fiveMinAudio) {
        fiveMinAudio = new Audio('/sounds/6_notification.mp3');
        fiveMinAudio.preload = 'auto';
    }
    return fiveMinAudio;
}

/**
 * Play the 5-minute warning sound once.
 * If a sound is already playing, this call is ignored (debounce).
 * If multiple boats hit the threshold at the same moment, only one play occurs.
 */
function playFiveMinWarning() {
    if (fiveMinAudioPlaying) return;
    fiveMinAudioPlaying = true;

    const audio = getFiveMinAudio();
    audio.currentTime = 0;
    audio.play().then(() => {
        // Allow another play after the sound finishes (~1s clip)
        setTimeout(() => {
            fiveMinAudioPlaying = false;
        }, 1200);
    }).catch(() => {
        // Autoplay blocked or audio unavailable — silently ignore
        fiveMinAudioPlaying = false;
    });
}

/**
 * Check all timer displays for the 5-minute threshold.
 * Called from the 200ms countdown interval.
 * Only fires ONCE per rental (tracked by rental ID via dataset).
 * Only fires for active rentals (occupied/warning/time_up/overdue).
 * Never fires for available/ended/received/maintenance.
 */
function checkFiveMinWarning() {
    let shouldPlay = false;

    document.querySelectorAll('.timer-display').forEach(el => {
        const status = el.dataset.status || '';

        // Only check active rental statuses — never for ended/received/maintenance/available
        if (!['occupied', 'warning', 'time_up', 'overdue'].includes(status)) return;

        const remaining = parseInt(el.dataset.remaining) || 0;
        if (remaining <= 0 || remaining > FIVE_MIN_THRESHOLD) return;

        // Get rental ID from the card wrapper's dataset
        const cardWrapper = el.closest('.boat-card-wrapper');
        if (!cardWrapper) return;
        const rentalId = parseInt(cardWrapper.dataset.rentalId) || 0;
        if (!rentalId) return;

        // Fire once per rental — check the Map
        if (!fiveMinWarningFired.get(rentalId)) {
            fiveMinWarningFired.set(rentalId, true);
            shouldPlay = true;
        }
    });

    if (shouldPlay) {
        playFiveMinWarning();
    }
}

/**
 * Reset the 5-minute warning tracking for a specific rental.
 * Called when a new rental starts (rental ID changes).
 */
function resetFiveMinWarningForRental(rentalId) {
    if (rentalId) {
        fiveMinWarningFired.delete(rentalId);
    }
}

/**
 * Play 1_notification.mp3 ONCE to the worker whose timer just hit 0.
 * 
 * IMPORTANT: This does NOT rely on data-status="time_up" because the server
 * only sets that status via a scheduled cron command (brms:check-overdue)
 * which may run up to a minute later. Instead, we detect expiry by checking
 * data-remaining === "0" — the server sends remaining_seconds = 0 immediately
 * when the timer expires, so we can fire the notification right away.
 * 
 * Logic:
 * - Reads window.currentWorkerId to identify the current browser user
 * - Scans all timer-display elements with active statuses (occupied, warning,
 *   time_up, overdue) where data-remaining is exactly "0"
 * - For each such card, checks if the card's worker_id matches currentWorkerId
 * - Fires only ONCE per rental (tracked by rentalId via timerUpNotifFired Map)
 * - Does NOT loop — plays once as a notification ding
 * 
 * Called from the 200ms countdown interpolation loop.
 */
function playTimerUpNotification() {
    const currentWorkerId = window.currentWorkerId;
    if (!currentWorkerId) return;

    let shouldPlay = false;

    document.querySelectorAll('.timer-display').forEach(el => {
        const status = el.dataset.status || '';

        // Only active rental statuses — boat's status may still be 'occupied'
        // or 'warning' because the cron hasn't run yet to set 'time_up'
        if (!['occupied', 'warning', 'time_up', 'overdue'].includes(status)) return;

        // Fire when remaining_seconds is EXACTLY 0 (timer expired)
        const remaining = el.dataset.remaining;
        if (remaining !== '0') return;

        const cardWrapper = el.closest('.boat-card-wrapper');
        if (!cardWrapper) return;

        const workerId = parseInt(cardWrapper.dataset.workerId);
        const rentalId = parseInt(cardWrapper.dataset.rentalId);

        // Only fire for cards belonging to the CURRENT worker
        if (workerId !== currentWorkerId) return;

        // Ensure we have a valid rental ID and haven't fired for it yet
        if (!rentalId || timerUpNotifFired.get(rentalId)) return;

        timerUpNotifFired.set(rentalId, true);
        shouldPlay = true;
    });

    if (shouldPlay) {
        // Play 1_notification.mp3 once using the preloaded audio element
        try {
            const audioEl = document.getElementById('worker-timer-up-audio');
            if (audioEl) {
                audioEl.currentTime = 0;
                audioEl.play().catch(() => {
                    // Autoplay blocked — silently fail
                });
            } else {
                // Fallback: create a fresh Audio object
                const fallback = new Audio('/sounds/1_notification.mp3');
                fallback.play().catch(() => {});
            }
        } catch (e) {
            // Audio not supported
        }
    }
}

/**
 * Reset the timer-up notification tracking for a specific rental.
 * Called when a rental changes (new rental starts, or data resets).
 */
function resetTimerUpNotifForRental(rentalId) {
    if (rentalId) {
        timerUpNotifFired.delete(rentalId);
    }
}

/**
 * Unlock audio on first user gesture (click/touch/keydown).
 * Browsers block audio.play() and AudioContext unless triggered by user interaction.
 * This one-shot handler runs once and removes itself.
 */
function initAudioUnlock() {
    function unlock() {
        if (audioUnlocked) return;
        // Unlock AudioContext
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        } catch (e) {
            audioCtx = null;
        }
        // Unlock HTML audio elements
        ['alarm-audio', 'worker-timer-up-audio'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.load();
                el.play().then(() => {
                    el.pause();
                    el.currentTime = 0;
                }).catch(() => {});
            }
        });
        audioUnlocked = true;
        document.removeEventListener('click', unlock);
        document.removeEventListener('touchstart', unlock);
        document.removeEventListener('keydown', unlock);
    }
    document.addEventListener('click', unlock);
    document.addEventListener('touchstart', unlock);
    document.addEventListener('keydown', unlock);
}

/**
 * Play alarm sound for time_up status.
 * Uses Web Audio API beep as primary (always works after unlock).
 * Uses HTML audio element as enhancement (for custom alarm sounds).
 */
function playAlarm() {
    if (alarmInterval) return; // Already playing

    // If not yet unlocked, try to unlock now
    if (!audioUnlocked) {
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            audioUnlocked = true;
        } catch (e) {
            audioCtx = null;
        }
    }

    // Start audio element (enhancement)
    const audioEl = document.getElementById('alarm-audio');
    if (audioEl) {
        audioEl.loop = true;
        audioEl.play().catch(() => {});
    }

    alarmInterval = setInterval(() => {
        // Check if we should stop — no time_up cards AND no awaiting_confirmation popup
        const hasTimeUp = document.querySelector('.timer-display[data-status="time_up"]');
        const hasAwaitingPopup = document.getElementById('return-popup')?.style?.display === 'flex';
        if (!hasTimeUp && !hasAwaitingPopup) {
            stopAlarm();
            return;
        }
        // Web Audio API beep (primary audio — works even if MP3 fails)
        if (audioCtx) {
            try {
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = 880;
                gain.gain.value = 0.3;
                osc.start();
                osc.stop(audioCtx.currentTime + 0.2);
            } catch (e) {
                // Web Audio not available
            }
        }
    }, 1000);
}

/**
 * Stop the alarm.
 */
function stopAlarm() {
    if (alarmInterval) {
        clearInterval(alarmInterval);
        alarmInterval = null;
    }
    const audioEl = document.getElementById('alarm-audio');
    if (audioEl) {
        audioEl.pause();
        audioEl.currentTime = 0;
    }
}

// Start rental
async function startRental(boatId) {
    if (!confirm('Start rental for this boat?')) return;
    
    try {
        const response = await fetch('/api/rentals/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ boat_id: boatId })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Rental started successfully!');
            triggerSync();
        } else {
            showToast('error', data.message || 'Failed to start rental.');
        }
    } catch (err) {
        showToast('error', 'Network error. Please try again.');
    }
}

// End rental (worker or admin)
async function endRental(rentalId) {
    if (!confirm('End this rental? The boat will be marked as ended awaiting receipt.')) return;
    
    try {
        const response = await fetch(`/api/rentals/${rentalId}/end`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ notes: '' })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast('success', data.message || 'Rental ended successfully.');
            stopAlarm(); // Stop alarm when rental is ended
            triggerSync();
        } else {
            showToast('error', data.message || 'Failed to end rental.');
        }
    } catch (err) {
        showToast('error', 'Network error. Please try again.');
    }
}

// Expose to global scope for onclick handlers
window.startRental = startRental;
window.endRental = endRental;
window.syncTimerFromServer = syncTimerFromServer;
window.playAlarm = playAlarm;
window.stopAlarm = stopAlarm;
window.resetFiveMinWarningForRental = resetFiveMinWarningForRental;
window.resetTimerUpNotifForRental = resetTimerUpNotifForRental;

document.addEventListener('DOMContentLoaded', function() {
    startCountdownInterpolation();
    initAudioUnlock();
});
