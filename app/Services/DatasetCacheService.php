<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Dataset cache service for dashboard project datasets.
 * Phase 3.3: Provincial dashboard dataset cache layer.
 * Phase 2 (Coordinator): Coordinator dataset cache layer.
 *
 * Caches project collections keyed by provincial ID and FY (Provincial)
 * or by FY only (Coordinator).
 * General users bypass cache (session-dependent scope).
 */
class DatasetCacheService
{
    /**
     * Get the provincial dashboard project dataset (cached for provincial role).
     * Phase 3.3: Uses cache for Provincial role; General users bypass cache.
     *
     * @param \App\Models\User $provincial Provincial or general user
     * @param string $fy Financial year (e.g. "2025-26")
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getProvincialDataset($provincial, string $fy)
    {
        $with = ['user', 'reports.accountDetails', 'budgets'];

        // Phase 4.5 — Lightweight project column projection.
        // Reduces project payload; relations remain full for resolver and widgets.
        $select = [
            'id',
            'project_id',
            'province_id',
            'society_id',
            'project_type',
            'user_id',
            'in_charge',
            'commencement_month_year',
            'opening_balance',
            'amount_sanctioned',
            'amount_forwarded',
            'local_contribution',
            'overall_project_budget',
            'status',
            'current_phase',
            'project_title',
        ];

        // Phase 4A — Shared dataset returned from cache.
        // This collection is passed to multiple dashboard widgets.
        // It must be treated as immutable by callers.

        // General users: bypass cache (dataset scope depends on session province filter)
        if ($provincial->role === 'general') {
            return ProjectQueryService::forProvincial($provincial, $fy)
                ->select($select)
                ->with($with)
                ->get();
        }

        $cacheKey = "provincial_dataset_{$provincial->id}_{$fy}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($provincial, $fy, $with, $select) {
            return ProjectQueryService::forProvincial($provincial, $fy)
                ->select($select)
                ->with($with)
                ->get();
        });
    }

    /**
     * Clear cached provincial dataset for the given provincial and FY.
     * Call on project/report approval, revert, update, or budget changes.
     *
     * @param int $provincialId Provincial user ID
     * @param string $fy Financial year (e.g. "2025-26")
     * @return void
     */
    public static function clearProvincialDataset(int $provincialId, string $fy): void
    {
        Cache::forget("provincial_dataset_{$provincialId}_{$fy}");
    }

    /**
     * Get the Coordinator dashboard project dataset (cached by FY).
     * Phase 2: Uses ProjectQueryService::forCoordinator() and caches result.
     * Optional filters (province, center, role, parent_id, project_type) are
     * applied in-memory after retrieval so cache key stays coordinator_dataset_{fy}.
     *
     * @param User $coordinator Coordinator user
     * @param string $fy Financial year (e.g. "2025-26")
     * @param array<string, mixed>|null $filters Optional: province, center, role, parent_id, project_type (single value or array)
     * @return Collection
     */
    public static function getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection
    {
        $with = ['user', 'reports.accountDetails', 'budgets'];
        $select = [
            'id',
            'project_id',
            'province_id',
            'society_id',
            'project_type',
            'user_id',
            'in_charge',
            'commencement_month_year',
            'opening_balance',
            'amount_sanctioned',
            'amount_forwarded',
            'local_contribution',
            'overall_project_budget',
            'status',
            'current_phase',
            'project_title',
        ];

        $cacheKey = "coordinator_dataset_{$fy}";

        /** @var Collection $collection */
        $collection = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($coordinator, $fy, $select, $with) {
            return ProjectQueryService::forCoordinator($coordinator, $fy)
                ->select($select)
                ->with($with)
                ->get();
        });

        if ($filters !== null && $filters !== []) {
            $collection = self::applyCoordinatorFilters($collection, $filters);
        }

        return $collection;
    }

    /**
     * Apply optional filters to coordinator dataset in-memory.
     * Keys: province (→ project.province_id), center (→ user.center), role (→ user.role),
     * parent_id (→ user.parent_id), project_type (→ project.project_type).
     * Values can be scalar or array (whereIn semantics).
     *
     * @param Collection $collection
     * @param array<string, mixed> $filters
     * @return Collection
     */
    protected static function applyCoordinatorFilters(Collection $collection, array $filters): Collection
    {
        return $collection->filter(function ($project) use ($filters) {
            $user = $project->relationLoaded('user') ? $project->user : null;

            if (isset($filters['province'])) {
                $v = $filters['province'];
                // Coordinator UI filters by user.province (string); support both province_id and user.province
                if (is_numeric($v) || (is_array($v) && isset($v[0]) && is_numeric($v[0]))) {
                    $ids = is_array($v) ? $v : [$v];
                    if (! in_array($project->province_id, $ids, true)) {
                        return false;
                    }
                } else {
                    $provinces = is_array($v) ? $v : [$v];
                    if ($user === null || ! in_array($user->province, $provinces, true)) {
                        return false;
                    }
                }
            }
            if (isset($filters['project_type'])) {
                $v = $filters['project_type'];
                $types = is_array($v) ? $v : [$v];
                if (! in_array($project->project_type, $types, true)) {
                    return false;
                }
            }
            if ($user === null) {
                return true;
            }
            if (isset($filters['center'])) {
                $v = $filters['center'];
                $centers = is_array($v) ? $v : [$v];
                if (! in_array($user->center, $centers, true)) {
                    return false;
                }
            }
            if (isset($filters['role'])) {
                $v = $filters['role'];
                $roles = is_array($v) ? $v : [$v];
                if (! in_array($user->role, $roles, true)) {
                    return false;
                }
            }
            if (isset($filters['parent_id'])) {
                $v = $filters['parent_id'];
                $ids = is_array($v) ? $v : [$v];
                if (! in_array($user->parent_id, $ids, true)) {
                    return false;
                }
            }
            return true;
        })->values();
    }

    /**
     * Clear cached coordinator dataset for the given FY.
     * Call on project/report approval, revert, update, or budget changes.
     *
     * @param string $fy Financial year (e.g. "2025-26")
     * @return void
     */
    public static function clearCoordinatorDataset(string $fy): void
    {
        Cache::forget("coordinator_dataset_{$fy}");
    }
}
