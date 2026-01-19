<?php

namespace App\Helpers;

use App\Models\OldProjects\Project;
use App\Models\User;
use App\Constants\ProjectStatus;

/**
 * Helper class for project permission checks
 *
 * Provides static methods to check various permissions related to projects.
 * Handles ownership, in-charge relationships, and role-based access control.
 */
class ProjectPermissionHelper
{
    /**
     * Check if user can edit a project
     *
     * @param Project $project The project to check
     * @param User $user The user to check permissions for
     * @return bool True if user can edit the project, false otherwise
     */
    public static function canEdit(Project $project, User $user): bool
    {
        // Check if project is in editable status
        if (!ProjectStatus::isEditable($project->status)) {
            return false;
        }

        // Check ownership
        return self::isOwnerOrInCharge($project, $user);
    }

    /**
     * Check if user can submit a project
     *
     * @param Project $project The project to check
     * @param User $user The user to check permissions for
     * @return bool True if user can submit the project, false otherwise
     */
    public static function canSubmit(Project $project, User $user): bool
    {
        // Check if project is in submittable status
        if (!ProjectStatus::isSubmittable($project->status)) {
            return false;
        }

        // Check user role
        if (!in_array($user->role, ['executor', 'applicant'])) {
            return false;
        }

        // Check ownership
        return self::isOwnerOrInCharge($project, $user);
    }

    /**
     * Check if user can view a project
     *
     * @param Project $project The project to check
     * @param User $user The user to check permissions for
     * @return bool True if user can view the project, false otherwise
     */
    public static function canView(Project $project, User $user): bool
    {
        // Admin and coordinators can view all projects
        if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
            return true;
        }

        // Check ownership
        return self::isOwnerOrInCharge($project, $user);
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
     * Get query builder for projects that user can edit
     *
     * @param User $user The user to get editable projects for
     * @return \Illuminate\Database\Eloquent\Builder Query builder for editable projects
     */
    public static function getEditableProjects(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Project::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
        });

        // Filter by editable statuses
        $query->whereIn('status', ProjectStatus::getEditableStatuses());

        // For executors and applicants, exclude approved projects
        if (in_array($user->role, ['executor', 'applicant'])) {
            $query->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR);
        }

        return $query;
    }
}

