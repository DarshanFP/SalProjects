# Phase 4 — Shared Dataset Implementation

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard Shared Dataset Optimization  
**Reference:** Phase4_SharedDataset_Feasibility_Audit.md  

---

## 1. Phase Overview

Phase 4 implements a **dual dataset architecture** for the Provincial dashboard:

1. **teamProjects** — All project statuses, FY-filtered, from DatasetCacheService. Used by team widgets.
2. **projects** — Approved only, with optional center/role/project_type filters. Used by Budget Summary.

This eliminates duplicate project queries, ensures resolver compatibility (including budgets eager load), and enforces immutable dataset usage. Phase 3.2/3.3 shared dataset and cache integration remain intact.

---

## 2. Controller Changes

### 2.1 Dual Dataset Architecture

| Dataset | Source | Scope | Used By |
|---------|--------|-------|---------|
| `teamProjects` | `DatasetCacheService::getProvincialDataset()` | All statuses, FY | Team Performance, Chart, Center, Enhanced Budget, Center Comparison |
| `projects` | `ProjectQueryService::forProvincial()->approved()->...` | Approved + filters | Budget Summary |

### 2.2 Code Changes in `provincialDashboard()`

**Variable rename:** `$teamProjectsInFy` → `$teamProjects`

**Projects query:** Added `budgets` to eager load:
```php
$projects = $projectsQuery->with(['user', 'reports.accountDetails', 'budgets'])->get();
```

**Team dataset:** Renamed and clarified:
```php
$teamProjects = DatasetCacheService::getProvincialDataset($provincial, $fy);
```

**Widget calls:** All five team widget methods now receive `$teamProjects`:
- `calculateTeamPerformanceMetrics($provincial, $fy, $teamProjects)`
- `prepareChartDataForTeamPerformance($provincial, $fy, $teamProjects)`
- `calculateCenterPerformance($provincial, $fy, $teamProjects)`
- `calculateEnhancedBudgetData($provincial, $fy, $teamProjects)`
- `prepareCenterComparisonData($provincial, $fy, $teamProjects)`

---

## 3. Dataset Architecture

### 3.1 Data Flow

```
DatasetCacheService::getProvincialDataset()
   ↓
teamProjects (all statuses, FY)
   ├─ Team Performance Metrics
   ├─ Chart Data
   ├─ Center Performance
   ├─ Enhanced Budget Data
   └─ Center Comparison

ProjectQueryService::forProvincial()->approved() + filters
   ↓
projects (approved + center/role/type filters)
   └─ Budget Summary
```

### 3.2 Eager Loads

| Dataset | Relations |
|---------|-----------|
| teamProjects | user, reports.accountDetails, budgets |
| projects | user, reports.accountDetails, budgets |

---

## 4. Widget Method Refactors

### 4.1 Signatures (Unchanged for Backward Compatibility)

```php
function calculateTeamPerformanceMetrics($provincial, string $fy, $teamProjects = null)
function prepareChartDataForTeamPerformance($provincial, string $fy, $teamProjects = null)
function calculateCenterPerformance($provincial, string $fy, $teamProjects = null)
function calculateEnhancedBudgetData($provincial, string $fy, $teamProjects = null)
function prepareCenterComparisonData($provincial, string $fy, $teamProjects = null)
```

When `$teamProjects === null`, each method falls back to its internal project query.

### 4.2 PHPDoc Updates

All five methods now document:
- `@param \Illuminate\Support\Collection|null $teamProjects Immutable shared dataset (all statuses). Must not be mutated.`

### 4.3 Internal Project Queries

When `$teamProjects` is passed:
- No `Project::` queries are executed in any widget method.
- DPReport queries remain (team reports, center reports, expense trends) — unchanged per requirements.

---

## 5. Resolver Compatibility

### 5.1 Required Relations

| Relation | PhaseBasedBudgetStrategy | DirectMappedIndividualBudgetStrategy |
|----------|--------------------------|--------------------------------------|
| user | ✓ (for center/name) | ✓ |
| reports.accountDetails | ✓ | ✓ |
| budgets | ✓ (now eager-loaded) | N/A (type-specific relations) |

### 5.2 Changes

- **DatasetCacheService:** Added `budgets` to eager load.
- **projects query:** Added `budgets` to eager load.

This prevents N+1 from `PhaseBasedBudgetStrategy::resolve()` which calls `$project->loadMissing('budgets')` when budgets are not loaded.

---

## 6. Immutable Dataset Safeguard

### 6.1 Rule

Shared datasets must not be mutated. Widget methods may only use:
- `filter()` — returns new collection
- `map()` — returns new collection
- `groupBy()` — returns new collection
- `where()` / `whereIn()` — returns new collection

Prohibited:
- `transform()` — mutates in place
- `forget()` — mutates
- `push()` — mutates

### 6.2 Audit Result

All five widget methods use only non-mutating operations on `$teamProjects`. No `transform`, `forget`, or `push` applied to the shared dataset.

### 6.3 PHPDoc

Each method documents that `$teamProjects` is immutable and must not be mutated.

---

## 7. Query Reduction Analysis

### 7.1 Before Phase 4 (Post Phase 3.3)

- 1 cached team dataset (DatasetCacheService)
- 1 approved filtered query (projects)
- Widget methods received shared dataset — no internal project queries when provided

### 7.2 After Phase 4

| Query | Count | Purpose |
|-------|-------|---------|
| teamProjects | 1 (cached) | DatasetCacheService |
| projects | 1 | Budget Summary (approved + filters) |
| projectTypes, userIdsWithProjectsInFy | From baseProjectsQuery (clones) | Filter dropdowns |
| DPReport | Unchanged | Team reports, center reports, expense trends |

**Result:** No additional project queries. Duplicate project fetches eliminated. Only two project datasets in controller: `teamProjects` and `projects`.

---

## 8. Performance Impact

### 8.1 Execution Flow

1. **1 cached dataset fetch** — `DatasetCacheService::getProvincialDataset()` (cache hit) or DB query (miss)
2. **1 approved filtered query** — `$projects` for Budget Summary
3. **No additional project queries** in widget methods when dataset is provided

### 8.2 Memory

- teamProjects: shared across 5 widget methods (no duplication)
- projects: single collection for Budget Summary
- budgets eager load: increases payload slightly but prevents N+1 (net positive for performance)

---

## 9. Files Modified

| File | Changes |
|------|---------|
| `app/Services/DatasetCacheService.php` | Added `budgets` to eager load |
| `app/Http/Controllers/ProvincialController.php` | Added `budgets` to projects query; renamed `teamProjectsInFy` → `teamProjects`; PHPDoc for widget methods |

---

## 10. Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | teamProjects from DatasetCacheService (all statuses) | ✓ |
| 2 | projects = approved + filters (Budget Summary only) | ✓ |
| 3 | Five widget methods receive teamProjects | ✓ |
| 4 | Backward compatibility: $teamProjects = null fallback | ✓ |
| 5 | budgets in eager load (DatasetCacheService + projects) | ✓ |
| 6 | PHPDoc immutability on widget methods | ✓ |
| 7 | No duplicate Project:: queries when dataset passed | ✓ |
| 8 | Only two project datasets: teamProjects, projects | ✓ |
| 9 | DPReport queries unchanged | ✓ |
| 10 | DatasetCacheService integration intact | ✓ |
