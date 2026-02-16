# M3.5.4 — Financial Integration Tests

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.5.4 — Financial Integration Tests  
**Mode:** Controlled Test Addition  
**Date:** 2025-02-15

---

## Objective

Add feature test coverage for canonical financial invariants in `ProjectFinancialResolver`. Tests are read-only; no application logic changes.

---

## Test File

`tests/Feature/FinancialResolverTest.php`

---

## Test Cases

### 1) Approved Project

| Assertion | Description |
|-----------|-------------|
| `opening_balance == budget` | Opening balance equals overall project budget |
| `sanctioned > 0` | Approved project must have positive sanctioned amount |

**Setup:** Phase-based (Development) project with `status = approved_by_coordinator`, `amount_sanctioned > 0`, `opening_balance == overall_project_budget`.

---

### 2) Draft Project

| Assertion | Description |
|-----------|-------------|
| `sanctioned == 0` | Draft with no pending request (budget == forwarded + local) |
| `opening_balance == forwarded + local` | Opening balance equals contributions when sanctioned is 0 |

**Setup:** Phase-based project with `status = draft`, `overall_project_budget = forwarded + local` so pending request is 0.

---

### 3) Reverted Project

| Assertion | Description |
|-----------|-------------|
| `sanctioned == 0` | Reverted with no pending request (budget == forwarded + local) |
| `opening_balance == forwarded + local` | Same as draft; opening balance equals contributions |

**Setup:** Phase-based project with `status = reverted_by_coordinator`, `overall_project_budget = forwarded + local`.

---

### 4) Pending Request Calculation

| Assertion | Description |
|-----------|-------------|
| `pending = budget - (forwarded + local)` | Resolver returns pending request as amount_sanctioned for non-approved |
| `opening_balance = sanctioned + forwarded + local` | Opening equals budget when formula holds |

**Setup:** Phase-based project with `status = draft`, `budget > forwarded + local`. Assert `amount_sanctioned == budget - (forwarded + local)`.

---

### 5) Approved Project with Budget Rows

| Assertion | Description |
|-----------|-------------|
| `sanctioned > 0` | Approved project has positive sanctioned |
| `opening_balance == overall_project_budget` | Opening balance equals sum of this_phase (overall) |

**Setup:** Phase-based project with budget rows, `status = approved_by_coordinator`, DB `opening_balance` and `amount_sanctioned` set.

---

## Coverage

- **Project type:** Development Projects (phase-based) only in this test file
- **Resolver:** `ProjectFinancialResolver::resolve()`
- **Database:** Uses `DatabaseTransactions`; no persistent changes

---

## Non-Breaking Behavior

- No application logic changes
- Tests only assert resolver output
- Failure indicates invariant or formula drift

---

**M3.5.4 Complete — Financial Integration Tests Added**
