# Coordinator Dashboard Implementation Roadmap

**Date:** 2026-03-06  
**Based on:** Coordinator_Dashboard_Performance_Architecture_Audit.md, Coordinator_Dashboard_Architecture_Audit.md, Province Partitioning Analysis  
**Objective:** Phase-wise implementation plan for the Coordinator dashboard architecture, reusing the optimized Provincial pipeline and incorporating province dataset partitioning.

---

## Executive Summary

The Coordinator dashboard currently exhibits significant performance anti-patterns: duplicate project queries across 9+ widgets, per-project resolver execution in 8 locations (~2,900 resolver calls for 500 projects), no shared dataset, no dataset cache, and no full dashboard cache. This roadmap defines an 8-phase implementation that:

1. **Introduces a centralized query layer** via `ProjectQueryService::forCoordinator()`
2. **Adds a dataset cache layer** via `DatasetCacheService::getCoordinatorDataset()`
3. **Reuses lightweight projection** from Provincial (Phase 4.5)
4. **Implements province partitioning** — single `groupBy` pass, shared across widgets
5. **Replaces per-project resolver loops** with `resolveCollection()` (1 call per request)
6. **Refactors widgets** to consume shared dataset + partitions + resolved map
7. **Adds full dashboard cache** with `coordinator_dashboard_{$fy}_{$filterHash}`
8. **Validates performance** under large dataset load

**Expected improvements:** 75–85% reduction in project queries; ~99% reduction in resolver executions; 10–30× improvement on cache hits; ~250 ms savings from province partitioning at 5,000 projects.

---

## 1. Architecture Goals

| Goal | Description |
|------|-------------|
| **Query centralization** | All coordinator project queries flow through `ProjectQueryService::forCoordinator()` |
| **Dataset reuse** | Single dataset per request; widgets consume shared collection |
| **Province partitioning** | Build `$projectsByProvince` once; pass to widgets; eliminate repeated `groupBy` |
| **Resolver batching** | One `resolveCollection()` per request; pass map to all widgets |
| **Dataset caching** | `DatasetCacheService::getCoordinatorDataset()` with 10 min TTL |
| **Dashboard caching** | Full payload cache; exclude real-time widgets (pending approvals, activity feed) |
| **Pipeline alignment** | Match Provincial pipeline: Query → Dataset → Resolver → Aggregation → Cache → View |

---

## 2. Dataset Design

### 2.1 Primary Dataset

- **Source:** `ProjectQueryService::forCoordinator($coordinator, $fy)` → `Project::inFinancialYear($fy)` (or `getVisibleProjectsQuery` for consistency)
- **Scope:** All projects in FY; optional filters (province, center, role, parent_id, project_type) applied in query or in-memory
- **Caching:** `DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)`
- **Structure:** Flat `Collection` of `Project` models

### 2.2 Lightweight Projection

Reuse Provincial Phase 4.5 `$select` array:

```php
$select = [
    'id', 'project_id', 'province_id', 'society_id', 'project_type',
    'user_id', 'in_charge', 'commencement_month_year', 'opening_balance',
    'amount_sanctioned', 'amount_forwarded', 'local_contribution',
    'overall_project_budget', 'status', 'current_phase', 'project_title',
];
```

- **Relations:** `user`, `reports.accountDetails`, `budgets` (required for resolver and widgets)

### 2.3 Reports Dataset

- **Source:** `DPReport::whereIn('project_id', $teamProjects->pluck('project_id'))->with('user')->get()`
- **Scope:** All reports for projects in FY dataset
- **Structure:** Flat `Collection`; partitioned by province alongside projects

---

## 3. Province Partitioning Strategy

### 3.1 Partition Structure

Build once after dataset + resolver:

```php
$projectsByProvince = $teamProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown');
$reportsByProvince = $allReports->groupBy(fn($r) => $r->user->province ?? 'Unknown');
$approvedProjectsByProvince = $teamProjects->filter(fn($p) => $p->isApproved())
    ->groupBy(fn($p) => $p->user->province ?? 'Unknown');
```

### 3.2 Where to Build Partitions

- **Location:** CoordinatorController, after `DatasetCacheService::getCoordinatorDataset()` and `ProjectFinancialResolver::resolveCollection()`
- **Not in DatasetCacheService** — keeps dataset layer flat; partitioning is aggregation logic

### 3.3 Widgets Consuming Partitions

| Widget | Uses | Notes |
|--------|------|-------|
| coordinatorDashboard (stats) | projectsByProvince | projects_by_province count |
| getSystemPerformanceData | projectsByProvince, reportsByProvince | province_metrics |
| getSystemAnalyticsData | projectsByProvince, approvedProjectsByProvince, reportsByProvince | province comparison charts |
| getSystemBudgetOverviewData | approvedProjectsByProvince | budget by province |
| getProvinceComparisonData | projectsByProvince, reportsByProvince | provincePerformance |
| getPendingApprovalsData | (reports) | pendingByProvince — may build from own data or receive if shared |

### 3.4 Immutability

Partitioned collections are derived from `$teamProjects`. Widgets must treat them as read-only (Phase 4A rule). Use `filter()`, `map()`, `groupBy()` to derive; no `transform()`, `push()`, etc.

---

## 4. Resolver Optimization Strategy

### 4.1 Single Batch Resolution

```php
$resolvedFinancials = ProjectFinancialResolver::resolveCollection($teamProjects);
```

- **When:** After `$teamProjects` is loaded; before any widget aggregation
- **Output:** `[project_id => [opening_balance, amount_requested, ...]]`

### 4.2 Widget Lookup Pattern

Replace:

```php
$resolver->resolve($project);
```

With:

```php
$resolvedFinancials[$project->project_id]['opening_balance'] ?? 0
```

### 4.3 Pending Projects

`$teamProjects` includes all statuses. Pending projects use `amount_requested` from resolver map. No separate resolver pass for pending.

---

## 5. Caching Architecture

### 5.1 Cache Layers

| Layer | Key | TTL | Contents |
|-------|-----|-----|----------|
| Dataset | `coordinator_dataset_{$fy}_{$filterHash}` | 10 min | Flat project collection |
| Dashboard | `coordinator_dashboard_{$fy}_{$filterHash}` | 5–10 min | Widget payload (excluding real-time) |

### 5.2 Filter Hash

`$filterHash = md5(json_encode($request->only(['province', 'center', 'role', 'parent_id', 'project_type']) ?? []))`

### 5.3 Exclusions from Dashboard Cache

- Pending approvals (getPendingApprovalsData) — real-time; 2 min widget cache or compute every request
- Activity feed (getSystemActivityFeedData) — real-time; 2 min widget cache

### 5.4 Invalidation

- **Triggers:** Project approval/revert, report approval/revert, budget sync
- **Action:** `DatasetCacheService::clearCoordinatorDataset($fy)`; clear dashboard keys for that FY (or use cache tags if Redis)

### 5.5 Cache Key Alignment

Align `invalidateDashboardCache()` with actual cache keys (e.g. `coordinator_pending_approvals_data_{$fy}`) so invalidation works correctly.

---

## 6. Phase-wise Implementation Plan

### Phase 1 — Query Layer Architecture

**Goal:** Centralize coordinator project queries via `ProjectQueryService::forCoordinator()`.

**Tasks:**

1. Add `ProjectQueryService::forCoordinator(User $coordinator, string $fy): Builder`
   - Return `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` or `Project::inFinancialYear($fy)`
   - Coordinator scope = all projects; no user filter

2. Document usage in ProjectQueryService PHPDoc

**Files:** `app/Services/ProjectQueryService.php`

**Validation:** Unit test or manual: `forCoordinator` returns query with `inFinancialYear` applied; no duplicate logic.

#### Phase 1 — Implementation Complete (2026-03-06)

`ProjectQueryService::forCoordinator` has been implemented:

- **Method signature:** `forCoordinator(User $coordinator, string $fy): Builder`
- **Delegation:** Calls `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` for centralized access logic
- **FY scope:** FY filtering is applied by `ProjectAccessService` when `$fy` is passed; no duplication
- **Coordinator scope:** Global project visibility (no user hierarchy; `getVisibleProjectsQuery` returns unfiltered query for coordinator role)
- **Intended use:** Base query for coordinator dashboard dataset, dataset caching layer (`DatasetCacheService::getCoordinatorDataset`), and performance pipeline

No controller code was modified. Phase 1 is non-breaking and preparatory for Phase 2.

---

### Phase 2 — Dataset Cache Layer

**Goal:** Add `DatasetCacheService::getCoordinatorDataset()` with lightweight projection.

**Tasks:**

1. Add `getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection`
   - Base: `ProjectQueryService::forCoordinator($coordinator, $fy)`
   - Apply filters (province, center, role, parent_id, project_type) via `whereHas` or in-memory
   - Same `$select` and `$with` as `getProvincialDataset`
   - Cache key: `coordinator_dataset_{$fy}_{md5(json_encode($filters ?? []))}`

2. Add `clearCoordinatorDataset(string $fy): void` — clear all coordinator dataset keys for FY (or use prefix if driver supports)

3. Wire `clearCoordinatorDataset` to project/report approval, revert, budget sync events (same handlers as `clearProvincialDataset`)

**Files:** `app/Services/DatasetCacheService.php`, event listeners/observers

**Validation:** Coordinator dashboard uses `getCoordinatorDataset`; cache hit on second load; invalidation clears cache.

#### Phase 2 — Implementation Complete (2026-03-06)

`DatasetCacheService::getCoordinatorDataset()` and `clearCoordinatorDataset()` have been implemented:

- **getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection**
  - Base query: `ProjectQueryService::forCoordinator($coordinator, $fy)` (Phase 1)
  - Lightweight projection: same `$select` and `$with` as `getProvincialDataset` (user, reports.accountDetails, budgets)
  - Cache key: `coordinator_dataset_{$fy}` (single key per FY; filters applied in-memory after retrieval)
  - TTL: 10 minutes
  - Optional filters (province, center, role, parent_id, project_type) applied in-memory via `applyCoordinatorFilters()` so cache remains FY-only

- **clearCoordinatorDataset(string $fy): void**
  - `Cache::forget("coordinator_dataset_{$fy}")` for invalidation
  - To be wired to project/report approval, revert, budget sync in a later phase

No changes to Provincial dataset logic or to CoordinatorController. Phase 2 is non-breaking and preparatory for shared-dataset refactor in Phase 4.

---

### Phase 3 — Lightweight Dataset Projection

**Goal:** Reuse Provincial projection for coordinator dataset.

**Tasks:**

1. Ensure `getCoordinatorDataset` uses the same `$select` array as `getProvincialDataset` (Phase 4.5)
2. No new logic; alignment only

**Files:** `app/Services/DatasetCacheService.php`

**Validation:** Project columns match Provincial; resolver and widgets work correctly.

#### Phase 3 — Validation Complete (2026-03-06)

Projection alignment validation performed. **No code changes required.**

- **Projection match:** Coordinator and Provincial use identical 16-column `$select` and identical `$with` (user, reports.accountDetails, budgets).
- **Resolver compatibility:** All required fields (project_id, opening_balance, amount_sanctioned, amount_forwarded, local_contribution, overall_project_budget, status, current_phase, project_type) and relations (budgets, reports.accountDetails) are present.
- **Widget compatibility:** All Coordinator widgets (calculateBudgetSummariesFromProjects, getSystemPerformanceData, getSystemAnalyticsData, getSystemBudgetOverviewData, getProvinceComparisonData, getProvincialManagementData, getSystemHealthData) have their required project fields covered.
- **Memory impact:** Projection reduces payload vs full model load.

See `Phase3_ProjectionAlignment_Validation.md` for full audit.

---

### Phase 4 — Province Partitioned Dataset

**Goal:** Build province partitions once; pass to widgets.

**Tasks:**

1. In `coordinatorDashboard`, after `getCoordinatorDataset` and `resolveCollection`:
   - Load reports: `DPReport::whereIn('project_id', $teamProjects->pluck('project_id'))->with('user')->get()`
   - Build: `$projectsByProvince`, `$reportsByProvince`, `$approvedProjectsByProvince`

2. Add partition parameters to widget method signatures:
   - `getProvinceComparisonData($teamProjects, $resolvedFinancials, $projectsByProvince, $reportsByProvince, ...)`
   - `getSystemPerformanceData(..., $projectsByProvince, $reportsByProvince)`
   - `getSystemAnalyticsData(..., $projectsByProvince, $approvedProjectsByProvince, $reportsByProvince)`
   - `getSystemBudgetOverviewData(..., $approvedProjectsByProvince)` (or derive from $teamProjects)

3. Refactor widget internals to use partitions instead of internal `groupBy`

4. Document immutability of partition structures (Phase 4A pattern)

**Files:** `app/Http/Controllers/CoordinatorController.php`

**Validation:** Province metrics match pre-refactor; no repeated `groupBy` in widget methods; single partition build per request.

#### Phase 4 — Implementation Complete (2026-03-06)

See `Phase4_ProvincePartition_Implementation.md` for full report.

- **coordinatorDashboard** now uses `DatasetCacheService::getCoordinatorDataset`, `ProjectFinancialResolver::resolveCollection`, loads reports once, builds partitions once
- **getSystemPerformanceData** accepts partitions; removed internal groupBy and Cache::remember
- **getSystemAnalyticsData** accepts partitions; removed internal groupBy and Cache::remember
- **getSystemBudgetOverviewData** accepts teamProjects, resolvedFinancials, approvedProjectsByProvince; derives approved/pending from teamProjects
- **getProvinceComparisonData** accepts partitions; removed internal groupBy and Cache::remember
- **DatasetCacheService::applyCoordinatorFilters** extended to support province as user.province (string) for coordinator UI filters

---

### Phase 5 — Resolver Batch Optimization

**Goal:** Replace per-project resolver loops with single `resolveCollection()`.

**Tasks:**

1. In `coordinatorDashboard`, call `ProjectFinancialResolver::resolveCollection($teamProjects)` once after dataset load

2. Pass `$resolvedFinancials` to all widget methods:
   - calculateBudgetSummariesFromProjects
   - getSystemBudgetOverviewData
   - getProvinceComparisonData
   - getSystemPerformanceData
   - getSystemAnalyticsData
   - getProvincialManagementData
   - getSystemHealthData

3. Replace all `$resolver->resolve($project)` with `$resolvedFinancials[$project->project_id] ?? []`

4. Remove resolver injection from widget methods where no longer needed

**Files:** `app/Http/Controllers/CoordinatorController.php`

**Validation:** Single resolver call per request; totals match previous behavior; no per-project resolver in widgets.

#### Phase 5 — Implementation Complete (2026-03-06)

See `Phase5_ResolverBatch_Implementation.md` for full report.

- **calculateBudgetSummariesFromProjects** accepts optional `$resolvedFinancials`; uses map when provided; falls back to resolver for non-dashboard callers (e.g. projectBudgets)
- **getProvincialManagementData** now receives `$teamProjects`, `$resolvedFinancials`, `$allReports`; removed internal resolve loop and Cache::remember
- **getSystemHealthData** now receives `$teamProjects`, `$resolvedFinancials`, `$allReports`; removed internal resolve loop and Cache::remember
- **coordinatorDashboard** passes `$resolvedFinancials` to all widgets; single `resolveCollection()` per request

---

### Phase 6 — Shared Dataset Widget Refactor

**Goal:** Remove widget-internal project queries; consume shared dataset and partitions.

**Tasks:**

1. Refactor `coordinatorDashboard` to:
   - Fetch `$teamProjects` from `DatasetCacheService::getCoordinatorDataset`
   - Load `$allReports` once
   - Call `resolveCollection($teamProjects)`
   - Build `$projectsByProvince`, `$reportsByProvince`, `$approvedProjectsByProvince`
   - Pass dataset + partitions + map to all widget methods

2. Refactor each widget to accept and use shared data:
   - getSystemPerformanceData
   - getSystemAnalyticsData
   - getSystemBudgetOverviewData
   - getProvinceComparisonData
   - getProvincialManagementData
   - getSystemHealthData
   - calculateBudgetSummariesFromProjects

3. Remove internal `Project::` and `DPReport::` queries from widget methods (except getPendingApprovalsData, getSystemActivityFeedData which may keep separate fetch for real-time)

4. Apply request filters (province, center, etc.) to dataset in controller before passing, or pass filters and let widgets filter in-memory

**Files:** `app/Http/Controllers/CoordinatorController.php`

**Validation:** No duplicate project queries in coordinator dashboard load; all widgets receive shared data; totals and charts correct.

#### Phase 6 — Implementation Complete (2026-03-06)

See `Phase6_SharedDataset_Audit.md` and `Phase6_SharedDataset_Remediation.md`.

**Summary:**
- **Project:: in widgets:** None (pass)
- **DPReport:: in widgets:** None (pass) — remediation applied to `getSystemBudgetOverviewData`; now receives `$allReports` and uses in-memory filtering
- **Resolver usage:** All widgets use `$resolvedFinancials[$project->project_id]` (pass)
- **Partition usage:** All widgets use provided partitions; no internal province `groupBy` (pass)
- **Pipeline:** Controller owns dataset + reports + resolveCollection + partitions (pass)

**Status:** Full pass. Phase 6 remediation complete; system ready for Phase 7 (dashboard cache).

---

### Phase 7 — Coordinator Dashboard Cache

**Goal:** Implement full dashboard cache.

**Tasks:**

1. Before full pipeline, check cache: `coordinator_dashboard_{$fy}_{$filterHash}`
2. On hit: merge real-time data (pending approvals, activity feed); return view
3. On miss: run full pipeline; cache result (exclude pending/activity or use short TTL for those)
4. TTL: 5–10 min (configurable)
5. Update `invalidateDashboardCache` to clear new keys; fix key alignment for FY-specific and filter-specific keys

**Files:** `app/Http/Controllers/CoordinatorController.php`, `config/dashboard.php`

**Validation:** Second load within TTL serves from cache; filter change causes miss; real-time data fresh.

---

### Phase 8 — Performance Validation

**Goal:** Validate under large dataset; measure improvements.

**Tasks:**

1. Create test scenario: FY with 500, 2,000, or 5,000 projects (or use staging)
2. Measure coordinator dashboard load time:
   - Before optimization (current code)
   - After Phase 6 (shared dataset + resolver + partitions)
   - After Phase 7 (with dashboard cache hit)
3. Verify: query count, resolver call count, memory usage
4. Document baseline and post-optimization metrics

**Validation:** Load time improves; no regression in correctness; cache hit significantly faster.

---

## 7. Implementation Order & Dependencies

```
Phase 1 (Query Layer)     → No dependencies
Phase 2 (Dataset Cache)   → Depends on Phase 1
Phase 3 (Projection)      → Depends on Phase 2; alignment only
Phase 4 (Province Partition) → Depends on Phase 2 (dataset available)
Phase 5 (Resolver Batch)  → Depends on Phase 2; can parallel with Phase 4
Phase 6 (Widget Refactor) → Depends on Phases 4, 5
Phase 7 (Dashboard Cache) → Depends on Phase 6
Phase 8 (Validation)      → After Phase 7
```

**Recommended order:** 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8.

Phases 4 and 5 can be done in parallel if different developers; Phase 6 requires both.

---

## 8. Expected Performance Improvements

| Metric | Current (500 projects) | After Optimization | Improvement |
|--------|------------------------|--------------------|-------------|
| Project queries per dashboard load | ~25+ | 1 (dataset) | 96%+ |
| Resolver calls | ~2,900 | 1 (resolveCollection) | ~99.97% |
| Province groupBy passes | 6 | 1 | 83% |
| Dataset cache | None | 10 min TTL | N/A |
| Dashboard cache | Widget-level | Full payload | 10–30× on hit |
| Estimated load time (500 projects) | ~12s | ~1.5s (cache miss), ~0.3s (cache hit) | 8–40× |

---

## 9. Rollback Plan

| Phase | Rollback |
|-------|----------|
| 1 | Remove `forCoordinator`; restore direct `Project::` in CoordinatorController |
| 2 | Remove `getCoordinatorDataset`; fetch from DB in controller |
| 3 | Revert to full model load if projection causes issues |
| 4 | Remove partition params; restore widget-internal `groupBy` |
| 5 | Restore per-project `$resolver->resolve()` in widgets |
| 6 | Restore per-widget project queries |
| 7 | Remove dashboard cache wrapper |
| 8 | N/A (validation only) |

---

## 10. Implementation Feasibility Findings

**Source:** Coordinator_Dashboard_Implementation_Feasibility_Audit.md (2026-03-06)

### 10.1 Audit Verdict

**READY FOR IMPLEMENTATION.** All eight phases are feasible; no blocking conflicts identified.

### 10.2 Key Findings

| Area | Finding | Action |
|------|---------|--------|
| Query layer | No conflict with `forProvincial()`; `ProjectAccessService::getVisibleProjectsQuery` already supports coordinator | Use Option A: delegate to PAS |
| DatasetCacheService | Cache keys isolated; no collision with Provincial | Use `coordinator_dataset_{fy}` without filter hash for simpler invalidation; apply filters in-memory |
| Province partitioning | Compatible with dataset cache; partitions built post-retrieval | No change |
| Resolver batch | `resolveCollection()` exists; same eager loads as Provincial | No change |
| Widget refactor | All six widgets can accept shared dataset; getProvincialManagementData filters by teamUserIds from `$teamProjects` | Proceed as planned |
| Dashboard cache | Keys isolated (`coordinator_dashboard_` vs `provincial_dashboard_`) | No change |

### 10.3 Recommended Adjustment

**Dataset cache key:** Use `coordinator_dataset_{fy}` instead of `coordinator_dataset_{fy}_{filterHash}` to simplify `clearCoordinatorDataset()`. Apply request filters (province, center, role, etc.) in the controller after dataset retrieval. This avoids the need to clear multiple filter-hash variants on invalidation.

### 10.4 Pre-Implementation Checklist

- Confirm cache driver (file vs Redis)
- Confirm where `clearProvincialDataset` is (or will be) wired for project/report events; wire `clearCoordinatorDataset` alongside
- Ensure Project model has `isApproved()` (used in partitioning)

---

## 11. References

- `Documentations/V2/Budgets/Dashboards/Coordinator/Coordinator_Dashboard_Implementation_Feasibility_Audit.md`
- `Documentations/V2/Budgets/Dashboards/Coordinator/Coordinator_Dashboard_Architecture_Audit.md`
- `Documentations/V2/Budgets/Dashboards/Coordinator/Coordinator_Dashboard_Performance_Architecture_Audit.md` (includes Section 7A Province Partitioning)
- `Documentations/V2/Budgets/Dashboards/Provincial/Provincial_Dashboard_FY_Architecture_Implementation_Plan.md`
- `Documentations/V2/Budgets/Dashboards/Provincial/Phase4_5_LightweightDataset_Implementation.md`
- `Documentations/V2/Budgets/Dashboards/Provincial/Phase5_ResolverBatch_Implementation.md`
- `Documentations/V2/Budgets/Dashboards/Provincial/Phase6_DashboardCache_Implementation.md`
