<?php

namespace App\Services\Budget;

use App\Models\OldProjects\Project;

/**
 * Project Fund Fields Resolver (Phase 1)
 *
 * Canonical read-only resolver for project-level budget values.
 * Computes expected values from correct source per project type.
 * Does NOT persist anything; Phase 1 is read-only.
 *
 * @see Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md
 * @see Documentations/V1/Basic Info fund Mapping Issue/Basic_Info_Fund_Fields_Mapping_Analysis.md
 * @see Documentations/V2/Budgets/BasicInformation_Resolver_Unification.md
 */
class ProjectFundFieldsResolver
{
    public function __construct(
        protected DerivedCalculationService $calculationService
    ) {
    }

    /**
     * Resolve project-level fund fields from the correct source per project type.
     *
     * Temporary adapter delegation to ProjectFinancialResolver.
     * Kept for backward compatibility.
     * To be removed after full migration.
     *
     * @param Project $project
     * @param bool $dryRun Always true in Phase 1; no writes
     * @return array{overall_project_budget: float, amount_forwarded: float, local_contribution: float, amount_sanctioned: float, opening_balance: float}
     */
    public function resolve(Project $project, bool $dryRun = true): array
    {
        $resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $resolved = $resolver->resolve($project);

        $projectType = $project->project_type ?? '';

        BudgetAuditLogger::logResolverCall(
            $project->project_id ?? $project->id,
            $projectType,
            $resolved,
            $dryRun
        );

        $stored = $this->getStoredValues($project);
        if ($this->hasDiscrepancy($resolved, $stored)) {
            BudgetAuditLogger::logDiscrepancy(
                $project->project_id ?? $project->id,
                $projectType,
                $resolved,
                $stored
            );
        }

        return $resolved;
    }

    /**
     * Get stored values from project for comparison.
     */
    protected function getStoredValues(Project $project): array
    {
        return [
            'overall_project_budget' => (float) ($project->overall_project_budget ?? 0),
            'amount_forwarded' => (float) ($project->amount_forwarded ?? 0),
            'local_contribution' => (float) ($project->local_contribution ?? 0),
            'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),
            'opening_balance' => (float) ($project->opening_balance ?? 0),
        ];
    }

    /**
     * Check if resolved differs from stored (with tolerance for floating-point).
     */
    protected function hasDiscrepancy(array $resolved, array $stored): bool
    {
        $tolerance = 0.01;
        foreach (array_keys($resolved) as $key) {
            $r = $resolved[$key] ?? 0;
            $s = $stored[$key] ?? 0;
            if (abs($r - $s) > $tolerance) {
                return true;
            }
        }
        return false;
    }
}
