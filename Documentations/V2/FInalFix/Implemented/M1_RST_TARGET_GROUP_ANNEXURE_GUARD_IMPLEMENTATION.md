# M1 — RST Target Group Annexure Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php` ONLY.

---

## 1. Summary

A **skip-empty guard** was added to `TargetGroupAnnexureController::store()` so that delete+recreate runs only when the **RST Target Group Annexure** section (multi-row, six parallel arrays) is **meaningfully filled**.

- **Before:** Every call to `store()` (and thus `update()`) deleted all existing `ProjectRSTTargetGroupAnnexure` rows for the project, then recreated rows from the request. If the request had no rows or only empty/null values, existing data was wiped with nothing meaningful written → **data loss**.
- **After:** After normalizing the six section arrays, the controller builds a `$normalizedRows` array (one row per index, each row the six field values). It then calls `isTargetGroupAnnexureMeaningfullyFilled($normalizedRows)`. If it returns **false**, the method logs and returns `response()->json(['message' => 'Target Group Annexure saved successfully.'], 200)` without starting a transaction, deleting, or creating. Existing data is unchanged. If the section has at least one meaningful value in any row, behaviour is **unchanged** (transaction, delete, recreate).

Validation, transaction structure, response type, field names, and schema were not modified.

---

## 2. Before vs After

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: one or more rows with data | Delete existing, create rows, 200. | **Same.** |
| Section absent or all six arrays empty | Delete existing, create zero rows → **data loss**. | **Skip:** no transaction, no delete, no create; return 200 with same message. |
| Arrays have rows but all values null/empty | Delete existing, create rows with nulls → **data loss**. | **Skip:** no mutation; return 200. |
| At least one row has one non-empty string or numeric | Delete then create. | **Same.** |

---

## 3. Manual Test Cases

1. **Full payload → delete+recreate (unchanged)**  
   Submit store/update with at least one row (e.g. `rst_name`, `rst_religion`, etc. as arrays with values).  
   **Expect:** Existing annexure rows deleted, new rows created, 200 with "Target Group Annexure saved successfully."

2. **Section absent / all arrays empty → skip**  
   Submit with the six keys absent or all normalized to empty arrays.  
   **Expect:** No transaction, no delete, no create. 200 with same message. Log: "Section absent or empty; skipping mutation".

3. **One row, all fields null/empty → skip**  
   Submit with one index but all six values null or empty string.  
   **Expect:** Guard false; skip mutation; 200.

4. **One meaningful value in one row → execute**  
   Same as (3) but one field has e.g. a non-empty string or a number.  
   **Expect:** Guard true; transaction, delete, recreate; 200.

5. **Multiple rows, only last row has one filled field → execute**  
   Submit several rows with only one cell meaningful.  
   **Expect:** Guard true; full delete+recreate; 200.

---

## 4. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true if any row has any meaningful string (trim !== '') or numeric value; normal payloads unchanged. |
| Row structure mismatch | `$normalizedRows` is built from the same six arrays and same index logic used in the create loop; each row is the six scalar values at that index. |
| update() path | update() delegates to store(); guard runs in store(), so both paths protected. |
| Response format | Early return uses the exact same JSON and 200 as the success path. |
| Transaction behaviour | Guard returns before `DB::beginTransaction()`; when guard passes, transaction and delete/recreate logic are unchanged. |

---

## 5. Confirmation: Only This File Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php`**

No changes were made to validation, FormRequests, routes, schema, other controllers, or any other file.

---

*End of M1 RST Target Group Annexure Guard Implementation.*
