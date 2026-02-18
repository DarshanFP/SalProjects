<?php

namespace App\Services;

use App\Helpers\ProjectPermissionHelper;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Centralized project lifecycle operations: trash, restore, force delete.
 * Authorization delegated to ProjectPermissionHelper.
 */
class ProjectLifecycleService
{
    /**
     * Soft delete (move to trash). No child data or files removed.
     *
     * @param Project $project
     * @param User $user
     * @return string 'trashed' | 'already_trashed'
     */
    public function trash(Project $project, User $user): string
    {
        if (!ProjectPermissionHelper::canDelete($project, $user)) {
            abort(403, 'You do not have permission to delete this project.');
        }

        if ($project->trashed()) {
            return 'already_trashed';
        }

        $project->delete();

        Log::info('ProjectLifecycleService::trash - Project soft deleted', [
            'project_id' => $project->project_id,
        ]);

        return 'trashed';
    }

    /**
     * Restore a soft-deleted project from trash.
     *
     * @param Project $project
     * @param User $user
     * @return string 'restored' | 'already_active'
     */
    public function restore(Project $project, User $user): string
    {
        if (!ProjectPermissionHelper::canDelete($project, $user)) {
            abort(403, 'You do not have permission to restore this project.');
        }

        if (!$project->trashed()) {
            return 'already_active';
        }

        $project->restore();

        Log::info('ProjectLifecycleService::restore - Project restored from trash', [
            'project_id' => $project->project_id,
        ]);

        return 'restored';
    }

    /**
     * Permanently delete a trashed project (admin only).
     *
     * @param Project $project
     * @param User $user
     * @return void
     */
    public function forceDelete(Project $project, User $user): void
    {
        if ($user->role !== 'admin') {
            abort(403, 'Only administrators can permanently delete projects.');
        }

        ActivityHistoryService::logProjectForceDelete($project, $user);
        app(ProjectForceDeleteCleanupService::class)->forceDelete($project);

        Log::info('ProjectLifecycleService::forceDelete - Project permanently deleted', [
            'project_id' => $project->project_id,
        ]);
    }
}
