# Provincial Dashboard Implementation Plan Feasibility Audit

**Date:** 2026-03-05  
**Scope:** Verify whether the current Laravel codebase can safely implement all phases defined in `Provincial_Dashboard_FY_Architecture_Implementation_Plan.md` without breaking existing logic.

---

## 1. Controller Architecture Compatibility

### 1.1 Current Structure

| Aspect | Current State |
|--------|---------------|
| **Entry point** | `ProvincialController::provincialDashboard(Request $request)` |
| **Project queries** | Main: `Project::accessibleByUserIds($accessibleUserIds)->approved()->inFinancialYear($fy)` with optional `whereHas('user', ...)` for center/role and `where('project_type', ...)`. Society: `Project::where('province_id', $provinceId)->whereNotNull('society_id')->inFinancialYear($fy)`. |
| **Data flow** | Main `$projects` passed to `calculateBudgetSummariesFromProjects($projects, $request)`. Widget methods (`calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateCenterPerformance`, `calculateEnhancedBudgetData`, `prepareCenterComparisonData`) perform their own queries internally. |
| **Shared dataset** | Main `$projects` is used only for budget summary. Widgets do not receive or reuse it. |

### 1.2 Support for Plan Phases

| Phase | Compatible? | Notes |
|-------|-------------|-------|
| **Shared dataset injection** | Partial | Controller has `$projects` for budget summary; widget methods would need refactoring to accept `$approvedProjects` instead of querying. Structure allows passing datasets into private methods. |
| **Dataset caching** | Yes | Controller could check cache before building `$projectsQuery`; cache key must include FY and filters (center, role, project_type). |
| **Resolver batching** | Yes | `ProjectFinancialResolver::resolveCollection()` exists and is used in ExecutorController. ProvincialController currently uses per-project `$resolver->resolve()` in loops. Refactor is straightforward. |

### 1.3 Conclusion

Controller structure supports shared dataset and resolver batching with refactoring. Dataset caching is feasible but cache key must account for request filters.

---

## 2. FY Propagation Safety

### 2.1 Call Chain Verification

| Method | Signature | Called By | $fy Passed? |
|--------|-----------|-----------|-------------|
| `calculateTeamPerformanceMetrics($provincial, string $fy)` | 2 params | `provincialDashboard()` line 226 | Yes |
| `prepareChartDataForTeamPerformance($provincial, string $fy)` | 2 params | `provincialDashboard()` line 227 | Yes |
| `calculateCenterPerformance($provincial, string $fy)` | 2 params | `provincialDashboard()` line 228; `prepareCenterComparisonData()` line 2555 | Yes |
| `calculateEnhancedBudgetData($provincial, string $fy)` | 2 params | `provincialDashboard()` line 236 | Yes |
| `prepareCenterComparisonData($provincial, string $fy)` | 2 params | `provincialDashboard()` line 239 | Yes |

### 2.2 FY Usage in Methods

All five methods use `->inFinancialYear($fy)` on their project queries. No missing arguments observed in current code.

### 2.3 Phase 1 Status

**Phase 1 (Runtime Fix)** is effectively **already addressed** in the codebase. `prepareCenterComparisonData($provincial, $fy)` accepts and forwards `$fy`. No changes required unless a different code path exists in another environment.

---

## 3. Dataset Sharing Feasibility

### 3.1 Current vs Required Datasets

| Widget Method | Current Dataset | Accepts Shared? | Refactor Complexity |
|---------------|-----------------|-----------------|---------------------|
| `calculateBudgetSummariesFromProjects` | `$projects` (filtered) | Already receives projects | None |
| `calculateTeamPerformanceMetrics` | Fetches `teamProjects` (all statuses), `teamReports` | Could accept approved + pending + reports | **Medium** — needs `teamReports` separately; teamProjects includes non-approved |
| `prepareChartDataForTeamPerformance` | Same as above | Same | **Medium** |
| `calculateCenterPerformance` | Per-center: `Project::whereIn('user_id', $centerUsers)` | Could derive from shared `$approvedProjects` filtered by center | **Medium** — structure differs; also needs `teamReports` per center |
| `calculateEnhancedBudgetData` | Fetches approved + pending | Can accept both | **Low** |

### 3.2 Data Shape Mismatches

- **calculateTeamPerformanceMetrics** and **prepareChartDataForTeamPerformance** use `teamProjects` (all statuses, FY-filtered) and `teamReports` (all statuses, no FY). Main `$projects` is approved-only. Sharing would require either:
  - Passing approved + pending projects and reports separately, or
  - Changing widget semantics to use approved-only (may change displayed metrics).
- **calculateCenterPerformance** fetches projects per center via `whereIn('user_id', $centerUsers)`. A shared `$approvedProjects` could be filtered in memory by `$project->user_id` in `$centerUsers` if `user` relation is loaded. Requires `user` (or at least `user_id` + center lookup).

### 3.3 Conclusion

Dataset sharing is **feasible with moderate refactoring**. Main budget summary already uses shared `$projects`. Widget methods need signature changes and logic to consume shared collections. Care must be taken to preserve semantics (e.g., team metrics use all-status projects; budget data uses approved only).

---

## 4. Dataset Cache Compatibility

### 4.1 Determinism

| Factor | Deterministic? | Impact on Cache Key |
|--------|----------------|---------------------|
| FY | Yes | Must include `$fy` |
| Center | Yes (request) | Must include if cached dataset is filtered |
| Role | Yes (request) | Must include if filtered |
| Project type | Yes (request) | Must include if filtered |

### 4.2 Current Filter Application

Main `$projects` query applies center, role, project_type **before** `->get()`. Cached dataset for budget summary must be keyed by these filters.

Widget methods (`calculateTeamPerformanceMetrics`, etc.) use **unfiltered** team datasets (no center/role/project_type). Their dataset can be cached with key `dashboard_dataset_provincial_{province_id}_{fy}`.

### 4.3 Cache Key Recommendation

- **Budget summary (filtered):** `dashboard_dataset_provincial_{province_id}_{fy}_{center}_{role}_{project_type}` (use empty string or hash for "all").
- **Widget data (unfiltered):** `dashboard_dataset_provincial_{province_id}_{fy}` — one cache per province+FY for team metrics, center performance, enhanced budget.

### 4.4 Conclusion

Dataset caching is **feasible**. Cache keys must distinguish filtered (budget summary) vs unfiltered (widgets) datasets. Two cache layers may be needed, or a single unfiltered cached dataset with in-memory filtering for budget summary.

---

## 5. Projection Dataset Compatibility

### 5.1 Plan Projection Fields

```
project_id, province_id, society_id, project_type, user_id, in_charge,
commencement_month_year, opening_balance, amount_sanctioned,
amount_forwarded, local_contribution, overall_project_budget, status
```

### 5.2 Resolver Strategy Requirements

| Strategy | Project Attributes | Relations | Notes |
|----------|--------------------|-----------|-------|
| **PhaseBasedBudgetStrategy** | `amount_forwarded`, `local_contribution`, `current_phase`, `overall_project_budget`, `amount_sanctioned`, `opening_balance` | `budgets` (loadMissing) | Uses `budgets` for phase-based total; fallback to `overall_project_budget` if empty. |
| **DirectMappedIndividualBudgetStrategy** | `project_type` | `iiesExpenses`, `iesExpenses`, `ilpBudget`, `iahBudgetDetails`, `igeBudget` (loadMissing) | Type-specific; fallback to `fallbackFromProject()` using project attributes. |
| **ProjectFinancialResolver::applyCanonicalSeparation** | `amount_sanctioned`, `opening_balance`, `amount_forwarded`, `local_contribution` | — | Uses `$project->isApproved()` which needs `status`. |

### 5.3 Missing Attributes

- **PhaseBasedBudgetStrategy:** `current_phase` — not in plan projection. Add to select.
- **Resolver / BudgetSyncGuard::isApproved:** Uses `$project->status` — included.

### 5.4 Relation Dependency

- **PhaseBasedBudgetStrategy:** Calls `$project->loadMissing('budgets')`. Lightweight projection without `budgets` will trigger N+1 when strategy runs. Either include `->with('budgets')` (partial lightweight) or accept that PhaseBasedBudgetStrategy will issue extra queries.
- **DirectMappedIndividualBudgetStrategy:** Calls `loadMissing($this->getRelationsForType($projectType))` (e.g., `iiesExpenses`, `iesExpenses`). Same: projection without these relations causes lazy loading.

### 5.5 Conclusion

**Partial compatibility.** Projection can reduce columns from `projects` table. Resolver strategies, however, rely on relations (`budgets`, type-specific tables). A fully lightweight projection (no relations) would force:

1. Extra queries via `loadMissing()` (reducing projection benefit), or  
2. Strategy changes to use only project attributes (fallback paths) — may change semantics for some project types.

**Recommendation:** Use projection for `projects` columns but keep minimal `with(['user', 'budgets'])` and type-specific relations where needed. Or implement a "resolver-friendly" projection that includes only required relations.

---

## 6. Resolver Batch Compatibility

### 6.1 resolveCollection() Signature

```php
public static function resolveCollection(Collection $projects): array
```

Accepts `Collection<int, Project>`. Returns `[project_id => resolved_array]`.

### 6.2 Requirements

- Projects must be `Project` instances (Eloquent).
- Each project must have `project_id`, `status`, and attributes used by strategies.
- Docblock says: "Projects must have reports, reports.accountDetails, budgets eager-loaded." Current ExecutorController passes projects with `reports`, `reports.accountDetails`, `budgets` in `$with`.

### 6.3 Provincial Usage

`calculateBudgetSummariesFromProjects` uses `$project->reports` and `$project->user`. Resolver itself does not use reports; aggregation logic does (for approved/unapproved expenses). So for `resolveCollection` to be the only change, projects must still have `reports` and `reports.accountDetails` for budget summary; `user` for center grouping.

### 6.4 Conclusion

`resolveCollection()` is **compatible**. Provincial can call it once and pass the map to aggregation methods. Projects must have `reports`, `reports.accountDetails`, `user` (or equivalent) for aggregation logic; resolver strategies may also require `budgets` or type-specific relations. No changes needed to `ProjectFinancialResolver` for batch use.

---

## 7. Database Aggregation Feasibility

### 7.1 Schema Support

| Column | Exists | Indexed | Usage |
|--------|--------|---------|-------|
| `province_id` | Yes | Yes | Province-scoped queries |
| `society_id` | Yes | Yes | Society breakdown |
| `project_type` | Yes | — | Grouping |
| `commencement_month_year` | Yes | Yes | FY filter |
| `opening_balance` | Yes | — | Sum aggregation |
| `user_id` | Yes | Yes (composite) | Center via users join |

### 7.2 Semantic Constraint

Resolver computes `opening_balance` from strategies (phase-based, type-specific). Raw `SUM(opening_balance)` from `projects` may not match resolved totals for:

- Phase-based types (strategy uses `budgets` table).
- Direct-mapped types (strategy uses type-specific tables).

DB aggregation is **accurate only** for cases where resolved `opening_balance` equals stored `opening_balance` (e.g., approved projects with no phase/type overrides). For full consistency with resolver, aggregations must either:

- Run post-resolution in PHP, or  
- Use a materialized/snapshot table with precomputed resolved values.

### 7.3 Safe Aggregations

- **Project counts:** `COUNT(*)` by province, center, society — safe.
- **Raw `SUM(opening_balance)`:** Use with caution; may diverge from resolver for phase-based and some direct-mapped types.
- **Center totals:** Join `users` on `user_id` for `center`; `GROUP BY users.center` — schema supports it.

### 7.4 Conclusion

Database aggregation is **partially feasible**. Count and simple sums (where resolver equals DB) work. For resolver-accurate totals, prefer PHP aggregation or snapshot tables. Phase 8 should document this semantic constraint.

---

## 8. Snapshot Table Feasibility

### 8.1 Table Creation

Laravel migrations and schema builder support new tables. `dashboard_snapshots` can be added via migration.

### 8.2 Refresh Mechanism

- **Laravel Scheduler:** `app/Console/Kernel.php` — can schedule snapshot refresh (e.g., hourly).
- **Queue jobs:** Laravel queues support job dispatch; snapshot refresh can be queued.
- **Event-triggered:** Would require observers/listeners (see Section 9).

### 8.3 Conclusion

Snapshot tables are **feasible**. No schema conflicts identified. Scheduler and queues are available for refresh.

---

## 9. Cache Invalidation Feasibility

### 9.1 Current State

- **EventServiceProvider:** No project or report event listeners.
- **Project model:** No `Project::observe()` in `AppServiceProvider` or `AuthServiceProvider`.
- **DPReport model:** No observers.
- **CoordinatorController:** Has `invalidateDashboardCache()` called manually after some actions; not wired to model events.

### 9.2 Required Work

- Add `ProjectObserver` and `DPReportObserver` (or equivalent).
- Register in `AppServiceProvider::boot()`: `Project::observe(ProjectObserver::class)`.
- In observers, call a `DashboardCacheInvalidator` service to clear keys such as `dashboard_dataset_provincial_{province_id}_*` and `provincial_dashboard_*`.

### 9.3 Conclusion

**New implementation required.** No existing event-driven invalidation for dashboards. Observers and an invalidation service must be added. Low risk if observers only clear cache and do not alter business logic.

---

## 10. UI Compatibility

### 10.1 Current Provincial Index

| Feature | Present | Notes |
|---------|---------|-------|
| FY selector | Yes | In filter form, `name="fy"`, `onchange="this.form.submit()"` |
| Center selector | Yes | Same form |
| Role selector | Yes | Same form |
| Project type selector | Yes | Same form |
| Filter persistence | Yes | Form submits to `route('provincial.dashboard')` with query params |
| Clear Filters | Yes | Links to `route('provincial.dashboard')` (resets all, including FY) |

### 10.2 Phase 3 Control Bar

Plan suggests a control bar above Budget Overview. Current layout uses a filter form inside the Budget Summary card. A separate control bar can be added without changing existing structure. Blade supports it.

### 10.3 Conclusion

UI is **compatible**. FY selector, filters, and persistence already exist. Phase 3 (control bar) is additive.

---

## 11. Performance Impact Estimation

| Phase | Expected Improvement | Confidence |
|-------|----------------------|------------|
| **Phase 4 (shared dataset)** | 3–5× fewer project queries (from ~6+ to 1–2) | High |
| **Phase 4.5 (lightweight projection)** | 20–40× load speed, 80–90% memory reduction **if** resolver strategies avoid extra relation loads; else moderate gains | Medium (depends on strategy handling) |
| **Phase 5 (resolver batching)** | N→1 resolver passes; ~2–4× for typical N | High |
| **Phase 6 (dashboard cache)** | 10–30× on cache hit (repeat loads) | High |
| **Phase 8 (DB aggregation)** | Significant for totals-only reads when safe (counts, simple sums) | Medium |
| **Phase 9 (snapshot tables)** | Near-instant dashboard when reading from snapshots | High |

---

## 12. Risks Before Implementation

| Risk | Severity | Mitigation |
|------|----------|------------|
| **Resolver strategies load relations** | Medium | Lightweight projection may trigger `loadMissing()`; include minimal relations or accept extra queries for Phase 4.5. |
| **Dataset cache key complexity** | Low | Document key format; include FY and filters where needed. |
| **Team metrics vs budget semantics** | Medium | Confirm whether team metrics must use all-status projects; refactor carefully to preserve behavior. |
| **DB aggregation vs resolver semantics** | Medium | Use DB aggregation only where resolver matches DB (e.g., approved projects with canonical `opening_balance`); otherwise keep PHP aggregation. |
| **Event observers** | Low | Add observers for cache invalidation only; avoid side effects. |
| **Clear Filters resets FY** | Low | Document or change "Clear Filters" to preserve FY if required. |
| **Society breakdown uses different query** | Low | Society stats use `province_id` + `society_id`; can be integrated into shared dataset or kept separate. |

---

## Summary Matrix

| Phase | Feasibility | Requires | May Break |
|-------|-------------|----------|-----------|
| 1 | Done | None | No |
| 2 | Immediate | Replace `listAvailableFY` with `listAvailableFYFromProjects` | No |
| 3 | Immediate | Add control bar | No |
| 3.5 | Feasible | Cache layer, key design | No |
| 4 | Refactoring | Pass shared datasets to widget methods | Possible if semantics change |
| 4.5 | Partial | Resolver strategy handling of relations | Possible if projection omits required relations |
| 5 | Immediate | Use `resolveCollection`, pass map to aggregations | No |
| 6 | Feasible | Cache wrapper, invalidation | No |
| 8 | Partial | DB aggregation only where safe | Possible if used for resolver-sensitive totals |
| 9 | Feasible | Migration, scheduler/job | No |
| 10 | New code | Observers, invalidation service | No |
