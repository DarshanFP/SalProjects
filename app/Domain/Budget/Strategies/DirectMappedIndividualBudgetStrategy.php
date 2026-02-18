<?php

namespace App\Domain\Budget\Strategies;

use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\DerivedCalculationService;

/**
 * Resolves fund fields for direct-mapped individual / IGE project types.
 *
 * M3.7 Phase 1: Does not write amount_sanctioned for non-approved; returns requested separately.
 * Resolver enforces sanctioned = 0 pre-approval.
 *
 * Uses type-specific tables (IIES, IES, ILP, IAH, IGE). No phase logic.
 * Uses DerivedCalculationService for sums where applicable.
 *
 * Project types: Individual - Initial - Educational support (IIES),
 * Individual - Ongoing Educational support (IES), Individual - Livelihood Application (ILP),
 * Individual - Access to Health (IAH), Institutional Ongoing Group Educational proposal (IGE).
 *
 * @see Documentations/V2/Budgets/Overview/FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md
 */
class DirectMappedIndividualBudgetStrategy implements ProjectFinancialStrategyInterface
{
    public function __construct(
        protected DerivedCalculationService $calculationService
    ) {
    }

    /**
     * @inheritDoc
     * M3.7: For non-approved returns amount_requested from type, amount_sanctioned = 0, opening_balance = forwarded + local.
     */
    public function resolve(Project $project): array
    {
        $projectType = $project->project_type ?? '';

        $project->loadMissing($this->getRelationsForType($projectType));
        $resolved = match ($projectType) {
            'Individual - Initial - Educational support' => $this->resolveIIES($project),
            'Individual - Ongoing Educational support' => $this->resolveIES($project),
            'Individual - Livelihood Application' => $this->resolveILP($project),
            'Individual - Access to Health' => $this->resolveIAH($project),
            'Institutional Ongoing Group Educational proposal' => $this->resolveIGE($project),
            default => $this->fallbackFromProject($project),
        };

        // M3.7 Phase 1: Canonical separation. Do not treat sanctioned as requested; return requested separately.
        if (BudgetSyncGuard::isApproved($project)) {
            $resolved['amount_sanctioned'] = (float) ($project->amount_sanctioned ?? 0);
            $resolved['amount_requested'] = 0.0;
            $resolved['opening_balance'] = (float) ($project->opening_balance ?? 0);
        } else {
            $resolved['amount_requested'] = (float) ($resolved['amount_sanctioned'] ?? 0);
            $resolved['amount_sanctioned'] = 0.0;
            $resolved['opening_balance'] = (float) ($resolved['amount_forwarded'] ?? 0) + (float) ($resolved['local_contribution'] ?? 0);
        }

        return $this->normalize($resolved);
    }

    private function getRelationsForType(string $projectType): array
    {
        return match ($projectType) {
            'Individual - Initial - Educational support' => ['iiesExpenses'],
            'Individual - Ongoing Educational support' => ['iesExpenses'],
            'Individual - Livelihood Application' => ['ilpBudget'],
            'Individual - Access to Health' => ['iahBudgetDetails'],
            'Institutional Ongoing Group Educational proposal' => ['igeBudget'],
            default => [],
        };
    }

    private function resolveIIES(Project $project): array
    {
        \Log::info('IIES Resolver Debug', [
            'project_id' => $project->project_id,
            'project_type' => $project->project_type,
            'relation_loaded' => $project->relationLoaded('iiesExpenses'),
            'relation_value_is_null' => is_null($project->iiesExpenses),
            'raw_relation' => $project->iiesExpenses,
        ]);
        $expenses = $project->iiesExpenses;
        if (!$expenses) {
            return $this->fallbackFromProject($project);
        }

        $overall = (float) ($expenses->iies_total_expenses ?? 0);
        $local = (float) ($expenses->iies_expected_scholarship_govt ?? 0)
            + (float) ($expenses->iies_support_other_sources ?? 0)
            + (float) ($expenses->iies_beneficiary_contribution ?? 0);
        $sanctioned = (float) ($expenses->iies_balance_requested ?? 0);
        $opening = $overall;

        return [
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ];
    }

    private function resolveIES(Project $project): array
    {
        $expenses = $project->iesExpenses?->first();
        if (!$expenses) {
            return $this->fallbackFromProject($project);
        }

        $overall = (float) ($expenses->total_expenses ?? 0);
        $local = (float) ($expenses->expected_scholarship_govt ?? 0)
            + (float) ($expenses->support_other_sources ?? 0)
            + (float) ($expenses->beneficiary_contribution ?? 0);
        $sanctioned = (float) ($expenses->balance_requested ?? 0);
        $opening = $overall;

        return [
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ];
    }

    private function resolveILP(Project $project): array
    {
        $budgets = $project->ilpBudget;
        if (!$budgets || $budgets->isEmpty()) {
            return $this->fallbackFromProject($project);
        }

        $costValues = $budgets->map(fn ($b) => (float) ($b->cost ?? 0));
        $overall = $this->calculationService->calculateProjectTotal($costValues);
        $first = $budgets->first();
        $local = (float) ($first->beneficiary_contribution ?? 0);
        $sanctioned = (float) ($first->amount_requested ?? 0);
        $opening = $overall;

        return [
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ];
    }

    private function resolveIAH(Project $project): array
    {
        $details = $project->iahBudgetDetails;
        if (!$details || $details->isEmpty()) {
            return $this->fallbackFromProject($project);
        }

        $amountValues = $details->map(fn ($d) => (float) ($d->amount ?? 0));
        $overall = $this->calculationService->calculateProjectTotal($amountValues);
        $first = $details->first();
        $local = (float) ($first->family_contribution ?? 0);
        $sanctioned = (float) ($first->amount_requested ?? 0);
        $opening = $overall;

        return [
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ];
    }

    private function resolveIGE(Project $project): array
    {
        $budgets = $project->igeBudget;
        if (!$budgets || $budgets->isEmpty()) {
            return $this->fallbackFromProject($project);
        }

        $totalAmountValues = $budgets->map(fn ($b) => (float) ($b->total_amount ?? 0));
        $overall = $this->calculationService->calculateProjectTotal($totalAmountValues);

        $scholarshipValues = $budgets->map(fn ($b) => (float) ($b->scholarship_eligibility ?? 0));
        $familyValues = $budgets->map(fn ($b) => (float) ($b->family_contribution ?? 0));
        $local = $this->calculationService->calculateProjectTotal($scholarshipValues)
            + $this->calculationService->calculateProjectTotal($familyValues);

        $amountRequestedValues = $budgets->map(fn ($b) => (float) ($b->amount_requested ?? 0));
        $sanctioned = $this->calculationService->calculateProjectTotal($amountRequestedValues);
        $opening = $overall;

        return [
            'overall_project_budget' => $overall,
            'amount_forwarded' => 0,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ];
    }

    private function fallbackFromProject(Project $project): array
    {
        return [
            'overall_project_budget' => (float) ($project->overall_project_budget ?? 0),
            'amount_forwarded' => (float) ($project->amount_forwarded ?? 0),
            'local_contribution' => (float) ($project->local_contribution ?? 0),
            'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),
            'amount_requested' => 0.0,
            'opening_balance' => (float) ($project->opening_balance ?? 0),
        ];
    }

    /**
     * Ensure all values are non-negative floats rounded to 2 decimals. Includes amount_requested (M3.7).
     */
    protected function normalize(array $values): array
    {
        $keys = [
            'overall_project_budget',
            'amount_forwarded',
            'local_contribution',
            'amount_sanctioned',
            'amount_requested',
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
