<?php

namespace App\Services\Budget;

use App\Constants\ProjectStatus;
use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\Project;

/**
 * Detect approved projects whose stored fund fields diverge from type-table sources
 * or violate the canonical opening-balance invariant.
 */
class ApprovedProjectFundFieldAudit
{
    public const TOLERANCE = 0.01;

    public const FUND_KEYS = [
        'overall_project_budget',
        'amount_forwarded',
        'local_contribution',
        'amount_sanctioned',
        'opening_balance',
    ];

    public function __construct(
        protected ProjectFinancialResolver $financialResolver
    ) {
    }

    /**
     * @return array{
     *     project: Project,
     *     stored: array<string, float>,
     *     derived: array<string, float>,
     *     issues: list<string>
     * }
     */
    public function analyze(Project $project): array
    {
        $stored = $this->storedValues($project);
        $derived = $this->financialResolver->resolveTypeDerivedFundFields($project);
        $issues = $this->detectIssues($stored, $derived);

        return [
            'project' => $project,
            'stored' => $stored,
            'derived' => $derived,
            'issues' => $issues,
        ];
    }

    public function needsRepair(array $stored, array $derived): bool
    {
        return $this->detectIssues($stored, $derived) !== [];
    }

    /**
     * @return list<string>
     */
    public function detectIssues(array $stored, array $derived): array
    {
        $issues = [];

        if (($stored['amount_sanctioned'] ?? 0) <= self::TOLERANCE
            && ($derived['amount_sanctioned'] ?? 0) > self::TOLERANCE) {
            $issues[] = 'zero_stored_sanctioned';
        }

        $expectedOpening = ($stored['amount_sanctioned'] ?? 0)
            + ($stored['amount_forwarded'] ?? 0)
            + ($stored['local_contribution'] ?? 0);

        if (abs(($stored['opening_balance'] ?? 0) - $expectedOpening) > self::TOLERANCE) {
            $issues[] = 'opening_balance_invariant';
        }

        foreach (self::FUND_KEYS as $key) {
            if (abs(($stored[$key] ?? 0) - ($derived[$key] ?? 0)) > self::TOLERANCE) {
                $issues[] = 'derived_mismatch:' . $key;
            }
        }

        return array_values(array_unique($issues));
    }

    public function storedValues(Project $project): array
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
     * @return \Illuminate\Database\Eloquent\Builder<Project>
     */
    public function approvedProjectsQuery(?string $projectId = null)
    {
        $query = Project::query()
            ->whereIn('status', ProjectStatus::APPROVED_STATUSES);

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        return $query;
    }
}
