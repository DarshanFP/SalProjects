# Phase 2.1 — FormDataExtractor Adoption Tracker

## Purpose

This file tracks incremental adoption of `FormDataExtractor` across eligible project controllers. Phase 2.1 replaces the Phase 1A inline normalization pattern (`$request->only($fillable)` + `ArrayToScalarNormalizer::forFillable()`) with `FormDataExtractor::forFillable($request, $fillable)`. Adoption is controller-by-controller; behavior must remain identical.

---

## Phase 2.1 Adoption Status (Summary)

| Status | Count | Controllers |
|--------|-------|-------------|
| **Done (Adopted)** | 18 | All full Phase 1A pattern controllers listed below |
| **Partial (not adopted)** | 1 | IESExpensesController — header only; details use array iteration |
| **Excluded** | 27 | Attachment, FormRequest, array, JSON, etc. — see table |

**Done:** All 18 controllers with full Phase 1A pattern (`$request->only($fillable)` + `ArrayToScalarNormalizer::forFillable()`) have been migrated to `FormDataExtractor::forFillable($request, $fillable)`.

**Remaining eligible:** None. IESExpensesController has a partial pattern and does not qualify for the standard swap.

**Done (18 controllers):** IESPersonalInfoController, IESEducationBackgroundController, IESImmediateFamilyDetailsController • EducationBackgroundController (IIES) • IAHPersonalInfoController, IAHHealthConditionController, IAHSupportDetailsController • PersonalInfoController, RiskAnalysisController (ILP) • InstitutionInfoController, DevelopmentMonitoringController (IGE) • PresentSituationController, RationaleController (CCI) • InstitutionInfoController, TargetGroupController (RST) • InterventionLogicController (LDP) • ProjectEduRUTBasicInfoController • CICBasicInfoController.

---

## Eligibility Criteria

**Eligible:** Controllers that currently use:
- `$request->only($fillable)` (or equivalent scoped extract)
- `ArrayToScalarNormalizer::forFillable()`

**Excluded:**
- Attachment controllers (IESAttachmentsController, IIESAttachmentsController, IAHDocumentsController, ILP AttachedDocumentsController)
- Report / Monthly / Quarterly controllers
- ExportController
- Controllers that use FormRequest `validated()` or `getNormalizedInput()` without inline ArrayToScalarNormalizer
- Controllers that do not call `fill()` on models from request data
- Controllers that intentionally omit ArrayToScalarNormalizer (e.g. array controllers, JSON-field controllers)

---

## Inventory Table

| Module | Controller | Phase 1A Pattern | Adopted (2.1) | Verified | Notes |
|--------|------------|------------------|---------------|----------|-------|
| IES | IESPersonalInfoController | Yes | ✅ | ✅ | Pilot |
| IES | IESEducationBackgroundController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IES | IESImmediateFamilyDetailsController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IES | IESExpensesController | Partial (header) | ❌ | ❌ | Header uses ArrayToScalarNormalizer; details use array iteration |
| IES | IESAttachmentsController | No | ⬜ | ⬜ | Excluded — attachment |
| IES | IESFamilyWorkingMembersController | No | ⬜ | ⬜ | Excluded — array controller |
| IIES | EducationBackgroundController | Yes | ✅ | ✅ | Pilot |
| IIES | IIESAttachmentsController | No | ⬜ | ⬜ | Excluded — attachment |
| IIES | IIESPersonalInfoController | No | ⬜ | ⬜ | Excluded — FormRequest validated() |
| IIES | IIESFamilyWorkingMembersController | No | ⬜ | ⬜ | Excluded — FormRequest getNormalizedInput() |
| IIES | IIESExpensesController | No | ⬜ | ⬜ | Excluded — FormRequest getNormalizedInput() |
| IIES | IIESImmediateFamilyDetailsController | No | ⬜ | ⬜ | Excluded — FormRequest pattern |
| IIES | FinancialSupportController | No | ⬜ | ⬜ | Excluded — FormRequest getNormalizedInput() |
| IAH | IAHPersonalInfoController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IAH | IAHHealthConditionController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IAH | IAHSupportDetailsController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IAH | IAHDocumentsController | No | ⬜ | ⬜ | Excluded — attachment |
| IAH | IAHEarningMembersController | No | ⬜ | ⬜ | Excluded — array controller |
| IAH | IAHBudgetDetailsController | No | ⬜ | ⬜ | Excluded — array controller |
| ILP | PersonalInfoController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| ILP | RiskAnalysisController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| ILP | AttachedDocumentsController | No | ⬜ | ⬜ | Excluded — attachment |
| ILP | StrengthWeaknessController | No | ⬜ | ⬜ | Excluded — JSON fields, no ArrayToScalarNormalizer |
| ILP | RevenueGoalsController | No | ⬜ | ⬜ | Excluded — $request->only only |
| ILP | BudgetController | No | ⬜ | ⬜ | Excluded — array controller |
| IGE | InstitutionInfoController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IGE | DevelopmentMonitoringController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| IGE | IGEBudgetController | No | ⬜ | ⬜ | Excluded — array controller |
| IGE | NewBeneficiariesController | No | ⬜ | ⬜ | Excluded — array controller |
| IGE | OngoingBeneficiariesController | No | ⬜ | ⬜ | Excluded — array controller |
| IGE | IGEBeneficiariesSupportedController | No | ⬜ | ⬜ | Excluded — array controller |
| CCI | PresentSituationController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| CCI | RationaleController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| CCI | AchievementsController | No | ⬜ | ⬜ | Excluded — JSON fields |
| CCI | AnnexedTargetGroupController | No | ⬜ | ⬜ | Excluded — array controller |
| CCI | AgeProfileController | No | ⬜ | ⬜ | Excluded — FormRequest validated() |
| CCI | EconomicBackgroundController | No | ⬜ | ⬜ | Excluded — FormRequest validated() |
| CCI | PersonalSituationController | No | ⬜ | ⬜ | Excluded — FormRequest validated() |
| CCI | StatisticsController | No | ⬜ | ⬜ | Excluded — FormRequest validated() |
| RST | InstitutionInfoController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| RST | TargetGroupController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| RST | GeographicalAreaController | No | ⬜ | ⬜ | Excluded — $request->only only |
| RST | BeneficiariesAreaController | No | ⬜ | ⬜ | Excluded — $request->only only |
| RST | TargetGroupAnnexureController | No | ⬜ | ⬜ | Excluded — $request->only only |
| LDP | InterventionLogicController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| LDP | TargetGroupController | No | ⬜ | ⬜ | Excluded — $request->only only |
| LDP | NeedAnalysisController | No | ⬜ | ⬜ | Excluded — file upload only |
| EduRUT | ProjectEduRUTBasicInfoController | Yes | ✅ | ❌ | Straight swap; identical behavior |
| EduRUT | EduRUTTargetGroupController | No | ⬜ | ⬜ | Excluded — $request->only only |
| EduRUT | EduRUTAnnexedTargetGroupController | No | ⬜ | ⬜ | Excluded — array controller |
| CIC | CICBasicInfoController | Yes | ✅ | ❌ | Straight swap; identical behavior |

---

## How to Update This Tracker

- **One controller per change.** Do not bulk-update.
- **Adopted** can be set to ✅ only after the code change (replacement of inline pattern with `FormDataExtractor::forFillable`) is committed.
- **Verified** can be set to ✅ only after local verification (create/edit flows exercised, no new log errors).
- Do not mark Verified before Adopted.
- When in doubt about eligibility, list the controller with a note rather than guessing.

---

## Explicit Non-Scope

- This tracker does **not** authorize Phase 2.2 (ProjectAttachmentHandler) or any other Phase 2 component.
- Completion of adoption across all eligible controllers does **not** imply Phase 2 sign-off.
- Deployment remains forbidden until `PHASE_2_SIGNOFF.md` is approved and all phase gates are met.
