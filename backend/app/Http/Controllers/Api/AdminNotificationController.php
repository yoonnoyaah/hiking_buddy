<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    private array $categories = [
        'admin_location',
        'admin_reports',
    ];

    private function isAdmin(Request $request): bool
    {
        return $request->user() &&
            strtolower((string) $request->user()->role) === 'admin';
    }

    private function forbidden()
    {
        return response()->json([
            'success' => false,
            'message' => 'Administrator access is required.',
        ], 403);
    }

    public function index(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $user = $request->user();

        $notifications = $user->notifications()
            ->whereIn('data->category', $this->categories)
            ->latest()
            ->paginate(20);

        $unread = $user->unreadNotifications()
            ->whereIn('data->category', $this->categories);

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_counts' => [
                'location' => (clone $unread)
                    ->where('data->category', 'admin_location')
                    ->count(),

                'reports' => (clone $unread)
                    ->where('data->category', 'admin_reports')
                    ->count(),

                'total' => (clone $unread)->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->whereIn('data->category', $this->categories)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Admin notification not found.',
            ], 404);
        }

        // This only marks the notification as read.
        // It does not approve or resolve the related item.
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Admin notification marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $request->validate([
            'category' => 'nullable|in:admin_location,admin_reports',
        ]);

        $query = $request->user()
            ->unreadNotifications()
            ->whereIn('data->category', $this->categories);

        if ($request->filled('category')) {
            $query->where('data->category', $request->input('category'));
        }

        $query->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin notifications marked as read.',
        ]);
    }
}