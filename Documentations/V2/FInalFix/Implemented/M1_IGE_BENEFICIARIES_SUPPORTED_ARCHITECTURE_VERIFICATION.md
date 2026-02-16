# M1 — IGE BeneficiariesSupported Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target file:** `app/Http/Controllers/Projects/IGE/IGEBeneficiariesSupportedController.php`  
**Scope:** Single file only. No code modified.

---

## 1. Structural Summary

- **store()** (lines 16–51): Takes `FormRequest` and `$projectId`. Uses `$request->only($fillable)` for `class` and `total_number`. Normalizes both to arrays (`$classes`, `$totalNumbers`). Starts a transaction, deletes all existing rows for the project, then loops over `$classes` and creates rows when both `$classVal` and `$totalNum` are non-null. Commits and redirects on success; rollBack and redirect on exception.
- **update()** (lines 96–100): Delegates to `store()` with `return $this->store($request, $projectId)`.
- **destroy()** (lines 104–120): Starts a transaction, deletes all rows for the project, commits, redirects on success; rollBack and redirect on exception.
- **show()** / **edit()**: Read-only; fetch by `project_id` and return collection or null/empty collection.

**Delete pattern:** Unconditional `ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->delete()` inside the try block, followed by a create loop. No check that the section has meaningful data before the delete.

**Section driver key:** `class`. The loop is `foreach ($classes as $index => $classVal)`. Row count is driven by the length of `$classes`.

**Parallel arrays:** `$classes` (from `class`) and `$totalNumbers` (from `total_number`). Both are normalized with the same scalar-to-array pattern; missing or non-array values become `[]` or a single-element array.

**Row create condition:** `if ($classVal !== null && $totalNum !== null)` — both class and total_number must be non-null for a row to be created.

---

## 2. Delete Pattern Description

1. **Normalization:** `$classes` and `$totalNumbers` are built from `$data['class']` and `$data['total_number']` with `is_array(...) ? ... : (isset(...) ? [...] : [])`.
2. **Transaction:** `DB::beginTransaction()` is called (no `transactionLevel()` check).
3. **Delete:** `ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->delete()` runs unconditionally inside the try block.
4. **Recreate:** `foreach ($classes as $index => $classVal)`; for each index, if both `$classVal` and `$totalNum` are non-null, `create()` is called.
5. **Commit / response:** `DB::commit()`, then redirect with success message.

There is no guard or early return before the delete. Deletion is always scoped by `project_id`.

---

## 3. Empty Section Behavior

- **Section key (`class`) missing from request:**  
  `$data['class']` is absent, so `$data['class'] ?? null` is `null`. The normalization yields `$classes = []`. The delete at line 30 **still runs**. The foreach runs zero times, so no rows are created. **Result: all existing beneficiaries supported for the project are removed; data loss.**

- **Arrays present but empty (`class => []`, `total_number => []`):**  
  `$classes = []`, so the loop runs zero times. The delete has already run. **Result: same as above; data loss.**

- **Arrays present but all values null:**  
  The loop runs (e.g. for three nulls), but `$classVal !== null && $totalNum !== null` is false for each index, so no creates. The delete has already run. **Result: data loss.**

There is no protection against empty or absent section: delete always executes before any check on row content.

---

## 4. Transaction Behavior

- **store():** Uses `DB::beginTransaction()` before the try block. The delete and all creates are inside the try block. On success, `DB::commit()` is called; on exception, `DB::rollBack()` and redirect with error. There is no early return before the transaction or before the delete.
- **destroy():** Uses its own `DB::beginTransaction()`, try, delete, `DB::commit()`, and in catch `DB::rollBack()`. No nesting with store(); it is a separate entry point for “delete all beneficiaries supported” for the project.

---

## 5. Response Behavior

- **Success:** Redirect only (no JSON). `redirect()->route('projects.edit', $projectId)->with('success', 'Beneficiaries supported saved successfully.')`.
- **Error:** `redirect()->back()->with('error', 'Failed to save IGE beneficiaries supported.')`.
- No explicit HTTP status code in the controller; redirects are standard Laravel (e.g. 302).
- No 403 or budget-lock logic in this controller; authorization is assumed to be handled by FormRequest/routing.
- Exceptions are caught in try/catch; the controller does not rethrow; it returns redirects.

---

## 6. Data Loss Risk Classification

**C) High Data Loss Risk (unconditional delete + empty recreate)**

**Reasoning:**

- Delete is unconditional and runs before any validation of whether the section has meaningful rows.
- If `class` is missing, empty array, or all values are null (and corresponding `total_number` null), the delete still runs and the create loop adds zero rows.
- The same M1 vulnerability as other delete-then-recreate section controllers: an empty or absent section triggers a full wipe of existing data with no replacement.

---

## 7. Verdict

**NEEDS GUARD**

The controller is vulnerable to empty-section delete data loss. A skip-empty guard (e.g. before `DB::beginTransaction()`) should ensure that when the section key is absent, the arrays are empty, or all rows are null/empty, the method does not start a transaction or run the delete, and returns the same success response (e.g. redirect with success message) without mutating data.

---

## 8. Confirmation

No code was changed. This document is a read-only architectural verification. No guard has been implemented. Only the single file `app/Http/Controllers/Projects/IGE/IGEBeneficiariesSupportedController.php` was analyzed.

---

*End of M1 IGE BeneficiariesSupported Architecture Verification.*
