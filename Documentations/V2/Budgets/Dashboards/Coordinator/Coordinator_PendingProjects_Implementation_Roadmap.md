# Coordinator Pending Projects — Full Architecture Upgrade Roadmap

**Date:** 2026-03-08  
**Route:** `GET /coordinator/projects-list?status=forwarded_to_coordinator`  
**Controller:** `CoordinatorController::projectList()`  
**View:** `resources/views/coordinator/ProjectList.blade.php`  
**Reference Audit:** [Coordinator_PendingProjects_Feature_Audit.md](./Coordinator_PendingProjects_Feature_Audit.md)

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
| Pagination | Manual `skip()->take($perPage)->get()`, fixed 100 per page |
| Filter Options | `Cache::remember('coordinator_project_list_filters', 5 min)` — not FY-scoped |
| ProjectQueryService | **Not used** |
| DatasetCacheService | **Not used** (dashboard uses it; project list does not) |
| Grand Totals | **None** |
| Status Distribution | **None** |

### 1.3 Service Availability

| Service | Status | Used by projectList? |
|---------|--------|----------------------|
| ProjectQueryService::forCoordinator() | ✓ Exists | No |
| DatasetCacheService::getCoordinatorDataset() | ✓ Exists | No (dashboard only) |
| ProjectFinancialResolver::resolveCollection() | ✓ Exists | No |

---

## 2. Identified Gaps

### 2.1 Architecture Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| Per-project financial resolver (N+1) | **Critical** | `$resolver->resolve($project)` called in loop; should use `resolveCollection()` |
| Manual pagination | **High** | Uses `skip()->take()->get()` instead of `->paginate()->withQueryString()` |
| No ProjectQueryService usage | **Medium** | Controller calls ProjectAccessService directly; could use `ProjectQueryService::forCoordinator()` for consistency |
| No dataset reuse | **High** | Full dataset not loaded for metrics; no shared resolvedFinancials map |
| No grand totals / status distribution | **High** | Missing summary block and status breakdown |

### 2.2 Performance Gaps

| Gap | Severity | Description |
|-----|----------|-------------|
| N+1 resolver calls | **Critical** | 100 projects = 100 resolver executions per page load |
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

### 2.5 Categorization by Impact

| Category | Critical | High | Medium | Low |
|----------|----------|------|--------|-----|
| Architecture | 1 | 2 | 1 | 0 |
| Performance | 2 | 0 | 1 | 0 |
| UX/UI | 0 | 2 | 2 | 0 |
| Query Layer | 0 | 0 | 1 | 0 |
| Data Layer | 0 | 1 | 0 | 0 |
| Caching | 0 | 0 | 1 | 1 |

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
Attach budget_utilization, health_status from resolvedFinancials to page items
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

### Phase 2 — Financial Resolver Optimization

**Goal:** Replace per-project `resolve()` with single `resolveCollection()` call.

**Scope:**
- Load paginated projects (or full dataset for Phase 3; see below)
- Call `ProjectFinancialResolver::resolveCollection($projects)` once
- Replace `$resolver->resolve($project)` in `map()` with lookup from `$resolvedFinancials[$project->project_id]`
- Attach `calculated_budget`, `calculated_expenses`, `calculated_remaining`, `budget_utilization`, `health_indicator` from map

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`

**Risks:** Low. Same output; different resolution path. Must ensure resolvedFinancials keys match project_id.

**Expected Benefits:**
- Eliminates N+1 resolver calls (100 → 1 per page)
- Significant performance improvement on large pages
- Aligns with Provincial/Executor patterns

**Dependencies:** None. Can be done independently of Phase 1.

**Note:** For Phase 2, resolve only the **paginated page** (current page items). Grand totals and status distribution require full dataset and are Phase 3/4.

---

### Phase 3 — Summary Metrics (Grand Totals)

**Goal:** Add grand totals summary block above table.

**Scope:**
- Load full filtered dataset (before pagination): `(clone $baseQuery)->with([...])->get()`
- Call `resolveCollection($fullDataset)` once (or reuse from Phase 2 if full dataset is loaded there)
- Compute grand totals: overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, amount_requested, opening_balance
- Pass `$grandTotals`, `$totalRecordCount` to view
- Add summary card UI (same structure as Provincial)

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`
- `resources/views/coordinator/ProjectList.blade.php`

**Risks:** Medium. Loading full dataset for large result sets (e.g. 5000+ projects) could increase memory and time. Mitigation: consider count-based limit or aggregated SUM queries for very large sets.

**Expected Benefits:**
- At-a-glance financial overview
- Parity with Provincial page
- Shared resolvedFinancials map for table + totals

**Dependencies:** Phase 2 (resolveCollection). Phase 3 extends the pattern: full dataset → resolveCollection → grand totals + paginated listing.

**Architecture Note:** Provincial loads full dataset once, resolves once, computes grand totals, then paginates. Coordinator should follow the same flow: baseQuery → fullDataset (get) → resolveCollection → grandTotals + statusDistribution → paginate for listing → attach financials to page items from map.

---

### Phase 4 — Status Distribution

**Goal:** Add status distribution cards and optional chart modal.

**Scope:**
- Compute `$statusDistribution = $fullDataset->groupBy('status')->map->count()` (already available if Phase 3 loads full dataset)
- Pass to view
- Add status cards above table (6 cards, same as Provincial)
- Add status chart modal (ApexCharts donut) — optional

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`
- `resources/views/coordinator/ProjectList.blade.php`

**Risks:** Low. Reuses full dataset from Phase 3; no additional queries.

**Expected Benefits:**
- Visual status breakdown
- Parity with Provincial
- Better UX for coordinators

**Dependencies:** Phase 3 (full dataset for status counts).

---

### Phase 5 — Query Layer Refactor (ProjectQueryService)

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

### Phase 6 — Filter Improvements

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

### Phase 7 — Dataset Reuse & Flow Alignment

**Goal:** Unify controller flow: single full-dataset load, single resolveCollection, shared map.

**Scope:**
- Consolidate flow: baseQuery → fullDataset → resolveCollection → grandTotals + statusDistribution → paginate
- Ensure paginated page items receive budget_utilization, health_status from resolvedFinancials (no second resolver run)
- Remove any redundant queries
- Document flow in controller comments

**Files Affected:**
- `app/Http/Controllers/CoordinatorController.php`

**Risks:** Low. Refactor only; behaviour unchanged if Phases 2–4 are done correctly.

**Expected Benefits:**
- Single source of truth for financials
- Clear, maintainable pipeline
- Matches Provincial architecture

**Dependencies:** Phases 2, 3, 4.

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

---

### Phase 9 — Cache Layer (Optional; Not Recommended)

**Goal:** Evaluate and document DatasetCacheService usage for project list.

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
Phase 1 (Pagination)     ─┬─► Phase 2 (Resolver) ─┬─► Phase 3 (Grand Totals) ─┬─► Phase 4 (Status Dist.)
                         │                        │                           └─► Phase 7 (Dataset Reuse)
                         │                        │
Phase 5 (Query Layer)    ─┘                        └─► (Phase 2 can use paginated set only;
                                                         Phase 3 needs full dataset)
Phase 6 (Filters)       ───► Independent
Phase 8 (UI Polish)     ───► Independent
Phase 9 (Cache Eval)    ───► Documentation only
```

**Recommended Order:**
1. **Phase 1** — Pagination (low risk, high value)
2. **Phase 2** — Resolver optimization (critical performance)
3. **Phase 5** — Query layer (quick, improves consistency)
4. **Phase 3** — Grand totals (requires full dataset + resolveCollection)
5. **Phase 4** — Status distribution (depends on Phase 3)
6. **Phase 7** — Dataset reuse (consolidates flow after 2–4)
7. **Phase 6** — Filter improvements
8. **Phase 8** — UI polish
9. **Phase 9** — Cache evaluation (documentation)

---

## 6. Risk Analysis

### 6.1 Per-Phase Risk

| Phase | Risk Level | Performance Impact | Possible Regressions |
|-------|------------|--------------------|----------------------|
| 1 | Low | Neutral | Pagination URL change; ensure links preserve all params |
| 2 | Low | **High positive** | Resolver output must match; verify keyed by project_id |
| 3 | Medium | Negative on huge result sets | Memory spike with 5000+ projects; consider limit |
| 4 | Low | None | Chart JS dependency; ensure ApexCharts loaded |
| 5 | Very Low | None | Behaviour identical |
| 6 | Low | Slight increase (FY-scoped queries) | Filter options may be fewer |
| 7 | Low | None | Logic consolidation only |
| 8 | Low | None | Redirect must preserve query string |
| 9 | None | None | Documentation |

### 6.2 Mitigation Strategies

| Risk | Mitigation |
|------|------------|
| Full dataset memory | For Phase 3, consider `$baseQuery->limit(10000)->get()` or aggregated SUM/count queries for totals if dataset exceeds threshold |
| Resolver key mismatch | Use `$project->project_id` consistently; add assertion in dev |
| Pagination regression | Test with all filter combinations; ensure `withQueryString()` used |
| Filter cache staleness | Include FY in cache key or compute per-request when FY-scoping added |

---

## 7. Expected Performance Improvements

| Improvement | Before | After | Impact |
|-------------|--------|-------|--------|
| Resolver calls per page | 100 (N+1) | 1 (batch) | ~99% reduction |
| Grand totals | None | Single pass over full dataset | New feature |
| Status distribution | None | From same full dataset | New feature |
| Pagination | Manual, fixed 100 | Laravel paginator, 10/25/50/100 | UX + robustness |
| Filter options | All FYs | FY-scoped | Relevance; minor query cost |

**Estimated:** 50–80% reduction in controller execution time for typical page loads (depending on resolver cost vs DB query cost).

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
| **Pagination** | Manual skip/take | Laravel paginate + withQueryString |
| **Per-page** | Fixed 100 | 10, 25, 50, 100 |
| **Grand Totals** | None | Summary block |
| **Status Distribution** | None | Cards + optional chart |
| **Dataset Cache** | Not used | Not used (by design) |
| **Filter Scoping** | Global | FY-scoped |
| **Filter Cache** | 5 min, no FY | FY in key or per-request |

The roadmap is designed for **incremental, low-risk delivery**. Phase 1 and 2 deliver immediate performance and UX gains; Phases 3–4 add summary metrics; Phases 5–8 complete the architecture and polish. DatasetCacheService is explicitly **not** recommended for the project list due to its filter-heavy nature.
