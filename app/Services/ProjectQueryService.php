<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use App\Models\User;
use App\Constants\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProjectQueryService
{
    /**
     * Get a query builder for projects where user is owner or in-charge
     *
     * @param User $user
     * @return Builder
     */
    public static function getProjectsForUserQuery(User $user): Builder
    {
        return Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('in_charge', $user->id);
        });
    }

    /**
     * Get project IDs where user is owner or in-charge
     *
     * @param User $user
     * @return Collection
     */
    public static function getProjectIdsForUser(User $user): Collection
    {
        return self::getProjectsForUserQuery($user)->pluck('project_id');
    }

    /**
     * Get projects where user is owner or in-charge
     *
     * @param User $user
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getProjectsForUser(User $user, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::getProjectsForUserQuery($user);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get a query builder for projects where multiple users are owners or in-charge
     *
     * @param array|Collection $userIds
     * @return Builder
     */
    public static function getProjectsForUsersQuery($userIds): Builder
    {
        return Project::where(function($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                  ->orWhereIn('in_charge', $userIds);
        });
    }

    /**
     * Get project IDs where multiple users are owners or in-charge
     *
     * @param array|Collection $userIds
     * @return Collection
     */
    public static function getProjectIdsForUsers($userIds): Collection
    {
        return self::getProjectsForUsersQuery($userIds)->pluck('project_id');
    }

    /**
     * Get projects with status filter
     *
     * @param User $user
     * @param array|string $statuses Status or array of statuses
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getProjectsForUserByStatus(User $user, $statuses, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::getProjectsForUserQuery($user);

        if (is_array($statuses)) {
            $query->whereIn('status', $statuses);
        } else {
            $query->where('status', $statuses);
        }

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get approved projects for user
     *
     * @param User $user
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getApprovedProjectsForUser(User $user, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        return self::getProjectsForUserByStatus($user, [
            ProjectStatus::APPROVED_BY_COORDINATOR,
            ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
            ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL,
        ], $with);
    }

    /**
     * Get editable projects for user (draft, reverted statuses)
     *
     * @param User $user
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEditableProjectsForUser(User $user, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        return self::getProjectsForUserByStatus($user, ProjectStatus::getEditableStatuses(), $with);
    }

    /**
     * Get reverted projects for user
     *
     * @param User $user
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRevertedProjectsForUser(User $user, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        return self::getProjectsForUserByStatus($user, [
            ProjectStatus::REVERTED_BY_PROVINCIAL,
            ProjectStatus::REVERTED_BY_COORDINATOR,
            ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
            ProjectStatus::REVERTED_TO_EXECUTOR,
            ProjectStatus::REVERTED_TO_APPLICANT,
            ProjectStatus::REVERTED_TO_PROVINCIAL,
            ProjectStatus::REVERTED_TO_COORDINATOR,
        ], $with);
    }

    /**
     * Apply search filter to project query.
     * Phase 5B2: Society text search via join on societies.name; fallback search on projects.society_name for legacy rows.
     *
     * @param Builder $query
     * @param string $searchTerm
     * @return Builder
     */
    public static function applySearchFilter(Builder $query, string $searchTerm): Builder
    {
        $query->leftJoin('societies', 'projects.society_id', '=', 'societies.id')
              ->select('projects.*');

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('projects.project_id', 'like', "%{$searchTerm}%")
              ->orWhere('projects.project_title', 'like', "%{$searchTerm}%")
              ->orWhere('societies.name', 'like', "%{$searchTerm}%")
              ->orWhere('projects.society_name', 'like', "%{$searchTerm}%")
              ->orWhere('projects.place', 'like', "%{$searchTerm}%");
        });
    }
}
