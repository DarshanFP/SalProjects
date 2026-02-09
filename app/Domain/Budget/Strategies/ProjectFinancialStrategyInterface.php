<?php

namespace App\Domain\Budget\Strategies;

use App\Models\OldProjects\Project;

/**
 * Interface for project financial resolution strategies.
 *
 * Each strategy resolves the five canonical fund fields from the appropriate
 * source per project type. Does NOT handle expenses, utilization, or remaining balance.
 *
 * @see Documentations/V2/Budgets/Overview/FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md
 */
interface ProjectFinancialStrategyInterface
{
    /**
     * Resolve project-level fund fields.
     *
     * @param Project $project
     * @return array{
     *     overall_project_budget: float,
     *     amount_forwarded: float,
     *     local_contribution: float,
     *     amount_sanctioned: float,
     *     opening_balance: float
     * }
     */
    public function resolve(Project $project): array;
}
