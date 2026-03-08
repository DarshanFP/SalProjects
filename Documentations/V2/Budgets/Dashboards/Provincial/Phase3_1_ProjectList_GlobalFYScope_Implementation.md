# Phase 3.1 — Provincial Project Lists Global FY Filter

**Date:** 2026-03-05  
**Phase:** Provincial Project Lists Global FY Filter  
**Goal:** Ensure all project lists support FY filtering regardless of project status.

---

## Summary

Financial Year filtering has been implemented across all Provincial project listing pages. Both `/provincial/projects-list` (all statuses including `?status=*`) and `/provincial/approved-projects` now apply FY filtering based on project `commencement_month_year`. FY selectors are added to filter forms; pagination and query string persistence are preserved.

---

## Pages Affected

| Page | Route | Method | FY Applied |
|------|-------|--------|------------|
| All Team Projects | `/provincial/projects-list` | `projectList()` | ✓ |
| Filtered by status | `/provincial/projects-list?status=*` | `projectList()` | ✓ |
| Approved Projects | `/provincial/approved-projects` | `approvedProjects()` | ✓ |

---

## 1. Controller Changes

### 1.1 `projectList()`

**FY extraction (start of method):**
```php
$fy = $request->input('fy', FinancialYearHelper::currentFY());
```

**Base query modification:**
```php
$baseQuery = Project::accessibleByUserIds($accessibleUserIds)
    ->inFinancialYear($fy)
    ->when($request->filled('project_type'), ...)
    // ... existing filters
```

**FY list for dropdown:**
```php
$fyList = FinancialYearHelper::listAvailableFYFromProjects(
    Project::accessibleByUserIds($accessibleUserIds)->approved(),
    false
);
if (empty($fyList)) {
    $fyList = [FinancialYearHelper::currentFY()];
}
```

**View variables:** Added `fy`, `fyList` to `compact()`.

---

### 1.2 `approvedProjects()`

**FY extraction:**
```php
$fy = $request->input('fy', FinancialYearHelper::currentFY());
```

**Projects query modification:**
```php
$projectsQuery = Project::accessibleByUserIds($accessibleUserIds)
    ->approved()
    ->inFinancialYear($fy);
```

**FY list:** Same pattern as `projectList()` — `listAvailableFYFromProjects(..., false)` with fallback to `[currentFY()]`.

**View variables:** Added `fy`, `fyList` to `compact()`.

---

## 2. View Changes

### 2.1 `ProjectList.blade.php`

- **FY selector:** First column in filter form
- **Label:** Financial Year
- **Name:** `fy`
- **Options:** `@foreach($fyList ?? [] as $year)` with `FY {{ $year }}`
- **Form:** `method="GET"`, `action="{{ route('provincial.projects.list') }}"` — unchanged
- **Pagination:** `->withQueryString()` — preserves `fy` in page links

### 2.2 `approvedProjects.blade.php`

- **FY selector:** First column in filter form
- **Label:** Financial Year
- **Name:** `fy`
- **Options:** Same pattern as `ProjectList`
- **Form:** `method="GET"`, `action="{{ route('provincial.approved.projects') }}"` — unchanged
- **Minor:** Row uses `g-2 align-items-end`; `form-control` → `form-select` for consistency

---

## 3. Query String Persistence

| Aspect | projectList | approvedProjects |
|--------|-------------|------------------|
| Form method | GET | GET |
| Pagination | `->paginate()->withQueryString()` | N/A (no pagination) |
| Per-page form | `request()->except('per_page', 'page')` includes `fy` | — |
| URL format | `?fy=2026-27&center=...&status=...&page=2` | `?fy=2026-27&project_type=...&user_id=...` |

---

## 4. FY List Source

- **Source:** `FinancialYearHelper::listAvailableFYFromProjects(Project::accessibleByUserIds($accessibleUserIds)->approved(), false)`
- **Scope:** Approved projects only (Phase 2.1 data integrity)
- **Fallback:** `[FinancialYearHelper::currentFY()]` when no approved project dates exist
- **No fabricated FYs:** Static `listAvailableFY()` is not used

---

## 5. Filter Order of Operations

**projectList:**
1. `accessibleByUserIds`
2. `inFinancialYear($fy)`
3. `when(project_type)`, `when(user_id)`, `when(status)`, `when(center)`, `when(society_id)`
4. `get()` / `paginate()`

**approvedProjects:**
1. `accessibleByUserIds`
2. `approved`
3. `inFinancialYear($fy)`
4. Filters (place, user_id, project_type)
5. `get()`

---

## 6. Data Behavior

- **scopeInFinancialYear:** Excludes projects with null `commencement_month_year`
- **Effect:** Projects without commencement dates are excluded when FY filter is active
- **Index:** `commencement_month_year` is indexed (migration `2026_03_05_071759`)

---

## 7. Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/ProvincialController.php` | `projectList`: added `$fy`, `inFinancialYear($fy)`, `$fyList`, pass to view. `approvedProjects`: same pattern |
| `resources/views/provincial/ProjectList.blade.php` | FY selector as first filter column |
| `resources/views/provincial/approvedProjects.blade.php` | FY selector, form styling tweaks |

---

## 8. Verification

| Item | Status |
|------|--------|
| FY applied to projectList (all statuses) | ✓ |
| FY applied to approvedProjects | ✓ |
| FY selector in both views | ✓ |
| Query string preserved (GET, withQueryString) | ✓ |
| fyList from approved projects only | ✓ |
| Minimal fallback when no projects | ✓ |
| Existing filters unchanged | ✓ |
