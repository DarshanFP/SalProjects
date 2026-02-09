# Validation & Normalization Layer Design – Batch 2

*Companion to Validation_Normalization_Design.md. Extends model-level examples and covers secondary flows.*

---

## Model-Level Examples – Batch 2 (Conceptual)

*These examples illustrate the design. Do not implement.*

### IAH (Individual Aftercare Home) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectIAHPersonalInfo | IAHPersonalInfoController | `$request->all()`; `?? null` per field | All nullable strings/integers | FormRequest Store/UpdateIAHPersonalInfoRequest; normalize placeholders for age, children (integer); trim strings |
| ProjectIAHHealthCondition | IAHHealthConditionController | `$request->all()` | Likely nullable | Same pattern |
| ProjectIAHSupportDetails | IAHSupportDetailsController | `$request->all()` | | Same |
| ProjectIAHEarningMembers | IAHEarningMembersController | `$request->all()` | | Same |
| ProjectIAHBudgetDetails | IAHBudgetDetailsController | `$request->all()`; `?? 0` for amounts | Decimal columns | Normalize empty/placeholder → 0; add max for decimals |
| ProjectIAHDocuments | IAHDocumentsController | `$request->all()` | File paths nullable | Validate file inputs; normalize single vs array |

**Risk:** IAH budget/amount fields may have same NOT NULL + empty-string issue as IIES if any column is NOT NULL with default.

---

### IGE (Institutional Group Educational) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectIGEInstitutionInfo | InstitutionInfoController | `$request->all()` | | FormRequest; trim; placeholder → null for numeric |
| ProjectIGEBudget | IGEBudgetController | `$request->all()` | Decimal | Normalize → 0; max bounds |
| ProjectIGENewBeneficiaries | NewBeneficiariesController | `$request->all()` | | Same |
| ProjectIGEOngoingBeneficiaries | OngoingBeneficiariesController | `$request->all()` | | Same |
| ProjectIGEBeneficiariesSupported | IGEBeneficiariesSupportedController | `$request->all()` | | Same |
| ProjectIGEDevelopmentMonitoring | DevelopmentMonitoringController | `$request->all()` | | Same |

**Production note:** IGE PDF export had undefined `$IGEbudget` – view-controller contract gap, not validation. Ensure ExportController passes same variables as show.

---

### ILP (Individual Livelihood Project) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectILPPersonalInfo | PersonalInfoController | `$request->all()`; `(int) ?? 0` for small_business_status | Boolean/tinyint | Normalize boolean-like; FormRequest |
| ProjectILPRevenueGoals | RevenueGoalsController | `$request->all()`; `?? null` for amounts | Decimal nullable | Placeholder → null; max for decimals |
| ProjectILPStrengthWeakness | StrengthWeaknessController | `$request->all()` | | FormRequest; trim |
| ProjectILPRiskAnalysis | RiskAnalysisController | `$request->all()` | | Same |
| ProjectILPBudget | ILP BudgetController | `$request->all()`; `?? null` for beneficiary_contribution, amount_requested | | Normalize; max bounds |
| ProjectILPAttachedDocuments | AttachedDocumentsController | `$request->all()` | | File handling; single vs array |

**Note:** ILP PersonalInfoController uses `(int) ($validated['small_business_status'] ?? 0)` – correct for boolean coercion but still bypasses FormRequest for other fields.

---

### IES (Individual Educational Support) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectIESPersonalInfo | IESPersonalInfoController | `$request->all()` | | FormRequest; trim |
| ProjectIESEducationBackground | IESEducationBackgroundController | `$request->all()` | | Same |
| ProjectIESExpenses | IESExpensesController | `$request->all()`; `?? null` for amounts | Check migration for NOT NULL | If NOT NULL, normalize → 0; else → null |
| ProjectIESFamilyWorkingMembers | IESFamilyWorkingMembersController | `$request->all()`; `fill($request->all())` in ImmediateFamilyDetails | | FormRequest; avoid raw fill with all() |
| ProjectIESAttachments | IESAttachmentsController | Model expects single file; Blade uses `[]` | | **Contract alignment:** normalize file to single or iterate array |

**Production issue:** `getClientOriginalExtension()` on array – file input must be normalized before handler.

---

### RST (Residential Skill Training) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectRSTInstitutionInfo | InstitutionInfoController | `$request->all()` | | FormRequest; placeholder → null for numeric |
| ProjectRSTGeographicalArea | GeographicalAreaController | `$request->all()` | | Same |
| ProjectRSTTargetGroup | TargetGroupController | `$request->all()` | | Same |
| ProjectRSTTargetGroupAnnexure | TargetGroupAnnexureController | `$request->all()` | | Same |
| ProjectRSTBeneficiariesArea | BeneficiariesAreaController | `$request->all()` | | Same |

---

### CCI (Children in Crisis Intervention) – Extended

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectCCIPersonalSituation | PersonalSituationController | `$request->all()`; `?? null` | All integer nullable | **Placeholder → null** (same as Statistics) |
| ProjectCCIAgeProfile | AgeProfileController | `$request->all()` | Integer nullable | Same |
| ProjectCCIEconomicBackground | EconomicBackgroundController | `$request->all()` | | Same |
| ProjectCCIPresentSituation | PresentSituationController | `$request->all()` | | Same |
| ProjectCCIRationale | RationaleController | `$request->all()` | | FormRequest; trim text |
| ProjectCCIAchievements | AchievementsController | `$request->all()` | | Same |
| ProjectCCIAnnexedTargetGroup | AnnexedTargetGroupController | `$request->all()` | | Same |

**Shared rule:** All CCI integer columns nullable; normalize `-`, `N/A`, empty string → `null`.

---

### EduRUT Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectEduRUTBasicInfo | ProjectEduRUTBasicInfoController | `$request->all()` | | FormRequest; trim |
| ProjectEduRUTTargetGroup | EduRUTTargetGroupController | `$request->all()` | | Same |
| ProjectEduRUTAnnexedTargetGroup | EduRUTAnnexedTargetGroupController | `$request->all()` | | Same |

---

### CIC (Crisis Intervention Center) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectCICBasicInfo | CICBasicInfoController | `$request->all()` | | FormRequest; trim; placeholder handling |

---

### LDP (Logical Framework / Development Project) Project Type

| Model | Controller | Current Pattern | DB Constraints | Proposed |
|-------|------------|-----------------|----------------|----------|
| ProjectLDPInterventionLogic | InterventionLogicController | `$request->all()` | | FormRequest |
| ProjectLDPNeedAnalysis | NeedAnalysisController | `$request->all()` | | Same |
| ProjectLDPTargetGroup | TargetGroupController | `$request->all()` | | Same |

---

## Secondary Flows

### PDF Export (ExportController)

| Concern | Current | Proposed |
|---------|---------|----------|
| **Variable passing** | `pdf.blade.php` includes partials without passing `$IGEbudget`; show passes it | ExportController `loadAllProjectData` must pass same variables as show. Define a contract: list of variables required by each project-type partial. |
| **Null safety in views** | Partials use `$var ?? 'N/A'` for display | Ensure all partials use `@isset` or `??` for variables that may be missing. |
| **Validation** | Export is read-only; no input validation | No validation layer needed. Normalization only if export receives filter params (e.g. date range). |

**Design rule:** PDF export must use the same data-loading contract as the show view. Add a shared method or service that returns the canonical dataset for a project type.

---

### Bulk Report Actions

| Flow | Controller | Current | Proposed |
|------|------------|---------|----------|
| bulkForwardReports | ProvincialController | Validates report IDs; forwards each | Validate `report_ids` array: required, array, each exists; normalize empty elements. |
| bulkReportAction | CoordinatorController | bulk_approve, bulk_revert | Validate `report_ids`, `action`, `revert_reason` when action=revert. |
| bulkActionReports | GeneralController | approve_as_coordinator, approve_as_provincial, export | Same pattern; validate IDs and action. |

**Normalization:** Filter out null/empty from `report_ids` before processing; reject if resulting array empty.

---

### Report Attachments (ReportAttachmentController)

| Concern | Current | Proposed |
|---------|---------|----------|
| Validation | `Validator::make($request->all(), [...])` | Move to FormRequest StoreReportAttachmentRequest / UpdateReportAttachmentRequest. |
| File type | file, mimes:pdf,doc,docx,xls,xlsx, max:2048 | Keep; ensure single vs array handled if view uses `[]`. |

---

### Activity History Filters (ActivityHistoryController)

| Concern | Current | Proposed |
|---------|---------|----------|
| Input | `$request->all()` passed to `getWithFilters` | Service should validate/sanitize filter keys (date_from, date_to, user_id, etc.). Reject unknown keys. |
| Normalization | Unknown | Trim date strings; normalize empty to null for optional filters. |

---

## Implementation Priority Matrix

| Priority | Area | Rationale | Effort |
|----------|------|------------|--------|
| P0 | IIES Expenses, IIES Financial Support | Production NOT NULL violations; blocks users | Low |
| P0 | Budget (project_budgets) | Numeric overflow; data corruption | Low |
| P0 | CCI Statistics, CCI PersonalSituation, CCI AgeProfile, etc. | Placeholder `-` causes SQL errors | Medium |
| P1 | IES Attachments | File array vs single – complete failure | Low |
| P1 | Logical Framework | Undefined key `activity` | Low |
| P1 | StoreBudgetRequest max bounds | Prevent overflow | Low |
| P2 | IAH, IGE, ILP budget/amount fields | Same NOT NULL risk as IIES | Medium |
| P2 | All `$request->all()` → FormRequest | Consistency; prevent future gaps | High |
| P2 | PDF Export variable contract | IGE and similar export failures | Medium |
| P3 | Bulk report actions | Validate IDs and action | Low |
| P3 | Report attachments FormRequest | Consistency | Low |
| P3 | Activity history filter sanitization | Security; reject unknown params | Low |
| P4 | Phase 2–4 normalization (trim, placeholder expansion, structural) | After P0–P1 data safety | Medium |

---

## Controller Inventory – Batch 2

*Controllers using `$request->all()` without type-specific FormRequest validation:*

| Controller | Store | Update | FormRequest Exists? | Uses It? |
|------------|-------|--------|---------------------|----------|
| IAHPersonalInfoController | ✓ | ✓ | Store/UpdateIAHPersonalInfoRequest | No (type-hints FormRequest) |
| IAHHealthConditionController | ✓ | ✓ | Yes | No |
| IAHSupportDetailsController | ✓ | ✓ | Yes | No |
| IAHEarningMembersController | ✓ | ✓ | Yes | No |
| IAHBudgetDetailsController | ✓ | ✓ | Yes | No |
| IAHDocumentsController | ✓ | ✓ | Yes | No |
| IGEInstitutionInfoController | ✓ | ✓ | Yes | No |
| IGEBudgetController | ✓ | ✓ | Yes | No |
| NewBeneficiariesController | ✓ | ✓ | Yes | No |
| OngoingBeneficiariesController | ✓ | ✓ | Yes | No |
| IGEBeneficiariesSupportedController | ✓ | ✓ | Yes | No |
| DevelopmentMonitoringController | ✓ | ✓ | Yes | No |
| ILPPersonalInfoController | ✓ | ✓ | Yes | No |
| ILPRevenueGoalsController | ✓ | ✓ | Yes | No |
| ILPStrengthWeaknessController | ✓ | ✓ | Yes | No |
| ILPRiskAnalysisController | ✓ | ✓ | Yes | No |
| ILPBudgetController | ✓ | ✓ | Yes | No |
| ILPAttachedDocumentsController | ✓ | ✓ | Yes | No |
| IESPersonalInfoController | ✓ | ✓ | Yes | No |
| IESEducationBackgroundController | ✓ | ✓ | Yes | No |
| IESExpensesController | ✓ | ✓ | Yes | No |
| IESFamilyWorkingMembersController | ✓ | ✓ | Yes | No |
| IESAttachmentsController | ✓ | ✓ | Yes | No |
| RSTInstitutionInfoController | ✓ | ✓ | Yes | No |
| RSTGeographicalAreaController | ✓ | ✓ | Yes | No |
| RSTTargetGroupController | ✓ | ✓ | Yes | No |
| RSTTargetGroupAnnexureController | ✓ | ✓ | Yes | No |
| RSTBeneficiariesAreaController | ✓ | ✓ | Yes | No |
| EduRUTTargetGroupController | ✓ | ✓ | Yes | No |
| EduRUTAnnexedTargetGroupController | ✓ | ✓ | Yes | No |
| ProjectEduRUTBasicInfoController | ✓ | ✓ | Yes | No |
| CICBasicInfoController | - | ✓ | Yes | No |
| LDPInterventionLogicController | ✓ | ✓ | Yes | No |
| LDPNeedAnalysisController | ✓ | ✓ | Yes | No |
| LDPTargetGroupController | ✓ | ✓ | Yes | No |

**Pattern:** FormRequest classes exist for most of these, but controllers type-hint generic `FormRequest` and call `$request->all()` instead of `$request->validated()`. The parent ProjectController passes StoreProjectRequest/UpdateProjectRequest, which does not contain type-specific rules. Routes would need to invoke these sub-controllers with the correct FormRequest for each operation.

---

## DO NOT

- Implement code
- Refactor controllers
- Add validation rules
- Modify database schema

This document extends the architectural design for planning only.

---

*Document generated: January 31, 2026*  
*Companion to Validation_Normalization_Design.md*
