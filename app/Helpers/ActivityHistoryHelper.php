<?php

namespace App\Helpers;

use App\Models\ActivityHistory;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;

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

        // Admin and coordinator can view all
        if (in_array($user->role, ['admin', 'coordinator'])) {
            return true;
        }

        // Provincial can view if project belongs to their executors/applicants
        if ($user->role === 'provincial') {
            $teamUserIds = User::where('parent_id', $user->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            return in_array($project->user_id, $teamUserIds->toArray()) ||
                   in_array($project->in_charge, $teamUserIds->toArray());
        }

        // Executor/applicant can view if they own or are in-charge
        if (in_array($user->role, ['executor', 'applicant'])) {
            return $project->user_id === $user->id || $project->in_charge === $user->id;
        }

        return false;
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

        if (!$report) {
            return false;
        }

        $project = $report->project;

        if (!$project) {
            return false;
        }

        // Admin and coordinator can view all
        if (in_array($user->role, ['admin', 'coordinator'])) {
            return true;
        }

        // Provincial can view if report belongs to their executors/applicants
        if ($user->role === 'provincial') {
            $teamUserIds = User::where('parent_id', $user->id)
                ->whereIn('role', ['executor', 'applicant'])
                ->pluck('id');

            return in_array($project->user_id, $teamUserIds->toArray()) ||
                   in_array($project->in_charge, $teamUserIds->toArray());
        }

        // Executor/applicant can view if they own or are in-charge of the project
        if (in_array($user->role, ['executor', 'applicant'])) {
            return $project->user_id === $user->id || $project->in_charge === $user->id;
        }

        return false;
    }

    /**
     * Get query builder for activities based on user role
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getQueryForUser(User $user)
    {
        $query = ActivityHistory::query();

        if (in_array($user->role, ['admin', 'coordinator'])) {
            // No filtering - see all activities
            return $query;
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
