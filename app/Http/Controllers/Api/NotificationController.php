<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function unread(): JsonResponse
    {
        $notifications = $this->notificationService->getUnread(auth()->user(), 10);

        return response()->json([
            'success' => true,
            'data' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'message' => $n->message,
                'created_at' => $n->created_at?->diffForHumans(),
                'is_read' => $n->is_read,
            ]),
            'count' => $notifications->count(),
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Not authorized.'], 403);
        }

        $this->notificationService->markRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $this->notificationService->markAllRead(auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
