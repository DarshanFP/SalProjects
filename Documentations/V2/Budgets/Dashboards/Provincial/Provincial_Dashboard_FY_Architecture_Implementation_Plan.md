# Provincial Dashboard FY Architecture Implementation Plan

**Date:** 2026-03-05  
**Based on:** Provincial_Dashboard_Feasibility_Audit.md  
**Objective:** Upgrade Provincial Dashboard to align with Executor Dashboard architecture: Dynamic FY selector, resolver-based aggregation, optimized dataset reuse, and performance improvements for large provinces.

---

## 1. Current Provincial Dashboard Architecture

### 1.1 Controller & Routing

| Attribute | Value |
|-----------|-------|
| **Route** | `GET /provincial/dashboard` |
| **Controller** | `ProvincialController::provincialDashboard()` |
| **View** | `provincial.index` (extends `provincial.dashboard` layout) |

### 1.2 FY Handling

- **Source:** `$fy = $request->input('fy', FinancialYearHelper::currentFY())` — already present.
- **FY list:** `FinancialYearHelper::listAvailableFY()` — static 10-year window; no DB.
- **FY filtering:** `->inFinancialYear($fy)` applied to all project queries (main, society, widget methods).

### 1.3 Project Dataset

- **Scope:** `Project::accessibleByUserIds($accessibleUserIds)` (executors/applicants under provincial).
- **Main query:** Approved projects, FY-filtered, with optional center/role/project_type filters.
- **Widget queries:** Each of `calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateCenterPerformance`, `calculateEnhancedBudgetData`, and `prepareCenterComparisonData` performs its own project/report queries. No shared dataset.

### 1.4 Financial Aggregation

- **Resolver usage:** `$resolver->resolve($project)` in loops — per-project resolution, no batch.
- **resolveCollection():** Not used. Executor dashboard uses `ProjectFinancialResolver::resolveCollection()` once and reuses the map.

### 1.5 UI

- **FY selector:** Present in Budget Summary card (inside filter form); uses `$availableFY` and auto-submits on change.
- **Filter form:** FY, Center, Role, Project Type; form submits to `route('provincial.dashboard')` and preserves query params.

---

## 2. Architectural Risks Identified

| Risk | Description |
|------|-------------|
| **FY propagation** | Historical documentation notes `calculateCenterPerformance($provincial, string $fy)` was called with one argument inside `prepareCenterComparisonData()`. Verify all call chains pass `$fy` consistently. |
| **Query multiplicity** | ~10+ separate project/report queries per page load; no shared dataset. |
| **Resolver redundancy** | Same project resolved multiple times across widget methods; no `resolveCollection()` usage. |
| **Static FY dropdown** | `listAvailableFY()` returns fixed 10 years; does not reflect actual project data (e.g., provinces with only recent FYs). |
| **Scalability** | At 1000+ projects, per-widget queries and per-project resolution become costly. |

---

## 3. Upgrade Goals

1. **Dynamic Financial Year selector** — FY list derived from project data; fallback to static list.
2. **Resolver-based financial aggregation consistency** — Use `resolveCollection()` once; reuse map for all aggregations.
3. **Optimized dataset reuse** — Single approved (and where needed, pending) project set per request; share across widgets.
4. **Performance improvements for large provinces** — Reduce queries and resolution passes; optional request-level cache.
5. **UI parity with Executor dashboard** — Control bar with FY selector; auto-submit on change; preserve selected FY in all requests.

---

### 3.1 Dashboard Performance Architecture Roadmap

The dashboard system (Executor, Provincial, Coordinator) follows a layered performance architecture designed to scale from 100 → 5,000 → 50,000+ projects without future architectural rewrites.

**Architecture layers:**

| Layer | Responsibility |
|-------|----------------|
| **Query Layer** | Build scoped project queries (accessibleByUserIds, inFinancialYear, filters) |
| **Dataset Layer** | Fetch and project datasets; lightweight selects; shared collections |
| **Resolver Layer** | `ProjectFinancialResolver::resolveCollection()` — batch financial resolution |
| **Aggregation Layer** | Compute totals, by-center, by-society, by-type from resolved data |
| **Cache Layer** | Dataset cache, dashboard cache, snapshot tables |
| **Presentation Layer** | Controllers pass data to views; widgets render |

**Data pipeline principle:**

```
QUERY → DATASET → RESOLVER → AGGREGATION → CACHE → VIEW
```

This architecture ensures all dashboards (Executor, Provincial, Coordinator) reuse the same data pipeline and scale consistently.

---

## 4. Phase-wise Implementation Plan

### Phase 1 — Runtime Fix

**Goal:** Ensure `calculateCenterPerformance($provincial, string $fy)` receives `$fy` consistently; audit FY propagation across the controller.

**Tasks:**

1. **Audit call chain**
   - Verify `prepareCenterComparisonData($provincial, string $fy)` accepts and forwards `$fy`.
   - Verify controller calls `prepareCenterComparisonData($provincial, $fy)` with `$fy`.
   - Verify `prepareCenterComparisonData` calls `calculateCenterPerformance($provincial, $fy)` with `$fy`.

2. **Fix if needed**
   - Add `string $fy` to `prepareCenterComparisonData` signature if missing.
   - Pass `$fy` from `provincialDashboard()` into `prepareCenterComparisonData($provincial, $fy)`.
   - Ensure `calculateCenterPerformance($provincial, $fy)` is called with both arguments inside `prepareCenterComparisonData`.

3. **Consistency audit**
   - Confirm all methods that take `$fy` receive it from their callers:
     - `calculateTeamPerformanceMetrics($provincial, $fy)`
     - `prepareChartDataForTeamPerformance($provincial, $fy)`
     - `calculateCenterPerformance($provincial, $fy)`
     - `calculateEnhancedBudgetData($provincial, $fy)`
     - `prepareCenterComparisonData($provincial, $fy)`

**Files:** `app/Http/Controllers/ProvincialController.php`

**Validation:** Load provincial dashboard with Center Comparison widget visible; no "Too few arguments" error; center performance data reflects selected FY.

---

### Phase 2 — Dynamic Financial Year Selector

**Goal:** Replace static FY list with project-derived list; fallback to static list when empty.

**Tasks:**

1. **Add dynamic FY list in controller**
   - After `$fy = $request->input('fy', FinancialYearHelper::currentFY())`, build base query for provincial's scope:
     ```php
     $baseQuery = Project::accessibleByUserIds($accessibleUserIds);
     ```
   - Call:
     ```php
     $availableFY = FinancialYearHelper::listAvailableFYFromProjects($baseQuery);
     ```
   - If `empty($availableFY)`, fallback:
     ```php
     $availableFY = FinancialYearHelper::listAvailableFY();
     ```

2. **Replace static list**
   - Remove or replace the line `$availableFY = FinancialYearHelper::listAvailableFY();` with the logic above.
   - Ensure `$availableFY` is passed to the view as before.

**Files:** `app/Http/Controllers/ProvincialController.php`

**Validation:** Provincial with projects only in FY 2024-25 and 2025-26 sees only those FYs in dropdown; provincial with no projects sees static fallback list.

---

### Phase 3 — Provincial Dashboard UI Controls

**Goal:** Add a dashboard control bar with FY selector; ensure selected FY is preserved across requests; match Executor UX where applicable.

**Tasks:**

1. **Control bar placement**
   - Add a compact control bar at the top of the dashboard content (above Budget Overview section).
   - Include: Financial Year selector (required); optionally surface Center, Role, Project Type if not already in filter form.
   - Ensure selector auto-submits on change: `onchange="this.form.submit()"`.

2. **Preserve FY in requests**
   - Filter form already submits to `route('provincial.dashboard')` with `fy` in query string.
   - Ensure all dashboard links (e.g., "Clear Filters", "View All Reports") preserve `fy` when appropriate, or document that "Clear Filters" resets to default FY.

3. **UI consistency**
   - Align styling of FY selector with Executor dashboard (form-select, label "Financial Year").
   - If Executor uses a control bar pattern, mirror it for provincial.

**Files:** `resources/views/provincial/index.blade.php`

**Validation:** FY selector at top of page; changing FY submits form and reloads dashboard with new totals, charts, and center comparison; FY preserved when applying other filters.

---

### Phase 3.5 — Universal Dashboard Dataset Cache

**Goal:** Introduce a shared dataset cache layer used by Executor, Provincial, and Coordinator dashboards. Accelerates dashboards by 10–30× with minimal code changes.

**Architecture flow:**

```
Controller
    ↓
Dataset Service
    ↓
Dataset Cache
    ↓
Resolver
    ↓
Aggregations
```

**Tasks:**

1. **Define cache key format**
   - Pattern: `dashboard_dataset_{role}_{user_or_province_id}_{fy}`
   - Examples:
     - `dashboard_dataset_executor_37_2025-26`
     - `dashboard_dataset_provincial_5_2025-26`
     - `dashboard_dataset_coordinator_0_2025-26` (or scope identifier)

2. **Cache TTL**
   - 5–10 minutes (configurable).

3. **Cache contents**
   - Store: `approvedProjects`, `pendingProjects` (or lightweight projection equivalents).
   - Controllers retrieve dataset from cache before querying the database; on miss, fetch and populate cache.

4. **Integration**
   - Dataset service (or controller logic) checks cache first; on hit, skip project query; on miss, query DB and store in cache.
   - Apply to ExecutorController, ProvincialController, CoordinatorController (or equivalent dashboard entry points).

**Benefits:**
- Prevents repeated dataset queries across dashboard loads
- Improves dashboard load time dramatically
- Reduces database load across all dashboards

**Files:** Dataset services, `ProvincialController`, `ExecutorController`, `CoordinatorController` (or shared `DashboardDatasetService`)

**Validation:** Repeated dashboard loads within TTL serve dataset from cache; load time improves; data consistency maintained.

---

### Phase 4 — Dataset Optimization

**Goal:** Create a shared approved (and pending, where needed) project dataset once per request; use it for budget summaries, team metrics, charts, society stats, and center performance.

**Tasks:**

1. **Single approved project fetch**
   - Early in `provincialDashboard()`, after `$accessibleUserIds` and `$fy` are known, fetch:
     ```php
     $approvedProjects = Project::accessibleByUserIds($accessibleUserIds)
         ->approved()
         ->inFinancialYear($fy)
         ->with(['user', 'reports' => fn($q) => $q->orderBy('created_at', 'desc'), 'reports.accountDetails', 'budgets'])
         ->get();
     ```

2. **Apply filters for main budget summary**
   - Main `$projects` used by `calculateBudgetSummariesFromProjects` may have center/role/project_type filters. Either:
     - **Option A:** Apply filters to `$approvedProjects` in memory (filter by `$project->user->center`, `$project->user->role`, `$project->project_type`).
     - **Option B:** Keep main budget query separate but also build unfiltered `$approvedProjects` for widget methods.

3. **Refactor widget methods to accept shared dataset**
   - Change method signatures to accept project collections and (in Phase 5) resolved financials:
     - `calculateBudgetSummariesFromProjects($projects, $request, ?$resolvedFinancials = null)`
     - `calculateTeamPerformanceMetrics($provincial, $fy, $approvedProjects = null)` — fetch only if null
     - `prepareChartDataForTeamPerformance($provincial, $fy, $approvedProjects = null)`
     - `calculateCenterPerformance($provincial, $fy, $approvedProjects = null)`
     - `calculateEnhancedBudgetData($provincial, $fy, $approvedProjects = null)`
   - Pass `$approvedProjects` from controller into each; avoid internal project fetches when provided.

4. **Society breakdown**
   - Society stats use `Project::where('province_id', $provinceId)->whereNotNull('society_id')->inFinancialYear($fy)->get()`. Either:
     - Derive society subset from `$approvedProjects` (filter by `province_id`, `society_id` not null), or
     - Keep society query separate if dataset differs (e.g., includes pending); document decision.

5. **Pending projects**
   - `calculateEnhancedBudgetData` uses pending projects for `pendingTotal`. Fetch once:
     ```php
     $pendingProjects = Project::accessibleByUserIds($accessibleUserIds)
         ->notApproved()
         ->inFinancialYear($fy)
         ->with(['user', 'reports.accountDetails'])
         ->get();
     ```
   - Pass `$pendingProjects` into `calculateEnhancedBudgetData`.

**Files:** `app/Http/Controllers/ProvincialController.php`

**Validation:** Single approved (and pending) fetch; all widgets use shared data; no duplicate project queries for same scope; totals match previous behavior.

---

### Phase 4A — Immutable Dataset Architecture Safeguard

**Goal:** Prevent the "Controller-Owned Dataset Mutation" anti-pattern. Ensure datasets passed to widget methods are never mutated, avoiding hidden cross-widget data corruption, inconsistent aggregations, and scaling issues.

**Context:** Phase 4 introduces shared datasets (`teamProjects`, `projects`) passed from controller to multiple widget methods. If any widget mutates the shared collection (e.g. `->transform()`, `->forget()`, or `->push()`), other widgets receive corrupted data. At scale (5,000+ projects), mutations on large collections cause performance degradation and subtle bugs.

**Audit result (Phase4A_ImmutableDataset_Feasibility_Audit.md):** All five widget methods currently use only read-only operations. No collection mutations detected. **Strategy A (Documentation + Guidelines)** recommended—no runtime guard or immutable wrapper required.

**Rule:** Datasets must be **immutable**. Widget methods may only **read** and **derive** new collections; they must **not** mutate the shared dataset.

**Enforcement strategy:** Documentation + development guidelines. Optional: CI/audit rule to flag prohibited operations in widget methods.

**Prohibited operations on shared datasets (`teamProjects`, `projects`):**
- `transform()`, `forget()`, `push()`, `pop()`, `shift()`, `splice()`
- `put()`, `prepend()`, direct array assignment

**Allowed operations (derive new collections):**
- `filter()`, `map()`, `groupBy()`, `where()`, `whereIn()`
- `pluck()`, `merge()`, `unique()`, `sort()`, `values()`, `take()`, `sum()`, `count()`

**Architecture:**

```
Controller
   ↓
DatasetCacheService
   ↓
Collections (treated as read-only by convention)
   ↓
Widget Aggregations (read-only; derive new collections)
```

**Implementation tasks:**
1. Document immutability in PHPDoc for widget method parameters (already done in Phase 4).
2. Add development guideline: do not use prohibited operations on `$teamProjects` or `$projects` in dashboard widget methods.
3. Optional: Add CI/static check to flag prohibited operations in ProvincialController widget methods.

**Benefits:**
- Prevents accidental dataset mutation
- No performance overhead
- Compatible with Phase 4.5 (lightweight projection) and Phase 5 (resolver batching)
- Compatible with DatasetCacheService (standard collections)
- Enables future snapshot tables and caching reuse

**Files:** `app/Http/Controllers/ProvincialController.php`, widget methods. See `Phase4A_ImmutableDataset_Feasibility_Audit.md` for full audit.

---

### Phase 4.5 — Lightweight Dataset Projection

**Goal:** Reduce memory usage for large provincial dashboards (1000–5000+ projects) by selecting only required project columns. Full model hydration loads ~50+ columns per project; projection loads ~15.

**Audit (Phase4_5_LightweightDataset_Feasibility_Audit.md):** Project-column projection is feasible. Relations (user, reports.accountDetails, budgets) must be retained—widgets and resolver depend on them. DirectMappedIndividualBudgetStrategy uses type-specific relations (iiesExpenses, etc.) via loadMissing—accept N+1 or add conditional eager load. Expected memory savings from project select alone: ~10–15%; relations dominate footprint.

**Tasks:**

1. **Introduce project select in DatasetCacheService**
   - Add `select()` to the dataset query (teamProjects = all statuses, FY-filtered):
     ```php
     ProjectQueryService::forProvincial($provincial, $fy)
         ->select([
             'project_id',
             'province_id',
             'society_id',
             'project_type',
             'user_id',
             'in_charge',
             'commencement_month_year',
             'opening_balance',
             'amount_sanctioned',
             'amount_forwarded',
             'local_contribution',
             'overall_project_budget',
             'status',
             'current_phase',
             'project_title'
         ])
         ->with(['user', 'reports.accountDetails', 'budgets'])
         ->get();
     ```
   - Must include `current_phase` (PhaseBasedBudgetStrategy), `project_title` (Enhanced Budget top projects).

2. **Retain required relations**
   - `user` — required for center, name (grouping, display)
   - `reports.accountDetails` — required for expense totals
   - `budgets` — required for PhaseBasedBudgetStrategy
   - Do NOT remove relations; projection applies to project columns only.

3. **DirectMappedIndividual N+1**
   - DirectMappedIndividualBudgetStrategy loads type-specific relations (iiesExpenses, iesExpenses, ilpBudget, iahBudgetDetails, igeBudget) via loadMissing.
   - Accept N+1 for now (typically few Individual projects per province), or add conditional eager load if profiling shows impact.

4. **Integrate with DatasetCacheService**
   - Add `select()` to the query inside `DatasetCacheService::getProvincialDataset()`.
   - ProjectQueryService returns a builder; apply `->select([...])` before `->with([...])->get()`.
   - No new service required; DatasetCacheService remains the dataset source.

**Files:** `app/Services/DatasetCacheService.php`, `app/Services/ProjectQueryService.php` (if builder returned)

**Benefits:**
- Reduces project attribute payload (~10–15% memory for project columns)
- Smaller cache serialization
- Compatible with resolver and widgets; no logic changes

**Validation:** Large province (1000+ projects) — memory improves; budget summaries, charts, and center comparison produce identical results. See Phase4_5_LightweightDataset_Feasibility_Audit.md for full analysis.

---

### Phase 5 — Resolver Batch Optimization

**Goal:** Use `ProjectFinancialResolver::resolveCollection()` once; reuse resolved results for all dashboard aggregations. Assumes Phase 4 dataset + Phase 4.5 lightweight projection.

**Audit (Phase5_ResolverBatch_Feasibility_Audit.md):** Feasible. resolveCollection() already exists. Same project is currently resolved 4–6× across widgets. Single pass on teamProjects produces map covering approved and pending. Strategies are stateless; both PhaseBased and DirectMappedIndividual are batch-compatible. Pass map to widgets; use `$resolvedFinancials[$project->project_id] ?? []`. Estimated 75–83% reduction in resolver executions; 15–30% dashboard response improvement for 500+ projects.

**Tasks:**

1. **Resolve once on teamProjects**
   - After `$teamProjects` is loaded (from DatasetCacheService):
     ```php
     $resolvedFinancials = \App\Domain\Budget\ProjectFinancialResolver::resolveCollection($teamProjects);
     ```
   - Map includes both approved (opening_balance) and pending (amount_requested); no split needed.

2. **Update aggregation method signatures**
   - Add optional `?array $resolvedFinancials = null` to: calculateBudgetSummariesFromProjects, calculateTeamPerformanceMetrics, prepareChartDataForTeamPerformance, calculateCenterPerformance, calculateEnhancedBudgetData.
   - When provided, use map lookup; when null, fall back to inline resolution (backward compatibility).

3. **Controller: pass map to all widgets**
   - calculateBudgetSummariesFromProjects($projects, $request, $resolvedFinancials)
   - calculateTeamPerformanceMetrics(..., $teamProjects, $resolvedFinancials)
   - prepareChartDataForTeamPerformance(..., $teamProjects, $resolvedFinancials)
   - calculateCenterPerformance(..., $teamProjects, $resolvedFinancials)
   - calculateEnhancedBudgetData(..., $teamProjects, $resolvedFinancials)
   - $projects is subset of teamProjects; same map works.

4. **Remove per-project resolver calls**
   - Replace `$resolver->resolve($project)` with `$resolvedFinancials[$project->project_id] ?? []` in all widget methods when map is passed.

5. **Society stats and projectList** (optional follow-up)
   - getSocietyStats: separate dataset; can call resolveCollection(societyProjects) or keep inline.
   - projectList: apply same pattern — resolveCollection(fullDataset) once.

**Files:** `app/Http/Controllers/ProvincialController.php`

**Validation:** Resolver invoked once per project; budget summaries, charts, center performance, and team budget overview use pre-resolved data; numbers match Phase 4 baseline.

---

### Phase 6 — Dashboard Cache Layer

**Goal:** Cache final dashboard widget data for a short duration; serve from cache on repeated requests; invalidate on TTL or project/report changes.

**Audit (Phase6_DashboardCache_Feasibility_Audit.md):** Feasible. Cache key must include province_id, fy, and filter hash (center, role, project_type) because budgetSummaries is filter-dependent. Cache final widget data; exclude real-time data (pending, approval queue). General users bypass cache.

**Tasks:**

1. **Cache key**
   - `provincial_dashboard_{province_id}_{fy}_{filterHash}`
   - filterHash = md5(center|role|project_type) from request

2. **Cache scope**
   - Province + FY + filters. General users bypass.

3. **Cache contents**
   - budgetSummaries, performanceMetrics, chartData, centerPerformance, budgetData, centerComparison, societyStats, centers, allCenters, roles, projectTypes, fyList, fy, enableSocietyBreakdown
   - Exclude: pendingProjects, pendingReports, approvalQueue (compute on every request)

4. **TTL**
   - 5–10 minutes; configurable via `config('dashboard.cache_ttl_minutes', 5)`

5. **Invalidation**
   - Primary: TTL. Optional: explicit clear when DatasetCacheService::clearProvincialDataset is called.

6. **Implementation**
   - Bypass for general users and when province_id is null.
   - Check dashboard cache; on hit, merge real-time data (pending, approval), return view.
   - On miss: full pipeline; store result; merge real-time data; return view.

**Files:** `app/Http/Controllers/ProvincialController.php`, optional `config/dashboard.php`

**Validation:** Repeated loads within TTL serve from cache; filter change causes cache miss; real-time data always fresh.

---

### Phase 7 — Final Validation

**Goal:** Verify end-to-end behavior: FY selector drives all dashboard data; aggregations match resolver semantics; UI parity achieved.

**Verification checklist:**

| # | Verification | Pass |
|---|--------------|------|
| 1 | FY selector changes dashboard totals (Budget Summary cards) | |
| 2 | Budget summaries match resolver results (spot-check approved/pending) | |
| 3 | Charts (Team Performance, Budget by Type/Center) respond to FY | |
| 4 | Society statistics respond to FY (when enabled) | |
| 5 | Center Comparison widget responds to FY | |
| 6 | No "Too few arguments" or FY-related runtime errors | |
| 7 | Dynamic FY dropdown shows only FYs with projects (or fallback) | |
| 8 | Lightweight dataset (Phase 4.5): memory and load time acceptable for 1000+ projects | |
| 9 | Cache reduces load time on repeated requests (Phase 6) | |
| 10 | Clear Filters resets filters; FY behavior documented | |

**Test scenarios:**

1. Provincial with projects in FY 2024-25 and 2025-26 — switch FY; verify totals and charts update.
2. Provincial with no projects — verify static FY list and no errors.
3. Provincial with multiple societies — verify society breakdown uses correct FY.
4. Large province (1000+ projects) — verify acceptable load time and memory; compare before/after Phase 4–4.5–6.

---

### Phase 8 — Database Aggregation Strategy

**Goal:** Move heavy aggregations from PHP loops to the database. Reduces memory usage and scales to tens of thousands of projects.

**Tasks:**

1. **Replace PHP loops with SQL aggregation**
   - Instead of resolving totals in PHP:
     ```php
     foreach ($projects as $project) {
         $total += $project->opening_balance;
     }
     ```
   - Use database aggregation:
     ```sql
     SELECT SUM(opening_balance) FROM projects
     WHERE province_id = ? AND commencement_month_year BETWEEN ? AND ?
     ```

2. **Use database grouping for breakdowns**
   - Province totals: `GROUP BY province_id`
   - Center totals: join `users` on `user_id`; `GROUP BY users.center`
   - Society totals: `GROUP BY society_id`

3. **Integration**
   - Add aggregation queries (or query builder methods) that return pre-aggregated totals.
   - Use for dashboard summary cards where full project resolution is not required; fall back to resolver for detailed breakdowns.

**Benefits:**
- Reduces memory usage
- Scales to tens of thousands of projects
- Faster aggregation queries

**Files:** New aggregation service or model scopes, `ProvincialController`, `ExecutorController`, `CoordinatorController`

**Validation:** Totals match resolver-based aggregation; load time and memory improve for large datasets.

---

### Phase 9 — Dashboard Snapshot Tables

**Goal:** Introduce optional snapshot tables to store precomputed financial statistics. Supports 50,000+ projects and enables historical analytics.

**Tasks:**

1. **Create snapshot table (example schema)**
   - Table: `dashboard_snapshots`
   - Columns: `province_id`, `fy`, `total_budget`, `total_expenses`, `project_count`, `updated_at`, and optionally `center_id`, `society_id` for breakdowns.

2. **Refresh mechanism**
   - Scheduled jobs (e.g., hourly or on-demand) refresh snapshot values.
   - Trigger on project/report events or batch refresh.

3. **Dashboard integration**
   - Dashboards optionally read snapshot values instead of computing statistics live.
   - Fall back to live computation when snapshot is stale or missing.

**Benefits:**
- Supports 50,000+ projects
- Reduces resolver workload
- Enables historical analytics

**Files:** Migration for `dashboard_snapshots`, snapshot service, scheduled job, dashboard controllers

**Validation:** Snapshot values match live computation; dashboard loads from snapshot when available; refresh job updates correctly.

---

### Phase 10 — Event-Driven Cache Invalidation

**Goal:** Invalidate dashboard and dataset cache when data changes, instead of relying solely on TTL expiration.

**Tasks:**

1. **Define invalidation triggers**
   - Project approved
   - Project updated
   - Budget updated
   - Report submitted
   - Report approved

2. **Implementation**
   - Use model observers or event listeners to invalidate relevant cache keys.
   - Invalidate: `dashboard_dataset_*`, `provincial_dashboard_*`, `executor_dashboard_*`, `coordinator_dashboard_*` (or equivalent) for affected scope.

3. **Scope-aware invalidation**
   - Project update → invalidate cache for that project's province, executor, coordinator scope.
   - Report update → same.

**Benefits:**
- Ensures dashboards show fresh data after changes
- Avoids stale cache issues
- Complements TTL for reliability

**Files:** Model observers, event listeners, cache invalidation service

**Validation:** After project/report change, dashboard reflects new data on next load; no stale totals.

---

## 5. Implementation Order & Dependencies

```
Phase 1 (Runtime Fix)       → No dependencies
Phase 2 (Dynamic FY)        → No dependencies
Phase 3 (UI Controls)       → Depends on Phase 2 (FY list)
Phase 3.5 (Dataset Cache)   → No dependencies; enables faster Phase 4
Phase 4 (Dataset)           → No dependencies; enables Phase 4.5
Phase 4A (Immutable Dataset Safeguard) → Depends on Phase 4; architectural safeguard
Phase 4.5 (Lightweight)     → Depends on Phase 4, 4A; enables Phase 5
Phase 5 (Resolver)          → Depends on Phase 4, 4.5
Phase 6 (Caching)           → Depends on Phase 4, 4.5, 5
Phase 7 (Validation)        → After Phases 1–6
Phase 8 (DB Aggregation)    → Depends on Phase 5; for 5k+ scale
Phase 9 (Snapshots)         → Depends on Phase 8; for 50k+ scale
Phase 10 (Cache Invalidation) → Depends on Phase 6, 3.5
```

**Recommended order:** 1 → 2 → 3 → 3.5 → 4 → 4A → 4.5 → 5 → 6 → 7 → 10; then 8 → 9 as scale demands.

---

## 6. Dashboard Scalability Roadmap

The dashboard architecture supports three scaling levels. Implement phases incrementally as project count grows.

### Level 1 — Standard System (≤ 5,000 projects)

**Architecture:**
- Shared dataset (Phase 4)
- Lightweight projection queries (Phase 4.5)
- `resolveCollection()` batch resolution (Phase 5)
- Dashboard cache (Phase 6)

**Phases:** 1–7

---

### Level 2 — Large System (≤ 50,000 projects)

**Architecture additions:**
- Database aggregation (Phase 8)
- Universal dataset cache (Phase 3.5)
- Resolver batching (Phase 5)
- Dashboard snapshots (Phase 9)
- Event-driven cache invalidation (Phase 10)

**Phases:** 1–10

---

### Level 3 — Enterprise System (100,000+ projects)

**Architecture additions:**
- Financial summary tables (extended snapshots)
- Analytics warehouse (read replicas, denormalized views)
- Background aggregation jobs (queue-driven snapshot refresh)

---

## 7. Dashboard Architecture Diagram

Final architecture shared across Executor, Provincial, and Coordinator dashboards:

```
DATABASE
   ↓
DATASET SERVICES
   ↓
SHARED DATASETS
   ↓
FINANCIAL RESOLVER
   ↓
AGGREGATION LAYER
   ↓
CACHE LAYER
   ↓
CONTROLLERS
   ↓
VIEWS
```

**Flow:**
1. **DATABASE** — Projects, reports, users.
2. **DATASET SERVICES** — Fetch scoped datasets (lightweight projection, FY filter).
3. **SHARED DATASETS** — Single approved/pending collections per request; dataset cache.
4. **FINANCIAL RESOLVER** — `ProjectFinancialResolver::resolveCollection()`.
5. **AGGREGATION LAYER** — Totals, by-center, by-society; optionally DB aggregation or snapshots.
6. **CACHE LAYER** — Dataset cache, dashboard cache, snapshot tables.
7. **CONTROLLERS** — ExecutorController, ProvincialController, CoordinatorController.
8. **VIEWS** — Executor, Provincial, Coordinator dashboard views.

This architecture is shared across **Executor Dashboard**, **Provincial Dashboard**, and **Coordinator Dashboard**, ensuring consistent performance and scalability.

---

## 8. Rollback Plan

| Phase | Rollback |
|-------|----------|
| 1 | Revert FY propagation changes; restore previous method signatures if needed |
| 2 | Restore `$availableFY = FinancialYearHelper::listAvailableFY()` |
| 3 | Remove control bar; keep existing filter form |
| 3.5 | Remove dataset cache; always fetch from DB |
| 4 | Restore per-widget project queries; remove shared dataset |
| 4A | Remove immutability documentation; no code rollback (documentation only) |
| 4.5 | Remove `ProvincialDashboardDatasetService`; revert to full model load with relations |
| 5 | Restore per-project `$resolver->resolve()` calls |
| 6 | Remove cache wrapper; always compute fresh |
| 8 | Revert to PHP-based aggregation; remove DB aggregation queries |
| 9 | Remove snapshot tables; dashboards read live data only |
| 10 | Remove event listeners; rely on TTL only for cache expiration |

---

## 9. Coordinator Dashboard Architecture

The Coordinator dashboard provides **global oversight** across all provinces. It must reuse the same optimized pipeline as Provincial to ensure scalability and consistency.

### 9.1 Coordinator Scope

- **Access:** Global — all projects in the system; no province/user hierarchy.
- **ProjectAccessService:** `getVisibleProjectsQuery($coordinator, $fy)` returns unfiltered query for coordinator role.
- **ProjectQueryService:** Requires new method `forCoordinator($coordinator, $fy)` returning `Project::inFinancialYear($fy)` (or delegating to `getVisibleProjectsQuery`).

### 9.2 Target Pipeline

```
ProjectQueryService::forCoordinator($coordinator, $fy)
    ↓
DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)
    ↓
Lightweight dataset projection
    ↓
ProjectFinancialResolver::resolveCollection($teamProjects)
    ↓
Province-level aggregation
    ↓
Dashboard cache
    ↓
View
```

### 9.3 Current vs Recommended State

| Layer | Current (Coordinator) | Recommended |
|-------|----------------------|-------------|
| Query | Direct `Project::` | ProjectQueryService::forCoordinator |
| Dataset | None; per-widget fetch | DatasetCacheService::getCoordinatorDataset |
| Resolver | Per-project in loops | resolveCollection() once |
| Aggregation | Per-widget independent | Shared dataset + map |
| Cache | Widget-level only | Full dashboard cache |

### 9.4 Coordinator Implementation Phases

| Phase | Action |
|-------|--------|
| 1 | Add `ProjectQueryService::forCoordinator($coordinator, $fy)` |
| 2 | Add `DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)` |
| 3 | Replace per-project resolver loops with `resolveCollection()` |
| 4 | Introduce shared dataset; refactor widget methods |
| 5 | Add full coordinator dashboard cache |
| 6 | Wire project/report events to clear coordinator dataset cache |

### 9.5 Cache Keys

- **Dataset:** `coordinator_dataset_{$fy}_{$filterHash}`
- **Dashboard:** `coordinator_dashboard_{$fy}_{$filterHash}`

### 9.6 Reference

See `Documentations/V2/Budgets/Dashboards/Coordinator/Coordinator_Dashboard_Performance_Architecture_Audit.md` for full analysis, duplicate query detection, resolver misuse, and implementation roadmap.

---

## 10. References

- `Documentations/V2/Budgets/Dashboards/Provincial/Provincial_Dashboard_Feasibility_Audit.md`
- `Documentations/V2/Budgets/Dashboards/Dashboard_Performance_Architecture_Roadmap.md` — Dataset services, resolver batching, projection, caching, DB aggregation, snapshots, invalidation, scalability
- `Documentations/V2/Budgets/Dashboards/Financial_Year_Dashboard_Implementation_Plan_20260304.md`
- `app/Support/FinancialYearHelper.php` — `listAvailableFYFromProjects()`, `listAvailableFY()`
- `app/Domain/Budget/ProjectFinancialResolver.php` — `resolveCollection()`
- `app/Http/Controllers/ExecutorController.php` — Executor dashboard patterns
