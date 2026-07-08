<?php

namespace App\Services\Budget;

use App\Models\OldProjects\Project;

/**
 * Budget Sync Service (Phase 2 / Phase 4)
 *
 * Controlled, explicit writes to `projects` for PRE-APPROVAL projects only.
 * Populates canonical project-level budget fields so approval and reporting read correct data.
 * Phase 4 adds explicit repair for approved projects with stale fund fields.
 * All writes are guarded, feature-flagged, and logged.
 *
 * @see Documentations/V1/Basic Info fund Mapping Issue/PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md
 */
class BudgetSyncService
{
    protected ProjectFundFieldsResolver $resolver;

    protected ApprovedProjectFundFieldAudit $fundFieldAudit;

    public function __construct(
        ProjectFundFieldsResolver $resolver,
        ApprovedProjectFundFieldAudit $fundFieldAudit
    ) {
        $this->resolver = $resolver;
        $this->fundFieldAudit = $fundFieldAudit;
    }

    /**
     * Fields allowed to be written on type budget save (Step 2A).
     * Do NOT write amount_sanctioned or opening_balance here.
     */
    protected const TYPE_SAVE_FIELDS = [
        'overall_project_budget',
        'local_contribution',
        'amount_forwarded',
    ];

    /**
     * All five fund fields written before approval (Step 2B).
     * M3.7 Phase 1: amount_sanctioned is NOT written for non-approved projects; only approval flow persists it.
     */
    protected const PRE_APPROVAL_FIELDS = [
        'overall_project_budget',
        'amount_forwarded',
        'local_contribution',
        'amount_sanctioned',
        'opening_balance',
    ];

    /**
     * Fields written in pre-approval sync when project is NOT approved (M3.7).
     * Excludes amount_sanctioned — draft update never writes sanctioned.
     */
    protected const PRE_APPROVAL_FIELDS_WITHOUT_SANCTIONED = [
        'overall_project_budget',
        'amount_forwarded',
        'local_contribution',
        'opening_balance',
    ];

    /**
     * Sync to projects after type-specific budget save (Step 2A).
     * Updates ONLY overall_project_budget, local_contribution, amount_forwarded.
     * Idempotent; guarded by feature flag and "project not approved".
     *
     * @param Project $project Must be loaded with any relations needed by resolver (e.g. budgets, iiesExpenses).
     * @return bool True if sync was performed, false if guarded or skipped.
     */
    public function syncFromTypeSave(Project $project): bool
    {
        if (!BudgetSyncGuard::canSyncOnTypeSave($project)) {
            BudgetAuditLogger::logGuardRejection(
                $project->project_id ?? $project->id,
                'sync_on_type_save: guard blocked (approved or flags off)'
            );
            return false;
        }

        $resolved = $this->resolver->resolve($project, false);
        $oldValues = $this->getStoredValues($project);

        $newValues = array_intersect_key($resolved, array_flip(self::TYPE_SAVE_FIELDS));
        $updatePayload = [];
        foreach (self::TYPE_SAVE_FIELDS as $key) {
            $updatePayload[$key] = $newValues[$key] ?? $oldValues[$key];
        }

        $project->update($updatePayload);

        BudgetAuditLogger::logSync(
            $project->project_id ?? $project->id,
            'budget_save',
            $oldValues,
            $this->getStoredValues($project->fresh()),
            $project->project_type ?? ''
        );

        return true;
    }

    /**
     * Sync to projects immediately before approval (Step 2B).
     * M3.7 Phase 1: If project is NOT approved, do NOT update amount_sanctioned — only approval flow writes it.
     * Idempotent; guarded by feature flag and status forwarded_to_coordinator.
     *
     * @param Project $project Must be loaded with relations needed by resolver.
     * @return bool True if sync was performed, false if guarded or skipped.
     */
    public function syncBeforeApproval(Project $project): bool
    {
        if (!BudgetSyncGuard::canSyncBeforeApproval($project)) {
            BudgetAuditLogger::logGuardRejection(
                $project->project_id ?? $project->id,
                'pre_approval: guard blocked (status not forwarded_to_coordinator or flags off)'
            );
            return false;
        }

        $resolved = $this->resolver->resolve($project, false);
        $oldValues = $this->getStoredValues($project);

        // M3.7: Never write amount_sanctioned for non-approved projects. Draft update never writes sanctioned.
        $fieldsToSync = $project->isApproved()
            ? self::PRE_APPROVAL_FIELDS
            : self::PRE_APPROVAL_FIELDS_WITHOUT_SANCTIONED;

        $updatePayload = [];
        foreach ($fieldsToSync as $key) {
            $updatePayload[$key] = $resolved[$key] ?? $oldValues[$key];
        }

        $project->update($updatePayload);

        BudgetAuditLogger::logSync(
            $project->project_id ?? $project->id,
            'pre_approval',
            $oldValues,
            $this->getStoredValues($project->fresh()),
            $project->project_type ?? ''
        );

        return true;
    }

    /**
     * Repair approved project fund fields from type-specific source tables (Phase 4).
     * Explicit CLI/admin action — not gated by sync feature flags.
     *
     * @param Project $project Must have relations needed by resolver loaded or loadable.
     * @param string $trigger Audit log trigger label (e.g. cli_repair, admin_accept_suggested)
     * @return bool True when an update was performed
     */
    public function repairApprovedProject(Project $project, string $trigger = 'approved_repair'): bool
    {
        if (!$project->isApproved()) {
            BudgetAuditLogger::logGuardRejection(
                $project->project_id ?? $project->id,
                'approved_repair: project is not approved'
            );
            return false;
        }

        $financialResolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
        $resolved = $financialResolver->resolveTypeDerivedFundFields($project);
        $oldValues = $this->getStoredValues($project);

        if (!$this->fundFieldAudit->needsRepair($oldValues, $resolved)) {
            return false;
        }

        if (($resolved['amount_sanctioned'] ?? 0) <= ApprovedProjectFundFieldAudit::TOLERANCE) {
            BudgetAuditLogger::logGuardRejection(
                $project->project_id ?? $project->id,
                'approved_repair: type-derived amount_sanctioned is zero — skipped'
            );
            return false;
        }

        $updatePayload = [];
        foreach (ApprovedProjectFundFieldAudit::FUND_KEYS as $key) {
            $updatePayload[$key] = $resolved[$key] ?? $oldValues[$key];
        }

        $project->update($updatePayload);

        BudgetAuditLogger::logSync(
            $project->project_id ?? $project->id,
            $trigger,
            $oldValues,
            $this->getStoredValues($project->fresh()),
            $project->project_type ?? ''
        );

        return true;
    }

    /**
     * Get current stored fund values from project for logging.
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
}
