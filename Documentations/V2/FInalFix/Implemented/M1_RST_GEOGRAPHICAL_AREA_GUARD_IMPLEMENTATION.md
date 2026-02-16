# M1 — RST Geographical Area Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/RST/GeographicalAreaController.php` ONLY.

---

## 1. Summary of Change

A **skip-empty guard** was added to `GeographicalAreaController::store()` so that delete+recreate runs only when the **RST Geographical Area** section (multi-row, four parallel arrays: `mandal`, `village`, `town`, `no_of_beneficiaries`) is **meaningfully filled**.

- **Before:** Every call to `store()` (and thus `update()`) deleted all existing `ProjectRSTGeographicalArea` rows for the project, then recreated rows from the request. If the request had no rows or only empty/null values, existing data was wiped with nothing meaningful written → **data loss**.
- **After:** After normalizing the four arrays, the controller calls `isGeographicalAreaMeaningfullyFilled($mandals, $villages, $towns, $noOfBeneficiaries)`. If it returns **false**, the method logs and returns `response()->json(['message' => 'Geographical Areas saved successfully.'], 200)` without starting a transaction, deleting, or creating. Existing data is unchanged. If the section has at least one meaningful value in any row, behaviour is **unchanged** (transaction, delete, recreate).

Validation, transaction structure, response format, success message, normalization, delete/create logic, and `destroy()` were not modified.

---

## 2. Before vs After

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: one or more rows with data | Delete existing, create rows, 200. | **Same.** |
| Section absent or all arrays empty | Delete existing, create zero rows → **data loss**. | **Skip:** no transaction, no delete, no create; return 200 with same message. |
| Arrays have rows but every cell null/empty | Delete existing, create rows with nulls → **data loss**. | **Skip:** no mutation; return 200. |
| At least one row has one non-empty string or numeric | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/RST/GeographicalAreaController.php`

**3.1 Insertion in `store()` (after normalization, before `DB::beginTransaction()`):**

```php
        $noOfBeneficiaries = is_array($data['no_of_beneficiaries'] ?? null) ? ... : [];

+       if (! $this->isGeographicalAreaMeaningfullyFilled($mandals, $villages, $towns, $noOfBeneficiaries)) {
+           Log::info('GeographicalAreaController@store - Section absent or empty; skipping mutation', [
+               'project_id' => $projectId,
+           ]);
+           return response()->json(['message' => 'Geographical Areas saved successfully.'], 200);
+       }
+
        DB::beginTransaction();
```

**3.2 New private methods (end of class):**

```php
    private function isGeographicalAreaMeaningfullyFilled(
        array $mandals,
        array $villages,
        array $towns,
        array $noOfBeneficiaries
    ): bool {
        if ($mandals === []) {
            return false;
        }

        foreach ($mandals as $index => $mandal) {
            $mandalVal = is_array($mandal ?? null) ? (reset($mandal) ?? null) : ($mandal ?? null);
            $villagesVal = is_array($villages[$index] ?? null) ? (reset($villages[$index]) ?? null) : ($villages[$index] ?? null);
            $townVal = is_array($towns[$index] ?? null) ? (reset($towns[$index]) ?? null) : ($towns[$index] ?? null);
            $noOfBeneficiariesVal = is_array($noOfBeneficiaries[$index] ?? null) ? (reset($noOfBeneficiaries[$index]) ?? null) : ($noOfBeneficiaries[$index] ?? null);

            if ($this->meaningfulString($mandalVal) || $this->meaningfulNumeric($mandalVal)
                || $this->meaningfulString($villagesVal) || $this->meaningfulNumeric($villagesVal)
                || $this->meaningfulString($townVal) || $this->meaningfulNumeric($townVal)
                || $this->meaningfulString($noOfBeneficiariesVal) || $this->meaningfulNumeric($noOfBeneficiariesVal)) {
                return true;
            }
        }

        return false;
    }

    private function meaningfulString($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function meaningfulNumeric($value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }
```

---

## 4. Manual Test Cases

1. **Full payload → delete+recreate (unchanged)**  
   Submit store/update with at least one row (e.g. `mandal`, `village`, `town`, `no_of_beneficiaries` as arrays with values).  
   **Expect:** Existing geographical area rows deleted, new rows created, 200 with "Geographical Areas saved successfully."

2. **Section absent / all arrays empty → skip**  
   Submit with the four keys absent or all normalized to empty arrays.  
   **Expect:** No transaction, no delete, no create. 200 with same message. Log: "Section absent or empty; skipping mutation".

3. **One row, all fields null/empty → skip**  
   Submit with one index but all four values null or empty string.  
   **Expect:** Guard false; skip mutation; 200.

4. **One meaningful value in one row → execute**  
   Same as (3) but one field has e.g. a non-empty string or a number.  
   **Expect:** Guard true; transaction, delete, recreate; 200.

5. **Multiple rows, only one cell filled → execute**  
   Submit several rows with only one cell meaningful.  
   **Expect:** Guard true; full delete+recreate; 200.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true if any row (by primary array `mandals`) has any meaningful string (trim !== '') or numeric value; normal payloads unchanged. |
| Row structure mismatch | Scalar extraction in the guard matches the create loop (same index and reset() logic). |
| update() path | update() delegates to store(); guard runs in store(), so both paths protected. |
| Response format | Early return uses the exact same JSON and 200 as the success path. |
| Transaction behaviour | Guard returns before `DB::beginTransaction()`; when guard passes, transaction and delete/recreate logic are unchanged. |

---

## 6. Confirmation: Only This Controller Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/RST/GeographicalAreaController.php`**

No changes were made to validation, FormRequests, routes, schema, `destroy()`, other controllers, or any other file.

---

*End of M1 RST Geographical Area Guard Implementation.*
