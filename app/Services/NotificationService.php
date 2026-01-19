<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling notifications
 *
 * Provides methods for creating and managing notifications for users.
 * Handles user preferences, notification types, and email notifications.
 */
class NotificationService
{
    /**
     * Create a notification for a user
     *
     * @param User $user The user to create notification for
     * @param string $type Notification type (e.g., 'approval', 'rejection', 'report_submission')
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string|null $relatedType Type of related entity (e.g., 'project', 'report')
     * @param int|null $relatedId ID of related entity
     * @param array|null $metadata Additional metadata for the notification
     * @return Notification The created notification instance
     */
    public static function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?array $metadata = null
    ): Notification {
        // Check user preferences
        $preferences = NotificationPreference::getOrCreateForUser($user->id);

        if (!$preferences->shouldNotify($type)) {
            Log::info("Notification skipped due to user preferences", [
                'user_id' => $user->id,
                'type' => $type,
            ]);
            // Still create the notification but mark it as read (user preference)
            // Or return null if we don't want to create it at all
            // For now, we'll create it but the user won't see it in unread count
        }

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'metadata' => $metadata,
            'is_read' => !$preferences->shouldNotify($type), // Mark as read if user doesn't want this type
        ]);

        // Future Enhancement: Email notifications
        // Email notification functionality is planned but not yet implemented.
        // When implemented, this section will send email notifications based on user preferences.
        // See: Documentations/CODING_STANDARDS.md for more information.
        if ($preferences->email_notifications && $preferences->shouldNotify($type)) {
            // Email notification logic will be added here in a future release
            // Currently, notifications are only stored in the database
            Log::info("Email notification requested (not yet implemented)", [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
            ]);
        }

        return $notification;
    }

    /**
     * Notify about project approval.
     */
    public static function notifyApproval(
        User $user,
        string $relatedType,
        int $relatedId,
        string $relatedTitle
    ): Notification {
        return self::create(
            $user,
            'approval',
            'Project Approved',
            "Your {$relatedType} '{$relatedTitle}' has been approved.",
            $relatedType,
            $relatedId,
            ['related_title' => $relatedTitle]
        );
    }

    /**
     * Notify about project rejection.
     */
    public static function notifyRejection(
        User $user,
        string $relatedType,
        int $relatedId,
        string $relatedTitle,
        ?string $reason = null
    ): Notification {
        $message = "Your {$relatedType} '{$relatedTitle}' has been rejected.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        return self::create(
            $user,
            'rejection',
            'Project Rejected',
            $message,
            $relatedType,
            $relatedId,
            [
                'related_title' => $relatedTitle,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notify about report submission.
     */
    public static function notifyReportSubmission(
        User $user,
        int $reportId,
        ?int $projectId = null
    ): Notification {
        $message = "A new report (ID: {$reportId}) has been submitted.";
        if ($projectId) {
            $message .= " Project ID: {$projectId}";
        }

        return self::create(
            $user,
            'report_submission',
            'New Report Submitted',
            $message,
            'report',
            $reportId,
            [
                'report_id' => $reportId,
                'project_id' => $projectId,
            ]
        );
    }

    /**
     * Notify about status change.
     */
    public static function notifyStatusChange(
        User $user,
        string $relatedType,
        int $relatedId,
        string $relatedTitle,
        string $oldStatus,
        string $newStatus
    ): Notification {
        return self::create(
            $user,
            'status_change',
            'Status Changed',
            "The status of '{$relatedTitle}' has changed from '{$oldStatus}' to '{$newStatus}'.",
            $relatedType,
            $relatedId,
            [
                'related_title' => $relatedTitle,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]
        );
    }

    /**
     * Notify about project revert.
     */
    public static function notifyRevert(
        User $user,
        string $relatedType,
        int $relatedId,
        string $relatedTitle,
        ?string $reason = null
    ): Notification {
        $message = "Your {$relatedType} '{$relatedTitle}' has been reverted.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        return self::create(
            $user,
            'revert',
            'Project Reverted',
            $message,
            $relatedType,
            $relatedId,
            [
                'related_title' => $relatedTitle,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notify about deadline reminder.
     */
    public static function notifyDeadlineReminder(
        User $user,
        string $relatedType,
        int $relatedId,
        string $relatedTitle,
        string $deadline
    ): Notification {
        return self::create(
            $user,
            'deadline_reminder',
            'Deadline Reminder',
            "Reminder: '{$relatedTitle}' deadline is approaching: {$deadline}",
            $relatedType,
            $relatedId,
            [
                'related_title' => $relatedTitle,
                'deadline' => $deadline,
            ]
        );
    }

    /**
     * Get unread count for a user.
     */
    public static function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get recent notifications for a user.
     */
    public static function getRecent(int $userId, int $limit = 10)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark notification as read.
     */
    public static function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete a notification.
     */
    public static function delete(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->delete();
            return true;
        }

        return false;
    }
}
