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
use App\Models\OldProjects\IIES\ProjectIIESEducationBackground;
use App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use App\Models\OldProjects\ILP\ProjectILPBudget;
use App\Models\OldProjects\ILP\ProjectILPBusinessStrengthWeakness;
use App\Models\OldProjects\ILP\ProjectILPPersonalInfo;
use App\Models\OldProjects\ILP\ProjectILPRevenueGoals;
use App\Models\OldProjects\ILP\ProjectILPRiskAnalysis;
use App\Models\OldProjects\RST\ProjectDPRSTBeneficiariesArea;
use App\Models\ProjectComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status'
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
        return $this->hasOne(ProjectLDPTargetGroup::class, 'project_id', 'project_id');
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
        return $this->hasMany(ProjectIESImmediateFamilyDetails::class, 'project_id', 'project_id');
    }
    public function iesFamilyWorkingMembers()
    {
        return $this->hasMany(ProjectIESFamilyWorkingMembers::class, 'project_id', 'project_id');
    }
    public function iesExpenses()
    {
        return $this->hasMany(ProjectIESExpenses::class, 'project_id', 'project_id');
    }
    public function iesEductionBackground()
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
    public function ilpRevenueGoals()
    {
        return $this->hasMany(ProjectILPRevenueGoals::class, 'project_id', 'project_id');
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
    public function iahDocuments()
    {
        return $this->hasMany(ProjectIAHDocuments::class, 'project_id', 'project_id');
    }
    public function iahEarningMembers()
    {
        return $this->hasMany(ProjectIAHEarningMembers::class, 'project_id', 'project_id');
    }
    public function iahHealthConditon()
    {
        return $this->hasOne(ProjectIAHHealthCondition::class, 'project_id', 'project_id');
    }
    public function iahSupportDetails()
    {
        return $this->hasOne(ProjectIAHSupportDetails::class, 'project_id', 'project_id');
    }
    //    //Relationship for IIES projects - Initial

    public function educationBackground()
    {
        return $this->hasOne(ProjectIIESEducationBackground::class, 'project_id', 'project_id');
    }
    public function scopeFinancialSupport()
    {
        return $this->hasOne(ProjectIIESScopeFinancialSupport::class, 'project_id', 'project_id');
    }
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

}
