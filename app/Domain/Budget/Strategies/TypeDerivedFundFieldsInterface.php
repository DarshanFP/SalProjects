<?php

namespace App\Domain\Budget\Strategies;

use App\Models\OldProjects\Project;

/**
 * Strategy capability: compute fund fields from type-specific source tables
 * without overlaying stored approved-project DB values.
 *
 * Used for approved-project repair and report overview fallback when
 * projects.amount_sanctioned / opening_balance are stale or zero.
 */
interface TypeDerivedFundFieldsInterface
{
    /**
     * @return array{
     *     overall_project_budget: float,
     *     amount_forwarded: float,
     *     local_contribution: float,
     *     amount_sanctioned: float,
     *     amount_requested?: float,
     *     opening_balance: float
     * }
     */
    public function resolveFromTypeTables(Project $project): array;
}
