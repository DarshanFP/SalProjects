<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProjectPhaseService
{
    /**
     * Calculate final commencement date based on overall period and current phase
     *
     * Formula: Final Commencement = Initial Commencement + (Current Phase - 1) * 12 months
     *
     * @param Project $project
     * @return Carbon|null
     */
    public static function calculateFinalCommencementDate(Project $project): ?Carbon
    {
        if (!$project->commencement_month_year) {
            Log::warning('Project missing commencement_month_year', [
                'project_id' => $project->project_id
            ]);
            return null;
        }

        try {
            $initialCommencement = Carbon::parse($project->commencement_month_year);
            $currentPhase = $project->current_phase ?? 1;

            // Each phase is 12 months
            // Phase 1 starts at initial commencement
            // Phase 2 starts 12 months after initial commencement
            // Phase 3 starts 24 months after initial commencement, etc.
            $monthsToAdd = ($currentPhase - 1) * 12;

            $finalCommencement = $initialCommencement->copy()->addMonths($monthsToAdd);

            Log::debug('Calculated final commencement date', [
                'project_id' => $project->project_id,
                'initial_commencement' => $initialCommencement->format('Y-m-d'),
                'current_phase' => $currentPhase,
                'months_to_add' => $monthsToAdd,
                'final_commencement' => $finalCommencement->format('Y-m-d')
            ]);

            return $finalCommencement;
        } catch (\Exception $e) {
            Log::error('Error calculating final commencement date', [
                'project_id' => $project->project_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate total months elapsed from final commencement date
     *
     * @param Project $project
     * @return int
     */
    public static function getTotalMonthsElapsed(Project $project): int
    {
        $finalCommencement = self::calculateFinalCommencementDate($project);
        if (!$finalCommencement) {
            return 0;
        }

        $now = Carbon::now();
        $totalMonths = $finalCommencement->diffInMonths($now);

        // If current date is before commencement, return 0
        if ($now->isBefore($finalCommencement)) {
            return 0;
        }

        return $totalMonths;
    }

    /**
     * Calculate months elapsed in current phase (0-11)
     *
     * @param Project $project
     * @return int Returns 0-11, where 0 means just started, 11 means almost complete
     */
    public static function getMonthsElapsedInCurrentPhase(Project $project): int
    {
        $totalMonths = self::getTotalMonthsElapsed($project);

        // Months in current phase (0-11)
        // Month 0 = just started current phase
        // Month 11 = 11 months into current phase
        $monthsInPhase = $totalMonths % 12;

        return $monthsInPhase;
    }

    /**
     * Check if project is eligible for completion
     *
     * Project is eligible if:
     * 1. Status is approved_by_coordinator
     * 2. At least 10 months have elapsed in current phase
     * 3. Project is not already completed
     *
     * @param Project $project
     * @return bool
     */
    public static function isEligibleForCompletion(Project $project): bool
    {
        // Must be approved
        if ($project->status !== \App\Constants\ProjectStatus::APPROVED_BY_COORDINATOR) {
            return false;
        }

        // Must not be already completed
        if ($project->completed_at) {
            return false;
        }

        // Must have commencement date
        if (!$project->commencement_month_year) {
            return false;
        }

        // Check if 10+ months have elapsed
        $monthsElapsed = self::getMonthsElapsedInCurrentPhase($project);

        return $monthsElapsed >= 10;
    }

    /**
     * Get phase information for display
     *
     * @param Project $project
     * @return array
     */
    public static function getPhaseInfo(Project $project): array
    {
        $finalCommencement = self::calculateFinalCommencementDate($project);
        $totalMonths = self::getTotalMonthsElapsed($project);
        $monthsInPhase = self::getMonthsElapsedInCurrentPhase($project);
        $isEligible = self::isEligibleForCompletion($project);

        return [
            'final_commencement_date' => $finalCommencement ? $finalCommencement->format('Y-m-d') : null,
            'final_commencement_display' => $finalCommencement ? $finalCommencement->format('F Y') : 'Not set',
            'current_phase' => $project->current_phase ?? 1,
            'overall_project_period' => $project->overall_project_period ?? 1,
            'total_months_elapsed' => $totalMonths,
            'months_in_current_phase' => $monthsInPhase,
            'months_remaining_in_phase' => max(0, 12 - $monthsInPhase),
            'is_eligible_for_completion' => $isEligible,
            'phase_progress_percentage' => min(100, round(($monthsInPhase / 12) * 100, 2)),
        ];
    }

    /**
     * Calculate next phase start date
     *
     * @param Project $project
     * @return Carbon|null
     */
    public static function getNextPhaseStartDate(Project $project): ?Carbon
    {
        $finalCommencement = self::calculateFinalCommencementDate($project);
        if (!$finalCommencement) {
            return null;
        }

        $currentPhase = $project->current_phase ?? 1;
        $monthsToNextPhase = (12 - self::getMonthsElapsedInCurrentPhase($project));

        return $finalCommencement->copy()->addMonths(($currentPhase - 1) * 12 + 12);
    }
}
