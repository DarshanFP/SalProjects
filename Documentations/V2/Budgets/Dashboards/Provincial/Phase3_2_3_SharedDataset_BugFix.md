# Phase 3.2.3 — Shared Dataset $accessibleUserIds Bug Fix

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard Shared Dataset Refactor  
**Bug:** Undefined variable `$accessibleUserIds` when optional `$teamProjects` dataset is passed

---

## Summary

During the Provincial dashboard shared-dataset refactor, several widget methods were modified to accept an optional pre-fetched `$teamProjects` parameter to avoid redundant queries. In some methods, `$accessibleUserIds` was only initialized inside `if ($teamProjects === null)`, but was referenced later regardless of the branch, causing "Undefined variable $accessibleUserIds" when the shared dataset was passed.

**Fix:** Move `$accessibleUserIds = $this->getAccessibleUserIds($provincial)` outside the conditional block so it is always defined before any use.

---

## 1. Methods Audited

All methods in `ProvincialController.php` that accept optional `$teamProjects` and reference `$accessibleUserIds`:

| Method | Line | Has `$teamProjects = null` | Uses `$accessibleUserIds` | Status |
|--------|------|---------------------------|---------------------------|--------|
| `calculateTeamPerformanceMetrics` | 2208 | ✓ | ✓ (line 2223) | **FIXED** |
| `prepareChartDataForTeamPerformance` | 2289 | ✓ | ✓ (line 2300) | **FIXED** |
| `calculateCenterPerformance` | 2352 | ✓ | ✓ (inside `if` only) | Already correct |
| `calculateEnhancedBudgetData` | 2417 | ✓ | ✓ (line 2556, expense trends loop) | **FIXED** |
| `prepareCenterComparisonData` | 2598 | ✓ | No direct use (delegates to `calculateCenterPerformance`) | No fix needed |

---

## 2. Methods Fixed

### 2.1 `calculateTeamPerformanceMetrics` (lines 2208–2285)

**Before:**
```php
if ($teamProjects === null) {
    $accessibleUserIds = $this->getAccessibleUserIds($provincial);
    $teamProjects = Project::accessibleByUserIds($accessibleUserIds)
        ->inFinancialYear($fy)
        ...
}
// BUG: $accessibleUserIds undefined when $teamProjects passed
$teamReports = DPReport::accessibleByUserIds($accessibleUserIds)->get();
```

**After:**
```php
$accessibleUserIds = $this->getAccessibleUserIds($provincial);

if ($teamProjects === null) {
    $teamProjects = Project::accessibleByUserIds($accessibleUserIds)
        ->inFinancialYear($fy)
        ...
}

$teamReports = DPReport::accessibleByUserIds($accessibleUserIds)->get();
```

---

### 2.2 `prepareChartDataForTeamPerformance` (lines 2289–2348)

**Before:**
```php
if ($teamProjects === null) {
    $accessibleUserIds = $this->getAccessibleUserIds($provincial);
    $teamProjects = Project::accessibleByUserIds($accessibleUserIds)->inFinancialYear($fy)->get();
}
// BUG: $accessibleUserIds undefined when $teamProjects passed
$teamReports = DPReport::accessibleByUserIds($accessibleUserIds)->get();
```

**After:**
```php
$accessibleUserIds = $this->getAccessibleUserIds($provincial);

if ($teamProjects === null) {
    $teamProjects = Project::accessibleByUserIds($accessibleUserIds)->inFinancialYear($fy)->get();
}

$teamReports = DPReport::accessibleByUserIds($accessibleUserIds)->get();
```

---

### 2.3 `calculateEnhancedBudgetData` (lines 2417–2594)

**Before:**
```php
if ($teamProjects === null) {
    $accessibleUserIds = $this->getAccessibleUserIds($provincial);
    $teamProjects = Project::accessibleByUserIds($accessibleUserIds)
        ->inFinancialYear($fy)
        ...
}
// ... logic uses $teamProjects ...
// Inside expense-trends loop (line ~2556):
$accessibleUserIds = $this->getAccessibleUserIds($provincial);  // Redundant per iteration
$monthReports = DPReport::accessibleByUserIds($accessibleUserIds)->...
```

**After:**
```php
$accessibleUserIds = $this->getAccessibleUserIds($provincial);

if ($teamProjects === null) {
    $teamProjects = Project::accessibleByUserIds($accessibleUserIds)
        ->inFinancialYear($fy)
        ...
}
// ... logic uses $teamProjects ...
// Inside expense-trends loop: removed redundant inner assignment
$monthReports = DPReport::accessibleByUserIds($accessibleUserIds)->...
```

---

### 2.4 `calculateCenterPerformance` (lines 2352–2412)

**Status:** Already correct. `$accessibleUserIds` was initialized before the `if` block at line 2354.

### 2.5 `prepareCenterComparisonData` (lines 2598–2610)

**Status:** No fix needed. Delegates to `calculateCenterPerformance`, which has correct initialization.

---

## 3. Line Numbers

| Method | Initialization Line (after fix) | Conditional Block | Downstream Use Lines |
|--------|--------------------------------|-------------------|----------------------|
| `calculateTeamPerformanceMetrics` | 2213 | 2215–2220 | 2223 |
| `prepareChartDataForTeamPerformance` | 2293 | 2295–2297 | 2300 |
| `calculateCenterPerformance` | 2354 | 2356–2361 | (inside if only; uses for $teamProjects fetch) |
| `calculateEnhancedBudgetData` | 2423 | 2425–2430 | 2556 (inside expense-trends loop) |

---

## 4. Verification Results

| Check | Result |
|-------|--------|
| `php -l app/Http/Controllers/ProvincialController.php` | No syntax errors |
| All `$accessibleUserIds` references have prior definition | ✓ |
| Pattern applied consistently | ✓ |

---

## 5. Root Cause

The shared-dataset optimization introduced an optional `$teamProjects` parameter. When `$teamProjects` is provided by the caller, the `if ($teamProjects === null)` block is skipped. Methods that both:

1. Set `$accessibleUserIds` only inside that block, and  
2. Use `$accessibleUserIds` after the block  

produced an undefined variable when the shared dataset path was used.

---

## 6. Files Modified

- `app/Http/Controllers/ProvincialController.php` — 3 methods updated
