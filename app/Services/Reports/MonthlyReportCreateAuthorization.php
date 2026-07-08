<?php

namespace App\Services\Reports;

use App\Constants\ProjectStatus;
use App\Helpers\ProjectPermissionHelper;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Phase 5: Authorization for monthly report create/store.
 *
 * Rule: only approved projects owned or in-charge by executor/applicant (same province).
 */
class MonthlyReportCreateAuthorization
{
    /**
     * @return array{allowed: bool, reason: ?string, message: ?string}
     */
    public static function check(User $user, Project $project): array
    {
        $context = [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'project_id' => $project->project_id,
            'project_status' => $project->status,
        ];

        if (!in_array($user->role, ['executor', 'applicant'], true)) {
            Log::warning('Monthly report create denied: invalid role', $context);

            return [
                'allowed' => false,
                'reason' => 'invalid_role',
                'message' => 'You do not have permission to create reports for this project.',
            ];
        }

        if (!ProjectStatus::isApproved($project->status ?? '')) {
            Log::warning('Monthly report create denied: project not approved', $context);

            return [
                'allowed' => false,
                'reason' => 'project_not_approved',
                'message' => 'Reports can only be created for approved projects.',
            ];
        }

        if (!ProjectPermissionHelper::isOwnerOrInCharge($project, $user)) {
            Log::warning('Monthly report create denied: not owner or in-charge', array_merge($context, [
                'project_user_id' => $project->user_id,
                'project_in_charge' => $project->in_charge,
            ]));

            return [
                'allowed' => false,
                'reason' => 'not_owner_or_in_charge',
                'message' => 'You do not have permission to create reports for this project.',
            ];
        }

        if (!ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
            Log::warning('Monthly report create denied: province mismatch', array_merge($context, [
                'user_province_id' => $user->province_id,
                'project_province_id' => $project->province_id,
            ]));

            return [
                'allowed' => false,
                'reason' => 'province_mismatch',
                'message' => 'You do not have permission to create reports for this project.',
            ];
        }

        Log::info('Monthly report create authorized', $context);

        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    public static function authorize(User $user, Project $project): bool
    {
        return self::check($user, $project)['allowed'];
    }

    public static function abortUnlessAllowed(User $user, Project $project): void
    {
        $result = self::check($user, $project);
        if (!$result['allowed']) {
            abort(403, $result['message'] ?? 'Forbidden');
        }
    }

    public static function reportExistsForPeriod(string $projectId, int $month, int $year): bool
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfDay();

        return DPReport::where('project_id', $projectId)
            ->whereYear('report_month_year', $periodStart->year)
            ->whereMonth('report_month_year', $periodStart->month)
            ->exists();
    }
}
