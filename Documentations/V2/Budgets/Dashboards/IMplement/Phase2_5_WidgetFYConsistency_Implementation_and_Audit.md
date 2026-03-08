# Phase-2.5 Implementation Report

**Date:** 2026-03-04  
**Phase:** Phase 2.5 — Widget FY Consistency Fix  
**Plan Reference:** Executor_Dashboard_Scope_FY_Phased_Implementation_Plan.md  
**Status:** Complete

---

## 1. Overview

Phase-2.5 propagates the selected FY (`$fy`) from `executorDashboard()` into all financial and project-related widget methods so that Quick Stats, Budget Analytics (chart data), Action Items, and Upcoming Deadlines use the FY-filtered approved project dataset. Previously, only the budget summary and project tables respected FY; widgets used unfiltered approved projects. Now all widgets respect the dashboard FY selection, ensuring consistent totals across the dashboard when the user changes FY.

---

## 2. Controller Methods Updated

| Method | Change |
|--------|--------|
| **getChartData** | Added `?string $fy = null`; passes `$fy` to `getApprovedOwnedProjectsForUser` when non-null for budget/expense aggregation |
| **getQuickStats** | Added `?string $fy = null`; passes `$fy` to `getApprovedOwnedProjectsForUser` for active project count and budget totals when non-null |
| **getActionItems** | Added `?string $fy = null`; passes `$fy` to `getApprovedOwnedProjectsForUser` for overdue reports logic when non-null |
| **getUpcomingDeadlines** | Added `?string $fy = null`; passes `$fy` to `getApprovedOwnedProjectsForUser` when non-null |

---

## 3. FY Filtering Logic

**Propagation flow:**

1. `executorDashboard()` derives `$fy` from request: `$fy = $request->input('fy', FinancialYearHelper::currentFY())` (always non-null; default is current FY).
2. `$fy` is passed to: `getChartData($user, $request, $fy)`, `getQuickStats($user, $fy)`, `getActionItems($user, $fy)`, `getUpcomingDeadlines($user, $fy)`.
3. Each widget uses conditional logic for `getApprovedOwnedProjectsForUser`:
   - When `$fy !== null`: `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)`
   - When `$fy === null`: `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with)` (no FY filter)

**Other callers:** Methods like `reportList()` call `getUpcomingDeadlines($user)` without `$fy`. In those cases `$fy` defaults to `null` and behaviour remains unchanged (no FY filter).

---

## 4. Behaviour Consistency

| Scenario | Behaviour |
|----------|-----------|
| **FY provided** (e.g. from `executorDashboard`) | Widgets use `getApprovedOwnedProjectsForUser($user, $with, $fy)`. Totals, charts, action items, and deadlines are scoped to the selected FY. |
| **FY null** (e.g. from `reportList`) | Widgets use `getApprovedOwnedProjectsForUser($user, $with)` without FY. Behaviour matches pre–Phase 2.5. |

Backward compatibility is preserved via optional `?string $fy = null` and conditional use of the third parameter.

---

## 5. Compatibility Audit

| Check | Result |
|-------|--------|
| ProjectQueryService unchanged | Confirmed — only controller calls updated; no changes to service |
| FinancialYearHelper unchanged | Confirmed |
| Other dashboards unaffected | Confirmed — only ExecutorController modified |
| Existing callers without FY | `reportList()`, etc. call `getUpcomingDeadlines($user)` without `$fy`; they receive `null` and behave as before |

---

## 6. Risk Assessment

**LOW RISK**

- Only additive optional parameters
- Existing callers that omit `$fy` retain prior behaviour
- No changes to ProjectQueryService or FinancialYearHelper
- PHP syntax validated
- No linter errors

---

## 7. Next Phase Readiness

Phase-2.5 is complete. Ready to proceed to:

**Phase 2.6 — Financial Resolver Optimization**

- Introduce batch resolution (e.g. `ProjectFinancialResolver::resolveCollection()`)
- Resolve financials once per request and reuse across widgets
- Reduce repeated resolver calls for improved scalability
