# Financial Year Dashboard Implementation Plan

**Task:** Financial Year Dashboard Implementation Plan (Application Architecture Planning)  
**Date:** 2026-03-04  
**Mode:** Planning only (no code or database changes)

---

## 1. Objective

### Goal of FY Dashboards

- **View budgets per financial year** — Allow users to see total approved budget and expenses scoped to a selected FY (India: 1 April–31 March).
- **Track projects approved in each FY** — Identify which projects fall into a given FY using `projects.commencement_month_year`.
- **Show FY-based budget totals** — Aggregate `opening_balance` (and optionally pending `amount_requested`) per FY for approved and non-approved projects.
- **Improve financial reporting accuracy** — Align dashboard totals with government/organisational FY reporting instead of all-time or calendar-year views.

### Success Criteria

- Dashboards can filter projects by a selected FY.
- Aggregation remains role-scoped (Executor/Provincial/General/Coordinator).
- Resolver stays the single source for financial values; no new bypass paths.
- All approved projects have `commencement_month_year`, so FY derivation is reliable.

---

## 2. Financial Year Derivation Logic

### India FY Definition

- **FY start:** 1 April (year Y)
- **FY end:** 31 March (year Y+1)
- **Label:** FY Y–(Y+1) in short form, e.g. **2024-25**

### Calculation Rules

```
IF month(commencement_month_year) >= 4
    FY = year-year+1   (e.g. 2024 → "2024-25")
ELSE
    FY = year-1-year   (e.g. 2024 → "2023-24")
```

### Examples

| commencement_month_year | Derived FY |
|-------------------------|------------|
| 2024-08-01 | 2024-25 |
| 2024-02-10 | 2023-24 |
| 2025-04-01 | 2025-26 |
| 2026-03-31 | 2025-26 |

### System Format

- **Internal/API:** String `"YYYY-YY"` (e.g. `"2024-25"`).
- **Display:** Same or "FY 2024-25" as needed in UI.

---

## 3. Financial Year Helper Design

### Proposed Class

**Path:** `app/Support/FinancialYearHelper.php`  
(Alternative: `app/Helpers/FinancialYearHelper.php` if project convention prefers Helpers.)

### Functions

| Function | Signature | Purpose |
|----------|-----------|---------|
| **currentFY()** | `(): string` | Returns the current financial year string (e.g. "2025-26") based on today’s date. Used as default for FY dropdown. |
| **fromDate(Carbon $date)** | `(Carbon $date): string` | Derives FY string from any date (e.g. project’s `commencement_month_year`). Core logic for project→FY mapping. |
| **startDate(string $fy)** | `(string $fy): Carbon` | Given FY string "2024-25", returns 1 April 2024. Used for query range start. |
| **endDate(string $fy)** | `(string $fy): Carbon` | Given FY string "2024-25", returns 31 March 2025. Used for query range end. |
| **listAvailableFY()** | `(): array` | Returns list of FY strings for dropdown (e.g. from oldest project commencement to current FY). Can be config-driven (e.g. last N years) or derived from data. |

### Behaviour Notes

- **fromDate:** Use month ≥ 4 → FY = year-(year+1); else FY = (year-1)-year. Input can be from `commencement_month_year` or any Carbon.
- **startDate/endDate:** Parse "2024-25" into start year 2024, end year 2025; return Carbon instances at 00:00:00 for start and end-of-day for end (or startOfDay/endOfDay as appropriate for `BETWEEN`).
- **listAvailableFY:** Avoid N+1; consider cache or a single min(commencement_month_year) query plus current FY range.

---

## 4. Project Model Scope

### Scope Definition

**Name:** `scopeInFinancialYear`  
**Usage:** `Project::inFinancialYear($fy)`

### Logic

1. Resolve FY bounds: `$start = FinancialYearHelper::startDate($fy);` `$end = FinancialYearHelper::endDate($fy);`
2. Filter: `WHERE commencement_month_year BETWEEN $start AND $end`
3. Handle nulls: Exclude `commencement_month_year IS NULL` (or document that FY filter implies non-null).

### Example Implementation (Concept Only)

```php
// In Project model
public function scopeInFinancialYear($query, string $fy)
{
    $start = FinancialYearHelper::startDate($fy);
    $end = FinancialYearHelper::endDate($fy);
    return $query->whereNotNull('commencement_month_year')
                 ->whereBetween('commencement_month_year', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
}
```

### Example Usage

```php
// Approved budget total for FY 2024-25
$total = Project::approved()
    ->inFinancialYear('2024-25')
    ->sum('opening_balance');

// With role scope (e.g. provincial)
$ids = $projectAccessService->getAccessibleUserIds($user);
$total = Project::accessibleByUserIds($ids)
    ->approved()
    ->inFinancialYear('2024-25')
    ->sum('opening_balance');
```

---

## 5. Dashboard Integration Plan

| Dashboard | Current Aggregation | FY Integration Point |
|-----------|---------------------|----------------------|
| **Executor Dashboard** | Own projects via ProjectQueryService; resolver `opening_balance`; approved only for budget totals | Apply `inFinancialYear($request->fy)` (or default current FY) after `getApprovedOwnedProjectsForUser` / owned project query. FY dropdown on dashboard filters. |
| **Coordinator Dashboard** | `Project::approved()` with optional province/center/role; resolver or direct `opening_balance`; system performance and finance sub-dashboards | Apply `inFinancialYear($fy)` to approved project query before aggregation. FY dropdown in coordinator dashboard (and project list if needed). |
| **Provincial Dashboard** | `Project::accessibleByUserIds($ids)->approved()`; resolver `opening_balance`; society stats use `SUM(amount_sanctioned)` | Apply `inFinancialYear($fy)` after accessible/approved. FY dropdown on provincial dashboard. |
| **General Dashboard** | Coordinator hierarchy + direct team; resolver `opening_balance` | Apply `inFinancialYear($fy)` to the same project collections used for budget totals. FY dropdown on general dashboard. |
| **Budget Report** (BudgetExportController) | Project query with project_type, status, start_date/end_date (created_at) | Add optional FY filter; apply `inFinancialYear($fy)` when FY is selected (can coexist with date range or replace it for FY view). |
| **Coordinator Project List** | `Project::approved()` + filters (province, status, project_type, start_date, end_date on created_at) | Optional FY filter; apply `inFinancialYear($fy)` when FY selected. |

### Principle

- **Existing query:** Each dashboard already applies role scope and status (e.g. approved).
- **FY integration:** Add `.inFinancialYear($fy)` (and optionally a request/session FY parameter) so that aggregations and lists are restricted to the chosen FY.

---

## 6. Financial Aggregation Strategy

### Approved Projects

- **Metric:** Total budget for the FY = sum of project-level funds available at approval.
- **Field:** `opening_balance` (canonical; matches resolver for approved projects).
- **Query pattern:**  
  `Project::approved()->inFinancialYear($fy)->...` then sum via resolver or DB:
  - Resolver: iterate and sum `$resolver->resolve($p)['opening_balance']`.
  - DB (when consistent with resolver): `Project::approved()->inFinancialYear($fy)->sum('opening_balance')` for approved-only totals.

### Pending Projects (Optional)

- **Metric:** Total requested (not yet sanctioned) in the FY.
- **Field:** Resolver `amount_requested` only (no DB column).
- **Query pattern:**  
  `Project::notApproved()->inFinancialYear($fy)->...` then for each project `$resolver->resolve($p)['amount_requested']` and sum.  
  Note: Pending projects may have null `commencement_month_year`; decide whether to include them in FY at all (e.g. only if commencement is set).

### Why Resolver Remains Canonical

- Resolver encodes approved vs non-approved semantics (sanctioned vs requested, opening_balance source).
- Dashboards that already use the resolver for per-project amounts should keep doing so; FY is an extra filter on which projects are included, not a change to how amounts are computed.
- Direct `sum('opening_balance')` is acceptable for approved-only aggregates because approval flow and invariants keep DB in sync with resolver for approved projects.

---

## 7. Role-Based Filter Safety

### Order of Operations

1. **Apply role/access filter** — Restrict to projects the user may see (e.g. `accessibleByUserIds`, `getVisibleProjectsQuery`, or controller-specific scoping).
2. **Apply status filter** — e.g. `approved()` for budget totals.
3. **Apply FY scope** — `inFinancialYear($fy)`.
4. **Aggregate** — Sum opening_balance (or resolve and sum).

### Integration with Existing Services

- **ProjectAccessService::getVisibleProjectsQuery($user):** Returns base query already scoped by role. Chain:  
  `$this->projectAccessService->getVisibleProjectsQuery($user)->approved()->inFinancialYear($fy)`.
- **ProjectQueryService (e.g. getApprovedOwnedProjectsForUser):** Returns collection or query for owned projects; add `inFinancialYear($fy)` on the query before `get()` if the method returns a query, or filter the collection by FY in memory using FinancialYearHelper::fromDate (less ideal; prefer query scope).
- **accessibleByUserIds($ids):** Used by Provincial; chain:  
  `Project::accessibleByUserIds($ids)->approved()->inFinancialYear($fy)`.

### Safety Rule

- FY scope must always be applied **after** role/access scope so that users never see projects outside their visibility, regardless of FY.

---

## 8. Dashboard UI Changes

### New Control: Financial Year Dropdown

- **Label:** e.g. "Financial Year"
- **Options:** From `FinancialYearHelper::listAvailableFY()` (e.g. `["2022-23", "2023-24", "2024-25", "2025-26", "2026-27"]`).
- **Default:** Current FY via `FinancialYearHelper::currentFY()`.
- **Behaviour:** On change, submit filter (full page or AJAX) with `fy=2024-25` (or equivalent); backend applies `inFinancialYear($fy)`.

### Placement by Dashboard

| Dashboard | Where to add dropdown |
|-----------|------------------------|
| Executor | Near existing filters / summary cards (e.g. top of dashboard or filter bar). |
| Coordinator | System dashboard and finance/approval dashboard: filter bar or summary section. Project list: with existing filters (province, status, project type, dates). |
| Provincial | Main dashboard filter area; optionally project list. |
| General | Dashboard filter area for coordinator hierarchy and direct team views. |
| Budget Report | Filters section alongside project type, status, and date range. |

### Accessibility

- Ensure dropdown is in a form or sends a clear query parameter so that links and bookmarks can preserve FY (e.g. `?fy=2024-25`).

---

## 9. Performance Considerations

### Query Shape

- FY filter adds: `WHERE commencement_month_year BETWEEN ? AND ?` (and optionally `AND commencement_month_year IS NOT NULL`).
- Combined with existing filters (status, user scope, etc.), this remains index-friendly.

### Optional Index

- **Index:** `projects(commencement_month_year)`.
- **When to add:** If dashboard or report queries become slow on large datasets (e.g. thousands of projects). Measure first; add index in a migration only when profiling shows benefit.
- **Note:** Do not add in this planning phase; treat as a future optimisation step.

### Caching

- `listAvailableFY()` can be cached (e.g. per request or short TTL) to avoid repeated computation.
- Dashboard totals can be cached per (user/role scope, FY) with short TTL if needed; ensure cache invalidation on approval/revert.

---

## 10. Implementation Phases

| Phase | Purpose | Affected Files (Conceptual) | Risk Level |
|-------|---------|-----------------------------|------------|
| **Phase 1 — FinancialYearHelper** | Central FY logic and API for the app | New: `app/Support/FinancialYearHelper.php` (or Helpers). Tests: `FinancialYearHelperTest`. | Low |
| **Phase 2 — Service Query Layer** | Reusable FY filter and service-level FY support | `app/Models/OldProjects/Project.php` (scope). ProjectQueryService, ProjectAccessService (optional FY param). Tests: feature/unit for scope. | Low |
| **Phase 2.5 — Aggregation Consistency Fix** | Align dashboard financial aggregation with ProjectFinancialResolver before FY filtering | CoordinatorController, ProvincialController. See Phase 2.5 section below. | Medium |
| **Phase 3 — Dashboard FY Integration** | Apply FY filter in controllers | CoordinatorController, ProvincialController, GeneralController, ExecutorController, BudgetExportController. | Medium |
| **Phase 4 — UI filter implementation** | FY dropdown and request handling | Views and optional JS for coordinator, provincial, general, executor dashboards; budget report view. | Low |
| **Phase 5 — FY Analytics Enhancements** (optional) | Dedicated FY summaries or charts | New or extended dashboard partials; controller methods returning FY-specific stats. | Low |

### Phase Dependencies

- Phase 2 depends on Phase 1 (scope uses Helper).
- Phase 2.5 depends on Phase 2; ensures consistent resolver usage before Phase 3 adds FY filter.
- Phases 3 and 4 can be done together per dashboard (backend + dropdown).
- Phase 5 can follow once Phase 3–4 are stable.

---

## Phase 2.5 — Aggregation Consistency Fix

### Purpose

Ensure financial aggregation across dashboards uses **ProjectFinancialResolver** consistently. This phase resolves inconsistencies identified in **Dashboard_Financial_Aggregation_Audit_20260304.md** and ensures all dashboards use the same canonical source before FY filtering is introduced in Phase 3.

---

### Fix 1 — Coordinator Budget Overview

**File:** `CoordinatorController`  
**Method:** `getSystemBudgetOverviewData()`

**Current logic:** Uses raw `$p->opening_balance` for approved project budget totals (e.g. `$approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0))` and in breakdowns by type, province, center, provincial).

**Required change:** Replace raw `opening_balance` aggregation with ProjectFinancialResolver results.

**Example:**

- **Before:** `$totalBudget = $approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));`
- **After:** Resolve once per project (memoize), then sum:  
  `$resolvedFinancials[$p->project_id] = $resolver->resolve($p);`  
  `$totalBudget = $approvedProjects->sum(fn ($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0));`

**Purpose:** Ensure financial calculations remain consistent with other coordinator dashboards (e.g. `getSystemPerformanceData`, `getSystemAnalyticsData`), which already use the resolver.

---

### Fix 2 — Provincial Center Performance

**File:** `ProvincialController`  
**Method:** `calculateCenterPerformance()`

**Current logic:** Uses raw `$p->opening_balance` for approved projects:  
`$centerBudget = (float) ($approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0)) ?? 0);`

**Required change:** Replace raw sums with resolver-based values. Resolve each approved project once (memoize), then aggregate `opening_balance` from resolver results.

**Purpose:** Align provincial dashboards with executor and coordinator aggregation logic so that center-wise totals use the same canonical source as other provincial widgets (e.g. `calculateBudgetSummariesFromProjects`, `calculateTeamPerformanceMetrics`).

---

### Fix 3 — Provincial Society Statistics Strategy

**File:** `ProvincialController`  
**Context:** Society statistics (when province has more than one active society) currently use raw DB aggregation:

- **Approved totals:** `Project::where(...)->selectRaw('society_id, SUM(COALESCE(amount_sanctioned, 0))...')->groupBy('society_id')`
- **Pending totals:** `Project::where(...)->selectRaw('...SUM(GREATEST(0, COALESCE(overall_project_budget, 0) - ...))...')->groupBy('society_id')`

**Decision required:**

| Option | Description | Trade-offs |
|--------|-------------|------------|
| **Option A (Recommended)** | Use ProjectFinancialResolver per project and aggregate results (e.g. load projects by society, resolve each, sum `opening_balance` / `amount_requested` per society). | **Pros:** Consistent with rest of app; type-specific and phase-based logic respected. **Cons:** More queries or in-memory work per society. |
| **Option B** | Keep raw DB aggregation for performance but document that these statistics may diverge from resolver calculations (e.g. for phase-based or type-specific projects). | **Pros:** Single aggregated query; fast. **Cons:** Society totals may not match resolver-derived totals shown elsewhere; audit trail should state "raw DB, not resolver". |

**Recommendation:** Option A for consistency; if performance is a concern, implement Option A first and profile; consider Option B only if profiling shows a clear need, with explicit documentation of the divergence.

---

## Phase Execution Log

| Phase | Status | Execution Date | Audit Report | Notes |
|-------|--------|----------------|--------------|-------|
| Phase 1 — FinancialYearHelper | Completed | 2026-03-04 | FY_Phase1_Post_Implementation_Audit_20260304.md | FinancialYearHelper created and Project scope added. |
| Phase 2 — Service Query Layer | Completed | 2026-03-04 | FY_Phase2_Post_Implementation_Audit_20260304.md | Service-level FY filtering support added. |
| Phase 2.5 — Aggregation Consistency | Completed | 2026-03-04 | Phase_2.5_Post_Implementation_Audit_20260304.md | Coordinator and Provincial dashboards now use ProjectFinancialResolver consistently. |
| Phase 3 — Dashboard FY Integration | Completed | 2026-03-04 | FY_Phase3_Post_Implementation_Audit_20260304.md | Dashboards now support Financial Year filtering with current FY default. |
| Phase 4 — UI filter implementation | Pending | — | — | — |
| Phase 5 — FY Analytics Enhancements | Pending | — | — | — |

---

## 11. Regression Risk Analysis

| Risk | Mitigation |
|------|------------|
| **Incorrect FY derivation** | Unit tests for FinancialYearHelper (fromDate, startDate, endDate) with edge cases (Apr 1, Mar 31, month &lt; 4 and ≥ 4). Use Helper everywhere; no duplicate FY logic. |
| **Resolver bypass** | Keep using resolver for per-project amounts where already in use; FY only narrows the set of projects. Do not introduce new `sum('overall_project_budget')` or similar for FY totals. |
| **Dashboard query conflicts** | Apply FY only when user selects an FY (or default current FY). When FY is not applied, keep current behaviour (all-time or existing filters). Document default (e.g. current FY) so behaviour is clear. |
| **Legacy projects without commencement** | Audits show all approved projects have `commencement_month_year`. Scope excludes nulls; non-approved with null commencement are omitted from FY filter. Acceptable for FY dashboard. |
| **Role scope bypass** | Always apply role/access filter before FY in code and in tests; add tests that assert role visibility with FY filter. |

---

## 12. Testing Plan

### Scenarios

1. **FY derivation**
   - Project with `commencement_month_year` 2024-08-01 appears in FY 2024-25.
   - Project with 2024-02-10 appears in FY 2023-24.
   - Boundary: 2024-04-01 → 2024-25; 2025-03-31 → 2024-25.

2. **Dashboard aggregation**
   - With FY 2024-25 selected, totals only include projects whose commencement falls in 2024-04-01–2025-03-31.
   - Default FY (current) matches expectation for current FY.

3. **Role visibility**
   - Executor sees only own projects in selected FY.
   - Provincial sees only accessible projects in selected FY.
   - Coordinator/General see all projects in selected FY (no extra restriction beyond FY).

4. **Resolver consistency**
   - Sum of resolver `opening_balance` for approved projects in FY equals (or is consistent with) dashboard total for that FY.

5. **Null commencement**
   - Projects with null `commencement_month_year` do not appear in any FY filter result (or document if a fallback is used later).

### Test Types

- Unit: FinancialYearHelper.
- Feature: Dashboard endpoints with `?fy=2024-25` and role-based access.
- Regression: Existing dashboard totals without FY filter (or with default FY) remain correct.

---

## 13. Final Architectural Verdict

### SAFE FOR IMPLEMENTATION

**Reasons**

1. **Stable base:** Budget approval, resolver, and role-based access are in place and audited.
2. **Reliable data:** All approved projects have `commencement_month_year`; FY derivation is well-defined and testable.
3. **Additive change:** FY is a new filter and helper; existing flows stay intact when FY is not used or when default is current FY.
4. **Clear scope:** Single helper, one model scope, and controller/view changes are scoped and phased.
5. **No schema change required:** Uses existing `commencement_month_year`; optional index later if needed.
6. **Risk control:** Order of operations (role → status → FY → aggregate) preserves security; resolver remains canonical; tests can lock behaviour.

Proceed with implementation according to the phases above, with Phase 1–2 first, then Phase 3–4 per dashboard, and Phase 5 as optional enhancement.

---

## Architecture Evolution Notes

*Purpose: Track architectural decisions or improvements that occur during implementation.*

| Date | Decision / Change | Rationale |
|------|-------------------|-----------|
| 2026-03-04 | Added FinancialYearHelper and Project::inFinancialYear scope. | Phase 1 implementation: central FY logic and model scope for dashboard filtering. |
| 2026-03-04 | Phase 2 introduced optional FY parameter in ProjectQueryService and ProjectAccessService. | Service-layer integration for FY filtering; backward compatible when FY not provided. |
| 2026-03-04 | Added Phase 2.5 to resolve dashboard aggregation inconsistencies before introducing FY filtering. | Align all dashboard financial totals with ProjectFinancialResolver (CoordinatorController getSystemBudgetOverviewData, ProvincialController calculateCenterPerformance, provincial society stats) so FY filter in Phase 3 is applied on a consistent aggregation base. |

---

## Known Future Optimizations

- **Optional DB index on `projects(commencement_month_year)`** — Add if dashboard or report queries become slow on large project datasets. Profile first; introduce via migration when justified.
- **FY caching strategy** — Cache `listAvailableFY()` (per request or short TTL) and optionally dashboard totals per (user/role scope, FY) with appropriate cache invalidation on approval/revert.
- **Dashboard performance monitoring** — Monitor response times for FY-filtered dashboard endpoints; set thresholds and alerts if degradation is observed.

---

*Plan produced in planning-only mode. No code or database was modified.*
