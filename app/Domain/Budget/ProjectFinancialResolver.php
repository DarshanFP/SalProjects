<?php

namespace App\Domain\Budget;

use App\Domain\Budget\Strategies\DirectMappedIndividualBudgetStrategy;
use App\Domain\Budget\Strategies\PhaseBasedBudgetStrategy;
use App\Domain\Budget\Strategies\ProjectFinancialStrategyInterface;
use App\Models\OldProjects\Project;
use App\Services\Budget\DerivedCalculationService;
use Illuminate\Support\Facades\Log;

/**
 * Project Financial Resolver (Scaffold)
 *
 * Single entry point for resolving project-level fund fields. Delegates to
 * strategy based on project_type. Contains ZERO arithmetic, DB queries,
 * expense logic, or phase manipulation.
 *
 * NOT YET WIRED — scaffolding only. Do not use in controllers until
 * parity tests pass.
 *
 * @see Documentations/V2/Budgets/Overview/FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md
 * @see Documentations/V2/Budgets/Overview/RESOLVER_IMPLEMENTATION_TODO.md
 */
class ProjectFinancialResolver
{
    /** @var list<string> */
    private const PHASE_BASED_TYPES = [
        'Development Projects',
        'NEXT PHASE - DEVELOPMENT PROPOSAL',
        'Livelihood Development Projects',
        'Residential Skill Training Proposal 2',
        'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER',
        'CHILD CARE INSTITUTION',
        'Rural-Urban-Tribal',
    ];

    /** @var list<string> */
    private const DIRECT_MAPPED_INDIVIDUAL_TYPES = [
        'Individual - Initial - Educational support',
        'Individual - Ongoing Educational support',
        'Individual - Livelihood Application',
        'Individual - Access to Health',
        'Institutional Ongoing Group Educational proposal',
    ];

    public function __construct(
        protected DerivedCalculationService $calculationService
    ) {
    }

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
    public function resolve(Project $project): array
    {
        $strategy = $this->getStrategyForProject($project);
        $result = $strategy->resolve($project);
        $normalized = $this->normalize($result);

        $this->assertFinancialInvariants($project, $normalized);

        return $normalized;
    }

    /**
     * M3.5.3: Log warnings if financial invariants are violated. Non-breaking.
     */
    private function assertFinancialInvariants(Project $project, array $data): void
    {
        $projectId = $project->project_id ?? 'unknown';
        $sanctioned = (float) ($data['amount_sanctioned'] ?? 0);
        $opening = (float) ($data['opening_balance'] ?? 0);
        $overall = (float) ($data['overall_project_budget'] ?? 0);
        $tolerance = 0.01;

        if ($project->isApproved()) {
            if ($sanctioned <= 0) {
                Log::warning('Financial invariant violation: approved project must have amount_sanctioned > 0', [
                    'project_id' => $projectId,
                    'amount_sanctioned' => $sanctioned,
                    'invariant' => 'amount_sanctioned > 0',
                ]);
            }
            if (abs($opening - $overall) > $tolerance) {
                Log::warning('Financial invariant violation: approved project must have opening_balance == overall_project_budget', [
                    'project_id' => $projectId,
                    'opening_balance' => $opening,
                    'overall_project_budget' => $overall,
                    'invariant' => 'opening_balance == overall_project_budget',
                ]);
            }
        } else {
            if (abs($sanctioned) > $tolerance) {
                Log::warning('Financial invariant violation: non-approved project must have amount_sanctioned == 0', [
                    'project_id' => $projectId,
                    'amount_sanctioned' => $sanctioned,
                    'invariant' => 'amount_sanctioned == 0',
                ]);
            }
        }
    }

    private function getStrategyForProject(Project $project): ProjectFinancialStrategyInterface
    {
        $projectType = $project->project_type ?? '';

        if (in_array($projectType, self::PHASE_BASED_TYPES, true)) {
            return new PhaseBasedBudgetStrategy($this->calculationService);
        }

        if (in_array($projectType, self::DIRECT_MAPPED_INDIVIDUAL_TYPES, true)) {
            return new DirectMappedIndividualBudgetStrategy($this->calculationService);
        }

        return new PhaseBasedBudgetStrategy($this->calculationService);
    }

    /**
     * Ensure all numeric values are float, rounded to 2 decimals.
     */
    protected function normalize(array $data): array
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
            $v = $data[$k] ?? 0;
            $out[$k] = round(max(0, (float) $v), 2);
        }
        return $out;
    }
}
