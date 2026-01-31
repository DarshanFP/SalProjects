<?php

namespace App\Services\Budget;

use App\Constants\ProjectStatus;
use App\Models\BudgetCorrectionAudit;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6: Admin-only, explicit, auditable budget correction.
 * Bypasses Phase 3 edit locks; every action is logged and attributable.
 * NO automatic correction; all updates require explicit admin action.
 *
 * @see Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md §10 Phase 6a
 */
class AdminCorrectionService
{
    protected ProjectFundFieldsResolver $resolver;

    protected const FUND_KEYS = [
        'overall_project_budget',
        'amount_forwarded',
        'local_contribution',
        'amount_sanctioned',
        'opening_balance',
    ];

    public function __construct(ProjectFundFieldsResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Apply system-suggested (resolver) values to project. Re-validate with resolver before applying.
     * Approved status remains approved.
     */
    public function acceptSuggested(Project $project, User $admin, ?string $comment = null): void
    {
        $this->assertApproved($project);
        $resolved = $this->resolver->resolve($project, true);
        $stored = $this->getStoredValues($project);

        DB::transaction(function () use ($project, $admin, $resolved, $stored, $comment) {
            $this->applyValuesToProject($project, $resolved);
            $this->logAudit(
                $project,
                $admin,
                BudgetCorrectionAudit::ACTION_ACCEPT_SUGGESTED,
                $stored,
                $resolved,
                $comment ?? 'Budget alignment correction – system-suggested values applied.'
            );
        });
    }

    /**
     * Apply admin-provided values. Mandatory reason. Validates: combined ≤ overall, sanctioned/opening formula.
     */
    public function manualCorrection(Project $project, User $admin, array $newValues, string $reason): void
    {
        $this->assertApproved($project);
        $this->validateManualValues($newValues);
        $stored = $this->getStoredValues($project);
        $normalized = $this->normalizeManualValues($newValues);

        DB::transaction(function () use ($project, $admin, $stored, $normalized, $reason) {
            $this->applyValuesToProject($project, $normalized);
            $this->logAudit(
                $project,
                $admin,
                BudgetCorrectionAudit::ACTION_MANUAL_CORRECTION,
                $stored,
                $normalized,
                $reason
            );
        });
    }

    /**
     * Reject correction: no data change; project marked as "reviewed" via audit log only.
     */
    public function rejectCorrection(Project $project, User $admin, ?string $comment = null): void
    {
        $this->assertApproved($project);
        $stored = $this->getStoredValues($project);

        $this->logAudit(
            $project,
            $admin,
            BudgetCorrectionAudit::ACTION_REJECT,
            $stored,
            null,
            $comment ?? 'Correction rejected – no change applied.'
        );
    }

    /**
     * Get stored fund values from project (for comparison/audit).
     */
    public function getStoredValues(Project $project): array
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
     * Check if stored and resolved differ (tolerance 0.01).
     */
    public function hasDiscrepancy(array $resolved, array $stored): bool
    {
        $tolerance = 0.01;
        foreach (self::FUND_KEYS as $key) {
            $r = $resolved[$key] ?? 0;
            $s = $stored[$key] ?? 0;
            if (abs($r - $s) > $tolerance) {
                return true;
            }
        }
        return false;
    }

    protected function assertApproved(Project $project): void
    {
        if (!ProjectStatus::isApproved($project->status ?? '')) {
            throw ValidationException::withMessages([
                'project' => ['Only approved projects can be reconciled.'],
            ]);
        }
    }

    protected function validateManualValues(array $values): void
    {
        $overall = (float) ($values['overall_project_budget'] ?? 0);
        $forwarded = (float) ($values['amount_forwarded'] ?? 0);
        $local = (float) ($values['local_contribution'] ?? 0);

        if ($overall < 0 || $forwarded < 0 || $local < 0) {
            throw ValidationException::withMessages([
                'overall_project_budget' => ['Values must be non-negative.'],
            ]);
        }
        if ($forwarded + $local > $overall) {
            throw ValidationException::withMessages([
                'local_contribution' => ['Combined forwarded + local contribution cannot exceed overall budget.'],
            ]);
        }
    }

    protected function normalizeManualValues(array $values): array
    {
        $overall = round(max(0, (float) ($values['overall_project_budget'] ?? 0)), 2);
        $forwarded = round(max(0, (float) ($values['amount_forwarded'] ?? 0)), 2);
        $local = round(max(0, (float) ($values['local_contribution'] ?? 0)), 2);
        $sanctioned = round(max(0, $overall - ($forwarded + $local)), 2);
        $opening = round($sanctioned + $forwarded + $local, 2);

        return [
            'overall_project_budget' => $overall,
            'amount_forwarded' => $forwarded,
            'local_contribution' => $local,
            'amount_sanctioned' => $sanctioned,
            'opening_balance' => $opening,
        ];
    }

    protected function applyValuesToProject(Project $project, array $values): void
    {
        $project->update([
            'overall_project_budget' => (string) $values['overall_project_budget'],
            'amount_forwarded' => (string) $values['amount_forwarded'],
            'local_contribution' => (string) $values['local_contribution'],
            'amount_sanctioned' => (string) $values['amount_sanctioned'],
            'opening_balance' => (string) $values['opening_balance'],
        ]);
    }

    protected function logAudit(
        Project $project,
        User $admin,
        string $actionType,
        array $beforeValues,
        ?array $afterValues,
        ?string $adminComment
    ): void {
        BudgetCorrectionAudit::create([
            'project_id' => $project->id,
            'project_type' => $project->project_type ?? '',
            'admin_user_id' => $admin->id,
            'user_role' => $admin->role ?? 'admin',
            'action_type' => $actionType,
            'old_overall' => $beforeValues['overall_project_budget'] ?? null,
            'old_forwarded' => $beforeValues['amount_forwarded'] ?? null,
            'old_local' => $beforeValues['local_contribution'] ?? null,
            'old_sanctioned' => $beforeValues['amount_sanctioned'] ?? null,
            'old_opening' => $beforeValues['opening_balance'] ?? null,
            'new_overall' => $afterValues['overall_project_budget'] ?? null,
            'new_forwarded' => $afterValues['amount_forwarded'] ?? null,
            'new_local' => $afterValues['local_contribution'] ?? null,
            'new_sanctioned' => $afterValues['amount_sanctioned'] ?? null,
            'new_opening' => $afterValues['opening_balance'] ?? null,
            'admin_comment' => $adminComment,
            'ip_address' => request()?->ip(),
        ]);
    }
}
