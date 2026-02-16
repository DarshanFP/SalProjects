# M1 — IGE New Beneficiaries Skip-Empty-Section Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php` ONLY.

---

## 1. Summary of Change

A **skip-empty guard** was added to `NewBeneficiariesController::store()` so that delete+recreate runs only when the **New Beneficiaries** section is **meaningfully filled** (at least one row with a non-empty trimmed string in any of the five parallel fields).

- **Before:** Every call to `store()` deleted all existing new beneficiaries, then recreated from request arrays. If `beneficiary_name` was missing, empty array, or all rows were blank, existing data was wiped and no new rows were created → **data loss**.
- **After:** After normalizing the five arrays and **before** any transaction or delete, the controller calls `isIGENewBeneficiariesMeaningfullyFilled(...)`. If it returns **false**, the method logs and returns without starting a transaction or deleting: when `$shouldRedirect` is true it returns `redirect()->back()->with('success', 'New beneficiaries saved successfully.')`; when called from update (no redirect) it returns `true`. Existing data is unchanged. If the section has meaningful data, behaviour is **unchanged** (transaction, delete, create loop).

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: at least one row with non-empty beneficiary_name (and optional other fields) | Delete existing, create rows. | **Same.** |
| beneficiary_name key missing | Delete existing, create nothing → **data loss**. | **Skip:** no transaction, no delete, no create; return success (redirect or true). |
| beneficiary_name empty array [] | Delete existing, create nothing → **data loss**. | **Skip:** no transaction, no delete, no create; return success. |
| All rows empty strings / null / whitespace | Delete existing, create nothing → **data loss**. | **Skip:** no transaction, no delete, no create; return success. |
| One row with meaningful string in any of the five fields | Delete then create. | **Same.** |

---

## 3. Code Insertion Snippet

**File:** `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php`

**3.1 Guard block (after normalized arrays, before transaction check):**

```php
        $familyBackgroundNeeds = is_array($data['family_background_need'] ?? null) ? ... : [];

        if (! $this->isIGENewBeneficiariesMeaningfullyFilled(
            $beneficiaryNames,
            $castes,
            $addresses,
            $groupYearOfStudies,
            $familyBackgroundNeeds
        )) {
            Log::info('IGENewBeneficiariesController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            if ($shouldRedirect) {
                return redirect()->back()->with('success', 'New beneficiaries saved successfully.');
            }
            return true;
        }

        // Check if we're already in a transaction (called from ProjectController@update)
        $inTransaction = DB::transactionLevel() > 0;
```

**3.2 New private methods (end of class):**

- `isIGENewBeneficiariesMeaningfullyFilled(array $beneficiaryNames, array $caste, array $address, array $groupYearOfStudy, array $familyBackgroundNeed): bool` — returns false if `$beneficiaryNames === []`; otherwise computes `$maxIndex = max(count(...))` of the five arrays and loops `$i = 0` to `$maxIndex - 1`; returns true if any index has `meaningfulString()` true for any of the five values; otherwise false.
- `meaningfulString($value): bool` — `is_string($value) && trim($value) !== ''`.

---

## 4. Guard Logic Explanation

- **Section key:** `beneficiary_name`. If the normalized array is empty, the guard returns false (section absent or empty).
- **Meaningful row:** A row (index `$i`) is meaningful if **any** of the five parallel arrays has a value at that index that is a non-empty trimmed string (`meaningfulString`).
- **Guard returns false** when: section key is missing (array becomes `[]`), section key is empty array, or every row has all five values empty/null/non-string (so no meaningful string in any field).
- **Guard returns true** when: at least one row has at least one field that is a non-empty trimmed string. Then the existing delete+recreate logic runs as before.
- **Response when guard blocks:** Success message is preserved: redirect with `'success', 'New beneficiaries saved successfully.'` when called standalone; `return true` when called from `update()` so ProjectController’s contract is unchanged.

---

## 5. Manual Test Cases

1. **Full payload → delete+recreate runs (unchanged)**  
   Submit store/update with at least one row where `beneficiary_name` (or another field) is a non-empty string.  
   **Expect:** Existing new beneficiaries deleted, new rows created, then redirect to edit (or return true from update).

2. **beneficiary_name key missing → skip**  
   Submit with no `beneficiary_name` (or key absent).  
   **Expect:** No transaction, no delete, no create. Log: "Section absent or empty; skipping mutation". Return redirect with success when standalone; return true when from update.

3. **beneficiary_name empty array → skip**  
   Submit with `beneficiary_name => []`.  
   **Expect:** Skip; no transaction, no delete, no create; same success response.

4. **All rows blank → skip**  
   Submit with all five arrays containing only empty strings, null, or whitespace.  
   **Expect:** Skip; no transaction, no delete, no create; same success response.

5. **One meaningful field in one row → execute**  
   Same arrays but at least one index has e.g. non-empty `caste` or `address`.  
   **Expect:** Guard returns true; transaction (if applicable), delete, and create loop run; existing behaviour.

---

## 6. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Full submit incorrectly skipped | Guard returns true when any of the five arrays has at least one non-empty trimmed string at some index; full payloads with at least one valid row are unchanged. |
| update() path broken | When guard blocks and `$shouldRedirect` is false (update path), controller returns `true` so ProjectController still receives success. |
| Transaction behaviour | Guard runs **before** `$inTransaction` check and `DB::beginTransaction()`; when guard skips, no transaction is started. When guard passes, transaction logic is unchanged. |
| Response format | Standalone: redirect with same success message. Update: return true. No change to error responses or redirect targets. |

---

## 7. Confirmation: Only This Controller Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php`**

No changes were made to:

- Validation classes (StoreIGENewBeneficiariesRequest, UpdateIGENewBeneficiariesRequest)
- ProjectController or any other controller
- Transaction wiring (conditional begin/commit/rollBack and `transactionLevel()` check unchanged)
- Create loop or redirect behaviour (other than the guard’s early return)
- Routes or models

---

*End of M1 IGE New Beneficiaries Guard Implementation.*
