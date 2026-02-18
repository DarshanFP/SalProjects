<?php

namespace App\Helpers;

use App\Models\OldProjects\Project;
use App\Models\User;
use App\Constants\ProjectStatus;

/**
 * Helper class for project permission checks
 *
 * Province-based access: project.province_id must equal user.province_id (no cross-province access).
 * Executor/Applicant: edit only own or in-charge projects. Provincial/Coordinator: edit all in their province.
 */
class ProjectPermissionHelper
{
    /**
     * Enforce province isolation: user can only access projects in their province.
     * If user has no province_id (e.g. admin/general), allow (backward compatibility).
     */
    public static function passesProvinceCheck(Project $project, User $user): bool
    {
        if ($user->province_id === null) {
            return true;
        }
        return $project->province_id === $user->province_id;
    }

    /**
     * Check if user can edit a project
     * 1) Province must match. 2) Status must be editable for role (Wave 5F: canEditForRole). 3) Role-based: executor/applicant = owner or in_charge; provincial/coordinator = true.
     */
    public static function canEdit(Project $project, User $user): bool
    {
        if (!self::passesProvinceCheck($project, $user)) {
            return false;
        }
        if (!ProjectStatus::canEditForRole($project->status, $user->role)) {
            return false;
        }
        if (in_array($user->role, ['provincial', 'coordinator'])) {
            return true;
        }
        if (in_array($user->role, ['executor', 'applicant'])) {
            return $project->user_id === $user->id || $project->in_charge === $user->id;
        }
        if (in_array($user->role, ['admin', 'general'])) {
            return true;
        }
        return false;
    }

    /**
     * Alias for canEdit (update uses same rules as edit).
     */
    public static function canUpdate(Project $project, User $user): bool
    {
        return self::canEdit($project, $user);
    }

    /**
     * Check if user can delete a project (same rules as canEdit).
     */
    public static function canDelete(Project $project, User $user): bool
    {
        return self::canEdit($project, $user);
    }

    /**
     * Check if user can submit a project
     */
    public static function canSubmit(Project $project, User $user): bool
    {
        if (!self::passesProvinceCheck($project, $user)) {
            return false;
        }
        if (!ProjectStatus::isSubmittable($project->status)) {
            return false;
        }
        if (!in_array($user->role, ['executor', 'applicant'])) {
            return false;
        }
        return $project->user_id === $user->id || $project->in_charge === $user->id;
    }

    /**
     * Check if user can view a project
     * Province must match; then provincial/coordinator/admin/general can view; executor/applicant only if owner or in_charge.
     */
    public static function canView(Project $project, User $user): bool
    {
        if (!self::passesProvinceCheck($project, $user)) {
            return false;
        }
        if (in_array($user->role, ['admin', 'coordinator', 'provincial', 'general'])) {
            return true;
        }
        if (in_array($user->role, ['executor', 'applicant'])) {
            return $project->user_id === $user->id || $project->in_charge === $user->id;
        }
        return false;
    }

    /**
     * Check if user is the owner or in-charge of the project
     *
     * @param Project $project The project to check
     * @param User $user The user to check
     * @return bool True if user is owner or in-charge, false otherwise
     */
    public static function isOwnerOrInCharge(Project $project, User $user): bool
    {
        // User is the creator
        if ($project->user_id === $user->id) {
            return true;
        }

        // User is the in-charge
        if ($project->in_charge === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is the owner (not just in-charge)
     *
     * @param Project $project The project to check
     * @param User $user The user to check
     * @return bool True if user is the owner, false otherwise
     */
    public static function isOwner(Project $project, User $user): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Check if user is only in-charge (not owner)
     *
     * @param Project $project The project to check
     * @param User $user The user to check
     * @return bool True if user is in-charge but not owner, false otherwise
     */
    public static function isOnlyInCharge(Project $project, User $user): bool
    {
        return $project->in_charge === $user->id && $project->user_id !== $user->id;
    }

    /**
     * Check if applicant can edit a project
     *
     * Applicants can edit projects they own or are in-charge of, same as executors.
     *
     * @param Project $project The project to check
     * @param User $user The user to check (must be applicant role)
     * @return bool True if applicant can edit the project, false otherwise
     */
    public static function canApplicantEdit(Project $project, User $user): bool
    {
        if ($user->role !== 'applicant') {
            return false;
        }

        // Applicants can edit projects they own or are in-charge of (same as executors)
        return self::isOwnerOrInCharge($project, $user);
    }

    /**
     * Get query builder for projects that user can edit (province-scoped).
     */
    public static function getEditableProjects(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Project::query();

        if ($user->province_id !== null) {
            $query->where('province_id', $user->province_id);
        }

        if (in_array($user->role, ['executor', 'applicant'])) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('in_charge', $user->id);
            });
            $query->notApproved();
        }

        $query->whereIn('status', ProjectStatus::getEditableStatuses());

        return $query;
    }
}

