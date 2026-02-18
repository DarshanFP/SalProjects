<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Requests\Projects\SubmitProjectRequest;
use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Helpers\ProjectPermissionHelper;
use App\Services\ActivityHistoryService;
use App\Services\ProjectStatusService;
use App\Services\ProjectPhaseService;
use App\Traits\HandlesErrors;
use App\Services\ProjectQueryService;
use App\Services\Budget\BudgetSyncGuard;
use App\Helpers\SocietyVisibilityHelper;
// Aliases for CCI Controllers with prefix 'CCI' -
use App\Http\Controllers\Projects\CCI\AchievementsController as CCIAchievementsController;
use App\Http\Controllers\Projects\CCI\AgeProfileController as CCIAgeProfileController;
use App\Http\Controllers\Projects\CCI\AnnexedTargetGroupController as CCIAnnexedTargetGroupController;
use App\Http\Controllers\Projects\CCI\EconomicBackgroundController as CCIEconomicBackgroundController;
use App\Http\Controllers\Projects\CCI\PersonalSituationController as CCIPersonalSituationController;
use App\Http\Controllers\Projects\CCI\PresentSituationController as CCIPresentSituationController;
use App\Http\Controllers\Projects\CCI\RationaleController as CCIRationaleController;
use App\Http\Controllers\Projects\CCI\StatisticsController as CCIStatisticsController;
// Aliases for IGE Controllers
use App\Http\Controllers\Projects\IGE\InstitutionInfoController as IGEInstitutionInfoController;
use App\Http\Controllers\Projects\IGE\IGEBeneficiariesSupportedController as IGEBeneficiariesSupportedController;
use App\Http\Controllers\Projects\IGE\NewBeneficiariesController as IGENewBeneficiariesController;
use App\Http\Controllers\Projects\IGE\OngoingBeneficiariesController as IGEOngoingBeneficiariesController;
use App\Http\Controllers\Projects\IGE\IGEBudgetController as IGEBudgetController;
use App\Http\Controllers\Projects\IGE\DevelopmentMonitoringController as IGEDevelopmentMonitoringController;
// LDP - Livelihood Development Project controllers
use App\Http\Controllers\Projects\LDP\InterventionLogicController as LDPInterventionLogicController;
use App\Http\Controllers\Projects\LDP\NeedAnalysisController as LDPNeedAnalysisController;
use App\Http\Controllers\Projects\LDP\TargetGroupController as LDPTargetGroupController;
// RST - Residential Skill Training controllers
// RST Controllers
use App\Http\Controllers\Projects\RST\BeneficiariesAreaController as RSTBeneficiariesAreaController;
use App\Http\Controllers\Projects\RST\GeographicalAreaController as RSTGeographicalAreaController;
use App\Http\Controllers\Projects\RST\InstitutionInfoController as RSTInstitutionInfoController;
use App\Http\Controllers\Projects\RST\TargetGroupAnnexureController as RSTTargetGroupAnnexureController;
use App\Http\Controllers\Projects\RST\TargetGroupController as RSTTargetGroupController;
// IES - Individual - Ongoing Educational Support
use App\Http\Controllers\Projects\IES\IESAttachmentsController as IESAttachmentsController;
use App\Http\Controllers\Projects\IES\IESEducationBackgroundController as IESEducationBackgroundController;
use App\Http\Controllers\Projects\IES\IESExpensesController as IESExpensesController;
use App\Http\Controllers\Projects\IES\IESFamilyWorkingMembersController as IESFamilyWorkingMembersController;
use App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController as IESImmediateFamilyDetailsController;
use App\Http\Controllers\Projects\IES\IESPersonalInfoController as IESPersonalInfoController;
// Aliases for ILP Controllers
use App\Http\Controllers\Projects\ILP\PersonalInfoController as ILPPersonalInfoController;
use App\Http\Controllers\Projects\ILP\RevenueGoalsController as ILPRevenueGoalsController;
use App\Http\Controllers\Projects\ILP\StrengthWeaknessController as ILPStrengthWeaknessController;
use App\Http\Controllers\Projects\ILP\RiskAnalysisController as ILPRiskAnalysisController;
use App\Http\Controllers\Projects\ILP\AttachedDocumentsController as ILPAttachedDocumentsController;
use App\Http\Controllers\Projects\ILP\BudgetController as ILPBudgetController;
// Aliases for IAH Controllers
use App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController as IAHBudgetDetailsController;
use App\Http\Controllers\Projects\IAH\IAHDocumentsController as IAHDocumentsController;
use App\Http\Controllers\Projects\IAH\IAHEarningMembersController as IAHEarningMembersController;
use App\Http\Controllers\Projects\IAH\IAHHealthConditionController as IAHHealthConditionController;
use App\Http\Controllers\Projects\IAH\IAHPersonalInfoController as IAHPersonalInfoController;
use App\Http\Controllers\Projects\IAH\IAHSupportDetailsController as IAHSupportDetailsController;
// Aliases for IIES Controllers
use App\Http\Controllers\Projects\IIES\EducationBackgroundController as IIESEducationBackgroundController;
use App\Http\Controllers\Projects\IIES\FinancialSupportController as IIESFinancialSupportController;
use App\Http\Controllers\Projects\IIES\IIESAttachmentsController as IIESAttachmentsController;
use App\Http\Controllers\Projects\IIES\IIESFamilyWorkingMembersController as IIESFamilyWorkingMembersController;
use App\Http\Controllers\Projects\IIES\IIESImmediateFamilyDetailsController as IIESImmediateFamilyDetailsController;
use App\Http\Controllers\Projects\IIES\IIESPersonalInfoController as IIESPersonalInfoController;
use App\Http\Controllers\Projects\IIES\IIESExpensesController as IIESExpensesController;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\IIES\ProjectIIESEducationBackground;
use App\Domain\Budget\ProjectFinancialResolver;
use App\Services\ProjectLifecycleService;

class ProjectController extends Controller
{
    protected $logicalFrameworkController;
    protected $sustainabilityController;
    //Edu-RUT
    protected $eduRUTBasicInfoController;
    protected $eduRUTTargetGroupController;
    protected $eduRUTAnnexedTargetGroupController;
    // CIC
    protected $cicBasicInfoController;
    // CCI
    protected $cciAchievementsController;
    protected $cciAgeProfileController;
    protected $cciAnnexedTargetGroupController;
    protected $cciEconomicBackgroundController;
    protected $cciPersonalSituationController;
    protected $cciPresentSituationController;
    protected $cciRationaleController;
    protected $cciStatisticsController;
    // Declarations for IGE controllers
    protected $igeInstitutionInfoController;
    protected $igeBeneficiariesSupportedController;
    protected $igeNewBeneficiariesController;
    protected $igeOngoingBeneficiariesController;
    protected $igeBudgetController;
    protected $igeDevelopmentMonitoringController;
    // LDP controllers
    protected $ldpInterventionLogicController;
    protected $ldpNeedAnalysisController;
    protected $ldpTargetGroupController;

    // RST controllers
    protected $rstBeneficiariesAreaController;
    protected $rstGeographicalAreaController;
    protected $rstInstitutionInfoController;
    protected $rstTargetGroupAnnexureController;
    protected $rstTargetGroupController;
    // IES - Individual - Ongoing Educational Support
    protected $iesAttachmentsController;
    protected $iesEducationBackgroundController;
    protected $iesExpensesController;
    protected $iesFamilyWorkingMembersController;
    protected $iesImmediateFamilyDetailsController;
    protected $iesPersonalInfoController;
    //  ILP - Individual - Livelihood Application
    protected $ilpPersonalInfoController;
    protected $ilpRevenueGoalsController;
    protected $ilpStrengthWeaknessController;
    protected $ilpRiskAnalysisController;
    protected $ilpAttachedDocumentsController;
    protected $ilpBudgetController;
    //  IAH - Individual - Access to Health
    protected $iahBudgetDetailsController;
    protected $iahDocumentsController;
    protected $iahEarningMembersController;
    protected $iahHealthConditionController;
    protected $iahPersonalInfoController;
    protected $iahSupportDetailsController;
    // Individual - Initial Educational Support controllers
    protected $iiesEducationBackgroundController;
    protected $iiesFinancialSupportController;
    protected $iiesAttachmentsController;
    protected $iiesFamilyWorkingMembersController;
    protected $iiesImmediateFamilyDetailsController;
    protected $iiesPersonalInfoController;
    protected $iiesExpensesController;






    public function __construct(
        LogicalFrameworkController $logicalFrameworkController,
        SustainabilityController $sustainabilityController,
        //Edu-RUT
        ProjectEduRUTBasicInfoController $eduRUTBasicInfoController,
        EduRUTTargetGroupController $eduRUTTargetGroupController,
        EduRUTAnnexedTargetGroupController $eduRUTAnnexedTargetGroupController,
        // CIC
        CICBasicInfoController $cicBasicInfoController,
        // CCI
        CCIAchievementsController $cciAchievementsController,
        CCIAgeProfileController $cciAgeProfileController,
        CCIAnnexedTargetGroupController $cciAnnexedTargetGroupController,
        CCIEconomicBackgroundController $cciEconomicBackgroundController,
        CCIPersonalSituationController $cciPersonalSituationController,
        CCIPresentSituationController $cciPresentSituationController,
        CCIRationaleController $cciRationaleController,
        CCIStatisticsController $cciStatisticsController,
        // IGE controllers...
        IGEInstitutionInfoController $igeInstitutionInfoController,
        IGEBeneficiariesSupportedController $igeBeneficiariesSupportedController,
        IGENewBeneficiariesController $igeNewBeneficiariesController,
        IGEOngoingBeneficiariesController $igeOngoingBeneficiariesController,
        IGEBudgetController $igeBudgetController,
        IGEDevelopmentMonitoringController $igeDevelopmentMonitoringController,
        // LDP controllers
        LDPInterventionLogicController $ldpInterventionLogicController,
        LDPNeedAnalysisController $ldpNeedAnalysisController,
        LDPTargetGroupController $ldpTargetGroupController,
        // RST controllers
        RSTBeneficiariesAreaController $rstBeneficiariesAreaController,
        RSTGeographicalAreaController $rstGeographicalAreaController,
        RSTInstitutionInfoController $rstInstitutionInfoController,
        RSTTargetGroupAnnexureController $rstTargetGroupAnnexureController,
        RSTTargetGroupController $rstTargetGroupController,
        // IES - Individual - Ongoing Educational Support
        IESAttachmentsController $iesAttachmentsController,
        IESEducationBackgroundController $iesEducationBackgroundController,
        IESExpensesController $iesExpensesController,
        IESFamilyWorkingMembersController $iesFamilyWorkingMembersController,
        IESImmediateFamilyDetailsController $iesImmediateFamilyDetailsController,
        IESPersonalInfoController $iesPersonalInfoController,
    //  ILP - Individual - Livelihood Application
        ILPPersonalInfoController $ilpPersonalInfoController,
        ILPRevenueGoalsController $ilpRevenueGoalsController,
        ILPStrengthWeaknessController $ilpStrengthWeaknessController,
        ILPRiskAnalysisController $ilpRiskAnalysisController,
        ILPAttachedDocumentsController $ilpAttachedDocumentsController,
        ILPBudgetController $ilpBudgetController,
     //  IAH - Individual - Access to Health
        IAHBudgetDetailsController $iahBudgetDetailsController,
        IAHDocumentsController $iahDocumentsController,
        IAHEarningMembersController $iahEarningMembersController,
        IAHHealthConditionController $iahHealthConditionController,
        IAHPersonalInfoController $iahPersonalInfoController,
        IAHSupportDetailsController $iahSupportDetailsController,
    //  IIES - Individual - Initial Educational Support
        IIESEducationBackgroundController $iiesEducationBackgroundController,
        IIESFinancialSupportController $iiesFinancialSupportController,
        IIESAttachmentsController $iiesAttachmentsController,
        IIESFamilyWorkingMembersController $iiesFamilyWorkingMembersController,
        IIESImmediateFamilyDetailsController $iiesImmediateFamilyDetailsController,
        IIESPersonalInfoController $iiesPersonalInfoController,
        IIESExpensesController $iiesExpensesController

    ) {
        $this->logicalFrameworkController = $logicalFrameworkController;
        $this->sustainabilityController = $sustainabilityController;
        //Edu-RUT
        $this->eduRUTBasicInfoController = $eduRUTBasicInfoController;
        $this->eduRUTTargetGroupController = $eduRUTTargetGroupController;
        $this->eduRUTAnnexedTargetGroupController = $eduRUTAnnexedTargetGroupController;
        // CIC
        $this->cicBasicInfoController = $cicBasicInfoController;
        // CCI
        $this->cciAchievementsController = $cciAchievementsController;
        $this->cciAgeProfileController = $cciAgeProfileController;
        $this->cciAnnexedTargetGroupController = $cciAnnexedTargetGroupController;
        $this->cciEconomicBackgroundController = $cciEconomicBackgroundController;
        $this->cciPersonalSituationController = $cciPersonalSituationController;
        $this->cciPresentSituationController = $cciPresentSituationController;
        $this->cciRationaleController = $cciRationaleController;
        $this->cciStatisticsController = $cciStatisticsController;
        // IGE controllers...
        $this->igeInstitutionInfoController = $igeInstitutionInfoController;
        $this->igeBeneficiariesSupportedController = $igeBeneficiariesSupportedController;
        $this->igeNewBeneficiariesController = $igeNewBeneficiariesController;
        $this->igeOngoingBeneficiariesController = $igeOngoingBeneficiariesController;
        $this->igeBudgetController = $igeBudgetController;
        $this->igeDevelopmentMonitoringController = $igeDevelopmentMonitoringController;
        // LDP controllers
        $this->ldpInterventionLogicController = $ldpInterventionLogicController;
        $this->ldpNeedAnalysisController = $ldpNeedAnalysisController;
        $this->ldpTargetGroupController = $ldpTargetGroupController;
        // RST controllers
        $this->rstBeneficiariesAreaController = $rstBeneficiariesAreaController;
        $this->rstGeographicalAreaController = $rstGeographicalAreaController;
        $this->rstInstitutionInfoController = $rstInstitutionInfoController;
        $this->rstTargetGroupAnnexureController = $rstTargetGroupAnnexureController;
        $this->rstTargetGroupController = $rstTargetGroupController;
        // IES - Individual - Ongoing Educational Support
        $this->iesAttachmentsController = $iesAttachmentsController;
        $this->iesEducationBackgroundController = $iesEducationBackgroundController;
        $this->iesExpensesController = $iesExpensesController;
        $this->iesFamilyWorkingMembersController = $iesFamilyWorkingMembersController;
        $this->iesImmediateFamilyDetailsController = $iesImmediateFamilyDetailsController;
        $this->iesPersonalInfoController = $iesPersonalInfoController;
        // Assign ILP controllers
        $this->ilpPersonalInfoController = $ilpPersonalInfoController;
        $this->ilpRevenueGoalsController = $ilpRevenueGoalsController;
        $this->ilpStrengthWeaknessController = $ilpStrengthWeaknessController;
        $this->ilpRiskAnalysisController = $ilpRiskAnalysisController;
        $this->ilpAttachedDocumentsController = $ilpAttachedDocumentsController;
        $this->ilpBudgetController = $ilpBudgetController;
        //  IAH - Individual - Access to Health
        $this->iahBudgetDetailsController = $iahBudgetDetailsController;
        $this->iahDocumentsController = $iahDocumentsController;
        $this->iahEarningMembersController = $iahEarningMembersController;
        $this->iahHealthConditionController = $iahHealthConditionController;
        $this->iahPersonalInfoController = $iahPersonalInfoController;
        $this->iahSupportDetailsController = $iahSupportDetailsController;
        //  IIES - Individual - Initial Educational Support
        $this->iiesEducationBackgroundController = $iiesEducationBackgroundController;
        $this->iiesFinancialSupportController = $iiesFinancialSupportController;
        $this->iiesAttachmentsController = $iiesAttachmentsController;
        $this->iiesFamilyWorkingMembersController = $iiesFamilyWorkingMembersController;
        $this->iiesImmediateFamilyDetailsController = $iiesImmediateFamilyDetailsController;
        $this->iiesPersonalInfoController = $iiesPersonalInfoController;
        $this->iiesExpensesController = $iiesExpensesController;


    }

    public function index()
    {
        $user = Auth::user();

        // Fetch projects where the user is either the owner or the in-charge
        // Exclude all approved projects (any approval status) for executors/applicants — M3 Phase 1
        // Eager load relationships to prevent N+1 queries
        $projects = \App\Services\ProjectQueryService::getProjectsForUserQuery($user)
            ->notApproved()
            ->with(['user', 'objectives', 'budgets'])
            ->get();

        return view('projects.Oldprojects.index', compact('projects', 'user'));
    }


public function create()
{
    Log::info('ProjectController@create - Starting create process');

    $user = Auth::user();
    $users = User::all();

    Log::info('ProjectController@create - Fetching development projects for predecessor selection', [
        'user_id' => $user->id
    ]);
    // Always fetch development projects for all project types (for predecessor selection)
    // Eager load relationships to prevent N+1 queries
    $developmentProjects = \App\Services\ProjectQueryService::getProjectsForUserQuery($user)
        ->whereIn('project_type', [
            ProjectType::DEVELOPMENT_PROJECTS,
            ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL
        ])
        ->with(['user', 'budgets'])
        ->orderBy('project_id', 'desc')
        ->get();
    Log::info('ProjectController@create - Development projects fetched', [
        'count' => $developmentProjects->count()
    ]);

    $predecessorBeneficiaries = [];
    $predecessorObjectives = [];
    $predecessorActivities = [];
    $predecessorSustainability = [];
    $predecessorBudget = [];
    $predecessorAttachments = [];
    $predecessorProjectId = request()->get('predecessor_project_id');

    if ($predecessorProjectId) {
        Log::info('ProjectController@create - Fetching predecessor project data', [
            'predecessor_project_id' => $predecessorProjectId
        ]);
        $predecessorProject = Project::with([
            'DPRSTBeneficiariesAreas',
            'objectives.results',
            'objectives.risks',
            'objectives.activities.timeframes',
            'sustainabilities',
            'budgets',
            'attachments'
        ])->find($predecessorProjectId);

        if ($predecessorProject) {
            $predecessorBeneficiaries = $predecessorProject->DPRSTBeneficiariesAreas->map(function ($area) {
                return [
                    'project_area' => $area->project_area,
                    'category' => $area->category_beneficiary,
                    'direct' => $area->direct_beneficiaries,
                    'indirect' => $area->indirect_beneficiaries
                ];
            })->toArray();
            $predecessorObjectives = $predecessorProject->objectives->map(function ($objective) {
                return [
                    'objective_id' => $objective->objective_id,
                    'objective' => $objective->objective,
                    'results' => $objective->results->map(function ($result) {
                        return [
                            'result_id' => $result->result_id,
                            'result' => $result->result
                        ];
                    })->toArray(),
                    'risks' => $objective->risks->map(function ($risk) {
                        return [
                            'risk_id' => $risk->risk_id,
                            'risk' => $risk->risk
                        ];
                    })->toArray(),
                    'activities' => $objective->activities->map(function ($activity) {
                        return [
                            'activity_id' => $activity->activity_id,
                            'activity' => $activity->activity,
                            'verification' => $activity->verification,
                            'timeframes' => $activity->timeframes->map(function ($timeframe) {
                                return [
                                    'timeframe_id' => $timeframe->timeframe_id,
                                    'month' => $timeframe->month,
                                    'is_active' => $timeframe->is_active
                                ];
                            })->toArray()
                        ];
                    })->toArray()
                ];
            })->toArray();
            $predecessorSustainability = $predecessorProject->sustainabilities->first()
                ? $predecessorProject->sustainabilities->first()->toArray()
                : [];
            $predecessorBudget = $predecessorProject->budgets->groupBy('phase')->map(function ($phase) {
                return [
                    'amount_sanctioned' => $phase->sum('amount'),
                    'budget' => $phase->map(function ($budget) {
                        return [
                            'particular' => $budget->particular,
                            'rate_quantity' => $budget->rate_quantity,
                            'rate_multiplier' => $budget->rate_multiplier,
                            'rate_duration' => $budget->rate_duration,
                            'rate_increase' => $budget->rate_increase,
                            'this_phase' => $budget->this_phase,
                            'next_phase' => $budget->next_phase,
                        ];
                    })->toArray(),
                ];
            })->toArray();
            $predecessorAttachments = $predecessorProject->attachments->map(function ($attachment) {
                return [
                    'file' => $attachment->file,
                    'file_name' => $attachment->file_name,
                    'description' => $attachment->description,
                ];
            })->toArray();
            Log::info('ProjectController@create - Predecessor data fetched', [
                'predecessor_project_id' => $predecessorProjectId,
                'beneficiaries_count' => count($predecessorBeneficiaries),
                'objectives_count' => count($predecessorObjectives)
            ]);
        } else {
            Log::warning('ProjectController@create - Predecessor project not found', [
                'predecessor_project_id' => $predecessorProjectId
            ]);
        }
    }

    // Phase 5B1: Role-based society dropdown (executor/applicant see province + global)
    $societies = SocietyVisibilityHelper::getSocietiesForProjectForm($user);

    Log::info('ProjectController@create - Preparing data for view');
    return view('projects.Oldprojects.createProjects', compact(
        'users',
        'user',
        'developmentProjects',
        'societies',
        'predecessorBeneficiaries',
        'predecessorObjectives',
        'predecessorActivities',
        'predecessorSustainability',
        'predecessorBudget',
        'predecessorAttachments'
    ));
}
public function getProjectDetails($project_id)
{
    Log::info('ProjectController@getProjectDetails - Request received', [
        'project_id' => $project_id,
        'request_headers' => request()->headers->all(),
        'request_method' => request()->method()
    ]);

    try {
        Log::debug('ProjectController@getProjectDetails - Querying project', [
            'project_id' => $project_id
        ]);
        $project = Project::where('project_id', $project_id)
            ->with(['user', 'society', 'DPRSTBeneficiariesAreas', 'objectives.results', 'objectives.risks', 'objectives.activities.timeframes'])
            ->firstOrFail();

        Log::info('ProjectController@getProjectDetails - Project retrieved', [
            'project_id' => $project_id,
            'project_data' => $project->toArray()
        ]);

        $responseData = [
            'project_title' => $project->project_title,
            'society_id' => $project->society_id,
            'society_name' => optional($project->society)->name ?? $project->society_name,
            'president_name' => $project->president_name,
            'applicant_name' => $project->user ? $project->user->name : null,
            'applicant_mobile' => $project->user ? $project->user->phone : null,
            'applicant_email' => $project->user ? $project->user->email : null,
            'in_charge' => $project->in_charge,
            'in_charge_name' => $project->in_charge_name,
            'in_charge_mobile' => $project->in_charge_mobile,
            'in_charge_email' => $project->in_charge_email,
            'full_address' => $project->full_address,
            'overall_project_period' => $project->overall_project_period,
            'current_phase' => $project->current_phase,
            'commencement_month' => $project->commencement_month,
            'commencement_year' => $project->commencement_year,
            'overall_project_budget' => $project->overall_project_budget,
            // Key Information fields
            'initial_information' => $project->initial_information,
            'target_beneficiaries' => $project->target_beneficiaries,
            'general_situation' => $project->general_situation,
            'need_of_project' => $project->need_of_project,
            'economic_situation' => $project->economic_situation,
            'goal' => $project->goal,
            'beneficiaries_areas' => $project->DPRSTBeneficiariesAreas->map(function ($area) {
                return [
                    'project_area' => $area->project_area,
                    'category' => $area->category_beneficiary,
                    'direct' => $area->direct_beneficiaries,
                    'indirect' => $area->indirect_beneficiaries
                ];
            })->toArray(),
            'objectives' => $project->objectives->map(function ($objective) {
                return [
                    'objective_id' => $objective->objective_id,
                    'objective' => $objective->objective,
                    'results' => $objective->results->map(function ($result) {
                        return [
                            'result_id' => $result->result_id,
                            'result' => $result->result
                        ];
                    })->toArray(),
                    'risks' => $objective->risks->map(function ($risk) {
                        return [
                            'risk_id' => $risk->risk_id,
                            'risk' => $risk->risk
                        ];
                    })->toArray(),
                    'activities' => $objective->activities->map(function ($activity) {
                        return [
                            'activity_id' => $activity->activity_id,
                            'activity' => $activity->activity,
                            'verification' => $activity->verification,
                            'timeframes' => $activity->timeframes->map(function ($timeframe) {
                                return [
                                    'timeframe_id' => $timeframe->timeframe_id,
                                    'month' => $timeframe->month,
                                    'is_active' => $timeframe->is_active
                                ];
                            })->toArray()
                        ];
                    })->toArray()
                ];
            })->toArray()
        ];

        Log::info('ProjectController@getProjectDetails - Preparing response', [
            'project_id' => $project_id,
            'response_data' => $responseData
        ]);

        return response()->json($responseData, 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning('ProjectController@getProjectDetails - Project not found', [
            'project_id' => $project_id
        ]);
        return response()->json(['error' => 'Project not found'], 404);
    } catch (\Exception $e) {
        Log::error('ProjectController@getProjectDetails - Unexpected error', [
            'project_id' => $project_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Failed to fetch project details'], 500);
    }
}
public function store(StoreProjectRequest $request)
{
    // Phase 2 observability: request payload at entry
    Log::info('ProjectController@store - Entry', [
        'project_type' => $request->project_type,
        'save_as_draft' => $request->has('save_as_draft') ? $request->input('save_as_draft') : null,
        'has_iies_bname' => $request->has('iies_bname'),
    ]);

    Log::info('ProjectController@store - Transaction begin');
    DB::beginTransaction();
    Log::info('ProjectController@store - Transaction started');

    try {
        Log::info('ProjectController@store - Data received from form', [
            'project_type' => $request->project_type,
            'project_title' => $request->project_title,
            'society_name' => $request->society_name,
            'overall_project_period' => $request->overall_project_period,
            'current_phase' => $request->current_phase,
        ]);
        Log::info('Received Project Type:', ['project_type' => $request->project_type]);

        $project = $this->storeGeneralInfoAndMergeProjectId($request);

        $institutionalRedirect = $this->storeInstitutionalSections($request, $project);
        if ($institutionalRedirect !== null) {
            return $institutionalRedirect;
        }

        // Step 3: Handle key information (excluded for Individual project types)
        if (!in_array($request->project_type, ProjectType::getIndividualTypes())) {
            (new KeyInformationController())->store($request, $project);
        }

        Log::info('ProjectController@store - Before project type switch', [
            'project_type' => $request->project_type,
        ]);
        $handlers = $this->getProjectTypeStoreHandlers();
        $handler = $handlers[$request->project_type] ?? null;
        if ($handler !== null) {
            $handler($request, $project);
        } else {
            Log::warning('Unknown project type', ['project_type' => $request->project_type]);
        }

        Log::info('ProjectController@store - Transaction commit');
        DB::commit();
        return $this->applyPostCommitStatusAndRedirect($request, $project);
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        $this->logStoreRollback($e, 'ValidationException');
        throw $e;
    } catch (\Exception $e) {
        DB::rollBack();
        $this->logStoreRollback($e, 'Exception');
        Log::error('ProjectController@store - Error during project creation', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->withErrors(['error' => 'There was an error creating the project. Please try again.'])->withInput();
    }
}

private function storeGeneralInfoAndMergeProjectId(StoreProjectRequest $request): Project
{
    $project = (new GeneralInfoController())->store($request);
    Log::info('General project details stored', ['project_id' => $project->project_id]);
    $request->merge(['project_id' => $project->project_id]);
    return $project;
}

private function storeInstitutionalSections(StoreProjectRequest $request, Project $project): ?\Illuminate\Http\RedirectResponse
{
    if (!ProjectType::isInstitutional($request->project_type)) {
        return null;
    }
    $logicalFrameworkResponse = $this->logicalFrameworkController->store($request);
    if ($logicalFrameworkResponse instanceof \Illuminate\Http\RedirectResponse) {
        DB::rollBack();
        return $logicalFrameworkResponse;
    }
    $this->sustainabilityController->store($request, $project->project_id);
    (new BudgetController())->store($request, $project);
    if ($request->hasFile('file')) {
        (new AttachmentController())->store($request, $project);
    }
    return null;
}

private function storeIiesType(StoreProjectRequest $request, Project $project): void
{
    Log::info('Processing Individual - Initial - Educational support project type');
    $isIiesDraft = $request->has('save_as_draft') && $request->input('save_as_draft') == '1';
    $willCallPersonalInfoStore = $request->filled('iies_bname');
    $hasFinancialSupportData = $request->has('govt_eligible_scholarship') && $request->has('other_eligible_scholarship');
    Log::info('ProjectController@store - IIES case', [
        'is_draft' => $isIiesDraft,
        'iies_bname_filled' => $request->filled('iies_bname'),
        'will_call_personal_info_store' => $willCallPersonalInfoStore,
    ]);
    if (!$isIiesDraft) {
        $request->validate(['iies_bname' => 'required|string|max:255']);
    }
    if ($willCallPersonalInfoStore) {
        $this->iiesPersonalInfoController->store($request, $project->project_id);
    }
    $this->iiesFamilyWorkingMembersController->store($request, $project->project_id);
    $this->iiesImmediateFamilyDetailsController->store($request, $project->project_id);
    $this->iiesEducationBackgroundController->store($request, $project->project_id);
    if (!$isIiesDraft || $hasFinancialSupportData) {
        $this->iiesFinancialSupportController->store($request, $project->project_id);
    }
    $this->iiesAttachmentsController->store($request, $project->project_id);
    $this->iiesExpensesController->store($request, $project->project_id);
}

private function getProjectTypeStoreHandlers(): array
{
    return [
        ProjectType::RURAL_URBAN_TRIBAL => function ($request, $project) {
            Log::info('Processing Rural-Urban-Tribal project type');
            $this->eduRUTBasicInfoController->store($request, $project->project_id);
            $this->eduRUTTargetGroupController->store($request, $project->project_id);
            $this->eduRUTAnnexedTargetGroupController->store($request);
        },
        ProjectType::CHILD_CARE_INSTITUTION => function ($request, $project) {
            Log::info('Processing Child Care Institution project type');
            $this->cciAchievementsController->store($request, $project->project_id);
            $this->cciAgeProfileController->store($request, $project->project_id);
            $this->cciAnnexedTargetGroupController->store($request, $project->project_id);
            $this->cciEconomicBackgroundController->store($request, $project->project_id);
            $this->cciPersonalSituationController->store($request, $project->project_id);
            $this->cciPresentSituationController->store($request, $project->project_id);
            $this->cciRationaleController->store($request, $project->project_id);
            $this->cciStatisticsController->store($request, $project->project_id);
        },
        ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL => function ($request, $project) {
            Log::info('Processing Institutional Ongoing Group Educational proposal');
            $this->igeInstitutionInfoController->store($request, $project->project_id);
            $this->igeBeneficiariesSupportedController->store($request, $project->project_id);
            $this->igeNewBeneficiariesController->store($request, $project->project_id);
            $this->igeOngoingBeneficiariesController->store($request, $project->project_id);
            $this->igeBudgetController->store($request, $project->project_id);
            $this->igeDevelopmentMonitoringController->store($request, $project->project_id);
        },
        ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS => function ($request, $project) {
            Log::info('Processing Livelihood Development Projects');
            $this->ldpInterventionLogicController->store($request, $project->project_id);
            $this->ldpNeedAnalysisController->store($request, $project->project_id);
            $this->ldpTargetGroupController->store($request, $project->project_id);
        },
        ProjectType::RESIDENTIAL_SKILL_TRAINING => function ($request, $project) {
            Log::info('Processing Residential Skill Training Proposal 2');
            $this->rstBeneficiariesAreaController->store($request, $project->project_id);
            $this->rstGeographicalAreaController->store($request, $project->project_id);
            $this->rstInstitutionInfoController->store($request, $project->project_id);
            $this->rstTargetGroupAnnexureController->store($request, $project->project_id);
            $this->rstTargetGroupController->store($request, $project->project_id);
        },
        ProjectType::DEVELOPMENT_PROJECTS => function ($request, $project) {
            Log::info('Processing Development Projects');
            $this->rstBeneficiariesAreaController->store($request, $project->project_id);
        },
        ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL => function ($request, $project) {
            Log::info('Processing NEXT PHASE - DEVELOPMENT PROPOSAL');
            $this->rstBeneficiariesAreaController->store($request, $project->project_id);
        },
        ProjectType::CRISIS_INTERVENTION_CENTER => function ($request, $project) {
            Log::info('Processing PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER');
            $this->cicBasicInfoController->store($request, $project->project_id);
        },
        ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL => function ($request, $project) {
            Log::info('Processing Individual - Ongoing Educational Support project type');
            $this->iesPersonalInfoController->store($request, $project->project_id);
            $this->iesFamilyWorkingMembersController->store($request, $project->project_id);
            $this->iesImmediateFamilyDetailsController->store($request, $project->project_id);
            $this->iesEducationBackgroundController->store($request, $project->project_id);
            $this->iesExpensesController->store($request, $project->project_id);
            $this->iesAttachmentsController->store($request, $project->project_id);
        },
        ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION => function ($request, $project) {
            Log::info('Processing Individual - Livelihood Application');
            $this->ilpPersonalInfoController->store($request, $project->project_id);
            $this->ilpRevenueGoalsController->store($request, $project->project_id);
            $this->ilpStrengthWeaknessController->store($request, $project->project_id);
            $this->ilpRiskAnalysisController->store($request, $project->project_id);
            $this->ilpAttachedDocumentsController->store($request, $project->project_id);
            $this->ilpBudgetController->store($request, $project->project_id);
        },
        ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH => function ($request, $project) {
            Log::info('Processing Individual - Access to Health');
            $this->iahPersonalInfoController->store($request, $project->project_id);
            $this->iahEarningMembersController->store($request, $project->project_id);
            $this->iahHealthConditionController->store($request, $project->project_id);
            $this->iahSupportDetailsController->store($request, $project->project_id);
            $this->iahBudgetDetailsController->store($request, $project->project_id);
            $this->iahDocumentsController->store($request, $project->project_id);
        },
        ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL => function ($request, $project) {
            $this->storeIiesType($request, $project);
        },
    ];
}

private function logStoreRollback(\Throwable $e, string $context): void
{
    Log::info("ProjectController@store - Rollback ({$context})", [
        'exception_class' => get_class($e),
        'message' => $e->getMessage(),
    ]);
}

private function applyPostCommitStatusAndRedirect(StoreProjectRequest $request, Project $project): \Illuminate\Http\RedirectResponse
{
    if ($request->has('save_as_draft') && $request->input('save_as_draft') == '1') {
        $project->status = ProjectStatus::DRAFT;
        $project->save();
        Log::info('Project saved as draft', ['project_id' => $project->project_id]);
    } else {
        $project->status = ProjectStatus::DRAFT;
        $project->save();
    }
    Log::info('Project and all related data saved successfully', ['project_id' => $project->project_id]);
    if ($request->has('save_as_draft') && $request->input('save_as_draft') == '1') {
        return redirect()->route('projects.edit', $project->project_id)
            ->with('success', 'Project saved as draft. You can continue editing later.');
    }
    return redirect()->route('projects.index')->with('success', 'Project created successfully.');
}

public function show($project_id)
{
    try {
        Log::info('ProjectController@show - Starting show process', ['project_id' => $project_id]);
        Log::info('ProjectController@show - Fetching project data with relationships');
        // Eager load all relationships to prevent N+1 queries
        $project = Project::where('project_id', $project_id)
            ->with([
                'budgets',
                'attachments',
                'objectives.results',
                'objectives.risks',
                'objectives.activities.timeframes',
                'sustainabilities',
                'user',
                'society',
                'statusHistory.changedBy',
                'reports.accountDetails', // Load reports with account details for budget calculations
                // IIES (Individual - Initial - Educational support) so Show partials can use $project->iiesXXX
                'iiesPersonalInfo',
                'iiesFamilyWorkingMembers',
                'iiesImmediateFamilyDetails',
                'iiesEducationBackground',
                'iiesFinancialSupport',
                'iiesAttachments.files',
                'iiesExpenses.expenseDetails',
            ])
            ->firstOrFail();

        $user = Auth::user();
        Log::info('ProjectController@show - Project and user fetched', [
            'project_type' => $project->project_type,
            'user_id' => $user->id,
            'user_role' => $user->role,
            'project_status' => $project->status
        ]);

        // Province isolation + role-based view (ProjectPermissionHelper::canView)
        if (!ProjectPermissionHelper::canView($project, $user)) {
            Log::warning('ProjectController@show - Access denied', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'project_id' => $project_id,
                'project_status' => $project->status
            ]);
            abort(403, 'You do not have permission to view this project.');
        }

        // Initialize data array with defaults
        $data = [
            'project' => $project,
            'user' => $user,
            'basicInfo' => null,
            'targetGroups' => null,
            'annexedTargetGroups' => null,
            'achievements' => null,
            'ageProfile' => null,
            'annexedTargetGroup' => null,
            'economicBackground' => null,
            'personalSituation' => null,
            'presentSituation' => null,
            'rationale' => null,
            'statistics' => null,
            'interventionLogic' => null,
            'needAnalysis' => null,
            'LDPtargetGroups' => null,
            'IGEInstitutionInfo' => null,
            'beneficiariesSupported' => null,
            'newBeneficiaries' => null,
            'ongoingBeneficiaries' => null,
            'budget' => null,
            'developmentMonitoring' => null,
            'RSTBeneficiariesArea' => null,
            'RSTGeographicalArea' => null,
            'RSTInstitutionInfo' => null,
            'RSTTargetGroupAnnexure' => null,
            'RSTTargetGroup' => null,
            'IESpersonalInfo' => null,
            'IESfamilyWorkingMembers' => null,
            'IESimmediateFamilyDetails' => null,
            'IESEducationBackground' => null,
            'IESExpenses' => null,
            'IESAttachments' => null,
            'ILPPersonalInfo' => null,
            'ILPRevenueGoals' => null,
            'ILPStrengthWeakness' => null,
            'ILPRiskAnalysis' => null,
            'ILPAttachedDocuments' => null,
            'ILPBudgets' => null,
            'IAHPersonalInfo' => null,
            'IAHEarningMembers' => null,
            'IAHHealthCondition' => null,
            'IAHSupportDetails' => null,
            'IAHBudgetDetails' => null,
            'IAHDocuments' => null,
            'IIESPersonalInfo' => null,
            'IIESFamilyWorkingMembers' => null,
            'IIESImmediateFamilyDetails' => null,
            'IIESEducationBackground' => null,
            'IIESFinancialSupport' => null,
            'IIESAttachments' => null,
            'IIESExpenses' => null,
        ];

        // Handle project-specific data
        switch ($project->project_type) {
            case 'Rural-Urban-Tribal':
                Log::info('ProjectController@show - Fetching Rural-Urban-Tribal data');
                $data['basicInfo'] = $this->eduRUTBasicInfoController->show($project_id);
                $data['RUTtargetGroups'] = $this->eduRUTTargetGroupController->show($project_id);
                $data['annexedTargetGroups'] = $this->eduRUTAnnexedTargetGroupController->show($project_id);
                break;

            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                Log::info('ProjectController@show - Fetching CIC data');
                $data['basicInfo'] = $this->cicBasicInfoController->show($project->project_id);
                break;

            case 'CHILD CARE INSTITUTION':
                Log::info('ProjectController@show - Fetching CCI data');
                $data['achievements'] = $this->cciAchievementsController->show($project->project_id);
                $data['ageProfile'] = $this->cciAgeProfileController->show($project->project_id);
                $data['annexedTargetGroup'] = $this->cciAnnexedTargetGroupController->show($project->project_id);
                $data['economicBackground'] = $this->cciEconomicBackgroundController->show($project->project_id);
                $data['personalSituation'] = $this->cciPersonalSituationController->show($project->project_id);
                $data['presentSituation'] = $this->cciPresentSituationController->show($project->project_id);
                $data['rationale'] = $this->cciRationaleController->show($project->project_id);
                $data['statistics'] = $this->cciStatisticsController->show($project->project_id);
                break;

            case 'Institutional Ongoing Group Educational proposal':
                Log::info('ProjectController@show - Fetching IGE data');
                $data['IGEInstitutionInfo'] = $this->igeInstitutionInfoController->show($project->project_id);
                $data['beneficiariesSupported'] = $this->igeBeneficiariesSupportedController->show($project->project_id);
                $data['newBeneficiaries'] = $this->igeNewBeneficiariesController->show($project->project_id);
                $data['ongoingBeneficiaries'] = $this->igeOngoingBeneficiariesController->show($project->project_id);
                $data['budget'] = $this->igeBudgetController->show($project->project_id);
                $data['developmentMonitoring'] = $this->igeDevelopmentMonitoringController->show($project->project_id);
                break;

            case 'Livelihood Development Projects':
                Log::info('ProjectController@show - Fetching LDP data');
                $data['interventionLogic'] = $this->ldpInterventionLogicController->show($project_id);
                $data['needAnalysis'] = $this->ldpNeedAnalysisController->show($project_id);
                $data['LDPtargetGroups'] = $this->ldpTargetGroupController->show($project_id);
                break;

            case 'Residential Skill Training Proposal 2':
                Log::info('ProjectController@show - Fetching RST data');
                $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
                $data['RSTGeographicalArea'] = $this->rstGeographicalAreaController->show($project->project_id);
                $data['RSTInstitutionInfo'] = $this->rstInstitutionInfoController->show($project->project_id);
                $data['RSTTargetGroupAnnexure'] = $this->rstTargetGroupAnnexureController->show($project->project_id);
                $data['RSTTargetGroup'] = $this->rstTargetGroupController->show($project->project_id);
                break;

            case 'Development Projects':
                Log::info('ProjectController@show - Fetching Development Projects data');
                $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
                break;

            case 'NEXT PHASE - DEVELOPMENT PROPOSAL':
                Log::info('ProjectController@show - Fetching NEXT PHASE - DEVELOPMENT PROPOSAL data');
                $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
                break;

            case 'Individual - Ongoing Educational support':
                Log::info('ProjectController@show - Fetching IES data');
                $data['IESpersonalInfo'] = $this->iesPersonalInfoController->show($project->project_id);
                $data['IESfamilyWorkingMembers'] = $this->iesFamilyWorkingMembersController->show($project->project_id);
                $data['IESimmediateFamilyDetails'] = $this->iesImmediateFamilyDetailsController->show($project->project_id);
                $data['IESEducationBackground'] = $this->iesEducationBackgroundController->show($project->project_id);
                $data['IESExpenses'] = $this->iesExpensesController->show($project->project_id);
                $data['IESAttachments'] = $this->iesAttachmentsController->show($project->project_id) ?? [];
                Log::info('ProjectController@show - IES attachments fetched', [
                    'attachments' => $data['IESAttachments']
                ]);
                break;

            case 'Individual - Livelihood Application':
                Log::info('ProjectController@show - Fetching ILP data');
                $data['ILPPersonalInfo'] = $this->ilpPersonalInfoController->show($project_id) ?? [];
                $data['ILPRevenueGoals'] = $this->ilpRevenueGoalsController->show($project_id) ?? [];
                $data['ILPStrengthWeakness'] = $this->ilpStrengthWeaknessController->show($project_id) ?? [];
                $data['ILPRiskAnalysis'] = $this->ilpRiskAnalysisController->show($project_id) ?? [];
                $data['ILPAttachedDocuments'] = $this->ilpAttachedDocumentsController->show($project_id) ?? [];
                $data['ILPBudgets'] = $this->ilpBudgetController->show($project_id) ?? collect([]);
                Log::info('ProjectController@show - ILP data fetched', [
                    'personal_info' => $data['ILPPersonalInfo'],
                    'attachments' => $data['ILPAttachedDocuments']
                ]);
                break;

            case 'Individual - Access to Health':
                Log::info('ProjectController@show - Fetching IAH data');
                $data['IAHPersonalInfo'] = $this->iahPersonalInfoController->show($project->project_id);
                $data['IAHEarningMembers'] = $this->iahEarningMembersController->show($project->project_id);
                $data['IAHHealthCondition'] = $this->iahHealthConditionController->show($project->project_id);
                $data['IAHSupportDetails'] = $this->iahSupportDetailsController->show($project->project_id);
                $data['IAHBudgetDetails'] = $this->iahBudgetDetailsController->show($project->project_id);
                $data['IAHDocuments'] = $this->iahDocumentsController->show($project->project_id) ?? [];
                Log::info('ProjectController@show - IAH documents fetched', [
                    'documents' => $data['IAHDocuments']
                ]);
                break;

            case 'Individual - Initial - Educational support':
                Log::info('ProjectController@show - Fetching IIES data');
                $data['IIESPersonalInfo'] = $this->iiesPersonalInfoController->show($project->project_id);
                $data['IIESFamilyWorkingMembers'] = $this->iiesFamilyWorkingMembersController->show($project->project_id);
                $data['IIESImmediateFamilyDetails'] = $this->iiesImmediateFamilyDetailsController->show($project->project_id);
                $data['IIESEducationBackground'] = $this->iiesEducationBackgroundController->show($project->project_id);
                $data['IIESFinancialSupport'] = $this->iiesFinancialSupportController->show($project->project_id);
                $data['IIESAttachments'] = $this->iiesAttachmentsController->show($project_id) ?? [];
                $data['IIESExpenses'] = $this->iiesExpensesController->show($project_id) ?? [];
                Log::info('ProjectController@show - IIES attachments fetched', [
                    'attachments' => $data['IIESAttachments']
                ]);
                break;

            default:
                Log::warning('ProjectController@show - Unknown project type', [
                    'project_type' => $project->project_type
                ]);
                break;
        }

        // For non-individual types, attachments are already eager-loaded in $project->attachments
        if (!in_array($project->project_type, [
            'Individual - Ongoing Educational support',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support'
        ])) {
            Log::info('ProjectController@show - Using default attachments from project model', [
                'attachments_count' => $project->attachments->count()
            ]);
        }

        // Phase 1: Resolved fund fields for display. Always use ProjectFinancialResolver as single
        // source of truth. Read-only, no writes.
        \Log::info('Controller Debug Before Resolve', [
            'project_id' => $project->project_id,
            'relation_loaded' => $project->relationLoaded('iiesExpenses'),
            'relation_value_is_null' => is_null($project->iiesExpenses),
        ]);
        $resolver = app(ProjectFinancialResolver::class);
        $data['resolvedFundFields'] = $resolver->resolve($project);
        \Log::info('Resolved Fund Fields Output', $data['resolvedFundFields']);

        Log::info('ProjectController@show - Data prepared for view', [
            'project_id' => $project_id,
            'data_keys' => array_keys($data)
        ]);

        return view('projects.Oldprojects.show', $data);
    } catch (\Exception $e) {
        return $this->handleException($e, 'ProjectController@show', $this->getStandardErrorMessage('load', 'project'), [
            'project_id' => $project_id,
        ]);
    }
}



public function edit($project_id)
{
    Log::info('ProjectController@edit - Starting edit process', ['project_id' => $project_id]);

    try {
        Log::info('ProjectController@edit - Fetching project data with relationships');
        $project = Project::where('project_id', $project_id)
            ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
            ->firstOrFail();

        Log::info('ProjectController@edit - Project fetched', [
            'project_type' => $project->project_type,
            'attachments_count' => $project->attachments->count()
        ]);

        $budgetsForEdit = $project->budgets
            ->where('phase', (int) ($project->current_phase ?? 1))
            ->values();

        $user = Auth::user();

        // Always fetch development projects for predecessor selection (for all project types)
        // Include both DEVELOPMENT_PROJECTS and NEXT_PHASE_DEVELOPMENT_PROPOSAL
        $developmentProjects = ProjectQueryService::getProjectsForUserQuery($user)
            ->whereIn('project_type', [
                ProjectType::DEVELOPMENT_PROJECTS,
                ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL
            ])
            ->orderBy('project_id', 'desc')
            ->get();

        $users = User::all();
        Log::info('ProjectController@edit - User and development projects fetched', [
            'user_id' => $user->id,
            'development_projects_count' => $developmentProjects->count()
        ]);

        // Check if project can be edited - use ProjectPermissionHelper for consistent checking
        // This handles all status checks, ownership checks, and role-based permissions
        if (!ProjectPermissionHelper::canEdit($project, $user)) {
            Log::warning('ProjectController@edit - Attempt to edit project without permission', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'project_id' => $project_id,
                'project_status' => $project->status,
                'project_user_id' => $project->user_id,
                'project_in_charge' => $project->in_charge
            ]);
            return redirect()->route('projects.show', $project_id)
                ->with('error', 'You do not have permission to edit this project.');
        }

        // Initialize variables for different project types
        $basicInfo = null;
        $targetGroups = null;
        $annexedTargetGroups = null;
        $cicBasicInfo = null;
        $achievements = null;
        $ageProfile = null;
        $targetGroup = null;
        $economicBackground = null;
        $personalSituation = null;
        $presentSituation = null;
        $rationale = null;
        $statistics = null;
        $IGEinstitutionInfo = null;
        $beneficiariesSupported = collect();
        $newBeneficiaries = null;
        $ongoingBeneficiaries = null;
        $budget = null;
        $developmentMonitoring = null;
        $interventionLogic = null;
        $needAnalysis = null;
        $LDPtargetGroups = null;
        $beneficiariesArea = null;
        $geographicalArea = null;
        $RSTinstitutionInfo = null;
        $RSTtargetGroupAnnexure = null;
        $RSTtargetGroup = null;
        $IESpersonalInfo = null;
        $IESfamilyWorkingMembers = null;
        $IESimmediateFamilyDetails = null;
        $IESEducationBackground = null;
        $IESExpenses = null;
        $IESAttachments = null;
        $ILPPersonalInfo = null;
        $ILPRevenueGoals = null;
        $ILPStrengthWeakness = null;
        $ILPRiskAnalysis = null;
        $ILPAttachedDocuments = null;
        $ILPBudget = null;
        $IAHPersonalInfo = null;
        $IAHEarningMembers = null;
        $IAHHealthCondition = null;
        $IAHSupportDetails = null;
        $IAHBudgetDetails = null;
        $IAHDocuments = null;
        $IIESPersonalInfo = null;
        $IIESFamilyWorkingMembers = null;
        $IIESImmediateFamilyDetails = null;
        $IIESEducationBackground = null;
        $IIESFinancialSupport = null;
        $IIESAttachments = null;
        $iiesExpenses = null;

        // Handle specific project types
        switch ($project->project_type) {
            case 'Rural-Urban-Tribal':
                Log::info('ProjectController@edit - Fetching Rural-Urban-Tribal data');
                $basicInfo = $this->eduRUTBasicInfoController->edit($project->project_id);
                $targetGroups = $this->eduRUTTargetGroupController->edit($project->project_id);
                $annexedTargetGroups = $this->eduRUTAnnexedTargetGroupController->edit($project->project_id);
                break;

            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                Log::info('ProjectController@edit - Fetching CIC data');
                $cicBasicInfo = $this->cicBasicInfoController->edit($project->project_id);
                break;

            case 'CHILD CARE INSTITUTION':
                Log::info('ProjectController@edit - Fetching CCI data');
                $achievements = $this->cciAchievementsController->edit($project->project_id);
                $ageProfile = $this->cciAgeProfileController->edit($project->project_id);
                $targetGroup = $this->cciAnnexedTargetGroupController->edit($project->project_id);
                $economicBackground = $this->cciEconomicBackgroundController->edit($project->project_id);
                $personalSituation = $this->cciPersonalSituationController->edit($project->project_id);
                $presentSituation = $this->cciPresentSituationController->edit($project->project_id);
                $rationale = $this->cciRationaleController->edit($project->project_id);
                $statistics = $this->cciStatisticsController->edit($project->project_id);
                break;

            case 'Institutional Ongoing Group Educational proposal':
                Log::info('ProjectController@edit - Fetching IGE data');
                $IGEinstitutionInfo = $this->igeInstitutionInfoController->edit($project->project_id);
                $beneficiariesSupported = $this->igeBeneficiariesSupportedController->edit($project->project_id);
                $newBeneficiaries = $this->igeNewBeneficiariesController->edit($project->project_id);
                $ongoingBeneficiaries = $this->igeOngoingBeneficiariesController->edit($project->project_id);
                $budget = $this->igeBudgetController->edit($project->project_id);
                $developmentMonitoring = $this->igeDevelopmentMonitoringController->edit($project->project_id);
                break;

            case 'Livelihood Development Projects':
                Log::info('ProjectController@edit - Fetching LDP data');
                $interventionLogic = $this->ldpInterventionLogicController->edit($project_id);
                $needAnalysis = $this->ldpNeedAnalysisController->edit($project_id);
                $LDPtargetGroups = $this->ldpTargetGroupController->edit($project_id);
                break;

            case 'Residential Skill Training Proposal 2':
                Log::info('ProjectController@edit - Fetching RST data');
                $beneficiariesArea = $this->rstBeneficiariesAreaController->edit($project->project_id);
                $geographicalArea = $this->rstGeographicalAreaController->edit($project->project_id);
                $RSTinstitutionInfo = $this->rstInstitutionInfoController->edit($project->project_id);
                $RSTtargetGroupAnnexure = $this->rstTargetGroupAnnexureController->edit($project->project_id);
                $RSTtargetGroup = $this->rstTargetGroupController->edit($project->project_id);
                break;

            case 'Development Projects':
                Log::info('ProjectController@edit - Fetching Development Projects data');
                $beneficiariesArea = $this->rstBeneficiariesAreaController->edit($project->project_id);
                break;

            case 'Individual - Ongoing Educational support':
                Log::info('ProjectController@edit - Fetching IES data');
                $IESpersonalInfo = $this->iesPersonalInfoController->edit($project->project_id);
                $IESfamilyWorkingMembers = $this->iesFamilyWorkingMembersController->edit($project->project_id);
                $IESimmediateFamilyDetails = $this->iesImmediateFamilyDetailsController->edit($project->project_id);
                $IESEducationBackground = $this->iesEducationBackgroundController->edit($project->project_id);
                $IESExpenses = $this->iesExpensesController->edit($project->project_id);
                $IESAttachments = $this->iesAttachmentsController->edit($project->project_id) ?? [];
                Log::info('ProjectController@edit - IES attachments fetched', [
                    'attachments' => $IESAttachments
                ]);
                break;

            case 'Individual - Livelihood Application':
                Log::info('ProjectController@edit - Fetching ILP data');
                $ILPPersonalInfo = $this->ilpPersonalInfoController->edit($project->project_id) ?? [];
                $ILPRevenueGoals = $this->ilpRevenueGoalsController->edit($project->project_id) ?? [];
                $ILPStrengthWeakness = $this->ilpStrengthWeaknessController->edit($project->project_id) ?? [];
                $ILPRiskAnalysis = $this->ilpRiskAnalysisController->edit($project->project_id) ?? [];
                $ILPAttachedDocuments = $this->ilpAttachedDocumentsController->edit($project->project_id) ?? [];
                $ILPBudget = $this->ilpBudgetController->edit($project->project_id) ?? collect([]);
                Log::info('ProjectController@edit - ILP attachments fetched', [
                    'attachments' => $ILPAttachedDocuments
                ]);
                break;

            case 'Individual - Access to Health':
                Log::info('ProjectController@edit - Fetching IAH data');
                $IAHPersonalInfo = $this->iahPersonalInfoController->edit($project->project_id);
                $IAHEarningMembers = $this->iahEarningMembersController->edit($project->project_id);
                $IAHHealthCondition = $this->iahHealthConditionController->edit($project->project_id);
                $IAHSupportDetails = $this->iahSupportDetailsController->edit($project->project_id);
                $IAHBudgetDetails = $this->iahBudgetDetailsController->edit($project->project_id);
                $IAHDocuments = $this->iahDocumentsController->edit($project->project_id) ?? [];
                Log::info('ProjectController@edit - IAH documents fetched', [
                    'documents' => $IAHDocuments
                ]);
                break;

            case 'Individual - Initial - Educational support':
                Log::info('ProjectController@edit - Fetching IIES data');
                $IIESPersonalInfo = $this->iiesPersonalInfoController->edit($project->project_id);
                $IIESFamilyWorkingMembers = $this->iiesFamilyWorkingMembersController->edit($project->project_id);
                $IIESImmediateFamilyDetails = $this->iiesImmediateFamilyDetailsController->edit($project->project_id);
                $IIESEducationBackground = $this->iiesEducationBackgroundController->edit($project->project_id);
                $IIESFinancialSupport = $this->iiesFinancialSupportController->edit($project->project_id);
                $IIESAttachments = $this->iiesAttachmentsController->edit($project->project_id) ?? [];
                $iiesExpenses = $this->iiesExpensesController->edit($project->project_id) ?? [];
                Log::info('ProjectController@edit - IIES attachments fetched', [
                    'attachments' => $IIESAttachments
                ]);
                break;

            default:
                Log::warning('ProjectController@edit - Unknown project type', [
                    'project_type' => $project->project_type
                ]);
                break;
        }

        // For non-individual types, attachments are available via $project->attachments
        if (!in_array($project->project_type, [
            'Individual - Ongoing Educational support',
            'Individual - Livelihood Application',
            'Individual - Access to Health',
            'Individual - Initial - Educational support'
        ])) {
            Log::info('ProjectController@edit - Using default attachments from project model', [
                'attachments_count' => $project->attachments->count()
            ]);
        }

        Log::info('ProjectController@edit - Preparing data for view', [
            'project_id' => $project_id
        ]);

        // Phase 3: Budget locked when project is approved (restrict flag on)
        $budgetLockedByApproval = !BudgetSyncGuard::canEditBudget($project);

        // Financial semantics UI: resolved fund fields for display (no raw amount_sanctioned)
        $resolver = app(ProjectFinancialResolver::class);
        $resolvedFundFields = $resolver->resolve($project);

        // Phase 5B1: Role-based society dropdown
        $societies = SocietyVisibilityHelper::getSocietiesForProjectForm($user);

        return view('projects.Oldprojects.edit', compact(
            'project', 'developmentProjects', 'user', 'users', 'societies', 'resolvedFundFields',
            'basicInfo', 'targetGroups', 'annexedTargetGroups', 'cicBasicInfo',
            'achievements', 'ageProfile', 'targetGroup', 'economicBackground',
            'personalSituation', 'presentSituation', 'rationale', 'statistics',
            'IGEinstitutionInfo', 'beneficiariesSupported', 'newBeneficiaries',
            'ongoingBeneficiaries', 'budget', 'developmentMonitoring',
            'interventionLogic', 'needAnalysis', 'LDPtargetGroups',
            'beneficiariesArea', 'geographicalArea', 'RSTinstitutionInfo',
            'RSTtargetGroupAnnexure', 'RSTtargetGroup',
            'IESpersonalInfo', 'IESfamilyWorkingMembers',
            'IESimmediateFamilyDetails', 'IESEducationBackground',
            'IESExpenses', 'IESAttachments',
            'ILPPersonalInfo', 'ILPRevenueGoals', 'ILPStrengthWeakness',
            'ILPRiskAnalysis', 'ILPAttachedDocuments', 'ILPBudget',
            'IAHPersonalInfo', 'IAHEarningMembers', 'IAHHealthCondition',
            'IAHSupportDetails', 'IAHBudgetDetails', 'IAHDocuments',
            'IIESPersonalInfo', 'IIESFamilyWorkingMembers',
            'IIESImmediateFamilyDetails', 'IIESEducationBackground',
            'IIESFinancialSupport', 'IIESAttachments', 'iiesExpenses',
            'budgetsForEdit', 'budgetLockedByApproval'
        ));
    } catch (\Exception $e) {
        Log::error('ProjectController@edit - Error retrieving project data', [
            'project_id' => $project_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->withErrors(['error' => 'Unable to retrieve project data.']);
    }
}




    public function update(UpdateProjectRequest $request, $project_id)
    {
        // Authorization already checked by UpdateProjectRequest
        // Validation already done by UpdateProjectRequest

        Log::info('ProjectController@update - Starting update process', [
            'project_id' => $project_id,
            'project_type' => $request->project_type,
            'project_title' => $request->project_title,
            'society_id' => $request->society_id,
        ]);

        // Force `phases` to be an array if it doesn't exist
        $request->merge(['phases' => $request->input('phases', [])]);

        DB::beginTransaction();
        try {
            Log::info('ProjectController@update - Fetching project from database');
            $project = Project::where('project_id', $project_id)->firstOrFail();

            // Status and permission checks already done by UpdateProjectRequest
            // No need to check again here

            Log::info('ProjectController@update - Project fetched', [
                'project_type' => $project->project_type
            ]);

            // Update general project details
            Log::info('ProjectController@update - Updating general info');
            $project = (new GeneralInfoController())->update($request, $project->project_id);
            if (!in_array($project->project_type, ProjectType::getIndividualTypes())) {
                (new KeyInformationController())->update($request, $project);
            }
            Log::info('ProjectController@update - General info and key information updated');

            // Handle common sections for non-individual project types
            // Use ProjectType helper method instead of hard-coded array
            if (ProjectType::isInstitutional($project->project_type)) {
                Log::info('ProjectController@update - Updating common sections for non-individual type');
                $this->logicalFrameworkController->update($request, $project->project_id);
                $this->sustainabilityController->update($request, $project->project_id);
                (new BudgetController())->update($request, $project);
                if ($request->hasFile('file')) {
                    Log::info('ProjectController@update - Updating default attachment');
                    (new AttachmentController())->update($request, $project->project_id);
                } else {
                    Log::info('ProjectController@update - No new attachment uploaded for default partial');
                }
            }

            // Handle project type-specific updates
            switch ($project->project_type) {
                case ProjectType::RURAL_URBAN_TRIBAL:
                    Log::info('ProjectController@update - Updating Rural-Urban-Tribal data');
                    $this->eduRUTBasicInfoController->update($request, $project->project_id);
                    $this->eduRUTTargetGroupController->update($request, $project->project_id);
                    $this->eduRUTAnnexedTargetGroupController->update($request, $project->project_id);
                    break;

                case ProjectType::CRISIS_INTERVENTION_CENTER:
                    Log::info('ProjectController@update - Updating CIC data');
                    $this->cicBasicInfoController->update($request, $project->project_id);
                    break;

                case ProjectType::CHILD_CARE_INSTITUTION:
                    Log::info('ProjectController@update - Updating CCI data');
                    $this->cciAchievementsController->update($request, $project->project_id);
                    $this->cciAgeProfileController->update($request, $project->project_id);
                    $this->cciAnnexedTargetGroupController->update($request, $project->project_id);
                    $this->cciEconomicBackgroundController->update($request, $project->project_id);
                    $this->cciPersonalSituationController->update($request, $project->project_id);
                    $this->cciPresentSituationController->update($request, $project->project_id);
                    $this->cciRationaleController->update($request, $project->project_id);
                    $this->cciStatisticsController->update($request, $project->project_id);
                    break;

                case ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL:
                    Log::info('ProjectController@update - Updating IGE data');
                    $this->igeInstitutionInfoController->update($request, $project->project_id);
                    $this->igeBeneficiariesSupportedController->update($request, $project->project_id);
                    $this->igeNewBeneficiariesController->update($request, $project->project_id);
                    $this->igeOngoingBeneficiariesController->update($request, $project->project_id);
                    $this->igeBudgetController->update($request, $project->project_id);
                    $this->igeDevelopmentMonitoringController->update($request, $project->project_id);
                    break;

                case ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS:
                    Log::info('ProjectController@update - Updating LDP data');
                    $this->ldpInterventionLogicController->update($request, $project->project_id);
                    $this->ldpNeedAnalysisController->update($request, $project->project_id);
                    $this->ldpTargetGroupController->update($request, $project->project_id);
                    break;

                case ProjectType::RESIDENTIAL_SKILL_TRAINING:
                    Log::info('ProjectController@update - Updating RST data');
                    $this->rstBeneficiariesAreaController->update($request, $project->project_id);
                    $this->rstGeographicalAreaController->update($request, $project->project_id);
                    $this->rstInstitutionInfoController->update($request, $project->project_id);
                    $this->rstTargetGroupAnnexureController->update($request, $project->project_id);
                    $this->rstTargetGroupController->update($request, $project->project_id);
                    break;

                case ProjectType::DEVELOPMENT_PROJECTS:
                    Log::info('ProjectController@update - Updating Development Projects data');
                    $this->rstBeneficiariesAreaController->update($request, $project->project_id);
                    break;

                case ProjectType::NEXT_PHASE_DEVELOPMENT_PROPOSAL:
                    Log::info('ProjectController@update - Updating NEXT PHASE - DEVELOPMENT PROPOSAL data');
                    $this->rstBeneficiariesAreaController->update($request, $project->project_id);
                    break;

                case ProjectType::INDIVIDUAL_ONGOING_EDUCATIONAL:
                    Log::info('ProjectController@update - Updating IES data');
                    $this->iesPersonalInfoController->update($request, $project->project_id);
                    $this->iesFamilyWorkingMembersController->update($request, $project->project_id);
                    $this->iesImmediateFamilyDetailsController->update($request, $project->project_id);
                    $this->iesEducationBackgroundController->update($request, $project->project_id);
                    $this->iesExpensesController->update($request, $project->project_id);
                    $this->iesAttachmentsController->update($request, $project->project_id);
                    break;

                case ProjectType::INDIVIDUAL_LIVELIHOOD_APPLICATION:
                    Log::info('ProjectController@update - Updating ILP data');
                    $this->ilpPersonalInfoController->update($request, $project_id);
                    $this->ilpRevenueGoalsController->update($request, $project_id);
                    $this->ilpStrengthWeaknessController->update($request, $project_id);
                    $this->ilpRiskAnalysisController->update($request, $project_id);
                    $this->ilpAttachedDocumentsController->update($request, $project_id);
                    $this->ilpBudgetController->update($request, $project_id);
                    break;

                case ProjectType::INDIVIDUAL_ACCESS_TO_HEALTH:
                    Log::info('ProjectController@update - Updating IAH data');
                    $this->iahPersonalInfoController->update($request, $project->project_id);
                    $this->iahEarningMembersController->update($request, $project->project_id);
                    $this->iahHealthConditionController->update($request, $project->project_id);
                    $this->iahSupportDetailsController->update($request, $project->project_id);
                    $this->iahBudgetDetailsController->update($request, $project->project_id);
                    $this->iahDocumentsController->update($request, $project->project_id);
                    break;

                case ProjectType::INDIVIDUAL_INITIAL_EDUCATIONAL:
                    Log::info('ProjectController@update - Updating IIES data');
                    $this->iiesPersonalInfoController->update($request, $project->project_id);
                    $this->iiesFamilyWorkingMembersController->update($request, $project->project_id);
                    $this->iiesImmediateFamilyDetailsController->update($request, $project->project_id);
                    $this->iiesEducationBackgroundController->update($request, $project->project_id);
                    $this->iiesFinancialSupportController->update($request, $project->project_id);
                    $this->iiesAttachmentsController->update($request, $project->project_id);
                    $this->iiesExpensesController->update($request, $project->project_id);
                    break;

                default:
                    Log::warning('ProjectController@update - Unknown project type', [
                        'project_type' => $project->project_type
                    ]);
                    break;
            }

            DB::commit();

            // Refresh project to get latest data
            $project->refresh();

            // When saving as draft, explicitly keep status DRAFT
            if ($request->boolean('save_as_draft')) {
                $project->status = \App\Constants\ProjectStatus::DRAFT;
                $project->save();
            }

            // Log activity update
            $user = Auth::user();
            ActivityHistoryService::logProjectUpdate($project, $user, 'Project details updated');

            Log::info('ProjectController@update - Project updated successfully', [
                'project_id' => $project->project_id
            ]);

            return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@update - Error during update', [
                'project_id' => $project_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
        }
    }



    /**
     * Soft delete (move to trash). No child data or files are removed.
     * Child cleanup runs only on forceDelete() via Project::forceDeleting.
     */
    public function destroy($project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $result = app(ProjectLifecycleService::class)->trash($project, Auth::user());

        if ($result === 'already_trashed') {
            return redirect()->route('projects.index')->with('info', 'Project is already in trash.');
        }

        return redirect()->route('projects.index')->with('success', 'Project moved to trash successfully.');
    }

    /**
     * List trashed projects. Province and role scoped.
     * Executors/Applicants see only own trashed; Provincial sees province; Coordinator/General/Admin see all.
     */
    public function trashIndex()
    {
        $user = auth()->user();
        $projects = ProjectQueryService::getTrashedProjectsQuery($user)
            ->with(['society', 'user'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(15);

        return view('projects.trash.index', compact('projects', 'user'));
    }

    /**
     * Restore a soft-deleted project (Move to Trash reversal).
     * Only allowed when user has canDelete() permission (same as trash).
     */
    public function restore($project_id)
    {
        $project = Project::withTrashed()->where('project_id', $project_id)->firstOrFail();
        $result = app(ProjectLifecycleService::class)->restore($project, Auth::user());

        if ($result === 'already_active') {
            return redirect()->route('projects.trash.index')->with('info', 'Project is not in trash.');
        }

        return redirect()->route('projects.trash.index')->with('success', 'Project restored successfully.');
    }

    /**
     * Permanently delete a trashed project (admin only). Removes project and all related data.
     */
    public function forceDelete($project_id)
    {
        $project = Project::withTrashed()->where('project_id', $project_id)->firstOrFail();

        app(ProjectLifecycleService::class)->forceDelete($project, Auth::user());

        return redirect()->route('projects.trash.index')->with('success', 'Project permanently deleted.');
    }

    // 9122024
    public function listProjects(Request $request)
{
    $user = Auth::user();
    $query = Project::query()->with('user');

    // Province isolation: only projects in user's province
    if ($user->province_id !== null) {
        $query->where('province_id', $user->province_id);
    }

    // If Provincial: show only projects whose user's parent_id = provincial->id
    if ($user->role === 'provincial') {
        $query->whereHas('user', function($q) use ($user) {
            $q->where('parent_id', $user->id);
        });
    }

    // Apply filters if provided (e.g., project_type)
    if ($request->filled('project_type')) {
        $query->where('project_type', $request->project_type);
    }

    // You can add more filters here as needed

    $projects = $query->get();

    return view('projects.Coord-Prov-ProjectList', compact('projects'));
}
// Status
public function submitToProvincial(SubmitProjectRequest $request, $project_id)
{
    // Authorization already checked by SubmitProjectRequest
    // Validation already done by SubmitProjectRequest

    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    try {
        ProjectStatusService::submitToProvincial($project, $user);
        return redirect()->back()->with('success', 'Project submitted to Provincial successfully.');
    } catch (Exception $e) {
        Log::error('Error submitting project to provincial', [
            'project_id' => $project_id,
            'error' => $e->getMessage(),
        ]);
        return redirect()->back()->withErrors(['error' => $e->getMessage()]);
    }
}

// Approved Projects for Executors
public function approvedProjects()
{
    $user = Auth::user();

    // Fetch approved projects where the user is either the owner or the in-charge
    $projects = ProjectQueryService::getApprovedProjectsForUser($user)
        ->sortBy(['project_id', 'user_id'])
        ->values();

    return view('projects.Oldprojects.approved', compact('projects', 'user'));
}

/**
 * Mark project as completed
 *
 * @param string $project_id
 * @return \Illuminate\Http\RedirectResponse
 */
public function markAsCompleted($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Check permission - only owner or in-charge can mark as completed
    if (!ProjectPermissionHelper::isOwnerOrInCharge($project, $user)) {
        Log::warning('Unauthorized attempt to mark project as completed', [
            'project_id' => $project_id,
            'user_id' => $user->id,
            'user_role' => $user->role
        ]);
        abort(403, 'You do not have permission to mark this project as completed.');
    }

    // Check if project is approved
    if (!ProjectStatus::isApproved($project->status)) {
        return redirect()->back()
            ->withErrors([
                'error' => 'Only approved projects can be marked as completed.'
            ]);
    }

    // Check if already completed
    if ($project->is_completed) {
        return redirect()->back()
            ->with('info', 'This project is already marked as completed.');
    }

    // Check if eligible (10+ months elapsed)
    if (!ProjectPhaseService::isEligibleForCompletion($project)) {
        $monthsElapsed = ProjectPhaseService::getMonthsElapsedInCurrentPhase($project);
        $phaseInfo = ProjectPhaseService::getPhaseInfo($project);

        return redirect()->back()
            ->withErrors([
                'error' => "Project cannot be marked as completed. " .
                          "Only {$monthsElapsed} months have elapsed in the current phase. " .
                          "Minimum 10 months required. " .
                          "Project will be eligible for completion in " .
                          (10 - $monthsElapsed) . " more month(s)."
            ]);
    }

    // Mark as completed
    $project->completed_at = now();
    $project->save();

    Log::info('Project marked as completed', [
        'project_id' => $project->project_id,
        'project_title' => $project->project_title,
        'user_id' => $user->id,
        'user_role' => $user->role,
        'completed_at' => $project->completed_at,
        'phase_info' => ProjectPhaseService::getPhaseInfo($project)
    ]);

    return redirect()->back()
        ->with('success', 'Project marked as completed successfully.');
}

}
