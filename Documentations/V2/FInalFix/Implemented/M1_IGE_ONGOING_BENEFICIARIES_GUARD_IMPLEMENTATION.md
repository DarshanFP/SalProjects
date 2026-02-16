# M1 — IGE Ongoing Beneficiaries Skip-Empty-Section Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target file path:** `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`

---

## 1. Summary

A **skip-empty guard** was added to `OngoingBeneficiariesController::store()` so that delete+recreate runs only when the **Ongoing Beneficiaries** section has at least one meaningful beneficiary name (non-empty trimmed string). The guard runs **before** `DB::beginTransaction()` and uses the section key **obeneficiary_name** only.

---

## 2. Before vs After

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: at least one non-empty trimmed `obeneficiary_name` | Delete existing, create rows, redirect success. | **Same.** |
| `obeneficiary_name` absent | Delete existing, create nothing → data loss. | **Skip:** no transaction, no delete, no create; return same success redirect. |
| `obeneficiary_name` empty array `[]` | Delete existing, create nothing → data loss. | **Skip:** no transaction, no delete, no create; return same success redirect. |
| All names null or empty after trim | Delete existing, create nothing (or only null rows) → data loss. | **Skip:** no transaction, no delete, no create; return same success redirect. |
| One name non-empty trimmed string | Delete then create. | **Same.** |

---

## 3. Code Diff Snippet

**File:** `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`

**3.1 Guard block (after normalized arrays, before `DB::beginTransaction()`):**

```php
        $operformanceDetails = is_array($data['operformance_details'] ?? null) ? ... : [];

        if (! $this->isIGEOngoingBeneficiariesMeaningfullyFilled($obeneficiaryNames)) {
            Log::info('IGEOngoingBeneficiariesController@store - Section absent or empty; skipping mutation', [
                'project_id' => $projectId,
            ]);

            return redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries saved successfully.');
        }

        DB::beginTransaction();
```

**3.2 New private methods (end of class):**

```php
    private function isIGEOngoingBeneficiariesMeaningfullyFilled(array $names): bool
    {
        if ($names === []) {
            return false;
        }
        foreach ($names as $name) {
            $val = is_array($name ?? null) ? (reset($name) ?? '') : ($name ?? '');
            if ($this->meaningfulString($val)) {
                return true;
            }
        }
        return false;
    }

    private function meaningfulString($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
```

- **Guard returns false** when: `obeneficiary_name` is absent (normalized to `[]`), or is `[]`, or every element is null/empty after trim (no element passes `meaningfulString`).
- **Guard returns true** when: at least one element of `$obeneficiaryNames` is a non-empty trimmed string (same scalar/array coercion as the create loop).
- **On skip:** Same redirect as normal success: `redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries saved successfully.')`.

---

## 4. Manual Test Cases

1. **Full payload with at least one non-empty name**  
   Submit store/update with `obeneficiary_name` (and parallel arrays) containing at least one non-empty trimmed string.  
   **Expect:** Transaction, delete, create loop, commit, redirect to `projects.edit` with success message.

2. **`obeneficiary_name` absent**  
   Submit with no `obeneficiary_name` key.  
   **Expect:** No transaction, no delete, no create. Log: "Section absent or empty; skipping mutation". Redirect to `projects.edit` with success message.

3. **`obeneficiary_name` empty array**  
   Submit with `obeneficiary_name => []`.  
   **Expect:** Skip; same log and same success redirect.

4. **All names null or empty/whitespace**  
   Submit with e.g. `obeneficiary_name => [null, '', '  ']`.  
   **Expect:** Skip; same log and same success redirect.

5. **One non-empty name**  
   Same as (4) but at least one element is a non-empty trimmed string.  
   **Expect:** Guard returns true; transaction, delete, create run; normal success redirect.

---

## 5. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Valid submit incorrectly skipped | Guard returns true when any name is a non-empty trimmed string; coercion matches the existing loop. |
| Redirect behaviour changed | On skip, the exact same redirect is used as on normal success: `redirect()->route('projects.edit', $projectId)->with('success', 'Ongoing Beneficiaries saved successfully.')`. |
| Transaction structure changed | Guard runs **before** `DB::beginTransaction()`; when guard passes, transaction logic is unchanged. No `transactionLevel()` logic added. |
| foreach / delete / messages changed | Not modified; only the guard and two private methods were added. |

---

## 6. Confirmation: Only This File Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php`**

No changes were made to:

- Method signatures (`store(FormRequest $request, $projectId)` unchanged)
- Validation, FormRequests, routes, or other controllers
- Transaction structure (begin/commit/rollBack unchanged)
- foreach loop, delete call, or success/error messages
- Redirect targets or flash keys

---

*End of M1 IGE Ongoing Beneficiaries Guard Implementation.*
