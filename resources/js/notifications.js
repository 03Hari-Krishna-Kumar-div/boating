/**
 * Notifications Manager - Toast messages, browser notifications
 * 
 * Integrated with RealtimeSync:
 * - Toast notifications come from the dashboard API data (1s poll)
 * - Notification dropdown list is updated via updateNotifDropdown()
 * - The redundant 5-second poll for unread count has been removed.
 * 
 * Note: Audio alarm functions (playAlarm/stopAlarm) are in timer.js.
 */

function initNotifications() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

function showToast(type, message) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    // Deduplicate: remove existing toast with same message
    const existing = container.querySelectorAll('.toast-clay .toast-body span.flex-grow-1');
    for (const span of existing) {
        if (span.textContent === message) {
            span.closest('.toast-clay').remove();
        }
    }

    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info: 'bi-info-circle-fill'
    };

    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#0d6efd'
    };

    const toast = document.createElement('div');
    toast.className = 'toast-clay toast show mb-2';
    toast.innerHTML = `
        <div class="toast-body d-flex align-items-center gap-2">
            <i class="bi ${icons[type] || icons.info}" style="color:${colors[type] || colors.info};font-size:1.2rem;"></i>
            <span class="flex-grow-1">${message}</span>
            <button type="button" class="btn-close btn-close-sm" onclick="this.closest('.toast').remove()"></button>
        </div>
    `;

    container.prepend(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);

    if (type === 'warning' && 'Notification' in window && Notification.permission === 'granted') {
        new Notification('Dhanalakshmi Boating Warning', { body: message, icon: '/favicon.ico' });
    }
}

async function markNotifRead(notifId) {
    try {
        await fetch(`/api/notifications/${notifId}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });
        // Immediately update the dropdown to remove the unread badge
        fetchUnreadNotifications();
    } catch (err) {
        console.error('Failed to mark notification as read:', err);
    }
}

async function markAllNotifRead() {
    try {
        await fetch('/api/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });
        
        const list = document.getElementById('notif-list');
        if (list) {
            list.innerHTML = '<div class="p-3 text-center text-muted">No notifications</div>';
        }
        const count = document.getElementById('notif-count');
        if (count) {
            count.style.display = 'none';
        }
    } catch (err) {
        console.error('Failed to mark all as read:', err);
    }
}

function updateNotifCount(count) {
    const el = document.getElementById('notif-count');
    if (el) {
        if (count > 0) {
            el.textContent = count > 99 ? '99+' : count;
            el.style.display = 'inline';
        } else {
            el.style.display = 'none';
        }
    }
}

/**
 * Fetch unread notifications and update the dropdown.
 * Called on demand (after mark-as-read) or via RealtimeSync.
 */
async function fetchUnreadNotifications() {
    try {
        const response = await fetch('/api/notifications/unread', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        
        if (data.success) {
            updateNotifCount(data.count);
            
            const list = document.getElementById('notif-list');
            if (list) {
                if (data.data.length === 0) {
                    list.innerHTML = '<div class="p-3 text-center text-muted">No notifications</div>';
                } else {
                    list.innerHTML = data.data.map(n => `
                        <div class="p-2 border-bottom notification-item" onclick="markNotifRead(${n.id})">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-${n.type === 'warning' ? 'warning' : n.type === 'error' ? 'danger' : 'info'} rounded-pill mt-1">!</span>
                                <div>
                                    <div class="small">${n.message}</div>
                                    <small class="text-muted">${n.created_at}</small>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            }
        }
    } catch (err) {
        // Silent fail — errors are already handled by RealtimeSync connection indicator
    }
}

// Expose to global scope
window.showToast = showToast;
window.markNotifRead = markNotifRead;
window.markAllNotifRead = markAllNotifRead;
window.updateNotifCount = updateNotifCount;
window.fetchUnreadNotifications = fetchUnreadNotifications;

document.addEventListener('DOMContentLoaded', function() {
    initNotifications();

    // Initial fetch of unread notifications for the dropdown
    fetchUnreadNotifications();

    // Periodically refresh the notification dropdown every 10 seconds
    // (the count/icon is already updated via RealtimeSync dashboard data)
    setInterval(fetchUnreadNotifications, 10000);

    // Also refresh when RealtimeSync syncs (via action-complete event)
    document.addEventListener('action-complete', fetchUnreadNotifications);
});
