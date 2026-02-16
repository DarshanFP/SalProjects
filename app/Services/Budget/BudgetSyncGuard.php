<?php

namespace App\Services\Budget;

use App\Constants\ProjectStatus;
use App\Models\OldProjects\Project;

/**
 * Budget Sync Guard (Phase 0 / Phase 3)
 *
 * Encapsulates rules for when budget sync to projects is allowed (Phase 2)
 * and when budget edits are allowed (Phase 3).
 *
 * LOCKED RULES (from PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md):
 * - On type budget save: do NOT sync when $project->isApproved()
 * - Pre-approval sync: run only when status is forwarded_to_coordinator
 * - Backfill: MUST NOT modify sanctioned/opening for approved projects
 *
 * Phase 3 (post-approval enforcement): Any code path that mutates budget data
 * (overall_project_budget, amount_forwarded, local_contribution, or type-specific
 * budget tables) MUST call canEditBudget($project) before performing the mutation.
 * This includes: HTTP controllers (store/update), and any future entry points
 * (console commands, jobs, imports). There are currently no background jobs or
 * console commands that mutate budget; if added, they MUST use this guard.
 */
class BudgetSyncGuard
{
    /**
     * Check if sync on type budget save is allowed.
     *
     * Returns true only when:
     * - config('budget.sync_to_projects_on_type_save') is true
     * - config('budget.resolver_enabled') is true
     * - Project status is NOT approved (reverted projects are allowed)
     *
     * @param Project $project
     * @return bool
     */
    public static function canSyncOnTypeSave(Project $project): bool
    {
        if (!config('budget.resolver_enabled', false)) {
            return false;
        }

        if (!config('budget.sync_to_projects_on_type_save', false)) {
            return false;
        }

        // Do NOT sync approved projects on type save
        if ($project->isApproved()) {
            return false;
        }

        return true;
    }

    /**
     * Check if pre-approval sync is allowed.
     *
     * Returns true only when:
     * - config('budget.sync_to_projects_before_approval') is true
     * - config('budget.resolver_enabled') is true
     * - Project status is forwarded_to_coordinator (approval flow entry point)
     *
     * @param Project $project
     * @return bool
     */
    public static function canSyncBeforeApproval(Project $project): bool
    {
        if (!config('budget.resolver_enabled', false)) {
            return false;
        }

        if (!config('budget.sync_to_projects_before_approval', false)) {
            return false;
        }

        if (!ProjectStatus::isForwardedToCoordinator($project->status ?? '')) {
            return false;
        }

        return true;
    }

    /**
     * Check if project is approved (budget fields frozen).
     *
     * @param Project $project
     * @return bool
     */
    public static function isApproved(Project $project): bool
    {
        return $project->isApproved();
    }

    /**
     * Check if project is reverted (editable again).
     *
     * @param Project $project
     * @return bool
     */
    public static function isReverted(Project $project): bool
    {
        return ProjectStatus::isReverted($project->status ?? '');
    }

    /**
     * Check if budget sections are editable (Phase 3).
     *
     * Returns false when project is approved AND restrict_general_info_after_approval is true.
     * Reverted projects (isEditable) are allowed to edit; approved projects are locked when config is on.
     *
     * @param Project $project
     * @return bool
     */
    public static function canEditBudget(Project $project): bool
    {
        if (!config('budget.restrict_general_info_after_approval', false)) {
            return true;
        }
        return !$project->isApproved();
    }
}
