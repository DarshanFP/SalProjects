<?php

namespace App\Models\OldProjects;

use App\Models\OldProjects\CCI\ProjectCCIAchievements;
use App\Models\OldProjects\CCI\ProjectCCIAgeProfile;
use App\Models\OldProjects\CCI\ProjectCCIAnnexedTargetGroup;
use App\Models\OldProjects\CCI\ProjectCCIEconomicBackground;
use App\Models\OldProjects\CCI\ProjectCCIPersonalSituation;
use App\Models\OldProjects\CCI\ProjectCCIPresentSituation;
use App\Models\OldProjects\CCI\ProjectCCIRationale;
use App\Models\OldProjects\CCI\ProjectCCIStatistics;
use App\Models\OldProjects\IAH\ProjectIAHBudgetDetails;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
use App\Models\OldProjects\IAH\ProjectIAHEarningMembers;
use App\Models\OldProjects\IAH\ProjectIAHHealthCondition;
use App\Models\OldProjects\IAH\ProjectIAHPersonalInfo;
use App\Models\OldProjects\IAH\ProjectIAHSupportDetails;
use App\Models\OldProjects\IES\ProjectIESAttachments;
use App\Models\OldProjects\IES\ProjectIESEducationBackground;
use App\Models\OldProjects\IES\ProjectIESExpenses;
use App\Models\OldProjects\IES\ProjectIESFamilyWorkingMembers;
use App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails;
use App\Models\OldProjects\IES\ProjectIESPersonalInfo;
use App\Models\OldProjects\LDP\ProjectLDPInterventionLogic;
use App\Models\OldProjects\LDP\ProjectLDPNeedAnalysis;
use App\Models\OldProjects\LDP\ProjectLDPTargetGroup;
use App\Models\OldProjects\RST\ProjectRSTInstitutionInfo;
use App\Models\OldProjects\RST\ProjectRSTTargetGroup;
use App\Models\OldProjects\RST\ProjectRSTTargetGroupAnnexure;
use App\Models\OldProjects\RST\ProjectRSTGeographicalArea;
use App\Models\OldProjects\RST\ProjectRSTPersonalCost;
use App\Models\OldProjects\RST\ProjectRSTProgrammeExpenses;
use App\Models\OldProjects\RST\ProjectRSTFinancialSummary;
use App\Models\OldProjects\ProjectEduRUTBasicInfo;
use App\Models\OldProjects\ProjectEduRUTTargetGroup;
use App\Models\OldProjects\ProjectEduRUTAnnexedTargetGroup;
use App\Models\OldProjects\IGE\ProjectIGEInstitutionInfo;
use App\Models\OldProjects\IGE\ProjectIGEBeneficiariesSupported;
use App\Models\OldProjects\IGE\ProjectIGEOngoingBeneficiaries;
use App\Models\OldProjects\IGE\ProjectIGENewBeneficiaries;
use App\Models\OldProjects\IGE\ProjectIGEBudget;
use App\Models\OldProjects\IGE\ProjectIGEDevelopmentMonitoring;
use App\Models\OldProjects\IIES\ProjectIIESAttachments;
use App\Models\OldProjects\IIES\ProjectIIESEducationBackground;
use App\Models\OldProjects\IIES\ProjectIIESExpenses;
use App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers;
use App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails;
use App\Models\OldProjects\IIES\ProjectIIESPersonalInfo;
use App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use App\Models\OldProjects\ILP\ProjectILPBusinessStrengthWeakness;
use App\Models\OldProjects\ILP\ProjectILPPersonalInfo;
use App\Models\OldProjects\ILP\ProjectILPRevenueExpense;
use App\Models\OldProjects\ILP\ProjectILPRevenueIncome;
use App\Models\OldProjects\ILP\ProjectILPRevenuePlanItem;
use App\Models\OldProjects\ILP\ProjectILPRiskAnalysis;
use App\Models\OldProjects\RST\ProjectDPRSTBeneficiariesArea;
use App\Models\ProjectComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property int $user_id
 * @property string $project_type
 * @property string|null $project_title
 * @property string|null $society_name
 * @property string|null $president_name
 * @property int $in_charge
 * @property string|null $in_charge_name
 * @property string|null $in_charge_mobile
 * @property string|null $in_charge_email
 * @property string|null $executor_name
 * @property string|null $executor_mobile
 * @property string|null $executor_email
 * @property string|null $full_address
 * @property int|null $overall_project_period
 * @property int|null $current_phase
 * @property string|null $commencement_month_year
 * @property string $overall_project_budget
 * @property string|null $amount_forwarded
 * @property string|null $amount_sanctioned
 * @property string|null $opening_balance
 * @property string|null $coordinator_india_name
 * @property string|null $coordinator_india_phone
 * @property string|null $coordinator_india_email
 * @property string|null $coordinator_luzern_name
 * @property string|null $coordinator_luzern_phone
 * @property string|null $coordinator_luzern_email
 * @property string $status
 * @property string $goal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectDPRSTBeneficiariesArea> $DPRSTBeneficiariesAreas
 * @property-read int|null $d_p_r_s_t_beneficiaries_areas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectEduRUTAnnexedTargetGroup> $annexed_target_groups
 * @property-read int|null $annexed_target_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectBudget> $budgets
 * @property-read int|null $budgets_count
 * @property-read ProjectCCIAchievements|null $cciAchievements
 * @property-read ProjectCCIAgeProfile|null $cciAgeProfile
 * @property-read ProjectCCIAnnexedTargetGroup|null $cciAnnexedTargetGroup
 * @property-read ProjectCCIEconomicBackground|null $cciEconomicBackground
 * @property-read ProjectCCIPersonalSituation|null $cciPersonalSituation
 * @property-read ProjectCCIPresentSituation|null $cciPresentSituation
 * @property-read ProjectCCIRationale|null $cciRationale
 * @property-read ProjectCCIStatistics|null $cciStatistics
 * @property-read \App\Models\OldProjects\ProjectCICBasicInfo|null $cicBasicInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectComment> $comments
 * @property-read int|null $comments_count
 * @property-read ProjectEduRUTBasicInfo|null $eduRUTBasicInfo
 * @property-read mixed $commencement_month
 * @property-read mixed $commencement_year
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIAHBudgetDetails> $iahBudgetDetails
 * @property-read int|null $iah_budget_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIAHDocuments> $iahDocuments
 * @property-read int|null $iah_documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIAHEarningMembers> $iahEarningMembers
 * @property-read int|null $iah_earning_members_count
 * @property-read ProjectIAHHealthCondition|null $iahHealthCondition
 * @property-read ProjectIAHPersonalInfo|null $iahPersonalInfo
 * @property-read ProjectIAHSupportDetails|null $iahSupportDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIESAttachments> $iesAttachements
 * @property-read int|null $ies_attachements_count
 * @property-read ProjectIESEducationBackground|null $iesEducationBackground
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIESExpenses> $iesExpenses
 * @property-read int|null $ies_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIESFamilyWorkingMembers> $iesFamilyWorkingMembers
 * @property-read int|null $ies_family_working_members_count
 * @property-read ProjectIESImmediateFamilyDetails|null $iesImmediateFamilyDetails
 * @property-read ProjectIESPersonalInfo|null $iesPersonalInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIGEBeneficiariesSupported> $igeBeneficiariesSupported
 * @property-read int|null $ige_beneficiaries_supported_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIGEBudget> $igeBudget
 * @property-read int|null $ige_budget_count
 * @property-read ProjectIGEDevelopmentMonitoring|null $igeDevelopmentMonitoring
 * @property-read ProjectIGEInstitutionInfo|null $igeInstitutionInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIGENewBeneficiaries> $igeNewBeneficiaries
 * @property-read int|null $ige_new_beneficiaries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIGEOngoingBeneficiaries> $igeOngoingBeneficiaries
 * @property-read int|null $ige_ongoing_beneficiaries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIIESAttachments> $iiesAttachments
 * @property-read int|null $iies_attachments_count
 * @property-read ProjectIIESEducationBackground|null $iiesEducationBackground
 * @property-read ProjectIIESExpenses|null $iiesExpenses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectIIESFamilyWorkingMembers> $iiesFamilyWorkingMembers
 * @property-read int|null $iies_family_working_members_count
 * @property-read ProjectIIESScopeFinancialSupport|null $iiesFinancialSupport
 * @property-read ProjectIIESImmediateFamilyDetails|null $iiesImmediateFamilyDetails
 * @property-read ProjectIIESPersonalInfo|null $iiesPersonalInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectILPAttachedDocuments> $ilpAttachedDocuments
 * @property-read int|null $ilp_attached_documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectILPBudget> $ilpBudget
 * @property-read int|null $ilp_budget_count
 * @property-read ProjectILPBusinessStrengthWeakness|null $ilpBusinessStrengthWeakness
 * @property-read ProjectILPPersonalInfo|null $ilpPersonalInfo
 * @property-read ProjectILPRiskAnalysis|null $ilpRiskAnalysis
 * @property-read ProjectLDPInterventionLogic|null $ldpInterventionLogic
 * @property-read ProjectLDPNeedAnalysis|null $ldpNeedAnalysis
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectLDPTargetGroup> $ldpTargetGroup
 * @property-read int|null $ldp_target_group_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectObjective> $logical_frameworks
 * @property-read int|null $logical_frameworks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read Project|null $predecessor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPReport> $reports
 * @property-read int|null $reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectILPRevenueExpense> $revenueExpenses
 * @property-read int|null $revenue_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectILPRevenueIncome> $revenueIncomes
 * @property-read int|null $revenue_incomes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectILPRevenuePlanItem> $revenuePlanItems
 * @property-read int|null $revenue_plan_items_count
 * @property-read ProjectRSTFinancialSummary|null $rstFinancialSummaries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectRSTGeographicalArea> $rstGeographicalAreas
 * @property-read int|null $rst_geographical_areas_count
 * @property-read ProjectRSTInstitutionInfo|null $rstInstitutionInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectRSTPersonalCost> $rstPersonalCosts
 * @property-read int|null $rst_personal_costs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectRSTProgrammeExpenses> $rstProgrammeExpenses
 * @property-read int|null $rst_programme_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectRSTTargetGroup> $rstTargetGroup
 * @property-read int|null $rst_target_group_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectRSTTargetGroupAnnexure> $rstTargetGroupAnnexure
 * @property-read int|null $rst_target_group_annexure_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Project> $successors
 * @property-read int|null $successors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectSustainability> $sustainabilities
 * @property-read int|null $sustainabilities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectEduRUTTargetGroup> $target_groups
 * @property-read int|null $target_groups_count
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCoordinatorIndiaEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCoordinatorIndiaName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCoordinatorIndiaPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCoordinatorLuzernEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCoordinatorLuzernName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCoordinatorLuzernPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCurrentPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereExecutorEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereExecutorMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereExecutorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereInChargeEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereInChargeMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereInChargeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereOpeningBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereOverallProjectBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereOverallProjectPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project wherePresidentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereProjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUserId($value)
 * @mixin \Eloquent
 */
class Project extends Model
{
    use HasFactory;
    // protected $primaryKey = 'project_id';

    protected $fillable = [
        'user_id',
        'project_id',
        'project_type',
        'project_title',
        'society_name',
        'president_name',
        'in_charge',
        'in_charge_name',
        'in_charge_mobile',
        'in_charge_email',
        'executor_name',
        'executor_mobile',
        'executor_email',
        'full_address',
        'overall_project_period',
        'current_phase',
        'commencement_month_year',
        'overall_project_budget',
        'amount_forwarded',
        'amount_sanctioned',
        'opening_balance',
        'coordinator_india_name',
        'coordinator_india_phone',
        'coordinator_india_email',
        'coordinator_luzern_name',
        'coordinator_luzern_phone',
        'coordinator_luzern_email',
        'goal',
        'status',
        'predecessor_project_id'
    ];
// In your Project model or a helper:
public static $statusLabels = [
    'draft' => 'Draft (Executor still working)',
    'submitted_to_provincial' => 'Executor submitted to Provincial',
    'reverted_by_provincial' => 'Returned by Provincial for changes',
    'forwarded_to_coordinator' => 'Provincial sent to Coordinator',
    'reverted_by_coordinator' => 'Coordinator sent back for changes',
    'approved_by_coordinator' => 'Approved by Coordinator',
    'rejected_by_coordinator' => 'Rejected by Coordinator',
];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->project_id = $model->generateProjectId();
        });
    }

    private function generateProjectId()
    {
        $initialsMap = [
            'CHILD CARE INSTITUTION' => 'CCI',
            'Development Projects' => 'DP',
            'Rural-Urban-Tribal' => 'RUT',
            'Institutional Ongoing Group Educational proposal' => 'IOGEP',
            'Livelihood Development Projects' => 'LDP',
            'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' => 'CIC',
            'NEXT PHASE - DEVELOPMENT PROPOSAL' => 'NPD',
            'Residential Skill Training Proposal 2' => 'RSTP2',
            'Individual - Ongoing Educational support' => 'IOES',
            'Individual - Livelihood Application' => 'ILA',
            'Individual - Access to Health' => 'IAH',
            'Individual - Initial - Educational support' => 'IIES',
        ];

        $initials = $initialsMap[$this->project_type] ?? 'GEN';

        $latestProject = self::where('project_id', 'like', $initials . '-%')->latest('id')->first();
        $sequenceNumber = $latestProject ? intval(substr($latestProject->project_id, strlen($initials) + 1)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

        return $initials . '-' . $sequenceNumberPadded;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function budgets()
    {
        return $this->hasMany(ProjectBudget::class, 'project_id', 'project_id');
    }

    public function attachments()
    {
        return $this->hasMany(ProjectAttachment::class, 'project_id', 'project_id');
    }
    public function logical_frameworks()
    {
        return $this->hasMany(ProjectObjective::class, 'project_id', 'project_id');
    }
    public function sustainabilities()
    {
        return $this->hasMany(ProjectSustainability::class, 'project_id', 'project_id');
    }
    public function objectives()
    {
        return $this->hasMany(ProjectObjective::class, 'project_id', 'project_id');
    }

    // Accessor for Commencement Month
    public function getCommencementMonthAttribute()
    {
        return $this->commencement_month_year ? date('m', strtotime($this->commencement_month_year)) : null;
    }

    // Accessor for Commencement Year
    public function getCommencementYearAttribute()
    {
        return $this->commencement_month_year ? date('Y', strtotime($this->commencement_month_year)) : null;
    }

    // Relationship for EduRUT
    public function eduRUTBasicInfo()
    {
        return $this->hasOne(ProjectEduRUTBasicInfo::class, 'project_id', 'project_id');
    }

    public function target_groups()
    {
        return $this->hasMany(ProjectEduRUTTargetGroup::class, 'project_id', 'project_id');
    }
    public function annexed_target_groups()
    {
        return $this->hasMany(ProjectEduRUTAnnexedTargetGroup::class, 'project_id', 'project_id');
    }
    //Relationship for CIC projects
    public function cicBasicInfo()
    {
        return $this->hasOne(ProjectCICBasicInfo::class, 'project_id', 'project_id');
    }
    // Relationship for CCI projects
    public function cciRationale()
    {
        return $this->hasOne(ProjectCCIRationale::class, 'project_id', 'project_id');
    }
    public function cciStatistics()
    {
        return $this->hasOne(ProjectCCIStatistics::class, 'project_id', 'project_id');
    }
    public function cciAnnexedTargetGroup()
    {
        return $this->hasOne(ProjectCCIAnnexedTargetGroup::class, 'project_id', 'project_id');
    }
    public function cciPersonalSituation()
    {
        return $this->hasOne(ProjectCCIPersonalSituation::class, 'project_id', 'project_id');
    }
    public function cciEconomicBackground()
    {
        return $this->hasOne(ProjectCCIEconomicBackground::class, 'project_id', 'project_id');
    }
    public function cciAchievements()
    {
        return $this->hasOne(ProjectCCIAchievements::class, 'project_id', 'project_id');
    }
    public function cciPresentSituation()
    {
        return $this->hasOne(ProjectCCIPresentSituation::class, 'project_id', 'project_id');
    }
    public function cciAgeProfile()
    {
        return $this->hasOne(ProjectCCIAgeProfile::class, 'project_id', 'project_id');
    }
    //Livelihood Development Projects (LDP)
    public function ldpNeedAnalysis()
    {
        return $this->hasOne(ProjectLDPNeedAnalysis::class, 'project_id', 'project_id');
    }
    public function ldpTargetGroup()
    {
        return $this->hasMany(ProjectLDPTargetGroup::class, 'project_id', 'project_id');
    }
    public function ldpInterventionLogic()
    {
        return $this->hasOne(ProjectLDPInterventionLogic::class, 'project_id', 'project_id');
    }
    //Residential Skill Training Proposal (RSTP)
    public function rstInstitutionInfo()
    {
        return $this->hasOne(ProjectRSTInstitutionInfo::class, 'project_id', 'project_id');
    }
    public function rstTargetGroup()
    {
        return $this->hasMany(ProjectRSTTargetGroup::class, 'project_id', 'project_id');
    }
    public function rstTargetGroupAnnexure()
    {
        return $this->hasMany(ProjectRSTTargetGroupAnnexure::class, 'project_id', 'project_id');
    }
    public function rstGeographicalAreas()
    {
        return $this->hasMany(ProjectRSTGeographicalArea::class, 'project_id', 'project_id');
    }
    public function rstPersonalCosts()
    {
        return $this->hasMany(ProjectRSTPersonalCost::class, 'project_id', 'project_id');
    }
    public function rstProgrammeExpenses()
    {
        return $this->hasMany(ProjectRSTProgrammeExpenses::class, 'project_id', 'project_id');
    }
    public function rstFinancialSummaries()
    {
        return $this->hasOne(ProjectRSTFinancialSummary::class, 'project_id', 'project_id');
    }
    // Relationship with ProjectDPRSTBeneficiariesArea model
    //for Development Projects and Residential Skill Training Proposal
    public function DPRSTBeneficiariesAreas()
    {
        return $this->hasMany(ProjectDPRSTBeneficiariesArea::class, 'project_id', 'project_id');
    }
    //Relationship for IOGEP projects
    public function igeInstitutionInfo()
    {
        return $this->hasOne(ProjectIGEInstitutionInfo::class, 'project_id', 'project_id');
    }
    public function igeBeneficiariesSupported()
    {
        return $this->hasMany(ProjectIGEBeneficiariesSupported::class, 'project_id', 'project_id');
    }
    public function igeOngoingBeneficiaries()
    {
        return $this->hasMany(ProjectIGEOngoingBeneficiaries::class, 'project_id', 'project_id');
    }
    public function igeNewBeneficiaries()
    {
        return $this->hasMany(ProjectIGENewBeneficiaries::class, 'project_id', 'project_id');
    }
    public function igeBudget()
    {
        return $this->hasMany(ProjectIGEBudget::class, 'project_id', 'project_id');
    }
    public function igeDevelopmentMonitoring()
    {
        return $this->hasOne(ProjectIGEDevelopmentMonitoring::class, 'project_id', 'project_id');
    }
    //Relationship for IES projects - Ongoing
    public function iesPersonalInfo()
    {
        return $this->hasOne(ProjectIESPersonalInfo::class, 'project_id', 'project_id');
    }
    public function iesImmediateFamilyDetails()
    {
        return $this->hasOne(ProjectIESImmediateFamilyDetails::class, 'project_id', 'project_id');
    }
    public function iesFamilyWorkingMembers()
    {
        return $this->hasMany(ProjectIESFamilyWorkingMembers::class, 'project_id', 'project_id');
    }
    public function iesExpenses() //iesExpenses
    {
        return $this->hasMany(ProjectIESExpenses::class, 'project_id', 'project_id');
    }
    public function iesEducationBackground()
    {
        return $this->hasOne(ProjectIESEducationBackground::class, 'project_id', 'project_id');
    }
    public function iesAttachements()
    {
        return $this->hasMany(ProjectIESAttachments::class, 'project_id', 'project_id');
    }
    //Relationship for Individual Livelihood projects
    public function ilpPersonalInfo()
    {
        return $this->hasOne(ProjectILPPersonalInfo::class, 'project_id', 'project_id');
    }
    // public function ilpRevenueGoals()
    // {
    //     return $this->hasMany(ProjectILPRevenueGoals::class, 'project_id', 'project_id');
    // }
        public function revenuePlanItems()
    {
        return $this->hasMany(ProjectILPRevenuePlanItem::class, 'project_id', 'project_id');
    }

    public function revenueIncomes()
    {
        return $this->hasMany(ProjectILPRevenueIncome::class, 'project_id', 'project_id');
    }

    public function revenueExpenses()
    {
        return $this->hasMany(ProjectILPRevenueExpense::class, 'project_id', 'project_id');
    }

    public function ilpRiskAnalysis()
    {
        return $this->hasOne(ProjectILPRiskAnalysis::class, 'project_id', 'project_id');
    }
    public function ilpBusinessStrengthWeakness()
    {
        return $this->hasOne(ProjectILPBusinessStrengthWeakness::class, 'project_id', 'project_id');
    }
    public function ilpBudget()
    {
        return $this->hasMany(ProjectILPBudget::class, 'project_id', 'project_id');
    }
    public function ilpAttachedDocuments()
    {
        return $this->hasMany(ProjectILPAttachedDocuments::class, 'project_id', 'project_id');
    }
    // Individual Access to health
    public function iahPersonalInfo()
    {
        return $this->hasOne(ProjectIAHPersonalInfo::class, 'project_id', 'project_id');
    }
    public function iahBudgetDetails()
    {
        return $this->hasMany(ProjectIAHBudgetDetails::class, 'project_id', 'project_id');
    }
    // public function iahDocuments()
    // {
    //     return $this->hasMany(ProjectIAHDocuments::class, 'project_id', 'project_id');
    // }
    public function iahDocuments()
{
    return $this->hasMany(ProjectIAHDocuments::class, 'project_id', 'project_id');
}

    public function iahEarningMembers()
    {
        return $this->hasMany(ProjectIAHEarningMembers::class, 'project_id', 'project_id');
    }
    public function iahHealthCondition()
    {
        return $this->hasOne(ProjectIAHHealthCondition::class, 'project_id', 'project_id');
    }
    public function iahSupportDetails()
    {
        return $this->hasOne(ProjectIAHSupportDetails::class, 'project_id', 'project_id');
    }
    //    //Relationship for IIES projects - Initial

    // public function educationBackground()
    // {
    //     return $this->hasOne(ProjectIIESEducationBackground::class, 'project_id', 'project_id');
    // }
    public function iiesEducationBackground()
{
    return $this->hasOne(ProjectIIESEducationBackground::class, 'project_id', 'project_id');
}

    public function iiesFinancialSupport()
    {
        return $this->hasOne(ProjectIIESScopeFinancialSupport::class, 'project_id', 'project_id');
    }
    public function iiesAttachments()
    {
        return $this->hasMany(ProjectIIESAttachments::class, 'project_id', 'project_id');
    }
    public function iiesImmediateFamilyDetails()
    {
        return $this->hasOne(ProjectIIESImmediateFamilyDetails::class, 'project_id', 'project_id');
    }
    public function iiesPersonalInfo()
    {
        return $this->hasOne(ProjectIIESPersonalInfo::class, 'project_id', 'project_id');
    }
    public function iiesFamilyWorkingMembers()
    {
        return $this->hasMany(ProjectIIESFamilyWorkingMembers::class, 'project_id', 'project_id');
    }
    public function iiesExpenses()
    {
        return $this->hasOne(ProjectIIESExpenses::class, 'project_id', 'project_id');
    }
    // comments relationship
    public function comments()
    {
        return $this->hasMany(ProjectComment::class, 'project_id', 'project_id');
    }

    public function generateProjectCommentId()
    {
        $latestComment = $this->comments()->orderBy('created_at', 'desc')->first();
        $nextNumber = $latestComment ? (int)substr($latestComment->project_comment_id, -3) + 1 : 1;
        return $this->project_id . '.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
// Next Phase - Debelopment Proposal

public function predecessor()
{
    return $this->belongsTo(Project::class, 'predecessor_project_id', 'project_id');
}

public function successors()
{
    return $this->hasMany(Project::class, 'predecessor_project_id', 'project_id');
}

public function reports()
{
    return $this->hasMany(\App\Models\Reports\Monthly\DPReport::class, 'project_id', 'project_id');
}

}
