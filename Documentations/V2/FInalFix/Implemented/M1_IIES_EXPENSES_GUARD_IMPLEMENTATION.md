# M1 — IIESExpensesController Skip-Empty-Sections Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` ONLY.

---

## 1. Summary of Change

A **skip-empty guard** was added to `IIESExpensesController::store()` (used by both store and update) so that delete+recreate runs only when the **IIES Expenses** section (nested: parent + children) is **meaningfully filled** per the nested guard spec.

- **Before:** Every call to `store()` deleted existing parent and child rows (if any), then recreated parent and children from validated input. If the request had no meaningful parent fields and no meaningful child rows, existing expense data was wiped and replaced with empty or default data → **data loss**.
- **After:** After validation, the controller builds `$parentData` (the five parent totals) and `$particulars` / `$amounts` (child arrays), then calls `isIIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)`. If it returns **false**, the method logs and returns `response()->json(['message' => 'IIES estimated expenses saved successfully.'], 200)` without deleting or creating. Existing data is unchanged. If the section has meaningful data, behaviour is **unchanged** (delete existing, create parent, create children, sync).

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: parent totals + particulars/amounts with data | Delete existing, create parent + children, sync. | **Same.** |
| No parent fields + no children in request (all null/empty arrays) | Delete existing, create parent with 0s and no details → **data loss**. | **Skip:** no delete, no create; return 200 with message. |
| Parent all null/blank + children empty | Delete existing, create empty parent + no details → **data loss**. | **Skip:** no delete, no create; return 200. |
| One meaningful child row (particular or amount) | Delete then create. | **Same.** |
| Parent has at least one meaningful numeric (e.g. total) but no children | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`

**3.1 Insertion in `store()` (after `$validated`, before any delete):**

```php
        $validated = $validator->validated();

+       $parentData = [
+           'iies_total_expenses' => $validated['iies_total_expenses'] ?? null,
+           'iies_expected_scholarship_govt' => $validated['iies_expected_scholarship_govt'] ?? null,
+           'iies_support_other_sources' => $validated['iies_support_other_sources'] ?? null,
+           'iies_beneficiary_contribution' => $validated['iies_beneficiary_contribution'] ?? null,
+           'iies_balance_requested' => $validated['iies_balance_requested'] ?? null,
+       ];
+       $particulars = $validated['iies_particulars'] ?? [];
+       $amounts = $validated['iies_amounts'] ?? [];
+       if (! is_array($particulars)) { $particulars = []; }
+       if (! is_array($amounts)) { $amounts = []; }
+
+       // M1 Data Integrity Shield: skip delete+recreate when section is absent or empty.
+       if (! $this->isIIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)) {
+           Log::info('IIESExpensesController@store - Section absent or empty; skipping mutation', [
+               'project_id' => $projectId,
+           ]);
+
+           return response()->json(['message' => 'IIES estimated expenses saved successfully.'], 200);
+       }
+
        Log::info('Storing IIES estimated expenses', ['project_id' => $projectId]);

        $existingExpenses = ProjectIIESExpenses::where('project_id', $projectId)->first();
```

**3.2 Removal of duplicate extraction:** The later lines `$particulars = $validated['iies_particulars'] ?? [];` and `$amounts = $validated['iies_amounts'] ?? [];` were removed; the create loop uses the same `$particulars` and `$amounts` defined above.

**3.3 New private methods (end of class):**

- `isIIESExpensesMeaningfullyFilled(array $parentData, array $particulars, array $amounts): bool` — returns true if any parent field is meaningful (via `meaningfulNumeric`) or any child row has meaningful particular (string) or amount (numeric); otherwise false.
- `meaningfulString($value): bool` — `is_string($value) && trim($value) !== ''`.
- `meaningfulNumeric($value): bool` — `$value !== null && $value !== '' && is_numeric($value)`.

---

## 4. Manual Test Cases

1. **Full payload → delete+recreate runs (unchanged)**  
   Submit store/update with parent totals and at least one row in `iies_particulars` / `iies_amounts`.  
   **Expect:** Existing expenses deleted, parent + children created, sync run, 200 with "IIES estimated expenses saved successfully."

2. **No parent + no children in request → skip**  
   Submit with all five parent keys null/absent and `iies_particulars` / `iies_amounts` empty or absent.  
   **Expect:** No delete, no create. 200 with same message. Log: "Section absent or empty; skipping mutation".

3. **Parent all blank + children empty → skip**  
   Submit with parent fields null/blank and particulars/amounts `[]`.  
   **Expect:** Skip; no delete, no create; 200.

4. **One meaningful child → execute**  
   Same as (3) but one index has non-empty particular or non-null numeric amount.  
   **Expect:** Guard returns true; delete+recreate runs; 200.

5. **Parent has meaningful numeric, no children → execute**  
   Submit with e.g. `iies_total_expenses` = 50000 and particulars/amounts empty.  
   **Expect:** Guard returns true; delete+recreate runs; 200.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true when any parent field is meaningful (including 0) or any child row has meaningful particular or amount; full payloads unchanged. |
| Parent/child keys mismatch | Parent data uses the same five keys used to create the parent model; particulars/amounts are the same arrays used in the create loop. |
| update() path | update() delegates to store(); guard runs in store() so both paths protected. |
| Response format | Early return uses same JSON message and 200 as success path. |

---

## 6. Confirmation: Only IIESExpensesController Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/IIES/IIESExpensesController.php`**

No changes were made to ProjectController, validation rules, database schema, transaction structure, resolver, budget logic, or any other controller.

---

*End of M1 IIES Expenses Guard Implementation.*
