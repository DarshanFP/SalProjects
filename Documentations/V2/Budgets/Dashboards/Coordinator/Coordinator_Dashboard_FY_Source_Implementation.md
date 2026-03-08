# Coordinator Dashboard — FY Source Implementation

**Date:** 2026-03-07  
**Phase:** X — Coordinator Dashboard FY Source Alignment  
**Status:** Implemented  
**URL:** `http://localhost:8000/coordinator/dashboard`

---

## 1. Problem Summary

The Coordinator Dashboard FY dropdown used a **static 10-year window** (`FinancialYearHelper::listAvailableFY()`) instead of project-derived financial years. This caused:

- **Architectural inconsistency** — Other coordinator pages (`/coordinator/projects-list`, `/coordinator/approved-projects`) use project-derived FY lists via `listAvailableFYFromProjects()`.
- **Fixed dropdown content** — Dropdown always showed current FY plus 9 past years (e.g. 2025-26 down to 2016-17), regardless of whether projects existed in those years.
- **Mismatch with data scope** — Coordinator budget overview filters by FY, but the FY options were not derived from the same approved project set.

---

## 2. Previous FY Architecture

| Component | Implementation |
|-----------|----------------|
| **Controller** | `$availableFY = FinancialYearHelper::listAvailableFY()` |
| **Helper method** | `listAvailableFY(int $yearsBack = 10)` — current FY + 9 past years; no DB |
| **Data source** | System date only (formula-based) |
| **Blade** | `@foreach($availableFY ?? [] as $year)` |

---

## 3. Root Cause (Static FY Helper)

The dashboard passed `listAvailableFY()` into `$availableFY`. That method:

- Uses `FinancialYearHelper::currentFY()` and subtracts years in a loop
- Returns a fixed 10-element array (e.g. `["2025-26", "2024-25", …, "2016-17"]`)
- Does **not** query projects or `commencement_month_year`

---

## 4. Implemented Fix

### 4.1 Change

Replaced static FY generation with project-derived FY list using the built-in fallback:

```php
$availableFY = FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), true);
```

### 4.2 Behavior

- **Query:** `Project::approved()` — all approved projects (coordinator scope)
- **Logic:** FYs derived from `commencement_month_year` via `FinancialYearHelper::fromDate()`
- **Fallback:** When no project dates exist, `listAvailableFYFromProjects(..., true)` returns `listAvailableFY()` so the dropdown always has options
- **Output:** Sorted descending (newest first)

---

## 5. Controller Code Changes

### 5.1 File

`app/Http/Controllers/CoordinatorController.php`

### 5.2 Method

`buildCoordinatorDashboardPayload()`

### 5.3 Diff

```diff
-        $availableFY = FinancialYearHelper::listAvailableFY();
+        $availableFY = FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), true);

         return [
```

### 5.4 Imports

No new imports required. Existing imports:

- `use App\Models\OldProjects\Project;`
- `use App\Support\FinancialYearHelper;`

---

## 6. Consistency Verification With Other Coordinator Pages

| Page | FY Variable | FY Source | Match |
|------|-------------|-----------|-------|
| **Project List** | `$fyList` | `listAvailableFYFromProjects(Project::approved(), false)` + fallback | ✓ Same query scope |
| **Approved Projects** | `$fyList` | `listAvailableFYFromProjects(Project::approved(), false)` + fallback | ✓ Same query scope |
| **Dashboard** | `$availableFY` | `listAvailableFYFromProjects(Project::approved(), true)` | ✓ Same query scope |

All three now use `Project::approved()` as the base query. The dashboard uses the built-in fallback (`true`); project list and approved projects use a manual fallback to `[currentFY()]`.

---

## 7. Dashboard Behavior After Fix

| Scenario | Result |
|----------|--------|
| Projects in 2025-26, 2024-25, 2023-24 | Dropdown shows those three FYs only |
| No approved projects | Dropdown shows static 10-year list (fallback) |
| FY filter selection | Request `?fy=2024-25` still updates dashboard data |
| Selected FY | Persists via query string and form submit |
| Dashboard cache | Unchanged; cache key still based on `$fy` and filters |
| Dataset pipeline | Unchanged; `DatasetCacheService::getCoordinatorDataset()` unaffected |

---

## 8. Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/CoordinatorController.php` | Replaced `listAvailableFY()` with `listAvailableFYFromProjects(Project::approved(), true)` in `buildCoordinatorDashboardPayload()` |

### Not Modified (Per Safety Rules)

- `resources/views/coordinator/widgets/system-budget-overview.blade.php` — Already uses `$availableFY`
- `app/Services/DatasetCacheService.php` — No FY generation; unchanged
- `app/Support/FinancialYearHelper.php` — No changes
- Caching keys — Unchanged
- Dashboard dataset pipeline — Unchanged

---

## 9. Safety Checklist

- [x] Only FY generation logic modified
- [x] Dashboard dataset pipeline not altered
- [x] DatasetCacheService not modified
- [x] Blade templates not modified
- [x] Existing caching keys unchanged
- [x] `invalidateDashboardCache()` left as-is (still uses `listAvailableFY()` for cache key enumeration)
