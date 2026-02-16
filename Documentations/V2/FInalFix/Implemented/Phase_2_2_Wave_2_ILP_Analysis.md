# Phase 2 — Wave 2 ILP Controllers — Architectural Analysis

**Date:** 2026-02-15  
**Type:** STRICT READ-ONLY — Analysis Only (No Code Changes)  
**Target:** 4 ILP controllers (RiskAnalysis, StrengthWeakness, RevenueGoals, Budget)

**Note:** Actual file paths use short names (e.g. `RiskAnalysisController.php`), not `ILPRiskAnalysisController.php`.

---

## 1. RiskAnalysisController

**Path:** `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php`

### Update Entry Path

- `update()` (lines 113–124) **calls** `$this->store()` and then:
  - If status 200: returns `['message' => 'Risk analysis updated successfully.', 'data' => $riskAnalysis]` (wraps store response)
  - Otherwise: returns `$result` from store as-is
- Call flow: `update()` → `store()` → delete + create; update wraps 200 response with data

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate (single row) | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **29** | `ProjectILPRiskAnalysis` | `where('project_id', $projectId)->delete()` | Yes (line 24) | **Yes** |

### Create Pattern (No Loop)

- **Request keys:** From model fillable via `FormDataExtractor::forFillable()`  
  - `identified_risks`, `mitigation_measures`, `business_sustainability`, `expected_profits`
- **Normalization:** `FormDataExtractor::forFillable()` applies ArrayToScalarNormalizer
- **One row:** Single model instance via `fill($data)` and `save()`
- **Create condition:** None

### Response Contract

- **store():** `response()->json(['message' => 'Risk analysis saved successfully.'], 200)`
- **update():** On 200 from store, returns `['message' => 'Risk analysis updated successfully.', 'data' => $riskAnalysis]`; otherwise returns store result

### Risk Assessment

| Question | Answer |
|----------|--------|
| Section keys missing → delete runs? | **YES** |
| Arrays/fields empty → delete runs? | **YES** |
| Guard present? | **NO** |
| Partial protection? | **NO** |

### Classification

**Single-row section**

### Proposed Guard Design

- **Normalized inputs:** `$data` from `FormDataExtractor::forFillable($request, $fillable)`
- **Method:** `isILPRiskAnalysisMeaningfullyFilled(array $data): bool`
- **Meaningful-fill:** At least one fillable field non-empty
- **Insertion point:** In `store()`, after `$data = FormDataExtractor::forFillable(...)` (line 23), before `DB::beginTransaction()` (line 24)
- **Guard location:** `store()`

---

## 2. StrengthWeaknessController

**Path:** `app/Http/Controllers/Projects/ILP/StrengthWeaknessController.php`

### Update Entry Path

- `update()` (lines 115–118) **delegates to** `store()`: `return $this->store($request, $projectId);`
- Call flow: `update()` → `store()` → delete + create

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate (single row) | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **29** | `ProjectILPBusinessStrengthWeakness` | `where('project_id', $projectId)->delete()` | Yes (line 24) | **Yes** |

### Create Pattern (No Loop)

- **Request keys:** `strengths`, `weaknesses` (arrays)
- **Normalization:** Scalar-to-array; if scalar and not empty string, wrap in `[$value]`; else use array or `[]`
- **One row:** Single model instance; `strengths` and `weaknesses` stored as `json_encode($strengths)`, `json_encode($weaknesses)`
- **Create condition:** None; always creates with whatever arrays are provided (including empty `[]`)

### Response Contract

- **Success:** `response()->json(['message' => 'Strengths and weaknesses saved successfully.'], 200)`
- **Failure:** `response()->json(['error' => '...'], 500)`

### Risk Assessment

| Question | Answer |
|----------|--------|
| Section keys missing → delete runs? | **YES** |
| Arrays empty → delete runs? | **YES** |
| Guard present? | **NO** |
| Partial protection? | **NO** |

### Classification

**Single-row section** (strengths/weaknesses as JSON in one row)

### Proposed Guard Design

- **Normalized inputs:** `$strengths`, `$weaknesses` (same arrays used in create)
- **Method:** `isILPStrengthWeaknessMeaningfullyFilled(array $strengths, array $weaknesses): bool`
- **Meaningful-fill:** At least one non-empty element in `$strengths` or `$weaknesses` (after trim)
- **Insertion point:** In `store()`, after normalization (after line 23), before `DB::beginTransaction()` (line 24)
- **Guard location:** `store()`

---

## 3. RevenueGoalsController

**Path:** `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php`

### Update Entry Path

- `update()` (lines 166–232) **does not delegate** to `store()`
- `store()` and `update()` are independent: `store()` is append-only (no delete); `update()` has its own delete-recreate logic
- Call flow: `update()` → delete (3 tables) → create (3 foreach loops)

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Append-only (no delete) | **No** | N/A |
| update() | Delete-Recreate | Yes (3 tables) | No |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| update() | **178** | `ProjectILPRevenuePlanItem` | `where('project_id', $projectId)->delete()` | Yes (line 174) | **Yes** |
| update() | **179** | `ProjectILPRevenueIncome` | same | Yes | **Yes** |
| update() | **180** | `ProjectILPRevenueExpense` | same | Yes | **Yes** |

### Create Pattern (3 Tables, 3 Loops)

- **Request keys:** `business_plan_items`, `annual_income`, `annual_expenses` (arrays of objects)
- **Normalization:** Scalar-to-array; each is array or `[$value]` or `[]`
- **One row (per table):** Each element in `business_plan_items` → `ProjectILPRevenuePlanItem`; each in `annual_income` → `ProjectILPRevenueIncome`; each in `annual_expenses` → `ProjectILPRevenueExpense`
- **Loop condition:** None; every element creates a row
- **Required fields:** None; all fields can be null/empty

### Response Contract

- **store():** `['message' => 'Revenue Goals saved successfully.']`, 200
- **update():** `['message' => 'Revenue Goals updated successfully.']`, 200

### Risk Assessment

| Question | Answer |
|----------|--------|
| Section keys missing → delete runs? | **YES** |
| Arrays empty → delete runs? | **YES** |
| Guard present? | **NO** |
| Partial protection? | **NO** |

### Classification

**Nested section** (3 child tables: plan items, income, expenses)

### Proposed Guard Design

- **Normalized inputs:** `$businessPlanItems`, `$annualIncome`, `$annualExpenses` (same as update)
- **Method:** `isILPRevenueGoalsMeaningfullyFilled(array $businessPlanItems, array $annualIncome, array $annualExpenses): bool`
- **Meaningful-fill:** At least one meaningful row in any of the three arrays (e.g. non-empty `item`, `description`, or any year value)
- **Insertion point:** In `update()` only, after normalization (after line 173), before `DB::beginTransaction()` (line 174)
- **Guard location:** `update()` (store has no delete; only update mutates)

---

## 4. BudgetController

**Path:** `app/Http/Controllers/Projects/ILP/BudgetController.php`

### Update Entry Path

- `update()` (lines 145–162) runs `BudgetSyncGuard::canEditBudget()`; if approved, returns 403
- If allowed, delegates: `return $this->store($request, $projectId);`
- Call flow: `update()` → (BudgetSyncGuard) → `store()` → delete + create

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate (multi-row) | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes (after 403 check) |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **45** | `ProjectILPBudget` | `where('project_id', $projectId)->delete()` | Yes (line 40) | **Yes** |

### Create Pattern

- **Request keys:** `budget_desc`, `cost`, `beneficiary_contribution`, `amount_requested`
- **Normalization:** Scalar-to-array for `budget_desc` and `cost`; scalar for `beneficiary_contribution`, `amount_requested`
- **One row:** Index in `$budgetDescs`; `foreach ($budgetDescs as $index => $description)`
- **Loop condition:** None; every index creates a row (no per-row gate)
- **Required fields:** None; creates even when empty

### Response Contract

- **Success:** `response()->json(['message' => 'Budget saved successfully.'], 200)`
- **Blocked:** `response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403)`

### Risk Assessment

| Question | Answer |
|----------|--------|
| Section keys missing → delete runs? | **YES** |
| Arrays empty → delete runs? | **YES** |
| Guard present? | **NO** (skip-empty) |
| Partial protection? | **YES** — `BudgetSyncGuard` blocks when project approved; does not guard empty section |

### Classification

**Budget-type** (multi-row; uses BudgetSyncService after commit)

### Proposed Guard Design

- **Normalized inputs:** `$budgetDescs`, `$costs` (same as create loop)
- **Method:** `isILPBudgetMeaningfullyFilled(array $budgetDescs, array $costs): bool`
- **Meaningful-fill:** At least one index with non-empty `budget_desc` (after trim) and valid `cost` (non-null, non-empty; 0 allowed)
- **Insertion point:** In `store()`, after normalization (move before transaction; currently inside try at 48–51), before `DB::beginTransaction()` (line 40)
- **Guard location:** `store()`

---

## Wave 2 Summary Table

| Controller | Delete Pattern | Delete Lines | Guard Present? | Partial Protection | Risk Level | Guard Needed In |
|------------|----------------|--------------|----------------|--------------------|------------|-----------------|
| RiskAnalysisController | Single-row delete-recreate | 29 | No | No | HIGH | store() |
| StrengthWeaknessController | Single-row delete-recreate | 29 | No | No | HIGH | store() |
| RevenueGoalsController | Multi-table delete-recreate | 178, 179, 180 | No | No | HIGH | **update()** |
| BudgetController | Multi-row delete-recreate | 45 | No | BudgetSyncGuard | HIGH | store() |

---

## Wave 2 Final Conclusion

### Highest risk controllers

- All four run delete unconditionally and have no skip-empty guard.
- **RevenueGoalsController** is notable: `update()` deletes from three tables and has no guard.

### Structural differences

1. **RevenueGoalsController:** `store()` is append-only; `update()` has separate delete-recreate. The guard must go in `update()`, not `store()`.
2. **RiskAnalysisController:** `update()` wraps the store response with `data` when status is 200.
3. **BudgetController:** Uses `BudgetSyncGuard` (403 when approved); skip-empty guard is separate.
4. **StrengthWeaknessController:** Single row with `strengths` and `weaknesses` JSON arrays; no per-row gate.

### Easiest to guard

1. **RiskAnalysisController** and **StrengthWeaknessController** — single-row sections, similar to IAH single-row controllers.
2. **BudgetController** — Multi-row with clear keys (`budget_desc`, `cost`); like IAHBudgetDetails.
3. **RevenueGoalsController** — Nested (3 tables, 3 loops); guard must be in `update()` and cover all three arrays.

### Special handling

- **ILPBudgetController:** Has BudgetSyncGuard; guard must not affect 403 behavior; insertion point is after BudgetSyncGuard, before transaction.
- **RevenueGoalsController:** Only `update()` needs a guard; `store()` does not delete.

---

*End of Phase 2 Wave 2 ILP Controllers Analysis.*
