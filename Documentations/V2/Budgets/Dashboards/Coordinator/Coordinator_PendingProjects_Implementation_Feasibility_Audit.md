# Coordinator Pending Projects — Implementation Feasibility Audit

**Date:** 2026-03-08  
**Scope:** Architecture audit only — no code modifications.  
**Roadmap Reference:** [Coordinator_PendingProjects_Implementation_Roadmap.md](./Coordinator_PendingProjects_Implementation_Roadmap.md)

---

## 1. Roadmap Accuracy Validation

### 1.1 Current Architecture — Verified

| Roadmap Claim | Actual Code | Location | Status |
|---------------|-------------|----------|--------|
| `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` | `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` | CoordinatorController.php:515-516 | ✅ Match |
| Filters: search, province, provincial_id, user_id, center, project_type, status, date range | All present | CoordinatorController.php:519-587 | ✅ Match |
| Manual pagination: `skip()->take()->get()` | `skip(($currentPage - 1) * $perPage)->take($perPage)->get()` | CoordinatorController.php:612-614 | ✅ Match |
| Per-project `$resolver->resolve($project)` | `$financials = $resolver->resolve($project)` | CoordinatorController.php:617 | ✅ Match |
| Per-project DPReport + DPAccountDetail queries | Lines 621-625 | CoordinatorController.php:620-625 | ✅ Match |
| Filter cache: `coordinator_project_list_filters` | `Cache::remember($filterCacheKey, now()->addMinutes(5), ...)` | CoordinatorController.php:561-562 | ✅ Match |
| Sort: query-level for created_at, project_id, project_title; post-fetch for budget_utilization | `in_array($sortBy, [...])` + post-fetch `sortBy` | CoordinatorController.php:596-603, 654-658 | ✅ Match |

### 1.2 Discrepancies

| Item | Roadmap | Actual | Notes |
|------|---------|--------|-------|
| Line reference for expense N+1 | "lines 620–627" | Lines 621-625 (DPReport/DPAccountDetail) | Minor; correct code block |
| `count()` before pagination | Not explicitly mentioned | Line 591: `$totalProjects = $projectsQuery->count()` | Extra query; eliminated when moving to full-dataset flow |
| Default per_page | "fixed 100" | `$request->get('per_page', 100)` — accepts request but no validation | User can pass arbitrary per_page (e.g. 9999); Phase 1 adds validation |

### 1.3 Verdict

**Roadmap is accurate.** The current architecture is correctly described. Minor omissions (count query, per_page validation) do not affect feasibility.

---

## 2. Controller Architecture Analysis

### 2.1 Current Query Pipeline (CoordinatorController::projectList)

```
Line 516:  $projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)
           ->with(['user.parent', 'reports.accountDetails', 'budgets'])
           ->withMax('statusHistory', 'created_at');

Lines 519-587:  Filters applied in-place (search, province, provincial_id, user_id, center, 
                project_type, project_types, status, statuses, start_date, end_date)

Line 591:  $totalProjects = $projectsQuery->count();

Lines 596-603:  Sort applied in-place (sort_by, sort_order)

Lines 612-614:  $projects = $projectsQuery->skip(...)->take($perPage)->get()

Lines 615-650:  ->map() with resolver->resolve() + DPReport::approved()->where() + DPAccountDetail::whereIn()

Lines 654-658:  Post-fetch sort for budget_utilization (if applicable)

Line 561:  Filter options from Cache::remember
```

### 2.2 Pagination Logic

- **Current:** `skip(($currentPage - 1) * $perPage)->take($perPage)->get()` (lines 612-614)
- **Manual metadata:** `$paginationData` built at lines 674-680
- **View receives:** `$projects` (Collection), `$pagination` (array)

### 2.3 Financial Resolver Usage

- **Pattern:** `app(\App\Domain\Budget\ProjectFinancialResolver::class)` resolved at line 609
- **Call:** `$resolver->resolve($project)` inside `map()` at line 617
- **Output used:** `$financials['opening_balance']` for `$projectBudget`
- **N+1:** 100 projects → 100 resolver calls per page

### 2.4 Expense Calculation Logic

**Current (lines 620-627):**
```php
$projectApprovedReportIds = DPReport::approved()
    ->where('project_id', $project->project_id)
    ->pluck('report_id');
$totalExpenses = DPAccountDetail::whereIn('report_id', $projectApprovedReportIds)
    ->sum('total_expenses') ?? 0;
```

- **N+1:** 2 queries per project (DPReport + DPAccountDetail) → 200 queries per 100 projects
- **Comment in code:** "optimized - use direct query instead of loading all" — **incorrect**; data is already eager-loaded

### 2.5 Eager-Loaded Relationships

**Line 516-518:**
```php
->with(['user.parent', 'reports.accountDetails', 'budgets'])
->withMax('statusHistory', 'created_at')
```

| Relationship | Loaded | Nested | Used for Expense Calc? |
|--------------|--------|--------|------------------------|
| `user.parent` | ✓ | Yes | No |
| `reports.accountDetails` | ✓ | Yes (loads reports + accountDetails) | **Yes — but unused** |
| `budgets` | ✓ | No | Resolver (via project) |
| `statusHistory` (max) | ✓ | withMax | Last action date |

**Finding:** `reports` and `reports.accountDetails` **are** eager-loaded. The in-memory expense calculation is **feasible** — the controller currently ignores this data and re-queries instead.

### 2.6 Sort Implementation

- **Query-level sort:** `created_at`, `project_id`, `project_title` (lines 597-603)
- **Post-fetch sort:** `budget_utilization` (lines 654-658) — applied to the current page’s collection only
- **Compatibility:** When switching to `paginate()`, post-fetch sort can remain on `$projects->getCollection()` before rendering

### 2.7 Query Cloning Feasibility

- **Current:** Single `$projectsQuery` mutated in place; no cloning
- **Target:** `(clone $baseQuery)` for full dataset and paginated listing
- **Laravel:** `Illuminate\Database\Eloquent\Builder` supports `__clone`; used in Provincial controller at lines 682, 737
- **Verdict:** Cloning is feasible and already used in the codebase.

---

## 3. Service Availability Verification

### 3.1 ProjectQueryService::forCoordinator

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Services/ProjectQueryService.php:47-51` |
| **Signature** | `public static function forCoordinator(User $coordinator, string $fy): Builder` |
| **Implementation** | Delegates to `app(ProjectAccessService::class)->getVisibleProjectsQuery($coordinator, $fy)` |
| **Return type** | `Illuminate\Database\Eloquent\Builder` |
| **Match** | ✅ Matches roadmap usage |

### 3.2 ProjectFinancialResolver::resolveCollection

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Domain/Budget/ProjectFinancialResolver.php:212-219` |
| **Signature** | `public static function resolveCollection(Collection $projects): array` |
| **Return** | `[project_id => financials]` — array keyed by `$project->project_id` |
| **PHPDoc** | "Projects must have reports, reports.accountDetails, budgets eager-loaded" |
| **Match** | ✅ Matches roadmap usage |

### 3.3 TableFormatter::resolvePerPage

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Helpers/TableFormatter.php:170-188` |
| **Signature** | `public static function resolvePerPage(?Request $request = null, int $default = 25): int` |
| **Behavior** | Validates against `ALLOWED_PAGE_SIZES` [10, 25, 50, 100]; rejects invalid values |
| **Match** | ✅ Roadmap passes `$request`; method accepts `?Request` — compatible |

### 3.4 TableFormatter::resolveSerial

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Helpers/TableFormatter.php:86-96` |
| **Signature** | `public static function resolveSerial($loop, $collection = null, bool $paginated = false): int` |
| **Behavior** | Uses `$collection->firstItem()` when paginated; `LengthAwarePaginator` has `firstItem()` |
| **Match** | ✅ Matches roadmap usage |

### 3.5 DatasetCacheService::getCoordinatorDataset

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Services/DatasetCacheService.php:99-137` |
| **Signature** | `public static function getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection` |
| **Eager loads** | `['user', 'reports.accountDetails', 'budgets']` |
| **Roadmap** | Should NOT be used for project list — filter-heavy |
| **Match** | ✅ Exists; roadmap correctly excludes it |

### 3.6 ProjectAccessService::getVisibleProjectsQuery

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Services/ProjectAccessService.php:103-124` |
| **Coordinator path** | Returns `Project::query()` (unfiltered) with `inFinancialYear($fy)` |
| **Match** | ✅ Same scope as `ProjectQueryService::forCoordinator` |

---

## 4. Pagination Feasibility

### 4.1 Current vs. Target

| Aspect | Current | Target | Compatible? |
|--------|---------|--------|-------------|
| Method | `skip()->take()->get()` | `paginate($perPage)->withQueryString()` | ✅ Yes |
| Per-page | `$request->get('per_page', 100)` unvalidated | `TableFormatter::resolvePerPage($request)` | ✅ Yes |
| View | Receives `$projects` (Collection), `$pagination` (array) | Receives `$projects` (LengthAwarePaginator) | ⚠️ View must change |
| Links | Manual prev/next in Blade | `$projects->links()` | ⚠️ View must change |
| Serial numbers | `$pagination['from'] + $loop->iteration - 1` | `TableFormatter::resolveSerial($loop, $projects, true)` | ✅ Yes |

### 4.2 Sort Compatibility

- **Query-level sort:** Unchanged; applied before `paginate()`
- **Post-fetch sort (budget_utilization):** Must run on `$projects->getCollection()` after pagination; ordering applies only to the current page. **Feasible** — same semantics as now.

### 4.3 Filter Preservation

- `paginate()->withQueryString()` keeps `page`, `per_page`, and all other query params in pagination URLs.
- **Verdict:** No incompatibility.

### 4.4 Potential Incompatibilities

| Risk | Assessment |
|------|------------|
| `$projects` type change (Collection → LengthAwarePaginator) | Blade must use `$projects->getCollection()` for `@forelse` if direct iteration differs; actually `@forelse($projects as $project)` works on paginator (it iterates the collection) |
| Manual `$pagination` array removed | Replace with `$projects->total()`, `$projects->currentPage()`, etc., or pass paginator directly |
| `firstItem()` for serials | `LengthAwarePaginator` provides `firstItem()` |

**Verdict:** Pagination refactor is **feasible**. View updates are required but straightforward.

---

## 5. Resolver Optimization Feasibility

### 5.1 resolveCollection Behavior

- **Implementation:** Iterates projects and calls `$resolver->resolve($project)` for each; returns `[$project->project_id => result]`
- **DB queries:** Resolver does not issue queries; uses loaded relations
- **Key format:** `$project->project_id` (string) — matches lookup for paginated items

### 5.2 Integration Points

- **Grand totals:** Loop over `$fullDataset`, sum from `$resolvedFinancials[$project->project_id]` ( Provincial pattern, lines 699-709 )
- **Page items:** Attach from `$resolvedFinancials[$project->project_id]` in `transform()` (Provincial lines 744-765)
- **Resolver PHPDoc:** Requires `reports`, `reports.accountDetails`, `budgets` — Coordinator already loads these

**Verdict:** Resolver optimization is **feasible**.

---

## 6. Expense N+1 Fix Feasibility

### 6.1 Eager Loading Verification

**Controller (line 517):**
```php
->with(['user.parent', 'reports.accountDetails', 'budgets'])
```

**Relations:**
- `Project::reports()` → `hasMany(DPReport::class)` (Project.php:842-844)
- `DPReport::accountDetails()` → `hasMany(DPAccountDetail::class)` (DPReport.php:253-256)

**Nested `reports.accountDetails`:** Loads `reports` and each report’s `accountDetails` — both needed for in-memory calculation.

### 6.2 Provincial Pattern

**ProvincialController (lines 714-718, 748-752):**
```php
$totalExpenses = 0;
if ($project->reports) {
    foreach ($project->reports as $report) {
        if ($report->isApproved() && $report->accountDetails) {
            $totalExpenses += $report->accountDetails->sum('total_expenses') ?? 0;
        }
    }
}
```

### 6.3 Semantic Equivalence

| Current (Coordinator) | Provincial / Proposed |
|----------------------|------------------------|
| `DPReport::approved()->where('project_id', ...)->pluck('report_id')` | `$report->isApproved()` |
| `DPAccountDetail::whereIn('report_id', ...)->sum('total_expenses')` | `$report->accountDetails->sum('total_expenses')` |

`DPReport::scopeApproved` uses `whereIn('status', self::APPROVED_STATUSES)`. `$report->isApproved()` uses `in_array($this->status, self::APPROVED_STATUSES)`. **Semantically equivalent.**

### 6.4 approved_reports_count

Current code (line 646): `$project->approved_reports_count = $projectApprovedReportIds->count()`.

Proposed: Compute from `$project->reports`:
```php
$project->approved_reports_count = collect($project->reports ?? [])->filter(fn($r) => $r->isApproved())->count();
```

**Verdict:** Expense N+1 fix is **feasible**. Eager loading is sufficient; Provincial pattern is a proven reference.

---

## 7. Full Dataset Strategy Evaluation

### 7.1 Proposed Flow

```
baseQuery (filters + sort applied)
  → (clone baseQuery)->with([...])->get()  = fullDataset
  → resolveCollection(fullDataset)
  → grandTotals + statusDistribution
  → (clone baseQuery)->paginate($perPage)->withQueryString()
  → attach from resolvedFinancials to page items
```

### 7.2 Clone Safety

- **Laravel Builder:** Supports `clone`; creates a new builder with copied bindings and structure
- **Provincial usage:** `(clone $baseQuery)` at lines 682, 737
- **Verdict:** Safe and already used

### 7.3 Filter Application

- All filters are applied to `$projectsQuery` in place before any `get()` or `paginate()`
- `baseQuery` = `$projectsQuery` after filters and sort, before `skip`/`take`
- Cloning before `skip`/`take` preserves the filtered, sorted query

### 7.4 Large Dataset Risk (>5000 projects)

| Risk | Mitigation (from roadmap) | Assessment |
|------|---------------------------|------------|
| Memory | `$baseQuery->limit(10000)->get()` or SQL aggregation | Reasonable |
| Time | Single `get()` on large result set | Consider `limit()` or COUNT/SUM aggregation for extreme cases |

**Verdict:** Full dataset strategy is **feasible**. Mitigations for large datasets are adequate.

---

## 8. Dataset Reuse Architecture Validation

### 8.1 Flow Validation

| Step | Feasible | Notes |
|------|----------|-------|
| `baseQuery` built with filters | ✅ | Current behavior |
| `fullDataset = (clone baseQuery)->with([...])->get()` | ✅ | Builder clone supported |
| `resolvedFinancials = resolveCollection(fullDataset)` | ✅ | Returns `[project_id => financials]` |
| Grand totals from `fullDataset` + `resolvedFinancials` | ✅ | Provincial pattern |
| Status distribution: `$fullDataset->groupBy('status')->map->count()` | ✅ | No extra queries |
| `paginate = (clone baseQuery)->paginate($perPage)` | ✅ | Independent clone |
| Attach to page items from `resolvedFinancials[$project->project_id]` | ✅ | Keys match |

### 8.2 project_id Key Consistency

- Resolver returns `$result[$project->project_id] = ...`
- `Project::$project_id` is the primary business key (string)
- Paginated items are `Project` models with the same `project_id`
- **Verdict:** Keys align; no mismatch risk.

### 8.3 Resolver Output Format

Resolver returns:
```php
[
    'overall_project_budget' => float,
    'amount_forwarded' => float,
    'local_contribution' => float,
    'amount_sanctioned' => float,
    'amount_requested' => float,
    'opening_balance' => float
]
```

Grand totals and expense calculations use these keys. **Compatible.**

---

## 9. Query Layer Refactor Validation

### 9.1 Swap Validation

| Current | Replacement | Equivalent? |
|---------|-------------|-------------|
| `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` | `ProjectQueryService::forCoordinator($coordinator, $fy)` | ✅ Yes — direct delegation |

**ProjectQueryService::forCoordinator** (line 50-51):
```php
return app(ProjectAccessService::class)->getVisibleProjectsQuery($coordinator, $fy);
```

### 9.2 Scope and FY

- Both use the same `ProjectAccessService` call
- Same FY, same visibility logic
- Eager loading is applied on the returned Builder; no change needed

**Verdict:** Query layer refactor is **feasible** and behavior-preserving.

---

## 10. Performance Impact Assessment

### 10.1 Current Query Count (100 projects/page)

| Source | Queries | Notes |
|--------|---------|-------|
| `$projectsQuery->count()` | 1 | Line 591 |
| Main `get()` with eager load | 1 (projects) + ~4–6 (relations) | user, user.parent, reports, accountDetails, budgets, statusHistory |
| `$resolver->resolve($project)` × 100 | 0 DB | Resolver is in-memory |
| `DPReport::approved()->where()->pluck()` × 100 | 100 | Line 621-622 |
| `DPAccountDetail::whereIn()->sum()` × 100 | 100 | Line 624-625 |
| Filter options | 1 (or cache hit) | Line 561 |
| **Total** | **~207–209** (or ~300 with relation count) | Depends on relation query counting |

The roadmap’s “~300” can be justified if relation queries are counted separately. In any case, the dominant cost is **~200 expense queries**, which the proposed fix removes.

### 10.2 Expected Query Count (After Implementation)

| Source | Queries |
|--------|---------|
| Full dataset `get()` with eager load | ~6 |
| `resolveCollection` | 0 |
| `paginate()` (count + select) | 2 |
| Filter options | 1 |
| **Total** | **~9** |

### 10.3 Roadmap Claim

| Claim | Assessment |
|-------|------------|
| ~300 → ~5–10 queries | ✅ **Realistic** — ~9 is within range |
| 50–80% faster page loads | ✅ **Plausible** — removal of ~200 queries will have a large impact |

---

## 11. Additional Risks Discovered

### 11.1 `count()` Query

- **Current:** Line 591 runs `$projectsQuery->count()` before `skip`/`take`
- **After full-dataset flow:** `$fullDataset->count()` replaces this; no separate count query for totals
- **Paginator:** Runs its own count; no conflict

### 11.2 budget_utilization Sort

- **Current:** Post-fetch sort on the current page only
- **After:** Same logic on `$projects->getCollection()`; applies only to the current page
- **Impact:** No semantic change

### 11.3 reports_count and approved_reports_count

- **Current:** `$project->reports_count`, `$project->approved_reports_count` set in map (lines 645-646)
- **Proposed flow:** Must be set when attaching from `resolvedFinancials` or computed from `$project->reports`
- **Note:** `resolveCollection` does not return report counts; they must be derived from `$project->reports` when attaching to page items

### 11.4 Health Indicator Logic

- **Coordinator:** 4 levels — good, moderate (≥50%), warning (≥75%), critical (≥90%) (lines 632-638)
- **Provincial:** 3 levels — good, warning (>75%), critical (>90%) (lines 721-726)
- **Mismatch:** Coordinator has “moderate” (50–75%); Provincial does not. Roadmap Phase 8 mentions documenting the column choice; this level difference should also be clarified.

### 11.5 Filter Cache Callback

- **Current:** `Cache::remember` callback runs 5–6 queries (provinces, centers, users, provincials, projectTypes, statuses) on cache miss
- **Scope:** Separate from project list N+1; roadmap does not propose caching changes until Phase 7
- **Risk:** Low

### 11.6 Missing Eager Load

- **statusHistory:** Only `withMax('statusHistory', 'created_at')` is used
- **View:** Uses `$project->status_history_max_created_at` — provided by `withMax`
- **No N+1** for status history

---

## 12. Final Verdict

### Summary

| Phase | Feasibility |
|-------|-------------|
| Phase 1 — Pagination | ✅ Feasible |
| Phase 2 — Resolver + Expense N+1 | ✅ Feasible |
| Phase 3 — Query Layer | ✅ Feasible |
| Phase 4 — Grand Totals | ✅ Feasible |
| Phase 5 — Status Distribution | ✅ Feasible |
| Phase 6 — Dataset Reuse | ✅ Feasible |
| Phase 7 — Filter Improvements | ✅ Feasible |
| Phase 8 — UI Polish | ✅ Feasible |
| Phase 9 — Cache Documentation | ✅ Feasible |

### Findings

1. **Eager loading:** `reports.accountDetails` is already loaded; in-memory expense calculation is viable.
2. **Provincial pattern:** Proven pattern exists for full-dataset flow, resolveCollection, and expense calculation.
3. **Services:** ProjectQueryService, ProjectFinancialResolver, TableFormatter, DatasetCacheService, ProjectAccessService exist and match roadmap assumptions.
4. **Cloning:** Eloquent Builder cloning is supported and used in Provincial.
5. **Performance:** Query reduction from ~200+ to ~9 is realistic; the roadmap’s estimates are credible.

### Recommendation

**Verdict: Fully Feasible**

The roadmap is technically feasible as written. No architecture changes are required. Implementers should:

1. Ensure `approved_reports_count` and `reports_count` are set when attaching metrics to page items.
2. Confirm whether to keep the Coordinator “moderate” health band or align with Provincial.
3. Use `TableFormatter::resolvePerPage($request)` to avoid arbitrary `per_page` values.

---

**Audit Complete.** No code modifications made; analysis and documentation only.
