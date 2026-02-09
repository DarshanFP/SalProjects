# Phase 1A — Controller Refactor Inventory

## Purpose

Phase 1A replaces unscoped `$request->all()` + `fill()` with scoped input (`$request->only($fillable)` + `ArrayToScalarNormalizer::forFillable()`) across all project sub-controllers. This prevents array-to-scalar conversion errors, mass assignment from unrelated form sections, and field collisions in multi-step project forms.

**Canonical reference**: `Phase_1A_Refactor_Playbook.md` — follow it exactly for each controller refactor.

**Execution**: Fix-First, Single-Deploy. All controllers refactored and verified locally; deployment after `PHASE_1A_FINAL_SIGNOFF.md` and all phase sign-offs.

---

## Local Verification Scope (Phase 1A)

**Definition**: Local verification means running the Laravel application locally (e.g. via MAMP, `php artisan serve`, or equivalent) and exercising controller flows through the browser or API, without deployment to production or staging.

**Flows to Test**:
- **Phase 1A.2 (Pilot)**: IES Personal Info — Create and Edit via IES project flow.
- **Phase 1A.3**: All IES controllers (6) and all IIES controllers (2) — end-to-end project create/edit flows per module.
- **Phase 1A.4**: Representative flows across remaining modules (IAH, ILP, IGE, CCI, RST, LDP, EduRUT, CIC) — sampling logic documented in sign-off.

**PASS Criteria**:
- Create and Edit flows complete successfully; no 500 errors.
- No "Array to string conversion" errors in logs or response.
- No mass-assignment regressions (unexpected fields persisted).
- Attachment flows (IES/IIES) upload and persist correctly.
- Laravel logs show no new Phase 1A–related errors during exercised flows.

**FAIL Criteria**:
- 500 errors on create or edit for refactored controllers.
- "Array to string conversion" or similar scalar/array mismatches.
- Attachment upload failure or regression.
- New unexpected errors in `storage/logs/laravel.log` during exercised flows.

---

## Inventory

### IES (Phase 1A.3)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| IES | IESEducationBackgroundController | `app/Http/Controllers/Projects/IES/IESEducationBackgroundController.php` | Array-to-scalar conversion (Phase 0 fix) | Completed | `Implementations/Phase_0/0.3_IES_Education_Background_Scalar.md` |
| IES | IESPersonalInfoController | `app/Http/Controllers/Projects/IES/IESPersonalInfoController.php` | `$request->all()` | Completed | `IESPersonalInfoController.md` |
| IES | IESImmediateFamilyDetailsController | `app/Http/Controllers/Projects/IES/IESImmediateFamilyDetailsController.php` | `fill($request->all())` | Completed | `IESImmediateFamilyDetailsController.md` |
| IES | IESFamilyWorkingMembersController | `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php` | `$request->all()` | Completed | `IESFamilyWorkingMembersController.md` |
| IES | IESExpensesController | `app/Http/Controllers/Projects/IES/IESExpensesController.php` | `$request->all()` | Completed | `IESExpensesController.md` |
| IES | IESAttachmentsController | `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` | `$request->all()` | Completed | `IESAttachmentsController.md` |

---

### IIES (Phase 1A.3)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| IIES | EducationBackgroundController | `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php` | `$request->all()` | Completed | `IIES_EducationBackgroundController.md` |
| IIES | IIESAttachmentsController | `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` | `$request->all()` | Completed | `IIESAttachmentsController.md` |

---

### IAH (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| IAH | IAHPersonalInfoController | `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php` | `$request->all()` | Completed | `IAHPersonalInfoController.md` |
| IAH | IAHBudgetDetailsController | `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | `$request->all()` | Completed | `IAHBudgetDetailsController.md` |
| IAH | IAHDocumentsController | `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` | `$request->all()` | Completed | `IAHDocumentsController.md` |
| IAH | IAHSupportDetailsController | `app/Http/Controllers/Projects/IAH/IAHSupportDetailsController.php` | `$request->all()` | Completed | `IAHSupportDetailsController.md` |
| IAH | IAHHealthConditionController | `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php` | `$request->all()` | Completed | `IAHHealthConditionController.md` |
| IAH | IAHEarningMembersController | `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php` | `$request->all()` | Completed | `IAHEarningMembersController.md` |

---

### ILP (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| ILP | PersonalInfoController | `app/Http/Controllers/Projects/ILP/PersonalInfoController.php` | `$request->all()` | Completed | `ILP_PersonalInfoController.md` |
| ILP | BudgetController | `app/Http/Controllers/Projects/ILP/BudgetController.php` | `$request->all()` | Completed | `ILP_BudgetController.md` |
| ILP | AttachedDocumentsController | `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` | `$request->all()` | Completed | `ILP_AttachedDocumentsController.md` |
| ILP | StrengthWeaknessController | `app/Http/Controllers/Projects/ILP/StrengthWeaknessController.php` | `$request->all()`, `$validatedData = $request->all()` | Completed | `ILP_StrengthWeaknessController.md` |
| ILP | RevenueGoalsController | `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php` | `$request->all()` | Completed | `ILP_RevenueGoalsController.md` |
| ILP | RiskAnalysisController | `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php` | `$request->all()`, `$validatedData = $request->all()` | Completed | `ILP_RiskAnalysisController.md` |

---

### IGE (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| IGE | IGEBudgetController | `app/Http/Controllers/Projects/IGE/IGEBudgetController.php` | `$request->all()` | Completed | `IGEBudgetController.md` |
| IGE | InstitutionInfoController | `app/Http/Controllers/Projects/IGE/InstitutionInfoController.php` | `$request->all()` | Completed | `IGE_InstitutionInfoController.md` |
| IGE | NewBeneficiariesController | `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php` | `$request->all()` | Completed | `IGE_NewBeneficiariesController.md` |
| IGE | OngoingBeneficiariesController | `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php` | `$request->all()` | Completed | `IGE_OngoingBeneficiariesController.md` |
| IGE | IGEBeneficiariesSupportedController | `app/Http/Controllers/Projects/IGE/IGEBeneficiariesSupportedController.php` | `$request->all()` | Completed | `IGE_IGEBeneficiariesSupportedController.md` |
| IGE | DevelopmentMonitoringController | `app/Http/Controllers/Projects/IGE/DevelopmentMonitoringController.php` | `$request->all()` | Completed | `IGE_DevelopmentMonitoringController.md` |

---

### CCI (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| CCI | RationaleController | `app/Http/Controllers/Projects/CCI/RationaleController.php` | `$request->all()` | Completed | `CCI_RationaleController.md` |
| CCI | AnnexedTargetGroupController | `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` | `$request->all()` | Completed | `CCI_AnnexedTargetGroupController.md` |
| CCI | PresentSituationController | `app/Http/Controllers/Projects/CCI/PresentSituationController.php` | `$request->all()` | Completed | `CCI_PresentSituationController.md` |
| CCI | AchievementsController | `app/Http/Controllers/Projects/CCI/AchievementsController.php` | `$request->all()` | Completed | `CCI_AchievementsController.md` |
| CCI | AgeProfileController | `app/Http/Controllers/Projects/CCI/AgeProfileController.php` | `fill($validated)` — FormRequest; scope verified | Completed | `CCI_AgeProfileController.md` |
| CCI | EconomicBackgroundController | `app/Http/Controllers/Projects/CCI/EconomicBackgroundController.php` | `fill($validated)` — FormRequest; scope verified | Completed | `CCI_EconomicBackgroundController.md` |
| CCI | PersonalSituationController | `app/Http/Controllers/Projects/CCI/PersonalSituationController.php` | `foreach` on `$validated` — FormRequest; scope verified | Completed | `CCI_PersonalSituationController.md` |
| CCI | StatisticsController | `app/Http/Controllers/Projects/CCI/StatisticsController.php` | `foreach` on `$validated` — FormRequest; scope verified | Completed | `CCI_StatisticsController.md` |

---

### RST (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| RST | GeographicalAreaController | `app/Http/Controllers/Projects/RST/GeographicalAreaController.php` | `$request->all()`, `$validatedData = $request->all()` | Completed | `RST_GeographicalAreaController.md` |
| RST | TargetGroupController | `app/Http/Controllers/Projects/RST/TargetGroupController.php` | `$request->all()` | Completed | `RST_TargetGroupController.md` |
| RST | TargetGroupAnnexureController | `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php` | `$request->all()` | Completed | `RST_TargetGroupAnnexureController.md` |
| RST | InstitutionInfoController | `app/Http/Controllers/Projects/RST/InstitutionInfoController.php` | `$request->all()` | Completed | `RST_InstitutionInfoController.md` |
| RST | BeneficiariesAreaController | `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php` | `$request->all()`, `$validatedData = $request->all()` | Completed | `RST_BeneficiariesAreaController.md` |

---

### LDP (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| LDP | TargetGroupController | `app/Http/Controllers/Projects/LDP/TargetGroupController.php` | `$request->all()` | Completed | `LDP_TargetGroupController.md` |
| LDP | NeedAnalysisController | `app/Http/Controllers/Projects/LDP/NeedAnalysisController.php` | `$request->all()` | Completed | `LDP_NeedAnalysisController.md` |
| LDP | InterventionLogicController | `app/Http/Controllers/Projects/LDP/InterventionLogicController.php` | `$request->all()` | Completed | `LDP_InterventionLogicController.md` |

---

### EduRUT (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| EduRUT | EduRUTAnnexedTargetGroupController | `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php` | `$request->all()` | Completed | `EduRUT_EduRUTAnnexedTargetGroupController.md` |
| EduRUT | EduRUTTargetGroupController | `app/Http/Controllers/Projects/EduRUTTargetGroupController.php` | `$validatedData = $request->all()` | Completed | `EduRUT_EduRUTTargetGroupController.md` |
| EduRUT | ProjectEduRUTBasicInfoController | `app/Http/Controllers/Projects/ProjectEduRUTBasicInfoController.php` | `$request->all()` | Completed | `EduRUT_ProjectEduRUTBasicInfoController.md` |

---

### CIC (Phase 1A.4)

| Module | Controller Name | File Path | Primary Risk | Status | Implementation MD |
|--------|-----------------|-----------|--------------|--------|-------------------|
| CIC | CICBasicInfoController | `app/Http/Controllers/Projects/CICBasicInfoController.php` | `$request->all()` | Completed | `CIC_CICBasicInfoController.md` |

---

## Excluded (Not in Scope)

| Controller / Area | Reason |
|-------------------|--------|
| `ProjectController` | Deferred to Phase 2 — store/update orchestration |
| `ExportController` | Deferred to Phase 2 |
| `BudgetController` (Projects) | Phase 1A.2 (derived-field enforcement); uses FormRequest `getNormalizedInput()`, not `$request->all()` |
| `ReportController`, `Reports/*`, `Monthly/*`, `Quarterly/*` | Deferred to Phase 2 — report flows |
| `GeneralInfoController`, `KeyInformationController` | Use `$request->validate()` / `$request->validated()` — already scoped |
| `AttachmentController` (Projects) | Use `$request->validate()` — already scoped |
| `LogicalFrameworkController`, `SustainabilityController` | Use `$request->validate()` — already scoped |
| `IIESFinancialSupportController`, `IIESExpensesController`, `IIESFamilyWorkingMembersController`, `IIESImmediateFamilyDetailsController`, `IIESPersonalInfoController` | Use `$validator->validated()` — already scoped; verify FormRequest rules are exhaustive |

---

## Summary

| Phase | Module | Controllers | Status |
|-------|--------|-------------|--------|
| 1A.3 | IES | 6 | 6 Completed |
| 1A.3 | IIES | 2 | 2 Completed |
| 1A.4 | IAH | 6 | 0 Not Started, 6 Completed |
| 1A.4 | ILP | 6 | 0 Not Started, 6 Completed |
| 1A.4 | IGE | 6 | 0 Not Started, 6 Completed |
| 1A.4 | CCI | 8 | 8 Completed |
| 1A.4 | RST | 5 | 5 Completed |
| 1A.4 | LDP | 3 | 3 Completed |
| 1A.4 | EduRUT | 3 | 3 Completed |
| 1A.4 | CIC | 1 | 1 Completed |
| **Total** | | **46** | **46 Completed (IES+IIES+IAH+ILP+IGE+CCI+RST+LDP+EduRUT+CIC)** |

---

*This file is the single tracker for Phase 1A controller refactors. Update Status and Implementation MD as work progresses.*
