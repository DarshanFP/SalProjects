# M1 — IESExpensesController Skip-Empty-Sections Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IES/IESExpensesController.php` ONLY.

---

## 1. Summary of Change

A **skip-empty guard** was added to `IESExpensesController::store()` (used by both store and update) so that delete+recreate runs only when the **IES Expenses** section (nested: parent + children) is **meaningfully filled** per the nested guard spec.

- **Before:** Every call to `store()` that passed the budget guard deleted existing parent and child rows (if any), then recreated parent and children from request data. If the request had no meaningful parent fields and no meaningful child rows, existing expense data was wiped and replaced with empty or default data → **data loss**.
- **After:** After building `$headerData` and before `DB::beginTransaction()`, the controller builds `$parentData` (the five parent header fields), normalizes `$particulars` and `$amounts` to arrays, then calls `isIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)`. If it returns **false**, the method logs and returns `response()->json(['message' => 'IES estimated expenses saved successfully.'], 200)` without starting a transaction, deleting, or creating. Existing data is unchanged. If the section has meaningful data, behaviour is **unchanged** (transaction, delete existing, create parent, create children, sync).

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: parent totals + particulars/amounts with data | Delete existing, create parent + children, sync. | **Same.** |
| No parent fields + no children in request (all null/empty arrays) | Delete existing, create parent with nulls and no details → **data loss**. | **Skip:** no transaction, no delete, no create; return 200 with message. |
| Parent all null/blank + children empty | Delete existing, create empty parent + no details → **data loss**. | **Skip:** no transaction, no delete, no create; return 200. |
| One meaningful child row (particular or amount) | Delete then create. | **Same.** |
| Parent has at least one meaningful numeric (e.g. total_expenses) but no children | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/IES/IESExpensesController.php`

**3.1 Insertion in `store()` (after `$headerData`, before `DB::beginTransaction()`):**

```php
        $headerData = ArrayToScalarNormalizer::forFillable($data, $fillableHeader);

+       $parentData = [
+           'total_expenses' => $headerData['total_expenses'] ?? null,
+           'expected_scholarship_govt' => $headerData['expected_scholarship_govt'] ?? null,
+           'support_other_sources' => $headerData['support_other_sources'] ?? null,
+           'beneficiary_contribution' => $headerData['beneficiary_contribution'] ?? null,
+           'balance_requested' => $headerData['balance_requested'] ?? null,
+       ];
+       $particulars = is_array($data['particulars'] ?? null) ? $data['particulars'] : [];
+       $amounts = is_array($data['amounts'] ?? null) ? $data['amounts'] : [];
+
+       if (! $this->isIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)) {
+           Log::info('IESExpensesController@store - Section absent or empty; skipping mutation', [
+               'project_id' => $projectId,
+           ]);
+           return response()->json(['message' => 'IES estimated expenses saved successfully.'], 200);
+       }
+
        DB::beginTransaction();
```

**3.2 New private methods (end of class):**

- `isIESExpensesMeaningfullyFilled(array $parentData, array $particulars, array $amounts): bool` — returns true if any parent value is meaningful (via `meaningfulNumeric`) or any child row has meaningful particular (string) or amount (numeric); otherwise false.
- `meaningfulString($value): bool` — `is_string($value) && trim($value) !== ''`.
- `meaningfulNumeric($value): bool` — `$value !== null && $value !== '' && is_numeric($value)`.

Existing delete logic, transaction (begin/commit/rollBack), response messages, normalization (ArrayToScalarNormalizer and the create-loop handling of particulars/amounts), `update()` delegation, and `destroy()` were **not** modified.

---

## 4. Manual Test Cases

1. **Full payload → delete+recreate runs (unchanged)**  
   Submit store/update with parent totals and at least one row in `particulars` / `amounts`.  
   **Expect:** Existing expenses deleted, parent + children created, sync run, 200 with "IES estimated expenses saved successfully."

2. **No parent + no children in request → skip**  
   Submit with all five parent keys null/absent and `particulars` / `amounts` empty or absent.  
   **Expect:** No transaction, no delete, no create. 200 with same message. Log: "Section absent or empty; skipping mutation".

3. **Parent all blank + children empty → skip**  
   Submit with parent fields null/blank and particulars/amounts `[]`.  
   **Expect:** Skip; no transaction, no delete, no create; 200.

4. **One meaningful child → execute**  
   Same as (3) but one index has non-empty particular or non-null numeric amount.  
   **Expect:** Guard returns true; transaction and delete+recreate run; 200.

5. **Parent has meaningful numeric, no children → execute**  
   Submit with e.g. `total_expenses` = 50000 and particulars/amounts empty.  
   **Expect:** Guard returns true; transaction and delete+recreate run; 200.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true when any parent field is meaningful (numeric, including 0) or any child row has meaningful particular or amount; full payloads unchanged. |
| Parent/child keys mismatch | Parent data uses the same five header keys used by `fill($headerData)`; particulars/amounts normalized to arrays; guard logic mirrors IIESExpensesController. |
| update() path | update() delegates to store(); guard runs in store() so both paths protected. |
| Response format | Early return uses the exact same JSON message and 200 as success path. |
| Transaction behaviour | Guard returns before `DB::beginTransaction()`; when guard passes, transaction flow is unchanged. |

---

## 6. Confirmation: Only IESExpensesController Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/IES/IESExpensesController.php`**

No changes were made to validation logic, FormRequests, database schema, transaction structure, other controllers, budget lock behaviour, normalization logic (ArrayToScalarNormalizer), field names, or `destroy()`.

---

*End of M1 IES Expenses Guard Implementation.*
