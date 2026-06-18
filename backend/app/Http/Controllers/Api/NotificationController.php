<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kingdom = $request->user()->kingdom;
        $notifications = $kingdom->notifications()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ]),
        ]);
    }

    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        $kingdom = $request->user()->kingdom;
        $notification = $kingdom->notifications()->find($notificationId);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquée comme lue',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $kingdom = $request->user()->kingdom;
        $kingdom->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Toutes les notifications marquées comme lues',
        ]);
    }
}
