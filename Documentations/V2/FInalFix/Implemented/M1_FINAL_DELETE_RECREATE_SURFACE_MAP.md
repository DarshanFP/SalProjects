# M1 — Final Delete-Recreate / Attachment Surface Map (Forensic Scan)

**Scan date:** 2026-02-14  
**Scope:** `app/Http/Controllers/Projects/` only (read-only analysis).  
**Confirmation:** No code was modified; this is a forensic documentation only.

---

## 1. Scan scope and method

- **Scope:** All PHP controllers under `app/Http/Controllers/Projects/` (61 files).
- **Patterns searched:**
  1. `where('project_id', …)->delete()`
  2. `->delete()` (including relation deletes)
  3. `first()` followed by `->delete()`
  4. `updateOrCreate(`
  5. `expenseDetails()->delete()` / attachment relation deletes
  6. `DB::transaction(` containing delete
  7. `destroy()` methods that delete without payload condition
  8. File upload logic that could create parent rows when no file exists

- **Per-match capture:** Controller, method, pattern type, M1 guard presence, section-key validation, empty-input-wipe risk, risk rating (HIGH / MEDIUM / LOW).

---

## 2. Classification summary

| Classification | Description | Count |
|----------------|-------------|-------|
| **A) UNPROTECTED DELETE-RECREATE** | store/update does delete-by-project_id then recreate; no M1 “meaningfully filled” guard; empty/absent section can wipe data | 30 |
| **B) ATTACHMENT WITHOUT FILE CHECK** | Controllers that handle attachments; all four scanned have file-presence check (store/update skip when no files) | 0 |
| **C) UPDATEORCREATE WITHOUT EMPTY CHECK** | Single-row or loop updateOrCreate without checking section payload; empty request can overwrite with empty | 8 |
| **D) SAFE (M1 protected)** | Delete-recreate or attachment flow guarded by M1 (section/file check before mutation) | 12 |
| **E) NON-RISK (intentional destroy only / no store wipe)** | destroy() only, full project delete, or no delete-recreate in store/update | 11 |

---

## 3. Table of all controllers and classification

| # | Controller | Classification | Pattern(s) | M1 guard | Section validated | Empty → delete? | Risk |
|---|------------|----------------|------------|----------|-------------------|------------------|------|
| 1 | ILP/AttachedDocumentsController | D) SAFE | delete in destroy; store/update use hasAnyILPFile | Yes | N/A (file check) | No | LOW |
| 2 | IAH/IAHDocumentsController | D) SAFE | delete in destroy; store/update use hasAnyIAHFile | Yes | N/A (file check) | No | LOW |
| 3 | IIES/IIESAttachmentsController | D) SAFE | delete in destroy; store/update file check | Yes | N/A (file check) | No | LOW |
| 4 | IES/IESAttachmentsController | D) SAFE | delete in destroy; store/update file check | Yes | N/A (file check) | No | LOW |
| 5 | LDP/TargetGroupController | D) SAFE | where(project_id)->delete in store; isLDPTargetGroupMeaningfullyFilled | Yes | Yes | No | LOW |
| 6 | RST/GeographicalAreaController | D) SAFE | where(project_id)->delete in store; isGeographicalAreaMeaningfullyFilled | Yes | Yes | No | LOW |
| 7 | RST/TargetGroupAnnexureController | D) SAFE | where(project_id)->delete in store; isTargetGroupAnnexureMeaningfullyFilled | Yes | Yes | No | LOW |
| 8 | IES/IESExpensesController | D) SAFE | expenseDetails()->delete + delete in store; isIESExpensesMeaningfullyFilled | Yes | Yes | No | LOW |
| 9 | IIES/IIESExpensesController | D) SAFE | expenseDetails()->delete + delete in store; isIIESExpensesMeaningfullyFilled | Yes | Yes | No | LOW |
| 10 | LogicalFrameworkController | D) SAFE | where(project_id)->delete in update; isLogicalFrameworkMeaningfullyFilled | Yes | Yes | No | LOW |
| 11 | RST/BeneficiariesAreaController | D) SAFE | where(project_id)->delete in store; isBeneficiariesAreaMeaningfullyFilled | Yes | Yes | No | LOW |
| 12 | BudgetController | D) SAFE | where(project_id)->delete in update; isBudgetSectionMeaningfullyFilled | Yes | Yes | No | LOW |
| 13 | ProjectController | E) NON-RISK | project->delete() in destroy only | N/A | N/A | No | LOW |
| 14 | GeneralInfoController | E) NON-RISK | project->delete() in destroy only | N/A | N/A | No | LOW |
| 15 | SustainabilityController | E) NON-RISK | $sustainability->delete() in destroy only | N/A | N/A | No | LOW |
| 16 | IIES/IIESFamilyWorkingMembersController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 17 | IIES/EducationBackgroundController | A) UNPROTECTED | where(project_id)->delete in store | No | No | Yes | HIGH |
| 18 | RST/TargetGroupController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 19 | RST/InstitutionInfoController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 20 | CCI/RationaleController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 21 | CCI/PresentSituationController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 22 | IGE/DevelopmentMonitoringController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 23 | IGE/InstitutionInfoController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 24 | ILP/PersonalInfoController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 25 | ILP/RiskAnalysisController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 26 | IAH/IAHSupportDetailsController | A) UNPROTECTED | where(project_id)->delete in store | No | No | Yes | HIGH |
| 27 | IAH/IAHHealthConditionController | A) UNPROTECTED | where(project_id)->delete in store | No | No | Yes | HIGH |
| 28 | IAH/IAHPersonalInfoController | A) UNPROTECTED | where(project_id)->delete in store | No | No | Yes | HIGH |
| 29 | IES/IESEducationBackgroundController | A) UNPROTECTED | where(project_id)->delete in store | No | No | Yes | HIGH |
| 30 | EduRUTTargetGroupController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 31 | EduRUTAnnexedTargetGroupController | A) UNPROTECTED | where(project_id)->delete in store | No | No | Yes | HIGH |
| 32 | CCI/AnnexedTargetGroupController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate in loop | No | No | Yes | HIGH |
| 33 | IGE/IGEBeneficiariesSupportedController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 34 | IGE/OngoingBeneficiariesController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 35 | IGE/NewBeneficiariesController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 36 | IGE/IGEBudgetController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 37 | ILP/RevenueGoalsController | A) UNPROTECTED | where(project_id)->delete (3 tables) in store & update | No | No | Yes | HIGH |
| 38 | ILP/StrengthWeaknessController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 39 | ILP/BudgetController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 40 | IAH/IAHEarningMembersController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 41 | IAH/IAHBudgetDetailsController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 42 | IES/IESFamilyWorkingMembersController | A) UNPROTECTED | where(project_id)->delete in store & update | No | No | Yes | HIGH |
| 43 | CCI/StatisticsController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 44 | CCI/EconomicBackgroundController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 45 | CCI/PersonalSituationController | A) UNPROTECTED | where(project_id)->delete in store; updateOrCreate | No | No | Yes | HIGH |
| 46 | IIES/FinancialSupportController | C) UPDATEORCREATE | updateOrCreate in store/update; delete only in destroy | No | No | Overwrite empty | MEDIUM |
| 47 | LDP/InterventionLogicController | C) UPDATEORCREATE | updateOrCreate in store; delete only in destroy | No | No | Overwrite empty | MEDIUM |
| 48 | CICBasicInfoController | C) UPDATEORCREATE | updateOrCreate in store/update | No | No | Overwrite empty | MEDIUM |
| 49 | ProjectEduRUTBasicInfoController | C) UPDATEORCREATE | updateOrCreate in store/update | No | No | Overwrite empty | MEDIUM |
| 50 | LDP/NeedAnalysisController | C) UPDATEORCREATE | updateOrCreate in store/update | No | No | Overwrite empty | MEDIUM |
| 51 | CCI/AchievementsController | C) UPDATEORCREATE | updateOrCreate in store/update | No | No | Overwrite empty | MEDIUM |
| 52 | CCI/AgeProfileController | C) UPDATEORCREATE | updateOrCreate in update(); store is create only | No | No | Overwrite empty (update) | MEDIUM |
| 53 | IIES/IIESPersonalInfoController | C) UPDATEORCREATE | updateOrCreate; single-record delete in destroy | No | No | Overwrite empty | MEDIUM |
| 54 | IIES/IIESImmediateFamilyDetailsController | E) NON-RISK | Single-record delete in destroy only; store does not delete-by-project_id | N/A | — | No | LOW |
| 55 | ExportController | E) NON-RISK | No delete-recreate in store/update; where(project_id) for read only | N/A | — | No | LOW |
| 56 | KeyInformationController | E) NON-RISK | No delete/updateOrCreate pattern in scope | N/A | — | No | LOW |
| 57 | BudgetExportController | E) NON-RISK | No delete-recreate in store/update | N/A | — | No | LOW |
| 58 | AttachmentController | E) NON-RISK | where(project_id) for read; no section wipe | N/A | — | No | LOW |
| 59 | OldDevelopmentProjectController | E) NON-RISK | No delete-by-project_id in store; different flow | N/A | — | No | LOW |
| 60 | IES/IESImmediateFamilyDetailsController | E) NON-RISK | Single-record delete in destroy only | N/A | — | No | LOW |
| 61 | IES/IESPersonalInfoController | E) NON-RISK | Single-record delete in destroy only | N/A | — | No | LOW |

*All 61 controllers under `app/Http/Controllers/Projects/` are listed.*

---

## 4. Unique controllers summary (deduplicated)

- **Total controllers scanned:** 61  
- **A) UNPROTECTED DELETE-RECREATE (needs guard):** 30 controllers  
- **B) ATTACHMENT WITHOUT FILE CHECK:** 0 (all four attachment controllers have file check)  
- **C) UPDATEORCREATE WITHOUT EMPTY CHECK:** 8 controllers  
- **D) SAFE (M1 protected):** 12 controllers  
- **E) NON-RISK (intentional destroy only or no store/update wipe):** 11 controllers (ProjectController, GeneralInfoController, SustainabilityController, ExportController, KeyInformationController, BudgetExportController, AttachmentController, OldDevelopmentProjectController, IIES/IIESImmediateFamilyDetailsController, IES/IESImmediateFamilyDetailsController, IES/IESPersonalInfoController).

---

## 5. High-risk (A) controllers – detailed notes

- **IIES/IIESFamilyWorkingMembersController** — store() and update() both run `ProjectIIESFamilyWorkingMembers::where('project_id', $projectId)->delete()` then loop-create. No check for meaningful payload; empty arrays cause full wipe, no recreate.
- **IIES/EducationBackgroundController** — store() deletes by project_id then creates from request; no section guard.
- **RST/TargetGroupController** — store() deletes `ProjectRSTTargetGroup::where('project_id', $projectId)` then updateOrCreate; empty payload can wipe then create/overwrite empty.
- **RST/InstitutionInfoController** — same pattern as TargetGroupController.
- **CCI/RationaleController, CCI/PresentSituationController** — delete by project_id then updateOrCreate; no section check.
- **IGE/DevelopmentMonitoringController, IGE/InstitutionInfoController** — same pattern.
- **ILP/PersonalInfoController** — delete by project_id then updateOrCreate.
- **ILP/RiskAnalysisController** — delete in store and update; no guard.
- **IAH/IAHSupportDetailsController, IAH/IAHHealthConditionController, IAH/IAHPersonalInfoController** — delete by project_id in store; no guard.
- **IES/IESEducationBackgroundController** — delete by project_id in store; no guard.
- **EduRUTTargetGroupController** — delete in store and update; no guard.
- **EduRUTAnnexedTargetGroupController** — delete by project_id in store; no guard.
- **CCI/AnnexedTargetGroupController** — delete by project_id then updateOrCreate in loop; no guard.
- **IGE/IGEBeneficiariesSupportedController, IGE/OngoingBeneficiariesController, IGE/NewBeneficiariesController, IGE/IGEBudgetController** — delete in store (and update where applicable); no guard.
- **ILP/RevenueGoalsController** — deletes three tables by project_id in store and update; no guard.
- **ILP/StrengthWeaknessController, ILP/BudgetController** — delete in store and update; no guard.
- **IAH/IAHEarningMembersController, IAH/IAHBudgetDetailsController** — delete in store and update; no guard.
- **IES/IESFamilyWorkingMembersController** — delete in store and update (update delegates to store); no guard.
- **CCI/StatisticsController, CCI/EconomicBackgroundController, CCI/PersonalSituationController** — delete by project_id in store then updateOrCreate; no guard.

---

## 6. Recommended implementation order (Wave continuation)

1. **Wave 2 (high-impact, same pattern as IES/IIES Expenses):**  
   - IES/IESFamilyWorkingMembersController  
   - IIES/IIESFamilyWorkingMembersController  
   - IIES/EducationBackgroundController  
   - IES/IESEducationBackgroundController  

2. **Wave 3 (RST / CCI / IGE single-section):**  
   - RST/TargetGroupController  
   - RST/InstitutionInfoController  
   - CCI/RationaleController  
   - CCI/PresentSituationController  
   - CCI/StatisticsController  
   - CCI/EconomicBackgroundController  
   - CCI/PersonalSituationController  
   - CCI/AnnexedTargetGroupController  
   - IGE/DevelopmentMonitoringController  
   - IGE/InstitutionInfoController  
   - IGE/IGEBeneficiariesSupportedController  
   - IGE/OngoingBeneficiariesController  
   - IGE/NewBeneficiariesController  
   - IGE/IGEBudgetController  

3. **Wave 4 (IAH / ILP):**  
   - IAH/IAHSupportDetailsController  
   - IAH/IAHHealthConditionController  
   - IAH/IAHPersonalInfoController  
   - IAH/IAHEarningMembersController  
   - IAH/IAHBudgetDetailsController  
   - ILP/PersonalInfoController  
   - ILP/RiskAnalysisController  
   - ILP/RevenueGoalsController  
   - ILP/StrengthWeaknessController  
   - ILP/BudgetController  

4. **Wave 5 (EduRUT / remaining):**  
   - EduRUTTargetGroupController  
   - EduRUTAnnexedTargetGroupController  

5. **Category C (updateOrCreate empty-check):**  
   - IIES/FinancialSupportController  
   - LDP/InterventionLogicController  
   - CICBasicInfoController  
   - ProjectEduRUTBasicInfoController  
   - LDP/NeedAnalysisController  
   - CCI/AchievementsController  
   - CCI/AgeProfileController  
   - IIES/IIESPersonalInfoController (if store/update can receive empty section)

---

## 7. Final surface map counts

| Metric | Value |
|--------|--------|
| Total controllers scanned | 61 |
| Total high-risk (A) | 30 |
| Total medium-risk (C) | 8 |
| Total safe (D) | 12 |
| Total non-risk (E) | 11 |
| Attachment controllers without file check (B) | 0 |
| Remaining M1 controllers to implement (A + C) | 38 |
| Controllers already protected (D) | 12 |

---

## 8. Confirmation

- **No code was modified.**  
- This document is a **forensic scan and classification only** within `app/Http/Controllers/Projects/`.  
- Recommended next controller for M1 guard (Wave 2): **IES/IESFamilyWorkingMembersController** or **IIES/IIESFamilyWorkingMembersController** (same pattern as IESExpensesController/IIESExpensesController, highest consistency with existing M1 work).
