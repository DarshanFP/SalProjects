# Phase 3.3 — Dataset Cache Layer Feasibility Audit

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard Dataset Cache  
**Goal:** Determine whether dashboard project datasets can be safely cached without introducing stale data or architectural risks.

---

## Executive Summary

| Verdict | **SAFE WITH CONDITIONS** |
|---------|--------------------------|
| Cache key | Must use `provincial_id` (not `province_id`) and exclude General users with session filters |
| Invalidations | Required on project/report approval, revert, update, and budget changes |
| Scope | Projects-only cache feasible; reports must be cached separately or refetched |

---

## Step 1 — Dataset Source Verification

### Primary Source

The Provincial dashboard uses **ProjectQueryService::forProvincial($provincial, $fy)** as the single project dataset source:

```php
// ProvincialController::provincialDashboard() line 130
$baseProjectsQuery = ProjectQueryService::forProvincial($provincial, $fy);
$teamProjectsInFy = $baseProjectsQuery->with(['user', 'reports.accountDetails'])->get();
```

### Widget Methods and Direct Queries

| Method | Uses shared `$teamProjects`? | Direct Project:: queries when passed? | Direct DPReport:: queries? |
|--------|------------------------------|---------------------------------------|----------------------------|
| `calculateTeamPerformanceMetrics` | ✓ | No | Yes — `DPReport::accessibleByUserIds($accessibleUserIds)->get()` |
| `prepareChartDataForTeamPerformance` | ✓ | No | Yes — same |
| `calculateCenterPerformance` | ✓ | No | Yes — `DPReport::whereIn('user_id', $centerUsers)->get()` |
| `calculateEnhancedBudgetData` | ✓ | No | Yes — `DPReport::accessibleByUserIds($accessibleUserIds)` in trends loop |
| `prepareCenterComparisonData` | ✓ (via `calculateCenterPerformance`) | No | Via delegate |

**Finding:** Project dataset is centralized via ProjectQueryService. Widget methods do **not** issue direct `Project::` queries when `$teamProjects` is passed. However, **all** widget methods still issue `DPReport::` queries because reports are fetched separately (by `accessibleUserIds` or center users). Caching only the project dataset would eliminate project queries but reports would still be fetched on every request.

### Other Dashboard Data Sources (Not Cache Candidates)

- **Society breakdown** (lines 72–98): `Project::where('province_id', ...)` — separate scope (province-wide, not accessible-user scoped)
- **Pending approvals**: `getPendingApprovalsForDashboard()` — project/report status filters
- **Approval queue**: `getApprovalQueueForDashboard()`
- **Team activities**: `ActivityHistoryService::getForProvincial()`
- **FY list**: `FinancialYearHelper::listAvailableFYFromProjects()`

---

## Step 2 — Dataset Determinism

### Dependencies

| Factor | Provincial role | General role |
|--------|-----------------|--------------|
| `province_id` | Indirect (via user hierarchy) | Used for managed provinces |
| `parent_id` | Direct — `getAccessibleUserIds` uses `User::where('parent_id', $provincial->id)` | N/A |
| **Session** | No | **Yes** — `session('province_filter_ids')`, `session('province_filter_all')` |
| Request params | No (widget dataset is unfiltered) | No |
| FY | Yes | Yes |

**Finding:** The dataset is **not fully deterministic** for **General** users. `ProjectAccessService::getAccessibleUserIds()` (lines 44–56) uses session when `$provincial->role === 'general'`:

```php
$filteredProvinceIds = session('province_filter_ids', []);
$filterAll = session('province_filter_all', true);
```

For **Provincial** users, the dataset depends only on `$provincial->id` (parent hierarchy) and `$fy`.

---

## Step 3 — Relation Load and Memory Impact

### Relations Loaded

| Relation | Loaded in dataset | Used by widgets |
|----------|-------------------|-----------------|
| `user` | ✓ | Center, name, role |
| `reports` | ✓ | Expense totals, approval status |
| `reports.accountDetails` | ✓ | `total_expenses` per report |

### Estimated Memory Footprint

Assumptions: ~3 KB/project (model + user), ~2 KB/report, ~0.5 KB/accountDetail; avg 4 reports/project, 1 accountDetail/report.

| Province scale | Projects | Est. memory (projects + relations) |
|----------------|----------|-----------------------------------|
| 1,000 projects | 1,000 | ~15–25 MB |
| 5,000 projects | 5,000 | ~75–125 MB |
| 10,000 projects | 10,000 | ~150–250 MB |

**Note:** Provincial dashboards are province-scoped. Large provinces (5K+ projects) would have sizable cache entries. Consider TTL and eviction if memory is constrained.

---

## Step 4 — Cache Invalidation Events

### Events That Must Invalidate Dataset Cache

| Event | Location | Impact |
|-------|----------|--------|
| Project approval | `ProjectStatusService::approve()` | Status, `commencement_month_year`, `amount_sanctioned`, `opening_balance` |
| Project revert | `CoordinatorController::revertToProvincial()`, etc. | Status, financial fields |
| Project reject | `ProjectStatusService::reject()` | Status |
| Project update | `ProjectController::update()`, `GeneralInfoController`, `BudgetController` | Any project fields |
| Report approval | `CoordinatorController::approveReport()` | Report status; affects expense totals |
| Report revert | `CoordinatorController::revertReport()` | Report status |
| Report submission/forward | `ProvincialController::forwardReports()` | Report status |
| Budget sync | `BudgetSyncService::syncBeforeApproval()` | `opening_balance`, etc. |
| Project create | Project store flow | New project in scope |
| User hierarchy change | User `parent_id` / province changes | Changes `accessibleUserIds` |

### Current State

- **CoordinatorController** calls `invalidateDashboardCache()` on approval/revert/reject.
- **ProvincialController** has **no** dashboard cache or invalidation logic.
- Any Phase 3.3 cache implementation must wire invalidation into Provincial flows and shared services (ProjectStatusService, ReportStatusService, etc.).

---

## Step 5 — Query Index Support

### FY Filtering

- **Scope:** `Project::scopeInFinancialYear($query, $fy)` (Project.php ~400)
- **Column:** `commencement_month_year`
- **Logic:** `whereBetween('commencement_month_year', [$start, $end])`

### Index Existence

Migration `2026_03_05_071759_add_project_query_indexes.php` adds:

```php
$table->index('commencement_month_year');
```

**Finding:** FY filtering uses an indexed column. No index changes required for caching.

---

## Step 6 — Cache Key Design Review

### Proposed Key

```
provincial_dataset_{provinceId}_{fy}
```

### Issues

1. **province_id is insufficient:** Two provincials in the same province have different `accessibleUserIds` (based on `parent_id`). The dataset is per-provincial, not per-province.

2. **General users:** Scope depends on session. A key based only on province/FY would be wrong when province filter changes.

### Recommended Key Structure

| User role | Cache key |
|-----------|-----------|
| Provincial | `provincial_dataset_{provincialId}_{fy}` |
| General | `provincial_dataset_general_{generalId}_{fy}_{sessionHash}` — or **exclude from cache** when session filter is active |

**Uniqueness:** For provincial users, `{provincialId}_{fy}` uniquely identifies the project dataset (owner/in-charge in `accessibleUserIds` for that provincial, in that FY).

---

## Step 7 — Feasibility Result

### Verdict: **SAFE WITH CONDITIONS**

Caching the Provincial dashboard project dataset is feasible if the following conditions are met.

### Conditions

1. **Cache key:** Use `provincial_dataset_{provincialId}_{fy}` (not `provinceId`). For General users with active province filters, either:
   - include a session hash in the key, or
   - skip caching when session filter is applied.

2. **Invalidation:** Implement cache invalidation for:
   - Project approval, revert, reject, update
   - Report approval, revert, forward
   - Budget sync
   - User hierarchy changes that affect `accessibleUserIds`

3. **Reports:** Widget methods still query `DPReport` separately. Either:
   - cache projects and reports together (larger payload, shared invalidation), or
   - cache projects only and accept report queries on each request, or
   - refactor to cache computed widget outputs (metrics, chart data) instead of raw datasets.

4. **TTL:** Use a short TTL (e.g. 5–15 minutes) as a safety net in addition to event-based invalidation.

5. **Provincial-only or session-aware:** Document that caching is primarily for Provincial role; General role requires session-aware key or no cache when filters are applied.

---

## Step 8 — Implementation Readiness

### Dataset Query Analysis

| Aspect | Status |
|--------|--------|
| Single project source | ✓ ProjectQueryService::forProvincial |
| No stray Project:: in widgets when dataset passed | ✓ |
| DPReport queries in widgets | ⚠ Still present; decide report caching strategy |

### Relation Load Evaluation

| Aspect | Status |
|--------|--------|
| Eager load: user, reports.accountDetails | ✓ |
| Memory at 1K projects | ~15–25 MB — acceptable |
| Memory at 10K projects | ~150–250 MB — monitor |

### Cache Invalidation Requirements

| Requirement | Status |
|-------------|--------|
| List events defined | ✓ |
| ProvincialController invalidation | ❌ Not implemented (no cache yet) |
| Hook points identified | ✓ ProjectStatusService, ReportStatusService, Controllers |

### Implementation Checklist

- [ ] Add cache layer around `ProjectQueryService::forProvincial(...)->with(...)->get()`
- [ ] Use key `provincial_dataset_{provincialId}_{fy}` for Provincial role
- [ ] For General: session-aware key or no cache when province filter active
- [ ] Add invalidation in ProvincialController, CoordinatorController, ProjectController, ReportStatusService (or equivalent)
- [ ] Consider cache tags if driver supports (e.g. Redis) for bulk invalidation by province/FY
- [ ] Set TTL (e.g. 10–15 min)
- [ ] Decide report caching strategy and apply consistently

---

## Appendix: Widget Method Query Summary

| Method | Project source | Report source |
|--------|----------------|---------------|
| `calculateTeamPerformanceMetrics` | `$teamProjects` (shared) | `DPReport::accessibleByUserIds()` |
| `prepareChartDataForTeamPerformance` | `$teamProjects` (shared) | `DPReport::accessibleByUserIds()` |
| `calculateCenterPerformance` | `$teamProjects` (shared) | `DPReport::whereIn('user_id', $centerUsers)` |
| `calculateEnhancedBudgetData` | `$teamProjects` (shared) | `DPReport::accessibleByUserIds()` (trends) |
| `prepareCenterComparisonData` | Via `calculateCenterPerformance` | Via delegate |
