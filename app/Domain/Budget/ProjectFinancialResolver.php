<?php

namespace App\Domain\Budget;

use App\Domain\Budget\Strategies\DirectMappedIndividualBudgetStrategy;
use App\Domain\Budget\Strategies\PhaseBasedBudgetStrategy;
use App\Domain\Budget\Strategies\ProjectFinancialStrategyInterface;
use App\Models\OldProjects\Project;
use App\Services\Budget\DerivedCalculationService;
use Illuminate\Support\Collection;
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
     * M3.7 Phase 1: Canonical separation.
     * - Non-approved: amount_sanctioned = 0, amount_requested = overall - (forwarded + local), opening = forwarded + local.
     * - Approved: amount_sanctioned = DB value, amount_requested = 0, opening_balance = DB value.
     *
     * @param Project $project
     * @param bool $force Optional; when true, caller may use for force-recompute semantics. No behavioural change when false.
     * @return array{
     *     overall_project_budget: float,
     *     amount_forwarded: float,
     *     local_contribution: float,
     *     amount_sanctioned: float,
     *     amount_requested: float,
     *     opening_balance: float
     * }
     */
    public function resolve(Project $project, bool $force = false): array
    {
        $strategy = $this->getStrategyForProject($project);
        $result = $strategy->resolve($project);
        $overlaid = $this->applyCanonicalSeparation($project, $result);
        $normalized = $this->normalize($overlaid);

        $this->assertFinancialInvariants($project, $normalized);

        return $normalized;
    }

    /**
     * M3.7 Phase 1: Apply canonical sanctioned vs requested separation.
     *
     * Non-approved: amount_sanctioned = 0, amount_requested = max(0, overall - (forwarded + local)), opening_balance = forwarded + local.
     * Approved: amount_sanctioned = project->amount_sanctioned, amount_requested = 0, opening_balance = project->opening_balance.
     */
    private function applyCanonicalSeparation(Project $project, array $result): array
    {
        $overall = (float) ($result['overall_project_budget'] ?? 0);
        $forwarded = (float) ($result['amount_forwarded'] ?? 0);
        $local = (float) ($result['local_contribution'] ?? 0);
        $combined = $forwarded + $local;

        if (!$project->isApproved()) {
            $requested = (float) ($result['amount_requested'] ?? max(0, $overall - $combined));
            return array_merge($result, [
                'amount_sanctioned' => 0.0,
                'amount_requested' => round(max(0, $requested), 2),
                'opening_balance' => $combined,
            ]);
        }

        return array_merge($result, [
            'amount_sanctioned' => (float) ($project->amount_sanctioned ?? 0),
            'amount_requested' => 0.0,
            'opening_balance' => (float) ($project->opening_balance ?? 0),
        ]);
    }

    /**
     * M3.5.3 / M3.7 Phase 1: Assert financial invariants. Log only; no auto-fix.
     * M3.7: Critical warning when DB has amount_sanctioned > 0 for non-approved project.
     */
    private function assertFinancialInvariants(Project $project, array $data): void
    {
        $projectId = $project->project_id ?? 'unknown';
        $sanctioned = (float) ($data['amount_sanctioned'] ?? 0);
        $opening = (float) ($data['opening_balance'] ?? 0);
        $overall = (float) ($data['overall_project_budget'] ?? 0);
        $tolerance = 0.01;

        // M3.7 Phase 1: Strict assertion — non-approved must not have sanctioned > 0 in DB (stabilizing semantics before cleanup).
        if (!$project->isApproved()) {
            $storedSanctioned = (float) ($project->amount_sanctioned ?? 0);
            if ($storedSanctioned > $tolerance) {
                Log::critical('Financial invariant violation: non-approved project has amount_sanctioned > 0 in DB (M3.7)', [
                    'project_id' => $projectId,
                    'stored_amount_sanctioned' => $storedSanctioned,
                    'invariant' => 'non_approved_implies_sanctioned_zero',
                ]);
            }
            if (abs($sanctioned) > $tolerance) {
                Log::warning('Financial invariant violation: resolved amount_sanctioned must be 0 for non-approved', [
                    'project_id' => $projectId,
                    'amount_sanctioned' => $sanctioned,
                    'invariant' => 'amount_sanctioned == 0',
                ]);
            }
        }

        if ($project->isApproved()) {
            if ($sanctioned <= 0) {
                Log::warning('Financial invariant violation: approved project must have amount_sanctioned > 0', [
                    'project_id' => $projectId,
                    'amount_sanctioned' => $sanctioned,
                    'invariant' => 'amount_sanctioned > 0',
                ]);
            }
            // Phase 1: Canonical rule — expected_opening = sanctioned + forwarded + local (replaces INV-7)
            $forwarded = (float) ($project->amount_forwarded ?? 0);
            $local = (float) ($project->local_contribution ?? 0);
            $expectedOpening = $sanctioned + $forwarded + $local;
            if (abs($opening - $expectedOpening) > $tolerance) {
                Log::warning('Financial invariant violation: opening_balance != amount_sanctioned + amount_forwarded + local_contribution (Phase 1 canonical)', [
                    'project_id' => $projectId,
                    'opening_balance' => $opening,
                    'expected_opening' => $expectedOpening,
                    'amount_sanctioned' => $sanctioned,
                    'amount_forwarded' => $forwarded,
                    'local_contribution' => $local,
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
     * Ensure all numeric values are float, rounded to 2 decimals. Includes amount_requested (M3.7).
     */
    protected function normalize(array $data): array
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
            $v = $data[$k] ?? 0;
            $out[$k] = round(max(0, (float) $v), 2);
        }
        return $out;
    }

    /**
     * Resolve financials for a collection of projects in one pass.
     * Phase 2.6: Batch resolution for dashboard optimization.
     * Returns map keyed by project_id; structure matches resolve() output.
     *
     * @param Collection<int, Project> $projects Projects must have reports, reports.accountDetails, budgets eager-loaded.
     * @return array<string, array{overall_project_budget: float, amount_forwarded: float, local_contribution: float, amount_sanctioned: float, amount_requested: float, opening_balance: float}>
     */
    public static function resolveCollection(Collection $projects): array
    {
        $resolver = app(self::class);
        $result = [];
        foreach ($projects as $project) {
            $result[$project->project_id] = $resolver->resolve($project);
        }
        return $result;
    }
}
