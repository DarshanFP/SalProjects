# M1 — LDP Target Group Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/LDP/TargetGroupController.php` ONLY.

---

## 1. Summary of Change

A **skip-empty guard** was added to `TargetGroupController::store()` (LDP) so that delete+recreate runs only when the **LDP Target Group** section (multi-row, four parallel arrays: `L_beneficiary_name`, `L_family_situation`, `L_nature_of_livelihood`, `L_amount_requested`) is **meaningfully filled**.

- **Before:** Every call to `store()` (and thus `update()`) deleted all existing `ProjectLDPTargetGroup` rows for the project, then recreated rows from the request. If the request had no rows or only empty/null values, existing data was wiped → **data loss**.
- **After:** After normalizing the four arrays, the controller calls `isLDPTargetGroupMeaningfullyFilled($beneficiaryNames, $familySituations, $natureOfLivelihoods, $amountRequested)`. If it returns **false**, the method logs and returns the **same** success redirect as the normal path: `redirect()->route('projects.edit', $projectId)->with('success', 'Target Group saved successfully.')` — without starting a transaction, deleting, or creating. Existing data is unchanged. If the section has at least one meaningful value in any row, behaviour is **unchanged** (transaction, delete, recreate, same redirect).

Validation, transaction structure, redirect routes, flash message text, delete/create loop, and `destroy()` were not modified. Response remains redirect-based; no JSON was introduced.

---

## 2. Before vs After

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: one or more rows with data | Delete existing, create rows, redirect with success. | **Same.** |
| Section absent or all arrays empty | Delete existing, create zero rows → **data loss**. | **Skip:** no transaction, no delete, no create; redirect with same success message. |
| Arrays have rows but every cell null/empty | Delete existing, create no rows (per-row null check) → **data loss**. | **Skip:** no mutation; redirect with success. |
| At least one row has one meaningful value | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/LDP/TargetGroupController.php`

**3.1 Insertion in `store()` (after normalization, before `DB::beginTransaction()`):**

```php
        $amountRequested = is_array($data['L_amount_requested'] ?? null) ? ... : [];

        if (! $this->isLDPTargetGroupMeaningfullyFilled($beneficiaryNames, $familySituations, $natureOfLivelihoods, $amountRequested)) {
            Log::info('LDPTargetGroupController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return redirect()
                ->route('projects.edit', $projectId)
                ->with('success', 'Target Group saved successfully.');
        }

        DB::beginTransaction();
```

**3.2 New private methods (end of class):**

- `isLDPTargetGroupMeaningfullyFilled(array $beneficiaryNames, array $familySituations, array $natureOfLivelihoods, array $amountRequested): bool` — returns false if all four arrays are empty; otherwise iterates by index (max length), extracts scalars the same way as the create loop, and returns true if any row has at least one meaningful string (name, family, nature) or meaningful numeric (amount); else false.
- `meaningfulString($value): bool` — `is_string($value) && trim($value) !== ''`.
- `meaningfulNumeric($value): bool` — `$value !== null && $value !== '' && is_numeric($value)`.

---

## 4. Manual Test Cases

1. **Full payload → delete+recreate (unchanged)**  
   Submit store/update with at least one row with data in one or more of the four fields.  
   **Expect:** Existing target group rows deleted, new rows created, redirect to `projects.edit` with success flash "Target Group saved successfully."

2. **Section absent / all arrays empty → skip**  
   Submit with the four keys absent or all normalized to empty arrays.  
   **Expect:** No transaction, no delete, no create. Same redirect and success message. Log: "Section absent or empty; skipping mutation".

3. **One row, all fields null/empty → skip**  
   Submit with one index but all four values null or empty string.  
   **Expect:** Guard false; skip mutation; redirect with success.

4. **One meaningful value in one row → execute**  
   Same as (3) but one field has e.g. a non-empty string or a number.  
   **Expect:** Guard true; transaction, delete, recreate; redirect with success.

5. **Multiple rows, only one cell filled → execute**  
   Submit several rows with only one meaningful cell.  
   **Expect:** Guard true; full delete+recreate; redirect with success.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true if any row (by index up to max length) has any meaningful string or numeric; normal payloads unchanged. |
| Row structure mismatch | Scalar extraction in the guard matches the create loop (same index and reset() logic). |
| update() path | update() delegates to store(); guard runs in store(), so both paths protected. |
| Response format | Early return uses the exact same redirect and flash message as the success path; no JSON introduced. |
| Transaction behaviour | Guard returns before `DB::beginTransaction()`; when guard passes, transaction and delete/recreate logic are unchanged. |

---

## 6. Confirmation: Only This Controller Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/LDP/TargetGroupController.php`**

No changes were made to validation, FormRequests, routes, schema, `destroy()`, other controllers, or any other file.

---

*End of M1 LDP Target Group Guard Implementation.*
