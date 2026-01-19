<?php

namespace App\Models;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'related_id',
        'previous_status',
        'new_status',
        'action_type',
        'changed_by_user_id',
        'changed_by_user_role',
        'changed_by_user_name',
        'reverted_to_user_id',
        'notes',
        'approval_context',
        'revert_level',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who changed the status
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Get the user to whom the project/report was reverted
     */
    public function revertedTo()
    {
        return $this->belongsTo(User::class, 'reverted_to_user_id');
    }

    /**
     * Get the project (if type is 'project')
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'related_id', 'project_id');
    }

    /**
     * Get the report (if type is 'report')
     */
    public function report()
    {
        return $this->belongsTo(DPReport::class, 'related_id', 'report_id');
    }

    /**
     * Get the related entity (project or report) based on type
     */
    public function related()
    {
        if ($this->type === 'project') {
            return $this->belongsTo(Project::class, 'related_id', 'project_id');
        } else {
            return $this->belongsTo(DPReport::class, 'related_id', 'report_id');
        }
    }

    /**
     * Get status label for previous status
     */
    public function getPreviousStatusLabelAttribute(): string
    {
        if ($this->type === 'project') {
            return Project::$statusLabels[$this->previous_status] ?? $this->previous_status ?? 'N/A';
        } else {
            return DPReport::$statusLabels[$this->previous_status] ?? $this->previous_status ?? 'N/A';
        }
    }

    /**
     * Get status label for new status
     */
    public function getNewStatusLabelAttribute(): string
    {
        if ($this->type === 'project') {
            return Project::$statusLabels[$this->new_status] ?? $this->new_status;
        } else {
            return DPReport::$statusLabels[$this->new_status] ?? $this->new_status;
        }
    }

    /**
     * Get badge class for new status
     */
    public function getNewStatusBadgeClassAttribute(): string
    {
        $badgeClasses = [
            'draft' => 'bg-secondary',
            'submitted_to_provincial' => 'bg-primary',
            'reverted_by_provincial' => 'bg-warning',
            'forwarded_to_coordinator' => 'bg-info',
            'reverted_by_coordinator' => 'bg-warning',
            'approved_by_coordinator' => 'bg-success',
            'rejected_by_coordinator' => 'bg-danger',
            // General user statuses
            'approved_by_general_as_coordinator' => 'bg-success',
            'reverted_by_general_as_coordinator' => 'bg-warning',
            'approved_by_general_as_provincial' => 'bg-success',
            'reverted_by_general_as_provincial' => 'bg-warning',
            // Granular revert statuses
            'reverted_to_executor' => 'bg-warning',
            'reverted_to_applicant' => 'bg-warning',
            'reverted_to_provincial' => 'bg-warning',
            'reverted_to_coordinator' => 'bg-warning',
        ];

        return $badgeClasses[$this->new_status] ?? 'bg-secondary';
    }

    /**
     * Get badge class for previous status
     */
    public function getPreviousStatusBadgeClassAttribute(): string
    {
        if (!$this->previous_status) {
            return 'bg-secondary';
        }

        $badgeClasses = [
            'draft' => 'bg-secondary',
            'submitted_to_provincial' => 'bg-primary',
            'reverted_by_provincial' => 'bg-warning',
            'forwarded_to_coordinator' => 'bg-info',
            'reverted_by_coordinator' => 'bg-warning',
            'approved_by_coordinator' => 'bg-success',
            'rejected_by_coordinator' => 'bg-danger',
            // General user statuses
            'approved_by_general_as_coordinator' => 'bg-success',
            'reverted_by_general_as_coordinator' => 'bg-warning',
            'approved_by_general_as_provincial' => 'bg-success',
            'reverted_by_general_as_provincial' => 'bg-warning',
            // Granular revert statuses
            'reverted_to_executor' => 'bg-warning',
            'reverted_to_applicant' => 'bg-warning',
            'reverted_to_provincial' => 'bg-warning',
            'reverted_to_coordinator' => 'bg-warning',
        ];

        return $badgeClasses[$this->previous_status] ?? 'bg-secondary';
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by project
     */
    public function scopeForProject($query, string $projectId)
    {
        return $query->where('type', 'project')->where('related_id', $projectId);
    }

    /**
     * Scope to filter by report
     */
    public function scopeForReport($query, string $reportId)
    {
        return $query->where('type', 'report')->where('related_id', $reportId);
    }
}
