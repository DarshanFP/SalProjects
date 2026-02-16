# M1 — BudgetController Skip-Empty-Sections Guard Implementation

**Date:** 2026-02-14  
**Milestone:** 1 — Data Integrity Shield  
**Scope:** BudgetController only. No other controller, validation, resolver, transaction logic, or config was modified.

---

## 1. Summary of Change

A **skip-empty guard** was added to `BudgetController::update()` so that bulk delete and recreate run only when the Budget section is **meaningfully filled** per M1_GUARD_RULE_SPEC.md.

- **Before:** Every call to `update()` with validated input performed `ProjectBudget::where(...)->delete()` then recreated rows from `phases[0]['budget']`. If the request had empty `phases` or empty `budget` array, existing budget rows were deleted and nothing (or only empty rows) was recreated, causing **data loss**.
- **After:** Before any delete, the controller checks `isBudgetSectionMeaningfullyFilled($phases)`. If the section is absent or empty (no meaningful row), the method **returns early** with the same `$project` instance: no delete, no create, no sync. Existing budget data is left unchanged. If the section has at least one meaningful row, behaviour is **unchanged** from before (delete then create then refresh/sync).

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: `phases[0]['budget']` has rows with particulars/amounts | Delete all for phase, create from payload, sync. | **Same.** |
| `phases` key absent (validated gives `[]`) | Delete all, create nothing → **data loss**. | **Skip:** no delete, no create; return $project. |
| `phases` present but `phases[0]['budget']` absent or not array | Delete all, create nothing or partial → **data loss**. | **Skip:** no delete, no create; return $project. |
| `phases[0]['budget']` is `[]` | Delete all, create nothing → **data loss**. | **Skip:** no delete, no create; return $project. |
| `budget` has rows but every row has empty particular and no numeric values | Delete all, create empty/default rows → **data loss**. | **Skip:** no delete, no create; return $project. |
| At least one row has non-empty `particular` or any non-null numeric (`this_phase`, `rate_quantity`, etc.) | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/BudgetController.php`

**3.1 Insertion in `update()` (after normalizing `$phases`, before delete):**

```php
        $phases = $validated['phases'] ?? [];
        if (! is_array($phases)) {
            $phases = [];
        }

+       // M1 Data Integrity Shield: skip delete+recreate when budget section is absent or empty.
+       if (! $this->isBudgetSectionMeaningfullyFilled($phases)) {
+           Log::info('BudgetController@update - Budget section absent or empty; skipping mutation', [
+               'project_id' => $project->project_id,
+           ]);
+
+           return $project;
+       }
+
        ProjectBudget::where('project_id', $project->project_id)
```

**3.2 New private method (end of class):**

```php
+   /**
+    * M1 Guard: true only when phases[0]['budget'] exists and has at least one row with meaningful data.
+    * Meaningful row = at least one non-empty string (after trim) or at least one non-null numeric value.
+    */
+   private function isBudgetSectionMeaningfullyFilled(array $phases): bool
+   {
+       if ($phases === []) {
+           return false;
+       }
+
+       $phase = $phases[0] ?? null;
+       if ($phase === null || ! isset($phase['budget']) || ! is_array($phase['budget'])) {
+           return false;
+       }
+
+       $budget = $phase['budget'];
+       if ($budget === []) {
+           return false;
+       }
+
+       foreach ($budget as $row) {
+           if (! is_array($row)) {
+               continue;
+           }
+           // Non-empty string (e.g. particular)
+           $particular = trim((string) ($row['particular'] ?? ''));
+           if ($particular !== '') {
+               return true;
+           }
+           // Non-null numeric (this_phase, rate_quantity, etc.)
+           $numericKeys = ['this_phase', 'rate_quantity', 'rate_multiplier', 'rate_duration', 'rate_increase'];
+           foreach ($numericKeys as $key) {
+               $val = $row[$key] ?? null;
+               if ($val !== null && $val !== '' && is_numeric($val)) {
+                   return true;
+               }
+           }
+       }
+
+       return false;
+   }
```

---

## 4. Manual Test Cases to Verify

1. **Full payload (unchanged behaviour)**  
   - Submit project edit with Budget section containing at least one row with e.g. `particular` = "Staff", `this_phase` = 10000.  
   - **Expect:** Existing budget rows for current phase deleted; new rows created; sync runs; project returned with refreshed budgets.

2. **`phases` absent**  
   - Submit project edit with no `phases` key in request (or validation normalizes to `phases` = []).  
   - **Expect:** No delete, no create. Existing budget rows unchanged. Log line: "Budget section absent or empty; skipping mutation".

3. **`phases` present, `phases[0]['budget']` missing**  
   - Request has `phases` = `[ {} ]` (no `budget` key).  
   - **Expect:** Skip; no delete, no create; existing data preserved.

4. **`phases[0]['budget']` empty array**  
   - Request has `phases` = `[ [ 'budget' => [] ] ]` or equivalent.  
   - **Expect:** Skip; no delete, no create.

5. **All rows empty**  
   - Request has `phases[0]['budget']` = `[ { 'particular' => '', 'this_phase' => null }, ... ]` (all particulars empty, all numerics null/empty).  
   - **Expect:** Skip; no delete, no create.

6. **One meaningful row**  
   - Same as above but one row has `particular` = "Office" or `this_phase` = 0 or any non-null numeric.  
   - **Expect:** Delete+recreate runs as before; new rows created.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true when any row has non-empty `particular` or any non-null numeric; full payloads are unchanged. |
| Validation sends different shape than guard expects | Guard uses same `$phases` / `phases[0]['budget']` as existing create loop; normalization (array cast) is shared. |
| Log noise | Single info log on skip; can be filtered by message or project_id. |
| Future change to budget structure | If new fields define "meaningful", extend `isBudgetSectionMeaningfullyFilled` (e.g. add keys to `$numericKeys` or string checks). |
| store() unchanged | Only `update()` was modified; `store()` remains as-is per scope. |

---

## 6. Confirmation: No Other Files Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/BudgetController.php`**

No changes were made to:

- Any other controller  
- Any validation (UpdateBudgetRequest, StoreBudgetRequest, rules)  
- Any resolver (ProjectFinancialResolver, ProjectFundFieldsResolver)  
- Any transaction logic (ProjectController or elsewhere)  
- Any config file  
- Any service class (no new service introduced; guard is a private method on BudgetController)

---

*End of M1 Budget Guard Implementation.*
