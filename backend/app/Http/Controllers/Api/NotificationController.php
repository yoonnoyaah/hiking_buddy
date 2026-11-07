<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Get notifications, optionally filtered by category
    public function index(Request $request)
    {
        $user = $request->user();

        $category = $request->query('category');

        $query = $user->notifications();

        if (in_array($category, ['trails', 'community'])) {
            $query->where('data->category', $category);
        }

        $notifications = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_counts' => [
                'trails' => $user->unreadNotifications()
                    ->where('data->category', 'trails')
                    ->count(),

                'community' => $user->unreadNotifications()
                    ->where('data->category', 'community')
                    ->count(),

                'total' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    // Mark one notification as read
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    // Mark all notifications as read
    public function markAllAsRead(Request $request)
    {
        $category = $request->input('category');

        $query = $request->user()->unreadNotifications();

        if (in_array($category, ['trails', 'community'])) {
            $query->where('data->category', $category);
        }

        $query->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
        ]);
    }
}
