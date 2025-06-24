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


namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_achievements_id
 * @property string $project_id
 * @property string|null $academic_achievements
 * @property string|null $sport_achievements
 * @property string|null $other_achievements
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereAcademicAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereCCIAchievementsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereOtherAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereSportAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAchievements whereUpdatedAt($value)
 */
	class ProjectCCIAchievements extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_age_profile_id
 * @property string $project_id
 * @property int|null $education_below_5_bridge_course_prev_year
 * @property int|null $education_below_5_bridge_course_current_year
 * @property int|null $education_below_5_kindergarten_prev_year
 * @property int|null $education_below_5_kindergarten_current_year
 * @property string|null $education_below_5_other_prev_year
 * @property string|null $education_below_5_other_current_year
 * @property int|null $education_6_10_primary_school_prev_year
 * @property int|null $education_6_10_primary_school_current_year
 * @property int|null $education_6_10_bridge_course_prev_year
 * @property int|null $education_6_10_bridge_course_current_year
 * @property string|null $education_6_10_other_prev_year
 * @property string|null $education_6_10_other_current_year
 * @property int|null $education_11_15_secondary_school_prev_year
 * @property int|null $education_11_15_secondary_school_current_year
 * @property int|null $education_11_15_high_school_prev_year
 * @property int|null $education_11_15_high_school_current_year
 * @property string|null $education_11_15_other_prev_year
 * @property string|null $education_11_15_other_current_year
 * @property int|null $education_16_above_undergraduate_prev_year
 * @property int|null $education_16_above_undergraduate_current_year
 * @property int|null $education_16_above_technical_vocational_prev_year
 * @property int|null $education_16_above_technical_vocational_current_year
 * @property string|null $education_16_above_other_prev_year
 * @property string|null $education_16_above_other_current_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereCCIAgeProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115HighSchoolCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115HighSchoolPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115OtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115OtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115SecondarySchoolCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115SecondarySchoolPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveOtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveOtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveTechnicalVocationalCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveTechnicalVocationalPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveUndergraduateCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveUndergraduatePrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610BridgeCourseCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610BridgeCoursePrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610OtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610OtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610PrimarySchoolCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610PrimarySchoolPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5BridgeCourseCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5BridgeCoursePrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5KindergartenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5KindergartenPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5OtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5OtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereUpdatedAt($value)
 */
	class ProjectCCIAgeProfile extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_target_group_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $dob
 * @property string|null $date_of_joining
 * @property string|null $class_of_study
 * @property string|null $family_background_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereCCITargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereClassOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereDateOfJoining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereFamilyBackgroundDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereUpdatedAt($value)
 */
	class ProjectCCIAnnexedTargetGroup extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_eco_bg_id
 * @property string $project_id
 * @property int|null $agricultural_labour_number
 * @property int|null $marginal_farmers_number
 * @property int|null $self_employed_parents_number
 * @property int|null $informal_sector_parents_number
 * @property int|null $any_other_number
 * @property string|null $general_remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereAgriculturalLabourNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereAnyOtherNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereCCIEcoBgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereGeneralRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereInformalSectorParentsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereMarginalFarmersNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereSelfEmployedParentsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIEconomicBackground whereUpdatedAt($value)
 */
	class ProjectCCIEconomicBackground extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_personal_situation_id
 * @property string $project_id
 * @property int|null $children_with_parents_last_year
 * @property int|null $children_with_parents_current_year
 * @property int|null $semi_orphans_last_year
 * @property int|null $semi_orphans_current_year
 * @property int|null $orphans_last_year
 * @property int|null $orphans_current_year
 * @property int|null $hiv_infected_last_year
 * @property int|null $hiv_infected_current_year
 * @property int|null $differently_abled_last_year
 * @property int|null $differently_abled_current_year
 * @property int|null $parents_in_conflict_last_year
 * @property int|null $parents_in_conflict_current_year
 * @property int|null $other_ailments_last_year
 * @property int|null $other_ailments_current_year
 * @property string|null $general_remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereCCIPersonalSituationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereChildrenWithParentsCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereChildrenWithParentsLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereDifferentlyAbledCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereDifferentlyAbledLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereGeneralRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereHivInfectedCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereHivInfectedLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOrphansCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOrphansLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOtherAilmentsCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOtherAilmentsLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereParentsInConflictCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereParentsInConflictLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereSemiOrphansCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereSemiOrphansLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereUpdatedAt($value)
 */
	class ProjectCCIPersonalSituation extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_present_situation_id
 * @property string $project_id
 * @property string|null $internal_challenges
 * @property string|null $external_challenges
 * @property string|null $area_of_focus
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereAreaOfFocus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereCCIPresentSituationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereExternalChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereInternalChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereUpdatedAt($value)
 */
	class ProjectCCIPresentSituation extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_rationale_id
 * @property string $project_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereCCIRationaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIRationale whereUpdatedAt($value)
 */
	class ProjectCCIRationale extends \Eloquent {}
}

namespace App\Models\OldProjects\CCI{
/**
 * 
 *
 * @property int $id
 * @property string $CCI_statistics_id
 * @property string $project_id
 * @property int|null $total_children_previous_year
 * @property int|null $total_children_current_year
 * @property int|null $reintegrated_children_previous_year
 * @property int|null $reintegrated_children_current_year
 * @property int|null $shifted_children_previous_year
 * @property int|null $shifted_children_current_year
 * @property int|null $pursuing_higher_studies_previous_year
 * @property int|null $pursuing_higher_studies_current_year
 * @property int|null $settled_children_previous_year
 * @property int|null $settled_children_current_year
 * @property int|null $working_children_previous_year
 * @property int|null $working_children_current_year
 * @property int|null $other_category_previous_year
 * @property int|null $other_category_current_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereCCIStatisticsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereOtherCategoryCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereOtherCategoryPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics wherePursuingHigherStudiesCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics wherePursuingHigherStudiesPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereReintegratedChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereReintegratedChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereSettledChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereSettledChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereShiftedChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereShiftedChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereTotalChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereTotalChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereWorkingChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereWorkingChildrenPreviousYear($value)
 */
	class ProjectCCIStatistics extends \Eloquent {}
}

namespace App\Models\OldProjects\IAH{
/**
 * 
 *
 * @property int $id
 * @property string $IAH_budget_id
 * @property string $project_id
 * @property string|null $particular
 * @property string|null $amount
 * @property string|null $total_expenses
 * @property string|null $family_contribution
 * @property string|null $amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereFamilyContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereIAHBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereUpdatedAt($value)
 */
	class ProjectIAHBudgetDetails extends \Eloquent {}
}

namespace App\Models\OldProjects\IAH{
/**
 * 
 *
 * @property int $id
 * @property string $IAH_doc_id
 * @property string $project_id
 * @property string|null $aadhar_copy
 * @property string|null $request_letter
 * @property string|null $medical_reports
 * @property string|null $other_docs
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereAadharCopy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereIAHDocId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereMedicalReports($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereOtherDocs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereRequestLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHDocuments whereUpdatedAt($value)
 */
	class ProjectIAHDocuments extends \Eloquent {}
}

namespace App\Models\OldProjects\IAH{
/**
 * 
 *
 * @property int $id
 * @property string $IAH_earning_id
 * @property string $project_id
 * @property string|null $member_name
 * @property string|null $work_type
 * @property string|null $monthly_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereIAHEarningId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereMemberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHEarningMembers whereWorkType($value)
 */
	class ProjectIAHEarningMembers extends \Eloquent {}
}

namespace App\Models\OldProjects\IAH{
/**
 * 
 *
 * @property int $id
 * @property string $IAH_health_id
 * @property string $project_id
 * @property string|null $illness
 * @property int|null $treatment
 * @property string|null $doctor
 * @property string|null $hospital
 * @property string|null $doctor_address
 * @property string|null $health_situation
 * @property string|null $family_situation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereDoctor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereDoctorAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereHealthSituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereHospital($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereIAHHealthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereIllness($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereTreatment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereUpdatedAt($value)
 */
	class ProjectIAHHealthCondition extends \Eloquent {}
}

namespace App\Models\OldProjects\IAH{
/**
 * 
 *
 * @property int $id
 * @property string $IAH_info_id
 * @property string|null $project_id
 * @property string|null $name
 * @property int|null $age
 * @property string|null $gender
 * @property string|null $dob
 * @property string|null $aadhar
 * @property string|null $contact
 * @property string|null $address
 * @property string|null $email
 * @property string|null $guardian_name
 * @property int|null $children
 * @property string|null $caste
 * @property string|null $religion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereAadhar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereChildren($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereGuardianName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereIAHInfoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHPersonalInfo whereUpdatedAt($value)
 */
	class ProjectIAHPersonalInfo extends \Eloquent {}
}

namespace App\Models\OldProjects\IAH{
/**
 * 
 *
 * @property int $id
 * @property string $IAH_support_id
 * @property string $project_id
 * @property int|null $employed_at_st_ann
 * @property string|null $employment_details
 * @property int|null $received_support
 * @property string|null $support_details
 * @property int|null $govt_support
 * @property string|null $govt_support_nature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereEmployedAtStAnn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereEmploymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereGovtSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereGovtSupportNature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereIAHSupportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereReceivedSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereSupportDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHSupportDetails whereUpdatedAt($value)
 */
	class ProjectIAHSupportDetails extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_attachment_id
 * @property string $project_id
 * @property string|null $aadhar_card
 * @property string|null $fee_quotation
 * @property string|null $scholarship_proof
 * @property string|null $medical_confirmation
 * @property string|null $caste_certificate
 * @property string|null $self_declaration
 * @property string|null $death_certificate
 * @property string|null $request_letter
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereAadharCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereCasteCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereDeathCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereFeeQuotation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereIESAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereMedicalConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereRequestLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereScholarshipProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereSelfDeclaration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereUpdatedAt($value)
 */
	class ProjectIESAttachments extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_education_id
 * @property string $project_id
 * @property string|null $previous_class
 * @property string|null $amount_sanctioned
 * @property string|null $amount_utilized
 * @property string|null $scholarship_previous_year
 * @property string|null $academic_performance
 * @property string|null $present_class
 * @property string|null $expected_scholarship
 * @property string|null $family_contribution
 * @property string|null $reason_no_support
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereAcademicPerformance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereAmountUtilized($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereExpectedScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereFamilyContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereIESEducationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground wherePresentClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground wherePreviousClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereReasonNoSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereScholarshipPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereUpdatedAt($value)
 */
	class ProjectIESEducationBackground extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_expense_id
 * @property string $particular
 * @property string $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\IES\ProjectIESExpenses $projectIESExpense
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenseDetail whereUpdatedAt($value)
 */
	class ProjectIESExpenseDetail extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_expense_id
 * @property string $project_id
 * @property string|null $total_expenses
 * @property string|null $expected_scholarship_govt
 * @property string|null $support_other_sources
 * @property string|null $beneficiary_contribution
 * @property string|null $balance_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IES\ProjectIESExpenseDetail> $expenseDetails
 * @property-read int|null $expense_details_count
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereBalanceRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereBeneficiaryContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereExpectedScholarshipGovt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereSupportOtherSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereUpdatedAt($value)
 */
	class ProjectIESExpenses extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_family_member_id
 * @property string $project_id
 * @property string|null $member_name
 * @property string|null $work_nature
 * @property string|null $monthly_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereIESFamilyMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereMemberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESFamilyWorkingMembers whereWorkNature($value)
 */
	class ProjectIESFamilyWorkingMembers extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_family_detail_id
 * @property string $project_id
 * @property int $mother_expired
 * @property int $father_expired
 * @property int $grandmother_support
 * @property int $grandfather_support
 * @property int $father_deserted
 * @property string|null $family_details_others
 * @property int $father_sick
 * @property int $father_hiv_aids
 * @property int $father_disabled
 * @property int $father_alcoholic
 * @property string|null $father_health_others
 * @property int $mother_sick
 * @property int $mother_hiv_aids
 * @property int $mother_disabled
 * @property int $mother_alcoholic
 * @property string|null $mother_health_others
 * @property int $own_house
 * @property int $rented_house
 * @property string|null $residential_others
 * @property string|null $family_situation
 * @property string|null $assistance_need
 * @property int $received_support
 * @property string|null $support_details
 * @property int $employed_with_stanns
 * @property string|null $employment_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereAssistanceNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereEmployedWithStanns($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereEmploymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFamilyDetailsOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherDeserted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereGrandfatherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereGrandmotherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereIESFamilyDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereOwnHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereReceivedSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereRentedHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereResidentialOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereSupportDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereUpdatedAt($value)
 */
	class ProjectIESImmediateFamilyDetails extends \Eloquent {}
}

namespace App\Models\OldProjects\IES{
/**
 * 
 *
 * @property int $id
 * @property string $IES_personal_id
 * @property string $project_id
 * @property string|null $bname
 * @property int|null $age
 * @property string|null $gender
 * @property string|null $dob
 * @property string|null $email
 * @property string|null $contact
 * @property string|null $aadhar
 * @property string|null $full_address
 * @property string|null $father_name
 * @property string|null $mother_name
 * @property string|null $mother_tongue
 * @property string|null $current_studies
 * @property string|null $bcaste
 * @property string|null $father_occupation
 * @property string|null $father_income
 * @property string|null $mother_occupation
 * @property string|null $mother_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereAadhar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereBcaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereBname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereCurrentStudies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFatherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFatherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFatherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereIESPersonalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherTongue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereUpdatedAt($value)
 */
	class ProjectIESPersonalInfo extends \Eloquent {}
}

namespace App\Models\OldProjects\IGE{
/**
 * 
 *
 * @property int $id
 * @property string $IGE_bnfcry_supprtd_id
 * @property string $project_id
 * @property string|null $class
 * @property int|null $total_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereIGEBnfcrySupprtdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereTotalNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBeneficiariesSupported whereUpdatedAt($value)
 */
	class ProjectIGEBeneficiariesSupported extends \Eloquent {}
}

namespace App\Models\OldProjects\IGE{
/**
 * 
 *
 * @property int $id
 * @property string $IGE_budget_id
 * @property string $project_id
 * @property string|null $name
 * @property string|null $study_proposed
 * @property string|null $college_fees
 * @property string|null $hostel_fees
 * @property string|null $total_amount
 * @property string|null $scholarship_eligibility
 * @property string|null $family_contribution
 * @property string|null $amount_requested
 * @property string|null $total_amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereCollegeFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereFamilyContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereHostelFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereIGEBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereScholarshipEligibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereStudyProposed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereTotalAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEBudget whereUpdatedAt($value)
 */
	class ProjectIGEBudget extends \Eloquent {}
}

namespace App\Models\OldProjects\IGE{
/**
 * 
 *
 * @property int $id
 * @property string $IGE_dvlpmnt_mntrng_id
 * @property string $project_id
 * @property string|null $proposed_activities
 * @property string|null $monitoring_methods
 * @property string|null $evaluation_process
 * @property string|null $conclusion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereConclusion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereEvaluationProcess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereIGEDvlpmntMntrngId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereMonitoringMethods($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereProposedActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereUpdatedAt($value)
 */
	class ProjectIGEDevelopmentMonitoring extends \Eloquent {}
}

namespace App\Models\OldProjects\IGE{
/**
 * 
 *
 * @property int $id
 * @property string $IGE_institution_id
 * @property string $project_id
 * @property string|null $institutional_type
 * @property string|null $age_group
 * @property int|null $previous_year_beneficiaries
 * @property string|null $outcome_impact
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereAgeGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereIGEInstitutionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereInstitutionalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereOutcomeImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo wherePreviousYearBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereUpdatedAt($value)
 */
	class ProjectIGEInstitutionInfo extends \Eloquent {}
}

namespace App\Models\OldProjects\IGE{
/**
 * 
 *
 * @property int $id
 * @property string $IGE_new_beneficiaries_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $caste
 * @property string|null $address
 * @property string|null $group_year_of_study
 * @property string|null $family_background_need
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereFamilyBackgroundNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereGroupYearOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereIGENewBeneficiariesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereUpdatedAt($value)
 */
	class ProjectIGENewBeneficiaries extends \Eloquent {}
}

namespace App\Models\OldProjects\IGE{
/**
 * 
 *
 * @property int $id
 * @property string $IGE_ongoing_bnfcry_id
 * @property string $project_id
 * @property string|null $obeneficiary_name
 * @property string|null $ocaste
 * @property string|null $oaddress
 * @property string|null $ocurrent_group_year_of_study
 * @property string|null $operformance_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereIGEOngoingBnfcryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOaddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereObeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOcaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOcurrentGroupYearOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereOperformanceDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEOngoingBeneficiaries whereUpdatedAt($value)
 */
	class ProjectIGEOngoingBeneficiaries extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_attachment_id
 * @property string $project_id
 * @property string|null $iies_aadhar_card
 * @property string|null $iies_fee_quotation
 * @property string|null $iies_scholarship_proof
 * @property string|null $iies_medical_confirmation
 * @property string|null $iies_caste_certificate
 * @property string|null $iies_self_declaration
 * @property string|null $iies_death_certificate
 * @property string|null $iies_request_letter
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIIESAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesAadharCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesCasteCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesDeathCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesFeeQuotation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesMedicalConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesRequestLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesScholarshipProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesSelfDeclaration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereUpdatedAt($value)
 */
	class ProjectIIESAttachments extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_education_id
 * @property string $project_id
 * @property string|null $prev_education
 * @property string|null $prev_institution
 * @property string|null $prev_insti_address
 * @property string|null $prev_marks
 * @property string|null $current_studies
 * @property string|null $curr_institution
 * @property string|null $curr_insti_address
 * @property string|null $aspiration
 * @property string|null $long_term_effect
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereAspiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCurrInstiAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCurrInstitution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCurrentStudies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereIIESEducationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereLongTermEffect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevInstiAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevInstitution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevMarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereUpdatedAt($value)
 */
	class ProjectIIESEducationBackground extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_expense_id
 * @property string $iies_particular
 * @property string $iies_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESExpenses|null $projectIIESExpense
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereIIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereIiesAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereIiesParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenseDetail whereUpdatedAt($value)
 */
	class ProjectIIESExpenseDetail extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_expense_id
 * @property string $project_id
 * @property string $iies_total_expenses
 * @property string $iies_expected_scholarship_govt
 * @property string $iies_support_other_sources
 * @property string $iies_beneficiary_contribution
 * @property string $iies_balance_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IIES\ProjectIIESExpenseDetail> $expenseDetails
 * @property-read int|null $expense_details_count
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesBalanceRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesBeneficiaryContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesExpectedScholarshipGovt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesSupportOtherSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereUpdatedAt($value)
 */
	class ProjectIIESExpenses extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_family_member_id
 * @property string $project_id
 * @property string $iies_member_name
 * @property string $iies_work_nature
 * @property string $iies_monthly_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIIESFamilyMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIiesMemberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIiesMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereIiesWorkNature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESFamilyWorkingMembers whereUpdatedAt($value)
 */
	class ProjectIIESFamilyWorkingMembers extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_family_detail_id
 * @property string $project_id
 * @property int $iies_mother_expired
 * @property int $iies_father_expired
 * @property int $iies_grandmother_support
 * @property int $iies_grandfather_support
 * @property int $iies_father_deserted
 * @property string|null $iies_family_details_others
 * @property int $iies_father_sick
 * @property int $iies_father_hiv_aids
 * @property int $iies_father_disabled
 * @property int $iies_father_alcoholic
 * @property string|null $iies_father_health_others
 * @property int $iies_mother_sick
 * @property int $iies_mother_hiv_aids
 * @property int $iies_mother_disabled
 * @property int $iies_mother_alcoholic
 * @property string|null $iies_mother_health_others
 * @property int $iies_own_house
 * @property int $iies_rented_house
 * @property string|null $iies_residential_others
 * @property string|null $iies_family_situation
 * @property string|null $iies_assistance_need
 * @property int $iies_received_support
 * @property string|null $iies_support_details
 * @property int $iies_employed_with_stanns
 * @property string|null $iies_employment_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIIESFamilyDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesAssistanceNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesEmployedWithStanns($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesEmploymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFamilyDetailsOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherDeserted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesGrandfatherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesGrandmotherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesOwnHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesReceivedSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesRentedHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesResidentialOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesSupportDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereUpdatedAt($value)
 */
	class ProjectIIESImmediateFamilyDetails extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_personal_id
 * @property string $project_id
 * @property string $iies_bname
 * @property int|null $iies_age
 * @property string|null $iies_gender
 * @property string|null $iies_dob
 * @property string|null $iies_email
 * @property string|null $iies_contact
 * @property string|null $iies_aadhar
 * @property string|null $iies_full_address
 * @property string|null $iies_father_name
 * @property string|null $iies_mother_name
 * @property string|null $iies_mother_tongue
 * @property string|null $iies_current_studies
 * @property string|null $iies_bcaste
 * @property string|null $iies_father_occupation
 * @property string|null $iies_father_income
 * @property string|null $iies_mother_occupation
 * @property string|null $iies_mother_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIIESPersonalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesAadhar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesBcaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesBname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesCurrentStudies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFatherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFatherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFatherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherTongue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereUpdatedAt($value)
 */
	class ProjectIIESPersonalInfo extends \Eloquent {}
}

namespace App\Models\OldProjects\IIES{
/**
 * 
 *
 * @property int $id
 * @property string $IIES_fin_sup_id
 * @property string $project_id
 * @property int $govt_eligible_scholarship
 * @property string|null $scholarship_amt
 * @property int $other_eligible_scholarship
 * @property string|null $other_scholarship_amt
 * @property string|null $family_contrib
 * @property string|null $no_contrib_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereFamilyContrib($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereGovtEligibleScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereIIESFinSupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereNoContribReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereOtherEligibleScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereOtherScholarshipAmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereScholarshipAmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESScopeFinancialSupport whereUpdatedAt($value)
 */
	class ProjectIIESScopeFinancialSupport extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_doc_id
 * @property string $project_id
 * @property string|null $aadhar_doc
 * @property string|null $request_letter_doc
 * @property string|null $purchase_quotation_doc
 * @property string|null $other_doc
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereAadharDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereILPDocId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereOtherDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments wherePurchaseQuotationDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereRequestLetterDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereUpdatedAt($value)
 */
	class ProjectILPAttachedDocuments extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_budget_id
 * @property string $project_id
 * @property string|null $budget_desc
 * @property string|null $cost
 * @property string|null $beneficiary_contribution
 * @property string|null $amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereBeneficiaryContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereBudgetDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereILPBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereUpdatedAt($value)
 */
	class ProjectILPBudget extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_strength_id
 * @property string $project_id
 * @property string|null $strengths
 * @property string|null $weaknesses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereILPStrengthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBusinessStrengthWeakness whereWeaknesses($value)
 */
	class ProjectILPBusinessStrengthWeakness extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_personal_id
 * @property string $project_id
 * @property string|null $name
 * @property int|null $age
 * @property string|null $gender
 * @property string|null $dob
 * @property string|null $email
 * @property string|null $contact_no
 * @property string|null $aadhar_id
 * @property string|null $address
 * @property string|null $occupation
 * @property string|null $marital_status
 * @property string|null $spouse_name
 * @property int|null $children_no
 * @property string|null $children_edu
 * @property string|null $religion
 * @property string|null $caste
 * @property string|null $family_situation
 * @property int $small_business_status
 * @property string|null $small_business_details
 * @property string|null $monthly_income
 * @property string|null $business_plan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereAadharId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereBusinessPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereChildrenEdu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereChildrenNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereILPPersonalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereSmallBusinessDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereSmallBusinessStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereSpouseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereUpdatedAt($value)
 */
	class ProjectILPPersonalInfo extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_revenue_expenses_id
 * @property string $project_id
 * @property string $description
 * @property string|null $year_1
 * @property string|null $year_2
 * @property string|null $year_3
 * @property string|null $year_4
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereILPRevenueExpensesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueExpense whereYear4($value)
 */
	class ProjectILPRevenueExpense extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueGoals newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueGoals newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueGoals query()
 */
	class ProjectILPRevenueGoals extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_revenue_income_id
 * @property string $project_id
 * @property string $description
 * @property string|null $year_1
 * @property string|null $year_2
 * @property string|null $year_3
 * @property string|null $year_4
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereILPRevenueIncomeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenueIncome whereYear4($value)
 */
	class ProjectILPRevenueIncome extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_revenue_plan_id
 * @property string $project_id
 * @property string $item
 * @property string|null $year_1
 * @property string|null $year_2
 * @property string|null $year_3
 * @property string|null $year_4
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereILPRevenuePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRevenuePlanItem whereYear4($value)
 */
	class ProjectILPRevenuePlanItem extends \Eloquent {}
}

namespace App\Models\OldProjects\ILP{
/**
 * 
 *
 * @property int $id
 * @property string $ILP_risk_id
 * @property string $project_id
 * @property string|null $identified_risks
 * @property string|null $mitigation_measures
 * @property string|null $business_sustainability
 * @property string|null $expected_profits
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereBusinessSustainability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereExpectedProfits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereILPRiskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereIdentifiedRisks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereMitigationMeasures($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPRiskAnalysis whereUpdatedAt($value)
 */
	class ProjectILPRiskAnalysis extends \Eloquent {}
}

namespace App\Models\OldProjects\LDP{
/**
 * 
 *
 * @property int $id
 * @property string $LDP_intervention_logic_id
 * @property string $project_id
 * @property string|null $intervention_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereInterventionDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereLDPInterventionLogicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereUpdatedAt($value)
 */
	class ProjectLDPInterventionLogic extends \Eloquent {}
}

namespace App\Models\OldProjects\LDP{
/**
 * 
 *
 * @property int $id
 * @property string $LDP_need_analysis_id
 * @property string $project_id
 * @property string|null $document_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereLDPNeedAnalysisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPNeedAnalysis whereUpdatedAt($value)
 */
	class ProjectLDPNeedAnalysis extends \Eloquent {}
}

namespace App\Models\OldProjects\LDP{
/**
 * 
 *
 * @property int $id
 * @property string $LDP_target_group_id
 * @property string $project_id
 * @property string|null $L_beneficiary_name
 * @property string|null $L_family_situation
 * @property string|null $L_nature_of_livelihood
 * @property int|null $L_amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLDPTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereLNatureOfLivelihood($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPTargetGroup whereUpdatedAt($value)
 */
	class ProjectLDPTargetGroup extends \Eloquent {}
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
 * @property string|null $predecessor_project_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\RST\ProjectDPRSTBeneficiariesArea> $DPRSTBeneficiariesAreas
 * @property-read int|null $d_p_r_s_t_beneficiaries_areas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectEduRUTAnnexedTargetGroup> $annexed_target_groups
 * @property-read int|null $annexed_target_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectBudget> $budgets
 * @property-read int|null $budgets_count
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIAchievements|null $cciAchievements
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIAgeProfile|null $cciAgeProfile
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIAnnexedTargetGroup|null $cciAnnexedTargetGroup
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIEconomicBackground|null $cciEconomicBackground
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIPersonalSituation|null $cciPersonalSituation
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIPresentSituation|null $cciPresentSituation
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIRationale|null $cciRationale
 * @property-read \App\Models\OldProjects\CCI\ProjectCCIStatistics|null $cciStatistics
 * @property-read \App\Models\OldProjects\ProjectCICBasicInfo|null $cicBasicInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectComment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\OldProjects\ProjectEduRUTBasicInfo|null $eduRUTBasicInfo
 * @property-read mixed $commencement_month
 * @property-read mixed $commencement_year
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IAH\ProjectIAHBudgetDetails> $iahBudgetDetails
 * @property-read int|null $iah_budget_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IAH\ProjectIAHDocuments> $iahDocuments
 * @property-read int|null $iah_documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IAH\ProjectIAHEarningMembers> $iahEarningMembers
 * @property-read int|null $iah_earning_members_count
 * @property-read \App\Models\OldProjects\IAH\ProjectIAHHealthCondition|null $iahHealthCondition
 * @property-read \App\Models\OldProjects\IAH\ProjectIAHPersonalInfo|null $iahPersonalInfo
 * @property-read \App\Models\OldProjects\IAH\ProjectIAHSupportDetails|null $iahSupportDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IES\ProjectIESAttachments> $iesAttachements
 * @property-read int|null $ies_attachements_count
 * @property-read \App\Models\OldProjects\IES\ProjectIESEducationBackground|null $iesEducationBackground
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IES\ProjectIESExpenses> $iesExpenses
 * @property-read int|null $ies_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IES\ProjectIESFamilyWorkingMembers> $iesFamilyWorkingMembers
 * @property-read int|null $ies_family_working_members_count
 * @property-read \App\Models\OldProjects\IES\ProjectIESImmediateFamilyDetails|null $iesImmediateFamilyDetails
 * @property-read \App\Models\OldProjects\IES\ProjectIESPersonalInfo|null $iesPersonalInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IGE\ProjectIGEBeneficiariesSupported> $igeBeneficiariesSupported
 * @property-read int|null $ige_beneficiaries_supported_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IGE\ProjectIGEBudget> $igeBudget
 * @property-read int|null $ige_budget_count
 * @property-read \App\Models\OldProjects\IGE\ProjectIGEDevelopmentMonitoring|null $igeDevelopmentMonitoring
 * @property-read \App\Models\OldProjects\IGE\ProjectIGEInstitutionInfo|null $igeInstitutionInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IGE\ProjectIGENewBeneficiaries> $igeNewBeneficiaries
 * @property-read int|null $ige_new_beneficiaries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IGE\ProjectIGEOngoingBeneficiaries> $igeOngoingBeneficiaries
 * @property-read int|null $ige_ongoing_beneficiaries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IIES\ProjectIIESAttachments> $iiesAttachments
 * @property-read int|null $iies_attachments_count
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESEducationBackground|null $iiesEducationBackground
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESExpenses|null $iiesExpenses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IIES\ProjectIIESFamilyWorkingMembers> $iiesFamilyWorkingMembers
 * @property-read int|null $iies_family_working_members_count
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport|null $iiesFinancialSupport
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESImmediateFamilyDetails|null $iiesImmediateFamilyDetails
 * @property-read \App\Models\OldProjects\IIES\ProjectIIESPersonalInfo|null $iiesPersonalInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ILP\ProjectILPAttachedDocuments> $ilpAttachedDocuments
 * @property-read int|null $ilp_attached_documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ILP\ProjectILPBudget> $ilpBudget
 * @property-read int|null $ilp_budget_count
 * @property-read \App\Models\OldProjects\ILP\ProjectILPBusinessStrengthWeakness|null $ilpBusinessStrengthWeakness
 * @property-read \App\Models\OldProjects\ILP\ProjectILPPersonalInfo|null $ilpPersonalInfo
 * @property-read \App\Models\OldProjects\ILP\ProjectILPRiskAnalysis|null $ilpRiskAnalysis
 * @property-read \App\Models\OldProjects\LDP\ProjectLDPInterventionLogic|null $ldpInterventionLogic
 * @property-read \App\Models\OldProjects\LDP\ProjectLDPNeedAnalysis|null $ldpNeedAnalysis
 * @property-read \App\Models\OldProjects\LDP\ProjectLDPTargetGroup|null $ldpTargetGroup
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectObjective> $logical_frameworks
 * @property-read int|null $logical_frameworks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectObjective> $objectives
 * @property-read int|null $objectives_count
 * @property-read Project|null $predecessor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPReport> $reports
 * @property-read int|null $reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ILP\ProjectILPRevenueExpense> $revenueExpenses
 * @property-read int|null $revenue_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ILP\ProjectILPRevenueIncome> $revenueIncomes
 * @property-read int|null $revenue_incomes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ILP\ProjectILPRevenuePlanItem> $revenuePlanItems
 * @property-read int|null $revenue_plan_items_count
 * @property-read \App\Models\OldProjects\RST\ProjectRSTFinancialSummary|null $rstFinancialSummaries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\RST\ProjectRSTGeographicalArea> $rstGeographicalAreas
 * @property-read int|null $rst_geographical_areas_count
 * @property-read \App\Models\OldProjects\RST\ProjectRSTInstitutionInfo|null $rstInstitutionInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\RST\ProjectRSTPersonalCost> $rstPersonalCosts
 * @property-read int|null $rst_personal_costs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\RST\ProjectRSTProgrammeExpenses> $rstProgrammeExpenses
 * @property-read int|null $rst_programme_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\RST\ProjectRSTTargetGroup> $rstTargetGroup
 * @property-read int|null $rst_target_group_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\RST\ProjectRSTTargetGroupAnnexure> $rstTargetGroupAnnexure
 * @property-read int|null $rst_target_group_annexure_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Project> $successors
 * @property-read int|null $successors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectSustainability> $sustainabilities
 * @property-read int|null $sustainabilities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectEduRUTTargetGroup> $target_groups
 * @property-read int|null $target_groups_count
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
 * @method static \Illuminate\Database\Eloquent\Builder|Project wherePredecessorProjectId($value)
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
 * @property string|null $verification
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
 * @property string $cic_basic_info_id
 * @property string $project_id
 * @property int|null $number_served_since_inception
 * @property int|null $number_served_previous_year
 * @property string|null $beneficiary_categories
 * @property string|null $sisters_intervention
 * @property string|null $beneficiary_conditions
 * @property string|null $beneficiary_problems
 * @property string|null $institution_challenges
 * @property string|null $support_received
 * @property string|null $project_need
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereBeneficiaryCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereBeneficiaryConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereBeneficiaryProblems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereCicBasicInfoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereInstitutionChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereNumberServedPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereNumberServedSinceInception($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereProjectNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereSistersIntervention($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereSupportReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereUpdatedAt($value)
 */
	class ProjectCICBasicInfo extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $annexed_target_group_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $family_background
 * @property string|null $need_of_support
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereAnnexedTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereFamilyBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereNeedOfSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereUpdatedAt($value)
 */
	class ProjectEduRUTAnnexedTargetGroup extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $operational_area_id
 * @property string $project_id
 * @property string|null $institution_type
 * @property string|null $group_type
 * @property string|null $category
 * @property string|null $project_location
 * @property string|null $sisters_work
 * @property string|null $conditions
 * @property string|null $problems
 * @property string|null $need
 * @property string|null $criteria
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereCriteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereInstitutionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereOperationalAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereProblems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereProjectLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereSistersWork($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereUpdatedAt($value)
 */
	class ProjectEduRUTBasicInfo extends \Eloquent {}
}

namespace App\Models\OldProjects{
/**
 * 
 *
 * @property int $id
 * @property string $target_group_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $caste
 * @property string|null $institution_name
 * @property string|null $class_standard
 * @property string|null $total_tuition_fee
 * @property int|null $eligibility_scholarship
 * @property string|null $expected_amount
 * @property string|null $contribution_from_family
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereClassStandard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereContributionFromFamily($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereEligibilityScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereExpectedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereInstitutionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereTotalTuitionFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereUpdatedAt($value)
 */
	class ProjectEduRUTTargetGroup extends \Eloquent {}
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
 * @property-read \App\Models\Reports\Monthly\DPActivity $DPactivity
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

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property int $id
 * @property string $DPRST_bnfcrs_area_id
 * @property string $project_id
 * @property string|null $project_area
 * @property string|null $category_beneficiary
 * @property int|null $direct_beneficiaries
 * @property int|null $indirect_beneficiaries
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereCategoryBeneficiary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereDPRSTBnfcrsAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereDirectBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereIndirectBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereProjectArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectDPRSTBeneficiariesArea whereUpdatedAt($value)
 */
	class ProjectDPRSTBeneficiariesArea extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTFinancialSummary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTFinancialSummary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTFinancialSummary query()
 */
	class ProjectRSTFinancialSummary extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property int $id
 * @property string $geographical_area_id
 * @property string $project_id
 * @property string|null $mandal
 * @property string|null $villages
 * @property string|null $town
 * @property int|null $no_of_beneficiaries
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereGeographicalAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereMandal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereNoOfBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereTown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTGeographicalArea whereVillages($value)
 */
	class ProjectRSTGeographicalArea extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property int $id
 * @property string $RST_institution_id
 * @property string $project_id
 * @property string|null $year_setup
 * @property int|null $total_students_trained
 * @property int|null $beneficiaries_last_year
 * @property string|null $training_outcome
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereBeneficiariesLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereRSTInstitutionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereTotalStudentsTrained($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereTrainingOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereYearSetup($value)
 */
	class ProjectRSTInstitutionInfo extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTPersonalCost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTPersonalCost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTPersonalCost query()
 */
	class ProjectRSTPersonalCost extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTProgrammeExpenses newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTProgrammeExpenses newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTProgrammeExpenses query()
 */
	class ProjectRSTProgrammeExpenses extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property int $id
 * @property string $RST_target_group_id
 * @property string $project_id
 * @property int|null $tg_no_of_beneficiaries
 * @property string|null $beneficiaries_description_problems
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereBeneficiariesDescriptionProblems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereRSTTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereTgNoOfBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroup whereUpdatedAt($value)
 */
	class ProjectRSTTargetGroup extends \Eloquent {}
}

namespace App\Models\OldProjects\RST{
/**
 * 
 *
 * @property int $id
 * @property string $target_group_anxr_id
 * @property string $project_id
 * @property string|null $rst_name
 * @property string|null $rst_religion
 * @property string|null $rst_caste
 * @property string|null $rst_education_background
 * @property string|null $rst_family_situation
 * @property string|null $rst_paragraph
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstEducationBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstParagraph($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereTargetGroupAnxrId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereUpdatedAt($value)
 */
	class ProjectRSTTargetGroupAnnexure extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $project_comment_id
 * @property string $project_id
 * @property int $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereProjectCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereUserId($value)
 */
	class ProjectComment extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $R_comment_id
 * @property string $report_id
 * @property int $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereRCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportComment whereUserId($value)
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
 * @property string|null $project_activity_id
 * @property string|null $activity
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPObjective $objective
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectTimeframe> $timeframes
 * @property-read int|null $timeframes_count
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereProjectActivityId($value)
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
 * @property string|null $project_objective_id
 * @property string $report_id
 * @property string|null $objective
 * @property array|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property bool|null $changes
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
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereProjectObjectiveId($value)
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\ReportAttachment> $attachments
 * @property-read int|null $attachments_count
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

namespace App\Models\Reports\Monthly{
/**
 * 
 *
 * @property int $id
 * @property string $attachment_id
 * @property string $report_id
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $description
 * @property string|null $public_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment wherePublicUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAttachment whereUpdatedAt($value)
 */
	class ReportAttachment extends \Eloquent {}
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

