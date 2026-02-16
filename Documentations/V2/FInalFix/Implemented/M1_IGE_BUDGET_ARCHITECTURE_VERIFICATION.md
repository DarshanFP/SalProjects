# M1 — IGE Budget Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Controller path:** `app/Http/Controllers/Projects/IGE/IGEBudgetController.php`  
**Mode:** READ-ONLY forensic analysis. No code modified.

---

## PHASE 1 — STRUCTURAL CLASSIFICATION

### 1.1 Section Type

**Multi-row (array-based rows).** One table (`ProjectIGEBudget`); multiple rows per project. No single parent row; no nested parent + child tables. Loop over a single array (`$names`) drives row count; parallel arrays supply the rest of the columns.

### 1.2 Primary Section Keys, Validation, update()

| Item | Detail |
|------|--------|
| **Primary section key** | `name` — drives the loop: `foreach ($names as $i => $name)`. |
| **Parallel arrays (request keys)** | `study_proposed`, `college_fees`, `hostel_fees`, `total_amount`, `scholarship_eligibility`, `family_contribution`, `amount_requested`. All normalized to arrays; row index `$i` used for each. |
| **Parent fields** | None. Flat multi-row only. |
| **Child arrays** | None. Single table. |
| **Validation** | FormRequest: `StoreIGEBudgetRequest` / `UpdateIGEBudgetRequest` (controller type-hints `FormRequest`). |
| **update()** | Delegates to store(): `return $this->store($request, $projectId);` |

### 1.3 Persistence Pattern

**Delete-then-recreate.**

- **Line 56:** `ProjectIGEBudget::where('project_id', $projectId)->delete();` — unconditional delete of all budget rows for the project.
- **Lines 58–81:** `foreach ($names as $i => $name)` — create row only when `!empty($nameVal)` (non-empty name after coercion).

Not updateOrCreate. Not firstOrNew → fill → save. Not hybrid; pure multi-row delete-then-recreate.

---

## PHASE 2 — DELETE RISK ANALYSIS

### 2.1 Delete Patterns Found

1. **store() (line 56)**  
   - `ProjectIGEBudget::where('project_id', $projectId)->delete();`  
   - Inside `DB::beginTransaction()` / try block, before the foreach create loop.  
   - **No** check on whether the section has meaningful data before this delete.

2. **destroy() (line 157)**  
   - `ProjectIGEBudget::where('project_id', $projectId)->delete();`  
   - Inside its own transaction/try. Explicit destroy action; not part of store/update persistence.

### 2.2 Empty-Section Guard Before store() Delete?

**No.** There is no guard that skips the transaction or the delete when the section is empty or absent. Flow is: budget lock check → normalize arrays → `DB::beginTransaction()` → try → Log → **delete** → foreach (create when `!empty($nameVal)`).

### 2.3 Behavior When Request Arrays Are …

- **Missing (e.g. no `name`):**  
  `$data['name'] ?? null` is `null`. Normalization yields `$names = []`. The delete at line 56 **still runs**. The foreach runs zero times. **Result: all existing IGE budget rows for the project are removed; no new rows; data loss.**

- **Present but empty (`name => []`):**  
  `$names = []`. Same as above: delete runs, loop runs zero times. **Result: data loss.**

- **Present but all rows empty (e.g. `name => ['', '', '']`):**  
  Loop runs; for each index `$nameVal` is empty, so `!empty($nameVal)` is false and no `create()` runs. Delete has already run. **Result: data loss.**

### 2.4 Would Existing Data Be Wiped?

**Yes.** For missing section key, empty array, or all blank names, the controller still executes the delete and creates zero rows. Existing IGE budget data for the project is wiped.

---

## PHASE 3 — DATA LOSS RISK DETERMINATION

**Risk level: HIGH**

**Reasoning:**

- Delete in store() **always** runs once the request passes the budget lock check and reaches the transaction. There is no conditional skip when the section is empty or absent.
- Empty or missing `name` (or all blank names) leads to delete + zero creates.
- Same M1 vulnerability as other delete-then-recreate section controllers before a skip-empty guard: **unconditional delete regardless of input; empty section wipes data.**

---

## PHASE 4 — BEHAVIORAL DIFFERENCES

### 4.1 vs IESExpensesController

| Aspect | IGEBudgetController | IESExpensesController |
|--------|----------------------|-------------------------|
| **Structure** | Flat multi-row (one table) | Nested: parent row + child rows (expenseDetails) |
| **Delete pattern** | `Model::where('project_id', ...)->delete()` then loop create | Parent: `$existing->expenseDetails()->delete(); $existing->delete();` then create parent + children |
| **Empty-section guard** | **None** | **Yes** — `isIESExpensesMeaningfullyFilled()` before transaction |
| **Transaction** | Yes (beginTransaction, commit, rollBack) | Yes |
| **Response** | **Redirect** (route + flash) | **JSON** (200 / 500) |
| **Budget lock** | Redirect back with error | **403 JSON** |
| **Normalizers** | Inline scalar-to-array only | ArrayToScalarNormalizer for header |
| **Post-save sync** | BudgetSyncService::syncFromTypeSave | Same |

### 4.2 vs IIESExpensesController

| Aspect | IGEBudgetController | IIESExpensesController |
|--------|----------------------|-------------------------|
| **Structure** | Flat multi-row | Nested (parent + expenseDetails) |
| **Delete pattern** | where()->delete() then loop create | first() then expenseDetails()->delete(), parent delete, then create parent + children |
| **Empty-section guard** | **None** | **Yes** — `isIIESExpensesMeaningfullyFilled()` before delete |
| **Response** | Redirect | JSON |
| **Budget lock** | Redirect back | **HttpResponseException** with 403 JSON |

### 4.3 vs BudgetController (Projects)

- BudgetController works with `phases` and project-level budget; different schema and flow (e.g. ProjectBudget, phases). Not the same section type. Uses BudgetSyncGuard; throws HttpResponseException (redirect) on lock. No direct multi-row delete-then-recreate of a single `project_id`-scoped table like IGE budget.

### 4.4 vs IGE Beneficiaries Controllers (e.g. IGEBeneficiariesSupportedController)

- Same **pattern**: multi-row delete-then-recreate, redirect, same normalization style.
- **Difference:** IGEBeneficiariesSupportedController has an **M1 skip-empty guard** before the transaction; IGEBudgetController does **not**.
- IGEBudgetController additionally has **budget lock** (BudgetSyncGuard) and **BudgetSyncService::syncFromTypeSave** after commit; IGE beneficiaries controllers do not.

---

## PHASE 5 — FINAL VERDICT

1. **STRUCTURALLY IDENTICAL to IIESExpenses?**  
   **No.** IIES is nested (parent + children) and returns JSON; IGE budget is flat multi-row and returns redirect.

2. **STRUCTURALLY SIMILAR but with differences?**  
   **Yes.** Same *risk pattern* (delete-then-recreate, no guard). Different structure (flat vs nested), different response (redirect vs JSON), and IGE budget has budget lock and sync.

3. **STRUCTURALLY DIFFERENT?**  
   From IIES/IES expenses: yes (flat vs nested, redirect vs JSON). From IGE beneficiaries: same delete-then-recreate style but no guard and has budget lock/sync.

4. **Does it require M1 Guard?**  
   **Yes.** Unconditional delete in store() with no empty-section check causes data loss when the section is missing, empty, or all blank.

5. **If yes — what type (multi-row or nested)?**  
   **Multi-row.** Single table, no parent row; guard should consider the section “meaningfully filled” when at least one row has meaningful data (e.g. non-empty `name` or meaningful numeric fields), and skip the transaction/delete when not.

---

## RECOMMENDATION

- Add an **M1 skip-empty guard** before `DB::beginTransaction()` in `store()`.
- Guard input: normalized arrays (e.g. `$names` and any parallel arrays used to define a “row”). Return false when section key is absent, arrays are empty, or all rows are empty/blank; then log and return the **same** success redirect without running the transaction or delete.
- Preserve: budget lock check (and its redirect), transaction structure, foreach create logic, response format (redirect), and post-commit BudgetSyncService::syncFromTypeSave.

---

## CONFIRMATION

**No code was modified during this verification.** This document is analysis only. No edits, refactoring, guards, or formatting changes were applied to any file.

---

*End of M1 IGE Budget Architecture Verification.*
