/**
 * Admin Actions - Extend, Reduce, Force End, Complete, Maintenance
 * Handles all admin rental override operations with AJAX
 * 
 * All operations include:
 * - User confirmation dialogs
 * - Friendly error messages (never show SQL exceptions)
 * - Activity logging (handled server-side)
 * - Notification to worker (handled server-side)
 * - Calls RealtimeSync.syncNow() after success for immediate cross-tab sync
 */

// Extend time presets
const EXTEND_PRESETS = [5, 10, 15, 30];

// Reduce time presets
const REDUCE_PRESETS = [5, 10];

/**
 * Extend rental time
 */
window.extendRental = async function(rentalId, minutes) {
    if (!confirm(`Extend rental by ${minutes} minutes?\n\nBoat will get ${minutes} additional minutes.`)) return;
    
    try {
        const res = await fetch(`/api/rentals/${rentalId}/extend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ minutes })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message || `Extended by ${minutes} minutes.`);
            triggerSync();
        } else {
            showToast('error', data.message || 'Unable to extend time. Please try again.');
        }
    } catch (err) {
        console.error('Extend error:', err);
        showToast('error', 'Unable to extend time. Please try again.');
    }
};

/**
 * Reduce rental time with confirmation
 */
window.reduceRental = async function(rentalId, minutes, boatNumber) {
    const msg = `Reduce ${minutes} minutes from Boat #${boatNumber}?\n\nTimer will be shortened by ${minutes} minutes.`;
    if (!confirm(msg)) return;
    
    try {
        const res = await fetch(`/api/rentals/${rentalId}/reduce`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ minutes })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message || `Reduced by ${minutes} minutes.`);
            triggerSync();
        } else {
            showToast('error', data.message || 'Unable to reduce time. Please try again.');
        }
    } catch (err) {
        console.error('Reduce error:', err);
        showToast('error', 'Unable to reduce time. Please try again.');
    }
};

/**
 * Force-end rental with confirmation
 */
window.forceEndRental = async function(rentalId, boatNumber) {
    const msg = boatNumber
        ? `Force end Boat #${boatNumber}?\n\nThis will immediately end the rental. The boat will become available.`
        : 'Force end this rental?\n\nThis will immediately end the rental.';
    
    if (!confirm(msg)) return;
    
    const notes = prompt('Reason for force-end (optional):');
    
    try {
        const res = await fetch(`/api/rentals/${rentalId}/force-end`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ notes: notes || '' })
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message || 'Rental force-ended successfully.');
            triggerSync();
        } else {
            showToast('error', data.message || 'Unable to force-end rental. Please try again.');
        }
    } catch (err) {
        console.error('Force-end error:', err);
        showToast('error', 'Unable to force-end rental. Please try again.');
    }
};

/**
 * Mark rental as completed
 */
window.completeRental = async function(rentalId) {
    if (!confirm('Mark this rental as completed?\n\nThis will end the rental and make the boat available.')) return;
    
    try {
        const res = await fetch(`/api/rentals/${rentalId}/complete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message || 'Rental marked as completed.');
            triggerSync();
        } else {
            showToast('error', data.message || 'Unable to complete rental. Please try again.');
        }
    } catch (err) {
        console.error('Complete error:', err);
        showToast('error', 'Unable to complete rental. Please try again.');
    }
};

/**
 * Move boat to maintenance
 */
window.moveToMaintenance = async function(boatId) {
    if (!confirm('Move this boat to maintenance?\n\nIt will no longer be available for rentals.')) return;
    
    try {
        const res = await fetch(`/api/boats/${boatId}/maintenance`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message || 'Boat moved to maintenance.');
            triggerSync();
        } else {
            showToast('error', data.message || 'Unable to update maintenance status. Please try again.');
        }
    } catch (err) {
        console.error('Maintenance error:', err);
        showToast('error', 'Unable to update maintenance status. Please try again.');
    }
};

/**
 * Remove boat from maintenance
 */
window.removeFromMaintenance = async function(boatId) {
    if (!confirm('Make this boat available?\n\nIt will be available for new rentals.')) return;
    
    try {
        const res = await fetch(`/api/boats/${boatId}/available`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            showToast('success', data.message || 'Boat is now available.');
            triggerSync();
        } else {
            showToast('error', data.message || 'Unable to update availability. Please try again.');
        }
    } catch (err) {
        console.error('Available error:', err);
        showToast('error', 'Unable to update availability. Please try again.');
    }
};

/**
 * Open extend modal
 */
window.openExtendModal = function(rentalId, boatNumber) {
    const modal = document.getElementById('extend-modal');
    if (!modal) return;
    document.getElementById('extend-rental-id').value = rentalId;
    document.getElementById('extend-boat-info').textContent = `Boat #${boatNumber}`;
    document.getElementById('extend-custom-minutes').value = '';
    
    // Reset preset buttons
    document.querySelectorAll('#extend-presets .preset-btn').forEach(b => b.classList.remove('active'));
    
    modal.style.display = 'flex';
};

/**
 * Open reduce modal
 */
window.openReduceModal = function(rentalId, boatNumber) {
    const modal = document.getElementById('reduce-modal');
    if (!modal) return;
    document.getElementById('reduce-rental-id').value = rentalId;
    document.getElementById('reduce-boat-info').textContent = `Boat #${boatNumber}`;
    document.getElementById('reduce-custom-minutes').value = '';
    
    document.querySelectorAll('#reduce-presets .preset-btn').forEach(b => b.classList.remove('active'));
    
    modal.style.display = 'flex';
};

document.addEventListener('DOMContentLoaded', function() {
    // Extend preset buttons
    document.querySelectorAll('#extend-presets .preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#extend-presets .preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('extend-custom-minutes').value = this.dataset.minutes;
        });
    });

    // Reduce preset buttons
    document.querySelectorAll('#reduce-presets .preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#reduce-presets .preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('reduce-custom-minutes').value = this.dataset.minutes;
        });
    });

    // Confirm extend
    const confirmExtend = document.getElementById('confirm-extend');
    if (confirmExtend) {
        confirmExtend.addEventListener('click', function() {
            const rentalId = document.getElementById('extend-rental-id').value;
            const minutes = parseInt(document.getElementById('extend-custom-minutes').value) || 0;
            if (minutes < 1) {
                showToast('error', 'Please enter a valid number of minutes (1-120).');
                return;
            }
            if (minutes > 120) {
                showToast('error', 'Maximum extension is 120 minutes.');
                return;
            }
            document.getElementById('extend-modal').style.display = 'none';
            extendRental(rentalId, minutes);
        });
    }

    // Confirm reduce
    const confirmReduce = document.getElementById('confirm-reduce');
    if (confirmReduce) {
        confirmReduce.addEventListener('click', function() {
            const rentalId = document.getElementById('reduce-rental-id').value;
            const boatInfo = document.getElementById('reduce-boat-info').textContent;
            const boatNumber = boatInfo.replace('Boat #', '');
            const minutes = parseInt(document.getElementById('reduce-custom-minutes').value) || 0;
            if (minutes < 1) {
                showToast('error', 'Please enter a valid number of minutes (1-120).');
                return;
            }
            if (minutes > 120) {
                showToast('error', 'Maximum reduction is 120 minutes.');
                return;
            }
            document.getElementById('reduce-modal').style.display = 'none';
            reduceRental(rentalId, minutes, boatNumber);
        });
    }

    // Close modals on backdrop click
    document.querySelectorAll('.time-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // Close on X
    document.querySelectorAll('.time-modal .close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.time-modal').style.display = 'none';
        });
    });
});

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}
