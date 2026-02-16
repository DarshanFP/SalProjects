# Phase 2 — Wave 1 IAH Controllers — Architectural Analysis

**Date:** 2026-02-15  
**Type:** STRICT READ-ONLY — Analysis Only (No Code Changes)  
**Target:** 5 IAH controllers

---

## 1. IAHEarningMembersController

**Path:** `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php`

### Update Entry Path

- `update()` (line 81-84) **delegates to `store()`**: `return $this->store($request, $projectId);`
- Call flow: `update()` → `store()` → delete + create loop

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **30** | `ProjectIAHEarningMembers` | `where('project_id', $projectId)->delete()` | Yes (line 27) | **Yes** |

- Delete **always runs** when `store()` is entered (and thus when `update()` is called).

### Create Loop Details

- **Request keys:** `member_name`, `work_type`, `monthly_income` (parallel arrays)
- **Normalization:** Scalar-to-array; if scalar, wrap in `[$value]`; else use array or `[]`
- **One row:** Index `$i` across the three arrays
- **Loop:** `for ($i = 0; $i < $rowCount; $i++)` where `$rowCount = count($memberNames)`
- **Create condition:**  
  `!empty($memberName) && !empty($workType) && $monthlyIncome !== null && $monthlyIncome !== ''`
- **Required fields:** All three non-empty for create; `monthly_income` may be 0

### Response Contract

- **Success:** `response()->json(['message' => 'IAH earning members details saved successfully.'], 200)`
- **Failure:** `response()->json(['error' => 'Failed to save IAH earning members details.'], 500)`

### Risk Assessment

| Question | Answer |
|----------|--------|
| Section keys missing → delete runs? | **YES** |
| Arrays empty → delete runs? | **YES** |
| Guard present? | **NO** |
| Partial protection (e.g. BudgetSyncGuard)? | **NO** |

### Classification

**Multi-row section**

### Proposed Guard Design

- **Normalized inputs:** `$memberNames`, `$workTypes`, `$monthlyIncomes` (same extraction as create loop)
- **Method:** `isIAHEarningMembersMeaningfullyFilled(array $memberNames, array $workTypes, array $monthlyIncomes): bool`
- **Meaningful-fill:** At least one index `$i` where all of: `!empty($memberName)`, `!empty($workType)`, `$monthlyIncome !== null && $monthlyIncome !== ''`
- **Insertion point:** In `store()`, **after** array normalization (after lines 37–39), **before** `DB::beginTransaction()` (line 27)
- **Guard location:** `store()` (update delegates to store)

---

## 2. IAHBudgetDetailsController

**Path:** `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`

### Update Entry Path

- `update()` (lines 104–119) runs `BudgetSyncGuard::canEditBudget()` check; if pass, **delegates to `store()`**: `return $this->store($request, $projectId);`
- Call flow: `update()` → (BudgetSyncGuard check) → `store()` → delete + create loop

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes (after guard) |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **50** | `ProjectIAHBudgetDetails` | `where('project_id', $projectId)->delete()` | Yes (line 46) | **Yes** |

- Delete **always runs** when `store()` is entered (after BudgetSyncGuard passes for update path).

### Create Loop Details

- **Request keys:** `particular`, `amount`, `family_contribution`
- **Normalization:** Scalar-to-array for `particular` and `amount`; `family_contribution` scalar (single value)
- **One row:** Index `$i` in `$particulars` / `$amounts`
- **Loop:** `for ($i = 0; $i < count($particulars); $i++)`
- **Create condition:**  
  `trim((string) $particular) !== '' && $amount !== null && $amount !== ''`
- **Required fields:** Non-empty `particular` and non-null/non-empty `amount`; 0 allowed for amount

### Response Contract

- **Success:** `response()->json(['message' => 'IAH budget details saved successfully.'], 200)`
- **Failure:** `response()->json(['error' => 'Failed to save IAH budget details.'], 500)`
- **Blocked (approved):** `response()->json(['error' => self::BUDGET_LOCKED_MESSAGE], 403)`

### Risk Assessment

| Question | Answer |
|----------|--------|
| Section keys missing → delete runs? | **YES** |
| Arrays empty → delete runs? | **YES** |
| Guard present? | **NO** (skip-empty guard) |
| Partial protection? | **YES** — `BudgetSyncGuard::canEditBudget()` blocks when project approved; does **not** guard empty section |

### Classification

**Multi-row section**

### Proposed Guard Design

- **Normalized inputs:** `$particulars`, `$amounts` (same as create loop)
- **Method:** `isIAHBudgetDetailsMeaningfullyFilled(array $particulars, array $amounts): bool`
- **Meaningful-fill:** At least one index `$i` where `trim((string)$particular) !== '' && $amount !== null && $amount !== ''`
- **Insertion point:** In `store()`, **after** array normalization (after lines 56–58), **before** `DB::beginTransaction()` (line 46)
- **Guard location:** `store()` (update delegates to store)

---

## 3. IAHSupportDetailsController

**Path:** `app/Http/Controllers/Projects/IAH/IAHSupportDetailsController.php`

### Update Entry Path

- `update()` (line 59–61) **delegates to `store()`**: `return $this->store($request, $projectId);`
- Call flow: `update()` → `store()` → delete + create single row

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate (single row) | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **34** | `ProjectIAHSupportDetails` | `where('project_id', $projectId)->delete()` | Yes (line 31) | **Yes** |

### Create Pattern (No Loop)

- **Request keys:** From model fillable via `FormDataExtractor::forFillable()`  
  - `employed_at_st_ann`, `employment_details`, `received_support`, `support_details`, `govt_support`, `govt_support_nature`
- **Normalization:** `FormDataExtractor::forFillable()` applies `ArrayToScalarNormalizer`
- **One row:** Single model instance created with `$supportDetails->fill($data)` and `save()`
- **Create condition:** None — always creates if store runs; no per-field gate

### Response Contract

- **Success:** `response()->json($supportDetails, 200)` (model as JSON)
- **Failure:** `response()->json(['error' => 'Failed to save IAH support details.'], 500)`

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
- **Method:** `isIAHSupportDetailsMeaningfullyFilled(array $data): bool`
- **Meaningful-fill:** At least one fillable field has a non-empty value (non-null, non-empty string, or meaningful numeric)
- **Insertion point:** In `store()`, **after** `$data = FormDataExtractor::forFillable(...)` (line 25), **before** `DB::beginTransaction()` (line 31)
- **Guard location:** `store()`

---

## 4. IAHHealthConditionController

**Path:** `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php`

### Update Entry Path

- `update()` (line 60–62) **delegates to `store()`**: `return $this->store($request, $projectId);`
- Call flow: `update()` → `store()` → delete + create single row

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate (single row) | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **35** | `ProjectIAHHealthCondition` | `where('project_id', $projectId)->delete()` | Yes (line 32) | **Yes** |

### Create Pattern (No Loop)

- **Request keys:** From model fillable via `FormDataExtractor::forFillable()`  
  - `illness`, `treatment`, `doctor`, `hospital`, `doctor_address`, `health_situation`, `family_situation`
- **Normalization:** `FormDataExtractor::forFillable()` applies `ArrayToScalarNormalizer`
- **One row:** Single model instance created with `$healthCondition->fill($data)` and `save()`
- **Create condition:** None

### Response Contract

- **Success:** `response()->json($healthCondition, 200)`
- **Failure:** `response()->json(['error' => 'Failed to save IAH health condition details.'], 500)`

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
- **Method:** `isIAHHealthConditionMeaningfullyFilled(array $data): bool`
- **Meaningful-fill:** At least one fillable field non-empty
- **Insertion point:** In `store()`, **after** `$data = FormDataExtractor::forFillable(...)` (line 26), **before** `DB::beginTransaction()` (line 32)
- **Guard location:** `store()`

---

## 5. IAHPersonalInfoController

**Path:** `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php`

### Update Entry Path

- `update()` (line 61–63) **delegates to `store()`**: `return $this->store($request, $projectId);`
- Call flow: `update()` → `store()` → delete + create single row

### Mutation Pattern Summary

| Method | Pattern | Delete? | Delegate? |
|--------|---------|---------|-----------|
| store() | Delete-Recreate (single row) | Yes | N/A |
| update() | Delegates to store() | Yes (via store) | Yes |

### Delete Block Details

| Location | Line | Model | Scope | In transaction? | Unconditional? |
|----------|------|-------|-------|-----------------|----------------|
| store() | **37** | `ProjectIAHPersonalInfo` | `where('project_id', $projectId)->delete()` | Yes (line 34) | **Yes** |

### Create Pattern (No Loop)

- **Request keys:** From model fillable via `FormDataExtractor::forFillable()`  
  - `name`, `age`, `gender`, `dob`, `aadhar`, `contact`, `address`, `email`, `guardian_name`, `children`, `caste`, `religion`
- **Normalization:** `FormDataExtractor::forFillable()` applies `ArrayToScalarNormalizer`
- **One row:** Single model instance created with `$personalInfo->fill($data)` and `save()`
- **Create condition:** None

### Response Contract

- **Success:** `response()->json($personalInfo, 200)`
- **Failure:** `response()->json(['error' => 'Failed to save IAH personal info.'], 500)`

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
- **Method:** `isIAHPersonalInfoMeaningfullyFilled(array $data): bool`
- **Meaningful-fill:** At least one fillable field non-empty
- **Insertion point:** In `store()`, **after** `$data = FormDataExtractor::forFillable(...)` (line 27), **before** `DB::beginTransaction()` (line 34)
- **Guard location:** `store()`

---

## Wave 1 Summary Table

| Controller | Delete Pattern | Delete Line | Guard Present? | Partial Protection | Risk Level | Guard Needed In |
|------------|----------------|-------------|----------------|--------------------|------------|-----------------|
| IAHEarningMembersController | Multi-row delete-recreate | 30 | No | No | HIGH | store() |
| IAHBudgetDetailsController | Multi-row delete-recreate | 50 | No | BudgetSyncGuard only (approved) | HIGH | store() |
| IAHSupportDetailsController | Single-row delete-recreate | 34 | No | No | HIGH | store() |
| IAHHealthConditionController | Single-row delete-recreate | 35 | No | No | HIGH | store() |
| IAHPersonalInfoController | Single-row delete-recreate | 37 | No | No | HIGH | store() |

---

## Wave 1 Final Conclusion

### Highest risk

- All five controllers run delete unconditionally and have no skip-empty guard.
- When section keys are missing or data is empty, existing data is wiped and no meaningful replacement is created.

### Easiest to guard

1. **Single-row controllers** (SupportDetails, HealthCondition, PersonalInfo): Same pattern (FormDataExtractor + fill + save); meaningful-fill is “any fillable field non-empty”.
2. **IAHEarningMembersController**: Multi-row with clear create condition; guard mirrors that condition.
3. **IAHBudgetDetailsController**: Multi-row with clear create condition; BudgetSyncGuard is separate and does not affect empty-section behavior.

### Structural notes

- All controllers use `update()` → `store()`, so guards belong in `store()` only.
- IAHBudgetDetailsController has an extra pre-check (`BudgetSyncGuard`) that returns 403 when the project is approved; skip-empty guard is independent and still required in `store()`.
- Single-row controllers use `FormDataExtractor::forFillable()`; guard input is the resulting `$data`.
- Multi-row controllers use parallel arrays and index-based loops; guard must mirror their create conditions.

### Guard placement

- **All guards in `store()`**, since `update()` delegates to `store()`.
- Insertion point for each: after normalization/data extraction, before `DB::beginTransaction()` and any delete.

---

*End of Phase 2 Wave 1 IAH Controllers Analysis.*
