<?php

namespace App\Services\Budget;

use Illuminate\Support\Facades\Log;

/**
 * Budget Audit Logger (Phase 0)
 *
 * Logging hooks for budget alignment. NO DB writes.
 * Used by Phase 1+ for resolver calls, sync events, and discrepancy logging.
 *
 * All logs go to the 'budget' channel (config/logging.php).
 */
class BudgetAuditLogger
{
    protected static function channel()
    {
        return Log::channel('budget');
    }

    /**
     * Log resolver call (Phase 1).
     *
     * @param int|string $projectId
     * @param string $projectType
     * @param array $resolvedValues
     * @param bool $dryRun
     */
    public static function logResolverCall($projectId, string $projectType, array $resolvedValues, bool $dryRun = true): void
    {
        self::channel()->info('Budget resolver called', [
            'project_id' => $projectId,
            'project_type' => $projectType,
            'resolved_values' => $resolvedValues,
            'dry_run' => $dryRun,
        ]);
    }

    /**
     * Log discrepancy between resolved and stored values (Phase 1).
     *
     * @param int|string $projectId
     * @param string $projectType
     * @param array $resolved
     * @param array $stored
     */
    public static function logDiscrepancy($projectId, string $projectType, array $resolved, array $stored): void
    {
        self::channel()->info('Budget discrepancy detected', [
            'project_id' => $projectId,
            'project_type' => $projectType,
            'resolved' => $resolved,
            'stored' => $stored,
        ]);
    }

    /**
     * Log sync event (Phase 2).
     *
     * @param int|string $projectId
     * @param string $trigger e.g. 'budget_save', 'pre_approval'
     * @param array $oldValues
     * @param array $newValues
     * @param string $projectType Optional; included for full reconstruction.
     */
    public static function logSync($projectId, string $trigger, array $oldValues, array $newValues, string $projectType = ''): void
    {
        self::channel()->info('Budget sync applied', [
            'project_id' => $projectId,
            'project_type' => $projectType,
            'trigger' => $trigger,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log guard rejection (Phase 2 - when sync is blocked).
     *
     * @param int|string $projectId
     * @param string $reason
     */
    public static function logGuardRejection($projectId, string $reason): void
    {
        self::channel()->info('Budget sync blocked by guard', [
            'project_id' => $projectId,
            'reason' => $reason,
        ]);
    }

    /**
     * Log blocked budget edit attempt (Phase 3 - post-approval enforcement).
     *
     * @param int|string $projectId
     * @param int|null $userId
     * @param string $attemptedAction
     * @param string $status
     */
    public static function logBlockedEditAttempt($projectId, $userId, string $attemptedAction, string $status): void
    {
        self::channel()->info('Budget edit blocked (project approved)', [
            'project_id' => $projectId,
            'user_id' => $userId,
            'attempted_action' => $attemptedAction,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log report vs project budget discrepancy (Phase 4 – read-only visibility).
     * Does NOT mutate data; used for visibility and audit.
     *
     * @param string $reportId
     * @param string $projectId
     * @param float $reportSanctioned Report-stored amount_sanctioned_overview
     * @param float $projectSanctioned Canonical projects.amount_sanctioned
     */
    public static function logReportProjectDiscrepancy(
        string $reportId,
        string $projectId,
        float $reportSanctioned,
        float $projectSanctioned
    ): void {
        self::channel()->info('Phase 4: Report vs project sanctioned discrepancy (display only)', [
            'report_id' => $reportId,
            'project_id' => $projectId,
            'report_amount_sanctioned_overview' => $reportSanctioned,
            'project_amount_sanctioned' => $projectSanctioned,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
