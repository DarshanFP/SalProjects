# Phase 2.1 — Unguarded Section Controllers Audit

**Mode:** STRICTLY READ-ONLY | **No code changes made.**

**Scope:** Project update lifecycle only. Reporting modules ignored.

---

## SECTION 1 — All Section Controllers Called During Project Update

Traced from `ProjectController@update` (lines 1357–908).

| Controller | Method | Called From | File:Line |
|------------|--------|-------------|-----------|
| GeneralInfoController | update | ProjectController@update | ProjectController.php:839 |
| KeyInformationController | update | ProjectController@update | ProjectController.php:841 |
| LogicalFrameworkController | update | ProjectController@update | ProjectController.php:848 |
| SustainabilityController | update | ProjectController@update | ProjectController.php:849 |
| BudgetController | update | ProjectController@update | ProjectController.php:850 |
| AttachmentController | update | ProjectController@update | ProjectController.php:853 |
| ProjectEduRUTBasicInfoController | update | ProjectController@update | ProjectController.php:858 |
| EduRUTTargetGroupController | update | ProjectController@update | ProjectController.php:859 |
| EduRUTAnnexedTargetGroupController | update | ProjectController@update | ProjectController.php:860 |
| CICBasicInfoController | update | ProjectController@update | ProjectController.php:865 |
| CCIAchievementsController | update | ProjectController@update | ProjectController.php:869 |
| CCIAgeProfileController | update | ProjectController@update | ProjectController.php:870 |
| CCIAnnexedTargetGroupController | update | ProjectController@update | ProjectController.php:871 |
| CCIEconomicBackgroundController | update | ProjectController@update | ProjectController.php:872 |
| CCIPersonalSituationController | update | ProjectController@update | ProjectController.php:873 |
| CCIPresentSituationController | update | ProjectController@update | ProjectController.php:874 |
| CCIRationaleController | update | ProjectController@update | ProjectController.php:875 |
| CCIStatisticsController | update | ProjectController@update | ProjectController.php:876 |
| IGEInstitutionInfoController | update | ProjectController@update | ProjectController.php:881 |
| IGEBeneficiariesSupportedController | update | ProjectController@update | ProjectController.php:882 |
| IGENewBeneficiariesController | update | ProjectController@update | ProjectController.php:883 |
| IGEOngoingBeneficiariesController | update | ProjectController@update | ProjectController.php:884 |
| IGEBudgetController | update | ProjectController@update | ProjectController.php:885 |
| IGEDevelopmentMonitoringController | update | ProjectController@update | ProjectController.php:886 |
| LDPInterventionLogicController | update | ProjectController@update | ProjectController.php:891 |
| LDPNeedAnalysisController | update | ProjectController@update | ProjectController.php:892 |
| LDPTargetGroupController | update | ProjectController@update | ProjectController.php:893 |
| RSTBeneficiariesAreaController | update | ProjectController@update | ProjectController.php:898 |
| RSTGeographicalAreaController | update | ProjectController@update | ProjectController.php:899 |
| RSTInstitutionInfoController | update | ProjectController@update | ProjectController.php:900 |
| RSTTargetGroupAnnexureController | update | ProjectController@update | ProjectController.php:901 |
| RSTTargetGroupController | update | ProjectController@update | ProjectController.php:902 |
| IESPersonalInfoController | update | ProjectController@update | ProjectController.php:916 |
| IESFamilyWorkingMembersController | update | ProjectController@update | ProjectController.php:917 |
| IESImmediateFamilyDetailsController | update | ProjectController@update | ProjectController.php:918 |
| IESEducationBackgroundController | update | ProjectController@update | ProjectController.php:919 |
| IESExpensesController | update | ProjectController@update | ProjectController.php:920 |
| IESAttachmentsController | update | ProjectController@update | ProjectController.php:921 |
| ILPPersonalInfoController | update | ProjectController@update | ProjectController.php:926 |
| ILPRevenueGoalsController | update | ProjectController@update | ProjectController.php:927 |
| ILPStrengthWeaknessController | update | ProjectController@update | ProjectController.php:928 |
| ILPRiskAnalysisController | update | ProjectController@update | ProjectController.php:929 |
| ILPAttachedDocumentsController | update | ProjectController@update | ProjectController.php:930 |
| ILPBudgetController | update | ProjectController@update | ProjectController.php:931 |
| IAHPersonalInfoController | update | ProjectController@update | ProjectController.php:936 |
| IAHEarningMembersController | update | ProjectController@update | ProjectController.php:937 |
| IAHHealthConditionController | update | ProjectController@update | ProjectController.php:938 |
| IAHSupportDetailsController | update | ProjectController@update | ProjectController.php:939 |
| IAHBudgetDetailsController | update | ProjectController@update | ProjectController.php:940 |
| IAHDocumentsController | update | ProjectController@update | ProjectController.php:941 |
| IIESPersonalInfoController | update | ProjectController@update | ProjectController.php:946 |
| IIESFamilyWorkingMembersController | update | ProjectController@update | ProjectController.php:947 |
| IIESImmediateFamilyDetailsController | update | ProjectController@update | ProjectController.php:948 |
| IIESEducationBackgroundController | update | ProjectController@update | ProjectController.php:949 |
| IIESFinancialSupportController | update | ProjectController@update | ProjectController.php:950 |
| IIESAttachmentsController | update | ProjectController@update | ProjectController.php:951 |
| IIESExpensesController | update | ProjectController@update | ProjectController.php:952 |

**Note:** KeyInformationController is invoked only when project type is not in `ProjectType::getIndividualTypes()`. LogicalFrameworkController, SustainabilityController, BudgetController, AttachmentController are invoked only when `ProjectType::isInstitutional($project->project_type)`. AttachmentController is invoked only when `$request->hasFile('file')`. The switch branch that runs depends on `$project->project_type`.

---

## SECTION 2 — Delete-Recreate Pattern Detection

Controllers that perform delete (or delete-before-create) in their **update** or **store** path (when called from update):

| Controller | Delete Found? | Line(s) | Pattern Type |
|------------|---------------|---------|--------------|
| IAHBudgetDetailsController | Yes | 50, 194 (store path) | Model::where()->delete() then create in loop |
| IESExpensesController | Yes | 72-73, 170-171 (store path) | expenseDetails()->delete(), parent delete, then create |
| IESFamilyWorkingMembersController | Yes | 89 (store path) | Model::where()->delete() then create in loop |
| IAHEarningMembersController | Yes | 30, 138 (store path) | Model::where()->delete() then create in loop |
| LogicalFrameworkController | Yes | 229, 401-404 | Model::where()->delete(); objective->results/risks/activities()->delete() |
| EduRUTTargetGroupController | Yes | 130, 176 (update path) | Model::where()->delete() then create in loop |
| IGEBudgetController | Yes | 66, 163 (store path) | Model::where()->delete() then create in loop |
| IGEBeneficiariesSupportedController | Yes | 41, 120 (store path) | Model::where()->delete() then create in loop |
| IGEOngoingBeneficiariesController | Yes | 41, 129 (store path) | Model::where()->delete() then create in loop |
| IGENewBeneficiariesController | Yes | 61, 167 (store path) | Model::where()->delete() then create in loop |
| LDPTargetGroupController | Yes | 39, 130 (store path) | Model::where()->delete() then create in loop |
| RSTGeographicalAreaController | Yes | 37, 112 (store path) | Model::where()->delete() then create in loop |
| RSTTargetGroupAnnexureController | Yes | 58, 138 (store path) | Model::where()->delete() then create in loop |
| IIESExpensesController | Yes | 72-73, 185-186 (store path) | expenseDetails()->delete(), parent delete, then create |
| RSTBeneficiariesAreaController | Yes | 39, 155 (store path) | Model::where()->delete() then create in loop |
| BudgetController | Yes | 122 (update path) | Model::where()->delete() then create in loop |
| IIESFamilyWorkingMembersController | Yes | 28, 101, 125 (store and update) | Model::where()->delete() then create in loop |
| LDPInterventionLogicController | Yes | 90 (destroy only) | No delete in update path — updateOrCreate in store |
| RSTInstitutionInfoController | Yes | 94 (destroy only) | No delete in update path — updateOrCreate in store |
| RSTTargetGroupController | Yes | 94 (destroy only) | No delete in update path — updateOrCreate in store |
| IGEDevelopmentMonitoringController | Yes | 101 (destroy only) | No delete in update path — updateOrCreate in store |
| IGEInstitutionInfoController | Yes | 94 (destroy only) | No delete in update path — updateOrCreate in store |
| ILPRiskAnalysisController | Yes | 30, 134 (store/destroy) | Model::where()->delete() then create (update calls store) |
| ILPPersonalInfoController | Yes | 103 (destroy only) | No delete in update path — updateOrCreate in store |
| IAHSupportDetailsController | Yes | 34, 119 (store/destroy) | Model::where()->delete() then create (update calls store) |
| IAHHealthConditionController | Yes | 35, 124 (store/destroy) | Model::where()->delete() then create (update calls store) |
| IAHPersonalInfoController | Yes | 35, 129 (store/destroy) | Model::where()->delete() then create (update calls store) |
| EduRUTAnnexedTargetGroupController | Yes | 108, 144 (update/destroy) | Model::where()->delete() then create in loop (update path) |
| ILPRevenueGoalsController | Yes | 177-179, 249-251 (update/destroy) | Model::where()->delete() x3 then create in loops (update path) |
| ILPStrengthWeaknessController | Yes | 30, 130 (store/destroy) | Model::where()->delete() then create (update calls store) |
| ILPBudgetController | Yes | 46, 170 (store path) | Model::where()->delete() then create in loop |

Other controllers (e.g. CCI Statistics, EconomicBackground, AgeProfile, PersonalSituation, Rationale, PresentSituation, Achievements; CCI AnnexedTargetGroup; SustainabilityController; GeneralInfoController; KeyInformationController) use updateOrCreate or single-record update only in the update path; delete appears only in destroy().

---

## SECTION 3 — Guard Detection

For each controller that uses delete-recreate in the **update** path:

| Controller | Has Skip Guard? | Condition | Risk Level |
|------------|-----------------|-----------|------------|
| LogicalFrameworkController | Yes | `isLogicalFrameworkMeaningfullyFilled($objectives)` — skip when section absent/empty | LOW (guarded) |
| BudgetController | Yes | `isBudgetSectionMeaningfullyFilled($phases)` — skip when budget section absent/empty | LOW (guarded) |
| RSTBeneficiariesAreaController | Yes | `isBeneficiariesAreaMeaningfullyFilled(...)` in store | LOW (guarded) |
| EduRUTTargetGroupController | Yes | `isEduRUTTargetGroupMeaningfullyFilled($groups)` | LOW (guarded) |
| IESFamilyWorkingMembersController | Yes | `isIESFamilyWorkingMembersMeaningfullyFilled(...)` in store | LOW (guarded) |
| IESExpensesController | Yes | `isIESExpensesMeaningfullyFilled(...)` in store | LOW (guarded) |
| IGEBeneficiariesSupportedController | Yes | `isIGEBeneficiariesSupportedMeaningfullyFilled(...)` | LOW (guarded) |
| IGENewBeneficiariesController | Yes | `isIGENewBeneficiariesMeaningfullyFilled(...)` | LOW (guarded) |
| IGEOngoingBeneficiariesController | Yes | `isIGEOngoingBeneficiariesMeaningfullyFilled(...)` | LOW (guarded) |
| IGEBudgetController | Yes | `isIGEBudgetMeaningfullyFilled(...)` | LOW (guarded) |
| LDPTargetGroupController | Yes | `isLDPTargetGroupMeaningfullyFilled(...)` | LOW (guarded) |
| RSTGeographicalAreaController | Yes | `isGeographicalAreaMeaningfullyFilled(...)` | LOW (guarded) |
| RSTTargetGroupAnnexureController | Yes | `isTargetGroupAnnexureMeaningfullyFilled(...)` | LOW (guarded) |
| IIESExpensesController | Yes | `isIIESExpensesMeaningfullyFilled(...)` | LOW (guarded) |
| IAHDocumentsController | Yes | `hasAnyIAHFile($request)` — skip when no files | LOW (guarded) |
| IESAttachmentsController | Yes | `$hasAnyFile` (hasFile for IES fields) | LOW (guarded) |
| IIESAttachmentsController | Yes | `$hasAnyFile` (hasFile for IIES fields) | LOW (guarded) |
| ILPAttachedDocumentsController | Yes | `hasAnyILPFile($request)` | LOW (guarded) |
| **IAHEarningMembersController** | **No** | None; store() always deletes then creates | **HIGH** |
| **IAHBudgetDetailsController** | **No** | Only BudgetSyncGuard (approved lock); no section-absent guard | **HIGH** |
| **IIESFamilyWorkingMembersController** | **No** | update() and store() always delete then create | **HIGH** |
| **ILPRiskAnalysisController** | **No** | store() always delete then create; update calls store | **HIGH** |
| **ILPStrengthWeaknessController** | **No** | store() always delete then create; update calls store | **HIGH** |
| **ILPRevenueGoalsController** | **No** | update() always deletes three tables then creates | **HIGH** |
| **ILPBudgetController** | **No** | Only BudgetSyncGuard; no section-absent guard | **HIGH** |
| **IAHSupportDetailsController** | **No** | store() always delete then create; update calls store | **HIGH** |
| **IAHHealthConditionController** | **No** | store() always delete then create; update calls store | **HIGH** |
| **IAHPersonalInfoController** | **No** | store() always delete then create; update calls store | **HIGH** |
| **EduRUTAnnexedTargetGroupController** | **No** | update() always delete at 108 then create in loop | **HIGH** |

No controller uses a literal `section_key` or `if (!$request->has('section_key')) return;`-style guard. Guards that exist are “meaningfully filled” checks (section data present and non-empty) or file-presence checks.

---

## SECTION 4 — Risk Classification

- **HIGH:** Always deletes existing rows (or multiple tables) in the update path; no section-absent or “meaningfully filled” check; called on every project update for that project type.
- **MEDIUM:** Delete only when section key/data present, or partial guard (e.g. budget lock only). None identified in this audit; all unguarded delete-recreate were classified HIGH.
- **LOW:** No delete-recreate in update path, or has skip guard when section absent/empty.

---

## SECTION 5 — Final Inventory

### Unguarded HIGH Risk Controllers

- **IAHEarningMembersController** — `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`  
  - update() delegates to store(); store() runs `ProjectIAHEarningMembers::where('project_id', $projectId)->delete()` (line 30) then creates from request. No check for section presence.

- **IAHBudgetDetailsController** — `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`  
  - update() delegates to store(); store() runs `ProjectIAHBudgetDetails::where('project_id', $projectId)->delete()` (line 50) then creates. Only BudgetSyncGuard (approved lock); no section-absent guard.

- **IIESFamilyWorkingMembersController** — `app/Http/Controllers/Projects/IIES/IIESFamilyWorkingMembersController.php`  
  - update() runs `ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete()` (line 101) then creates. No section-absent guard.

- **ILPRiskAnalysisController** — `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php`  
  - update() calls store(); store() runs `ProjectILPRiskAnalysis::where('project_id', $projectId)->delete()` (line 30) then create. No guard.

- **ILPStrengthWeaknessController** — `app/Http/Controllers/Projects/ILP/StrengthWeaknessController.php`  
  - update() calls store(); store() runs `ProjectILPBusinessStrengthWeakness::where('project_id', $projectId)->delete()` (line 30) then create. No guard.

- **ILPRevenueGoalsController** — `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php`  
  - update() runs delete on ProjectILPRevenuePlanItem, ProjectILPRevenueIncome, ProjectILPRevenueExpense (lines 177-179) then creates. No section-absent guard.

- **ILPBudgetController** — `app/Http/Controllers/Projects/ILP/BudgetController.php`  
  - update() delegates to store(); store() runs `ProjectILPBudget::where('project_id', $projectId)->delete()` (line 46) then create in loop. Only BudgetSyncGuard; no section-absent guard.

- **IAHSupportDetailsController** — `app/Http/Controllers/Projects/IAH/IAHSupportDetailsController.php`  
  - update() calls store(); store() runs `ProjectIAHSupportDetails::where('project_id', $projectId)->delete()` (line 34) then create. No guard.

- **IAHHealthConditionController** — `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php`  
  - update() calls store(); store() runs `ProjectIAHHealthCondition::where('project_id', $projectId)->delete()` (line 35) then create. No guard.

- **IAHPersonalInfoController** — `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php`  
  - update() calls store(); store() runs `ProjectIAHPersonalInfo::where('project_id', $projectId)->delete()` (line 35) then create. No guard.

- **EduRUTAnnexedTargetGroupController** — `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php`  
  - update() runs `ProjectEduRUTAnnexedTargetGroup::where('project_id', $projectId)->delete()` (line 108) then create in loop. No section-absent guard.

### Unguarded MEDIUM Risk Controllers

- None identified. All unguarded delete-recreate controllers above behave as “always delete on every update” for their project type.

---

## SECTION 6 — Impact Assessment

**If the user updates only General Info, can any of these controllers wipe unrelated section data?**

**YES.**

Reason:

1. **ProjectController@update** does not restrict which section controllers run based on which part of the form was edited. It always runs GeneralInfoController and KeyInformationController (when applicable), then for institutional types the common sections (Logical Framework, Sustainability, Budget, Attachment if file present), then **the full switch for the project’s type** — so every section controller for that type is invoked on every update.

2. **Full request is passed** to each section controller. Unguarded controllers do not check whether their section’s keys are present or meaningfully filled; they take `$request->only($fillable)` (or similar), get empty or default values when the user only submitted General Info, then run **delete** and optionally create from that empty set.

3. **Result:** For example, on an IAH project, if the user submits only General Info, the request may omit or send empty data for IAH Earning Members, Budget Details, Support Details, Health Condition, and Personal Info. IAHEarningMembersController->update() still runs, calls store(), deletes all `ProjectIAHEarningMembers` for the project, then creates zero rows. Same for the other unguarded IAH, ILP, IIES, and EduRUT controllers listed in Section 5. So **updating only General Info can wipe data in those sections** for the project type in question.

---

**Phase 2.1 Unguarded Section Audit Complete — No Code Changes Made**
