<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mark a single notification as read and return JSON.
     */
    public function markRead(string $id): JsonResponse
    {
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function readAll(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark all unread notifications for a specific task as read.
     */
    public function readByTask(int $taskId): JsonResponse
    {
        Auth::user()
            ->unreadNotifications()
            ->whereJsonContains('data->task_id', $taskId)
            ->get()
            ->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Return current unread count and recent notifications for polling.
     */
    public function counts(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'recent'       => $user->notifications()->latest()->take(15)->get()->map(fn ($n) => [
                'id'         => $n->id,
                'data'       => $n->data,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }
}
