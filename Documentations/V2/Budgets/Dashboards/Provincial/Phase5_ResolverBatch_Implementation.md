# Phase 5 — Resolver Batch Optimization Implementation

**Date:** 2026-03-05  
**Phase:** Phase 5 — Resolver Batch Optimization  
**Reference:** Phase5_ResolverBatch_Feasibility_Audit.md, Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## 1. Phase Overview

Phase 5 implements **resolver batch optimization** for the Provincial Dashboard. Previously, `ProjectFinancialResolver::resolve()` was invoked repeatedly inside loops across multiple widget methods, causing the same project to be resolved 4–6 times per request. This phase resolves all projects once via `resolveCollection($teamProjects)`, stores the results in a financial map, and passes that map to all widget methods. Resolver executions are reduced by ~75–83%; dashboard response time improves for large provinces.

---

## 2. Resolver Redundancy Problem

### Before

- `calculateTeamPerformanceMetrics`: resolved each approved project
- `prepareChartDataForTeamPerformance`: resolved each approved project again
- `calculateCenterPerformance`: resolved approved + pending per center
- `calculateEnhancedBudgetData`: resolved pending + approved
- `calculateBudgetSummariesFromProjects`: resolved each project in $projects
- `projectList`: resolved each project in fullDataset

The same project could be resolved multiple times across widgets. For 500 projects, ~2,000–3,000 resolver calls per request.

---

## 3. Batch Resolution Architecture

### Single Resolution Pass

```php
$teamProjects = DatasetCacheService::getProvincialDataset($provincial, $fy);
$resolvedFinancials = \App\Domain\Budget\ProjectFinancialResolver::resolveCollection($teamProjects);
```

### Map Structure

```php
$resolvedFinancials = [
    'DP-0041' => [
        'overall_project_budget' => 100000.00,
        'amount_forwarded' => 0.00,
        'local_contribution' => 5000.00,
        'amount_sanctioned' => 95000.00,
        'amount_requested' => 0.00,
        'opening_balance' => 100000.00,
    ],
    // ...
];
```

### Widget Lookup

```php
$financial = $resolvedFinancials[$project->project_id] ?? [];
$openingBalance = (float) ($financial['opening_balance'] ?? 0);
```

---

## 4. Controller Changes

### provincialDashboard

- After `$teamProjects = DatasetCacheService::getProvincialDataset(...)`:
  - Added: `$resolvedFinancials = \App\Domain\Budget\ProjectFinancialResolver::resolveCollection($teamProjects);`
- All widget calls now pass `$resolvedFinancials`:
  - `calculateBudgetSummariesFromProjects($projects, $request, $resolvedFinancials)`
  - `calculateTeamPerformanceMetrics($provincial, $fy, $teamProjects, $resolvedFinancials)`
  - `prepareChartDataForTeamPerformance($provincial, $fy, $teamProjects, $resolvedFinancials)`
  - `calculateCenterPerformance($provincial, $fy, $teamProjects, $resolvedFinancials)`
  - `calculateEnhancedBudgetData($provincial, $fy, $teamProjects, $resolvedFinancials)`
  - `prepareCenterComparisonData($provincial, $fy, $teamProjects, $resolvedFinancials)`

### projectList

- Replaced per-project resolver loop with:
  - `$resolvedFinancials = \App\Domain\Budget\ProjectFinancialResolver::resolveCollection($fullDataset);`
- Added `budgets` to eager load on fullDataset (resolver requirement)
- Grand totals and project mutation loop now use map lookup instead of resolver calls

---

## 5. Widget Method Signature Updates

| Method | New Signature |
|--------|---------------|
| calculateBudgetSummariesFromProjects | `($projects, $request, ?array $resolvedFinancials = null)` |
| calculateTeamPerformanceMetrics | `($provincial, $fy, $teamProjects = null, ?array $resolvedFinancials = null)` |
| prepareChartDataForTeamPerformance | `($provincial, $fy, $teamProjects = null, ?array $resolvedFinancials = null)` |
| calculateCenterPerformance | `($provincial, $fy, $teamProjects = null, ?array $resolvedFinancials = null)` |
| calculateEnhancedBudgetData | `($provincial, $fy, $teamProjects = null, ?array $resolvedFinancials = null)` |
| prepareCenterComparisonData | `($provincial, $fy, $teamProjects = null, ?array $resolvedFinancials = null)` |

When `$resolvedFinancials` is null, methods fall back to inline resolution for backward compatibility.

---

## 6. Resolver Call Replacement

### Pattern

**Before:**
```php
$resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
$resolvedFinancials = [];
foreach ($approvedProjects as $project) {
    $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
}
```

**After:**
```php
if ($resolvedFinancials === null) {
    $resolvedFinancials = [];
    foreach ($approvedProjects as $project) {
        $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
    }
}
// Use $resolvedFinancials[$project->project_id] for lookups
```

When the map is passed from the controller, the inline resolution block is skipped.

### Pending Project Handling

For pending projects (amount_requested):
```php
$fin = $resolvedFinancials[$p->project_id] ?? ($resolver ? $resolver->resolve($p) : []);
return (float) ($fin['amount_requested'] ?? 0);
```

---

## 7. Dataset Immutability Preservation

- **No project model mutation:** Financial data is stored in the external map only.
- **Forbidden:** `$project->financial = ...` or attaching resolver output to project models.
- **teamProjects** remains immutable; widgets read from the map.

---

## 8. Performance Impact Analysis

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| Resolver calls (100 projects) | ~400–600 | 100 | ~75–83% reduction |
| Resolver calls (500 projects) | ~2,000–3,000 | 500 | ~75–83% reduction |
| Resolver calls (2,000 projects) | ~8,000–12,000 | 2,000 | ~75–83% reduction |

- **provincialDashboard:** Single `resolveCollection($teamProjects)`; all widgets reuse the map.
- **projectList:** Single `resolveCollection($fullDataset)`; grand totals and paginated attach use the map.
- **Expected:** 15–30% dashboard response time improvement for provinces with 500+ projects.

---

## 9. Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/ProvincialController.php` | Added resolveCollection in provincialDashboard; pass map to all widgets; updated 6 method signatures; replaced resolver loops with map lookup; projectList uses resolveCollection |

---

## 10. Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | resolveCollection() called once per provincialDashboard request | ✓ |
| 2 | resolveCollection() called once per projectList request | ✓ |
| 3 | Widget methods receive resolvedFinancials map | ✓ |
| 4 | No project model mutation for financial data | ✓ |
| 5 | No resolver loops remain when map is passed | ✓ |
| 6 | Financial totals use map lookup | ✓ |
| 7 | Fallback to inline resolution when map is null | ✓ |
| 8 | Dataset immutability preserved | ✓ |
| 9 | projectList fullDataset includes budgets relation | ✓ |

---

## Summary

Phase 5 reduces resolver redundancy by resolving all projects once per request and reusing the financial map across widgets. Controller passes the map; widgets use map lookup with fallback. Dashboard behaviour is unchanged; performance improves for large provinces.
