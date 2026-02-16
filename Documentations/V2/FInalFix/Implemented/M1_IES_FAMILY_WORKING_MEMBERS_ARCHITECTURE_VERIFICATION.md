# M1 — IES Family Working Members Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`  
**Mode:** READ-ONLY analysis. No code changes. Guard not implemented.

---

## PHASE 1 — STRUCTURE ANALYSIS

### 1.1 store() — Delete Then Recreate

**Confirmed.** `store()` uses the delete-then-recreate pattern:

- **Line 70:** `ProjectIESFamilyWorkingMembers::where('project_id', $projectId)->delete();`
- **Lines 77–91:** Arrays `member_name`, `work_nature`, `monthly_income` are normalized, then a `for` loop over `count($memberNames)` creates new records only when `!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)`.

The delete is **unconditional** and runs before any check for meaningful input. There is no guard that skips the delete when the section is empty or absent.

### 1.2 update() Delegates to store()

**Confirmed.** `update()` (lines 143–147) delegates to `store()`:

```php
public function update(FormRequest $request, $projectId)
{
    // Reuse store logic
    return $this->store($request, $projectId);
}
```

So all behaviour and risk described for `store()` applies to `update()` as well.

### 1.3 Section Keys, Parallel Arrays, Validation, Transaction, Response

| Item | Detail |
|------|--------|
| **Section keys (row count)** | `member_name` drives the loop length via `count($memberNames)`. Parallel arrays: `work_nature`, `monthly_income`. |
| **Parallel arrays** | `$memberNames`, `$workNatures`, `$monthlyIncomes` — normalized from `$data['member_name']`, `$data['work_nature']`, `$data['monthly_income']` (lines 73–75). Scalar values are coerced to single-element arrays for iteration. |
| **Validation source** | FormRequest: `StoreIESFamilyWorkingMembersRequest` / `UpdateIESFamilyWorkingMembersRequest`. Controller type-hints `FormRequest`, so the route determines which request class runs (authorize + rules). Rules: `member_name`, `work_nature`, `monthly_income` as arrays with nullable elements. |
| **DB::transaction** | **Yes.** `DB::beginTransaction()` before delete; `DB::commit()` after loop; `DB::rollBack()` in catch (lines 58, 94, 102). |
| **Response type** | JSON: `200` with `['message' => '...']` on success; `500` with `['error' => '...']` on exception (lines 97, 105). |

### 1.4 Empty-Section Guard

**No.** There is no check that the section is “meaningfully filled” before running the delete. The flow is:

1. Begin transaction  
2. Delete all existing rows for the project  
3. Normalize arrays from request  
4. Loop and create only non-empty rows  

If the request has no meaningful rows (missing keys, empty arrays, or all empty strings/null), step 2 still runs and step 4 creates nothing → **existing data is wiped**.

---

## PHASE 2 — DATA LOSS RISK

### 2.1 If Section Key Is Missing

- `$data = $request->only($fillable)` → missing keys are absent in `$data`.
- Line 73: `$data['member_name'] ?? null` → `null`. `is_array(null)` is false, so the ternary yields: `(isset($data['member_name']) && $data['member_name'] !== '' ? [$data['member_name']] : [])` → `[]`.
- So `$memberNames = []`, `count($memberNames) = 0`, loop runs 0 times.
- Delete has **already** been executed. Result: **all existing family working members are removed; no new rows created.**

### 2.2 If Section Key Is Empty Array

- e.g. `member_name => []`. Then `$memberNames = []`, same as above. Delete runs, loop runs 0 times. **Same outcome: existing data wiped.**

### 2.3 If All Rows Are Empty Strings / Null

- e.g. `member_name => ['', '', '']`, same for `work_nature` and `monthly_income`. Loop runs (e.g. 3 times), but for each index `!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)` is false, so no `create()` calls.
- Delete has already run. Result: **existing data wiped; no new records.**

### 2.4 Conclusion — Data Loss

**EMPTY OR ABSENT SECTION WILL WIPE EXISTING DATA.**

- Missing section key → wipe.  
- Empty array for section → wipe.  
- All rows empty/null → wipe.  

Risk is **unconditional delete** before any “skip empty” logic.

---

## PHASE 3 — DIFFERENCES FROM IIES VERSION

Comparison with `IIESFamilyWorkingMembersController`.

### 3.1 Transaction

| Controller | store() | update() | destroy() |
|------------|--------|----------|-----------|
| **IES** | Uses `DB::beginTransaction()` / `commit()` / `rollBack()` in try/catch. | Same (via store()). | Uses transaction + try/catch. |
| **IIES** | **No** transaction in store(). | **No** transaction in update(). | **No** transaction in destroy(). |

### 3.2 Validation

| Aspect | IES | IIES |
|--------|-----|------|
| **Entry point** | FormRequest injected by route (authorize + rules). | store(): manually `StoreIIESFamilyWorkingMembersRequest::createFrom($request)`, `getNormalizedInput()`, `Validator::make()`, `validate()`, `validated()`. update(): same with `UpdateIIESFamilyWorkingMembersRequest`. |
| **Fields** | `member_name`, `work_nature`, `monthly_income`. | `iies_member_name`, `iies_work_nature`, `iies_monthly_income`. |
| **Normalization** | In controller: scalar-to-array and array-element coercion. | FormRequest uses `NormalizesInput` + `PlaceholderNormalizer::normalizeToZero` for `iies_monthly_income`. |
| **Extra rule** | None. | IIES uses `NumericBoundsRule` on monthly income. |

### 3.3 Error Handling

| Aspect | IES | IIES |
|--------|-----|------|
| **store() / update()** | try/catch: on exception, `DB::rollBack()`, `Log::error()`, return JSON 500. | No try/catch; exception propagates (no rollback; IIES has no transaction). |
| **show()** | try/catch: on error returns `collect([])`. | try/catch: on error `throw $e`. |
| **edit()** | try/catch: on error returns `null`. | try/catch: on error `throw $e`. |
| **destroy()** | try/catch + rollBack on failure. | No try/catch; returns 200 after delete. |

### 3.4 Response

| Method | IES | IIES |
|--------|-----|------|
| **store() success** | JSON 200, message. | JSON 200, message. |
| **store() failure** | JSON 500, error message. | Exception (no handler in controller). |
| **update()** | Delegates to store() → same as above. | Own implementation; JSON 200 on success; no catch. |
| **show()** | Returns model collection (or empty collection on error). | Returns model collection (or rethrows). |
| **edit()** | Returns model collection or null. | Returns view with project. |

### 3.5 Structural Summary

- **IES:** Single code path for persistence: `store()` (and `update()` → `store()`). Transaction + try/catch. No empty-section guard; delete always runs.
- **IIES:** Separate logic in `store()` and `update()` (no delegation). No transaction. Validation and normalization in FormRequest. Same delete-then-recreate pattern; same data-loss risk when section is empty or absent (no guard).

---

## DELIVERABLE SUMMARY

| Field | Value |
|-------|--------|
| **Date** | 2026-02-14 |
| **Risk level** | **High** (empty or absent section wipes existing data) |
| **Delete pattern** | Unconditional `Model::where('project_id', $projectId)->delete()` then recreate loop keyed by `member_name` (and parallel arrays). No check before delete. |
| **Guard presence** | **No** |
| **Behaviour on empty input** | Delete runs; loop creates zero rows; existing family working members are removed. |
| **Structural comparison to IIES** | IES: transaction, try/catch, update delegates to store, validation via injected FormRequest. IIES: no transaction, no try/catch in store/update, update has its own delete+loop, validation via manual FormRequest + Validator. Both: delete-then-recreate, no skip-empty guard. |

---

## Final Verdict

**UNPROTECTED DELETE-RECREATE**

- Delete is unconditional; there is no guard that skips mutation when the section is empty or absent.  
- Empty or absent section **will** wipe existing family working members data.  
- Implementation of an M1 skip-empty guard (similar to IESExpensesController) is recommended before relying on this endpoint with partial or empty payloads.  
- **No code changes** were made in this verification; guard **not** implemented.
