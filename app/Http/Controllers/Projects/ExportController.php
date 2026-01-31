<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\RST\ProjectDPRSTBeneficiariesArea;
use App\Models\OldProjects\RST\ProjectRSTInstitutionInfo;
use App\Models\OldProjects\RST\ProjectRSTTargetGroup;
use App\Models\OldProjects\RST\ProjectRSTTargetGroupAnnexure;
use App\Models\OldProjects\RST\ProjectRSTGeographicalArea;
// CCI Models
use App\Models\OldProjects\CCI\ProjectCCIAchievements;
use App\Models\OldProjects\CCI\ProjectCCIAgeProfile;
use App\Models\OldProjects\CCI\ProjectCCIAnnexedTargetGroup;
use App\Models\OldProjects\CCI\ProjectCCIEconomicBackground;
use App\Models\OldProjects\CCI\ProjectCCIPersonalSituation;
use App\Models\OldProjects\CCI\ProjectCCIPresentSituation;
use App\Models\OldProjects\CCI\ProjectCCIRationale;
use App\Models\OldProjects\CCI\ProjectCCIStatistics;
// IGE Models
use App\Models\OldProjects\IGE\ProjectIGEInstitutionInfo;
use App\Models\OldProjects\IGE\ProjectIGEBeneficiariesSupported;
use App\Models\OldProjects\IGE\ProjectIGENewBeneficiaries;
use App\Models\OldProjects\IGE\ProjectIGEOngoingBeneficiaries;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use App\Models\OldProjects\IGE\ProjectIGEDevelopmentMonitoring;
// LDP Models
use App\Models\OldProjects\LDP\ProjectLDPInterventionLogic;
use App\Models\OldProjects\LDP\ProjectLDPNeedAnalysis;
use App\Models\OldProjects\LDP\ProjectLDPTargetGroup;
// IES Models
use App\Models\OldProjects\IES\ProjectIESPersonalInfo;
use App\Models\OldProjects\IES\ProjectIESFamilyWorkingMembers;
use App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails;
use App\Models\OldProjects\IES\ProjectIESEducationBackground;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use App\Models\OldProjects\IES\ProjectIESAttachments;
// ILP Models
use App\Models\OldProjects\ILP\ProjectILPPersonalInfo;
use App\Models\OldProjects\ILP\ProjectILPRevenuePlanItem;
use App\Models\OldProjects\ILP\ProjectILPRevenueIncome;
use App\Models\OldProjects\ILP\ProjectILPRevenueExpense;
use App\Models\OldProjects\ILP\ProjectILPBusinessStrengthWeakness;
use App\Models\OldProjects\ILP\ProjectILPRiskAnalysis;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use App\Models\OldProjects\ILP\ProjectILPBudget;
// IAH Models
use App\Models\OldProjects\IAH\ProjectIAHPersonalInfo;
use App\Models\OldProjects\IAH\ProjectIAHEarningMembers;
use App\Models\OldProjects\IAH\ProjectIAHHealthCondition;
use App\Models\OldProjects\IAH\ProjectIAHSupportDetails;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
// IIES Models
use App\Models\OldProjects\IIES\ProjectIIESPersonalInfo;
use App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers;
use App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails;
use App\Models\OldProjects\IIES\ProjectIIESEducationBackground;
use App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport;
use App\Models\OldProjects\IIES\ProjectIIESAttachments;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
// EduRUT Models
use App\Models\OldProjects\ProjectEduRUTBasicInfo;
use App\Models\OldProjects\ProjectEduRUTTargetGroup;
use App\Models\OldProjects\ProjectEduRUTAnnexedTargetGroup;
// CIC Models
use App\Models\OldProjects\ProjectCICBasicInfo;
use App\Models\User;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Constants\ProjectStatus;
use App\Helpers\ProjectPermissionHelper;
use Mpdf\Mpdf;
// Controller imports
use App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController;
use App\Http\Controllers\Projects\EduRUTTargetGroupController;
use App\Http\Controllers\Projects\EduRUTAnnexedTargetGroupController;
use App\Http\Controllers\Projects\CICBasicInfoController;
use App\Http\Controllers\Projects\CCI\AchievementsController as CCIAchievementsController;
use App\Http\Controllers\Projects\CCI\AgeProfileController as CCIAgeProfileController;
use App\Http\Controllers\Projects\CCI\AnnexedTargetGroupController as CCIAnnexedTargetGroupController;
use App\Http\Controllers\Projects\CCI\EconomicBackgroundController as CCIEconomicBackgroundController;
use App\Http\Controllers\Projects\CCI\PersonalSituationController as CCIPersonalSituationController;
use App\Http\Controllers\Projects\CCI\PresentSituationController as CCIPresentSituationController;
use App\Http\Controllers\Projects\CCI\RationaleController as CCIRationaleController;
use App\Http\Controllers\Projects\CCI\StatisticsController as CCIStatisticsController;
use App\Http\Controllers\Projects\IGE\InstitutionInfoController as IGEInstitutionInfoController;
use App\Http\Controllers\Projects\IGE\IGEBeneficiariesSupportedController as IGEBeneficiariesSupportedController;
use App\Http\Controllers\Projects\IGE\NewBeneficiariesController as IGENewBeneficiariesController;
use App\Http\Controllers\Projects\IGE\OngoingBeneficiariesController as IGEOngoingBeneficiariesController;
use App\Http\Controllers\Projects\IGE\IGEBudgetController as IGEBudgetController;
use App\Http\Controllers\Projects\IGE\DevelopmentMonitoringController as IGEDevelopmentMonitoringController;
use App\Http\Controllers\Projects\LDP\InterventionLogicController as LDPInterventionLogicController;
use App\Http\Controllers\Projects\LDP\NeedAnalysisController as LDPNeedAnalysisController;
use App\Http\Controllers\Projects\LDP\TargetGroupController as LDPTargetGroupController;
use App\Http\Controllers\Projects\RST\BeneficiariesAreaController as RSTBeneficiariesAreaController;
use App\Http\Controllers\Projects\RST\GeographicalAreaController as RSTGeographicalAreaController;
use App\Http\Controllers\Projects\RST\InstitutionInfoController as RSTInstitutionInfoController;
use App\Http\Controllers\Projects\RST\TargetGroupAnnexureController as RSTTargetGroupAnnexureController;
use App\Http\Controllers\Projects\RST\TargetGroupController as RSTTargetGroupController;
use App\Http\Controllers\Projects\IES\IESPersonalInfoController;
use App\Http\Controllers\Projects\IES\IESFamilyWorkingMembersController;
use App\Http\Controllers\Projects\IES\IESImmediateFamilyDetailsController;
use App\Http\Controllers\Projects\IES\IESEducationBackgroundController;
use App\Http\Controllers\Projects\IES\IESExpensesController;
use App\Http\Controllers\Projects\IES\IESAttachmentsController;
use App\Http\Controllers\Projects\ILP\PersonalInfoController as ILPPersonalInfoController;
use App\Http\Controllers\Projects\ILP\RevenueGoalsController as ILPRevenueGoalsController;
use App\Http\Controllers\Projects\ILP\StrengthWeaknessController as ILPStrengthWeaknessController;
use App\Http\Controllers\Projects\ILP\RiskAnalysisController as ILPRiskAnalysisController;
use App\Http\Controllers\Projects\ILP\AttachedDocumentsController as ILPAttachedDocumentsController;
use App\Http\Controllers\Projects\ILP\BudgetController as ILPBudgetController;
use App\Http\Controllers\Projects\IAH\IAHPersonalInfoController;
use App\Http\Controllers\Projects\IAH\IAHEarningMembersController;
use App\Http\Controllers\Projects\IAH\IAHHealthConditionController;
use App\Http\Controllers\Projects\IAH\IAHSupportDetailsController;
use App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController;
use App\Http\Controllers\Projects\IAH\IAHDocumentsController;
use App\Http\Controllers\Projects\IIES\IIESPersonalInfoController;
use App\Http\Controllers\Projects\IIES\IIESFamilyWorkingMembersController;
use App\Http\Controllers\Projects\IIES\IIESImmediateFamilyDetailsController;
use App\Http\Controllers\Projects\IIES\EducationBackgroundController as IIESEducationBackgroundController;
use App\Http\Controllers\Projects\IIES\FinancialSupportController as IIESFinancialSupportController;
use App\Http\Controllers\Projects\IIES\IIESAttachmentsController;
use App\Http\Controllers\Projects\IIES\IIESExpensesController;

class ExportController extends Controller
{
    // Controller dependencies (same as ProjectController)
    protected $eduRUTBasicInfoController;
    protected $eduRUTTargetGroupController;
    protected $eduRUTAnnexedTargetGroupController;
    protected $cicBasicInfoController;
    protected $cciAchievementsController;
    protected $cciAgeProfileController;
    protected $cciAnnexedTargetGroupController;
    protected $cciEconomicBackgroundController;
    protected $cciPersonalSituationController;
    protected $cciPresentSituationController;
    protected $cciRationaleController;
    protected $cciStatisticsController;
    protected $igeInstitutionInfoController;
    protected $igeBeneficiariesSupportedController;
    protected $igeNewBeneficiariesController;
    protected $igeOngoingBeneficiariesController;
    protected $igeBudgetController;
    protected $igeDevelopmentMonitoringController;
    protected $ldpInterventionLogicController;
    protected $ldpNeedAnalysisController;
    protected $ldpTargetGroupController;
    protected $rstBeneficiariesAreaController;
    protected $rstGeographicalAreaController;
    protected $rstInstitutionInfoController;
    protected $rstTargetGroupAnnexureController;
    protected $rstTargetGroupController;
    protected $iesPersonalInfoController;
    protected $iesFamilyWorkingMembersController;
    protected $iesImmediateFamilyDetailsController;
    protected $iesEducationBackgroundController;
    protected $iesExpensesController;
    protected $iesAttachmentsController;
    protected $ilpPersonalInfoController;
    protected $ilpRevenueGoalsController;
    protected $ilpStrengthWeaknessController;
    protected $ilpRiskAnalysisController;
    protected $ilpAttachedDocumentsController;
    protected $ilpBudgetController;
    protected $iahPersonalInfoController;
    protected $iahEarningMembersController;
    protected $iahHealthConditionController;
    protected $iahSupportDetailsController;
    protected $iahBudgetDetailsController;
    protected $iahDocumentsController;
    protected $iiesPersonalInfoController;
    protected $iiesFamilyWorkingMembersController;
    protected $iiesImmediateFamilyDetailsController;
    protected $iiesEducationBackgroundController;
    protected $iiesFinancialSupportController;
    protected $iiesAttachmentsController;
    protected $iiesExpensesController;

    public function __construct(
        // Edu-RUT
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
        // IGE controllers
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
        // IES controllers
        IESAttachmentsController $iesAttachmentsController,
        IESEducationBackgroundController $iesEducationBackgroundController,
        IESExpensesController $iesExpensesController,
        IESFamilyWorkingMembersController $iesFamilyWorkingMembersController,
        IESImmediateFamilyDetailsController $iesImmediateFamilyDetailsController,
        IESPersonalInfoController $iesPersonalInfoController,
        // ILP controllers
        ILPPersonalInfoController $ilpPersonalInfoController,
        ILPRevenueGoalsController $ilpRevenueGoalsController,
        ILPStrengthWeaknessController $ilpStrengthWeaknessController,
        ILPRiskAnalysisController $ilpRiskAnalysisController,
        ILPAttachedDocumentsController $ilpAttachedDocumentsController,
        ILPBudgetController $ilpBudgetController,
        // IAH controllers
        IAHBudgetDetailsController $iahBudgetDetailsController,
        IAHDocumentsController $iahDocumentsController,
        IAHEarningMembersController $iahEarningMembersController,
        IAHHealthConditionController $iahHealthConditionController,
        IAHPersonalInfoController $iahPersonalInfoController,
        IAHSupportDetailsController $iahSupportDetailsController,
        // IIES controllers
        IIESEducationBackgroundController $iiesEducationBackgroundController,
        IIESFinancialSupportController $iiesFinancialSupportController,
        IIESAttachmentsController $iiesAttachmentsController,
        IIESFamilyWorkingMembersController $iiesFamilyWorkingMembersController,
        IIESImmediateFamilyDetailsController $iiesImmediateFamilyDetailsController,
        IIESPersonalInfoController $iiesPersonalInfoController,
        IIESExpensesController $iiesExpensesController
    ) {
        $this->eduRUTBasicInfoController = $eduRUTBasicInfoController;
        $this->eduRUTTargetGroupController = $eduRUTTargetGroupController;
        $this->eduRUTAnnexedTargetGroupController = $eduRUTAnnexedTargetGroupController;
        $this->cicBasicInfoController = $cicBasicInfoController;
        $this->cciAchievementsController = $cciAchievementsController;
        $this->cciAgeProfileController = $cciAgeProfileController;
        $this->cciAnnexedTargetGroupController = $cciAnnexedTargetGroupController;
        $this->cciEconomicBackgroundController = $cciEconomicBackgroundController;
        $this->cciPersonalSituationController = $cciPersonalSituationController;
        $this->cciPresentSituationController = $cciPresentSituationController;
        $this->cciRationaleController = $cciRationaleController;
        $this->cciStatisticsController = $cciStatisticsController;
        $this->igeInstitutionInfoController = $igeInstitutionInfoController;
        $this->igeBeneficiariesSupportedController = $igeBeneficiariesSupportedController;
        $this->igeNewBeneficiariesController = $igeNewBeneficiariesController;
        $this->igeOngoingBeneficiariesController = $igeOngoingBeneficiariesController;
        $this->igeBudgetController = $igeBudgetController;
        $this->igeDevelopmentMonitoringController = $igeDevelopmentMonitoringController;
        $this->ldpInterventionLogicController = $ldpInterventionLogicController;
        $this->ldpNeedAnalysisController = $ldpNeedAnalysisController;
        $this->ldpTargetGroupController = $ldpTargetGroupController;
        $this->rstBeneficiariesAreaController = $rstBeneficiariesAreaController;
        $this->rstGeographicalAreaController = $rstGeographicalAreaController;
        $this->rstInstitutionInfoController = $rstInstitutionInfoController;
        $this->rstTargetGroupAnnexureController = $rstTargetGroupAnnexureController;
        $this->rstTargetGroupController = $rstTargetGroupController;
        $this->iesPersonalInfoController = $iesPersonalInfoController;
        $this->iesFamilyWorkingMembersController = $iesFamilyWorkingMembersController;
        $this->iesImmediateFamilyDetailsController = $iesImmediateFamilyDetailsController;
        $this->iesEducationBackgroundController = $iesEducationBackgroundController;
        $this->iesExpensesController = $iesExpensesController;
        $this->iesAttachmentsController = $iesAttachmentsController;
        $this->ilpPersonalInfoController = $ilpPersonalInfoController;
        $this->ilpRevenueGoalsController = $ilpRevenueGoalsController;
        $this->ilpStrengthWeaknessController = $ilpStrengthWeaknessController;
        $this->ilpRiskAnalysisController = $ilpRiskAnalysisController;
        $this->ilpAttachedDocumentsController = $ilpAttachedDocumentsController;
        $this->ilpBudgetController = $ilpBudgetController;
        $this->iahPersonalInfoController = $iahPersonalInfoController;
        $this->iahEarningMembersController = $iahEarningMembersController;
        $this->iahHealthConditionController = $iahHealthConditionController;
        $this->iahSupportDetailsController = $iahSupportDetailsController;
        $this->iahBudgetDetailsController = $iahBudgetDetailsController;
        $this->iahDocumentsController = $iahDocumentsController;
        $this->iiesPersonalInfoController = $iiesPersonalInfoController;
        $this->iiesFamilyWorkingMembersController = $iiesFamilyWorkingMembersController;
        $this->iiesImmediateFamilyDetailsController = $iiesImmediateFamilyDetailsController;
        $this->iiesEducationBackgroundController = $iiesEducationBackgroundController;
        $this->iiesFinancialSupportController = $iiesFinancialSupportController;
        $this->iiesAttachmentsController = $iiesAttachmentsController;
        $this->iiesExpensesController = $iiesExpensesController;
    }

    public function downloadPdf($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)
                ->with(['attachments', 'objectives.risks', 'objectives.activities.timeframes', 'sustainabilities', 'budgets', 'user'])
                ->firstOrFail();

            $user = Auth::user();

            // Use ProjectPermissionHelper for consistent permission checking
            // For admin, coordinator, and provincial roles, check separately as they have different rules
            $hasAccess = false;

            if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
                // Admin, coordinators, and provincials have special access rules
                switch ($user->role) {
                    case 'provincial':
                        // Provincials can download projects from executors under them with specific statuses
                        if ($project->user->parent_id === $user->id) {
                            if (in_array($project->status, [
                                ProjectStatus::SUBMITTED_TO_PROVINCIAL,
                                ProjectStatus::REVERTED_BY_COORDINATOR,
                                ProjectStatus::APPROVED_BY_COORDINATOR
                            ])) {
                                $hasAccess = true;
                            }
                        }
                        break;

                    case 'coordinator':
                        // Coordinators can download projects with various statuses
                        if (in_array($project->status, [
                            ProjectStatus::FORWARDED_TO_COORDINATOR,
                            ProjectStatus::APPROVED_BY_COORDINATOR,
                            ProjectStatus::REVERTED_BY_COORDINATOR
                        ])) {
                            $hasAccess = true;
                        }
                        break;

                    case 'admin':
                        // Admins can download all projects
                        $hasAccess = true;
                        break;
                }
            } else {
                // For executor and applicant, use ProjectPermissionHelper
                $hasAccess = ProjectPermissionHelper::canView($project, $user);
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this project.');
            }

            $generalUser = User::where('role', 'general')->first();

            $projectRoles = [
                'executor' => $project->executor_name,
                'incharge' => $project->in_charge_name,
                'president' => $project->president_name,
                'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
                'coordinator' => $project->coordinator_india_name
            ];

            // Load all project data using the same approach as ProjectController show method
            $data = $this->loadAllProjectData($project_id);
            $data['projectRoles'] = $projectRoles;

            $html = view('projects.Oldprojects.pdf', $data)->render();
            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);
            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="project_' . $project_id . '.pdf"');
        } catch (\Exception $e) {
            Log::error('ExportController@downloadPdf - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
            throw $e;
        }
    }

    /**
     * Load all project data using the same approach as ProjectController show method
     */
    private function loadAllProjectData($project_id)
    {
        $project = Project::where('project_id', $project_id)
            ->with(['budgets', 'attachments', 'objectives.risks', 'objectives.activities.timeframes', 'sustainabilities', 'user'])
            ->firstOrFail();

        $user = Auth::user();

        // Initialize data array with defaults (same as ProjectController show method)
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

        // Handle project-specific data (same logic as ProjectController show method)
        switch ($project->project_type) {
            case 'Rural-Urban-Tribal':
                $data['basicInfo'] = $this->eduRUTBasicInfoController->show($project_id);
                $data['RUTtargetGroups'] = $this->eduRUTTargetGroupController->show($project_id);
                $data['annexedTargetGroups'] = $this->eduRUTAnnexedTargetGroupController->show($project_id);
                break;

            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                $data['basicInfo'] = $this->cicBasicInfoController->show($project->project_id);
                break;

            case 'CHILD CARE INSTITUTION':
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
                $data['IGEInstitutionInfo'] = $this->igeInstitutionInfoController->show($project->project_id);
                $data['beneficiariesSupported'] = $this->igeBeneficiariesSupportedController->show($project->project_id);
                $data['newBeneficiaries'] = $this->igeNewBeneficiariesController->show($project->project_id);
                $data['ongoingBeneficiaries'] = $this->igeOngoingBeneficiariesController->show($project->project_id);
                $data['budget'] = $this->igeBudgetController->show($project->project_id);
                $data['developmentMonitoring'] = $this->igeDevelopmentMonitoringController->show($project->project_id);
                break;

            case 'Livelihood Development Projects':
                $data['interventionLogic'] = $this->ldpInterventionLogicController->show($project_id);
                $data['needAnalysis'] = $this->ldpNeedAnalysisController->show($project_id);
                $data['LDPtargetGroups'] = $this->ldpTargetGroupController->show($project_id);
                break;

            case 'Residential Skill Training Proposal 2':
                $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
                $data['RSTGeographicalArea'] = $this->rstGeographicalAreaController->show($project->project_id);
                $data['RSTInstitutionInfo'] = $this->rstInstitutionInfoController->show($project->project_id);
                $data['RSTTargetGroupAnnexure'] = $this->rstTargetGroupAnnexureController->show($project->project_id);
                $data['RSTTargetGroup'] = $this->rstTargetGroupController->show($project->project_id);
                break;

            case 'Development Projects':
                $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
                break;

            case 'NEXT PHASE - DEVELOPMENT PROPOSAL':
                $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
                break;

            case 'Individual - Ongoing Educational support':
                $data['IESpersonalInfo'] = $this->loadIESPersonalInfo($project_id);
                $data['IESfamilyWorkingMembers'] = $this->loadIESFamilyWorkingMembers($project_id);
                $data['IESimmediateFamilyDetails'] = $this->loadIESImmediateFamilyDetails($project_id);
                $data['IESEducationBackground'] = $this->loadIESEducationBackground($project_id);
                $data['IESExpenses'] = $this->loadIESExpenses($project_id);
                $data['IESAttachments'] = $this->loadIESAttachments($project_id);
                break;

            case 'Individual - Livelihood Application':
                $data['ILPPersonalInfo'] = $this->loadILPPersonalInfo($project_id);
                $data['ILPRevenueGoals'] = $this->loadILPRevenueGoals($project_id);
                $data['ILPStrengthWeakness'] = $this->loadILPStrengthWeakness($project_id);
                $data['ILPRiskAnalysis'] = $this->loadILPRiskAnalysis($project_id);
                $data['ILPAttachedDocuments'] = $this->loadILPAttachedDocuments($project_id);
                $data['ILPBudgets'] = $this->loadILPBudget($project_id);
                break;

            case 'Individual - Access to Health':
                $data['IAHPersonalInfo'] = $this->loadIAHPersonalInfo($project_id);
                $data['IAHEarningMembers'] = $this->loadIAHEarningMembers($project_id);
                $data['IAHHealthCondition'] = $this->loadIAHHealthCondition($project_id);
                $data['IAHSupportDetails'] = $this->loadIAHSupportDetails($project_id);
                $data['IAHBudgetDetails'] = $this->loadIAHBudgetDetails($project_id);
                $data['IAHDocuments'] = $this->loadIAHDocuments($project_id);
                break;

            case 'Individual - Initial - Educational support':
                $data['IIESPersonalInfo'] = $this->loadIIESPersonalInfo($project_id);
                $data['IIESFamilyWorkingMembers'] = $this->loadIIESFamilyWorkingMembers($project_id);
                $data['IIESImmediateFamilyDetails'] = $this->loadIIESImmediateFamilyDetails($project_id);
                $data['IIESEducationBackground'] = $this->loadIIESEducationBackground($project_id);
                $data['IIESFinancialSupport'] = $this->loadIIESFinancialSupport($project_id);
                $data['IIESAttachments'] = $this->loadIIESAttachments($project_id);
                $data['IIESExpenses'] = $this->loadIIESExpenses($project_id);
                break;
        }

        return $data;
    }

    // Helper methods to load specific data (these would need to be implemented or injected)
    private function loadRSTBeneficiariesArea($project_id)
    {
        return ProjectDPRSTBeneficiariesArea::where('project_id', $project_id)->get();
    }

    private function loadRSTGeographicalArea($project_id)
    {
        return ProjectRSTGeographicalArea::where('project_id', $project_id)->get();
    }

    private function loadRSTInstitutionInfo($project_id)
    {
        return ProjectRSTInstitutionInfo::where('project_id', $project_id)->first();
    }

    private function loadRSTTargetGroupAnnexure($project_id)
    {
        // Implement based on your model structure
        return collect();
    }

    private function loadRSTTargetGroup($project_id)
    {
        // Implement based on your model structure
        return collect();
    }

    // Add other helper methods as needed...
    private function loadEduRUTBasicInfo($project_id) {
        return ProjectEduRUTBasicInfo::where('project_id', $project_id)->first();
    }
    private function loadEduRUTTargetGroup($project_id) {
        return ProjectEduRUTTargetGroup::where('project_id', $project_id)->get();
    }
    private function loadEduRUTAnnexedTargetGroup($project_id) {
        return ProjectEduRUTAnnexedTargetGroup::where('project_id', $project_id)->get();
    }
    private function loadCICBasicInfo($project_id) {
        return ProjectCICBasicInfo::where('project_id', $project_id)->first();
    }
    private function loadCCIAchievements($project_id) {
        return ProjectCCIAchievements::where('project_id', $project_id)->first();
    }
    private function loadCCIAgeProfile($project_id) {
        return ProjectCCIAgeProfile::where('project_id', $project_id)->first();
    }
    private function loadCCIAnnexedTargetGroup($project_id) {
        return ProjectCCIAnnexedTargetGroup::where('project_id', $project_id)->get();
    }
    private function loadCCIEconomicBackground($project_id) {
        return ProjectCCIEconomicBackground::where('project_id', $project_id)->first();
    }
    private function loadCCIPersonalSituation($project_id) {
        return ProjectCCIPersonalSituation::where('project_id', $project_id)->first();
    }
    private function loadCCIPresentSituation($project_id) {
        return ProjectCCIPresentSituation::where('project_id', $project_id)->first();
    }
    private function loadCCIRationale($project_id) {
        return ProjectCCIRationale::where('project_id', $project_id)->first();
    }
    private function loadCCIStatistics($project_id) {
        return ProjectCCIStatistics::where('project_id', $project_id)->first();
    }
    private function loadIGEInstitutionInfo($project_id) {
        return ProjectIGEInstitutionInfo::where('project_id', $project_id)->first();
    }
    private function loadIGEBeneficiariesSupported($project_id) {
        return ProjectIGEBeneficiariesSupported::where('project_id', $project_id)->first();
    }
    private function loadIGENewBeneficiaries($project_id) {
        return ProjectIGENewBeneficiaries::where('project_id', $project_id)->first();
    }
    private function loadIGEOngoingBeneficiaries($project_id) {
        return ProjectIGEOngoingBeneficiaries::where('project_id', $project_id)->first();
    }
    private function loadIGEBudget($project_id) {
        return ProjectIGEBudget::where('project_id', $project_id)->first();
    }
    private function loadIGEDevelopmentMonitoring($project_id) {
        return ProjectIGEDevelopmentMonitoring::where('project_id', $project_id)->first();
    }
    private function loadLDPInterventionLogic($project_id) {
        return ProjectLDPInterventionLogic::where('project_id', $project_id)->first();
    }
    private function loadLDPNeedAnalysis($project_id) {
        return ProjectLDPNeedAnalysis::where('project_id', $project_id)->first();
    }
    private function loadLDPTargetGroup($project_id) {
        return ProjectLDPTargetGroup::where('project_id', $project_id)->get();
    }
    private function loadIESPersonalInfo($project_id) {
        return ProjectIESPersonalInfo::where('project_id', $project_id)->first();
    }
    private function loadIESFamilyWorkingMembers($project_id) {
        return ProjectIESFamilyWorkingMembers::where('project_id', $project_id)->get();
    }
    private function loadIESImmediateFamilyDetails($project_id) {
        return ProjectIESImmediateFamilyDetails::where('project_id', $project_id)->first();
    }
    private function loadIESEducationBackground($project_id) {
        return ProjectIESEducationBackground::where('project_id', $project_id)->first();
    }
    private function loadIESExpenses($project_id) {
        return ProjectIESExpenses::where('project_id', $project_id)->first();
    }
    private function loadIESAttachments($project_id) {
        return ProjectIESAttachments::where('project_id', $project_id)->first();
    }
    private function loadILPPersonalInfo($project_id) {
        return ProjectILPPersonalInfo::where('project_id', $project_id)->first();
    }
    private function loadILPRevenueGoals($project_id) {
        try {
            return [
                'business_plan_items' => ProjectILPRevenuePlanItem::where('project_id', $project_id)->get()->toArray(),
                'annual_income' => ProjectILPRevenueIncome::where('project_id', $project_id)->get()->toArray(),
                'annual_expenses' => ProjectILPRevenueExpense::where('project_id', $project_id)->get()->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Error loading ILP Revenue Goals', ['project_id' => $project_id, 'error' => $e->getMessage()]);
            return [
                'business_plan_items' => [],
                'annual_income' => [],
                'annual_expenses' => [],
            ];
        }
    }
    private function loadILPStrengthWeakness($project_id) {
        try {
            $strengthWeakness = ProjectILPBusinessStrengthWeakness::where('project_id', $project_id)->first();

            return [
                'strengths' => $strengthWeakness ? json_decode($strengthWeakness->strengths, true) : [],
                'weaknesses' => $strengthWeakness ? json_decode($strengthWeakness->weaknesses, true) : [],
            ];
        } catch (\Exception $e) {
            Log::error('Error loading ILP Strength Weakness', ['project_id' => $project_id, 'error' => $e->getMessage()]);
            return [
                'strengths' => [],
                'weaknesses' => [],
            ];
        }
    }
    private function loadILPRiskAnalysis($project_id) {
        try {
            $riskAnalysis = ProjectILPRiskAnalysis::where('project_id', $project_id)->first();

            return [
                'identified_risks' => $riskAnalysis ? $riskAnalysis->identified_risks : '',
                'mitigation_measures' => $riskAnalysis ? $riskAnalysis->mitigation_measures : '',
                'business_sustainability' => $riskAnalysis ? $riskAnalysis->business_sustainability : '',
                'expected_profits' => $riskAnalysis ? $riskAnalysis->expected_profits : '',
            ];
        } catch (\Exception $e) {
            Log::error('Error loading ILP Risk Analysis', ['project_id' => $project_id, 'error' => $e->getMessage()]);
            return [
                'identified_risks' => '',
                'mitigation_measures' => '',
                'business_sustainability' => '',
                'expected_profits' => '',
            ];
        }
    }
    private function loadILPAttachedDocuments($project_id) {
        try {
            $documents = ProjectILPAttachedDocuments::where('project_id', $project_id)->first();

            return [
                'aadhar_doc' => $documents && $documents->aadhar_doc ? Storage::url($documents->aadhar_doc) : null,
                'request_letter_doc' => $documents && $documents->request_letter_doc ? Storage::url($documents->request_letter_doc) : null,
                'purchase_quotation_doc' => $documents && $documents->purchase_quotation_doc ? Storage::url($documents->purchase_quotation_doc) : null,
                'other_doc' => $documents && $documents->other_doc ? Storage::url($documents->other_doc) : null,
            ];
        } catch (\Exception $e) {
            Log::error('Error loading ILP Attached Documents', ['project_id' => $project_id, 'error' => $e->getMessage()]);
            return [
                'aadhar_doc' => null,
                'request_letter_doc' => null,
                'purchase_quotation_doc' => null,
                'other_doc' => null,
            ];
        }
    }
    private function loadILPBudget($project_id) {
        try {
            $budgets = ProjectILPBudget::where('project_id', $project_id)->get();

            return [
                'budgets' => $budgets,
                'total_amount' => $budgets->sum('cost'),
                'beneficiary_contribution' => $budgets->first()->beneficiary_contribution ?? 0,
                'amount_requested' => $budgets->first()->amount_requested ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error loading ILP Budget', ['project_id' => $project_id, 'error' => $e->getMessage()]);
            return [
                'budgets' => collect([]),
                'total_amount' => 0,
                'beneficiary_contribution' => 0,
                'amount_requested' => 0,
            ];
        }
    }
    private function loadIAHPersonalInfo($project_id) {
        return ProjectIAHPersonalInfo::where('project_id', $project_id)->first();
    }
    private function loadIAHEarningMembers($project_id) {
        return ProjectIAHEarningMembers::where('project_id', $project_id)->get();
    }
    private function loadIAHHealthCondition($project_id) {
        return ProjectIAHHealthCondition::where('project_id', $project_id)->first();
    }
    private function loadIAHSupportDetails($project_id) {
        return ProjectIAHSupportDetails::where('project_id', $project_id)->first();
    }
    private function loadIAHBudgetDetails($project_id) {
        return ProjectIAHBudgetDetails::where('project_id', $project_id)->first();
    }
    private function loadIAHDocuments($project_id) {
        return ProjectIAHDocuments::where('project_id', $project_id)->first();
    }
    private function loadIIESPersonalInfo($project_id) {
        return ProjectIIESPersonalInfo::where('project_id', $project_id)->first();
    }
    private function loadIIESFamilyWorkingMembers($project_id) {
        return ProjectIIESFamilyWorkingMembers::where('project_id', $project_id)->get();
    }
    private function loadIIESImmediateFamilyDetails($project_id) {
        return ProjectIIESImmediateFamilyDetails::where('project_id', $project_id)->first();
    }
    private function loadIIESEducationBackground($project_id) {
        return ProjectIIESEducationBackground::where('project_id', $project_id)->first();
    }
    private function loadIIESFinancialSupport($project_id) {
        return ProjectIIESScopeFinancialSupport::where('project_id', $project_id)->first();
    }
    private function loadIIESAttachments($project_id) {
        return ProjectIIESAttachments::where('project_id', $project_id)->first();
    }
    private function loadIIESExpenses($project_id) {
        return ProjectIIESExpenses::where('project_id', $project_id)->first();
    }

    public function downloadDoc($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)
                ->with([
                    'attachments',
                    'objectives.risks',
                    'objectives.activities.timeframes',
                    'sustainabilities',
                    'budgets',
                    'user'
                ])->firstOrFail();

            $user = Auth::user();

            // Use ProjectPermissionHelper for consistent permission checking
            // For admin, coordinator, and provincial roles, check separately as they have different rules
            $hasAccess = false;

            if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
                // Admin, coordinators, and provincials have special access rules
                switch ($user->role) {
                    case 'provincial':
                        // Provincials can download projects from executors under them with specific statuses
                        if ($project->user->parent_id === $user->id) {
                            if (in_array($project->status, [
                                ProjectStatus::SUBMITTED_TO_PROVINCIAL,
                                ProjectStatus::REVERTED_BY_COORDINATOR,
                                ProjectStatus::APPROVED_BY_COORDINATOR
                            ])) {
                                $hasAccess = true;
                            }
                        }
                        break;

                    case 'coordinator':
                        // Coordinators can download projects with various statuses
                        if (in_array($project->status, [
                            ProjectStatus::FORWARDED_TO_COORDINATOR,
                            ProjectStatus::APPROVED_BY_COORDINATOR,
                            ProjectStatus::REVERTED_BY_COORDINATOR
                        ])) {
                            $hasAccess = true;
                        }
                        break;

                    case 'admin':
                        // Admins can download all projects
                        $hasAccess = true;
                        break;
                }
            } else {
                // For executor and applicant, use ProjectPermissionHelper
                $hasAccess = ProjectPermissionHelper::canView($project, $user);
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this project.');
            }

            $generalUser = User::where('role', 'general')->first();

            $projectRoles = [
                'executor' => $project->executor_name,
                'incharge' => $project->in_charge_name,
                'president' => $project->president_name,
                'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
                'coordinator' => $project->coordinator_india_name
            ];

            $phpWord = new PhpWord();

            // Order as per show.blade.php
            // 1. General Information
            $this->addGeneralInfoSection($phpWord, $project, $projectRoles);

            // 2. Key Information
            $this->addKeyInformationSection($phpWord, $project);

            // 3. CCI Specific Partials
            if ($project->project_type === 'CHILD CARE INSTITUTION') {
                $this->addCCISections($phpWord, $project);
            }

            // 4. RST Specific Partials
            if (in_array($project->project_type, ['Residential Skill Training Proposal 2', 'Development Projects'])) {
                $this->addRSTSections($phpWord, $project);
            }

            // 5. Edu-RUT Specific Partials
            if ($project->project_type === 'Rural-Urban-Tribal') {
                $this->addEduRUTSections($phpWord, $project);
            }

            // 6. Individual Project Types
            if (in_array($project->project_type, [
                'Individual - Ongoing Educational support',
                'Individual - Livelihood Application',
                'Individual - Access to Health',
                'Individual - Initial - Educational support'
            ])) {
                $this->addIndividualProjectSections($phpWord, $project);
            }

            // 7. IGE Specific Partials
            if ($project->project_type === 'Institutional Ongoing Group Educational proposal') {
                $this->addIGESections($phpWord, $project);
            }

            // 8. LDP Specific Partials
            if ($project->project_type === 'Livelihood Development Projects') {
                $this->addLDPSections($phpWord, $project);
            }

            // 9. CIC Specific Partials
            if ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                $this->addCICSections($phpWord, $project);
            }

            // 10. Common Sections (for non-individual types)
            if (!in_array($project->project_type, [
                'Individual - Ongoing Educational support',
                'Individual - Livelihood Application',
                'Individual - Access to Health',
                'Individual - Initial - Educational support'
            ])) {
                $this->addLogicalFrameworkSection($phpWord, $project);
                $this->addSustainabilitySection($phpWord, $project);
                $this->addBudgetSection($phpWord, $project);
                $this->addAttachmentsSection($phpWord, $project);
            }

            // Comments Section - currently not included in Word doc
            // You can add a method if needed.

            // 14. Signature and Approval Sections (common at the end)
            $this->addSignatureAndApprovalSections($phpWord, $project, $projectRoles);

            // Save the file and return response
            $filePath = storage_path("app/public/Project_{$project->project_id}.docx");
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

            Log::info('ExportController@downloadDoc - DOC generated', ['project_id' => $project_id]);
            return response()->download($filePath)->deleteFileAfterSend(true);

         }
         catch (\Exception $e)
        {

            Log::error('ExportController@downloadDoc - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
            throw $e;
        }
    }


// General info
private function addGeneralInfoSection(PhpWord $phpWord, $project, $projectRoles)
{
    // Add a new section to the document
    $section = $phpWord->addSection();
    $section->addText("Project Details", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Add General Information
    $section->addText("Basic Information", ['bold' => true, 'size' => 14]);
    $section->addText("Project ID: {$project->project_id}");
    $section->addText("Project Title: {$project->project_title}");
    $section->addText("Project Type: {$project->project_type}");
    $section->addText("Society Name: {$project->society_name}");
    $section->addText("President Name: {$project->president_name}");
    $section->addText("In Charge Name: {$project->in_charge_name}");
    $section->addText("In Charge Phone: {$project->in_charge_mobile}");
    $section->addText("In Charge Email: {$project->in_charge_email}");
    $section->addText("Executor Name: {$project->executor_name}");
    $section->addText("Executor Phone: {$project->executor_mobile}");
    $section->addText("Executor Email: {$project->executor_email}");
    $section->addText("Full Address: {$project->full_address}");
    $section->addText("Overall Project Period: {$project->overall_project_period} years");
    $section->addText(
        "Commencement Month & Year: " .
        (\Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') ?? 'N/A')
    );
    $section->addText(
        "Overall Project Budget: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($project->overall_project_budget, 2)
    );
    $section->addText(
        "Amount Forwarded: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($project->amount_forwarded, 2)
    );
    $section->addText(
        "Amount Sanctioned: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($project->amount_sanctioned, 2)
    );
    $section->addText(
        "Opening Balance: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($project->opening_balance, 2)
    );
    $section->addText("Coordinator India Name: {$project->coordinator_india_name}");
    $section->addText("Coordinator India Phone: {$project->coordinator_india_phone}");
    $section->addText("Coordinator India Email: {$project->coordinator_india_email}");
    $section->addText("Coordinator Luzern Name: {$project->coordinator_luzern_name}");
    $section->addText("Coordinator Luzern Phone: {$project->coordinator_luzern_phone}");
    $section->addText("Coordinator Luzern Email: {$project->coordinator_luzern_email}");
    $section->addText("Status: " . ucfirst($project->status));
    $section->addTextBreak(1);

    // Add any additional fields if required in the future
}
//Key Information section
private function addKeyInformationSection(PhpWord $phpWord, $project)
{
    // Add a new section to the document
    $section = $phpWord->addSection();

    // Add a header for the section
    $section->addText("Key Information", ['bold' => true, 'size' => 14]);

    // Add a horizontal rule for visual separation
    $section->addText(str_repeat('-', 50), ['color' => 'gray']);

    // Subheading: Background of the project
    $section->addText("Background of the project", ['bold' => true, 'size' => 12]);
    $section->addTextBreak(0.3);

    // Prevailing social situation in the project area and its adverse effect on life
    if ($project->initial_information) {
        $section->addText("Prevailing social situation in the project area and its adverse effect on life:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->initial_information);
        $section->addTextBreak(0.5);
    }

    // Detailed information on target beneficiary of the project
    if ($project->target_beneficiaries) {
        $section->addText("Detailed information on target beneficiary of the project:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->target_beneficiaries);
        $section->addTextBreak(0.5);
    }

    // Educational & cultural situation in the project area
    if ($project->general_situation) {
        $section->addText("Educational & cultural situation in the project area:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->general_situation);
        $section->addTextBreak(0.5);
    }

    // Need of the Project
    if ($project->need_of_project) {
        $section->addText("Need of the Project:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->need_of_project);
        $section->addTextBreak(0.5);
    }

    // Prevailing economic situation in the project area
    if (!empty($project->economic_situation)) {
        $section->addText("Prevailing economic situation in the project area:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->economic_situation);
        $section->addTextBreak(0.5);
    }

    // Goal of the Project (last field)
    if ($project->goal) {
        $section->addText("Goal of the Project:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->goal);
        $section->addTextBreak(0.5);
    }

    // If no key information fields are present
    $hasAny = $project->initial_information || $project->target_beneficiaries ||
        $project->general_situation || $project->need_of_project ||
        !empty($project->economic_situation) || $project->goal;
    if (!$hasAny) {
        $section->addText("No key information provided yet.", ['italic' => true]);
    }

    // Add spacing at the end of the section
    $section->addTextBreak(1);
}

// Helper method to add text with preserved line breaks
private function addTextWithLineBreaks($section, $text)
{
    if (empty($text)) {
        $section->addText('N/A', ['size' => 12]);
        return;
    }
    
    // Split by newlines and add each line separately to preserve line breaks
    $lines = explode("\n", $text);
    foreach ($lines as $index => $line) {
        $section->addText($line, ['size' => 12]);
        // Add line break after each line except the last one
        if ($index < count($lines) - 1) {
            $section->addTextBreak(0.3);
        }
    }
}
// CHILD CARE INSTITUTION specific functions
private function addCCISections(PhpWord $phpWord, $project)
{
    // Add rationale
    $this->addRationaleSection($phpWord, $project);

    // Add statistics
    $this->addStatisticsSection($phpWord, $project);

    // Add annexed target group
    $this->addAnnexedTargetGroupSection($phpWord, $project);

    // Add age profile
    $this->addAgeProfileSection($phpWord, $project);

    // Add personal situation
    $this->addPersonalSituationSection($phpWord, $project);

    // Add economic background
    $this->addEconomicBackgroundSection($phpWord, $project);

    // Add achievements
    $this->addAchievementsSection($phpWord, $project);

    // Add present situation
    $this->addPresentSituationSection($phpWord, $project);
}
//Section - Rationa - CHILD CARE INSTITUTION
private function addRationaleSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add section title
    $section->addText("Rationale", ['bold' => true, 'size' => 14]);

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('RationaleTableStyle', $tableStyle);

    // Add table
    $table = $section->addTable('RationaleTableStyle');

    // Add rows
    $table->addRow();
    $table->addCell(3000)->addText("Description:", ['bold' => true]);
    $descriptionCell = $table->addCell(7000);
    $this->addTextWithLineBreaks($descriptionCell, $project->rationale->description ?? 'No rationale provided yet.');
}
//Section - Statistics - CHILD CARE INSTITUTION

private function addStatisticsSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add a title for the section
    $section->addText(
        "Statistics of Passed out / Rehabilitated / Re-integrated Children till Date",
        ['bold' => true, 'size' => 14]
    );

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('StatisticsTable', $tableStyle);
    $table = $section->addTable('StatisticsTable');

    // Add header row
    $table->addRow();
    $table->addCell(7000, ['valign' => 'center'])->addText("Description", ['bold' => true]);
    $table->addCell(3000, ['valign' => 'center'])->addText("Upto Previous Year", ['bold' => true]);
    $table->addCell(3000, ['valign' => 'center'])->addText("Current Year on Roll", ['bold' => true]);

    // Add data rows
    $statistics = $project->statistics; // Assuming $statistics is available as a property or relation

    $rows = [
        'Total number of children in the institution' => [
            'previous' => $statistics->total_children_previous_year ?? 'N/A',
            'current' => $statistics->total_children_current_year ?? 'N/A',
        ],
        'Children who are reintegrated with their guardians/parents' => [
            'previous' => $statistics->reintegrated_children_previous_year ?? 'N/A',
            'current' => $statistics->reintegrated_children_current_year ?? 'N/A',
        ],
        'Children who are shifted to other NGOs / Govt.' => [
            'previous' => $statistics->shifted_children_previous_year ?? 'N/A',
            'current' => $statistics->shifted_children_current_year ?? 'N/A',
        ],
        'Children who are pursuing higher studies outside' => [
            'previous' => $statistics->pursuing_higher_studies_previous_year ?? 'N/A',
            'current' => $statistics->pursuing_higher_studies_current_year ?? 'N/A',
        ],
        'Children who completed the studies and settled down in life (i.e., married etc.)' => [
            'previous' => $statistics->settled_children_previous_year ?? 'N/A',
            'current' => $statistics->settled_children_current_year ?? 'N/A',
        ],
        'Children who are now settled and working' => [
            'previous' => $statistics->working_children_previous_year ?? 'N/A',
            'current' => $statistics->working_children_current_year ?? 'N/A',
        ],
        'Any other category' => [
            'previous' => $statistics->other_category_previous_year ?? 'N/A',
            'current' => $statistics->other_category_current_year ?? 'N/A',
        ],
    ];

    foreach ($rows as $description => $data) {
        $table->addRow();
        $table->addCell(7000)->addText($description);
        $table->addCell(3000)->addText($data['previous']);
        $table->addCell(3000)->addText($data['current']);
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}
//Section - Target Group - CHILD CARE INSTITUTION

private function addAnnexedTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Section Title
    $section->addText("Annexed Target Group (CCI)", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add spacing

    // Check if annexedTargetGroup is available and not empty
    if (isset($project->annexed_target_groups) && $project->annexed_target_groups->isNotEmpty()) {
        // Define table style
        $tableStyle = [
            'borderSize' => 6, // 1pt = 8 twips, 0.75pt ≈ 6 twips
            'borderColor' => '000000', // Black border
            'cellMargin' => 80 // Adds padding inside cells
        ];
        $firstRowStyle = ['bgColor' => '101117']; // Dark background for header row
        $phpWord->addTableStyle('AnnexedTargetGroupTable', $tableStyle, $firstRowStyle);

        // Add Table
        $table = $section->addTable('AnnexedTargetGroupTable');

        // Add Header Row
        $table->addRow();
        $table->addCell(1000)->addText("S.No.", ['bold' => true, 'color' => 'FFFFFF'], ['align' => 'center']);
        $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(2000)->addText("Date of Birth", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(2000)->addText("Date of Joining", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(2000)->addText("Class of Study", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(4000)->addText("Family Background", ['bold' => true, 'color' => 'FFFFFF']);

        // Add Data Rows
        foreach ($project->annexed_target_groups as $index => $group) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1, [], ['align' => 'center']);
            $table->addCell(3000)->addText($group->beneficiary_name ?? 'N/A');
            $table->addCell(2000)->addText(
                isset($group->dob) ? \Carbon\Carbon::parse($group->dob)->format('d/m/Y') : 'N/A'
            );
            $table->addCell(2000)->addText(
                isset($group->date_of_joining) ? \Carbon\Carbon::parse($group->date_of_joining)->format('d/m/Y') : 'N/A'
            );
            $table->addCell(2000)->addText($group->class_of_study ?? 'N/A');
            $table->addCell(4000)->addText($group->family_background_description ?? 'N/A');
        }
    } else {
        // No Data Message
        $section->addText("No data available for Annexed Target Group.", ['italic' => true, 'size' => 12]);
    }

    $section->addTextBreak(2); // Add some spacing after the table
}
//Section - Age Profile - CHILD CARE INSTITUTION
private function addAgeProfileSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Age Profile of Children in the Institution", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Table for Age Profile Data
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
    ];
    $phpWord->addTableStyle('AgeProfileTable', $tableStyle);
    $table = $section->addTable('AgeProfileTable');

    // Add Table Header
    $table->addRow();
    $table->addCell(4000)->addText("Age Category", ['bold' => true]);
    $table->addCell(3000)->addText("Education", ['bold' => true]);
    $table->addCell(2000)->addText("Up to Previous Year", ['bold' => true]);
    $table->addCell(2000)->addText("Present Academic Year", ['bold' => true]);

    // Age Profile Data
    $ageProfile = $project->age_profile; // Assuming this relation is fetched with the project

    $dataRows = [
        // Children below 5 years
        ['Children below 5 years', 'Bridge course', $ageProfile['education_below_5_bridge_course_prev_year'] ?? 'N/A', $ageProfile['education_below_5_bridge_course_current_year'] ?? 'N/A'],
        ['', 'Kindergarten', $ageProfile['education_below_5_kindergarten_prev_year'] ?? 'N/A', $ageProfile['education_below_5_kindergarten_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_below_5_other_specify'] ?? 'Other', $ageProfile['education_below_5_other_prev_year'] ?? 'N/A', $ageProfile['education_below_5_other_current_year'] ?? 'N/A'],

        // Children between 6 to 10 years
        ['Children between 6 to 10 years', 'Primary school', $ageProfile['education_6_10_primary_school_prev_year'] ?? 'N/A', $ageProfile['education_6_10_primary_school_current_year'] ?? 'N/A'],
        ['', 'Bridge course', $ageProfile['education_6_10_bridge_course_prev_year'] ?? 'N/A', $ageProfile['education_6_10_bridge_course_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_6_10_other_specify'] ?? 'Other', $ageProfile['education_6_10_other_prev_year'] ?? 'N/A', $ageProfile['education_6_10_other_current_year'] ?? 'N/A'],

        // Children between 11 to 15 years
        ['Children between 11 to 15 years', 'Secondary school', $ageProfile['education_11_15_secondary_school_prev_year'] ?? 'N/A', $ageProfile['education_11_15_secondary_school_current_year'] ?? 'N/A'],
        ['', 'High school', $ageProfile['education_11_15_high_school_prev_year'] ?? 'N/A', $ageProfile['education_11_15_high_school_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_11_15_other_specify'] ?? 'Other', $ageProfile['education_11_15_other_prev_year'] ?? 'N/A', $ageProfile['education_11_15_other_current_year'] ?? 'N/A'],

        // 16 and above
        ['16 and above', 'Undergraduate', $ageProfile['education_16_above_undergraduate_prev_year'] ?? 'N/A', $ageProfile['education_16_above_undergraduate_current_year'] ?? 'N/A'],
        ['', 'Technical/Vocational education', $ageProfile['education_16_above_technical_vocational_prev_year'] ?? 'N/A', $ageProfile['education_16_above_technical_vocational_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_16_above_other_specify'] ?? 'Other', $ageProfile['education_16_above_other_prev_year'] ?? 'N/A', $ageProfile['education_16_above_other_current_year'] ?? 'N/A'],
    ];

    // Populate the table
    $currentCategory = '';
    foreach ($dataRows as $row) {
        $table->addRow();
        if ($row[0] !== $currentCategory) {
            $currentCategory = $row[0];
            $table->addCell(4000, ['vMerge' => 'restart'])->addText($row[0]);
        } else {
            $table->addCell(4000, ['vMerge' => 'continue']);
        }
        $table->addCell(3000)->addText($row[1]); // Education
        $table->addCell(2000)->addText($row[2]); // Previous Year
        $table->addCell(2000)->addText($row[3]); // Current Year
    }
}
//Section - Personal Situation - CHILD CARE INSTITUTION

private function addPersonalSituationSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Personal Situation of Children in the Institution", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Table for Personal Situation Data
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
    ];
    $phpWord->addTableStyle('PersonalSituationTable', $tableStyle);
    $table = $section->addTable('PersonalSituationTable');

    // Add Table Header
    $table->addRow();
    $table->addCell(5000)->addText("Description", ['bold' => true]);
    $table->addCell(3000)->addText("Up to Last Year", ['bold' => true]);
    $table->addCell(3000)->addText("Current Year", ['bold' => true]);

    // Personal Situation Data
    $personalSituation = $project->personal_situation; // Assuming you fetched this relation with the project

    $dataRows = [
        ['Children with parents', $personalSituation->children_with_parents_last_year ?? 'N/A', $personalSituation->children_with_parents_current_year ?? 'N/A'],
        ['Semi-orphans (living with relatives)', $personalSituation->semi_orphans_last_year ?? 'N/A', $personalSituation->semi_orphans_current_year ?? 'N/A'],
        ['Orphans', $personalSituation->orphans_last_year ?? 'N/A', $personalSituation->orphans_current_year ?? 'N/A'],
        ['HIV-infected/affected', $personalSituation->hiv_infected_last_year ?? 'N/A', $personalSituation->hiv_infected_current_year ?? 'N/A'],
        ['Differently-abled children', $personalSituation->differently_abled_last_year ?? 'N/A', $personalSituation->differently_abled_current_year ?? 'N/A'],
        ['Parents in conflict', $personalSituation->parents_in_conflict_last_year ?? 'N/A', $personalSituation->parents_in_conflict_current_year ?? 'N/A'],
        ['Other ailments', $personalSituation->other_ailments_last_year ?? 'N/A', $personalSituation->other_ailments_current_year ?? 'N/A'],
    ];

    foreach ($dataRows as $row) {
        $table->addRow();
        $table->addCell(5000)->addText($row[0]); // Description
        $table->addCell(3000)->addText($row[1]); // Up to Last Year
        $table->addCell(3000)->addText($row[2]); // Current Year
    }

    // Add General Remarks Section
    $section->addTextBreak(1);
    $section->addText("General Remarks", ['bold' => true]);
    $this->addTextWithLineBreaks($section, $personalSituation->general_remarks ?? 'No remarks provided.');
}
//Section - Economic BackgroundS - CHILD CARE INSTITUTION
private function addEconomicBackgroundSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Economic Background of Parents", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Economic Background Data
    $economicBackground = $project->economic_background; // Assuming you fetched this relation with the project

    $dataRows = [
        'Agricultural Labour' => $economicBackground->agricultural_labour_number ?? 'N/A',
        'Marginal Farmers (less than two and half acres)' => $economicBackground->marginal_farmers_number ?? 'N/A',
        'Parents in Self-Employment' => $economicBackground->self_employed_parents_number ?? 'N/A',
        'Parents Working in Informal Sector' => $economicBackground->informal_sector_parents_number ?? 'N/A',
        'Any Other' => $economicBackground->any_other_number ?? 'N/A',
    ];

    // Add data in a table format
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('EconomicBackgroundTable', $tableStyle);
    $table = $section->addTable('EconomicBackgroundTable');

    // Add Table Header
    $table->addRow();
    $table->addCell(7000)->addText("Description", ['bold' => true]);
    $table->addCell(3000)->addText("Value", ['bold' => true]);

    // Add Data Rows
    foreach ($dataRows as $label => $value) {
        $table->addRow();
        $table->addCell(7000)->addText($label);
        $table->addCell(3000)->addText($value);
    }

    // Add General Remarks
    $section->addTextBreak(1);
    $section->addText("General Remarks", ['bold' => true]);
    $this->addTextWithLineBreaks($section, $economicBackground->general_remarks ?? 'No remarks provided.');
}
//Section - Achievements - CHILD CARE INSTITUTION
private function addAchievementsSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Achievements", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    $achievements = $project->achievements; // Assuming this relation is fetched

    // Academic Achievements
    $section->addText("Academic Achievements:", ['bold' => true]);
    if (!empty($achievements->academic_achievements)) {
        foreach ($achievements->academic_achievements as $achievement) {
            $section->addText("- $achievement");
        }
    } else {
        $section->addText("No academic achievements recorded.", ['italic' => true, 'color' => '6c757d']);
    }
    $section->addTextBreak(1);

    // Sports Achievements
    $section->addText("Sports Achievements:", ['bold' => true]);
    if (!empty($achievements->sport_achievements)) {
        foreach ($achievements->sport_achievements as $achievement) {
            $section->addText("- $achievement");
        }
    } else {
        $section->addText("No sports achievements recorded.", ['italic' => true, 'color' => '6c757d']);
    }
    $section->addTextBreak(1);

    // Other Achievements
    $section->addText("Other Achievements:", ['bold' => true]);
    if (!empty($achievements->other_achievements)) {
        foreach ($achievements->other_achievements as $achievement) {
            $section->addText("- $achievement");
        }
    } else {
        $section->addText("No other achievements recorded.", ['italic' => true, 'color' => '6c757d']);
    }
}
// Section - Present Situation - CHILD CARE INSTITUTION
private function addPresentSituationSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header for Present Situation
    $section->addText("Present Situation of the Inmates", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Internal Challenges
    $section->addText("Internal Challenges Faced from Inmates:", ['bold' => true]);
    $this->addTextWithLineBreaks($section, $project->present_situation->internal_challenges ?? 'No internal challenges recorded.');
    $section->addTextBreak(1);

    // Add External Challenges
    $section->addText("External Challenges / Present Difficulties:", ['bold' => true]);
    $this->addTextWithLineBreaks($section, $project->present_situation->external_challenges ?? 'No external challenges recorded.');
    $section->addTextBreak(2);

    // Add Header for Area of Focus
    $section->addText("Area of Focus for the Current Year", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Main Focus Areas
    $section->addText("Main Focus Areas:", ['bold' => true]);
    $this->addTextWithLineBreaks($section, $project->present_situation->area_of_focus ?? 'No focus areas specified.');
    $section->addTextBreak(1);
}


// Residential Skill Training Specific Functions
private function addRSTSections(PhpWord $phpWord, $project)
{
    $this->addInstitutionInfoSection($phpWord, $project);
    $this->addBeneficiariesAreaSection($phpWord, $project);
    $this->addTargetGroupSection($phpWord, $project);
    $this->addTargetGroupAnnexureSection($phpWord, $project);
    $this->addGeographicalAreaSection($phpWord, $project);
}
// Section - Institution Info - Residential Skill Training
private function addInstitutionInfoSection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add the header
    $section->addText("Institution Information", ['bold' => true, 'size' => 14]);

    // Fetch RST Institution Info (assuming the relation or accessor is $project->RSTInstitutionInfo)
    $institutionInfo = $project->RSTInstitutionInfo;

    // Define the info labels and corresponding values
    $infoItems = [
        'Year the Training Center was set up:' => $institutionInfo?->year_setup ?? 'No data available.',
        'Total Students Trained Till Date:' => $institutionInfo?->total_students_trained ?? 'No data available.',
        'Beneficiaries Trained in the Last Year:' => $institutionInfo?->beneficiaries_last_year ?? 'No data available.',
        'Outcome/Impact of the Training:' => $institutionInfo?->training_outcome ?? 'No data available.',
    ];

    // Add the info grid (label-value pairs)
    foreach ($infoItems as $label => $value) {
        $section->addText($label, ['bold' => true]);
        $section->addText($value);
        $section->addTextBreak(0.5); // Add spacing between entries
    }

    $section->addTextBreak(1); // Add extra space after the section
}
// Section - Beneficiaries Area - Residential Skill Training
private function addBeneficiariesAreaSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Project Area", ['bold' => true, 'size' => 14]);

    // Add a table to represent the data
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 50
    ];
    $firstRowStyle = [
        'bgColor' => 'cccccc', // Light gray background
        'bold' => true
    ];

    // Register table style
    $phpWord->addTableStyle('BeneficiariesAreaTable', $tableStyle);

    // Create table
    $table = $section->addTable('BeneficiariesAreaTable');

    // Add header row
    $table->addRow();
    $table->addCell(3000)->addText("Project Area", $firstRowStyle);
    $table->addCell(3000)->addText("Category of Beneficiary", $firstRowStyle);
    $table->addCell(2000)->addText("Direct Beneficiaries", $firstRowStyle);
    $table->addCell(2000)->addText("Indirect Beneficiaries", $firstRowStyle);

    // Check if data exists
    if ($project->beneficiaries_area && $project->beneficiaries_area->isNotEmpty()) {
        // Loop through the beneficiaries area
        foreach ($project->beneficiaries_area as $area) {
            $table->addRow();
            $table->addCell(3000)->addText($area->project_area ?? 'N/A');
            $table->addCell(3000)->addText($area->category_beneficiary ?? 'N/A');
            $table->addCell(2000)->addText($area->direct_beneficiaries ?? 'N/A');
            $table->addCell(2000)->addText($area->indirect_beneficiaries ?? 'N/A');
        }
    } else {
        // Add a row indicating no data
        $table->addRow();
        $table->addCell(0, ['gridSpan' => 4])->addText("No project area data recorded.", ['italic' => true], ['alignment' => 'center']);
    }

    $section->addTextBreak(1); // Add spacing after the table
}
// Section - Target Group - Residential Skill Training
private function addTargetGroupSection(PhpWord $phpWord, $project)
{
    // Add a new section to the Word document
    $section = $phpWord->addSection();
    $section->addText("Target Group", ['bold' => true, 'size' => 14]);

    // Check if the collection has data
    if ($project->RSTTargetGroup->isNotEmpty()) {
        // Add table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('TargetGroupTableStyle', $tableStyle);

        // Create a table
        $table = $section->addTable('TargetGroupTableStyle');

        // Add table header row
        $table->addRow();
        $table->addCell(1000)->addText("S.No.", ['bold' => true]);
        $table->addCell(3000)->addText("Number of Beneficiaries", ['bold' => true]);
        $table->addCell(6000)->addText("Description of Problems", ['bold' => true]);

        // Loop through all rows in RSTTargetGroup
        foreach ($project->RSTTargetGroup as $index => $targetGroup) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($targetGroup->tg_no_of_beneficiaries ?? 'N/A');
            $problemCell = $table->addCell(6000);
            $this->addTextWithLineBreaks($problemCell, $targetGroup->beneficiaries_description_problems ?? 'N/A');
        }
    } else {
        // If no data is available
        $section->addText("No target group data available for this project.", ['italic' => true]);
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}

// Section - Target Group Annexure - Residential Skill Training
private function addTargetGroupAnnexureSection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();
    $section->addText("Target Group Annexure", ['bold' => true, 'size' => 14]);

    // Check if annexures are available
    $RSTTargetGroupAnnexure = $project->target_group_annexure ?? collect();
    if ($RSTTargetGroupAnnexure->isEmpty()) {
        $section->addText("No data available for Target Group Annexure.", ['italic' => true]);
        return;
    }

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 80
    ];
    $firstRowStyle = [
        'bgColor' => 'f2f2f2', // Light gray background for header
        'bold' => true
    ];
    $phpWord->addTableStyle('TargetGroupAnnexureTable', $tableStyle);

    // Create table
    $table = $section->addTable('TargetGroupAnnexureTable');

    // Add table headers
    $table->addRow();
    $table->addCell(2000)->addText("Name", $firstRowStyle);
    $table->addCell(2000)->addText("Religion", $firstRowStyle);
    $table->addCell(2000)->addText("Caste", $firstRowStyle);
    $table->addCell(3000)->addText("Education Background", $firstRowStyle);
    $table->addCell(3000)->addText("Family Situation", $firstRowStyle);
    $table->addCell(3000)->addText("Paragraph", $firstRowStyle);

    // Populate table rows
    foreach ($RSTTargetGroupAnnexure as $annexure) {
        $table->addRow();
        $table->addCell(2000)->addText($annexure->rst_name ?? 'N/A');
        $table->addCell(2000)->addText($annexure->rst_religion ?? 'N/A');
        $table->addCell(2000)->addText($annexure->rst_caste ?? 'N/A');
        $educationCell = $table->addCell(3000);
        $this->addTextWithLineBreaks($educationCell, $annexure->rst_education_background ?? 'N/A');
        $familyCell = $table->addCell(3000);
        $this->addTextWithLineBreaks($familyCell, $annexure->rst_family_situation ?? 'N/A');
        $paragraphCell = $table->addCell(3000);
        $this->addTextWithLineBreaks($paragraphCell, $annexure->rst_paragraph ?? 'N/A');
    }

    // Add spacing after table
    $section->addTextBreak(1);
}
// Section - Geographical Area - Residential Skill Training
private function addGeographicalAreaSection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add a title for the section
    $section->addText("Geographical Area of Beneficiaries", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add some space after the title

    // Check if there is data to display
    if ($project->geographical_area && $project->geographical_area->count() > 0) {
        // Define a table style
        $tableStyle = [
            'borderSize' => 6, // 0.75pt
            'borderColor' => '999999', // Light gray
            'cellMargin' => 80 // Padding inside cells
        ];
        $phpWord->addTableStyle('GeographicalTableStyle', $tableStyle);

        // Add the table
        $table = $section->addTable('GeographicalTableStyle');

        // Add the header row
        $table->addRow();
        $table->addCell(2000)->addText("Mandal", ['bold' => true]);
        $table->addCell(3000)->addText("Villages", ['bold' => true]);
        $table->addCell(3000)->addText("Town", ['bold' => true]);
        $table->addCell(3000)->addText("No of Beneficiaries", ['bold' => true]);

        // Loop through the geographical areas
        foreach ($project->geographical_area as $area) {
            $table->addRow();
            $table->addCell(2000)->addText($area->mandal ?? 'N/A');
            $table->addCell(3000)->addText($area->villages ?? 'N/A');
            $table->addCell(3000)->addText($area->town ?? 'N/A');
            $table->addCell(3000)->addText($area->no_of_beneficiaries ?? 'N/A');
        }
    } else {
        // Add a message for no data
        $section->addText("No geographical area data recorded.", ['italic' => true, 'size' => 12]);
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}



// Rural Urban Tribal Specific Functions
private function addEduRUTSections(PhpWord $phpWord, $project)
{
    $this->addEduRUTBasicInfoSection($phpWord, $project);
    $this->addEduRUTTargetGroupSection($phpWord, $project);
}

// Section - RUT Basic Info - Rural Urban Tribal
private function addEduRUTBasicInfoSection(PhpWord $phpWord, $basicInfo)
{
    $section = $phpWord->addSection();
    $section->addText("Basic Information of Project's Operational Area", ['bold' => true, 'size' => 14]);

    if ($basicInfo) {
        // Add a table for structured layout
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);

        // Institution Type
        $table->addRow();
        $table->addCell(5000)->addText("Institution Type:");
        $table->addCell(5000)->addText($basicInfo->institution_type ?? 'N/A');

        // Group Type
        $table->addRow();
        $table->addCell(5000)->addText("Group Type:");
        $table->addCell(5000)->addText($basicInfo->group_type ?? 'N/A');

        // Category
        $table->addRow();
        $table->addCell(5000)->addText("Category:");
        $table->addCell(5000)->addText($basicInfo->category ?? 'N/A');

        // Project Location
        $table->addRow();
        $table->addCell(5000)->addText("Project Location:");
        $table->addCell(5000)->addText($basicInfo->project_location ?? 'N/A');

        // Sisters' Work
        $table->addRow();
        $table->addCell(5000)->addText("Work of Sisters in the Project Area:");
        $table->addCell(5000)->addText($basicInfo->sisters_work ?? 'N/A');

        // Socio-Economic and Cultural Conditions
        $table->addRow();
        $table->addCell(5000)->addText("Socio-Economic and Cultural Conditions:");
        $table->addCell(5000)->addText($basicInfo->conditions ?? 'N/A');

        // Problems
        $table->addRow();
        $table->addCell(5000)->addText("Problems Identified and Their Consequences:");
        $table->addCell(5000)->addText($basicInfo->problems ?? 'N/A');

        // Need
        $table->addRow();
        $table->addCell(5000)->addText("Need of the Project:");
        $table->addCell(5000)->addText($basicInfo->need ?? 'N/A');

        // Criteria
        $table->addRow();
        $table->addCell(5000)->addText("Criteria for Selecting the Target Group:");
        $table->addCell(5000)->addText($basicInfo->criteria ?? 'N/A');
    } else {
        // If no basic info is available
        $section->addText("No basic information available for this project.", ['italic' => true]);
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - RUT Target Group - Rural Urban Tribal
private function addEduRUTTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Edu-Rural-Urban-Tribal - Target Group", ['bold' => true, 'size' => 14]);

    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('TargetGroupTableStyle', $tableStyle);

    $table = $section->addTable('TargetGroupTableStyle');

    // Add header row
    $table->addRow();
    $table->addCell(500)->addText("S.No.", ['bold' => true]);
    $table->addCell(2000)->addText("Beneficiary Name", ['bold' => true]);
    $table->addCell(1500)->addText("Caste", ['bold' => true]);
    $table->addCell(2000)->addText("Name of Institution", ['bold' => true]);
    $table->addCell(1500)->addText("Class / Standard", ['bold' => true]);
    $table->addCell(1500)->addText("Total Tuition Fee", ['bold' => true]);
    $table->addCell(2000)->addText("Eligibility of Scholarship", ['bold' => true]);
    $table->addCell(1500)->addText("Expected Amount", ['bold' => true]);
    $table->addCell(2000)->addText("Contribution from Family", ['bold' => true]);

    // Add rows dynamically based on the data
    if ($project->target_groups && $project->target_groups->count() > 0) {
        foreach ($project->target_groups as $index => $group) {
            $table->addRow();
            $table->addCell(500)->addText($index + 1); // S.No.
            $table->addCell(2000)->addText($group->beneficiary_name ?? 'N/A');
            $table->addCell(1500)->addText($group->caste ?? 'N/A');
            $table->addCell(2000)->addText($group->institution_name ?? 'N/A');
            $table->addCell(1500)->addText($group->class_standard ?? 'N/A');
            $table->addCell(1500)->addText($group->total_tuition_fee ? \App\Helpers\NumberFormatHelper::formatIndianCurrency($group->total_tuition_fee, 2) : 'N/A');
            $table->addCell(2000)->addText($group->eligibility_scholarship ? 'Yes' : 'No');
            $table->addCell(1500)->addText($group->expected_amount ? \App\Helpers\NumberFormatHelper::formatIndianCurrency($group->expected_amount, 2) : 'N/A');
            $table->addCell(2000)->addText($group->contribution_from_family ? \App\Helpers\NumberFormatHelper::formatIndianCurrency($group->contribution_from_family, 2) : 'N/A');
        }
    } else {
        // No data available
        $table->addRow();
        $table->addCell(9000, ['gridSpan' => 9])->addText("No target group data available.", ['italic' => true], ['align' => 'center']);
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - RUT Annexed Target Group - Rural Urban Tribal
private function addEduRUTAnnexedTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Annexed Target Group", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add spacing

    // Define the table style
    $tableStyle = [
        'borderSize' => 6, // 1pt = 8 twips, 6 twips ≈ 0.75pt
        'borderColor' => '000000',
        'cellMargin' => 50, // Padding inside cells
    ];
    $phpWord->addTableStyle('AnnexedTargetGroupTable', $tableStyle);
    $table = $section->addTable('AnnexedTargetGroupTable');

    // Add header row
    $table->addRow();
    $table->addCell(500)->addText("S.No.", ['bold' => true]);
    $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
    $table->addCell(5000)->addText("Family Background", ['bold' => true]);
    $table->addCell(3000)->addText("Need of Support", ['bold' => true]);

    // Check if data exists
    if ($project->annexed_target_groups && $project->annexed_target_groups->count() > 0) {
        foreach ($project->annexed_target_groups as $index => $group) {
            $table->addRow();
            $table->addCell(500)->addText($index + 1);
            $table->addCell(3000)->addText($group->beneficiary_name ?? 'N/A');
            $familyBgCell = $table->addCell(5000);
            $this->addTextWithLineBreaks($familyBgCell, $group->family_background ?? 'N/A');
            $needCell = $table->addCell(3000);
            $this->addTextWithLineBreaks($needCell, $group->need_of_support ?? 'N/A');
        }
    } else {
        // No data available
        $table->addRow();
        $table->addCell(11500, ['gridSpan' => 4])->addText("No Annexed Target Group data available.");
    }

    $section->addTextBreak(1); // Add spacing after the table
}


// Institutional Ongoing Group Educational proposal Specific Functions
private function addIGESpecificSections(PhpWord $phpWord, $project)
{
    $this->addIGEInstitutionInfoSection($phpWord, $project);
    $this->addIGEBeneficiariesSupportedSection($phpWord, $project);
    $this->addIGEOngoingBeneficiariesSection($phpWord, $project);
    $this->addIGENewBeneficiariesSection($phpWord, $project);
    $this->addIGEBudgetSection($phpWord, $project);
    $this->addIGEDevelopmentMonitoringSection($phpWord, $project);
}

// Section - IGE Institution Info - Institutional Ongoing Group Educational proposal
private function addIGEInstitutionInfoSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Institution Information", ['bold' => true, 'size' => 14]);

    // Create a table for institution info
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('InstitutionInfoTable', $tableStyle);
    $table = $section->addTable('InstitutionInfoTable');

    // Add the headers
    $table->addRow();
    $table->addCell(5000)->addText("Field", ['bold' => true]);
    $table->addCell(5000)->addText("Details", ['bold' => true]);

    // Add the rows with data
    $table->addRow();
    $table->addCell(5000)->addText("Institutional Type");
    $table->addCell(5000)->addText($project->institution_info?->institutional_type ?? 'N/A');

    $table->addRow();
    $table->addCell(5000)->addText("Age Group");
    $table->addCell(5000)->addText($project->institution_info?->age_group ?? 'N/A');

    $table->addRow();
    $table->addCell(5000)->addText("Number of Beneficiaries (Previous Years)");
    $table->addCell(5000)->addText($project->institution_info?->previous_year_beneficiaries ?? 'N/A');

    $table->addRow();
    $table->addCell(5000)->addText("Outcome/Impact");
    $table->addCell(5000)->addText($project->institution_info?->outcome_impact ?? 'No information provided.');

    // Add spacing after the section
    $section->addTextBreak(1);
}
// Section - IGE Beneficiaries Supported - Institutional Ongoing Group Educational proposal
private function addIGEBeneficiariesSupportedSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Number of Beneficiaries Supported this Year", ['bold' => true, 'size' => 14]);

    $beneficiariesSupported = $project->beneficiaries_supported;

    if ($beneficiariesSupported && $beneficiariesSupported->count()) {
        // Define table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('BeneficiariesTable', $tableStyle);

        // Add table
        $table = $section->addTable('BeneficiariesTable');

        // Add table headers
        $table->addRow();
        $table->addCell(1000)->addText("S.No", ['bold' => true]);
        $table->addCell(3000)->addText("Class", ['bold' => true]);
        $table->addCell(3000)->addText("Total Number", ['bold' => true]);

        // Add beneficiaries data
        foreach ($beneficiariesSupported as $index => $beneficiary) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($beneficiary->class);
            $table->addCell(3000)->addText($beneficiary->total_number);
        }
    } else {
        // Add fallback text if no data is available
        $section->addText("No beneficiaries supported data available.", ['italic' => true]);
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - IGE Ongoing Beneficiaries - Institutional Ongoing Group Educational proposal
private function addIGEOngoingBeneficiariesSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Ongoing Beneficiaries", ['bold' => true, 'size' => 14]);

    if ($project->ongoing_beneficiaries->isNotEmpty()) {
        // Define table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('OngoingBeneficiariesTable', $tableStyle);

        // Add table to the section
        $table = $section->addTable('OngoingBeneficiariesTable');

        // Add table header row
        $table->addRow();
        $table->addCell(1000)->addText("S.No", ['bold' => true]);
        $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
        $table->addCell(2000)->addText("Caste", ['bold' => true]);
        $table->addCell(3000)->addText("Address", ['bold' => true]);
        $table->addCell(3000)->addText("Present Group / Year of Study", ['bold' => true]);
        $table->addCell(3000)->addText("Performance Details", ['bold' => true]);

        // Add data rows
        foreach ($project->ongoing_beneficiaries as $index => $beneficiary) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($beneficiary->obeneficiary_name ?? 'N/A');
            $table->addCell(2000)->addText($beneficiary->ocaste ?? 'N/A');
            $oaddressCell = $table->addCell(3000);
            $this->addTextWithLineBreaks($oaddressCell, $beneficiary->oaddress ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->ocurrent_group_year_of_study ?? 'N/A');
            $perfCell = $table->addCell(3000);
            $this->addTextWithLineBreaks($perfCell, $beneficiary->operformance_details ?? 'N/A');
        }
    } else {
        // Add a fallback text if no data is found
        $section->addText("No ongoing beneficiaries found for this project.");
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - IGE New Beneficiaries - Institutional Ongoing Group Educational proposal
private function addIGENewBeneficiariesSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("New Beneficiaries", ['bold' => true, 'size' => 14]);

    // Check if there are new beneficiaries
    if ($project->new_beneficiaries->isNotEmpty()) {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('NewBeneficiariesTable', $tableStyle);
        $table = $section->addTable('NewBeneficiariesTable');

        // Add table header
        $table->addRow();
        $table->addCell(1000)->addText("S.No", ['bold' => true]);
        $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
        $table->addCell(2000)->addText("Caste", ['bold' => true]);
        $table->addCell(3000)->addText("Address", ['bold' => true]);
        $table->addCell(3000)->addText("Group / Year of Study", ['bold' => true]);
        $table->addCell(4000)->addText("Family Background and Need of Support", ['bold' => true]);

        // Add beneficiary data
        foreach ($project->new_beneficiaries as $index => $beneficiary) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($beneficiary->beneficiary_name ?? 'N/A');
            $table->addCell(2000)->addText($beneficiary->caste ?? 'N/A');
            $addressCell = $table->addCell(3000);
            $this->addTextWithLineBreaks($addressCell, $beneficiary->address ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->group_year_of_study ?? 'N/A');
            $familyBgNeedCell = $table->addCell(4000);
            $this->addTextWithLineBreaks($familyBgNeedCell, $beneficiary->family_background_need ?? 'N/A');
        }
    } else {
        // Add message if no beneficiaries are recorded
        $section->addText("No new beneficiaries recorded.", ['italic' => true]);
    }

    $section->addTextBreak(1); // Add space after the section
}
// Section - IGE Budget - Institutional Ongoing Group Educational proposal
private function addIGEBudgetSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Budget for Current Year", ['bold' => true, 'size' => 14]);

    if ($project->budget && $project->budget->isNotEmpty()) {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ];

        $phpWord->addTableStyle('BudgetTable', $tableStyle);
        $table = $section->addTable('BudgetTable');

        // Add table header
        $table->addRow();
        $headers = [
            "S.No", "Name", "Study Proposed to Be", "College Fees",
            "Hostel Fees", "Total Amount", "Scholarship Eligibility",
            "Family Contribution", "Amount Requested"
        ];

        foreach ($headers as $header) {
            $table->addCell(2000)->addText($header, ['bold' => true]);
        }

        // Initialize totals
        $totalCollegeFees = 0;
        $totalHostelFees = 0;
        $totalAmount = 0;
        $totalScholarshipEligibility = 0;
        $totalFamilyContribution = 0;
        $totalAmountRequested = 0;

        // Add table rows
        foreach ($project->budget as $index => $budget) {
            $collegeFees = $budget->college_fees ?? 0;
            $hostelFees = $budget->hostel_fees ?? 0;
            $totalRowAmount = $budget->total_amount ?? 0;
            $scholarshipEligibility = $budget->scholarship_eligibility ?? 0;
            $familyContribution = $budget->family_contribution ?? 0;
            $amountRequested = $budget->amount_requested ?? 0;

            $totalCollegeFees += $collegeFees;
            $totalHostelFees += $hostelFees;
            $totalAmount += $totalRowAmount;
            $totalScholarshipEligibility += $scholarshipEligibility;
            $totalFamilyContribution += $familyContribution;
            $totalAmountRequested += $amountRequested;

            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(2000)->addText($budget->name ?? 'N/A');
            $table->addCell(2000)->addText($budget->study_proposed ?? 'N/A');
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($collegeFees, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($hostelFees, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalRowAmount, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($scholarshipEligibility, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($familyContribution, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($amountRequested, 2));
        }

        // Add totals row
        $table->addRow();
        $table->addCell(3000, ['gridSpan' => 3])->addText("Totals", ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalCollegeFees, 2), ['bold' => true]);
        $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalHostelFees, 2), ['bold' => true]);
        $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalAmount, 2), ['bold' => true]);
        $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalScholarshipEligibility, 2), ['bold' => true]);
        $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalFamilyContribution, 2), ['bold' => true]);
        $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndian($totalAmountRequested, 2), ['bold' => true]);
    } else {
        $section->addText("No budget data available for this project.", ['italic' => true]);
    }
}
// Section - IGE Development Monitoring - Institutional Ongoing Group Educational proposal
private function addIGEDevelopmentMonitoringSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    $section->addText("Development Monitoring", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    $developmentMonitoring = $project->development_monitoring;

    // Add Proposed Activities
    $section->addText("Proposed Activities for Overall Development:", ['bold' => true]);
    $section->addText($developmentMonitoring?->proposed_activities ?? 'No data provided.');
    $section->addTextBreak(1);

    // Add Monitoring Methods
    $section->addText("Methods of Monitoring the Beneficiaries' Growth:", ['bold' => true]);
    $section->addText($developmentMonitoring?->monitoring_methods ?? 'No data provided.');
    $section->addTextBreak(1);

    // Add Evaluation Process and Responsibility
    $section->addText("Process of Evaluation and Responsibility:", ['bold' => true]);
    $section->addText($developmentMonitoring?->evaluation_process ?? 'No data provided.');
    $section->addTextBreak(1);

    // Add Conclusion
    $section->addText("Conclusion:", ['bold' => true]);
    $section->addText($developmentMonitoring?->conclusion ?? 'No data provided.');
    $section->addTextBreak(1);
}



// LDP - Livelihood Development Project Specific Functions
private function addLDPSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Livelihood Development Project Details", ['bold' => true, 'size' => 16]);

    // Call separate methods for each partial
    $this->addNeedAnalysisSection($phpWord, $project);
    $this->addLDPTargetGroupSection($phpWord, $project);
    $this->addInterventionLogicSection($phpWord, $project);
}
// Section - Need Analysis - LDP - Livelihood Development Project
private function addNeedAnalysisSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Need Analysis", ['bold' => true, 'size' => 16]);

    // Check if need analysis data exists
    if ($project->needAnalysis && $project->needAnalysis->document_path) {
        $documentName = basename($project->needAnalysis->document_path); // Extract document name
        $section->addText("Status: Document Uploaded", ['bold' => true]);
        $section->addText("Document Name: {$documentName}", ['size' => 12]);
    } else {
        $section->addText("Status: No document uploaded yet.", ['bold' => true]);
    }

    // Add spacing after the section
    $section->addTextBreak(1);
}
// Section - Target Group - LDP - Livelihood Development Project
private function addLDPTargetGroupSection(PhpWord $phpWord, $project)
{
    // Add a new section
    $section = $phpWord->addSection();

    // Add a title for the section
    $section->addText(
        "Annexed Target Group: Livelihood Development Projects",
        ['bold' => true, 'size' => 14],
        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
    );

    // Add spacing before the table
    $section->addTextBreak(1);

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 80,
    ];
    $firstRowStyle = ['bgColor' => '202BA3'];
    $phpWord->addTableStyle('TargetGroupTable', $tableStyle, $firstRowStyle);

    // Create table
    $table = $section->addTable('TargetGroupTable');

    // Add table header row
    $table->addRow();
    $table->addCell(1000, ['bgColor' => '202BA3'])->addText(
        "S.No.",
        ['bold' => true, 'color' => 'FFFFFF'],
        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
    );
    $table->addCell(3000, ['bgColor' => '202BA3'])->addText(
        "Beneficiary Name",
        ['bold' => true, 'color' => 'FFFFFF']
    );
    $table->addCell(3000, ['bgColor' => '202BA3'])->addText(
        "Family Situation",
        ['bold' => true, 'color' => 'FFFFFF']
    );
    $table->addCell(3000, ['bgColor' => '202BA3'])->addText(
        "Nature of Livelihood",
        ['bold' => true, 'color' => 'FFFFFF']
    );
    $table->addCell(2000, ['bgColor' => '202BA3'])->addText(
        "Amount Requested",
        ['bold' => true, 'color' => 'FFFFFF']
    );

    // Add data rows
    if (!empty($project->targetGroups) && $project->targetGroups->isNotEmpty()) {
        foreach ($project->targetGroups as $index => $targetGroup) {
            $table->addRow();
            $table->addCell(1000)->addText(($index + 1), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $table->addCell(3000)->addText($targetGroup->L_beneficiary_name ?? 'N/A');
            $familySituationCell = $table->addCell(3000);
            $this->addTextWithLineBreaks($familySituationCell, $targetGroup->L_family_situation ?? 'N/A');
            $table->addCell(3000)->addText($targetGroup->L_nature_of_livelihood ?? 'N/A');
            $table->addCell(2000)->addText($targetGroup->L_amount_requested ? \App\Helpers\NumberFormatHelper::formatIndianCurrency($targetGroup->L_amount_requested, 2) : 'N/A');
        }
    } else {
        // Add a row indicating no data is available
        $table->addRow();
        $table->addCell(0, ['gridSpan' => 5])->addText(
            "No target groups available.",
            ['italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}
// Section - Intervention Logic - LDP - Livelihood Development Project
private function addInterventionLogicSection(PhpWord $phpWord, $project)
{
    // Retrieve intervention logic data
    $interventionLogic = $project->interventionLogic; // Assuming this is a relationship or attribute on the project

    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add the header for the section
    $section->addText("Intervention Logic", ['bold' => true, 'size' => 16]);
    $section->addText("Description of how the project's interventions alleviate the existing problems.", ['italic' => true, 'size' => 12]);
    $section->addTextBreak(1);

    // Add the description
    if ($interventionLogic && $interventionLogic->intervention_description) {
        $section->addText("Description:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $interventionLogic->intervention_description);
    } else {
        $section->addText("No intervention logic provided.", ['italic' => true]);
    }

    // Add spacing after the section
    $section->addTextBreak(1);
}


// common sections
// Logical Framework
private function addLogicalFrameworkSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Header for Logical Framework
    $section->addText("Logical Framework", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Loop through each objective with index
    foreach ($project->objectives as $objIndex => $objective) {
        // Objective Header with index
        $section->addText("Objective " . ($objIndex + 1) . ":", ['bold' => true, 'size' => 14]);
        $this->addTextWithLineBreaks($section, $objective->objective);
        $section->addTextBreak(0.5);

        // Results / Outcomes with nested index
        $section->addText("Results / Outcomes:", ['bold' => true, 'size' => 12]);
        foreach ($objective->results as $resIndex => $result) {
            $section->addText("Objective " . ($objIndex + 1) . " - Result " . ($resIndex + 1) . ":", ['bold' => true, 'size' => 11]);
            $this->addTextWithLineBreaks($section, $result->result);
        }
        $section->addTextBreak(0.5);

        // Risks Section with nested index
        if ($objective->risks->isNotEmpty()) {
            $section->addText("Risks:", ['bold' => true, 'size' => 12]);
            foreach ($objective->risks as $riskIndex => $risk) {
                $section->addText("Objective " . ($objIndex + 1) . " - Risk " . ($riskIndex + 1) . ":", ['bold' => true, 'size' => 11]);
                $this->addTextWithLineBreaks($section, $risk->risk);
            }
        }
        $section->addTextBreak(0.5);

        // Activities and Means of Verification Table
        $section->addText("Activities and Means of Verification:", ['bold' => true, 'size' => 12]);
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('ActivitiesTable', $tableStyle);
        $table = $section->addTable('ActivitiesTable');

        // Table Header with index column
        $table->addRow();
        $table->addCell(500)->addText("No.", ['bold' => true]);
        $table->addCell(4500)->addText("Activities", ['bold' => true]);
        $table->addCell(5000)->addText("Means of Verification", ['bold' => true]);

        foreach ($objective->activities as $actIndex => $activity) {
            $table->addRow();
            $table->addCell(500)->addText($actIndex + 1);
            $activityCell = $table->addCell(4500);
            $this->addTextWithLineBreaks($activityCell, $activity->activity);
            $verificationCell = $table->addCell(5000);
            $this->addTextWithLineBreaks($verificationCell, $activity->verification);
        }
        $section->addTextBreak(1);

        // Time Frame Table
        $section->addText("Time Frame for Activities:", ['bold' => true, 'size' => 12]);
        $table = $section->addTable('ActivitiesTable');

        // Add table header for months with index column
        $table->addRow();
        $table->addCell(500)->addText("No.", ['bold' => true]);
        $table->addCell(4500)->addText("Activities", ['bold' => true]);
        foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $month) {
            $table->addCell(1000)->addText($month, ['bold' => true]);
        }

        // Add activities with time frames and index numbers
        foreach ($objective->activities as $actIndex => $activity) {
            $table->addRow();
            $table->addCell(500)->addText($actIndex + 1);
            $activityCell = $table->addCell(4500);
            $this->addTextWithLineBreaks($activityCell, $activity->activity);

            // Loop through months and add checkmark if active
            foreach (range(1, 12) as $month) {
                $isChecked = $activity->timeframes->contains(function ($timeframe) use ($month) {
                    return $timeframe->month == $month && $timeframe->is_active == 1;
                });
                $table->addCell(1000)->addText($isChecked ? '✔' : '');
            }
        }

        $section->addTextBreak(1);
    }
}
// Sustainability
private function addSustainabilitySection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add the section header
    $section->addText("Project Sustainability, Monitoring, and Methodologies", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    if ($project->sustainabilities->isEmpty()) {
        $section->addText("No sustainability information is available for this project.", ['italic' => true]);
        return;
    }

    // Define table style for grid layout with proper spacing
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 200, // Increased margin for better spacing between label and content
    ];
    $phpWord->addTableStyle('SustainabilityTable', $tableStyle);

    // Add the sustainability details in a table format with proper spacing
    foreach ($project->sustainabilities as $sustainability) {
        $table = $section->addTable('SustainabilityTable');

        // Sustainability of the Project
        $table->addRow();
        $labelCell = $table->addCell(5000);
        $labelCell->addText("Sustainability of the Project:", ['bold' => true, 'size' => 12]);
        $labelCell->addTextBreak(0.8); // Space after label
        $contentCell = $table->addCell(7000);
        $this->addTextWithLineBreaks($contentCell, $sustainability->sustainability ?? 'N/A');
        $contentCell->addTextBreak(1.2); // Space after content
        
        // Add spacing row (empty row for visual separation)
        $table->addRow();
        $spacingCell1 = $table->addCell(5000);
        $spacingCell1->addTextBreak(1.2); // Spacing in empty cell for visual separation
        $spacingCell2 = $table->addCell(7000);
        $spacingCell2->addTextBreak(1.2); // Spacing in empty cell for visual separation

        // Monitoring Process
        $table->addRow();
        $labelCell = $table->addCell(5000);
        $labelCell->addText("Monitoring Process of the Project:", ['bold' => true, 'size' => 12]);
        $labelCell->addTextBreak(0.8); // Space after label
        $contentCell = $table->addCell(7000);
        $this->addTextWithLineBreaks($contentCell, $sustainability->monitoring_process ?? 'N/A');
        $contentCell->addTextBreak(1.2); // Space after content
        
        // Add spacing row (empty row for visual separation)
        $table->addRow();
        $spacingCell1 = $table->addCell(5000);
        $spacingCell1->addTextBreak(1.2); // Spacing in empty cell for visual separation
        $spacingCell2 = $table->addCell(7000);
        $spacingCell2->addTextBreak(1.2); // Spacing in empty cell for visual separation

        // Reporting Methodology
        $table->addRow();
        $labelCell = $table->addCell(5000);
        $labelCell->addText("Methodology of Reporting:", ['bold' => true, 'size' => 12]);
        $labelCell->addTextBreak(0.8); // Space after label
        $contentCell = $table->addCell(7000);
        $this->addTextWithLineBreaks($contentCell, $sustainability->reporting_methodology ?? 'N/A');
        $contentCell->addTextBreak(1.2); // Space after content
        
        // Add spacing row (empty row for visual separation)
        $table->addRow();
        $spacingCell1 = $table->addCell(5000);
        $spacingCell1->addTextBreak(1.2); // Spacing in empty cell for visual separation
        $spacingCell2 = $table->addCell(7000);
        $spacingCell2->addTextBreak(1.2); // Spacing in empty cell for visual separation

        // Evaluation Methodology
        $table->addRow();
        $labelCell = $table->addCell(5000);
        $labelCell->addText("Methodology of Evaluation:", ['bold' => true, 'size' => 12]);
        $labelCell->addTextBreak(0.8); // Space after label
        $contentCell = $table->addCell(7000);
        $this->addTextWithLineBreaks($contentCell, $sustainability->evaluation_methodology ?? 'N/A');

        // Add spacing after each sustainability entry
        $section->addTextBreak(1.5);
    }
}
// Budget
private function addBudgetSection(PhpWord $phpWord, $project)
{
    // Create a new section for the budget details
    $section = $phpWord->addSection();
    $section->addText("Budget", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Group budgets by phase
    $groupedBudgets = $project->budgets->groupBy('phase');

    foreach ($groupedBudgets as $phase => $budgets) {
        // Add Phase Header
        $section->addText("Phase {$phase}", ['bold' => true, 'size' => 14]);
        $section->addText("Amount Sanctioned in Phase {$phase}: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($budgets->sum('this_phase'), 2));
        $section->addTextBreak(1);

        // Define table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('BudgetTable', $tableStyle);

        // Create the table
        $table = $section->addTable('BudgetTable');

        // Add table header
        $table->addRow();
        $table->addCell(500)->addText("No.", ['bold' => true]);
        $table->addCell(3500)->addText("Particular", ['bold' => true]);
        $table->addCell(1500)->addText("Costs", ['bold' => true]);
        $table->addCell(1500)->addText("Rate Multiplier", ['bold' => true]);
        $table->addCell(1500)->addText("Rate Duration", ['bold' => true]);
        $table->addCell(1500)->addText("Rate Increase (next phase)", ['bold' => true]);
        $table->addCell(1500)->addText("This Phase (Auto)", ['bold' => true]);
        $table->addCell(1500)->addText("Next Phase (Auto)", ['bold' => true]);

        // Add table rows with index numbers
        foreach ($budgets as $index => $budget) {
            $table->addRow();
            $table->addCell(500)->addText($index + 1);
            $cell = $table->addCell(3500);
            $this->addTextWithLineBreaks($cell, $budget->particular);
            $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budget->rate_quantity, 2));
            $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budget->rate_multiplier, 2));
            $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budget->rate_duration, 2));
            $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budget->rate_increase, 2));
            $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budget->this_phase, 2));
            $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budget->next_phase, 2));
        }

        // Add table footer for totals
        $table->addRow();
        $table->addCell(500)->addText(""); // Empty cell for No. column
        $table->addCell(3500)->addText("Total", ['bold' => true]);
        $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budgets->sum('rate_quantity'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budgets->sum('rate_multiplier'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budgets->sum('rate_duration'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budgets->sum('rate_increase'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budgets->sum('this_phase'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndian($budgets->sum('next_phase'), 2), ['bold' => true]);

        // Add spacing between phases
        $section->addTextBreak(1);
    }
}
// common Attachements of group projects
private function addAttachmentsSection(PhpWord $phpWord, $project)
{
    // Add a new section in the Word document
    $section = $phpWord->addSection();

    // Add the section header
    $section->addText("Attachments", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Check if attachments exist
    if ($project->attachments->isEmpty()) {
        $section->addText("No attachments available.", ['italic' => true]);
        return;
    }

    // Define table style for better presentation
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('AttachmentsTable', $tableStyle);
    $table = $section->addTable('AttachmentsTable');

    // Add table headers
    $table->addRow();
    $table->addCell(800)->addText("No.", ['bold' => true]);
    $table->addCell(4200)->addText("Attachment Name", ['bold' => true]);
    $table->addCell(5000)->addText("Description", ['bold' => true]);

    // Loop through attachments and add rows to the table with index numbers
    foreach ($project->attachments as $index => $attachment) {
        $table->addRow();
        $table->addCell(800)->addText($index + 1);
        $table->addCell(4200)->addText($attachment->file_name);
        $descCell = $table->addCell(5000);
        $this->addTextWithLineBreaks($descCell, $attachment->description ?? 'No description provided.');
    }

    // Add footer note
    $section->addTextBreak(1);
    $section->addText("(Click on the attachment name in the system to download it)", ['italic' => true, 'size' => 10]);
}

//  CIC - PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER Specific Functions
// Section - Basic Inforrmation - CIC - PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER
private function addCICSections(PhpWord $phpWord, $project)
{
    // Retrieve CIC-specific data
    $cicBasicInfo = $project->cicBasicInfo; // Assuming this relationship or attribute exists

    // Create a new section for CIC
    $section = $phpWord->addSection();

    // Add header
    $section->addText("PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER", ['bold' => true, 'size' => 16]);
    $section->addText("Basic Information", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Check if CIC basic info exists
    if ($cicBasicInfo) {
        // Add table for structured layout
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('CICBasicInfoTable', $tableStyle);
        $table = $section->addTable('CICBasicInfoTable');

        // Add rows for CIC details
        $table->addRow();
        $table->addCell(4000)->addText("Center Name:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->center_name ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Address:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->address ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Contact Person:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->contact_person ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Phone Number:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->phone_number ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Email Address:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->email_address ?? 'N/A');
    } else {
        // No CIC-specific data available
        $section->addText("No CIC basic information available.", ['italic' => true]);
    }

    // Add spacing after the section
    $section->addTextBreak(1);
}

private function addSignatureAndApprovalSections(PhpWord $phpWord, $project, $projectRoles)
{
    // Add a new section for signatures and approval
    $section = $phpWord->addSection();

    // Header for Signatures
    $section->addText("Signatures", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('SignaturesTable', $tableStyle);

    // Create the Signatures table
    $table = $section->addTable('SignaturesTable');

    // Table Header
    $table->addRow();
    $table->addCell(5000)->addText("Person", ['bold' => true]);
    $table->addCell(3000)->addText("Signature", ['bold' => true]);
    $table->addCell(2000)->addText("Date", ['bold' => true]);

    // Add rows for different roles
    $table->addRow();
    $table->addCell(5000)->addText("Project Executor\n" . ($projectRoles['executor'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Project Incharge\n" . ($projectRoles['incharge'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("President of the Society / Chair Person of the Trust\n" . ($projectRoles['president'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Project Sanctioned / Authorized by\n" . ($projectRoles['authorizedBy'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $section->addTextBreak(2);

    // Approval Section Header
    $section->addText("Approval - To be filled by the Project Coordinator:", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Create the Approval table
    $table = $section->addTable('SignaturesTable');

    $table->addRow();
    $table->addCell(5000)->addText("Amount Approved");
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Remarks if any");
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Project Coordinator\n" . ($projectRoles['coordinator'] ?? 'N/A'));
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Signature");
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Date");
    $table->addCell(5000)->addText('');
}

// Individual Project Sections
private function addIndividualProjectSections(PhpWord $phpWord, $project)
{
    switch ($project->project_type) {
        case 'Individual - Ongoing Educational support':
            $this->addIESections($phpWord, $project);
            break;
        case 'Individual - Initial - Educational support':
            $this->addIIESSections($phpWord, $project);
            break;
        case 'Individual - Livelihood Application':
            $this->addILPSections($phpWord, $project);
            break;
        case 'Individual - Access to Health':
            $this->addIAHSections($phpWord, $project);
            break;
    }
}

// IES - Individual - Ongoing Educational Support
private function addIESections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Personal Information
    $section->addText("Personal Information", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add personal info data here
    $section->addText("Student Name: " . ($project->iesPersonalInfo->student_name ?? 'N/A'));
    $section->addText("Age: " . ($project->iesPersonalInfo->age ?? 'N/A'));
    $section->addText("Gender: " . ($project->iesPersonalInfo->gender ?? 'N/A'));
    $section->addText("Address: " . ($project->iesPersonalInfo->address ?? 'N/A'));
    $section->addTextBreak(1);

    // Family Working Members
    $section->addText("Family Working Members", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iesFamilyWorkingMembers) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('FamilyTable', $tableStyle);
        $table = $section->addTable('FamilyTable');

        $table->addRow();
        $table->addCell(3000)->addText("Name", ['bold' => true]);
        $table->addCell(3000)->addText("Relationship", ['bold' => true]);
        $table->addCell(3000)->addText("Occupation", ['bold' => true]);
        $table->addCell(3000)->addText("Monthly Income", ['bold' => true]);

        foreach ($project->iesFamilyWorkingMembers as $member) {
            $table->addRow();
            $table->addCell(3000)->addText($member->name ?? 'N/A');
            $table->addCell(3000)->addText($member->relationship ?? 'N/A');
            $table->addCell(3000)->addText($member->occupation ?? 'N/A');
            $table->addCell(3000)->addText($member->monthly_income ?? 'N/A');
        }
    }
    $section->addTextBreak(1);

    // Educational Background
    $section->addText("Educational Background", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iesEducationBackground) {
        $section->addText("Current Class: " . ($project->iesEducationBackground->current_class ?? 'N/A'));
        $section->addText("School/College: " . ($project->iesEducationBackground->school_college ?? 'N/A'));
        $section->addText("Previous Academic Performance: " . ($project->iesEducationBackground->previous_performance ?? 'N/A'));
    }
    $section->addTextBreak(1);

    // Estimated Expenses
    $section->addText("Estimated Expenses", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iesExpenses) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('ExpensesTable', $tableStyle);
        $table = $section->addTable('ExpensesTable');

        $table->addRow();
        $table->addCell(5000)->addText("Expense Type", ['bold' => true]);
        $table->addCell(5000)->addText("Amount", ['bold' => true]);

        foreach ($project->iesExpenses as $expense) {
            $table->addRow();
            $table->addCell(5000)->addText($expense->expense_type ?? 'N/A');
            $table->addCell(5000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($expense->amount ?? 0, 2));
        }
    }
}

// IIES - Individual - Initial Educational Support
private function addIIESSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Personal Information
    $section->addText("Personal Information", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iiesPersonalInfo) {
        $section->addText("Student Name: " . ($project->iiesPersonalInfo->student_name ?? 'N/A'));
        $section->addText("Age: " . ($project->iiesPersonalInfo->age ?? 'N/A'));
        $section->addText("Gender: " . ($project->iiesPersonalInfo->gender ?? 'N/A'));
        $section->addText("Address: " . ($project->iiesPersonalInfo->address ?? 'N/A'));
    }
    $section->addTextBreak(1);

    // Scope of Financial Support
    $section->addText("Scope of Financial Support", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iiesFinancialSupport) {
        $section->addText("Support Type: " . ($project->iiesFinancialSupport->support_type ?? 'N/A'));
        $section->addText("Duration: " . ($project->iiesFinancialSupport->duration ?? 'N/A'));
        $section->addText("Amount: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($project->iiesFinancialSupport->amount ?? 0, 2));
    }
    $section->addTextBreak(1);

    // Estimated Expenses
    $section->addText("Estimated Expenses", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iiesExpenses) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('IIESExpensesTable', $tableStyle);
        $table = $section->addTable('IIESExpensesTable');

        $table->addRow();
        $table->addCell(5000)->addText("Expense Type", ['bold' => true]);
        $table->addCell(5000)->addText("Amount", ['bold' => true]);

        foreach ($project->iiesExpenses as $expense) {
            $table->addRow();
            $table->addCell(5000)->addText($expense->expense_type ?? 'N/A');
            $table->addCell(5000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($expense->amount ?? 0, 2));
        }
    }
}

// ILP - Individual - Livelihood Application
private function addILPSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Personal Information
    $section->addText("Personal Information", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->ilpPersonalInfo) {
        $section->addText("Applicant Name: " . ($project->ilpPersonalInfo->name ?? 'N/A'));
        $section->addText("Age: " . ($project->ilpPersonalInfo->age ?? 'N/A'));
        $section->addText("Gender: " . ($project->ilpPersonalInfo->gender ?? 'N/A'));
        $section->addText("Address: " . ($project->ilpPersonalInfo->address ?? 'N/A'));
    }
    $section->addTextBreak(1);

    // Revenue Goals - Business Plan Items
    $section->addText("Revenue Goals - Business Plan Items", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->revenuePlanItems && $project->revenuePlanItems->count() > 0) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('ILPBusinessPlanTable', $tableStyle);
        $table = $section->addTable('ILPBusinessPlanTable');

        $table->addRow();
        $table->addCell(4000)->addText("Business Plan Item", ['bold' => true]);
        $table->addCell(2000)->addText("Year 1", ['bold' => true]);
        $table->addCell(2000)->addText("Year 2", ['bold' => true]);
        $table->addCell(2000)->addText("Year 3", ['bold' => true]);
        $table->addCell(2000)->addText("Year 4", ['bold' => true]);

        foreach ($project->revenuePlanItems as $item) {
            $table->addRow();
            $table->addCell(4000)->addText($item->item ?? 'N/A');
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($item->year_1 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($item->year_2 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($item->year_3 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($item->year_4 ?? 0, 2));
        }
    } else {
        $section->addText("No Business Plan Items available.");
    }
    $section->addTextBreak(1);

    // Revenue Goals - Annual Income
    $section->addText("Revenue Goals - Annual Income", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->revenueIncomes && $project->revenueIncomes->count() > 0) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('ILPIncomeTable', $tableStyle);
        $table = $section->addTable('ILPIncomeTable');

        $table->addRow();
        $table->addCell(4000)->addText("Income Description", ['bold' => true]);
        $table->addCell(2000)->addText("Year 1", ['bold' => true]);
        $table->addCell(2000)->addText("Year 2", ['bold' => true]);
        $table->addCell(2000)->addText("Year 3", ['bold' => true]);
        $table->addCell(2000)->addText("Year 4", ['bold' => true]);

        foreach ($project->revenueIncomes as $income) {
            $table->addRow();
            $table->addCell(4000)->addText($income->description ?? 'N/A');
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($income->year_1 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($income->year_2 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($income->year_3 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($income->year_4 ?? 0, 2));
        }
    } else {
        $section->addText("No Annual Income data available.");
    }
    $section->addTextBreak(1);

    // Revenue Goals - Annual Expenses
    $section->addText("Revenue Goals - Annual Expenses", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->revenueExpenses && $project->revenueExpenses->count() > 0) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('ILPExpensesTable', $tableStyle);
        $table = $section->addTable('ILPExpensesTable');

        $table->addRow();
        $table->addCell(4000)->addText("Expense Description", ['bold' => true]);
        $table->addCell(2000)->addText("Year 1", ['bold' => true]);
        $table->addCell(2000)->addText("Year 2", ['bold' => true]);
        $table->addCell(2000)->addText("Year 3", ['bold' => true]);
        $table->addCell(2000)->addText("Year 4", ['bold' => true]);

        foreach ($project->revenueExpenses as $expense) {
            $table->addRow();
            $table->addCell(4000)->addText($expense->description ?? 'N/A');
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($expense->year_1 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($expense->year_2 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($expense->year_3 ?? 0, 2));
            $table->addCell(2000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($expense->year_4 ?? 0, 2));
        }
    } else {
        $section->addText("No Annual Expenses data available.");
    }
    $section->addTextBreak(1);

    // Risk Analysis
    $section->addText("Risk Analysis", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->ilpRiskAnalysis) {
        $section->addText("Identified Risks:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->ilpRiskAnalysis->identified_risks ?? 'N/A');
        $section->addTextBreak(0.5);
        $section->addText("Mitigation Measures:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->ilpRiskAnalysis->mitigation_measures ?? 'N/A');
        $section->addTextBreak(0.5);
        $section->addText("Business Sustainability:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->ilpRiskAnalysis->business_sustainability ?? 'N/A');
        $section->addTextBreak(0.5);
        $section->addText("Expected Profits:", ['bold' => true]);
        $this->addTextWithLineBreaks($section, $project->ilpRiskAnalysis->expected_profits ?? 'N/A');
    }
    $section->addTextBreak(1);

    // Budget
    $section->addText("Budget", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->ilpBudget) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('ILPBudgetTable', $tableStyle);
        $table = $section->addTable('ILPBudgetTable');

        $table->addRow();
        $table->addCell(5000)->addText("Item", ['bold' => true]);
        $table->addCell(5000)->addText("Amount", ['bold' => true]);

        foreach ($project->ilpBudget as $budget) {
            $table->addRow();
            $table->addCell(5000)->addText($budget->budget_desc ?? 'N/A');
            $table->addCell(5000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($budget->cost ?? 0, 2));
        }
    }
}

// IAH - Individual - Access to Health
private function addIAHSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Personal Information
    $section->addText("Personal Information", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iahPersonalInfo) {
        $section->addText("Patient Name: " . ($project->iahPersonalInfo->patient_name ?? 'N/A'));
        $section->addText("Age: " . ($project->iahPersonalInfo->age ?? 'N/A'));
        $section->addText("Gender: " . ($project->iahPersonalInfo->gender ?? 'N/A'));
        $section->addText("Address: " . ($project->iahPersonalInfo->address ?? 'N/A'));
    }
    $section->addTextBreak(1);

    // Health Conditions
    $section->addText("Health Conditions", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iahHealthCondition) {
        $section->addText("Medical Condition: " . ($project->iahHealthCondition->medical_condition ?? 'N/A'));
        $section->addText("Diagnosis: " . ($project->iahHealthCondition->diagnosis ?? 'N/A'));
        $section->addText("Treatment Required: " . ($project->iahHealthCondition->treatment_required ?? 'N/A'));
    }
    $section->addTextBreak(1);

    // Budget Details
    $section->addText("Budget Details", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    if ($project->iahBudgetDetails) {
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord->addTableStyle('IAHBudgetTable', $tableStyle);
        $table = $section->addTable('IAHBudgetTable');

        $table->addRow();
        $table->addCell(5000)->addText("Expense Type", ['bold' => true]);
        $table->addCell(5000)->addText("Amount", ['bold' => true]);

        foreach ($project->iahBudgetDetails as $budget) {
            $table->addRow();
            $table->addCell(5000)->addText($budget->expense_type ?? 'N/A');
            $table->addCell(5000)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($budget->amount ?? 0, 2));
        }
    }
}

// IGE - Institutional Ongoing Group Educational
private function addIGESections(PhpWord $phpWord, $project)
{
    // Institution Information
    $this->addIGEInstitutionInfoSection($phpWord, $project);

    // Beneficiaries Supported
    $this->addIGEBeneficiariesSupportedSection($phpWord, $project);

    // Ongoing Beneficiaries
    $this->addIGEOngoingBeneficiariesSection($phpWord, $project);

    // New Beneficiaries
    $this->addIGENewBeneficiariesSection($phpWord, $project);

    // Budget
    $this->addIGEBudgetSection($phpWord, $project);

    // Development Monitoring
    $this->addIGEDevelopmentMonitoringSection($phpWord, $project);
}

}
