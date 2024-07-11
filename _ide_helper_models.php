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


namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $beneficiary_name
 * @property string|null $support_date
 * @property string|null $self_employment
 * @property string|null $amount_sanctioned
 * @property string|null $monthly_profit
 * @property string|null $annual_profit
 * @property string|null $impact
 * @property string|null $challenges
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDLReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure query()
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereAnnualProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereMonthlyProfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereSelfEmployment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereSupportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QRDLAnnexure whereUpdatedAt($value)
 */
	class QRDLAnnexure extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
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
 * @property-read \App\Models\Reports\Quarterly\RQDLReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLAccountDetail whereUpdatedAt($value)
 */
	class RQDLAccountDetail extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $objective_id
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDLObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLActivity whereUpdatedAt($value)
 */
	class RQDLActivity extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property int|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDLActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Quarterly\RQDLReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLObjective whereWhyNotHappened($value)
 */
	class RQDLObjective extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDLReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLOutlook whereUpdatedAt($value)
 */
	class RQDLOutlook extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $path
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDLReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLPhoto whereUpdatedAt($value)
 */
	class RQDLPhoto extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $reporting_period
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $amount_sanctioned_overview
 * @property string|null $amount_forwarded_overview
 * @property string|null $amount_in_hand
 * @property string|null $total_balance_forwarded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDLAccountDetail> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\QRDLAnnexure> $annexures
 * @property-read int|null $annexures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDLObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDLOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDLPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereAmountForwardedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereAmountSanctionedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereReportingPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDLReport whereUserId($value)
 */
	class RQDLReport extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
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
 * @property-read \App\Models\Reports\Quarterly\RQDPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPAccountDetail whereUpdatedAt($value)
 */
	class RQDPAccountDetail extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $objective_id
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDPObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPActivity whereUpdatedAt($value)
 */
	class RQDPActivity extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property int|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDPActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Quarterly\RQDPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPObjective whereWhyNotHappened($value)
 */
	class RQDPObjective extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPOutlook whereUpdatedAt($value)
 */
	class RQDPOutlook extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $photo_path
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQDPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPPhoto whereUpdatedAt($value)
 */
	class RQDPPhoto extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $reporting_period
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $amount_sanctioned_overview
 * @property string|null $amount_forwarded_overview
 * @property string|null $amount_in_hand
 * @property string|null $total_balance_forwarded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDPAccountDetail> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDPObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDPOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDPPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereAmountForwardedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereAmountSanctionedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereReportingPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQDPReport whereUserId($value)
 */
	class RQDPReport extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $particulars
 * @property string $amount_forwarded
 * @property string $amount_sanctioned
 * @property string $total_amount
 * @property string $expenses_last_month
 * @property string $expenses_this_month
 * @property string $total_expenses
 * @property string $balance_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQISReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAccountDetail whereUpdatedAt($value)
 */
	class RQISAccountDetail extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $objective_id
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQISObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISActivity whereUpdatedAt($value)
 */
	class RQISActivity extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $age_group
 * @property string|null $education
 * @property int|null $up_to_previous_year
 * @property int|null $present_academic_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQISReport $report
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

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property int|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Quarterly\RQISReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISObjective whereWhyNotHappened($value)
 */
	class RQISObjective extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQISReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISOutlook whereUpdatedAt($value)
 */
	class RQISOutlook extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $photo_path
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQISReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISPhoto whereUpdatedAt($value)
 */
	class RQISPhoto extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $province
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $institution_type
 * @property string|null $beneficiary_statistics
 * @property string|null $monitoring_period
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $amount_sanctioned_overview
 * @property string|null $amount_forwarded_overview
 * @property string|null $total_balance_forwarded
 * @property string|null $amount_in_hand
 * @property int|null $total_up_to_previous_below_5
 * @property int|null $total_present_academic_below_5
 * @property int|null $total_up_to_previous_6_10
 * @property int|null $total_present_academic_6_10
 * @property int|null $total_up_to_previous_11_15
 * @property int|null $total_present_academic_11_15
 * @property int|null $total_up_to_previous_16_above
 * @property int|null $total_present_academic_16_above
 * @property int|null $grand_total_up_to_previous
 * @property int|null $grand_total_present_academic
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISAccountDetail> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISAgeProfile> $ageProfiles
 * @property-read int|null $age_profiles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereAmountForwardedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereAmountSanctionedOverview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereBeneficiaryStatistics($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereGrandTotalPresentAcademic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereGrandTotalUpToPrevious($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereInstitutionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereMonitoringPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalPresentAcademic1115($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalPresentAcademic16Above($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalPresentAcademic610($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalPresentAcademicBelow5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalUpToPrevious1115($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalUpToPrevious16Above($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalUpToPrevious610($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereTotalUpToPreviousBelow5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISReport whereUserId($value)
 */
	class RQISReport extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
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
 * @property-read \App\Models\Reports\Quarterly\RQSTReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTAccountDetails whereUpdatedAt($value)
 */
	class RQSTAccountDetails extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $objective_id
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQSTObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTActivity whereUpdatedAt($value)
 */
	class RQSTActivity extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property int|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Quarterly\RQSTReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTObjective whereWhyNotHappened($value)
 */
	class RQSTObjective extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQSTReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTOutlook whereUpdatedAt($value)
 */
	class RQSTOutlook extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $photo_path
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQSTReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTPhoto whereUpdatedAt($value)
 */
	class RQSTPhoto extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $reporting_period
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $prjct_amount_sanctioned
 * @property string|null $l_y_amount_forwarded
 * @property string|null $amount_in_hand
 * @property string|null $total_balance_forwarded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTAccountDetails> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTTraineeProfile> $inmatesProfiles
 * @property-read int|null $inmates_profiles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereLYAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport wherePrjctAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereReportingPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTReport whereUserId($value)
 */
	class RQSTReport extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $education_category
 * @property int|null $number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQSTReport $report
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

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
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
 * @property-read \App\Models\Reports\Quarterly\RQWDReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDAccountDetail whereUpdatedAt($value)
 */
	class RQWDAccountDetail extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $objective_id
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQWDObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDActivity whereUpdatedAt($value)
 */
	class RQWDActivity extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $age_category
 * @property string|null $status
 * @property int|null $number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQWDReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereAgeCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereUpdatedAt($value)
 */
	class RQWDInmatesProfile extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property int|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Quarterly\RQWDReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDObjective whereWhyNotHappened($value)
 */
	class RQWDObjective extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQWDReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDOutlook whereUpdatedAt($value)
 */
	class RQWDOutlook extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string|null $photo_path
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Quarterly\RQWDReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDPhoto whereUpdatedAt($value)
 */
	class RQWDPhoto extends \Eloquent {}
}

namespace App\Models\Reports\Quarterly{
/**
 * 
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $project_title
 * @property string|null $place
 * @property string|null $society_name
 * @property string|null $commencement_month_year
 * @property string|null $in_charge
 * @property int|null $total_beneficiaries
 * @property string|null $reporting_period
 * @property string|null $goal
 * @property string|null $account_period_start
 * @property string|null $account_period_end
 * @property string|null $prjct_amount_sanctioned
 * @property string|null $l_y_amount_forwarded
 * @property string|null $amount_in_hand
 * @property string|null $total_balance_forwarded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDAccountDetail> $accountDetails
 * @property-read int|null $account_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDInmatesProfile> $inmatesProfiles
 * @property-read int|null $inmates_profiles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDOutlook> $outlooks
 * @property-read int|null $outlooks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereAccountPeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereAccountPeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereAmountInHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereCommencementMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereLYAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport wherePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport wherePrjctAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereProjectTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereReportingPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereSocietyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereTotalBalanceForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereTotalBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDReport whereUserId($value)
 */
	class RQWDReport extends \Eloquent {}
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDLReport> $rqdlReports
 * @property-read int|null $rqdl_reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQDPReport> $rqdpReports
 * @property-read int|null $rqdp_reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQISReport> $rqisReports
 * @property-read int|null $rqis_reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQSTReport> $rqstReports
 * @property-read int|null $rqst_reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Quarterly\RQWDReport> $rqwdReports
 * @property-read int|null $rqwd_reports_count
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

