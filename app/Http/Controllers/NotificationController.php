<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ?->notifications()
            ->orderByDesc('created_at')
            ->get() ?? collect();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ?->unreadNotifications()
            ->orderByDesc('created_at')
            ->get() ?? collect();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()?->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marquée comme lue.',
        ]);
    }
}
