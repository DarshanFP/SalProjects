<?php

namespace App\Services;

use App\Models\ActivityHistory;
use App\Models\OldProjects\Project;
use App\Models\ProjectStatusHistory;
use App\Models\User;
use App\Constants\ProjectStatus;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Support\Facades\Log;
use Exception;

class ProjectStatusService
{
    /**
     * Submit project to provincial
     *
     * @param Project $project
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public static function submitToProvincial(Project $project, User $user): bool
    {
        if (!ProjectPermissionHelper::canSubmit($project, $user)) {
            throw new Exception('User does not have permission to submit this project.');
        }

        if (!ProjectStatus::isSubmittable($project->status)) {
            throw new Exception('Project cannot be submitted in current status: ' . $project->status);
        }

        $from = $project->status;
        $to = ProjectStatus::SUBMITTED_TO_PROVINCIAL;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            // Log status change
            self::logStatusChange($project, $previousStatus, $to, $user);

            Log::info('Project submitted to provincial', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);
        }

        return $saved;
    }

    /**
     * Forward project to coordinator
     *
     * @param Project $project
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public static function forwardToCoordinator(Project $project, User $user): bool
    {
        if (!in_array($user->role, ['provincial', 'general'])) {
            throw new Exception('Only provincial or general users can forward projects to coordinator.');
        }

        $allowedStatuses = [
            ProjectStatus::SUBMITTED_TO_PROVINCIAL,
            ProjectStatus::REVERTED_BY_COORDINATOR,
            ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
            ProjectStatus::REVERTED_TO_PROVINCIAL,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be forwarded in current status: ' . $project->status);
        }

        $from = $project->status;
        $to = ProjectStatus::FORWARDED_TO_COORDINATOR;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            // Log status change with approval context for General user
            $approvalContext = ($user->role === 'general') ? 'provincial' : null;
            self::logStatusChange($project, $previousStatus, $to, $user, null, $approvalContext);

            Log::info('Project forwarded to coordinator', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Approve project
     *
     * @param Project $project
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public static function approve(Project $project, User $user): bool
    {
        // Allow both coordinator and general roles
        if (!in_array($user->role, ['coordinator', 'general'])) {
            throw new Exception('Only coordinator or general users can approve projects.');
        }

        $allowedStatuses = [
            ProjectStatus::FORWARDED_TO_COORDINATOR,
            ProjectStatus::REVERTED_BY_COORDINATOR,
            ProjectStatus::REVERTED_TO_COORDINATOR,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project must be forwarded to coordinator before approval.');
        }

        // If General user, use specific status; otherwise use standard coordinator approval
        $newStatus = ($user->role === 'general')
            ? ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR
            : ProjectStatus::APPROVED_BY_COORDINATOR;

        $from = $project->status;
        $to = $newStatus;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            // Log status change with approval context for General user
            $approvalContext = ($user->role === 'general') ? 'coordinator' : null;
            self::logStatusChange($project, $previousStatus, $to, $user, null, $approvalContext);

            Log::info('Project approved', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'user_role' => $user->role,
                'new_status' => $newStatus,
                'approval_context' => $approvalContext,
            ]);
        }

        return $saved;
    }

    /**
     * Reject project (coordinator only). M4.3: centralizes reject transition.
     * Does NOT modify financial fields.
     *
     * @param Project $project
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public static function reject(Project $project, User $user): bool
    {
        if ($user->role !== 'coordinator') {
            throw new Exception('Only coordinator can reject projects.');
        }

        if (!ProjectStatus::isForwardedToCoordinator($project->status)) {
            throw new Exception('Project can only be rejected when forwarded to coordinator. Current status: ' . $project->status);
        }

        $from = $project->status;
        $to = ProjectStatus::REJECTED_BY_COORDINATOR;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            self::logStatusChange($project, $previousStatus, $to, $user);

            Log::info('Project rejected by coordinator', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
            ]);
        }

        return $saved;
    }

    /**
     * Enforce canonical financial state when reverting from approved to non-approved.
     * Only resets if current status is approved (idempotent for already reverted).
     * M4.2: amount_sanctioned = 0, opening_balance = amount_forwarded + local_contribution.
     *
     * @param Project $project
     * @return void
     */
    private static function applyFinancialResetOnRevert(Project $project): void
    {
        if (!ProjectStatus::isApproved($project->status)) {
            return;
        }
        $project->amount_sanctioned = 0;
        $project->opening_balance = (float) ($project->amount_forwarded ?? 0) + (float) ($project->local_contribution ?? 0);
    }

    /**
     * Revert project by provincial
     *
     * @param Project $project
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'executor', 'applicant', etc.
     * @param int|null $revertedToUserId User ID to whom project is reverted
     * @return bool
     * @throws Exception
     */
    public static function revertByProvincial(Project $project, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if (!in_array($user->role, ['provincial', 'general'])) {
            throw new Exception('Only provincial or general users can revert projects as provincial.');
        }

        $allowedStatuses = [
            ProjectStatus::SUBMITTED_TO_PROVINCIAL,
            ProjectStatus::FORWARDED_TO_COORDINATOR, // Can revert even if forwarded (General has full access)
            ProjectStatus::REVERTED_BY_COORDINATOR,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be reverted by provincial in current status: ' . $project->status);
        }

        // Determine new status based on user role and revert level
        if ($user->role === 'general' && $revertLevel) {
            // Use granular revert status based on level
            $newStatus = match($revertLevel) {
                'executor' => ProjectStatus::REVERTED_TO_EXECUTOR,
                'applicant' => ProjectStatus::REVERTED_TO_APPLICANT,
                'provincial' => ProjectStatus::REVERTED_TO_PROVINCIAL,
                default => ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            };
        } elseif ($user->role === 'general') {
            $newStatus = ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL;
        } else {
            $newStatus = ProjectStatus::REVERTED_BY_PROVINCIAL;
        }

        $from = $project->status;
        $to = $newStatus;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        self::applyFinancialResetOnRevert($project);
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            // Log status change with approval context and revert level
            $approvalContext = ($user->role === 'general') ? 'provincial' : null;
            self::logStatusChange($project, $previousStatus, $to, $user, $reason, $approvalContext, $revertLevel, $revertedToUserId);

            Log::info('Project reverted by provincial', [
                'project_id' => $project->project_id,
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
     * Revert project by coordinator
     *
     * @param Project $project
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'provincial', 'coordinator', etc.
     * @param int|null $revertedToUserId User ID to whom project is reverted
     * @return bool
     * @throws Exception
     */
    public static function revertByCoordinator(Project $project, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if (!in_array($user->role, ['coordinator', 'general'])) {
            throw new Exception('Only coordinator or general users can revert projects as coordinator.');
        }

        $allowedStatuses = [
            ProjectStatus::FORWARDED_TO_COORDINATOR,
            ProjectStatus::APPROVED_BY_COORDINATOR, // Can revert even approved projects (General has full access)
            ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be reverted by coordinator in current status: ' . $project->status);
        }

        // Determine new status based on user role and revert level
        if ($user->role === 'general' && $revertLevel) {
            // Use granular revert status based on level
            $newStatus = match($revertLevel) {
                'provincial' => ProjectStatus::REVERTED_TO_PROVINCIAL,
                'coordinator' => ProjectStatus::REVERTED_TO_COORDINATOR,
                default => ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
            };
        } elseif ($user->role === 'general') {
            $newStatus = ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR;
        } else {
            $newStatus = ProjectStatus::REVERTED_BY_COORDINATOR;
        }

        $from = $project->status;
        $to = $newStatus;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        self::applyFinancialResetOnRevert($project);
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            // Log status change with approval context and revert level
            $approvalContext = ($user->role === 'general') ? 'coordinator' : null;
            self::logStatusChange($project, $previousStatus, $to, $user, $reason, $approvalContext, $revertLevel, $revertedToUserId);

            Log::info('Project reverted by coordinator', [
                'project_id' => $project->project_id,
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
     * General user approves project as Coordinator (explicit context selection)
     *
     * @param Project $project
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public static function approveAsCoordinator(Project $project, User $user): bool
    {
        if ($user->role !== 'general') {
            throw new Exception('Only general users can explicitly approve as coordinator.');
        }

        $allowedStatuses = [
            ProjectStatus::FORWARDED_TO_COORDINATOR,
            ProjectStatus::REVERTED_BY_COORDINATOR,
            ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
            ProjectStatus::REVERTED_TO_COORDINATOR,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be approved as coordinator in current status: ' . $project->status);
        }

        $from = $project->status;
        $to = ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            self::logStatusChange($project, $previousStatus, $to, $user, null, 'coordinator');

            Log::info('Project approved by General as Coordinator', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
            ]);
        }

        return $saved;
    }

    /**
     * General user forwards/approves project as Provincial (explicit context selection)
     * This forwards the project to coordinator level (provincial approval)
     *
     * @param Project $project
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public static function approveAsProvincial(Project $project, User $user): bool
    {
        if ($user->role !== 'general') {
            throw new Exception('Only general users can explicitly approve as provincial.');
        }

        $allowedStatuses = [
            ProjectStatus::SUBMITTED_TO_PROVINCIAL,
            ProjectStatus::REVERTED_BY_PROVINCIAL,
            ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            ProjectStatus::REVERTED_TO_EXECUTOR,
            ProjectStatus::REVERTED_TO_APPLICANT,
            ProjectStatus::REVERTED_TO_PROVINCIAL,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be approved as provincial in current status: ' . $project->status);
        }

        $from = $project->status;
        $to = ProjectStatus::FORWARDED_TO_COORDINATOR;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        // When General acts as Provincial, they forward to coordinator
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            self::logStatusChange($project, $previousStatus, $to, $user, null, 'provincial');

            Log::info('Project approved/forwarded by General as Provincial', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
            ]);
        }

        return $saved;
    }

    /**
     * General user reverts project as Coordinator (explicit context selection)
     *
     * @param Project $project
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'provincial', 'coordinator'
     * @param int|null $revertedToUserId User ID to whom project is reverted
     * @return bool
     * @throws Exception
     */
    public static function revertAsCoordinator(Project $project, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if ($user->role !== 'general') {
            throw new Exception('Only general users can explicitly revert as coordinator.');
        }

        $allowedStatuses = [
            ProjectStatus::FORWARDED_TO_COORDINATOR,
            ProjectStatus::APPROVED_BY_COORDINATOR,
            ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be reverted as coordinator in current status: ' . $project->status);
        }

        // Determine new status based on revert level
        $newStatus = match($revertLevel) {
            'provincial' => ProjectStatus::REVERTED_TO_PROVINCIAL,
            'coordinator' => ProjectStatus::REVERTED_TO_COORDINATOR,
            default => ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
        };

        $from = $project->status;
        $to = $newStatus;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        self::applyFinancialResetOnRevert($project);
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            self::logStatusChange($project, $previousStatus, $to, $user, $reason, 'coordinator', $revertLevel, $revertedToUserId);

            Log::info('Project reverted by General as Coordinator', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'new_status' => $newStatus,
                'reason' => $reason,
                'revert_level' => $revertLevel,
            ]);
        }

        return $saved;
    }

    /**
     * General user reverts project as Provincial (explicit context selection)
     *
     * @param Project $project
     * @param User $user
     * @param string|null $reason
     * @param string|null $revertLevel 'executor', 'applicant', 'provincial'
     * @param int|null $revertedToUserId User ID to whom project is reverted
     * @return bool
     * @throws Exception
     */
    public static function revertAsProvincial(Project $project, User $user, ?string $reason = null, ?string $revertLevel = null, ?int $revertedToUserId = null): bool
    {
        if ($user->role !== 'general') {
            throw new Exception('Only general users can explicitly revert as provincial.');
        }

        $allowedStatuses = [
            ProjectStatus::SUBMITTED_TO_PROVINCIAL,
            ProjectStatus::FORWARDED_TO_COORDINATOR, // General can revert even forwarded projects
            ProjectStatus::REVERTED_BY_COORDINATOR,
        ];

        if (!in_array($project->status, $allowedStatuses)) {
            throw new Exception('Project cannot be reverted as provincial in current status: ' . $project->status);
        }

        // Determine new status based on revert level
        $newStatus = match($revertLevel) {
            'executor' => ProjectStatus::REVERTED_TO_EXECUTOR,
            'applicant' => ProjectStatus::REVERTED_TO_APPLICANT,
            'provincial' => ProjectStatus::REVERTED_TO_PROVINCIAL,
            default => ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
        };

        $from = $project->status;
        $to = $newStatus;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        self::applyFinancialResetOnRevert($project);
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            self::logStatusChange($project, $previousStatus, $to, $user, $reason, 'provincial', $revertLevel, $revertedToUserId);

            Log::info('Project reverted by General as Provincial', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'new_status' => $newStatus,
                'reason' => $reason,
                'revert_level' => $revertLevel,
            ]);
        }

        return $saved;
    }

    /**
     * General user reverts project to a specific level (granular revert)
     *
     * @param Project $project
     * @param User $user
     * @param string $revertLevel 'executor', 'applicant', 'provincial', 'coordinator'
     * @param string|null $reason
     * @param int|null $revertedToUserId User ID to whom project is reverted
     * @return bool
     * @throws Exception
     */
    public static function revertToLevel(Project $project, User $user, string $revertLevel, ?string $reason = null, ?int $revertedToUserId = null): bool
    {
        if ($user->role !== 'general') {
            throw new Exception('Only general users can revert projects to specific levels.');
        }

        // Validate revert level
        $allowedLevels = ['executor', 'applicant', 'provincial', 'coordinator'];
        if (!in_array($revertLevel, $allowedLevels)) {
            throw new Exception('Invalid revert level: ' . $revertLevel);
        }

        // Determine current status requirements based on revert level
        $currentStatus = $project->status;

        // Executor/Applicant revert requires project to be at provincial level or below
        if (in_array($revertLevel, ['executor', 'applicant'])) {
            $allowedStatuses = [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL,
                ProjectStatus::FORWARDED_TO_COORDINATOR,
                ProjectStatus::REVERTED_BY_PROVINCIAL,
                ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL,
            ];

            if (!in_array($currentStatus, $allowedStatuses)) {
                throw new Exception('Project cannot be reverted to ' . $revertLevel . ' in current status: ' . $currentStatus);
            }
        }

        // Provincial revert requires project to be at coordinator level
        if ($revertLevel === 'provincial') {
            $allowedStatuses = [
                ProjectStatus::FORWARDED_TO_COORDINATOR,
                ProjectStatus::APPROVED_BY_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
                ProjectStatus::REVERTED_BY_COORDINATOR,
                ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR,
            ];

            if (!in_array($currentStatus, $allowedStatuses)) {
                throw new Exception('Project cannot be reverted to provincial in current status: ' . $currentStatus);
            }
        }

        // Coordinator revert requires project to be approved (rare, but possible)
        if ($revertLevel === 'coordinator') {
            $allowedStatuses = [
                ProjectStatus::APPROVED_BY_COORDINATOR,
                ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
            ];

            if (!in_array($currentStatus, $allowedStatuses)) {
                throw new Exception('Project cannot be reverted to coordinator in current status: ' . $currentStatus);
            }
        }

        // Determine new status based on revert level
        $newStatus = match($revertLevel) {
            'executor' => ProjectStatus::REVERTED_TO_EXECUTOR,
            'applicant' => ProjectStatus::REVERTED_TO_APPLICANT,
            'provincial' => ProjectStatus::REVERTED_TO_PROVINCIAL,
            'coordinator' => ProjectStatus::REVERTED_TO_COORDINATOR,
            default => throw new Exception('Invalid revert level'),
        };

        $from = $project->status;
        $to = $newStatus;
        if (!self::canTransition($from, $to, $user->role)) {
            Log::warning('Invalid transition detected (soft)', [
                'project_id' => $project->project_id,
                'from' => $from,
                'to' => $to,
                'user_id' => $user->id,
                'method' => __METHOD__,
            ]);
        }

        $previousStatus = $project->status;
        self::applyFinancialResetOnRevert($project);
        $project->status = $to;
        $saved = $project->save();

        if ($saved) {
            // Determine approval context based on revert level
            $approvalContext = in_array($revertLevel, ['executor', 'applicant', 'provincial']) ? 'provincial' : 'coordinator';

            self::logStatusChange($project, $previousStatus, $to, $user, $reason, $approvalContext, $revertLevel, $revertedToUserId);

            Log::info('Project reverted to specific level by General', [
                'project_id' => $project->project_id,
                'user_id' => $user->id,
                'revert_level' => $revertLevel,
                'new_status' => $newStatus,
                'reason' => $reason,
                'reverted_to_user_id' => $revertedToUserId,
            ]);
        }

        return $saved;
    }

    /**
     * Log status change to history table
     *
     * @param Project $project
     * @param string|null $previousStatus Previous status (null for new projects)
     * @param string $newStatus
     * @param User $user
     * @param string|null $notes
     * @param string|null $approvalContext 'coordinator', 'provincial', 'general' (for General user dual-role actions)
     * @param string|null $revertLevel 'executor', 'applicant', 'provincial', 'coordinator' (for granular reverts)
     * @param int|null $revertedToUserId User ID to whom project was reverted (optional)
     * @return void
     */
    public static function logStatusChange(
        Project $project,
        ?string $previousStatus,
        string $newStatus,
        User $user,
        ?string $notes = null,
        ?string $approvalContext = null,
        ?string $revertLevel = null,
        ?int $revertedToUserId = null
    ): void {
        try {
            // Log to unified activity_histories table
            ActivityHistory::create([
                'type' => 'project',
                'related_id' => $project->project_id,
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

            // Also log to old table for backward compatibility (can be removed after migration)
            try {
                ProjectStatusHistory::create([
                    'project_id' => $project->project_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'changed_by_user_id' => $user->id,
                    'changed_by_user_role' => $user->role,
                    'changed_by_user_name' => $user->name,
                    'notes' => $notes,
                ]);
            } catch (\Exception $e) {
                // Ignore errors in old table (it may not exist after full migration)
            }
        } catch (\Exception $e) {
            // Log error but don't fail the status change
            Log::error('Failed to log status change', [
                'project_id' => $project->project_id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate status transition
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @param string $userRole
     * @return bool
     */
    public static function canTransition(string $currentStatus, string $newStatus, string $userRole): bool
    {
        // M4.5: Map aligned with all actual runtime transitions (audit M4.4). Enforcement still OFF.
        $transitions = [
            ProjectStatus::DRAFT => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
            ],
            ProjectStatus::REVERTED_BY_PROVINCIAL => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['general'], // approveAsProvincial
                ProjectStatus::REVERTED_TO_EXECUTOR => ['general'],    // revertToLevel
                ProjectStatus::REVERTED_TO_APPLICANT => ['general'],
            ],
            ProjectStatus::REVERTED_BY_COORDINATOR => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['provincial', 'general'], // forwardToCoordinator
                ProjectStatus::REVERTED_BY_PROVINCIAL => ['provincial', 'general'],   // revertByProvincial
                ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL => ['general'],
                ProjectStatus::REVERTED_TO_EXECUTOR => ['general'],   // revertAsProvincial / revertToLevel
                ProjectStatus::REVERTED_TO_APPLICANT => ['general'],
                ProjectStatus::REVERTED_TO_PROVINCIAL => ['general'], // revertToLevel
            ],
            ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['general'], // approveAsProvincial
                ProjectStatus::REVERTED_TO_EXECUTOR => ['general'],    // revertToLevel
                ProjectStatus::REVERTED_TO_APPLICANT => ['general'],
            ],
            ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['provincial', 'general'], // forwardToCoordinator
                ProjectStatus::REVERTED_TO_PROVINCIAL => ['general'],                  // revertToLevel
            ],
            ProjectStatus::REVERTED_TO_EXECUTOR => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['general'], // approveAsProvincial
            ],
            ProjectStatus::REVERTED_TO_APPLICANT => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['general'], // approveAsProvincial
            ],
            ProjectStatus::REVERTED_TO_PROVINCIAL => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['general'], // approveAsProvincial
            ],
            ProjectStatus::REVERTED_TO_COORDINATOR => [
                ProjectStatus::SUBMITTED_TO_PROVINCIAL => ['executor', 'applicant'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['provincial', 'general'], // forwardToCoordinator
            ],
            ProjectStatus::SUBMITTED_TO_PROVINCIAL => [
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['provincial', 'general'],
                ProjectStatus::REVERTED_BY_PROVINCIAL => ['provincial', 'general'],
                ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL => ['general'],
                ProjectStatus::REVERTED_TO_EXECUTOR => ['general'],
                ProjectStatus::REVERTED_TO_APPLICANT => ['general'],
                ProjectStatus::REVERTED_TO_PROVINCIAL => ['general'], // revertAsProvincial level=provincial
            ],
            ProjectStatus::FORWARDED_TO_COORDINATOR => [
                ProjectStatus::APPROVED_BY_COORDINATOR => ['coordinator', 'general'],
                ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR => ['general'],
                ProjectStatus::REVERTED_BY_COORDINATOR => ['coordinator', 'general'],
                ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR => ['general'],
                ProjectStatus::REVERTED_TO_PROVINCIAL => ['general'],
                ProjectStatus::REVERTED_TO_COORDINATOR => ['general'],
                ProjectStatus::REJECTED_BY_COORDINATOR => ['coordinator'],
                ProjectStatus::REVERTED_BY_PROVINCIAL => ['provincial', 'general'],   // revertByProvincial
                ProjectStatus::REVERTED_BY_GENERAL_AS_PROVINCIAL => ['general'],
                ProjectStatus::REVERTED_TO_EXECUTOR => ['general'],   // revertAsProvincial / revertToLevel
                ProjectStatus::REVERTED_TO_APPLICANT => ['general'],
            ],
            ProjectStatus::APPROVED_BY_COORDINATOR => [
                ProjectStatus::REVERTED_BY_COORDINATOR => ['coordinator', 'general'],
                ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR => ['general'],
                ProjectStatus::REVERTED_TO_PROVINCIAL => ['general'],
                ProjectStatus::REVERTED_TO_COORDINATOR => ['general'],
            ],
            ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR => [
                ProjectStatus::REVERTED_BY_GENERAL_AS_COORDINATOR => ['general'],
                ProjectStatus::REVERTED_TO_PROVINCIAL => ['general'],
                ProjectStatus::REVERTED_TO_COORDINATOR => ['general'],
                ProjectStatus::FORWARDED_TO_COORDINATOR => ['general'], // rollback on budget validation failure
            ],
        ];

        if (!isset($transitions[$currentStatus])) {
            return false;
        }

        if (!isset($transitions[$currentStatus][$newStatus])) {
            return false;
        }

        return in_array($userRole, $transitions[$currentStatus][$newStatus]);
    }
}

