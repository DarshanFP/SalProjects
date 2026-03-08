# Phase-1 FY Propagation Verification

**Date:** 2026-03-05  
**Scope:** Provincial Dashboard — `ProvincialController.php`  
**Goal:** Confirm Financial Year ($fy) is consistently propagated through controller and widget methods.  
**Mode:** Verification only (no code modification)

---

## Controller FY Initialization

### Entry Point: `provincialDashboard(Request $request)`

| Check | Status | Location |
|-------|--------|----------|
| Method exists | ✅ | Line 50 |
| FY extraction from request | ✅ | Line 55 |
| Default fallback | ✅ | `FinancialYearHelper::currentFY()` |

**Code:**
```php
$fy = $request->input('fy', FinancialYearHelper::currentFY());
```

**Import:** `App\Support\FinancialYearHelper` (Line 27)

---

## Widget Method FY Propagation

All five widget methods are invoked from `provincialDashboard` with `$fy`:

| Method | Invocation | Line | Status |
|--------|------------|------|--------|
| `calculateTeamPerformanceMetrics` | `$this->calculateTeamPerformanceMetrics($provincial, $fy)` | 226 | ✅ |
| `prepareChartDataForTeamPerformance` | `$this->prepareChartDataForTeamPerformance($provincial, $fy)` | 227 | ✅ |
| `calculateCenterPerformance` | `$this->calculateCenterPerformance($provincial, $fy)` | 228 | ✅ |
| `calculateEnhancedBudgetData` | `$this->calculateEnhancedBudgetData($provincial, $fy)` | 236 | ✅ |
| `prepareCenterComparisonData` | `$this->prepareCenterComparisonData($provincial, $fy)` | 239 | ✅ |

---

## Query FY Filtering

### Entry Point (provincialDashboard)

| Query Purpose | Model | Scope | Line | Status |
|---------------|-------|-------|------|--------|
| Society breakdown projects | Project | `->inFinancialYear($fy)` | 71–73 | ✅ |
| Approved projects (main list) | Project | `->inFinancialYear($fy)` | 129–131 | ✅ |

### Widget Methods — Project Queries

| Method | Query Purpose | Scope | Line | Status |
|--------|---------------|-------|------|--------|
| `calculateTeamPerformanceMetrics` | Team projects (all statuses) | `->inFinancialYear($fy)` | 2167–2168 | ✅ |
| `prepareChartDataForTeamPerformance` | Team projects | `->inFinancialYear($fy)` | 2246 | ✅ |
| `calculateCenterPerformance` | Center projects (per center loop) | `->inFinancialYear($fy)` | 2320 | ✅ |
| `calculateEnhancedBudgetData` | Approved projects | `->inFinancialYear($fy)` | 2376–2378 | ✅ |
| `calculateEnhancedBudgetData` | Pending projects | `->inFinancialYear($fy)` | 2383–2385 | ✅ |
| `prepareCenterComparisonData` | No direct queries | Delegates to `calculateCenterPerformance` | 2555 | ✅ |

**Note:** `prepareCenterComparisonData` delegates to `calculateCenterPerformance`, which applies `inFinancialYear($fy)` to its Project queries.

---

## Method Signatures

All five widget methods declare `string $fy`:

| Method | Signature | Line | Status |
|--------|-----------|------|--------|
| `calculateTeamPerformanceMetrics` | `($provincial, string $fy)` | 2158 | ✅ |
| `prepareChartDataForTeamPerformance` | `($provincial, string $fy)` | 2238 | ✅ |
| `calculateCenterPerformance` | `($provincial, string $fy)` | 2300 | ✅ |
| `calculateEnhancedBudgetData` | `($provincial, string $fy)` | 2367 | ✅ |
| `prepareCenterComparisonData` | `($provincial, string $fy)` | 2552 | ✅ |

---

## Verification Result

| Criterion | Result |
|-----------|--------|
| Controller FY initialization | **PASS** |
| Widget method FY propagation | **PASS** |
| Project query FY filtering (in-scope methods) | **PASS** |
| Method signatures | **PASS** |

### Overall: **PASS** ✅

Financial Year is consistently propagated through the Provincial dashboard controller and the five specified widget methods. All Project queries in those methods use `->inFinancialYear($fy)`.

---

## Ancillary Observations

- **Filter options (projectTypes):** The `projectTypes` query at 165–168 (`Project::accessibleByUserIds(...)->approved()->distinct()->pluck('project_type')`) does not apply `inFinancialYear($fy)`. This is outside the Phase-1 scope (five widget methods) but may be relevant for a future FY consistency pass on filter options.
