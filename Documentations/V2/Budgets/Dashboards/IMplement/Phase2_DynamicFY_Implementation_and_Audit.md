# Phase-2 Implementation Report

**Date:** 2026-03-04  
**Phase:** Phase 2 — Dynamic FY Infrastructure  
**Plan Reference:** Executor_Dashboard_Scope_FY_Phased_Implementation_Plan.md  
**Status:** Complete

---

## 1. Overview

Phase-2 adds `FinancialYearHelper::listAvailableFYFromProjects()` to derive the FY dropdown list from project data (`commencement_month_year`). This enables the Executor dashboard to show only FYs that contain projects for the selected scope, instead of a static 10-year list. Other dashboards (Coordinator, Provincial, General) continue to use `listAvailableFY()` and are unaffected. All changes are additive; `listAvailableFY()` is unchanged.

---

## 2. Method Implemented

### listAvailableFYFromProjects

**Signature:**
```php
listAvailableFYFromProjects(Builder $projectQuery): array
```

**Parameters:**
- `$projectQuery` — `\Illuminate\Database\Eloquent\Builder` (e.g. from ProjectQueryService methods)

**Return type:** `array<string>` — List of FY labels in format `"YYYY-YY"`, newest first

**Behaviour:**
- Accepts any project query builder
- Uses `clone` to avoid modifying the caller's query
- Returns FYs derived from project `commencement_month_year`
- Falls back to `listAvailableFY()` when no project dates exist

---

## 3. FY Derivation Logic

1. **Project dates:** `(clone $projectQuery)->whereNotNull('commencement_month_year')->distinct()->pluck('commencement_month_year')` — extracts distinct non-null commencement dates without loading full models.

2. **FY conversion:** For each date, `FinancialYearHelper::fromDate(Carbon::parse($date))` maps the date to an FY string (e.g. `2024-08-01` → `"2024-25"`).

3. **Unique FY list:** `array_unique()` removes duplicates (e.g. multiple projects in the same FY).

4. **Sort:** `rsort()` sorts descending so the newest FY is first (e.g. `["2025-26", "2024-25", "2023-24"]`).

5. **Return:** The sorted array is returned. If empty, fallback is applied.

---

## 4. Fallback Behaviour

When the derived FY list is empty (e.g. no projects or no non-null `commencement_month_year`):

- The method returns `FinancialYearHelper::listAvailableFY()` — the static 10-year list including the current FY.

This guarantees:
- A usable FY dropdown even when the scoped dataset has no projects
- At least `currentFY()` is present (via `listAvailableFY()`)
- Consistent UX when switching scopes that have no data in some cases

---

## 5. Performance Considerations

| Aspect | Implementation |
|--------|----------------|
| No model loading | `pluck('commencement_month_year')` selects only the date column; no full Project models |
| Distinct values | `distinct()` limits rows before pluck; one row per unique date |
| Query isolation | `clone $projectQuery` prevents side effects on the caller's builder |
| Minimal processing | Only unique dates are processed in PHP; FY conversion is O(n) for n distinct dates |

Correct pattern used: `distinct()` + `pluck()` — **not** `get()` — so the query does not load full project models.

---

## 6. Compatibility Audit

| Check | Result |
|-------|--------|
| `listAvailableFY()` unchanged | Confirmed — signature and body untouched |
| Other dashboards unaffected | Coordinator, Provincial, General use `listAvailableFY()` only; no changes |
| Accepts any project query builder | Yes — type-hinted as `Builder`; works with ProjectQueryService queries |
| ProjectQueryService compatible | Yes — can receive `getOwnedProjectsQuery()`, `getInChargeProjectsQuery()`, `getProjectsForUserQuery()`, etc. |

**Existing callers of `listAvailableFY()` (unchanged):**
- ExecutorController
- CoordinatorController
- ProvincialController
- GeneralController

---

## 7. Risk Assessment

**LOW RISK**

- Only a new method was added; no existing methods were modified
- `listAvailableFY()` remains the static FY source for other dashboards
- New method is additive; no callers yet; will be wired in Phase 3/4
- PHP syntax validated (`php -l`)
- No linter errors

---

## 8. Next Phase Readiness

Phase-2 is complete. Ready to proceed to:

**Phase 2.5 — Widget FY Consistency Fix**

- Pass `$fy` into `getChartData`, `getQuickStats`, `getActionItems`, `getUpcomingDeadlines`
- Ensure all financial widgets respect the selected FY

The `listAvailableFYFromProjects()` method will be integrated in later phases when the Executor dashboard switches to dynamic FY (e.g. after scope selector UI and backend wiring). The current Phase-2 implementation provides the helper; controllers remain unchanged as required.
