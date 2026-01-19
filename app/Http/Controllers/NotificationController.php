<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('filter')) {
            if ($request->filter === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->filter === 'read') {
                $query->where('is_read', true);
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20);
        $unreadCount = NotificationService::getUnreadCount($user->id);
        $preferences = NotificationPreference::getOrCreateForUser($user->id);

        return view('notifications.index', compact('notifications', 'unreadCount', 'preferences'));
    }

    /**
     * Get unread count (AJAX endpoint).
     */
    public function unreadCount()
    {
        $count = NotificationService::getUnreadCount(Auth::id());
        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications (AJAX endpoint).
     */
    public function recent()
    {
        $notifications = NotificationService::getRecent(Auth::id(), 5);
        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $success = NotificationService::markAsRead($id, Auth::id());

        if ($success) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $count = NotificationService::markAllAsRead(Auth::id());
        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Marked {$count} notifications as read."
        ]);
    }

    /**
     * Remove the specified notification.
     */
    public function destroy($id)
    {
        $success = NotificationService::delete($id, Auth::id());

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Notification deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        $preferences = NotificationPreference::getOrCreateForUser($user->id);

        $validated = $request->validate([
            'email_notifications' => 'sometimes|boolean',
            'in_app_notifications' => 'sometimes|boolean',
            'notification_frequency' => 'sometimes|in:immediate,daily,weekly',
            'status_change_notifications' => 'sometimes|boolean',
            'report_submission_notifications' => 'sometimes|boolean',
            'approval_notifications' => 'sometimes|boolean',
            'rejection_notifications' => 'sometimes|boolean',
            'deadline_reminder_notifications' => 'sometimes|boolean',
        ]);

        $preferences->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.',
            'preferences' => $preferences
        ]);
    }
}
