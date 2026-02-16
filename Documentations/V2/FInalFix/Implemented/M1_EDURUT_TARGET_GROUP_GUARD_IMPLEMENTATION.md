# M1 — EduRUT Target Group Skip-Empty-Section Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/EduRUTTargetGroupController.php` — **update() only**

---

## 1. Summary of Change

A **skip-empty guard** was added to **update()** only. After normalizing `$groups` from `target_group` and **before** `DB::beginTransaction()`, the controller calls `isEduRUTTargetGroupMeaningfullyFilled($groups)`. If it returns false, the method logs and returns the same JSON success response (200) without starting a transaction, deleting, or creating. When the section has at least one meaningful row (non-empty string or non-null numeric in any field), behaviour is unchanged: transaction, delete, recreate loop, commit, JSON success. **store()** and **destroy()** were not modified.

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| update() with at least one meaningful row | Transaction, delete, create loop, commit, 200 JSON. | **Same.** |
| update() with target_group missing | Transaction, delete, 0 creates → data loss. | **Skip:** no transaction, no delete, no create; 200 JSON with message. |
| update() with target_group = [] | Transaction, delete, 0 creates → data loss. | **Skip:** no transaction, no delete, no create; 200 JSON. |
| update() with all rows null/empty | Transaction, delete, create empty rows → data loss. | **Skip:** no transaction, no delete, no create; 200 JSON. |
| store() | Unchanged (append-only, no delete). | **Unchanged.** |
| destroy() | Unchanged. | **Unchanged.** |

---

## 3. Code Insertion Location

**File:** `app/Http/Controllers/Projects/EduRUTTargetGroupController.php`  
**Method:** `update()` only.

**Location:** After `$groups = ...` (normalization) and **before** `DB::beginTransaction()`.

**Guard block:**

```php
        if (! $this->isEduRUTTargetGroupMeaningfullyFilled($groups)) {
            Log::info('EduRUTTargetGroupController@update - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return response()->json([
                'message' => 'EduRUT target group updated successfully.'
            ], 200);
        }

        DB::beginTransaction();
```

**New private methods (end of class):** `isEduRUTTargetGroupMeaningfullyFilled($groups): bool`, `rowHasMeaningfulValue(array $row): bool`, `meaningfulString($value): bool`, `meaningfulNumeric($value): bool`.

---

## 4. Manual Test Cases

1. **update() with full payload (execute)**  
   Submit update with `target_group` containing at least one row with a non-empty string or numeric value in any field.  
   **Expect:** Guard passes; transaction, delete, create loop, commit; 200 JSON "Target group data updated successfully." (unchanged success path).

2. **update() with target_group missing (skip)**  
   Submit update with no `target_group` key.  
   **Expect:** No transaction, no delete, no create. Log: "Section absent or empty; skipping mutation". 200 JSON "EduRUT target group updated successfully."

3. **update() with target_group = [] (skip)**  
   Submit with `target_group => []`.  
   **Expect:** Skip; same log and 200 JSON.

4. **update() with all rows null/empty (skip)**  
   Submit with `target_group` present but every row has only null/empty values.  
   **Expect:** Skip; same log and 200 JSON.

5. **update() with one meaningful row (execute)**  
   Same as (4) but at least one row has one meaningful string or numeric.  
   **Expect:** Guard returns true; transaction, delete, create, commit; 200 JSON.

6. **store() unchanged**  
   Call store() with same payloads as above.  
   **Expect:** No guard; behaviour unchanged (append-only).

7. **destroy() unchanged**  
   Call destroy().  
   **Expect:** Same as before (delete, 200 JSON).

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Valid update incorrectly skipped | Guard returns true when any row is an array and has at least one meaningful string or numeric in any field; full payloads unchanged. |
| store() or destroy() behaviour changed | Only update() was modified; store() and destroy() untouched. |
| Response format changed | On skip, 200 JSON with `message` key; success path still returns 200 JSON. |
| Transaction structure changed | Guard returns before beginTransaction(); when guard passes, transaction block unchanged. |

---

## 6. Confirmation: Only update() Was Modified

Only the **update()** method and the **new private methods** at the end of the class were modified. No changes were made to:

- store()
- destroy()
- show()
- edit()
- uploadExcel()
- Validation rules
- Transaction structure (other than guard early return before beginTransaction)
- Response structure (JSON 200/500)
- Any other controller, model, or route

---

*End of M1 EduRUT Target Group Guard Implementation.*
