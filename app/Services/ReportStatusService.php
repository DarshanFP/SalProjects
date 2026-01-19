<?php

namespace App\Services;

use App\Models\ActivityHistory;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReportStatusService
{
    /**
     * Submit report to provincial
     *
     * @param DPReport $report
     * @param User $user
     * @return bool
     */
    public static function submitToProvincial(DPReport $report, User $user): bool
    {
        $allowedStatuses = [
            DPReport::STATUS_DRAFT,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            DPReport::STATUS_REVERTED_TO_EXECUTOR,
            DPReport::STATUS_REVERTED_TO_APPLICANT,
            DPReport::STATUS_REVERTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_TO_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be submitted in current status: ' . $report->status);
        }

        $previousStatus = $report->status;
        $report->status = DPReport::STATUS_SUBMITTED_TO_PROVINCIAL;
        $saved = $report->save();

        if ($saved) {
            // Log status change
            self::logStatusChange($report, $previousStatus, DPReport::STATUS_SUBMITTED_TO_PROVINCIAL, $user);

            Log::info('Report submitted to provincial', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);
        }

        return $saved;
    }

    /**
     * Forward report to coordinator
     *
     * @param DPReport $report
     * @param User $user
     * @return bool
     */
    public static function forwardToCoordinator(DPReport $report, User $user): bool
    {
        if (!in_array($user->role, ['provincial', 'general'])) {
            throw new \Exception('Only provincial or general users can forward reports to coordinator.');
        }

        $allowedStatuses = [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            DPReport::STATUS_REVERTED_TO_PROVINCIAL,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be forwarded in current status: ' . $report->status);
        }

        $previousStatus = $report->status;
        $report->status = DPReport::STATUS_FORWARDED_TO_COORDINATOR;
        $saved = $report->save();

        if ($saved) {
            // Log status change with approval context for General user
            $approvalContext = ($user->role === 'general') ? 'provincial' : null;
            self::logStatusChange($report, $previousStatus, DPReport::STATUS_FORWARDED_TO_COORDINATOR, $user, null, $approvalContext);

            Log::info('Report forwarded to coordinator', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Approve report by provincial (alias for forwardToCoordinator)
     * This is the provincial-level approval which forwards the report to coordinator
     *
     * @param DPReport $report
     * @param User $user
     * @return bool
     */
    public static function approveByProvincial(DPReport $report, User $user): bool
    {
        return self::forwardToCoordinator($report, $user);
    }

    /**
     * Approve report
     *
     * @param DPReport $report
     * @param User $user
     * @return bool
     */
    public static function approve(DPReport $report, User $user): bool
    {
        // Allow both coordinator and general roles
        if (!in_array($user->role, ['coordinator', 'general'])) {
            throw new \Exception('Only coordinator or general users can approve reports.');
        }

        $allowedStatuses = [
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_TO_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report must be forwarded to coordinator before approval.');
        }

        // If General user, use specific status; otherwise use standard coordinator approval
        $newStatus = ($user->role === 'general')
            ? DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR
            : DPReport::STATUS_APPROVED_BY_COORDINATOR;

        $previousStatus = $report->status;
        $report->status = $newStatus;
        $saved = $report->save();

        if ($saved) {
            // Log status change with approval context for General user
            $approvalContext = ($user->role === 'general') ? 'coordinator' : null;
            self::logStatusChange($report, $previousStatus, $newStatus, $user, null, $approvalContext);

            Log::info('Report approved', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'new_status' => $newStatus,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Revert report by provincial
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'executor', 'applicant', etc.
     * @param int|null $revertedToUserId User ID to whom report is reverted
     * @return bool
     */
    public static function revertByProvincial(DPReport $report, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if (!in_array($user->role, ['provincial', 'general'])) {
            throw new \Exception('Only provincial or general users can revert reports as provincial.');
        }

        $allowedStatuses = [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_FORWARDED_TO_COORDINATOR, // Can revert even if forwarded (General has full access)
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be reverted by provincial in current status: ' . $report->status);
        }

        // Determine new status based on user role and revert level
        if ($user->role === 'general' && $revertLevel) {
            // Use granular revert status based on level
            $newStatus = match($revertLevel) {
                'executor' => DPReport::STATUS_REVERTED_TO_EXECUTOR,
                'applicant' => DPReport::STATUS_REVERTED_TO_APPLICANT,
                'provincial' => DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                default => DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
            };
        } elseif ($user->role === 'general') {
            $newStatus = DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL;
        } else {
            $newStatus = DPReport::STATUS_REVERTED_BY_PROVINCIAL;
        }

        $previousStatus = $report->status;
        $report->status = $newStatus;
        $report->revert_reason = $reason;
        $saved = $report->save();

        if ($saved) {
            // Log status change with approval context and revert level
            $approvalContext = ($user->role === 'general') ? 'provincial' : null;
            self::logStatusChange($report, $previousStatus, $newStatus, $user, $reason, $approvalContext, $revertLevel, $revertedToUserId);

            Log::info('Report reverted by provincial', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'new_status' => $newStatus,
                'reason' => $reason,
                'revert_level' => $revertLevel,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Revert report by coordinator
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'provincial', 'coordinator', etc.
     * @param int|null $revertedToUserId User ID to whom report is reverted
     * @return bool
     */
    public static function revertByCoordinator(DPReport $report, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if (!in_array($user->role, ['coordinator', 'general'])) {
            throw new \Exception('Only coordinator or general users can revert reports as coordinator.');
        }

        $allowedStatuses = [
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_APPROVED_BY_COORDINATOR, // Can revert even approved reports (General has full access)
            DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be reverted by coordinator in current status: ' . $report->status);
        }

        // Determine new status based on user role and revert level
        if ($user->role === 'general' && $revertLevel) {
            // Use granular revert status based on level
            $newStatus = match($revertLevel) {
                'provincial' => DPReport::STATUS_REVERTED_TO_PROVINCIAL,
                'coordinator' => DPReport::STATUS_REVERTED_TO_COORDINATOR,
                default => DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            };
        } elseif ($user->role === 'general') {
            $newStatus = DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR;
        } else {
            $newStatus = DPReport::STATUS_REVERTED_BY_COORDINATOR;
        }

        $previousStatus = $report->status;
        $report->status = $newStatus;
        $report->revert_reason = $reason;
        $saved = $report->save();

        if ($saved) {
            // Log status change with approval context and revert level
            $approvalContext = ($user->role === 'general') ? 'coordinator' : null;
            self::logStatusChange($report, $previousStatus, $newStatus, $user, $reason, $approvalContext, $revertLevel, $revertedToUserId);

            Log::info('Report reverted by coordinator', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'new_status' => $newStatus,
                'reason' => $reason,
                'revert_level' => $revertLevel,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Reject report by coordinator
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $reason
     * @return bool
     */
    public static function reject(DPReport $report, User $user, ?string $reason = null): bool
    {
        if (!in_array($user->role, ['coordinator', 'general'])) {
            throw new \Exception('Only coordinator or general users can reject reports.');
        }

        $allowedStatuses = [
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_TO_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report must be forwarded to coordinator before rejection.');
        }

        $previousStatus = $report->status;
        $report->status = DPReport::STATUS_REJECTED_BY_COORDINATOR;
        $report->revert_reason = $reason;
        $saved = $report->save();

        if ($saved) {
            // Log status change with approval context for General user
            $approvalContext = ($user->role === 'general') ? 'coordinator' : null;
            self::logStatusChange($report, $previousStatus, DPReport::STATUS_REJECTED_BY_COORDINATOR, $user, $reason, $approvalContext);

            Log::info('Report rejected by coordinator', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'reason' => $reason,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Log status change to activity history table
     *
     * @param DPReport $report
     * @param string|null $previousStatus Previous status (null for new reports)
     * @param string $newStatus
     * @param User $user
     * @param string|null $notes
     * @param string|null $approvalContext 'coordinator', 'provincial', 'general' (for General user dual-role actions)
     * @param string|null $revertLevel 'executor', 'applicant', 'provincial', 'coordinator' (for granular reverts)
     * @param int|null $revertedToUserId User ID to whom report was reverted (optional)
     * @return void
     */
    public static function logStatusChange(
        DPReport $report,
        ?string $previousStatus,
        string $newStatus,
        User $user,
        ?string $notes = null,
        ?string $approvalContext = null,
        ?string $revertLevel = null,
        ?int $revertedToUserId = null
    ): void {
        try {
            ActivityHistory::create([
                'type' => 'report',
                'related_id' => $report->report_id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'action_type' => 'status_change',
                'changed_by_user_id' => $user->id,
                'changed_by_user_role' => $user->role,
                'changed_by_user_name' => $user->name,
                'reverted_to_user_id' => $revertedToUserId,
                'notes' => $notes,
                'approval_context' => $approvalContext,
                'revert_level' => $revertLevel,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the status change
            Log::error('Failed to log report status change', [
                'report_id' => $report->report_id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * General user approves report as Coordinator (explicit context selection)
     *
     * @param DPReport $report
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public static function approveAsCoordinator(DPReport $report, User $user): bool
    {
        if ($user->role !== 'general') {
            throw new \Exception('Only general users can explicitly approve as coordinator.');
        }

        $allowedStatuses = [
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            DPReport::STATUS_REVERTED_TO_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be approved as coordinator in current status: ' . $report->status);
        }

        $previousStatus = $report->status;
        $report->status = DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR;
        $saved = $report->save();

        if ($saved) {
            self::logStatusChange($report, $previousStatus, DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR, $user, null, 'coordinator');

            Log::info('Report approved by General as Coordinator', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
            ]);
        }

        return $saved;
    }

    /**
     * General user forwards/approves report as Provincial (explicit context selection)
     * This forwards the report to coordinator level (provincial approval)
     *
     * @param DPReport $report
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public static function approveAsProvincial(DPReport $report, User $user): bool
    {
        if ($user->role !== 'general') {
            throw new \Exception('Only general users can explicitly approve as provincial.');
        }

        $allowedStatuses = [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_PROVINCIAL,
            DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
            DPReport::STATUS_REVERTED_TO_EXECUTOR,
            DPReport::STATUS_REVERTED_TO_APPLICANT,
            DPReport::STATUS_REVERTED_TO_PROVINCIAL,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be approved as provincial in current status: ' . $report->status);
        }

        $previousStatus = $report->status;
        // When General acts as Provincial, they forward to coordinator
        $report->status = DPReport::STATUS_FORWARDED_TO_COORDINATOR;
        $saved = $report->save();

        if ($saved) {
            self::logStatusChange($report, $previousStatus, DPReport::STATUS_FORWARDED_TO_COORDINATOR, $user, null, 'provincial');

            Log::info('Report approved/forwarded by General as Provincial', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
            ]);
        }

        return $saved;
    }

    /**
     * General user reverts report as Coordinator (explicit context selection)
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'provincial', 'coordinator'
     * @param int|null $revertedToUserId User ID to whom report is reverted
     * @return bool
     * @throws \Exception
     */
    public static function revertAsCoordinator(DPReport $report, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if ($user->role !== 'general') {
            throw new \Exception('Only general users can explicitly revert as coordinator.');
        }

        $allowedStatuses = [
            DPReport::STATUS_FORWARDED_TO_COORDINATOR,
            DPReport::STATUS_APPROVED_BY_COORDINATOR,
            DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be reverted as coordinator in current status: ' . $report->status);
        }

        // Determine new status based on revert level
        $newStatus = match($revertLevel) {
            'provincial' => DPReport::STATUS_REVERTED_TO_PROVINCIAL,
            'coordinator' => DPReport::STATUS_REVERTED_TO_COORDINATOR,
            default => DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
        };

        $previousStatus = $report->status;
        $report->status = $newStatus;
        $report->revert_reason = $reason;
        $saved = $report->save();

        if ($saved) {
            self::logStatusChange($report, $previousStatus, $newStatus, $user, $reason, 'coordinator', $revertLevel, $revertedToUserId);

            Log::info('Report reverted by General as Coordinator', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'new_status' => $newStatus,
                'reason' => $reason,
                'revert_level' => $revertLevel,
            ]);
        }

        return $saved;
    }

    /**
     * General user reverts report as Provincial (explicit context selection)
     *
     * @param DPReport $report
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'executor', 'applicant', 'provincial'
     * @param int|null $revertedToUserId User ID to whom report is reverted
     * @return bool
     * @throws \Exception
     */
    public static function revertAsProvincial(DPReport $report, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if ($user->role !== 'general') {
            throw new \Exception('Only general users can explicitly revert as provincial.');
        }

        $allowedStatuses = [
            DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
            DPReport::STATUS_FORWARDED_TO_COORDINATOR, // General can revert even forwarded reports
            DPReport::STATUS_REVERTED_BY_COORDINATOR,
        ];

        if (!in_array($report->status, $allowedStatuses)) {
            throw new \Exception('Report cannot be reverted as provincial in current status: ' . $report->status);
        }

        // Determine new status based on revert level
        $newStatus = match($revertLevel) {
            'executor' => DPReport::STATUS_REVERTED_TO_EXECUTOR,
            'applicant' => DPReport::STATUS_REVERTED_TO_APPLICANT,
            'provincial' => DPReport::STATUS_REVERTED_TO_PROVINCIAL,
            default => DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
        };

        $previousStatus = $report->status;
        $report->status = $newStatus;
        $report->revert_reason = $reason;
        $saved = $report->save();

        if ($saved) {
            self::logStatusChange($report, $previousStatus, $newStatus, $user, $reason, 'provincial', $revertLevel, $revertedToUserId);

            Log::info('Report reverted by General as Provincial', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'new_status' => $newStatus,
                'reason' => $reason,
                'revert_level' => $revertLevel,
            ]);
        }

        return $saved;
    }

    /**
     * General user reverts report to a specific level (granular revert)
     *
     * @param DPReport $report
     * @param User $user
     * @param string $revertLevel 'executor', 'applicant', 'provincial', 'coordinator'
     * @param string|null $reason
     * @param int|null $revertedToUserId User ID to whom report is reverted
     * @return bool
     * @throws \Exception
     */
    public static function revertToLevel(DPReport $report, User $user, string $revertLevel, ?string $reason = null, ?int $revertedToUserId = null): bool
    {
        if ($user->role !== 'general') {
            throw new \Exception('Only general users can revert reports to specific levels.');
        }

        // Validate revert level
        $allowedLevels = ['executor', 'applicant', 'provincial', 'coordinator'];
        if (!in_array($revertLevel, $allowedLevels)) {
            throw new \Exception('Invalid revert level: ' . $revertLevel);
        }

        // Determine current status requirements based on revert level
        $currentStatus = $report->status;

        // Executor/Applicant revert requires report to be at provincial level or below
        if (in_array($revertLevel, ['executor', 'applicant'])) {
            $allowedStatuses = [
                DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                DPReport::STATUS_FORWARDED_TO_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
            ];

            if (!in_array($currentStatus, $allowedStatuses)) {
                throw new \Exception('Report cannot be reverted to ' . $revertLevel . ' in current status: ' . $currentStatus);
            }
        }

        // Provincial revert requires report to be at coordinator level
        if ($revertLevel === 'provincial') {
            $allowedStatuses = [
                DPReport::STATUS_FORWARDED_TO_COORDINATOR,
                DPReport::STATUS_APPROVED_BY_COORDINATOR,
                DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_COORDINATOR,
                DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
            ];

            if (!in_array($currentStatus, $allowedStatuses)) {
                throw new \Exception('Report cannot be reverted to provincial in current status: ' . $currentStatus);
            }
        }

        // Coordinator revert requires report to be approved (rare, but possible)
        if ($revertLevel === 'coordinator') {
            $allowedStatuses = [
                DPReport::STATUS_APPROVED_BY_COORDINATOR,
                DPReport::STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR,
            ];

            if (!in_array($currentStatus, $allowedStatuses)) {
                throw new \Exception('Report cannot be reverted to coordinator in current status: ' . $currentStatus);
            }
        }

        // Determine new status based on revert level
        $newStatus = match($revertLevel) {
            'executor' => DPReport::STATUS_REVERTED_TO_EXECUTOR,
            'applicant' => DPReport::STATUS_REVERTED_TO_APPLICANT,
            'provincial' => DPReport::STATUS_REVERTED_TO_PROVINCIAL,
            'coordinator' => DPReport::STATUS_REVERTED_TO_COORDINATOR,
            default => throw new \Exception('Invalid revert level'),
        };

        $previousStatus = $report->status;
        $report->status = $newStatus;
        $report->revert_reason = $reason;
        $saved = $report->save();

        if ($saved) {
            // Determine approval context based on revert level
            $approvalContext = in_array($revertLevel, ['executor', 'applicant', 'provincial']) ? 'provincial' : 'coordinator';

            self::logStatusChange($report, $previousStatus, $newStatus, $user, $reason, $approvalContext, $revertLevel, $revertedToUserId);

            Log::info('Report reverted to specific level by General', [
                'report_id' => $report->report_id,
                'user_id' => $user->id,
                'revert_level' => $revertLevel,
                'new_status' => $newStatus,
                'reason' => $reason,
                'reverted_to_user_id' => $revertedToUserId,
            ]);
        }

        return $saved;
    }
}
