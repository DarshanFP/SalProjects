# M1 — EduRUT Target Group Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target file:** `app/Http/Controllers/Projects/EduRUTTargetGroupController.php`  
**Mode:** READ-ONLY. No code modified.

---

## PHASE 1 — STRUCTURE IDENTIFICATION

### 1.1 update() vs store()

**update() does NOT delegate to store().** It contains its own logic (lines 107–159):

- Same fillable `['target_group']` and same normalization of `$groups` from `$data['target_group']`.
- Its own `DB::beginTransaction()`, try block, Log, **delete**, foreach create loop, commit, and JSON response.
- store() (lines 22–71) has **no delete**: it only normalizes `$groups`, starts a transaction, and runs a foreach create loop. So **store() is append-only**; **update() is full replace (delete then recreate)**.

### 1.2 Section Primary Key and Row Structure

- **Section primary key:** `target_group`. Single request key whose value is normalized to an array `$groups`.
- **Row structure:** Each element of `$groups` is expected to be an **array** (associative) with keys: `beneficiary_name`, `caste`, `institution_name`, `class_standard`, `total_tuition_fee`, `eligibility_scholarship`, `expected_amount`, `contribution_from_family`. So this is **one array of row-objects**, not separate parallel arrays like some IGE controllers.
- **Loop:** `foreach ($groups as $group)`; inside, `if (!is_array($group)) continue;` then extract fields from `$group['...']` and `create()`.

### 1.3 Delete-Then-Recreate

- **store():** Does **not** perform `Model::where('project_id', ...)->delete()`. It only creates rows. No delete in store().
- **update():** **Does** perform delete-then-recreate:
  - **Line 120:** `ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete();`
  - **Lines 122–147:** `foreach ($groups as $group)` with `create()` for each element (when `is_array($group)`).
- Delete runs **unconditionally** inside the try block; there is **no** guard or condition that skips the delete when the section is empty or absent.

---

## PHASE 2 — EMPTY SECTION BEHAVIOR

### Case A: Section key missing in request

- `$data['target_group'] ?? null` is `null`. Normalization yields `$groups = []`.
- **update():** Delete at line 120 **still runs**. `foreach ($groups as $group)` runs zero times. No rows created.
- **Result:** Existing target group rows for the project are removed; no new rows. **Data loss.**

### Case B: Section key present but empty array `[]`

- `$groups = []`. Same as Case A.
- **update():** Delete runs, loop runs zero times.
- **Result:** Existing rows wiped; no new rows. **Data loss.**

### Case C: Arrays exist but all values null/empty strings

- `$groups` may be e.g. `[{ beneficiary_name: null, caste: null, ... }, ...]`. Loop runs; each `$group` is an array so `continue` is not taken; `create()` is called for each with the extracted (null/empty) values.
- **update():** Delete runs, then rows are created with those null/empty values.
- **Result:** Existing data wiped and replaced with empty rows. **Data loss** (and empty rows recreated).

### Summary

- **Does delete still execute in empty/absent cases?** Yes (in update() for Cases A and B; in C delete runs then empty rows are created).
- **Would existing rows be wiped?** Yes.
- **Would empty rows be recreated?** In Case C yes; in A and B no (zero creates).
- **Is there any early return preventing delete?** No. No guard or early return before the transaction or before the delete in update().

---

## PHASE 3 — TRANSACTION STRUCTURE

1. **DB::transaction():** The controller does **not** use the `DB::transaction(callback)` form. It uses explicit **beginTransaction()**, **commit()**, and **rollBack()** in try/catch.
2. **store():** `DB::beginTransaction()` before try; create loop in try; `DB::commit()` on success; `DB::rollBack()` in catch. No delete.
3. **update():** `DB::beginTransaction()` before try; delete and create loop inside try; `DB::commit()` on success; `DB::rollBack()` in catch. **Delete is inside the transaction.**
4. **destroy():** Separate method. **Does not** use beginTransaction/commit/rollBack. It uses try/catch with `ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete()` and returns JSON. No transaction wrapper.
5. **destroy() when no records exist:** It does **not** use firstOrFail(). It calls `where('project_id', $projectId)->delete()` directly. If no rows exist, delete affects 0 rows; no exception. Method still returns 200 JSON with success message. So it is a no-op when no record exists, not a failure.

---

## PHASE 4 — VALIDATION / NORMALIZATION

1. **FormRequest:** store() and update() type-hint `FormRequest`; the actual request class is likely bound by the route. No specific StoreEduRUT...Request or ArrayToScalarNormalizer is referenced in the controller file.
2. **ArrayToScalarNormalizer:** Not used in this controller.
3. **Arrays normalized before looping:** Yes. `$groups` is set from `$data['target_group']` with the usual ternary: if array use it (or []), else if set and not empty string wrap in one-element array, else [].
4. **Numeric fields cast:** No explicit cast in the controller; values are passed to `create()` as extracted (possibly string or null).
5. **Empty strings converted to null:** No explicit conversion in the controller.

---

## PHASE 5 — RESPONSE BEHAVIOR

1. **store():** Returns **JSON**. Success: `response()->json(['message' => 'Target group data saved successfully.'], 200)`. Error: `response()->json(['error' => '...'], 500)`.
2. **update():** Returns **JSON**. Success: `response()->json(['message' => 'Target group data updated successfully.'], 200)`. Error: `response()->json(['error' => '...'], 500)`.
3. **destroy():** Returns **JSON**. Success: `response()->json(['message' => 'Target group data deleted successfully.'], 200)`. Error: `response()->json(['error' => '...'], 500)`.

No redirects in this controller.

---

## STRUCTURAL COMPARISON VS IGE / RST CONTROLLERS

| Aspect | EduRUTTargetGroupController | Typical IGE (e.g. OngoingBeneficiaries) | RST (e.g. target group style) |
|--------|-----------------------------|----------------------------------------|--------------------------------|
| **update()** | Own logic; does **not** delegate to store() | Delegates to store() | Often delegates to store() |
| **store()** | Append-only (no delete) | Delete then recreate | Varies |
| **update() persistence** | Delete then recreate | N/A (uses store()) | Often delete then recreate |
| **Section shape** | One array of row-objects (`target_group` = array of assoc arrays) | Parallel arrays (e.g. name[], caste[]) | Often parallel arrays or similar |
| **Empty guard** | None in update() | Present in some (e.g. OngoingBeneficiaries after M1) | Varies |
| **Response** | JSON only | Redirect | Often redirect |
| **destroy() transaction** | No transaction | Often has transaction | Varies |

EduRUT is **structurally different** in that store() never deletes and update() duplicates the loop logic and is the only path that performs delete-then-recreate. So the **vulnerable path is update() only**, not store().

---

## VERDICT

**VULNERABLE (delete-then-recreate without guard).**

- **update()** uses unconditional `ProjectEduRUTTargetGroup::where('project_id', $projectId)->delete()` followed by a recreate loop. There is no check that `target_group` is meaningfully filled before the delete.
- Missing, empty, or all-empty `target_group` in update() causes existing target group data to be wiped (and in the all-empty case replaced with empty rows).
- store() does not delete and is not vulnerable in the same way; the risk is confined to the update() path.

---

## CONFIRMATION

No code was modified during this verification. No guard was added. No refactoring, validation changes, transaction changes, or response changes were made. Only the controller file was analyzed.

---

*End of M1 EduRUT Target Group Architecture Verification.*
