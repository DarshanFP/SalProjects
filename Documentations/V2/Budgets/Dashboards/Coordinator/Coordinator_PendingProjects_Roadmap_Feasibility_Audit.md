# Coordinator Pending Projects — Roadmap Feasibility Audit

**Date:** 2026-03-08  
**Scope:** Architecture audit only — no code modifications.  
**Roadmap Reference:** [Coordinator_PendingProjects_Implementation_Roadmap.md](./Coordinator_PendingProjects_Implementation_Roadmap.md)

---

## 1. Current Architecture Validation

### 1.1 Controller Flow (Verified)

The roadmap correctly describes the `CoordinatorController::projectList()` flow:

| Aspect | Roadmap Description | Actual Code | Status |
|--------|---------------------|-------------|--------|
| Access Layer | `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` | Line 515–516: `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` | ✅ Correct |
| FY Scope | Applied via ProjectAccessService | `ProjectAccessService::getVisibleProjectsQuery` applies `inFinancialYear($fy)` when `$fy` is passed | ✅ Correct |
| Filters | search, province, provincial_id, user_id, center, project_type, status, date range | Lines 519–587: all present | ✅ Correct |
| Financial Resolution | Per-project `ProjectFinancialResolver::resolve()` in `map()` | Line 617: `$financials = $resolver->resolve($project)` inside `map()` | ✅ Correct |
| Pagination | Manual `skip()->take($perPage)->get()` | Lines 612–614: `skip()->take($perPage)->get()` | ✅ Correct |
| Filter Options | `Cache::remember('coordinator_project_list_filters', 5 min)` | Line 562: `Cache::remember($filterCacheKey, now()->addMinutes(5), ...)` | ✅ Correct |
| ProjectQueryService | Not used | No usage in `projectList` | ✅ Correct |
| DatasetCacheService | Not used | No usage in `projectList` | ✅ Correct |
| Grand Totals | None | No grand totals | ✅ Correct |
| Status Distribution | None | No status distribution | ✅ Correct |

### 1.2 Discrepancy: Expense N+1 (Not in Roadmap)

**Finding:** The roadmap identifies resolver N+1 but does **not** mention an additional **expense N+1**.

**Actual Code (Lines 620–627):**
```php
$projectApprovedReportIds = DPReport::approved()
    ->where('project_id', $project->project_id)
    ->pluck('report_id');
$totalExpenses = DPAccountDetail::whereIn('report_id', $projectApprovedReportIds)
    ->sum('total_expenses') ?? 0;
```

Per project, this runs **2 extra queries** (DPReport + DPAccountDetail). For 100 projects per page: **200 extra queries**.

**Important:** The controller already eager-loads `reports.accountDetails` (line 516). Provincial computes expenses in-memory from `$project->reports` and `$report->accountDetails`. Coordinator should do the same—**no additional DB queries needed**.

**Recommendation:** Add to Phase 2: replace DPReport/DPAccountDetail queries with in-memory calculation from eager-loaded `$project->reports` and `$report->accountDetails` (mirror Provincial logic).

### 1.3 Sort and Pagination

- **Sort:** Roadmap mentions "sort (query or post-fetch for budget_utilization)." Actual code applies query-level sort for `created_at`, `project_id`, `project_title`; `budget_utilization` is sorted post-fetch (lines 654–658). ✅ Correct.
- **Fixed 100 per page:** `$perPage = $request->get('per_page', 100)`—default 100; no validation against allowed sizes. Roadmap proposes `TableFormatter::resolvePerPage` (10, 25, 50, 100). ✅ Feasible.

---

## 2. Roadmap Phase Feasibility

### Phase 1 — Pagination Architecture

| Criterion | Assessment |
|-----------|------------|
| Code changes | Replace `skip()->take()->get()` with `->paginate($perPage)->withQueryString()`; use `TableFormatter::resolvePerPage($request)` |
| Compatibility | Fully compatible; Laravel paginator returns `LengthAwarePaginator` with `links()`, `firstItem()` |
| Dependencies | None |
| Risks | Low; ensure `withQueryString()` used for filter preservation |

**Verdict:** ✅ Feasible.

---

### Phase 2 — Financial Resolver Optimization

| Criterion | Assessment |
|-----------|------------|
| Code changes | Use `ProjectFinancialResolver::resolveCollection($projects)`; replace per-project `resolve()` with lookup from `$resolvedFinancials[$project->project_id]` |
| Compatibility | `resolveCollection()` returns `[project_id => financials]`; keys match `$project->project_id` |
| Dependencies | None |
| Risks | Low; ensure expenses also use in-memory calc (see §1.2) |

**Verdict:** ✅ Feasible. **Add expense N+1 fix** to scope.

---

### Phase 3 — Summary Metrics (Grand Totals)

| Criterion | Assessment |
|-----------|------------|
| Code changes | Load full filtered dataset; `resolveCollection`; sum grand totals; pass `$grandTotals`, `$totalRecordCount` to view |
| Compatibility | Provincial pattern confirmed: `fullDataset->get()` → `resolveCollection` → grand totals |
| Dependencies | Phase 2 (resolveCollection) |
| Risks | Medium—full dataset load for large result sets. Mitigation in roadmap (limit, SQL SUM) is appropriate |

**Verdict:** ✅ Feasible.

---

### Phase 4 — Status Distribution

| Criterion | Assessment |
|-----------|------------|
| Code changes | `$statusDistribution = $fullDataset->groupBy('status')->map->count()`; status cards; optional ApexCharts modal |
| Compatibility | Reuses full dataset from Phase 3 |
| Dependencies | Phase 3 |
| Risks | Low; Provincial view confirms ApexCharts and status cards pattern |

**Verdict:** ✅ Feasible.

---

### Phase 5 — Query Layer Refactor (ProjectQueryService)

| Criterion | Assessment |
|-----------|------------|
| Code changes | Replace `$this->projectAccessService->getVisibleProjectsQuery()` with `ProjectQueryService::forCoordinator()` |
| Compatibility | `ProjectQueryService::forCoordinator()` delegates to `ProjectAccessService::getVisibleProjectsQuery()` |
| Dependencies | None |
| Risks | Very low |

**Verdict:** ✅ Feasible.

---

### Phase 6 — Filter Improvements

| Criterion | Assessment |
|-----------|------------|
| Code changes | FY-scope project types, centers, users; include `$fy` in filter cache key |
| Compatibility | Provincial uses `inFinancialYear($fy)` for project types, centers, users. Coordinator can mirror. |
| Dependencies | None |
| Risks | Low; filter options may shrink when FY has fewer projects |

**Verdict:** ✅ Feasible.

---

### Phase 7 — Dataset Reuse & Flow Alignment

| Criterion | Assessment |
|-----------|------------|
| Code changes | Consolidate flow: baseQuery → fullDataset → resolveCollection → grandTotals + statusDistribution → paginate; attach financials from map |
| Compatibility | Matches Provincial architecture |
| Dependencies | Phases 2, 3, 4 |
| Risks | Low |

**Verdict:** ✅ Feasible.

---

### Phase 8 — UI Consistency & Polish

| Criterion | Assessment |
|-----------|------------|
| Code changes | Tooltips; budget column naming; preserve filters on redirect; Active Filters badges |
| Compatibility | Provincial uses `redirect()->route(..., request()->query())`; Coordinator approve goes to `coordinator.approved.projects`; revert uses `redirect()->back()` |
| Dependencies | None |
| Risks | Low; revert already preserves filters via `redirect()->back()` |

**Note:** Approve intentionally redirects to approved list. Revert uses `redirect()->back()`—if user came from filtered list, filters are preserved. Optional: add hidden `return_to` or explicit `redirect()->route('coordinator.projects.list', request()->query())` for clarity.

**Verdict:** ✅ Feasible.

---

### Phase 9 — Cache Layer Evaluation

| Criterion | Assessment |
|-----------|------------|
| Scope | Documentation only; no DatasetCacheService for project list |
| Compatibility | DatasetCacheService is for dashboards; project list is filter-heavy (search, status, date) |
| Risks | None |

**Verdict:** ✅ Correct decision.

---

## 3. Service Availability Verification

| Service / Method | Exists | Location |
|------------------|--------|----------|
| `ProjectQueryService::forCoordinator($coordinator, $fy)` | ✅ | `app/Services/ProjectQueryService.php:47–51` |
| `DatasetCacheService::getCoordinatorDataset(...)` | ✅ | `app/Services/DatasetCacheService.php:97–...` |
| `ProjectFinancialResolver::resolveCollection(Collection)` | ✅ | `app/Domain/Budget/ProjectFinancialResolver.php:212–220` |
| `TableFormatter::resolvePerPage(?Request, int)` | ✅ | `app/Helpers/TableFormatter.php:172–188` |
| `TableFormatter::ALLOWED_PAGE_SIZES` | ✅ | `[10, 25, 50, 100]` |
| `TableFormatter::resolveSerial()` | ✅ | For S.No. column with pagination |

All referenced services and methods are present and usable.

---

## 4. Architecture Improvements

### 4.1 Expense N+1 Fix (Add to Phase 2)

**Current:** 2 extra queries per project for expenses:
```php
DPReport::approved()->where('project_id', ...)->pluck('report_id');
DPAccountDetail::whereIn('report_id', ...)->sum('total_expenses');
```

**Proposed:** Use eager-loaded relations (Provincial pattern):
```php
$totalExpenses = 0;
foreach ($project->reports ?? [] as $report) {
    if ($report->isApproved() && $report->accountDetails) {
        $totalExpenses += $report->accountDetails->sum('total_expenses');
    }
}
```

**Impact:** Eliminates ~200 queries per page (100 projects × 2). Must be included in Phase 2.

### 4.2 Search Alignment (Optional, Phase 6)

Coordinator search: `project_id`, `project_title`, `project_type`, `status`.  
`ProjectQueryService::applySearchFilter` adds `society_name`, `place`. Consider aligning if product requires society/place search.

### 4.3 Grand Totals for Large Datasets (Phase 3)

Roadmap already proposes: `$baseQuery->limit(10000)->get()` or SQL aggregation. For very large FYs (e.g. 10k+ projects), prefer SQL `SUM()`/`COUNT()` for grand totals and status distribution to avoid memory spikes.

---

## 5. Performance Risk Assessment

| Risk | Description | Mitigation |
|------|-------------|------------|
| Full dataset load (Phase 3) | 5000+ projects in one collection | Limit (e.g. 10000) or aggregate via SQL; document threshold |
| Resolver N+1 (Phase 2) | 100 `resolve()` calls per page | Use `resolveCollection()`—single batch |
| Expense N+1 (Phase 2) | 200 extra queries per page | Use eager-loaded `reports.accountDetails` in-memory |
| Filter cache | Stale when FY changes | Include `$fy` in cache key |

**Estimated improvement (Phase 1 + 2 with expense fix):**
- Queries per page: ~300 → ~5–10 (base query, full dataset, resolveCollection, filter options).
- Page load: ~50–80% faster for typical 100-item pages.

---

## 6. Dataset Cache Evaluation

The roadmap correctly recommends **not** using DatasetCacheService for the coordinator project list:

| Factor | Assessment |
|--------|------------|
| Filter-heavy | 10+ filters; cache key would need filter hash |
| Search | Text search cannot be applied in-memory on cached collection |
| Status/date filters | High variability; low cache hit rate |
| Provincial | Also does not use DatasetCacheService for project list |
| Dashboard | DatasetCacheService remains appropriate for coordinator dashboard |

**Verdict:** Correct to exclude DatasetCacheService from project list.

---

## 7. Missing Improvements Identified

| Improvement | Severity | Roadmap | Recommendation |
|-------------|----------|---------|----------------|
| Expense N+1 (DPReport + DPAccountDetail per project) | Critical | Not mentioned | Add to Phase 2 |
| Search: society_name, place | Low | Not mentioned | Optional Phase 6 |
| Clear/Reset button | Low | Phase 8 | Coordinator Clear → `coordinator.projects.list`; Provincial Reset → `route(..., ['fy' => currentFY()])`. Decide: clear all vs reset to current FY with params |
| `TableFormatter::resolveSerial` for S.No. | Low | Phase 1 implied | Use for consistent S.No. across pages |

---

## 8. UI Parity with Provincial

| Feature | Provincial | Coordinator | Roadmap Phase |
|---------|------------|-------------|---------------|
| Grand totals summary block | ✅ | ❌ | Phase 3 |
| Status distribution cards | ✅ | ❌ | Phase 4 |
| Status chart modal (ApexCharts donut) | ✅ | ❌ | Phase 4 |
| Per-page selector (10, 25, 50, 100) | ✅ | ❌ | Phase 1 |
| `$projects->links()` | ✅ | ❌ (manual pagination) | Phase 1 |
| `TableFormatter::resolveSerial` | ✅ | ❌ | Phase 1 |
| Tooltips (data-bs-toggle="tooltip") | ✅ | ❌ | Phase 8 |
| Budget column naming | Overall/Existing/Local/Balance | Budget/Expenses/Remaining/Utilization | Phase 8 (document choice) |

Roadmap covers these UI differences.

---

## 9. Updated Phase Plan

Recommended phase order (unchanged from roadmap):

1. **Phase 1** — Pagination  
2. **Phase 2** — Resolver optimization **+ expense N+1 fix**  
3. **Phase 5** — Query layer  
4. **Phase 3** — Grand totals  
5. **Phase 4** — Status distribution  
6. **Phase 7** — Dataset reuse  
7. **Phase 6** — Filter improvements  
8. **Phase 8** — UI polish  
9. **Phase 9** — Cache evaluation (documentation)

**Phase 2 scope addition:**
- Replace `DPReport::approved()->where(...)` and `DPAccountDetail::whereIn(...)` with in-memory expense calculation from `$project->reports` and `$report->accountDetails` (mirror Provincial).

---

## 10. Final Recommendations

1. **Proceed with roadmap** — Phases are feasible and well-scoped.
2. **Extend Phase 2** — Include expense N+1 fix using eager-loaded relations.
3. **Follow Provincial pattern** — Full dataset → resolveCollection → grand totals + status → paginate → attach from map.
4. **Do not use DatasetCacheService** for project list.
5. **Optional:** In Phase 6, add society/place to search if needed; in Phase 8, document budget column naming choice (Coordinator vs Provincial).
6. **Phase 3 mitigation:** Document threshold (e.g. 5000 projects) for SQL aggregation vs full collection load.

---

**Audit Complete.** No code changes made; documentation only.
