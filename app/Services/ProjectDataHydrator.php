<?php

namespace App\Services;

use App\Domain\Budget\ProjectFinancialResolver;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Auth;
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

/**
 * Unified project data loading for PDF/export views.
 * Produces identical structure to ProjectController@show for consumption by pdf.blade.php.
 * Do NOT move business rules; only data loading.
 */
class ProjectDataHydrator
{
    public function __construct(
        protected ProjectEduRUTBasicInfoController $eduRUTBasicInfoController,
        protected EduRUTTargetGroupController $eduRUTTargetGroupController,
        protected EduRUTAnnexedTargetGroupController $eduRUTAnnexedTargetGroupController,
        protected CICBasicInfoController $cicBasicInfoController,
        protected CCIAchievementsController $cciAchievementsController,
        protected CCIAgeProfileController $cciAgeProfileController,
        protected CCIAnnexedTargetGroupController $cciAnnexedTargetGroupController,
        protected CCIEconomicBackgroundController $cciEconomicBackgroundController,
        protected CCIPersonalSituationController $cciPersonalSituationController,
        protected CCIPresentSituationController $cciPresentSituationController,
        protected CCIRationaleController $cciRationaleController,
        protected CCIStatisticsController $cciStatisticsController,
        protected IGEInstitutionInfoController $igeInstitutionInfoController,
        protected IGEBeneficiariesSupportedController $igeBeneficiariesSupportedController,
        protected IGENewBeneficiariesController $igeNewBeneficiariesController,
        protected IGEOngoingBeneficiariesController $igeOngoingBeneficiariesController,
        protected IGEBudgetController $igeBudgetController,
        protected IGEDevelopmentMonitoringController $igeDevelopmentMonitoringController,
        protected LDPInterventionLogicController $ldpInterventionLogicController,
        protected LDPNeedAnalysisController $ldpNeedAnalysisController,
        protected LDPTargetGroupController $ldpTargetGroupController,
        protected RSTBeneficiariesAreaController $rstBeneficiariesAreaController,
        protected RSTGeographicalAreaController $rstGeographicalAreaController,
        protected RSTInstitutionInfoController $rstInstitutionInfoController,
        protected RSTTargetGroupAnnexureController $rstTargetGroupAnnexureController,
        protected RSTTargetGroupController $rstTargetGroupController,
        protected IESPersonalInfoController $iesPersonalInfoController,
        protected IESFamilyWorkingMembersController $iesFamilyWorkingMembersController,
        protected IESImmediateFamilyDetailsController $iesImmediateFamilyDetailsController,
        protected IESEducationBackgroundController $iesEducationBackgroundController,
        protected IESExpensesController $iesExpensesController,
        protected IESAttachmentsController $iesAttachmentsController,
        protected ILPPersonalInfoController $ilpPersonalInfoController,
        protected ILPRevenueGoalsController $ilpRevenueGoalsController,
        protected ILPStrengthWeaknessController $ilpStrengthWeaknessController,
        protected ILPRiskAnalysisController $ilpRiskAnalysisController,
        protected ILPAttachedDocumentsController $ilpAttachedDocumentsController,
        protected ILPBudgetController $ilpBudgetController,
        protected IAHPersonalInfoController $iahPersonalInfoController,
        protected IAHEarningMembersController $iahEarningMembersController,
        protected IAHHealthConditionController $iahHealthConditionController,
        protected IAHSupportDetailsController $iahSupportDetailsController,
        protected IAHBudgetDetailsController $iahBudgetDetailsController,
        protected IAHDocumentsController $iahDocumentsController,
        protected IIESPersonalInfoController $iiesPersonalInfoController,
        protected IIESFamilyWorkingMembersController $iiesFamilyWorkingMembersController,
        protected IIESImmediateFamilyDetailsController $iiesImmediateFamilyDetailsController,
        protected IIESEducationBackgroundController $iiesEducationBackgroundController,
        protected IIESFinancialSupportController $iiesFinancialSupportController,
        protected IIESAttachmentsController $iiesAttachmentsController,
        protected IIESExpensesController $iiesExpensesController,
        protected ProjectFinancialResolver $financialResolver,
    ) {
    }

    /**
     * Load all project module data for the given project_id.
     * Returns structured array identical to ProjectController@show (for PDF consumption).
     *
     * @param string $project_id
     * @return array<string, mixed>
     */
    public function hydrate(string $project_id): array
    {
        $project = Project::where('project_id', $project_id)
            ->with(['budgets', 'attachments', 'objectives.risks', 'objectives.activities.timeframes', 'sustainabilities', 'user'])
            ->firstOrFail();

        $user = Auth::user();

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
                $data['IGEbudget'] = $this->igeBudgetController->show($project->project_id);
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
                $data['IESpersonalInfo'] = $this->iesPersonalInfoController->show($project->project_id);
                $data['IESfamilyWorkingMembers'] = $this->iesFamilyWorkingMembersController->show($project->project_id);
                $data['IESimmediateFamilyDetails'] = $this->iesImmediateFamilyDetailsController->show($project->project_id);
                $data['IESEducationBackground'] = $this->iesEducationBackgroundController->show($project->project_id);
                $data['IESExpenses'] = $this->iesExpensesController->show($project->project_id);
                $data['IESAttachments'] = $this->iesAttachmentsController->show($project->project_id) ?? [];
                break;

            case 'Individual - Livelihood Application':
                $data['ILPPersonalInfo'] = $this->ilpPersonalInfoController->show($project_id) ?? [];
                $data['ILPRevenueGoals'] = $this->ilpRevenueGoalsController->show($project_id) ?? [];
                $data['ILPStrengthWeakness'] = $this->ilpStrengthWeaknessController->show($project_id) ?? [];
                $data['ILPRiskAnalysis'] = $this->ilpRiskAnalysisController->show($project_id) ?? [];
                $data['ILPAttachedDocuments'] = $this->ilpAttachedDocumentsController->show($project_id) ?? [];
                $data['ILPBudgets'] = $this->ilpBudgetController->show($project_id) ?? collect([]);
                break;

            case 'Individual - Access to Health':
                $data['IAHPersonalInfo'] = $this->iahPersonalInfoController->show($project->project_id);
                $data['IAHEarningMembers'] = $this->iahEarningMembersController->show($project->project_id);
                $data['IAHHealthCondition'] = $this->iahHealthConditionController->show($project->project_id);
                $data['IAHSupportDetails'] = $this->iahSupportDetailsController->show($project->project_id);
                $data['IAHBudgetDetails'] = $this->iahBudgetDetailsController->show($project->project_id);
                $data['IAHDocuments'] = $this->iahDocumentsController->show($project->project_id) ?? [];
                break;

            case 'Individual - Initial - Educational support':
                $data['IIESPersonalInfo'] = $this->iiesPersonalInfoController->show($project->project_id);
                $data['IIESFamilyWorkingMembers'] = $this->iiesFamilyWorkingMembersController->show($project->project_id);
                $data['IIESImmediateFamilyDetails'] = $this->iiesImmediateFamilyDetailsController->show($project->project_id);
                $data['IIESEducationBackground'] = $this->iiesEducationBackgroundController->show($project->project_id);
                $data['IIESFinancialSupport'] = $this->iiesFinancialSupportController->show($project->project_id);
                $data['IIESAttachments'] = $this->iiesAttachmentsController->show($project_id) ?? [];
                $data['IIESExpenses'] = $this->iiesExpensesController->show($project_id) ?? [];
                break;
        }

        $data['resolvedFundFields'] = $this->financialResolver->resolve($project);

        return $data;
    }
}
