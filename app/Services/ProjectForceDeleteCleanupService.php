<?php

namespace App\Services;

use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectObjective;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Runs child cleanup and file deletion when a project is force-deleted.
 * Called from Project::forceDeleting so that soft delete never touches child data.
 */
class ProjectForceDeleteCleanupService
{
    /**
     * Permanently delete a project. Runs cleanup via forceDeleting event, then removes the row.
     */
    public function forceDelete(Project $project): void
    {
        $project->forceDelete();
    }

    public function cleanup(Project $project): void
    {
        $project_id = $project->project_id;
        $project_type = $project->project_type;

        Log::info('ProjectForceDeleteCleanupService - Starting cleanup for force delete', [
            'project_id' => $project_id,
            'project_type' => $project_type,
        ]);

        $nonIndividualTypes = [
            'Rural-Urban-Tribal',
            'CHILD CARE INSTITUTION',
            'Institutional Ongoing Group Educational proposal',
            'Livelihood Development Projects',
            'Residential Skill Training Proposal 2',
            'Development Projects',
            'NEXT PHASE - DEVELOPMENT PROPOSAL',
            'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER',
        ];

        if (in_array($project_type, $nonIndividualTypes)) {
            app(\App\Http\Controllers\Projects\SustainabilityController::class)->destroy($project_id);
            $this->deleteLogicalFrameworkByProjectId($project_id);
        }

        switch ($project_type) {
            case 'Rural-Urban-Tribal':
                app(\App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController::class)->destroy($project_id);
                app(\App\Http\Controllers\Projects\EduRUTTargetGroupController::class)->destroy($project_id);
                app(\App\Http\Controllers\Projects\EduRUTAnnexedTargetGroupController::class)->destroy($project_id);
                break;
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                app(\App\Http\Controllers\Projects\CICBasicInfoController::class)->destroy($project_id);
                break;
            case 'CHILD CARE INSTITUTION':
                $this->destroyCCISections($project_id);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $this->destroyIGESections($project_id);
                break;
            case 'Livelihood Development Projects':
                $this->destroyLDPSections($project_id);
                break;
            case 'Residential Skill Training Proposal 2':
            case 'Development Projects':
            case 'NEXT PHASE - DEVELOPMENT PROPOSAL':
                $this->destroyRSTSections($project_id, $project_type);
                break;
            case 'Individual - Ongoing Educational support':
                $this->destroyIESections($project_id);
                break;
            case 'Individual - Livelihood Application':
                $this->destroyILPSections($project_id);
                break;
            case 'Individual - Access to Health':
                $this->destroyIAHSections($project_id);
                break;
            case 'Individual - Initial - Educational support':
                $this->destroyIIESSections($project_id);
                break;
            default:
                Log::warning('ProjectForceDeleteCleanupService - Unknown project type', ['project_type' => $project_type]);
        }

        $this->deleteAttachmentFiles($project_id, $project_type);

        Log::info('ProjectForceDeleteCleanupService - Cleanup completed', ['project_id' => $project_id]);
    }

    private function deleteLogicalFrameworkByProjectId(string $project_id): void
    {
        $objectives = ProjectObjective::where('project_id', $project_id)->get();
        $controller = app(\App\Http\Controllers\Projects\LogicalFrameworkController::class);
        foreach ($objectives as $objective) {
            try {
                $controller->destroy($objective->objective_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - Objective delete failed', [
                    'objective_id' => $objective->objective_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function destroyCCISections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\CCI\AchievementsController::class,
            \App\Http\Controllers\Projects\CCI\AgeProfileController::class,
            \App\Http\Controllers\Projects\CCI\AnnexedTargetGroupController::class,
            \App\Http\Controllers\Projects\CCI\EconomicBackgroundController::class,
            \App\Http\Controllers\Projects\CCI\PersonalSituationController::class,
            \App\Http\Controllers\Projects\CCI\PresentSituationController::class,
            \App\Http\Controllers\Projects\CCI\RationaleController::class,
            \App\Http\Controllers\Projects\CCI\StatisticsController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - CCI section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyIGESections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\IGE\InstitutionInfoController::class,
            \App\Http\Controllers\Projects\IGE\IGEBeneficiariesSupportedController::class,
            \App\Http\Controllers\Projects\IGE\NewBeneficiariesController::class,
            \App\Http\Controllers\Projects\IGE\OngoingBeneficiariesController::class,
            \App\Http\Controllers\Projects\IGE\IGEBudgetController::class,
            \App\Http\Controllers\Projects\IGE\DevelopmentMonitoringController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - IGE section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyLDPSections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\LDP\InterventionLogicController::class,
            \App\Http\Controllers\Projects\LDP\NeedAnalysisController::class,
            \App\Http\Controllers\Projects\LDP\TargetGroupController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - LDP section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyRSTSections(string $project_id, string $project_type): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\RST\BeneficiariesAreaController::class,
            \App\Http\Controllers\Projects\RST\GeographicalAreaController::class,
            \App\Http\Controllers\Projects\RST\InstitutionInfoController::class,
            \App\Http\Controllers\Projects\RST\TargetGroupAnnexureController::class,
            \App\Http\Controllers\Projects\RST\TargetGroupController::class,
        ];
        if ($project_type !== 'Residential Skill Training Proposal 2') {
            $controllers = [\App\Http\Controllers\Projects\RST\BeneficiariesAreaController::class];
        }
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - RST section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyIESections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\IES\IESPersonalInfoController::class,
            \App\Http\Controllers\Projects\IES\IESFamilyWorkingMembersController::class,
            \App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController::class,
            \App\Http\Controllers\Projects\IES\IESEducationBackgroundController::class,
            \App\Http\Controllers\Projects\IES\IESExpensesController::class,
            \App\Http\Controllers\Projects\IES\IESAttachmentsController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - IES section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyILPSections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\ILP\PersonalInfoController::class,
            \App\Http\Controllers\Projects\ILP\RevenueGoalsController::class,
            \App\Http\Controllers\Projects\ILP\StrengthWeaknessController::class,
            \App\Http\Controllers\Projects\ILP\RiskAnalysisController::class,
            \App\Http\Controllers\Projects\ILP\AttachedDocumentsController::class,
            \App\Http\Controllers\Projects\ILP\BudgetController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - ILP section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyIAHSections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\IAH\IAHPersonalInfoController::class,
            \App\Http\Controllers\Projects\IAH\IAHEarningMembersController::class,
            \App\Http\Controllers\Projects\IAH\IAHHealthConditionController::class,
            \App\Http\Controllers\Projects\IAH\IAHSupportDetailsController::class,
            \App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController::class,
            \App\Http\Controllers\Projects\IAH\IAHDocumentsController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - IAH section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function destroyIIESSections(string $project_id): void
    {
        $controllers = [
            \App\Http\Controllers\Projects\IIES\IIESPersonalInfoController::class,
            \App\Http\Controllers\Projects\IIES\IIESFamilyWorkingMembersController::class,
            \App\Http\Controllers\Projects\IIES\IIESImmediateFamilyDetailsController::class,
            \App\Http\Controllers\Projects\IIES\EducationBackgroundController::class,
            \App\Http\Controllers\Projects\IIES\FinancialSupportController::class,
            \App\Http\Controllers\Projects\IIES\IIESAttachmentsController::class,
            \App\Http\Controllers\Projects\IIES\IIESExpensesController::class,
        ];
        foreach ($controllers as $class) {
            try {
                app($class)->destroy($project_id);
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - IIES section delete failed', ['class' => $class, 'error' => $e->getMessage()]);
            }
        }
    }

    private function deleteAttachmentFiles(string $project_id, string $project_type): void
    {
        $dirs = [
            'Individual - Ongoing Educational support' => "project_attachments/IES/{$project_id}",
            'Individual - Initial - Educational support' => "project_attachments/IIES/{$project_id}",
            'Individual - Access to Health' => "project_attachments/IAH/{$project_id}",
            'Individual - Livelihood Application' => "project_attachments/ILP/{$project_id}",
        ];
        if (isset($dirs[$project_type])) {
            try {
                if (Storage::disk('public')->exists($dirs[$project_type])) {
                    Storage::disk('public')->deleteDirectory($dirs[$project_type]);
                }
            } catch (\Throwable $e) {
                Log::warning('ProjectForceDeleteCleanupService - Storage delete failed', ['path' => $dirs[$project_type], 'error' => $e->getMessage()]);
            }
        }
        $genericPrefix = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $project_type);
        $genericDir = "project_attachments/{$genericPrefix}/{$project_id}";
        try {
            if (Storage::disk('public')->exists($genericDir)) {
                Storage::disk('public')->deleteDirectory($genericDir);
            }
        } catch (\Throwable $e) {
            Log::warning('ProjectForceDeleteCleanupService - Generic storage delete failed', ['path' => $genericDir, 'error' => $e->getMessage()]);
        }
    }
}
