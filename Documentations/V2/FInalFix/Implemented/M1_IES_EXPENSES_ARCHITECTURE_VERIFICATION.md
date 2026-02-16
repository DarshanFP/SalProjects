# M1 IES Expenses Architecture Verification

**Purpose:** Read-only verification of whether `IESExpensesController` is architecturally equivalent to `IIESExpensesController` (delete-then-recreate pattern, structure, delegation, response, normalization, transactions, empty-section data loss risk).

**Scope:**  
- `app/Http/Controllers/Projects/IES/IESExpensesController.php`  
- `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`  

**Date:** 2026-02-14  
**Mode:** Documentation only. No code changes.

---

## 1. Controller structure summary

### IESExpensesController

| Aspect | Detail |
|--------|--------|
| **Models** | `ProjectIESExpenses` (parent), `ProjectIESExpenseDetail` (child) |
| **Parent key** | `project_id` (scoped by `project_id`) |
| **Child relationship** | `expenseDetails()` → `ProjectIESExpenseDetail` via `IES_expense_id` |
| **store()** | Delete existing parent+children, create new parent, create children from arrays |
| **update()** | Delegates to `store()`: `return $this->store($request, $projectId);` |
| **Response** | JSON: `['message' => 'IES estimated expenses saved successfully.']` (200), or `['error' => ...]` (403/500) |
| **Transaction** | `DB::beginTransaction()` / `commit()` / `rollBack()` in `store()` and `destroy()` |
| **Budget lock** | `BudgetSyncGuard::canEditBudget($project)` → 403 JSON; no exception |

### IIESExpensesController

| Aspect | Detail |
|--------|--------|
| **Models** | `ProjectIIESExpenses` (parent), `ProjectIIESExpenseDetail` (child) |
| **Parent key** | `project_id` (scoped by `project_id`) |
| **Child relationship** | `expenseDetails()` → `ProjectIIESExpenseDetail` via `IIES_expense_id` |
| **store()** | After guard: delete existing parent+children, create new parent, create children from arrays |
| **update()** | Delegates to `store()`: `return $this->store($request, $projectId);` |
| **Response** | JSON: `['message' => 'IIES estimated expenses saved successfully.']` (200), or `['error' => ...]` (403) |
| **Transaction** | **None** in `store()`. `destroy()` has no transaction. |
| **Budget lock** | `BudgetSyncGuard::canEditBudget($project)` → throws `HttpResponseException` with 403 JSON |
| **Empty-section guard** | `isIIESExpensesMeaningfullyFilled()` — if not meaningful, returns 200 without mutating |

---

## 2. Parent field list

### IESExpensesController (from model fillable, excluding `project_id`, `IES_expense_id`)

- `total_expenses`
- `expected_scholarship_govt`
- `support_other_sources`
- `beneficiary_contribution`
- `balance_requested`

**Count:** 5 parent fields.  
**Source:** `$fillableHeader = array_diff((new ProjectIESExpenses())->getFillable(), ['project_id', 'IES_expense_id'])`; then `ArrayToScalarNormalizer::forFillable($data, $fillableHeader)`.

### IIESExpensesController

- `iies_total_expenses`
- `iies_expected_scholarship_govt`
- `iies_support_other_sources`
- `iies_beneficiary_contribution`
- `iies_balance_requested`

**Count:** 5 parent fields.  
**Source:** Explicit array from `$validated` in `store()`.

**Comparison:** Same number of parent fields (5). Naming differs (no prefix vs `iies_` prefix); semantic mapping is 1:1.

---

## 3. Child field list

### IESExpensesController

| Request keys | Detail model attributes |
|--------------|-------------------------|
| `particulars` (array) | `particular` |
| `amounts` (array)     | `amount`   |

Child creation: only when both `!empty($particular)` and `!empty($amount)`; scalar/array normalization applied (e.g. `reset($particulars[$i])` for nested values).

### IIESExpensesController

| Request keys | Detail model attributes |
|--------------|-------------------------|
| `iies_particulars` (array) | `iies_particular` |
| `iies_amounts` (array)     | `iies_amount`     |

Child creation: when `!empty($particular)` and `isset($amounts[$index])` and amount not null/empty.

**Comparison:** Same structure (two arrays → rows of two columns). Naming differs (`particulars`/`amounts` vs `iies_particulars`/`iies_amounts`; `particular`/`amount` vs `iies_particular`/`iies_amount`).

---

## 4. Delete pattern description

### IESExpensesController

- **Location:** `store()` and `destroy()`.
- **Pattern:**  
  1. `ProjectIESExpenses::where('project_id', $projectId)->first()`  
  2. If record exists: `$existingExpenses->expenseDetails()->delete();` then `$existingExpenses->delete();`
- **Unconditional in store():** Yes. No check for “section empty or absent”; delete runs whenever `store()` passes the budget guard and reaches the mutation block.
- **Empty guard:** None. No “skip mutation when section is empty/absent” logic.

### IIESExpensesController

- **Location:** `store()` (after guard) and `destroy()`.
- **Pattern:**  
  1. `ProjectIIESExpenses::where('project_id', $projectId)->first()`  
  2. If record exists: `$existingExpenses->expenseDetails()->delete();` then `$existingExpenses->delete();`
- **Conditional in store():** Yes. Delete (and recreate) runs only when `isIIESExpensesMeaningfullyFilled($parentData, $particulars, $amounts)` is true. Otherwise returns 200 with success message and **no** delete.
- **Empty guard:** Yes. “M1 Data Integrity Shield”: skip delete+recreate when section is absent or empty.

**Comparison:** Delete order (children then parent) and “first() then delete” pattern are the same. Only IIES has an empty-section guard before any delete.

---

## 5. Data loss risk assessment

### IESExpensesController

| Scenario | Result |
|----------|--------|
| All parent fields null, child arrays empty | **Data loss risk.** store() still runs delete then creates a new parent (with null/empty header) and zero children. Existing expenses are wiped. |
| Section key absent / empty payload | Same as above if request still hits store() and passes budget guard: delete runs, then recreate with whatever is in request (possibly empty). |
| Empty child arrays only | Delete runs; new parent created; no detail rows. Existing details lost. |

**Verdict:** IES has **no** empty-section guard. Submitting an empty or absent section can wipe existing IES expense data.

### IIESExpensesController

| Scenario | Result |
|----------|--------|
| All parent fields null/empty, child arrays empty | **No data loss.** `isIIESExpensesMeaningfullyFilled()` false → store() returns 200 without deleting or creating. |
| Section key absent / empty payload | Normalized/validated arrays empty, parent data empty → guard false → no mutation. |
| Empty child arrays, parent all null | Guard false → no mutation. |

**Verdict:** IIES has an empty-section guard; empty or absent section does not wipe existing data.

---

## 6. Differences from IIESExpensesController

| Dimension | IESExpensesController | IIESExpensesController |
|-----------|------------------------|-------------------------|
| **Parent field count** | 5 | 5 |
| **Parent field names** | No prefix (`total_expenses`, etc.) | `iies_` prefix |
| **Child array names** | `particulars`, `amounts` | `iies_particulars`, `iies_amounts` |
| **Detail attribute names** | `particular`, `amount` | `iies_particular`, `iies_amount` |
| **update()** | Delegates to store() | Delegates to store() |
| **Response type** | JSON | JSON |
| **Success message** | “IES estimated expenses saved successfully.” | “IIES estimated expenses saved successfully.” |
| **Transaction in store()** | Yes (`DB::beginTransaction` / commit / rollBack) | **No** |
| **Transaction in destroy()** | Yes | No |
| **Budget locked handling** | `return response()->json(..., 403)` | `throw HttpResponseException(response()->json(..., 403))` |
| **Empty-section guard** | **None** | **Present** (`isIIESExpensesMeaningfullyFilled`) |
| **Normalization** | `ArrayToScalarNormalizer::forFillable` + manual array handling | FormRequest `getNormalizedInput()` + Validator |
| **destroy() when no record** | `first()` → no-op, returns 200 | `firstOrFail()` → 404 if no record |

---

## 7. Final verdict

**STRUCTURALLY DIFFERENT**

- **Same:** Delete-then-recreate pattern, parent + child structure, 5 parent fields, update() delegating to store(), JSON response shape, same delete order (children then parent).
- **Different:**
  1. **Empty-section guard:** IIES has it; IES does not. This is a major behavioral difference for data loss risk.
  2. **Transaction usage:** IES wraps store() and destroy() in DB transactions; IIES does not.
  3. **Budget lock:** IES returns 403; IIES throws HttpResponseException with 403.
  4. **destroy() when no record:** IES no-op 200; IIES firstOrFail() → 404.
  5. **Normalization:** IES uses ArrayToScalarNormalizer + fillable; IIES uses FormRequest normalization + validation.

So the two controllers are **not** architecturally equivalent: structure and flow are similar, but IES lacks the empty-section guard and uses transactions where IIES does not, and error/edge handling differs.

---

*End of verification document. No code was modified.*
