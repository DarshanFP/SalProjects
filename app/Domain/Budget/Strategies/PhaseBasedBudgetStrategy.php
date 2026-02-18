<?php

namespace App\Domain\Budget\Strategies;

use App\Models\OldProjects\Project;
use App\Services\Budget\BudgetSyncGuard;
use App\Services\Budget\DerivedCalculationService;

/**
 * Resolves fund fields for phase-based budget project types.
 *
 * Uses project_budgets filtered by current_phase. Approved projects use DB
 * amount_sanctioned and opening_balance. All arithmetic via DerivedCalculationService.
 *
 * Project types: Development Projects, NEXT PHASE - DEVELOPMENT PROPOSAL,
 * Livelihood Development Projects, Residential Skill Training Proposal 2,
 * PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER, CHILD CARE INSTITUTION,
 * Rural-Urban-Tribal.
 *
 * @see Documentations/V2/Budgets/Overview/FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md
 */
class PhaseBasedBudgetStrategy implements ProjectFinancialStrategyInterface
{
    public function __construct(
        protected DerivedCalculationService $calculationService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Project $project): array
    {
        $forwarded = (float) ($project->amount_forwarded ?? 0);
        $local = (float) ($project->local_contribution ?? 0);
        $currentPhase = (int) ($project->current_phase ?? 1);

        $project->loadMissing('budgets');
        $phaseBudgets = $project->relationLoaded('budgets')
            ? $project->budgets->where('phase', $currentPhase)
            : collect();

        if ($phaseBudgets->isNotEmpty()) {
            $thisPhaseValues = $phaseBudgets->map(fn ($b) => (float) ($b->this_phase ?? 0));
            $overall = $this->calculationService->calculateProjectTotal($thisPhaseValues);
        } else {
            $overall = (float) ($project->overall_project_budget ?? 0);
        }

        $combined = $forwarded + $local;
        if (BudgetSyncGuard::isApproved($project)) {
            $sanctioned = (float) ($project->amount_sanctioned ?? 0);
            $opening = (float) ($project->opening_balance ?? 0);
            $requested = 0.0;
        } else {
            // M3.7: Do not treat sanctioned as requested. Return requested separately; resolver sets sanctioned = 0.
            $requested = max(0.0, $this->calculateAmountSanctioned($overall, $combined));
            $sanctioned = 0.0;
            $opening = $combined;
        }

        return $this->normalize([
            'overall_project_budget' => $overall,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => max(0, $sanctioned),
            'amount_requested' => max(0, $requested),
            'opening_balance' => max(0, $opening),
        ]);
    }

    /**
     * Amount requested (pre-approval) = overall - (forwarded + local).
     * Future: move to DerivedCalculationService when permitted.
     */
    private function calculateAmountSanctioned(float $overall, float $combinedContribution): float
    {
        return $overall - $combinedContribution;
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
