# Phase 2.4 – Budget Performance Guard

## Purpose

This document defines when to use **database `SUM()`** vs **in-memory aggregation via `DerivedCalculationService`**. It establishes architectural rules to prevent performance regressions and maintain domain consistency.

---

## 1. When to Use DB SUM()

Use database aggregation when:

| Scenario | Reason |
|----------|--------|
| **Reporting** | Queries over large datasets; DB aggregation is O(n) in DB, not in PHP memory. |
| **Exporting** | Bulk exports (CSV, PDF) need totals for many projects/phases; DB SUM scales better. |
| **Large dataset aggregation** | When summing across hundreds or thousands of rows, DB aggregation avoids loading all rows into memory. |
| **No business logic required** | Simple summation of stored values without formulas, rounding rules, or derived calculations. |

### Example

```php
// Reporting: total budget across all projects
$totalBudget = ProjectBudget::where('project_id', $projectId)->sum('total_budget');

// Export: phase totals for PDF
$phaseTotals = ProjectBudgetPhase::where('phase_id', $phaseId)->sum('total_amount');
```

---

## 2. When to Use DerivedCalculationService

Use `DerivedCalculationService` when:

| Scenario | Reason |
|----------|--------|
| **Business logic** | Row totals (`q × m × d`), phase totals from rows, project totals from phases. |
| **Remaining balance** | `total_budget - total_expenses`; must match canonical formula for domain consistency. |
| **Domain consistency** | Values must align with `budget-calculations.js` and freeze tests. |
| **Validation-related logic** | Checking submitted totals against computed totals; must use same formulas as storage. |
| **Freeze test enforcement** | Tests assert backend uses DerivedCalculationService; mixing DB SUM breaks architectural guarantees. |

### Example

```php
// Remaining balance: business logic
$remaining = $this->derivedCalculation->calculateRemainingBalance($totalBudget, $totalExpenses);

// Phase total from rows: formula-based
$phaseTotal = $this->derivedCalculation->calculatePhaseTotal($rows);

// Validation: compare submitted vs computed
$computedTotal = $this->derivedCalculation->calculateProjectTotal($phaseTotals);
```

---

## 3. Performance Risks of In-Memory Aggregation

| Risk | Description |
|------|-------------|
| **Memory pressure** | Loading all rows into PHP to sum them uses O(n) memory; large datasets can exhaust memory. |
| **N+1 / over-fetching** | Iterating collections and calling the service per row/phase can trigger many queries or load more data than needed. |
| **Redundant computation** | If values are already stored and correct, re-computing from raw rows is wasteful. |
| **Scaling limits** | In-memory aggregation does not scale for reporting dashboards or bulk exports across many projects. |

### Mitigation

- Use DB `SUM()` for read-heavy, large-scale aggregation.
- Use `DerivedCalculationService` only where business logic or domain consistency is required.

---

## 4. Architectural Rule

> **Controllers must not mix DB SUM and manual arithmetic.**

A controller should choose one approach for a given concern:

- **DB SUM path** — Use DB aggregation for totals; do not layer manual arithmetic on top of DB sums for the same semantic value.
- **DerivedCalculationService path** — Use the service for totals; do not mix in ad-hoc DB sums for the same semantic value.

Mixing both for the same conceptual total (e.g. "project total") leads to:

- Inconsistent results if formulas differ
- Parity drift between display, validation, and storage
- Unclear ownership of the "source of truth"

---

## 5. Confirmation

**No code changes were made in this step.** This document is advisory only and establishes the performance architecture guard for future development.
