<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportQueryService
{
    /**
     * Get project IDs for user (uses ProjectQueryService)
     *
     * @param User $user
     * @return Collection
     */
    public static function getProjectIdsForUser(User $user): Collection
    {
        return ProjectQueryService::getProjectIdsForUser($user);
    }

    /**
     * Get reports query for user's projects
     *
     * @param User $user
     * @return Builder
     */
    public static function getReportsForUserQuery(User $user): Builder
    {
        $projectIds = self::getProjectIdsForUser($user);
        return DPReport::whereIn('project_id', $projectIds->toArray());
    }

    /**
     * Get reports for user's projects
     *
     * @param User $user
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getReportsForUser(User $user, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::getReportsForUserQuery($user);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get reports for user's projects by status
     *
     * @param User $user
     * @param array|string $statuses Status or array of statuses
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getReportsForUserByStatus(User $user, $statuses, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::getReportsForUserQuery($user);

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
}
