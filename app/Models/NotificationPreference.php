<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_notifications',
        'in_app_notifications',
        'notification_frequency',
        'status_change_notifications',
        'report_submission_notifications',
        'approval_notifications',
        'rejection_notifications',
        'deadline_reminder_notifications',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'in_app_notifications' => 'boolean',
        'status_change_notifications' => 'boolean',
        'report_submission_notifications' => 'boolean',
        'approval_notifications' => 'boolean',
        'rejection_notifications' => 'boolean',
        'deadline_reminder_notifications' => 'boolean',
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create preferences for a user.
     */
    public static function getOrCreateForUser($userId)
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_notifications' => true,
                'in_app_notifications' => true,
                'notification_frequency' => 'immediate',
                'status_change_notifications' => true,
                'report_submission_notifications' => true,
                'approval_notifications' => true,
                'rejection_notifications' => true,
                'deadline_reminder_notifications' => true,
            ]
        );
    }

    /**
     * Check if user wants to receive notifications of a specific type.
     */
    public function shouldNotify($type)
    {
        if (!$this->in_app_notifications) {
            return false;
        }

        return match($type) {
            'status_change' => $this->status_change_notifications,
            'report_submission' => $this->report_submission_notifications,
            'approval' => $this->approval_notifications,
            'rejection' => $this->rejection_notifications,
            'deadline_reminder' => $this->deadline_reminder_notifications,
            default => true,
        };
    }
}
