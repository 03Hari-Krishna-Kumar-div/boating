/**
 * Workflow Actions
 * ================
 * Mark Received, Transfer Ownership, and related UI helpers.
 * Exposed as global functions for onclick handlers in Blade views.
 */

/**
 * Mark a rental as received (boat becomes available).
 */
async function markReceived(rentalId) {
    if (!confirm('Mark this boat as received? It will become available for all workers.')) return;

    try {
        const response = await fetch(`/api/rentals/${rentalId}/receive`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            showToast('success', data.message || 'Boat received successfully!');
            triggerSync();
        } else {
            showToast('error', data.message || 'Failed to mark as received.');
        }
    } catch (err) {
        showToast('error', 'Network error. Please try again.');
    }
}

/**
 * Open the Transfer Ownership modal.
 */
function openTransferModal(rentalId, boatNumber) {
    const modal = document.getElementById('transfer-modal');
    if (!modal) return;

    document.getElementById('transfer-rental-id').value = rentalId;
    document.getElementById('transfer-boat-info').textContent = `Transfer Boat #${boatNumber}`;
    modal.style.display = 'flex';
}

/**
 * Confirm transfer ownership (called by modal button).
 */
async function confirmTransfer() {
    const rentalId = document.getElementById('transfer-rental-id')?.value;
    const workerId = document.getElementById('transfer-worker-id')?.value;

    if (!rentalId || !workerId) {
        showToast('error', 'Please select a worker.');
        return;
    }

    if (!confirm('Transfer this boat to the selected worker?')) return;

    try {
        const response = await fetch(`/api/rentals/${rentalId}/transfer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ worker_id: parseInt(workerId) }),
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            showToast('success', data.message || 'Ownership transferred!');
            closeModal('transfer-modal');
            triggerSync();
        } else {
            showToast('error', data.message || 'Failed to transfer ownership.');
        }
    } catch (err) {
        showToast('error', 'Network error. Please try again.');
    }
}

/**
 * Trigger RealtimeSync (if available).
 */
function triggerSync() {
    if (window.RealtimeSync) {
        RealtimeSync.syncNow();
    }
}

/**
 * Close a modal by ID.
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

/**
 * Confirm or deny return (called by return popup YES/NO buttons).
 */
async function confirmReturn(returned) {
    const rentalId = window.currentAwaitingRentalId;
    if (!rentalId) {
        showToast('error', 'No rental to confirm.');
        return;
    }

    if (returned) {
        if (!confirm('Confirm that the customer has returned the boat?')) return;
    } else {
        if (!confirm('Mark this boat as still out? Overtime will start.')) return;
    }

    try {
        const response = await fetch(`/api/rentals/${rentalId}/confirm-return`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ returned }),
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            showToast('success', data.message || (returned ? 'Return confirmed.' : 'Marked as still out.'));
            closeModal('return-popup');
            if (window.stopAlarm) window.stopAlarm();
            triggerSync();
        } else {
            showToast('error', data.message || 'Failed to process return confirmation.');
        }
    } catch (err) {
        showToast('error', 'Network error. Please try again.');
    }
}

// ─── Expose globally ─────────────────────────────────────
window.markReceived = markReceived;
window.openTransferModal = openTransferModal;
window.confirmTransfer = confirmTransfer;
window.confirmReturn = confirmReturn;
window.closeModal = closeModal;
window.triggerSync = triggerSync;

// ─── Init: attach confirm-transfer button handler ─────────
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirm-transfer');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmTransfer);
    }

    // Close modal handlers
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.time-modal');
            if (modal) modal.style.display = 'none';
        });
    });
});
