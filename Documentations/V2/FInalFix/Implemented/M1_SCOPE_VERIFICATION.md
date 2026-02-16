# M1 — Data Integrity Shield (Skip-Empty-Sections) — Scope Verification

**Date:** 2026-02-14  
**Milestone:** 1 — Data Integrity Shield  
**Objective:** Verified scope list of section controllers that require a skip-empty guard. No code was modified.

---

## 1. Scope Definition

**In scope:** Controllers under `app/Http/Controllers/Projects/` that in **update() or store()**:

- Perform **bulk delete** using `Model::where('project_id', ...)->delete()` (or equivalent delete scoped by `project_id`), and  
- Then **recreate** rows from request input.

**Excluded (per instructions):**

- ProjectController  
- GeneralInfoController  
- SustainabilityController (does not bulk-delete in update/store; uses update-or-create pattern)  
- AttachmentController destroy-only logic  
- Resolver-related and societies-related code  

---

## 2. Controllers Requiring Skip-Empty Guard

Total: **20** section controllers.

| # | File path | Section type | Row type | Empty input check before delete | Delete runs unconditionally |
|---|-----------|--------------|----------|----------------------------------|------------------------------|
| 1 | `app/Http/Controllers/Projects/BudgetController.php` | Budget | Multi-row | No | Yes |
| 2 | `app/Http/Controllers/Projects/LogicalFrameworkController.php` | LogicalFramework | Nested | **update():** No. **store():** Yes (returns early) | **update():** Yes. **store():** N/A (skips) |
| 3 | `app/Http/Controllers/Projects/IIES/IIESFamilyWorkingMembersController.php` | IIES | Multi-row | No | Yes |
| 4 | `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` | IIES | Nested | No | Yes (delete only if existing; then always create) |
| 5 | `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php` | ILP | Single-row | No | Yes |
| 6 | `app/Http/Controllers/Projects/IAH/IAHSupportDetailsController.php` | IAH | Single-row | No | Yes |
| 7 | `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php` | IAH | Single-row | No | Yes |
| 8 | `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php` | IAH | Single-row | No | Yes |
| 9 | `app/Http/Controllers/Projects/EduRUTTargetGroupController.php` | Edu-RUT | Multi-row | No | Yes |
| 10 | `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php` | Edu-RUT | Multi-row | No | Yes |
| 11 | `app/Http/Controllers/Projects/LDP/TargetGroupController.php` | LDP | Multi-row | No | Yes |
| 12 | `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php` | RST | Multi-row | No | Yes |
| 13 | `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php` | RST | Multi-row | No | Yes |
| 14 | `app/Http/Controllers/Projects/RST/GeographicalAreaController.php` | RST | Multi-row | No | Yes |
| 15 | `app/Http/Controllers/Projects/IGE/IGEBeneficiariesSupportedController.php` | IGE | Multi-row | No | Yes |
| 16 | `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php` | IGE | Multi-row | No | Yes |
| 17 | `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php` | IGE | Multi-row | No | Yes |
| 18 | `app/Http/Controllers/Projects/IGE/IGEBudgetController.php` | IGE | Multi-row | No | Yes |
| 19 | `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php` | IES | Multi-row | No | Yes |
| 20 | `app/Http/Controllers/Projects/IES/IESExpensesController.php` | IES | Nested | No | Yes (delete if existing; then create) |

---

## 3. Row Type Summary

- **Multi-row:** 14 controllers (Budget, IIES FamilyWorkingMembers, Edu-RUT TargetGroup/Annexed, LDP TargetGroup, RST BeneficiariesArea/TargetGroupAnnexure/GeographicalArea, IGE BeneficiariesSupported/Ongoing/New/Budget, IES FamilyWorkingMembers).  
- **Single-row:** 4 controllers (ILP RiskAnalysis, IAH SupportDetails/HealthCondition/PersonalInfo).  
- **Nested:** 3 controllers (LogicalFramework: objectives → results, risks, activities → timeframes; IIESExpensesController: parent + expenseDetails; IESExpensesController: parent + expenseDetails).  

---

## 4. Controllers Already Safe (No Guard Needed for Update/Store)

These section controllers do **not** use bulk delete-then-recreate in `update()` or `store()`. They use `updateOrCreate()`, single-record update, or first/new + save. **Destroy()** may still perform bulk delete; that is intentional delete, not “empty request overwrite.”

| File path | Section type | Reason |
|-----------|--------------|--------|
| `app/Http/Controllers/Projects/IIES/FinancialSupportController.php` | IIES | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php` | IIES | first or new + fill + save in store; update delegates to store |
| `app/Http/Controllers/Projects/IIES/IIESImmediateFamilyDetailsController.php` | IIES | Single-record firstOrFail then delete in destroy only |
| `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` | IIES | Single-record delete in destroy only |
| `app/Http/Controllers/Projects/LDP/InterventionLogicController.php` | LDP | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/RST/TargetGroupController.php` | RST | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/RST/InstitutionInfoController.php` | RST | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/CCI/RationaleController.php` | CCI | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/CCI/PresentSituationController.php` | CCI | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/IGE/DevelopmentMonitoringController.php` | IGE | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/IGE/InstitutionInfoController.php` | IGE | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/ILP/PersonalInfoController.php` | ILP | updateOrCreate in store/update |
| `app/Http/Controllers/Projects/CICBasicInfoController.php` | CIC | Single-record firstOrFail then delete in destroy only |
| `app/Http/Controllers/Projects/ProjectEduRUTBasicInfoController.php` | Edu-RUT | Single-record firstOrFail then delete in destroy only |

---

## 5. Controllers Needing Custom Guard Logic (Nested Sections)

These need a **skip-empty** rule that accounts for **nested** structure (parent + children). Empty should be defined so that skipping preserves existing data when the **section payload** is absent or empty.

| Controller | Section type | Nested structure | Guard note |
|------------|--------------|------------------|------------|
| `LogicalFrameworkController` | LogicalFramework | Objectives → results, risks, activities → timeframes | Skip update when `objectives` is absent or empty; per-objective empty checks may be needed for partial payloads. |
| `IIESExpensesController` | IIES | Parent (ProjectIIESExpenses) + children (expenseDetails) | Skip when section payload is empty so existing expenses are not replaced by an empty parent + no details. |
| `IESExpensesController` | IES | Parent + expense details (nested) | Same as IIES: skip when section payload is empty to avoid wiping existing data. |

---

## 6. Risk Notes

1. **LogicalFrameworkController**  
   - **store()** already checks for empty/valid objectives and returns without deleting.  
   - **update()** has **no** empty check: it runs `ProjectObjective::where('project_id', $project_id)->delete()` then loops over `$objectives`. If the full-project edit form omits or sends empty `objectives`, all logical framework data can be deleted.

2. **BudgetController**  
   - Delete is unconditional; then creation uses `$phases[0]['budget']` only. Empty or missing phases/budget arrays result in delete with no rows recreated.

3. **Multi-row sections (RST, IGE, LDP, Edu-RUT, IIES/IES family working members)**  
   - All run unconditional bulk delete then recreate from request arrays. Empty or omitted arrays cause full section wipe with no replacement rows.

4. **IAH single-row sections (SupportDetails, HealthCondition, PersonalInfo)**  
   - Delete-then-create in store (and update delegates to store). Empty or minimal request can replace existing row with empty or default data.

5. **Nested expense controllers (IIES, IES)**  
   - Delete existing parent (and children) then create new parent + children. Empty section payload can leave project with no expense record or an empty one; guard must treat “no/empty section data” as skip.

6. **Consistency of “empty” definition**  
   - Guard implementation should define “empty” consistently per section (e.g. missing key, empty array, or all sent values blank) and document it so future sections align.

---

## 7. Summary Counts

| Category | Count |
|----------|--------|
| **Total controllers needing guard** | 20 |
| **Multi-row sections** | 14 |
| **Single-row sections** | 4 |
| **Nested sections (custom guard logic)** | 3 |
| **Controllers already safe (no guard in update/store)** | 14 |

---

*End of M1 Scope Verification. No code was modified.*
