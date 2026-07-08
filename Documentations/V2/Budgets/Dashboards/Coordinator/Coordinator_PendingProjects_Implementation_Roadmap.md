# Coordinator Pending Projects — Full Architecture Upgrade Roadmap

**Updated After Feasibility Audit — 2026-03-08**

---

**Date:** 2026-03-08  
**Route:** `GET /coordinator/projects-list?status=forwarded_to_coordinator`  
**Controller:** `CoordinatorController::projectList()`  
**View:** `resources/views/coordinator/ProjectList.blade.php`  
**Reference Audit:** [Coordinator_PendingProjects_Feature_Audit.md](./Coordinator_PendingProjects_Feature_Audit.md)  
**Feasibility Audit:** [Coordinator_PendingProjects_Roadmap_Feasibility_Audit.md](./Coordinator_PendingProjects_Roadmap_Feasibility_Audit.md)

**Scope:** Planning only — no code modifications.

---

## 1. Current Architecture

### 1.1 Request Flow

```
Request (GET /coordinator/projects-list)
    ↓
CoordinatorController::projectList()
    ↓
ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)
    ↓
Apply filters: search, province, provincial_id, user_id, center, project_type, status, date range
    ↓
Apply sort (query or post-fetch for budget_utilization)
    ↓
Manual pagination: skip()->take(100)->get()
    ↓
Per-project loop: $resolver->resolve($project)  [N+1]
    ↓
Per-project: DPReport::approved()->where(...) + DPAccountDetail::whereIn(...)  [Expense N+1]
    ↓
Filter options from Cache::remember('coordinator_project_list_filters', 5 min)
    ↓
View: coordinator/ProjectList.blade.php
```

### 1.2 Key Components

| Component | Current Usage |
|-----------|---------------|
| Access Layer | `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` |
| FY Scope | Applied via ProjectAccessService |
| Filters | search, province, provincial_id, user_id, center, project_type, status, start_date, end_date, sort_by, sort_order |
| Financial Resolution | Per-project `ProjectFinancialResolver::resolve()` in `map()` — **N+1** |
| Expense Calculation | Per-project `DPReport::approved()->where(...)` + `DPAccountDetail::whereIn(...)` — **~200 extra queries per page** |
| Pagination | Manual `skip()->take($perPage)->get()`, fixed 100 per page |
| Filter Options | `Cache::remember('coordinator_project_list_filters', 5 min)` — not FY-scoped |
| ProjectQueryService | **Not used** |
| DatasetCacheService | **Not used** (dashboard uses it; project list does not) |
| Grand Totals | **None** |
| Status Distribution | **None** |

### 1.3 Audit Validations

The feasibility audit confirmed the following service availability:

| Service / Method | Status | Notes |
|------------------|--------|-------|
| `ProjectQueryService::forCoordinator($coordinator, $fy)` | ✓ Exists | Delegates to ProjectAccessService; use for consistency |
| `DatasetCacheService::getCoordinatorDataset(...)` | ✓ Exists | **Should NOT be used for project list** — filter-heavy page; low cache hit rate |
| `ProjectFinancialResolver::resolveCollection(Collection)` | ✓ Exists | Returns `[project_id => financials]`; use for batch resolution |
| `TableFormatter::resolvePerPage($request)` | ✓ Exists | Validates against [10, 25, 50, 100] |
| `TableFormatter::ALLOWED_PAGE_SIZES` | ✓ Exists | `[10, 25, 50, 100]` |
| `TableFormatter::resolveSerial()` | ✓ Exists | S.No. column with pagination support |

---

## 2. Identified Gaps

### 2.1 Architecture Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| Per-project financial resolver (N+1) | **Critical** | `$resolver->resolve($project)` called in loop; should use `resolveCollection()` |
| Expense N+1 | **Critical** | `DPReport::approved()->where(...)` + `DPAccountDetail::whereIn(...)` per project — ~200 extra queries per page |
| Manual pagination | **High** | Uses `skip()->take()->get()` instead of `->paginate()->withQueryString()` |
| No ProjectQueryService usage | **Medium** | Controller calls ProjectAccessService directly; could use `ProjectQueryService::forCoordinator()` for consistency |
| No dataset reuse | **High** | Full dataset not loaded for metrics; no shared resolvedFinancials map |
| No grand totals / status distribution | **High** | Missing summary block and status breakdown |

### 2.2 Performance Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| N+1 resolver calls | **Critical** | 100 projects = 100 resolver executions per page load |
| Expense N+1 | **Critical** | 100 projects = ~200 extra queries (DPReport + DPAccountDetail per project) |
| Non-FY-scoped filter queries | **Medium** | Filter dropdown options (project types, centers, users) ignore selected FY |
| No batch resolution | **Critical** | `resolveCollection()` would resolve 100 projects in one pass |

### 2.3 UI Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| Grand totals summary block | **High** | Provincial has summary card; Coordinator lacks |
| Status distribution cards | **High** | No at-a-glance status breakdown |
| Status chart modal | **Medium** | Provincial has ApexCharts donut; Coordinator lacks |
| Per-page selector | **Medium** | Fixed 100; Provincial offers 10, 25, 50, 100 |
| TableFormatter::resolvePerPage | **Medium** | Not used; pagination metadata is manual |

### 2.4 Filter Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| FY-scoped filter options | **Medium** | Project types, centers, users should be limited to selected FY |
| Filter cache key | **Low** | Cache key does not include FY; stale options when FY changes |
| Reset behaviour | **Low** | Clear button works; could align with Provincial (reset to current FY with query params) |

---

## 3. Target Architecture

### 3.1 Final Pipeline

```
Request (GET /coordinator/projects-list)
    ↓
ProjectQueryService::forCoordinator($coordinator, $fy)  [or ProjectAccessService]
    ↓
Base Query (Builder)
    ↓
Apply request filters: search, province, provincial_id, user_id, center, project_type, status, date range, sort
    ↓
Full dataset (for grand totals + status distribution): (clone $baseQuery)->with([...])->get()
    ↓
ProjectFinancialResolver::resolveCollection($fullDataset)
    ↓
resolvedFinancials map (reused by table, totals, status)
    ↓
Grand totals + Status distribution (from full dataset + resolvedFinancials)
    ↓
Paginated listing: (clone $baseQuery)->paginate($perPage)->withQueryString()
    ↓
Attach budget_utilization, health_status from resolvedFinancials to page items (NO recalculation)
    ↓
View: coordinator/ProjectList.blade.php
```

### 3.2 DatasetCacheService — Not Recommended for Project List

**Evaluation:**

| Factor | Assessment |
|--------|------------|
| Filter-heavy page | Project list has 10+ filters; cache key would need to include filter hash |
| Search filter | Text search cannot be applied in-memory on cached collection |
| Status filter | Highly variable; cache hit rate would be low |
| Date range filter | Same; low cache reuse |
| Memory | Loading full FY dataset (all projects) for Coordinator could be large |
| Invalidation | Many filter combinations; complex cache invalidation |

**Recommendation:** **Do NOT use DatasetCacheService for coordinator project list.** The page is filter-heavy; caching a full FY dataset and applying filters in-memory would not align with search/status/date filters. Provincial project list also does not use DatasetCacheService. Keep DatasetCacheService for the **coordinator dashboard** only.

### 3.3 ProjectQueryService Integration

**Recommendation:** **Use `ProjectQueryService::forCoordinator($coordinator, $fy)`** as the base query builder for consistency with the coordinator dashboard and to centralize access logic.

**Responsibilities:**
- FY scoping via `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)`
- Role visibility (coordinator = global)
- Returns `Builder`; controller applies request filters (search, province, provincial_id, user_id, center, project_type, status, date range, sort)

**No changes required to ProjectQueryService** — it already delegates to ProjectAccessService. The controller would switch from:
```php
$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)
```
to:
```php
ProjectQueryService::forCoordinator($coordinator, $fy)
```

---

## 4. Phase-by-Phase Implementation Plan

### Phase 1 — Pagination Architecture

**Goal:** Replace manual pagination with Laravel paginator; add per-page selector.

**Scope:**
- Replace `skip()->take()->get()` with `->paginate($perPage)->withQueryString()`
- Introduce `TableFormatter::resolvePerPage($request)`
- Add per-page selector UI (10, 25, 50, 100)
- Update Blade to use `$projects->links()` and `$projects->firstItem()` for serial numbers

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`
- `resources/views/coordinator/ProjectList.blade.php`

**Risks:** Low. Pagination logic changes; URL structure (page param) remains compatible.

**Expected Benefits:**
- Standard Laravel pagination with query string persistence
- User-selectable page size
- Consistent with Provincial and TableFormatter standards

**Dependencies:** None.

---

### Phase 2 — Financial Resolver Optimization + Expense N+1 Elimination

**Goal:** Replace per-project `resolve()` with batch `resolveCollection()`, and eliminate expense N+1 by using eager-loaded relations.

**Scope — Part 1: Financial Resolver Optimization**
- Load paginated projects (or full dataset for Phase 4; see below)
- Call `ProjectFinancialResolver::resolveCollection($projects)` once
- Replace `$resolver->resolve($project)` in `map()` with lookup from `$resolvedFinancials[$project->project_id]`
- Attach `calculated_budget`, `calculated_expenses`, `calculated_remaining`, `budget_utilization`, `health_indicator` from map

**Scope — Part 2: Expense N+1 Elimination**

**Current N+1 issue (CoordinatorController, lines 620–627):**
For each project, the controller runs:
```php
$projectApprovedReportIds = DPReport::approved()
    ->where('project_id', $project->project_id)
    ->pluck('report_id');
$totalExpenses = DPAccountDetail::whereIn('report_id', $projectApprovedReportIds)
    ->sum('total_expenses') ?? 0;
```
- Result: **2 extra queries per project** — for 100 projects, **~200 extra queries per page**
- Root cause: The controller already eager-loads `reports.accountDetails` (line 516). Provincial computes expenses in-memory from these relations. Coordinator incorrectly re-queries instead.

**Replace with in-memory expense calculation** (mirror Provincial pattern):
```php
$totalExpenses = 0;
foreach ($project->reports ?? [] as $report) {
    if ($report->isApproved() && $report->accountDetails) {
        $totalExpenses += $report->accountDetails->sum('total_expenses');
    }
}
```

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`

**Risks:** Low. Same output; different resolution path. Must ensure resolvedFinancials keys match project_id; expense calculation uses already eager-loaded data.

**Expected Benefits:**
- Eliminates N+1 resolver calls (100 → 1 per page)
- Eliminates expense N+1 (~200 → 0 extra queries per page)
- **Total: ~300 queries per page → ~5–10 queries per page**
- Significant performance improvement; aligns with Provincial/Executor patterns

**Dependencies:** None. Can be done independently of Phase 1.

**Note:** For Phase 2, resolve only the **paginated page** (current page items). Grand totals and status distribution require full dataset and are Phase 4/5.

---

### Phase 3 — Query Layer Refactor (ProjectQueryService)

**Goal:** Use ProjectQueryService for base query consistency.

**Scope:**
- Replace `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` with `ProjectQueryService::forCoordinator($coordinator, $fy)`
- No behavioural change (ProjectQueryService delegates to ProjectAccessService)
- Improves consistency with coordinator dashboard

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`

**Risks:** Very low. Drop-in replacement.

**Expected Benefits:**
- Centralised query entry point
- Easier future refactoring (e.g. if ProjectQueryService adds coordinator-specific logic)

**Dependencies:** None. Can be done early (e.g. with Phase 1 or 2).

---

### Phase 4 — Summary Metrics (Grand Totals)

**Goal:** Add grand totals summary block above table.

**Scope:**
- Load full filtered dataset (before pagination): `(clone $baseQuery)->with([...])->get()`
- Call `resolveCollection($fullDataset)` once (or reuse from Phase 2 if full dataset is loaded there)
- Compute grand totals: overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, amount_requested, opening_balance
- Pass `$grandTotals`, `$totalRecordCount` to view
- Add summary card UI (same structure as Provincial)

**Architecture Note — Dataset Flow:**
```
baseQuery
  → fullDataset (clone + with([...])->get())
  → resolveCollection($fullDataset)
  → grandTotals + statusDistribution (from full dataset + resolvedFinancials)
  → paginate (clone baseQuery) for listing
  → attach financial metrics (budget_utilization, health_status) from resolvedFinancials map to page items
```

**Important:** Page items must receive `budget_utilization`, `health_status`, and expense-related fields **from the resolvedFinancials map** — do **NOT** recalculate per page item. Use the same resolvedFinancials map for both grand totals and the paginated table.

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`
- `resources/views/coordinator/ProjectList.blade.php`

**Risks:** Medium. Loading full dataset for large result sets (e.g. 5000+ projects) could increase memory and time. Mitigation: consider count-based limit or aggregated SUM queries for very large sets.

**Expected Benefits:**
- At-a-glance financial overview
- Parity with Provincial page
- Shared resolvedFinancials map for table + totals

**Dependencies:** Phase 2 (resolveCollection). Phase 4 extends the pattern: full dataset → resolveCollection → grand totals + paginated listing.

---

### Phase 5 — Status Distribution

**Goal:** Add status distribution cards and optional chart modal.

**Scope:**
- Compute `$statusDistribution = $fullDataset->groupBy('status')->map->count()` (already available if Phase 4 loads full dataset)
- Pass to view
- Add status cards above table (6 cards, same as Provincial)
- Add status chart modal (ApexCharts donut) — optional

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`
- `resources/views/coordinator/ProjectList.blade.php`

**Risks:** Low. Reuses full dataset from Phase 4; no additional queries.

**Expected Benefits:**
- Visual status breakdown
- Parity with Provincial
- Better UX for coordinators

**Dependencies:** Phase 4 (full dataset for status counts).

---

### Phase 6 — Dataset Reuse & Flow Alignment

**Goal:** Unify controller flow: single full-dataset load, single resolveCollection, shared map.

**Scope:**
- Consolidate flow: baseQuery → fullDataset → resolveCollection → grandTotals + statusDistribution → paginate
- Ensure paginated page items receive budget_utilization, health_status from resolvedFinancials (no second resolver run)
- Remove any redundant queries
- Document flow in controller comments

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`

**Risks:** Low. Refactor only; behaviour unchanged if Phases 2, 4, 5 are done correctly.

**Expected Benefits:**
- Single source of truth for financials
- Clear, maintainable pipeline
- Matches Provincial architecture

**Dependencies:** Phases 2, 4, 5.

---

### Phase 7 — Filter Improvements

**Goal:** FY-scope filter dropdown options; tune filter cache.

**Scope:**
- **FY-scoped filter options:** Limit project types, centers, users to those with projects in selected FY
  - Project types: `Project::inFinancialYear($fy)->distinct()->pluck('project_type')` (with coordinator scope)
  - Centers: From users with projects in FY
  - Users: Executors/applicants with projects in FY
- **Filter cache:** Include `$fy` in cache key: `coordinator_project_list_filters_{$fy}` or compute filter options per request when FY-scoping is added (simpler)
- Keep search, status, province, provincial_id as-is

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`
- Possibly remove or shorten filter cache TTL when FY-scoping is added

**Risks:** Low. Filter options become FY-dependent; users may see fewer options when FY has fewer projects.

**Expected Benefits:**
- Relevant filter options only
- Parity with Provincial filter scoping
- Avoids stale cross-FY options

**Dependencies:** None. Independent of other phases.

---

### Phase 8 — UI Consistency & Polish

**Goal:** Align UX with Provincial; preserve filters on redirect.

**Scope:**
- Add tooltips where appropriate (feather icons, Bootstrap tooltips)
- Consider budget column naming: keep Coordinator's Budget/Expenses/Remaining/Utilization or align to Provincial's Overall/Existing/Local/Balance (document decision)
- **Preserve filters on redirect:** After approve/revert, redirect with `request()->query()` or `redirect()->route('coordinator.projects.list', request()->query())` so user returns to same filter state
- Ensure Active Filters badges remain accurate
- Table layout consistency (responsive, column widths)

**Files Affected:**
- `resources/views/coordinator/ProjectList.blade.php`
- Approve/revert redirect logic (may be in ProjectController or CoordinatorController)

**Risks:** Low. UI-only; redirect change is isolated.

**Expected Benefits:**
- Better UX
- Filter state preserved after actions
- Consistent look with Provincial

**Dependencies:** None. Can be done at any time.

#### Health Indicator Alignment

Provincial uses three levels (good, warning, critical). Coordinator currently includes an additional "moderate" band (≥50% utilization). For consistency across dashboards, Coordinator will adopt the Provincial model.

**New logic (aligned with Provincial):**

| Level | Condition |
|-------|-----------|
| good | utilization ≤ 75% |
| warning | utilization > 75% |
| critical | utilization > 90% |

This change ensures consistent health indicators across Provincial project lists, Coordinator project lists, and Executor dashboards.

---

### Phase 9 — Cache Layer (Documentation)

**Goal:** Document DatasetCacheService usage decision for project list.

**Scope:**
- Document that DatasetCacheService is **not** used for coordinator project list
- Reason: filter-heavy (search, status, date range, etc.); low cache hit rate; search cannot be applied in-memory
- No implementation

**Files Affected:** Documentation only.

**Risks:** None.

**Expected Benefits:** Clear architectural decision; avoids future misapplication of dataset cache.

**Dependencies:** None.

---

## 5. Phase Dependencies

```
Phase 2 (Resolver + Expense N+1 Fix)  [First — performance-critical]
        │
        ├──► Phase 1 (Pagination)     [Independent; execute after Phase 2]
        │
        └──► Phase 4 (Grand Totals) ──► Phase 5 (Status Dist.)
                                              │
                                              ▼
                             Phase 6 (Dataset Reuse) ◄───┘

Phase 3 (Query Layer)    ───► Independent (can run after Phase 2)
Phase 7 (Filters)        ───► Independent
Phase 8 (UI Polish)      ───► Independent
Phase 9 (Cache Docs)     ───► Documentation only
```

**Recommended Order:**
1. **Phase 2** — Resolver optimization + Expense N+1 elimination (performance-critical)
2. **Phase 1** — Pagination architecture
3. **Phase 3** — Query layer refactor
4. **Phase 4** — Grand totals
5. **Phase 5** — Status distribution
6. **Phase 6** — Dataset reuse
7. **Phase 7** — Filter improvements
8. **Phase 8** — UI polish
9. **Phase 9** — Cache documentation

#### Implementation Strategy Adjustment

Resolver + expense N+1 elimination removes ~200 database queries per page and provides the largest performance improvement. Executing this phase first ensures immediate system-wide performance gains even before pagination and UI changes are introduced. Pagination and query-layer refactors can then proceed on a more efficient data pipeline.

---

## 6. Risk Analysis

### 6.1 Per-Phase Risk

| Phase | Risk Level | Performance Impact | Possible Regressions |
|-------|------------|--------------------|----------------------|
| 1 | Low | Neutral | Pagination URL change; ensure links preserve all params |
| 2 | Low | **High positive** | Resolver output must match; verify keyed by project_id; expense calc must use eager-loaded data |
| 3 | Very Low | None | Behaviour identical |
| 4 | Medium | Negative on huge result sets | Memory spike with 5000+ projects; consider limit |
| 5 | Low | None | Chart JS dependency; ensure ApexCharts loaded |
| 6 | Low | None | Logic consolidation only |
| 7 | Low | Slight increase (FY-scoped queries) | Filter options may be fewer |
| 8 | Low | None | Redirect must preserve query string |
| 9 | None | None | Documentation |

### 6.2 Pre-Implementation Risks (Current Codebase)

| Risk | Severity | Description | Mitigation |
|------|----------|-------------|------------|
| Resolver N+1 | **Critical** | 100 `resolve()` calls per page | Phase 2: use `resolveCollection()` — single batch |
| **Expense N+1** | **Critical** | `DPReport::approved()->where(...)` + `DPAccountDetail::whereIn(...)` per project — ~200 extra queries per page | **Phase 2:** in-memory expense calculation using eager-loaded `$project->reports` and `$report->accountDetails` (Provincial pattern) |
| Full dataset memory (Phase 4) | Medium | 5000+ projects in one collection | Limit (e.g. 10000) or SQL aggregation for totals |
| Resolver key mismatch | Low | resolvedFinancials keys vs project_id | Use `$project->project_id` consistently; add assertion in dev |
| Pagination regression | Low | Links lose filter params | Ensure `withQueryString()` used |
| Filter cache staleness | Low | FY change leaves stale options | Include FY in cache key or compute per-request |

### 6.3 Mitigation Strategies

| Risk | Mitigation |
|------|------------|
| Full dataset memory | For Phase 4, consider `$baseQuery->limit(10000)->get()` or aggregated SUM/count queries for totals if dataset exceeds threshold |
| Resolver key mismatch | Use `$project->project_id` consistently; add assertion in dev |
| Pagination regression | Test with all filter combinations; ensure `withQueryString()` used |
| Filter cache staleness | Include FY in cache key or compute per-request when FY-scoping added |

---

## 7. Expected Performance Improvements

| Improvement | Before | After | Impact |
|-------------|--------|-------|--------|
| **Queries per page** | **~300** (resolver N+1 + expense N+1 + base) | **~5–10** (base, full dataset, resolveCollection, filter options) | **~97% reduction** |
| Resolver calls per page | 100 (N+1) | 1 (batch) | ~99% reduction |
| Expense queries per page | ~200 (2 per project) | 0 (in-memory from eager-loaded relations) | 100% elimination |
| Grand totals | None | Single pass over full dataset | New feature |
| Status distribution | None | From same full dataset | New feature |
| Pagination | Manual, fixed 100 | Laravel paginator, 10/25/50/100 | UX + robustness |
| Filter options | All FYs | FY-scoped | Relevance; minor query cost |

**Estimated:** **50–80% faster page loads** for typical 100-item pages (depending on resolver cost vs DB query cost).

---

## 8. Future Enhancements

| Enhancement | Description | Priority |
|-------------|-------------|----------|
| Export | Add Excel/CSV export (similar to Provincial `projectsExport` placeholder) | Medium |
| Advanced filters | Consider moving more filters to primary row; improve Advanced Filters UX | Low |
| Aggregated grand totals | For very large datasets (10k+ projects), use SQL SUM/COUNT instead of loading full collection | Low |
| S.No column | Add serial number column with `TableFormatter::resolveSerial()` for consistency with Provincial | Low |
| Filter presets | Coordinator has `$filterPresets` in session; could add save/load preset UI | Low |

---

## 9. Summary

| Category | Current | Target |
|----------|---------|--------|
| **Query** | ProjectAccessService | ProjectQueryService::forCoordinator (optional) |
| **Financial Resolution** | Per-project resolve (N+1) | resolveCollection (batch) |
| **Expense Calculation** | Per-project DB queries (N+1) | In-memory from eager-loaded relations |
| **Pagination** | Manual skip/take | Laravel paginate + withQueryString |
| **Per-page** | Fixed 100 | 10, 25, 50, 100 |
| **Grand Totals** | None | Summary block |
| **Status Distribution** | None | Cards + optional chart |
| **Dataset Cache** | Not used | Not used (by design) |
| **Filter Scoping** | Global | FY-scoped |
| **Filter Cache** | 5 min, no FY | FY in key or per-request |
| **Health Indicator Logic** | 4 levels (good, moderate, warning, critical) | Aligned with Provincial (good, warning, critical) |

The roadmap is designed for **incremental, low-risk delivery**. Phase 2 delivers immediate performance gains (elimination of ~300 queries per page); Phase 1 adds pagination UX; Phases 3–6 complete the architecture; Phases 7–9 add filter improvements, UI polish, and documentation. DatasetCacheService is explicitly **not** recommended for the project list due to its filter-heavy nature.
