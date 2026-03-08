# Phase 1 — Invariant Rule Correction Audit

**Date:** 2026-03-04  
**Plan Reference:** Financial_Data_Stabilization_Implementation_Plan.md  
**Status:** Post-implementation audit

---

## 1. Files Modified

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Domain/Finance/FinancialInvariantService.php` | Rule replacement | Replaced INV-3 `opening_balance === amount_sanctioned` with canonical rule |
| `app/Domain/Budget/ProjectFinancialResolver.php` | Rule replacement | Replaced INV-7 `opening_balance == overall_project_budget` with canonical rule |
| `app/Http/Controllers/CoordinatorController.php` | Logic update | Approval pipeline now sets `opening_balance = sanctioned + forwarded + local`; uses `amount_sanctioned = overall - combined` (new money) |
| `tests/Unit/FinancialInvariantServiceTest.php` | New file | 8 unit tests for canonical invariant |
| `tests/Feature/ProjectApprovalWorkflowTest.php` | Update | Adjusted `test_coordinator_can_approve_valid_project_flow` for Phase 1 canonical rule |

---

## 2. Invariant Rules Before vs After

### FinancialInvariantService (INV-3)

| Before | After |
|--------|-------|
| `opening_balance === amount_sanctioned` | `abs(opening_balance - expected_opening_balance) <= 0.01` where `expected_opening_balance = amount_sanctioned + (amount_forwarded ?? 0) + (local_contribution ?? 0)` |
| Blocked approvals with forwarded/local contributions | Allows valid projects with forwarded and local contributions |

**Validation behavior:**
- If fails: logs warning, blocks approval (throws DomainException)
- Tolerance: 0.01

### ProjectFinancialResolver (INV-7)

| Before | After |
|--------|-------|
| `opening_balance == overall_project_budget` (log warning on mismatch) | `expected_opening = amount_sanctioned + amount_forwarded + local_contribution`; log warning if `abs(opening_balance - expected_opening) > 0.01` |
| False positives for Individual-type projects | Uses canonical formula; no blocking; resolver still returns DB value |

**Behavior:** Log only; does NOT block resolver output. Resolver returns DB `opening_balance` as-is.

---

## 3. Approval Pipeline Verification

### CoordinatorController Approval Flow

1. **Budget sync:** `BudgetSyncService::syncBeforeApproval($project)` runs before approval.
2. **Financial resolution:** Uses `ProjectFinancialResolver::resolve($project)` for `overall`, `amount_forwarded`, `local_contribution`.
3. **Amount sanctioned:** `amount_sanctioned = newMoney > 0 ? newMoney : fallback` where `newMoney = max(0, overall - (forwarded + local))`.
4. **Opening balance:** `opening_balance = amount_sanctioned + amount_forwarded + local_contribution`.
5. **Validation:** `FinancialInvariantService::validateForApproval($project, $approvalData)` with full data.
6. **Save:** `ProjectStatusService::approve()` persists `amount_sanctioned`, `opening_balance`.

### Fields Set During Approval

| Field | Source |
|-------|--------|
| `amount_sanctioned` | New money (overall - combined) or fallback to combined when all-forwarded |
| `opening_balance` | `amount_sanctioned + amount_forwarded + local_contribution` |
| `amount_forwarded` | From resolver (project DB) |
| `local_contribution` | From resolver (project DB) |

### Backward Compatibility

- When `forwarded = 0` and `local = 0`: `opening_balance = amount_sanctioned`. Invariant passes.
- New approvals with forwarded/local funds now produce correct canonical values.

---

## 4. Unit Test Results

| Test | Result |
|------|--------|
| passes when opening equals sanctioned only (CASE 1) | PASS |
| passes when opening equals sanctioned plus forwarded (CASE 2) | PASS |
| fails when opening mismatches canonical formula (CASE 3) | PASS |
| passes when opening equals sanctioned plus forwarded plus local (CASE 4) | PASS |
| fails when opening balance zero | PASS |
| fails when amount sanctioned zero | PASS |
| uses project values when data omits forwarded local | PASS |
| passes within tolerance | PASS |

**All 8 unit tests passed.**

---

## 5. Resolver Simulation Results

### Test Projects (DP-0024, DP-0025, DP-0041, IIES-0060)

| Project | Expected opening | Resolver output | Match |
|---------|------------------|-----------------|-------|
| DP-0024 | 1,040,000 | 1,040,000 | yes |
| DP-0025 | 1,830,000 | 1,830,000 | yes |
| DP-0041 | 630,000 | 630,000 | yes |
| IIES-0060 | 16,000 | 16,000 | yes |

### Invariant Validation (approval simulation)

| Project | Result | Notes |
|---------|--------|-------|
| DP-0024 | PASS | Valid data per canonical rule |
| DP-0025 | PASS | Valid data per canonical rule |
| DP-0041 | FAIL | amount_sanctioned = 0; Individual type, manual review |
| IIES-0060 | FAIL | amount_sanctioned = 0; Individual type, manual review |

### Full Approved Projects Simulation

- **Total approved projects:** 45
- **Mismatches (expected vs resolver):** 27
- **Projects passing:** 18

### Mismatch List (27 projects)

| Type | Count | Projects |
|------|-------|----------|
| opening_balance NULL | 13 | DP-0001, CIC-0001, DP-0002, DP-0003, DP-0005, DP-0006, DP-0007, DP-0008, DP-0009, DP-0016, DP-0017, DP-0020, DP-0022 |
| opening_balance = 0 | 7 | GEN-0005, GEN-0007, DP-0055, DP-0057, DP-0058, DP-0059, DP-0060, DP-0062 |
| Forwarded contribution mismatch | 6 | DP-0066, DP-0068, DP-0070, DP-0072, DP-0074, DP-0076 |

These 27 projects are in scope for Phase 2 data repair.

---

## 6. Dashboard Aggregation Impact

### Methods Reviewed

| Method | Location | Uses |
|--------|----------|------|
| `calculateBudgetSummariesFromProjects` | CoordinatorController, ExecutorController, ProvincialController | `resolver->resolve($project)['opening_balance']` |
| `getSystemBudgetOverviewData` | CoordinatorController | `resolvedFinancials[$p->project_id]['opening_balance']` |
| `calculateCenterPerformance` | ProvincialController | Aggregates via projects; uses resolver when needed |

### Impact Assessment

- Phase 1 changes do **not** change resolver return values.
- Resolver still returns DB `opening_balance` for approved projects.
- Only internal invariant assertion (and logging) changed.
- **Conclusion:** Phase 1 has no impact on dashboard totals. Totals remain as before Phase 1.

---

## 7. Regression Findings

### ProjectApprovalWorkflowTest

- **16 tests passed**, including:
  - `test_coordinator_can_approve_valid_project_flow` — approval with forwarded funds (overall=200000, forwarded=100000) → opening_balance=200000
  - `test_zero_opening_balance_blocks_approval` — invariant blocks approval when opening_balance = 0

### No Regressions Detected

- Approval flow works for projects with and without forwarded funds.
- Financial invariant blocks invalid data as intended.
- Resolver continues to return DB values without blocking.

---

## 8. System Stability Assessment

- Invariant rules aligned with canonical formula.
- Approval pipeline sets `opening_balance = sanctioned + forwarded + local`.
- Existing valid projects (e.g., DP-0024, DP-0025) continue to pass.
- 27 projects with legacy data need Phase 2 repair; Phase 1 does not modify data.
- Individual types (DP-0041, IIES-0060) have `amount_sanctioned = 0` and require manual review per plan.

---

## 9. Final Status Classification

**SAFE_TO_PROCEED_TO_PHASE_2**

Phase 1 invariant rule correction is complete. Rule changes and approval logic are in place, tests pass, and no regressions were observed. The 27 mismatch projects are ready for Phase 2 data repair.
