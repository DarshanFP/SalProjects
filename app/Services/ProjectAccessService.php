<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use App\Models\User;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Support\Collection;

/**
 * Centralized project access logic (Phase 7: Project View Access refactor).
 * Consolidates province, role, owner/in-charge rules for consistent access control.
 *
 * Coordinator: Top-level oversight role. No hierarchy. Global read access.
 * Does NOT use getAccessibleUserIds. No parent_id logic applies to coordinator.
 */
class ProjectAccessService
{
    protected array $accessibleUserIdsCache = [];

    /**
     * Get user IDs that a provincial user can access (executors/applicants in their scope).
     * For provincial: direct children. For general: direct children + users in managed provinces.
     * Respects province filter for general (from session).
     * Cached per request.
     *
     * For provincial and general only. Coordinator does NOT use this method.
     */
    public function getAccessibleUserIds(User $provincial): Collection
    {
        $cacheKey = $provincial->id . ($provincial->role === 'general'
            ? '_' . md5(json_encode([session('province_filter_ids', []), session('province_filter_all', true)]))
            : '');
        if (isset($this->accessibleUserIdsCache[$cacheKey])) {
            return $this->accessibleUserIdsCache[$cacheKey];
        }

        $userIds = collect();
        $directChildren = User::where('parent_id', $provincial->id)
            ->whereIn('role', ['executor', 'applicant'])
            ->pluck('id');
        $userIds = $userIds->merge($directChildren);

        if ($provincial->role === 'general') {
            $managedProvinces = $provincial->managedProvinces()->pluck('provinces.id');
            $filteredProvinceIds = session('province_filter_ids', []);
            $filterAll = session('province_filter_all', true);
            $provincesToUse = (!empty($filteredProvinceIds) && !$filterAll)
                ? array_intersect($managedProvinces->toArray(), $filteredProvinceIds)
                : $managedProvinces->toArray();

            if (!empty($provincesToUse)) {
                $provinceUsers = User::whereIn('province_id', $provincesToUse)
                    ->whereIn('role', ['executor', 'applicant', 'provincial'])
                    ->pluck('id');
                $userIds = $userIds->merge($provinceUsers);
            }
        }

        $result = $userIds->unique()->values();
        $this->accessibleUserIdsCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Check if user can view a project.
     * Consolidates province check, role rules, and provincial scope (owner/in-charge in team).
     *
     * Coordinator: Global read-only oversight. No hierarchy. Returns true after province check
     * (coordinator typically has province_id=null). No parent_id or getAccessibleUserIds applied.
     */
    public function canViewProject(Project $project, User $user): bool
    {
        if (!ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
            return false;
        }
        if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
            return true;
        }
        if (in_array($user->role, ['executor', 'applicant'])) {
            return $project->user_id === $user->id || $project->in_charge == $user->id;
        }
        if ($user->role === 'provincial') {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            $ids = $accessibleUserIds->toArray();
            return in_array($project->user_id, $ids)
                || ($project->in_charge && in_array($project->in_charge, $ids));
        }
        return false;
    }

    /**
     * Get query builder for projects visible to the user.
     * For provincial: owner or in-charge in scope. For general: unfiltered (managed provinces).
     * For executor/applicant: own or in-charge. For admin/coordinator: unfiltered (all projects).
     *
     * Coordinator: Global oversight. Returns unfiltered query (all projects).
     * No parent_id or hierarchy filter. No accessibleByUserIds applied.
     */
    public function getVisibleProjectsQuery(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Project::query();
        if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
            return $query;
        }
        if (in_array($user->role, ['executor', 'applicant'])) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('in_charge', $user->id);
            });
        }
        if ($user->role === 'provincial') {
            $ids = $this->getAccessibleUserIds($user);
            return $query->accessibleByUserIds($ids);
        }
        return $query->whereRaw('1 = 0');
    }
}
