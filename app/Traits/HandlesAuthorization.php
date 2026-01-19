<?php

namespace App\Traits;

use App\Models\User;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait HandlesAuthorization
{
    /**
     * Get the authenticated user
     *
     * @return User
     */
    protected function getAuthUser(): User
    {
        return Auth::user();
    }

    /**
     * Check if user has a specific role
     *
     * @param User|null $user
     * @param string|array $roles
     * @return bool
     */
    protected function hasRole(?User $user, $roles): bool
    {
        if (!$user) {
            $user = $this->getAuthUser();
        }

        if (is_array($roles)) {
            return in_array($user->role, $roles);
        }

        return $user->role === $roles;
    }

    /**
     * Require user to have a specific role, abort if not
     *
     * @param User|null $user
     * @param string|array $roles
     * @param string|null $message
     * @return void
     */
    protected function requireRole(?User $user, $roles, ?string $message = null): void
    {
        if (!$this->hasRole($user, $roles)) {
            $rolesStr = is_array($roles) ? implode(', ', $roles) : $roles;
            $message = $message ?? "Access denied. Only {$rolesStr} users can access this.";

            Log::warning('Access denied - Role check failed', [
                'user_id' => $user?->id ?? Auth::id(),
                'user_role' => $user?->role ?? 'unknown',
                'required_roles' => $roles,
            ]);

            abort(403, $message);
        }
    }

    /**
     * Check if user is admin
     *
     * @param User|null $user
     * @return bool
     */
    protected function isAdmin(?User $user = null): bool
    {
        return $this->hasRole($user, 'admin');
    }

    /**
     * Check if user is coordinator
     *
     * @param User|null $user
     * @return bool
     */
    protected function isCoordinator(?User $user = null): bool
    {
        return $this->hasRole($user, 'coordinator');
    }

    /**
     * Check if user is provincial
     *
     * @param User|null $user
     * @return bool
     */
    protected function isProvincial(?User $user = null): bool
    {
        return $this->hasRole($user, 'provincial');
    }

    /**
     * Check if user is executor or applicant
     *
     * @param User|null $user
     * @return bool
     */
    protected function isExecutorOrApplicant(?User $user = null): bool
    {
        return $this->hasRole($user, ['executor', 'applicant']);
    }

    /**
     * Check if user is general
     *
     * @param User|null $user
     * @return bool
     */
    protected function isGeneral(?User $user = null): bool
    {
        return $this->hasRole($user, 'general');
    }

    /**
     * Check if user is under another user (parent relationship)
     *
     * @param User $childUser
     * @param User $parentUser
     * @return bool
     */
    protected function isUserUnderParent(User $childUser, User $parentUser): bool
    {
        return $childUser->parent_id === $parentUser->id;
    }

    /**
     * Get all descendant user IDs recursively
     * This matches the implementation in GeneralController
     *
     * @param \Illuminate\Support\Collection $parentIds Collection of parent user IDs
     * @return \Illuminate\Support\Collection
     */
    protected function getAllDescendantUserIds($parentIds): \Illuminate\Support\Collection
    {
        if ($parentIds->isEmpty()) {
            return collect();
        }

        $children = \App\Models\User::whereIn('parent_id', $parentIds)->pluck('id');

        if ($children->isEmpty()) {
            return $parentIds;
        }

        return $parentIds->merge($this->getAllDescendantUserIds($children));
    }

    /**
     * Check if user can access project (using ProjectPermissionHelper)
     *
     * @param \App\Models\OldProjects\Project $project
     * @param User|null $user
     * @return bool
     */
    protected function canAccessProject(\App\Models\OldProjects\Project $project, ?User $user = null): bool
    {
        if (!$user) {
            $user = $this->getAuthUser();
        }

        return ProjectPermissionHelper::canView($project, $user);
    }

    /**
     * Check if user can edit project (using ProjectPermissionHelper)
     *
     * @param \App\Models\OldProjects\Project $project
     * @param User|null $user
     * @return bool
     */
    protected function canEditProject(\App\Models\OldProjects\Project $project, ?User $user = null): bool
    {
        if (!$user) {
            $user = $this->getAuthUser();
        }

        return ProjectPermissionHelper::canEdit($project, $user);
    }

    /**
     * Check if user can submit project (using ProjectPermissionHelper)
     *
     * @param \App\Models\OldProjects\Project $project
     * @param User|null $user
     * @return bool
     */
    protected function canSubmitProject(\App\Models\OldProjects\Project $project, ?User $user = null): bool
    {
        if (!$user) {
            $user = $this->getAuthUser();
        }

        return ProjectPermissionHelper::canSubmit($project, $user);
    }

    /**
     * Require user to have access to project, abort if not
     *
     * @param \App\Models\OldProjects\Project $project
     * @param User|null $user
     * @param string|null $message
     * @return void
     */
    protected function requireProjectAccess(\App\Models\OldProjects\Project $project, ?User $user = null, ?string $message = null): void
    {
        if (!$this->canAccessProject($project, $user)) {
            $message = $message ?? 'You do not have permission to access this project.';

            Log::warning('Access denied - Project access check failed', [
                'user_id' => $user?->id ?? Auth::id(),
                'project_id' => $project->project_id,
            ]);

            abort(403, $message);
        }
    }

    /**
     * Require user to be able to edit project, abort if not
     *
     * @param \App\Models\OldProjects\Project $project
     * @param User|null $user
     * @param string|null $message
     * @return void
     */
    protected function requireProjectEdit(\App\Models\OldProjects\Project $project, ?User $user = null, ?string $message = null): void
    {
        if (!$this->canEditProject($project, $user)) {
            $message = $message ?? 'You do not have permission to edit this project.';

            Log::warning('Access denied - Project edit check failed', [
                'user_id' => $user?->id ?? Auth::id(),
                'project_id' => $project->project_id,
            ]);

            abort(403, $message);
        }
    }

    /**
     * Get team user IDs (executors/applicants) under a user
     *
     * @param User $parentUser
     * @param array $roles Filter by roles (default: ['executor', 'applicant'])
     * @return \Illuminate\Support\Collection
     */
    protected function getTeamUserIds(User $parentUser, array $roles = ['executor', 'applicant']): \Illuminate\Support\Collection
    {
        return \App\Models\User::where('parent_id', $parentUser->id)
            ->whereIn('role', $roles)
            ->pluck('id');
    }
}
