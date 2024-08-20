<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $project_title
 * @property string $place
 * @property string $society_name
 * @property string $commencement_month_year
 * @property string $in_charge
 * @property int $total_beneficiaries
 * @property string $reporting_period
 * @property string $goal
 * @property string|null $total_amount_sanctioned
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\OldDevelopmentProjectAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\OldDevelopmentProjectBudget> $budgets
 * @property-read int|null $budgets_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\OldProjects\OldDevelopmentProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject query()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereReportingPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereTotalAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProject whereUserId($value)
 */
	class OldDevelopmentProject extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property string $file_path
 * @property string $file_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\OldDevelopmentProject $project
 * @method static \Database\Factories\OldProjects\OldDevelopmentProjectAttachmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereUpdatedAt($value)
 */
	class OldDevelopmentProjectAttachment extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property int $phase
 * @property string $description
 * @property string $rate_quantity
 * @property string $rate_multiplier
 * @property string $rate_duration
 * @property string|null $rate_increase
 * @property string $this_phase
 * @property string $next_phase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\OldDevelopmentProject $project
 * @method static \Database\Factories\OldProjects\OldDevelopmentProjectBudgetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereNextPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget wherePhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereThisPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereUpdatedAt($value)
 */
	class OldDevelopmentProjectBudget extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string|null $project_id
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
 * @property string|null $overall_project_period
 * @property string|null $current_phase
 * @property string|null $commencement_month_year
 * @property string|null $overall_project_budget
 * @property string|null $amount_forwarded
 * @property string|null $amount_sanctioned
 * @property string|null $opening_balance
 * @property string|null $coordinator_india_name
 * @property string|null $coordinator_india_phone
 * @property string|null $coordinator_india_email
 * @property string|null $coordinator_luzern_name
 * @property string|null $coordinator_luzern_phone
 * @property string|null $coordinator_luzern_email
 * @property string|null $goal
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectBudget> $budgets
 * @property-read int|null $budgets_count
 * @property-read mixed $commencement_month
 * @property-read mixed $commencement_year
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectObjective> $logical_frameworks
 * @property-read int|null $logical_frameworks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectSustainability> $sustainabilities
 * @property-read int|null $sustainabilities_count
 * @property-read \App\Models\User $user
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
 */
	class Project extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $activity_id
 * @property string $objective_id
 * @property string|null $activity
 * @property string $verification
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectObjective $objective
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectTimeframe> $timeframes
 * @property-read int|null $timeframes_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereVerification($value)
 */
	class ProjectActivity extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property string $file_path
 * @property string|null $file_name
 * @property string|null $description
 * @property string|null $public_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment wherePublicUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereUpdatedAt($value)
 */
	class ProjectAttachment extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property int $phase
 * @property string $particular
 * @property string $rate_quantity
 * @property string $rate_multiplier
 * @property string $rate_duration
 * @property string $rate_increase
 * @property string $this_phase
 * @property string $next_phase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPAccountDetail> $dpAccountDetails
 * @property-read int|null $dp_account_details_count
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereNextPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget wherePhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereThisPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereUpdatedAt($value)
 */
	class ProjectBudget extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property string $objective_id
 * @property string|null $objective
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\OldProjects\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectResult> $results
 * @property-read int|null $results_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectRisk> $risks
 * @property-read int|null $risks_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereObjective($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereUpdatedAt($value)
 */
	class ProjectObjective extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $result_id
 * @property string $objective_id
 * @property string|null $result
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult whereResultId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectResult whereUpdatedAt($value)
 */
	class ProjectResult extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $risk_id
 * @property string $objective_id
 * @property string|null $risk
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereRisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereRiskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereUpdatedAt($value)
 */
	class ProjectRisk extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $sustainability_id
 * @property string $project_id
 * @property string|null $sustainability
 * @property string|null $monitoring_process
 * @property string|null $reporting_methodology
 * @property string|null $evaluation_methodology
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereEvaluationMethodology($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereMonitoringProcess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereReportingMethodology($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereSustainability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereSustainabilityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereUpdatedAt($value)
 */
	class ProjectSustainability extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $timeframe_id
 * @property string $activity_id
 * @property string $month
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectActivity $activity
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereTimeframeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereUpdatedAt($value)
 */
	class ProjectTimeframe extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \App\Models\Reports\Monthly\DPReport|null $report
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment query()
 */
	class ReportComment extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $account_detail_id
 * @property string $project_id
 * @property string $report_id
 * @property string|null $particulars
 * @property string|null $amount_forwarded
 * @property string|null $amount_sanctioned
 * @property string|null $total_amount
 * @property string|null $expenses_last_month
 * @property string|null $expenses_this_month
 * @property string|null $total_expenses
 * @property string|null $balance_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectBudget $projectBudget
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereAccountDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereUpdatedAt($value)
 */
	class DPAccountDetail extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $activity_id
 * @property string $objective_id
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereUpdatedAt($value)
 */
	class DPActivity extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $objective_id
 * @property string $report_id
 * @property string|null $objective
 * @property string|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property int|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereObjective($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereWhyNotHappened($value)
 */
	class DPObjective extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $outlook_id
 * @property string $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereOutlookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereUpdatedAt($value)
 */
	class DPOutlook extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $photo_id
 * @property string $report_id
 * @property string|null $photo_path
 * @property string|null $photo_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto wherePhotoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto wherePhotoName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPPhoto whereUpdatedAt($value)
 */
	class DPPhoto extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string $project_id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $project_type
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $report_month_year
 * @property string|null $report_before_id
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $amount_sanctioned_overview
 * @property string|null $amount_forwarded_overview
 * @property string|null $amount_in_hand
 * @property string|null $total_balance_forwarded
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPAccountDetail> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\QRDLAnnexure> $annexures
 * @property-read int|null $annexures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportComment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\OldProjects\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\RQISAgeProfile> $rqis_age_profile
 * @property-read int|null $rqis_age_profile_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\RQSTTraineeProfile> $rqst_trainee_profile
 * @property-read int|null $rqst_trainee_profile_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\RQWDInmatesProfile> $rqwd_inmate_profile
 * @property-read int|null $rqwd_inmate_profile_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAmountForwardedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereAmountSanctionedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereProjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereReportBeforeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereReportMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPReport whereUserId($value)
 */
	class DPReport extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $dla_beneficiary_name
 * @property string|null $dla_support_date
 * @property string|null $dla_self_employment
 * @property string|null $dla_amount_sanctioned
 * @property string|null $dla_monthly_profit
 * @property string|null $dla_annual_profit
 * @property string|null $dla_impact
 * @property string|null $dla_challenges
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure query()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaAnnualProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaMonthlyProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaSelfEmployment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereDlaSupportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereUpdatedAt($value)
 */
	class QRDLAnnexure extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $age_group
 * @property string|null $education
 * @property int|null $up_to_previous_year
 * @property int|null $present_academic_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereAgeGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile wherePresentAcademicYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereUpToPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereUpdatedAt($value)
 */
	class RQISAgeProfile extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $education_category
 * @property int|null $number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereEducationCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereUpdatedAt($value)
 */
	class RQSTTraineeProfile extends \Eloquent {}
}

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $age_category
 * @property string|null $status
 * @property int|null $number
 * @property int|null $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereAgeCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereUpdatedAt($value)
 */
	class RQWDInmatesProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $phone
 * @property string|null $center
 * @property string|null $address
 * @property string $role
 * @property string $status
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read User|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCenter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @mixin \Eloquent
 * @property string $province
 * @property string|null $society_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportComment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPReport> $reports
 * @property-read int|null $reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

