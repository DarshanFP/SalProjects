# M1 — IGE New Beneficiaries Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php`  
**Mode:** READ-ONLY analysis. No code changes. Guard not implemented.

---

## PHASE 1 — STRUCTURE ANALYSIS

### 1.1 store() — Delete Then Recreate

**Confirmed.** `store()` uses the delete-then-recreate pattern:

- **Line 43:** `ProjectIGENewBeneficiaries::where('project_id', $projectId)->delete();`
- **Lines 46–64:** Loop over `$beneficiaryNames` (`foreach ($beneficiaryNames as $index => $name)`). For each index, if `!empty(trim($nameVal))` (i.e. beneficiary_name is non-empty after trim), a new record is created using parallel arrays: `caste`, `address`, `group_year_of_study`, `family_background_need`.

The delete is **unconditional** and runs inside the try block before the loop. There is no check that the section is meaningfully filled before deleting.

### 1.2 update() Delegates to store()

**Confirmed.** `update()` (lines 139–143) delegates to `store()` with `$shouldRedirect = false`:

```php
public function update(FormRequest $request, $projectId)
{
    // Reuse store logic but don't redirect (called from ProjectController@update)
    return $this->store($request, $projectId, false);
}
```

So all behaviour and risk described for `store()` apply to `update()` as well.

### 1.3 Section Keys, Parallel Arrays, Validation, Transaction, Response

| Item | Detail |
|------|--------|
| **Section key (row count)** | `beneficiary_name` drives iteration: loop is `foreach ($beneficiaryNames as $index => $name)`. |
| **Parallel arrays** | `$beneficiaryNames`, `$castes`, `$addresses`, `$groupYearOfStudies`, `$familyBackgroundNeeds` — normalized from `$data['beneficiary_name']`, `$data['caste']`, `$data['address']`, `$data['group_year_of_study']`, `$data['family_background_need']` (lines 21–25). Scalar values are coerced to single-element arrays. |
| **Validation source** | FormRequest: `StoreIGENewBeneficiariesRequest` / `UpdateIGENewBeneficiariesRequest`. Controller type-hints `FormRequest`; rules define arrays with nullable elements for all five keys. |
| **DB::transaction** | **Conditional.** Controller checks `DB::transactionLevel() > 0`. If **not** in a transaction, it calls `DB::beginTransaction()` before the try and `DB::commit()` / `DB::rollBack()` as appropriate. If **already** in a transaction (e.g. called from `ProjectController::update()`), it does **not** start or commit/rollBack its own transaction. |
| **Response type** | **Context-dependent.** When `$shouldRedirect && !$inTransaction`: redirect with flash message (success or error). When called from update (no redirect): returns `true` on success or rethrows exception. So: **redirect** when store is called standalone; **return true / throw** when called from `ProjectController::update()`. |

### 1.4 Empty-Section Guard

**No.** There is no check that the section has at least one meaningful row before running the delete. The flow is:

1. Normalize arrays from request  
2. Optionally begin transaction (if not already in one)  
3. Delete all existing new beneficiaries for the project  
4. Loop and create only rows where `!empty(trim($nameVal))`  

If the request has no meaningful rows (missing key, empty array, or all beneficiary_name values empty/whitespace), step 3 still runs and step 4 creates nothing → **existing data is wiped**.

---

## PHASE 2 — DATA LOSS RISK

### 2.1 If Section Key Is Missing

- `$data = $request->only($fillable)` → missing keys are absent.
- Line 21: `$data['beneficiary_name'] ?? null` → `null`. The ternary yields an empty array `[]` (same logic as other IGE/IES controllers: `is_array(null)` false, then the other branch gives `[]`).
- So `$beneficiaryNames = []`, `foreach` runs 0 times.
- Delete has **already** been executed (inside the same try block, before the loop). Result: **all existing new beneficiaries are removed; no new rows created.**

### 2.2 If Section Key Is Empty Array

- e.g. `beneficiary_name => []`. Then `$beneficiaryNames = []`, same as above. Delete runs, loop runs 0 times. **Same outcome: existing data wiped.**

### 2.3 If All Rows Are Empty Strings / Null

- e.g. `beneficiary_name => ['', '  ', null]`. Loop runs, but for each index `$nameVal` is empty or whitespace, so `!empty(trim($nameVal))` is false. No `create()` calls.
- Delete has already run. Result: **existing data wiped; no new records.**

### 2.4 Conclusion — Data Loss

**EMPTY OR ABSENT SECTION WILL WIPE EXISTING DATA.**

- Missing section key → wipe.  
- Empty array for section → wipe.  
- All rows empty/null/whitespace → wipe.  

Risk is **unconditional delete** before any “skip empty” logic.

---

## PHASE 3 — TRANSACTION SAFETY

### 3.1 Does the Controller Start Its Own DB::transaction?

**Conditionally, yes.** It starts a transaction only when **not** already inside one:

- **Lines 27–32:** `$inTransaction = DB::transactionLevel() > 0;` then `if (!$inTransaction) { DB::beginTransaction(); }`
- So when called **standalone** (e.g. direct store route), it begins its own transaction. When called from a parent that already started a transaction, it does not.

### 3.2 Does It Check DB::transactionLevel()?

**Yes.** Line 27: `$inTransaction = DB::transactionLevel() > 0;`. This is used to:

- Skip `DB::beginTransaction()` when already in a transaction.
- Skip `DB::commit()` when in a transaction (line 65–67).
- Skip `DB::rollBack()` when in a transaction (line 80–82).
- Decide response: redirect only when `!$inTransaction` (lines 75–76, 90–91); when in transaction, return `true` or rethrow (lines 78, 94).

### 3.3 Does It Run Safely Inside ProjectController::update() Transaction?

**Yes.** `ProjectController::update()` (line 1372) calls `DB::beginTransaction()` before the switch that includes IGE updates. At line 1437 it calls `$this->igeNewBeneficiariesController->update($request, $project->project_id)`, which delegates to `store($request, $projectId, false)`.

When that runs, `DB::transactionLevel() > 0` is true, so:

- `NewBeneficiariesController::store()` does **not** call `beginTransaction()` or `commit()`/`rollBack()`.
- All work (delete + creates) runs inside the existing ProjectController transaction.
- On success it returns `true`; on exception it rethrows so ProjectController can roll back.

So the controller is **safe** when nested inside `ProjectController::update()`.

---

## PHASE 4 — STRUCTURAL CLASSIFICATION

**A) UNPROTECTED DELETE-RECREATE**

- Pattern: unconditional delete of all rows for the project, then recreate from request arrays in a loop.
- Row creation is gated only by `!empty(trim($nameVal))` (beneficiary_name); no guard that skips the entire delete when the section is empty or absent.
- Matches the same risk pattern as IES Family Working Members and IES Expenses before their M1 guards.

---

## DELIVERABLE SUMMARY

| Field | Value |
|-------|--------|
| **Date** | 2026-02-14 |
| **Risk level** | **High** (empty or absent section wipes existing data) |
| **Delete pattern** | Unconditional `ProjectIGENewBeneficiaries::where('project_id', $projectId)->delete()` inside try block, then `foreach ($beneficiaryNames)` create loop. No check before delete. |
| **Guard presence** | **No** |
| **Behaviour on empty input** | Delete runs; loop creates zero rows (or only rows with non-empty trimmed name); existing new beneficiaries are removed. |
| **Transaction behaviour** | Conditional: starts own transaction only when `DB::transactionLevel() === 0`; when called from ProjectController::update(), uses parent transaction (no nested begin/commit/rollBack). |
| **Response type** | Redirect with flash when standalone store; return `true` or rethrow when called from update (no redirect). |
| **update() delegation** | **Confirmed:** `update()` calls `return $this->store($request, $projectId, false)`. |

---

## Final Verdict

**UNPROTECTED DELETE-RECREATE**

- Delete is unconditional; there is no guard that skips mutation when the section is empty or absent.
- Empty or absent section **will** wipe existing IGE New Beneficiaries data.
- Transaction handling is correct for both standalone and nested (ProjectController) use.
- Implementation of an M1 skip-empty guard is recommended; guard **not** implemented in this verification. **No code changes** were made.
