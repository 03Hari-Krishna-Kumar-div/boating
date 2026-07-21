<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Rental;

class NotificationService
{
    public function send(User $user, string $type, string $message, ?Rental $rental = null): ?Notification
    {
        // DB persistence disabled for performance — kept as no-op for API compat
        return null;
    }

    public function sendToAllWorkers(string $type, string $message, ?Rental $rental = null): void {}

    public function sendToAllAdmins(string $type, string $message, ?Rental $rental = null): void {}

    public function markRead(Notification $notification): Notification
    {
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return $notification->fresh();
    }

    public function getUnread(User $user, int $limit = 10)
    {
        return $user->notifications()->unread()->latest()->take($limit)->get();
    }

    public function markAllRead(User $user): void
    {
        $user->notifications()->unread()->update(['is_read' => true, 'read_at' => now()]);
    }
}
