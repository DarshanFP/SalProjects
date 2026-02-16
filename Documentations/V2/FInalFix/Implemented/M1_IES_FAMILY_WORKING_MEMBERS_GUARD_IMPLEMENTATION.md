# M1 — IES Family Working Members Skip-Empty-Section Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php` ONLY.

---

## 1. Risk Level Before Fix

**High.** Empty or absent section (missing `member_name` key, empty array, or all rows empty/null) caused unconditional delete then zero creates → **data loss**.

---

## 2. Summary of Change

A **skip-empty guard** was added to `IESFamilyWorkingMembersController::store()` (used by both store and update) so that delete+recreate runs only when the **Family Working Members** section is **meaningfully filled**.

- **Before:** Every call to `store()` deleted all existing family working members, then recreated from request arrays. If `member_name` was missing, empty array, or all rows were empty/null, existing data was wiped and no new rows were created → **data loss**.
- **After:** After normalizing `$memberNames`, `$workNatures`, and `$monthlyIncomes` from `$data`, and **before** `DB::beginTransaction()`, the controller calls `isIESFamilyWorkingMembersMeaningfullyFilled($memberNames, $workNatures, $monthlyIncomes)`. If it returns **false**, the method logs and returns `response()->json(['message' => 'Family working members saved successfully.'], 200)` without starting a transaction, deleting, or creating. Existing data is unchanged. If the section has meaningful data (at least one row with a non-empty name, work nature, or numeric income), behaviour is **unchanged** (transaction, delete existing, create loop).

---

## 3. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| Full payload: member_name, work_nature, monthly_income with at least one valid row | Delete existing, create rows. | **Same.** |
| member_name key missing | Delete existing, create nothing → **data loss**. | **Skip:** no transaction, no delete, no create; return 200 with message. |
| member_name empty array [] | Delete existing, create nothing → **data loss**. | **Skip:** no transaction, no delete, no create; return 200. |
| All rows empty strings / null | Delete existing, create nothing → **data loss**. | **Skip:** no transaction, no delete, no create; return 200. |
| One row with meaningful name, work_nature, or monthly_income | Delete then create. | **Same.** |

---

## 4. Code Changes (Snippet)

**File:** `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`

**4.1 Before transaction:** Array normalization was moved earlier so the guard can use it. Guard block added before `DB::beginTransaction()`:

- Normalize `$memberNames`, `$workNatures`, `$monthlyIncomes` from `$data` (same logic as before).
- Call `isIESFamilyWorkingMembersMeaningfullyFilled($memberNames, $workNatures, $monthlyIncomes)`.
- If false: `Log::info('IESFamilyWorkingMembersController@store - Section absent or empty; skipping mutation', ['project_id' => $projectId])`, then `return response()->json(['message' => 'Family working members saved successfully.'], 200)`.
- Duplicate array assignment inside the try block was removed; the loop uses the arrays already set above.

**4.2 New private methods (end of class):**

- `isIESFamilyWorkingMembersMeaningfullyFilled(array $memberNames, array $workNatures, array $monthlyIncomes): bool` — returns false if `$memberNames === []`; otherwise loops over indices up to `max(count-1)` of the three arrays and returns true if any row has `meaningfulString($name)` or `meaningfulString($work)` or `meaningfulNumeric($income)`; otherwise false.
- `meaningfulString($value): bool` — `is_string($value) && trim($value) !== ''`.
- `meaningfulNumeric($value): bool` — `$value !== null && $value !== '' && is_numeric($value)`.

Existing create-loop condition (`!empty($memberName) && !empty($workNature) && !empty($monthlyIncome)`), transaction (begin/commit/rollBack), response messages, `update()` delegation, and validation/FormRequests were **not** modified.

---

## 5. Manual Test Cases

1. **Full payload → delete+recreate runs (unchanged)**  
   Submit store/update with `member_name`, `work_nature`, `monthly_income` arrays containing at least one full row (non-empty name, work nature, and monthly income).  
   **Expect:** Existing members deleted, new rows created, 200 with "Family working members saved successfully."

2. **member_name key missing → skip**  
   Submit with no `member_name` (or key absent).  
   **Expect:** No transaction, no delete, no create. 200 with same message. Log: "Section absent or empty; skipping mutation".

3. **member_name empty array → skip**  
   Submit with `member_name => []` (and empty or absent work_nature / monthly_income).  
   **Expect:** Skip; no transaction, no delete, no create; 200.

4. **All rows empty/null → skip**  
   Submit with e.g. `member_name => ['', '', '']`, same for work_nature and monthly_income.  
   **Expect:** Skip; no transaction, no delete, no create; 200.

5. **One meaningful row → execute**  
   Same as (4) but at least one index has a non-empty string for name or work_nature, or a numeric monthly_income.  
   **Expect:** Guard returns true; transaction and delete+recreate run; 200.

---

## 6. Confirmation: Only IESFamilyWorkingMembersController Modified

Only the following file was modified:

- **`app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php`**

No changes were made to validation classes (StoreIESFamilyWorkingMembersRequest, UpdateIESFamilyWorkingMembersRequest), other controllers, routes, or models.

---

## 7. Confirmation: update() Remains Delegating to store()

`update(FormRequest $request, $projectId)` still contains only:

```php
return $this->store($request, $projectId);
```

The guard runs inside `store()`, so both store and update paths are protected.

---

## 8. Confirmation: Transaction Logic Unchanged

- When the guard **skips:** no `DB::beginTransaction()` is called; no delete or create runs.
- When the guard **passes:** `DB::beginTransaction()` is called, then the existing try block runs (project fetch, delete, loop create), `DB::commit()`, and success JSON. On exception, `DB::rollBack()` and 500 JSON. No change to transaction structure.

---

## 9. Confirmation: Response Type Unchanged (JSON)

- Success (full submit): `response()->json(['message' => 'Family working members saved successfully.'], 200)` — unchanged.
- Success (guard skip): `response()->json(['message' => 'Family working members saved successfully.'], 200)` — same format and status.
- Error: `response()->json(['error' => 'Failed to save family working members.'], 500)` — unchanged.

---

*End of M1 IES Family Working Members Guard Implementation.*
