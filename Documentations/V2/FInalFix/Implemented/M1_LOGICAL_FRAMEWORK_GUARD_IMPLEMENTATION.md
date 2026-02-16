# M1 — LogicalFrameworkController Skip-Empty-Sections Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** LogicalFrameworkController ONLY.

---

## 1. Summary of Change

A **skip-empty guard** was added to `LogicalFrameworkController::update()` so that bulk delete and recreate run only when the **objectives** section is **meaningfully filled** (nested section rules per M1_GUARD_RULE_SPEC.md).

- **Before:** `update()` always ran `ProjectObjective::where('project_id', $project_id)->delete()` inside a transaction, then recreated objectives/results/risks/activities from `$objectives`. If `objectives` was absent or empty, all logical framework data was deleted and nothing (or only empty rows) was recreated, causing **data loss**.
- **After:** Before the transaction, the controller calls `isLogicalFrameworkMeaningfullyFilled($objectives)`. If the section is absent or empty (no meaningful objective or child data), the method **returns early** with `redirect()->back()->with('success', 'Logical framework updated successfully.')`: no transaction, no delete, no create. Existing data is unchanged. If at least one objective has meaningful data, behaviour is **unchanged** (transaction, delete, recreate).

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: objectives with non-empty objective text and/or results/risks/activities | Delete all, recreate from payload. | **Same.** |
| `objectives` key missing (input default `[]`) | Delete all, create nothing → **data loss**. | **Skip:** no transaction, no delete; redirect back with success. |
| `objectives` = `[]` | Delete all, create nothing → **data loss**. | **Skip:** no transaction, no delete; redirect back with success. |
| `objectives` = `null` | Delete all or error. | **Skip:** no transaction, no delete; redirect back with success. |
| objectives with empty objective string and no meaningful results/risks/activities | Delete all, create empty rows → **data loss**. | **Skip:** no transaction, no delete; redirect back with success. |
| At least one objective with non-empty objective text, or one result/risk/activity with non-empty text | Delete then recreate. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/LogicalFrameworkController.php`

**3.1 Insertion in `update()` (after obtaining `$objectives`, before `DB::transaction`):**

```php
    $objectives = $request->input('objectives', []);

+   // M1 Data Integrity Shield: skip delete+recreate when section is absent or empty.
+   if (! $this->isLogicalFrameworkMeaningfullyFilled($objectives)) {
+       Log::info('LogicalFrameworkController@update - Section absent or empty; skipping mutation', [
+           'project_id' => $project_id,
+       ]);
+
+       return redirect()->back()->with('success', 'Logical framework updated successfully.');
+   }
+
    DB::transaction(function () use ($objectives, $project_id) {
```

**3.2 New private methods (end of class):**

- `isLogicalFrameworkMeaningfullyFilled($objectives): bool` — returns false when objectives is null, not array, or empty array; or when every objective has no meaningful data; returns true when at least one objective has meaningful objective text or at least one meaningful result/risk/activity.
- `objectiveHasMeaningfulData(array $objective): bool` — true if objective text is meaningful, or `childArrayHasMeaningfulData` for results (key `result`), risks (key `risk`), or activities (keys `activity` / `verification`).
- `childArrayHasMeaningfulData(array $children, string $fieldKey): bool` — true if any element has a meaningful string at `$fieldKey`.
- `meaningfulString($value): bool` — true when `is_string($value) && trim($value) !== ''`.

---

## 4. Manual Test Cases

1. **Full payload → delete+recreate runs (unchanged behaviour)**  
   Submit update with `objectives` containing at least one objective with non-empty `objective` text and/or at least one result/risk/activity with non-empty text.  
   **Expect:** Transaction runs, existing objectives deleted, new objectives/results/risks/activities created. No redirect before transaction.

2. **objectives key missing → skip, no delete**  
   Submit update with no `objectives` key (e.g. other sections only).  
   **Expect:** Guard returns false (input default `[]`). Redirect back with success. No transaction, no delete. Existing logical framework data unchanged. Log: "Section absent or empty; skipping mutation".

3. **objectives = [] → skip**  
   Submit with `objectives` = `[]`.  
   **Expect:** Skip; redirect back with success; no delete.

4. **objectives with empty objective and empty children → skip**  
   Submit with e.g. `objectives` = `[{ "objective": "", "results": [], "risks": [], "activities": [] }]`.  
   **Expect:** Skip; no meaningful data; redirect back with success; no delete.

5. **At least one meaningful objective or child → delete+recreate runs**  
   Same as (4) but one objective has `"objective": "Improve literacy"` or one result has `"result": "X% pass"`.  
   **Expect:** Guard returns true; transaction runs; delete+recreate as before.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true when any objective has meaningful objective text or any result/risk/activity has meaningful text; full payloads unchanged. |
| Nested structure mismatch | Helpers mirror the same keys used in delete+recreate (objective, results[].result, risks[].risk, activities[].activity, activities[].verification). |
| Response format change | Early return uses same success message and redirect()->back() as a successful update path; caller sees success. |
| store() unchanged | Only update() was modified; store() already had its own empty check and was left as-is. |

---

## 6. Confirmation: Only LogicalFrameworkController Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/LogicalFrameworkController.php`**

No changes were made to ProjectController, BudgetController, any other controller, validation rules, transaction wiring, resolver, budget logic, database schema, or response format elsewhere.

---

*End of M1 Logical Framework Guard Implementation.*
