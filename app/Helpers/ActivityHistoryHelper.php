<?php

namespace App\Helpers;

use App\Models\ActivityHistory;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use App\Services\ProjectAccessService;

class ActivityHistoryHelper
{
    /**
     * Check if user can view activity history
     *
     * @param ActivityHistory $activity
     * @param User $user
     * @return bool
     */
    public static function canView(ActivityHistory $activity, User $user): bool
    {
        // Admin and coordinator can view all activities
        if (in_array($user->role, ['admin', 'coordinator'])) {
            return true;
        }

        // Check based on activity type
        if ($activity->type === 'project') {
            return self::canViewProjectActivity($activity->related_id, $user);
        } else {
            return self::canViewReportActivity($activity->related_id, $user);
        }
    }

    /**
     * Check if user can view project activity
     *
     * @param string $projectId
     * @param User $user
     * @return bool
     */
    public static function canViewProjectActivity(string $projectId, User $user): bool
    {
        $project = Project::where('project_id', $projectId)->first();

        if (!$project) {
            return false;
        }

        return app(ProjectAccessService::class)->canViewProject($project, $user);
    }

    /**
     * Check if user can view report activity
     *
     * @param string $reportId
     * @param User $user
     * @return bool
     */
    public static function canViewReportActivity(string $reportId, User $user): bool
    {
        $report = DPReport::where('report_id', $reportId)->first();

        if (!$report || !$report->project) {
            return false;
        }

        return app(ProjectAccessService::class)->canViewProject($report->project, $user);
    }

    /**
     * Get query builder for activities based on user role.
     * Coordinator: routes through ProjectAccessService (global oversight = all projects).
     * Scope matches project visibility.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getQueryForUser(User $user)
    {
        $query = ActivityHistory::query();

        if (in_array($user->role, ['admin', 'coordinator'])) {
            // Use ProjectAccessService for consistency (coordinator = global; admin = global)
            $visibleProjectIds = app(ProjectAccessService::class)->getVisibleProjectsQuery($user)->pluck('project_id');
            $visibleReportIds = DPReport::whereIn('project_id', $visibleProjectIds)->pluck('report_id');
            return $query->where(function ($q) use ($visibleProjectIds, $visibleReportIds) {
                $q->where(function ($subQ) use ($visibleProjectIds) {
                    $subQ->where('type', 'project')->whereIn('related_id', $visibleProjectIds);
                })->orWhere(function ($subQ) use ($visibleReportIds) {
                    $subQ->where('type', 'report')->whereIn('related_id', $visibleReportIds);
                });
            });
        }

        if ($user->role === 'provincial') {
            // Get team user IDs
            $teamUserIds = User::where('parent_id', $user->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            // Get project IDs
            $projectIds = Project::where(function($q) use ($teamUserIds) {
                $q->whereIn('user_id', $teamUserIds)
                  ->orWhereIn('in_charge', $teamUserIds);
            })->pluck('project_id');

            // Get report IDs
            $reportIds = DPReport::whereIn('project_id', $projectIds)->pluck('report_id');

            return $query->where(function($q) use ($projectIds, $reportIds) {
                $q->where(function($subQ) use ($projectIds) {
                    $subQ->where('type', 'project')->whereIn('related_id', $projectIds);
                })->orWhere(function($subQ) use ($reportIds) {
                    $subQ->where('type', 'report')->whereIn('related_id', $reportIds);
                });
            });
        }

        if (in_array($user->role, ['executor', 'applicant'])) {
            // Get project IDs where user is owner or in-charge
            $projectIds = Project::where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
            })->pluck('project_id');

            // Get report IDs
            $reportIds = DPReport::whereIn('project_id', $projectIds)->pluck('report_id');

            return $query->where(function($q) use ($projectIds, $reportIds) {
                $q->where(function($subQ) use ($projectIds) {
                    $subQ->where('type', 'project')->whereIn('related_id', $projectIds);
                })->orWhere(function($subQ) use ($reportIds) {
                    $subQ->where('type', 'report')->whereIn('related_id', $reportIds);
                });
            });
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }
}
