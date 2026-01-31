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
 */
class ProjectFundFieldsResolver
{
    /**
     * Resolve project-level fund fields from the correct source per project type.
     *
     * @param Project $project
     * @param bool $dryRun Always true in Phase 1; no writes
     * @return array{overall_project_budget: float, amount_forwarded: float, local_contribution: float, amount_sanctioned: float, opening_balance: float}
     */
    public function resolve(Project $project, bool $dryRun = true): array
    {
        $projectType = $project->project_type ?? '';

        // Log every resolver call (MANDATORY)
        $resolved = $this->resolveForType($project, $projectType);
        BudgetAuditLogger::logResolverCall(
            $project->project_id ?? $project->id,
            $projectType,
            $resolved,
            $dryRun
        );

        // If resolved ≠ stored, log discrepancy (MANDATORY)
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
     * Resolve values based on project type.
     */
    protected function resolveForType(Project $project, string $projectType): array
    {
        $individualTypes = [
            'Individual - Initial - Educational support' => 'iies',
            'Individual - Ongoing Educational support' => 'ies',
            'Individual - Livelihood Application' => 'ilp',
            'Individual - Access to Health' => 'iah',
        ];

        if (isset($individualTypes[$projectType])) {
            return $this->resolveIndividualOrIge($project, $individualTypes[$projectType]);
        }

        if ($projectType === 'Institutional Ongoing Group Educational proposal') {
            return $this->resolveIndividualOrIge($project, 'ige');
        }

        // Development / institutional types using project_budgets
        return $this->resolveDevelopment($project);
    }

    /**
     * Development types: General Info + phase budgets (fallback when overall = 0).
     */
    protected function resolveDevelopment(Project $project): array
    {
        $overall = (float) ($project->overall_project_budget ?? 0);
        $forwarded = (float) ($project->amount_forwarded ?? 0);
        $local = (float) ($project->local_contribution ?? 0);

        // Locked rule: overall from General Info; phase sum only if overall = 0
        if ($overall == 0) {
            $project->loadMissing('budgets');
        }
        if ($overall == 0 && $project->relationLoaded('budgets') && $project->budgets->isNotEmpty()) {
            $currentPhase = (int) ($project->current_phase ?? 1);
            $phaseBudgets = $project->budgets->where('phase', $currentPhase);
            $overall = $phaseBudgets->sum(function ($b) {
                return (float) ($b->this_phase ?? 0);
            });
        }

        $sanctioned = $overall - ($forwarded + $local);
        $opening = $sanctioned + $forwarded + $local;

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => max(0, $sanctioned),
            'opening_balance' => max(0, $opening),
        ]);
    }

    /**
     * IIES: From ProjectIIESExpenses (single row per project).
     */
    protected function resolveIIES(Project $project): array
    {
        $expenses = $project->iiesExpenses;
        if (!$expenses) {
            return $this->fallbackFromProject($project);
        }

        $overall = (float) ($expenses->iies_total_expenses ?? 0);
        $local = (float) ($expenses->iies_expected_scholarship_govt ?? 0)
            + (float) ($expenses->iies_support_other_sources ?? 0)
            + (float) ($expenses->iies_beneficiary_contribution ?? 0);
        $sanctioned = (float) ($expenses->iies_balance_requested ?? 0);
        $opening = $overall; // Same as overall when Amount Forwarded = 0

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ]);
    }

    /**
     * IES: From ProjectIESExpenses (first/primary row).
     */
    protected function resolveIES(Project $project): array
    {
        $expenses = $project->iesExpenses->first();
        if (!$expenses) {
            return $this->fallbackFromProject($project);
        }

        $overall = (float) ($expenses->total_expenses ?? 0);
        $local = (float) ($expenses->expected_scholarship_govt ?? 0)
            + (float) ($expenses->support_other_sources ?? 0)
            + (float) ($expenses->beneficiary_contribution ?? 0);
        $sanctioned = (float) ($expenses->balance_requested ?? 0);
        $opening = $overall;

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ]);
    }

    /**
     * ILP: From ProjectILPBudget (multiple rows).
     */
    protected function resolveILP(Project $project): array
    {
        $budgets = $project->ilpBudget;
        if (!$budgets || $budgets->isEmpty()) {
            return $this->fallbackFromProject($project);
        }

        $overall = $budgets->sum(fn ($b) => (float) ($b->cost ?? 0));
        $first = $budgets->first();
        $local = (float) ($first->beneficiary_contribution ?? 0);
        $sanctioned = (float) ($first->amount_requested ?? 0);
        $opening = $overall;

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ]);
    }

    /**
     * IAH: From ProjectIAHBudgetDetails (multiple rows).
     */
    protected function resolveIAH(Project $project): array
    {
        $details = $project->iahBudgetDetails;
        if (!$details || $details->isEmpty()) {
            return $this->fallbackFromProject($project);
        }

        $overall = $details->sum(fn ($d) => (float) ($d->amount ?? 0));
        $first = $details->first();
        $local = (float) ($first->family_contribution ?? 0);
        $sanctioned = (float) ($first->amount_requested ?? 0);
        $opening = $overall;

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ]);
    }

    /**
     * IGE: From ProjectIGEBudget (multiple rows).
     */
    protected function resolveIGE(Project $project): array
    {
        $budgets = $project->igeBudget;
        if (!$budgets || $budgets->isEmpty()) {
            return $this->fallbackFromProject($project);
        }

        $overall = $budgets->sum(fn ($b) => (float) ($b->total_amount ?? 0));
        $local = $budgets->sum(fn ($b) => (float) ($b->scholarship_eligibility ?? 0))
            + $budgets->sum(fn ($b) => (float) ($b->family_contribution ?? 0));
        $sanctioned = $budgets->sum(fn ($b) => (float) ($b->amount_requested ?? 0));
        $opening = $overall;

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ]);
    }

    /**
     * Dispatch to type-specific resolver for individual/IGE types.
     * Loads required relations if not already loaded.
     */
    protected function resolveIndividualOrIge(Project $project, string $type): array
    {
        $relations = match ($type) {
            'iies' => ['iiesExpenses'],
            'ies' => ['iesExpenses'],
            'ilp' => ['ilpBudget'],
            'iah' => ['iahBudgetDetails'],
            'ige' => ['igeBudget'],
            default => [],
        };
        if (!empty($relations)) {
            $project->loadMissing($relations);
        }

        return match ($type) {
            'iies' => $this->resolveIIES($project),
            'ies' => $this->resolveIES($project),
            'ilp' => $this->resolveILP($project),
            'iah' => $this->resolveIAH($project),
            'ige' => $this->resolveIGE($project),
            default => $this->fallbackFromProject($project),
        };
    }

    /**
     * Fallback when type-specific data is missing: return project's current values.
     */
    protected function fallbackFromProject(Project $project): array
    {
        return $this->normalize([
            'overall_project_budget' => (float) ($project->overall_project_budget ?? 0),
            'amount_forwarded' => (float) ($project->amount_forwarded ?? 0),
            'local_contribution' => (float) ($project->local_contribution ?? 0),
            'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),
            'opening_balance' => (float) ($project->opening_balance ?? 0),
        ]);
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

    /**
     * Ensure all values are non-negative floats.
     */
    protected function normalize(array $values): array
    {
        $keys = [
            'overall_project_budget',
            'amount_forwarded',
            'local_contribution',
            'amount_sanctioned',
            'opening_balance',
        ];
        $out = [];
        foreach ($keys as $k) {
            $v = $values[$k] ?? 0;
            $out[$k] = round(max(0, (float) $v), 2);
        }
        return $out;
    }
}
