<?php

namespace App\Services;

use App\Models\ActivityHistory;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ActivityHistoryService
{
    /**
     * Log project update activity (status unchanged)
     *
     * @param Project $project
     * @param User $user
     * @param string|null $notes Additional notes about what was updated
     * @return void
     */
    public static function logProjectUpdate(Project $project, User $user, ?string $notes = null): void
    {
        try {
            ActivityHistory::create([
                'type' => 'project',
                'related_id' => $project->project_id,
                'previous_status' => $project->status, // Current status (no change)
                'new_status' => $project->status, // Same status (just updated)
                'action_type' => 'update',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Project updated',
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the update
            Log::error('Failed to log project update activity', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log project draft save activity
     *
     * @param Project $project
     * @param User $user
     * @param string|null $notes Additional notes about what was saved
     * @return void
     */
    public static function logProjectDraftSave(Project $project, User $user, ?string $notes = null): void
    {
        try {
            ActivityHistory::create([
                'type' => 'project',
                'related_id' => $project->project_id,
                'previous_status' => $project->status, // Status remains draft
                'new_status' => $project->status, // Status remains draft
                'action_type' => 'draft_save',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Project draft saved',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log project draft save activity', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log project submission activity
     *
     * @param Project $project
     * @param User $user
     * @param string|null $notes Additional notes about submission
     * @return void
     */
    public static function logProjectSubmit(Project $project, User $user, ?string $notes = null): void
    {
        try {
            ActivityHistory::create([
                'type' => 'project',
                'related_id' => $project->project_id,
                'previous_status' => 'draft', // Assuming submitted from draft
                'new_status' => $project->status, // Should be 'submitted_to_provincial'
                'action_type' => 'submit',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Project submitted',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log project submission activity', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log project comment activity (without status change)
     *
     * @param Project $project
     * @param User $user
     * @param string $comment The comment text
     * @return void
     */
    public static function logProjectComment(Project $project, User $user, string $comment): void
    {
        try {
            ActivityHistory::create([
                'type' => 'project',
                'related_id' => $project->project_id,
                'previous_status' => $project->status,
                'new_status' => $project->status, // Status unchanged
                'action_type' => 'comment',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $comment,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log project comment activity', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log report creation activity
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $notes Additional notes about creation
     * @return void
     */
    public static function logReportCreate(DPReport $report, User $user, ?string $notes = null): void
    {
        try {
            ActivityHistory::create([
                'type' => 'report',
                'related_id' => $report->report_id,
                'previous_status' => null, // No previous status for new reports
                'new_status' => $report->status, // Current status (usually 'draft')
                'action_type' => 'status_change', // Creation is a status change
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Report created',
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the creation
            Log::error('Failed to log report creation activity', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log report update activity (status unchanged)
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $notes Additional notes about what was updated
     * @param string|null $previousStatus Previous status before update (if status changed)
     * @return void
     */
    public static function logReportUpdate(DPReport $report, User $user, ?string $notes = null, ?string $previousStatus = null): void
    {
        try {
            // Use provided previousStatus or current status if not provided (no status change)
            $prevStatus = $previousStatus ?? $report->status;
            // Determine action_type: if status changed, use 'status_change', otherwise 'update'
            $actionType = ($previousStatus !== null && $previousStatus !== $report->status) ? 'status_change' : 'update';

            ActivityHistory::create([
                'type' => 'report',
                'related_id' => $report->report_id,
                'previous_status' => $prevStatus,
                'new_status' => $report->status, // Current status after update
                'action_type' => $actionType,
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Report updated',
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the update
            Log::error('Failed to log report update activity', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log report draft save activity
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $notes Additional notes about what was saved
     * @return void
     */
    public static function logReportDraftSave(DPReport $report, User $user, ?string $notes = null): void
    {
        try {
            ActivityHistory::create([
                'type' => 'report',
                'related_id' => $report->report_id,
                'previous_status' => $report->status, // Status remains draft
                'new_status' => $report->status, // Status remains draft
                'action_type' => 'draft_save',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Report draft saved',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log report draft save activity', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log report submission activity
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $notes Additional notes about submission
     * @return void
     */
    public static function logReportSubmit(DPReport $report, User $user, ?string $notes = null): void
    {
        try {
            ActivityHistory::create([
                'type' => 'report',
                'related_id' => $report->report_id,
                'previous_status' => 'draft', // Assuming submitted from draft
                'new_status' => $report->status, // Should be 'submitted_to_provincial'
                'action_type' => 'submit',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $notes ?? 'Report submitted',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log report submission activity', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log report comment activity (without status change)
     *
     * @param DPReport $report
     * @param User $user
     * @param string $comment The comment text
     * @return void
     */
    public static function logReportComment(DPReport $report, User $user, string $comment): void
    {
        try {
            ActivityHistory::create([
                'type' => 'report',
                'related_id' => $report->report_id,
                'previous_status' => $report->status,
                'new_status' => $report->status, // Status unchanged
                'action_type' => 'comment',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'notes' => $comment,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log report comment activity', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * Get activities for executor/applicant user
     * Shows activities for projects/reports they own or are in-charge of
     *
     * @param User $user
     * @return Collection
     */
    public static function getForExecutor(User $user): Collection
    {
        // Get project IDs where user is owner or in-charge
        $projectIds = Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
        })->pluck('project_id');

        // Get report IDs for those projects
        $reportIds = DPReport::whereIn('project_id', $projectIds)
            ->pluck('report_id');

        return ActivityHistory::where(function($query) use ($projectIds, $reportIds) {
            $query->where(function($q) use ($projectIds) {
                $q->where('type', 'project')
                  ->whereIn('related_id', $projectIds);
            })->orWhere(function($q) use ($reportIds) {
                $q->where('type', 'report')
                  ->whereIn('related_id', $reportIds);
            });
        })
        ->with('changedBy')
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Get activities for provincial user
     * Shows activities for all executors/applicants under them
     *
     * @param User $provincial
     * @return Collection
     */
    public static function getForProvincial(User $provincial): Collection
    {
        // Get executor/applicant IDs under this provincial
        $teamUserIds = User::where('parent_id', $provincial->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');

        // Get project IDs for those users
        $projectIds = Project::where(function($query) use ($teamUserIds) {
            $query->whereIn('user_id', $teamUserIds)
                  ->orWhereIn('in_charge', $teamUserIds);
        })->pluck('project_id');

        // Get report IDs for those projects
        $reportIds = DPReport::whereIn('project_id', $projectIds)
            ->pluck('report_id');

        return ActivityHistory::where(function($query) use ($projectIds, $reportIds) {
            $query->where(function($q) use ($projectIds) {
                $q->where('type', 'project')
                  ->whereIn('related_id', $projectIds);
            })->orWhere(function($q) use ($reportIds) {
                $q->where('type', 'report')
                  ->whereIn('related_id', $reportIds);
            });
        })
        ->with('changedBy')
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Get all activities for coordinator
     * Shows all activities in the system
     *
     * @return Collection
     */
    public static function getForCoordinator(): Collection
    {
        return ActivityHistory::with('changedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activity history for a specific project
     *
     * @param string $projectId
     * @return Collection
     */
    public static function getForProject(string $projectId): Collection
    {
        return ActivityHistory::where('type', 'project')
            ->where('related_id', $projectId)
            ->with('changedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activity history for a specific report
     *
     * @param string $reportId
     * @return Collection
     */
    public static function getForReport(string $reportId): Collection
    {
        return ActivityHistory::where('type', 'report')
            ->where('related_id', $reportId)
            ->with('changedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities with filters
     *
     * @param array $filters
     * @param User|null $user
     * @return Collection
     */
    public static function getWithFilters(array $filters, ?User $user = null): Collection
    {
        $query = ActivityHistory::query();

        // Apply role-based filtering
        if ($user) {
            if (in_array($user->role, ['executor', 'applicant'])) {
                // Get project IDs where user is owner or in-charge
                $projectIds = Project::where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('in_charge', $user->id);
                })->pluck('project_id');

                $reportIds = DPReport::whereIn('project_id', $projectIds)->pluck('report_id');

                $query->where(function($q) use ($projectIds, $reportIds) {
                    $q->where(function($subQ) use ($projectIds) {
                        $subQ->where('type', 'project')->whereIn('related_id', $projectIds);
                    })->orWhere(function($subQ) use ($reportIds) {
                        $subQ->where('type', 'report')->whereIn('related_id', $reportIds);
                    });
                });
            } elseif ($user->role === 'provincial') {
                $teamUserIds = User::where('parent_id', $user->id)
                    ->whereIn('role', ['executor', 'applicant'])
                    ->pluck('id');

                $projectIds = Project::where(function($q) use ($teamUserIds) {
                    $q->whereIn('user_id', $teamUserIds)
                      ->orWhereIn('in_charge', $teamUserIds);
                })->pluck('project_id');

                $reportIds = DPReport::whereIn('project_id', $projectIds)->pluck('report_id');

                $query->where(function($q) use ($projectIds, $reportIds) {
                    $q->where(function($subQ) use ($projectIds) {
                        $subQ->where('type', 'project')->whereIn('related_id', $projectIds);
                    })->orWhere(function($subQ) use ($reportIds) {
                        $subQ->where('type', 'report')->whereIn('related_id', $reportIds);
                    });
                });
            }
            // Coordinator sees all - no filtering needed
        }

        // Apply type filter
        if (isset($filters['type']) && in_array($filters['type'], ['project', 'report'])) {
            $query->where('type', $filters['type']);
        }

        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('new_status', $filters['status']);
        }

        // Apply date range filter
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('changed_by_user_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('related_id', 'like', "%{$search}%");
            });
        }

        return $query->with('changedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
