# M1 — IGE Ongoing Beneficiaries Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target file:** `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`  
**Mode:** READ-ONLY forensic analysis. No code modifications. No refactoring. No guards introduced.

---

## 1. STRUCTURE

### A. Delete-then-recreate

**Yes.** The controller uses delete-then-recreate:

- **Line 31:** `ProjectIGEOngoingBeneficiaries::where('project_id', $projectId)->delete();` (inside try block).
- **Lines 34–50:** `foreach ($obeneficiaryNames as $index => $name)` with `ProjectIGEOngoingBeneficiaries::create([...])` for each row where `$nameVal !== null`.

Delete is unconditional and runs before the loop. There is no check that the section has meaningful data before the delete.

### B. update() delegates to store()

**Yes.** `update()` (lines 65–69) delegates to `store()`:

```php
public function update(FormRequest $request, $projectId)
{
    // Reuse the store logic for updating
    return $this->store($request, $projectId);
}
```

No separate update logic; all persistence goes through `store()`.

### C. DB::transaction()

**Yes.** Logic is wrapped in a transaction:

- **Line 26:** `DB::beginTransaction();` (before try).
- **Line 51:** `DB::commit();` (on success).
- **Lines 55–56:** `DB::rollBack();` and redirect on exception.

The controller **always** starts its own transaction. It does **not** check `DB::transactionLevel()` and does not skip begin/commit/rollBack when already inside a parent transaction.

### D. Primary section key

**obeneficiary_name.** It is the key used to normalize the main array (`$obeneficiaryNames`) and drives the loop (`foreach ($obeneficiaryNames as $index => $name)`).

### E. Arrays that define row count

**$obeneficiaryNames** defines iteration. The loop is:

`foreach ($obeneficiaryNames as $index => $name)`.

Parallel arrays (same index used for each row) are: `$ocastes`, `$oaddresses`, `$ocurrentGroupYearOfStudies`, `$operformanceDetails`.

### F. Array normalization

**Yes.** Arrays are normalized (lines 21–25):

- `is_array($data['obeneficiary_name'] ?? null) ? ($data['obeneficiary_name'] ?? []) : (isset($data['obeneficiary_name']) && $data['obeneficiary_name'] !== '' ? [$data['obeneficiary_name']] : [])` for `obeneficiary_name`.
- Similar pattern for `ocaste`, `oaddress`, `ocurrent_group_year_of_study`, `operformance_details`: if not array, scalar is wrapped in a single-element array or default `[]`.

So missing keys or non-array values are turned into `[]` or `[singleValue]`.

### G. Skip-empty guard

**No.** There is no check for “section meaningfully filled” before the delete. Execution flow is: normalize arrays → `DB::beginTransaction()` → try → Log → delete → foreach → create (when `$nameVal !== null`) → commit → redirect. No conditional skip before the transaction or before the delete.

---

## 2. DATA LOSS RISK ANALYSIS

### If the section key is absent

- `$data['obeneficiary_name'] ?? null` is `null`. The ternary yields an empty array `[]` (same pattern as other IGE controllers).
- So `$obeneficiaryNames = []`, and `foreach ($obeneficiaryNames as ...)` runs zero times.
- The delete at line 31 has **already** run. No rows are created.
- **Result: all existing ongoing beneficiaries for the project are removed; data loss.**

### If the arrays are empty

- If `obeneficiary_name` is sent as `[]` (or becomes `[]` after normalization), `$obeneficiaryNames = []`, loop runs zero times.
- Delete has already run.
- **Result: same as above; data loss.**

### If arrays contain only blank values

- The create condition is `if ($nameVal !== null)`. So:
  - If `$nameVal` is `''` (empty string), `$nameVal !== null` is true, so a row **is** created (with empty string).
  - If `$nameVal` is `null`, no row is created.
- So “blank” behaviour depends on how values are normalized:
  - If all entries are `null`, loop runs but no creates → delete already ran → **data loss**.
  - If some entries are `''`, rows with empty string are created; existing rows are still deleted and replaced by those (possible unintended wipe of real data if request is “all blank” but normalized to empty strings).

So: **if the section is absent or empty, or all values are null, delete still runs and existing data is wiped with no (or only empty) replacement. There is no protection.**

### Will delete still execute?

**Yes.** Delete runs unconditionally inside the try block, before the loop. There is no guard that skips the transaction or the delete when the section is empty or absent.

### Current protection

**None.** No skip-empty guard, no check on array count or “meaningful” content before delete.

---

## 3. RESPONSE BEHAVIOUR

- **Type:** Redirect only. No JSON responses in this controller.
- **Success:** `redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries saved successfully.')` (line 54).
- **Error:** `redirect()->back()->with('error', 'Failed to save Ongoing Beneficiaries.')` (line 58).
- **Status code:** Not set explicitly; Laravel redirects are typically 302.
- **Budget lock:** Not referenced in this controller.
- **Authorization:** Not performed in the controller; assumed to be handled by FormRequest (e.g. `StoreIGEOngoingBeneficiariesRequest` / `UpdateIGEOngoingBeneficiariesRequest`) and/or routing/middleware.

---

## 4. DIFFERENCE FROM IGE NewBeneficiariesController

Comparison is architectural only (no assumptions beyond what the code shows).

| Aspect | OngoingBeneficiariesController | NewBeneficiariesController |
|--------|--------------------------------|----------------------------|
| **Section key / row driver** | `obeneficiary_name` → `$obeneficiaryNames` | `beneficiary_name` → `$beneficiaryNames` |
| **Parallel arrays** | 5: ocaste, oaddress, ocurrent_group_year_of_study, operformance_details | 5: caste, address, group_year_of_study, family_background_need |
| **Transaction** | Always `DB::beginTransaction()` then commit/rollBack. No `transactionLevel()` check. | Checks `DB::transactionLevel() > 0`; only starts transaction when not already in one. |
| **Store signature** | `store(FormRequest $request, $projectId)` | `store(FormRequest $request, $projectId, $shouldRedirect = true)` |
| **Response when successful** | Always `redirect()->route('projects.edit', $projectId)->with('success', ...)`. | If `$shouldRedirect && !$inTransaction`: redirect to `projects.edit`. Else: `return true` (for use when called from ProjectController::update()). |
| **Row create condition** | `if ($nameVal !== null)` | `if (!empty(trim($nameVal)))` |
| **Skip-empty guard** | None. | Yes: `isIGENewBeneficiariesMeaningfullyFilled(...)` before transaction; on false, early return (redirect or true). |
| **Nested in ProjectController::update()** | When called via update(), still starts its own transaction (nested) and returns redirect. | When called via update(), does not start transaction, returns `true` so parent can continue and commit. |

So OngoingBeneficiariesController is **structurally similar** (delete-then-recreate, normalized arrays, update delegates to store) but **lacks** the transactionLevel check, the `$shouldRedirect` parameter, and any skip-empty guard. Row “meaning” is defined differently: Ongoing uses `$nameVal !== null`; New uses `!empty(trim($nameVal))`.

---

## 5. FINAL VERDICT

**UNSAFE (delete runs even if empty).**

- Delete is unconditional; no guard skips mutation when the section is absent, empty, or all-null.
- Empty or absent section **will** wipe existing ongoing beneficiaries.
- Same risk pattern as other M1 delete-then-recreate controllers before a skip-empty guard; this controller does not yet have that guard.

---

## 6. OUTPUT FORMAT CONFIRMATION

- Date: 2026-02-14  
- Milestone: M1  
- Target file path: `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`  
- Sections: Structure, Data loss risk, Response behaviour, Difference from NewBeneficiariesController, Final verdict  
- No code modifications, no refactoring, no assumptions beyond the codebase read  

**End of verification.**
