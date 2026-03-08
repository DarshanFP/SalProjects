# Coordinator Dashboard — Financial Year Source Audit

**Date:** 2026-03-07  
**Scope:** Coordinator dashboard FY dropdown source, hardcoded vs dynamic architecture  
**URL:** `http://localhost:8000/coordinator/dashboard`  
**Status:** Audit complete — no code modifications

---

## 1. Current FY Dropdown Implementation

### 1.1 Location

| Item | Path | Lines |
|------|------|-------|
| FY selector | `resources/views/coordinator/widgets/system-budget-overview.blade.php` | 43–47 |

The FY dropdown is inside the **Project Budgets Overview** widget (filter section), which is included from the main coordinator index via:

```blade
@include('coordinator.widgets.system-budget-overview')
```

### 1.2 Blade Implementation

```blade
<label for="budget_filter_fy" class="form-label">Financial Year</label>
<select name="fy" id="budget_filter_fy" class="form-select form-select-sm" onchange="this.form.submit()">
    @foreach($availableFY ?? [] as $year)
        <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
    @endforeach
</select>
```

- **Type:** Blade loop over `$availableFY` (no static `<option>` tags)
- **Fallback:** `?? []` when `$availableFY` is null
- **No hardcoded FY values in Blade**

---

## 2. Location of Hardcoded FY List

### 2.1 Search Results

**Result:** The FY dropdown is **not hardcoded in Blade or JS**. The values come from a controller variable `$availableFY`.

A codebase-wide search for literal FY strings (`2016-17`, `2017-18`, … `2025-26`) showed:

| Location | Finding |
|----------|---------|
| Blade templates | No static FY options found |
| JavaScript files | No FY lists found |
| Config files | No FY arrays found |
| `FinancialYearHelper.php` | Contains logic that *generates* these values via formula |

### 2.2 Why the List Looks Fixed

The dropdown shows a fixed-looking list (FY 2025-26 down to FY 2016-17) because `FinancialYearHelper::listAvailableFY()` returns a **formula-based static 10-year window**:

- Current FY from `currentFY()` (date-based)
- Previous 9 years derived via loop: `year - i` for `i = 0..9`
- No database reads; uses system date only

So the values are **computed** rather than hardcoded, but the **architecture is static** (not project-derived).

---

## 3. Controller FY Handling

### 3.1 Entry Point

| Controller | Method | Flow |
|------------|--------|------|
| `CoordinatorController` | `coordinatorDashboard()` | Calls `buildCoordinatorDashboardPayload()` (directly or via cache) |

### 3.2 FY Variables Passed to View

| Variable | Source | Line |
|----------|--------|------|
| `$fy` | `$request->input('fy', FinancialYearHelper::currentFY())` | ~50 |
| `$availableFY` | `FinancialYearHelper::listAvailableFY()` | 194 |

### 3.3 Payload Construction

In `buildCoordinatorDashboardPayload()`:

```php
$availableFY = FinancialYearHelper::listAvailableFY();

return [
    // ... other payload keys ...
    'fy' => $fy,
    'availableFY' => $availableFY,
];
```

**Conclusion:** The controller passes `$availableFY` to the view. The dashboard **does receive** an FY list, but it is **static** (from `listAvailableFY()`), not project-derived.

---

## 4. Dataset FY Handling

### 4.1 DatasetCacheService

| Method | Exposes FY Options? |
|--------|---------------------|
| `getCoordinatorDataset(User $coordinator, string $fy, ?array $filters)` | **No** |

`getCoordinatorDataset()` returns a `Collection` of projects. It accepts `$fy` as input and uses it for filtering but **does not expose**:

- `availableFY`
- `fyList`
- `financialYears`
- `fyOptions`

FY dropdown options are **not** provided by the dataset service.

### 4.2 Data Flow Summary

```
Request (fy param) → buildCoordinatorDashboardPayload()
                          ├─ DatasetCacheService::getCoordinatorDataset($coordinator, $fy)
                          ├─ $availableFY = FinancialYearHelper::listAvailableFY()  ← FY list
                          └─ return [..., 'fy', 'availableFY']
                                         ↓
View (coordinator.index) → @include('coordinator.widgets.system-budget-overview')
                                         ↓
Widget → @foreach($availableFY ?? [] as $year)
```

---

## 5. Comparison with Other Coordinator Pages

### 5.1 Coordinator Project List (`/coordinator/projects-list`)

| Aspect | Implementation |
|--------|----------------|
| Blade | `@foreach($fyList ?? [] as $year)` |
| Variable | `$fyList` |
| Controller source | `FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false)` |
| Fallback | `[FinancialYearHelper::currentFY()]` when empty |
| **Type** | **Dynamic** — derived from project `commencement_month_year` |

### 5.2 Coordinator Approved Projects (`/coordinator/approved-projects`)

| Aspect | Implementation |
|--------|----------------|
| Blade | `@foreach($fyList ?? [] as $year)` |
| Variable | `$fyList` |
| Controller source | `FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false)` |
| Fallback | `[FinancialYearHelper::currentFY()]` when empty |
| **Type** | **Dynamic** — derived from project data |

### 5.3 Coordinator Dashboard (`/coordinator/dashboard`)

| Aspect | Implementation |
|--------|----------------|
| Blade | `@foreach($availableFY ?? [] as $year)` |
| Variable | `$availableFY` |
| Controller source | `FinancialYearHelper::listAvailableFY()` |
| Fallback | N/A (always returns 10 values) |
| **Type** | **Static** — fixed 10-year window (no project data) |

### 5.4 Inconsistency Summary

| Page | FY List Source | Architecture |
|------|----------------|--------------|
| Project List | `listAvailableFYFromProjects(Project::approved(), false)` | Dynamic (project-derived) |
| Approved Projects | `listAvailableFYFromProjects(Project::approved(), false)` | Dynamic (project-derived) |
| **Dashboard** | **`listAvailableFY()`** | **Static (formula-based)** |

The coordinator dashboard **bypasses** `listAvailableFYFromProjects()` and uses the static 10-year list instead.

---

## 6. Correct FY Architecture

### 6.1 FinancialYearHelper Methods

| Method | Logic | Use Case |
|--------|-------|----------|
| `listAvailableFY($yearsBack = 10)` | Current FY + 9 past years; no DB | Generic fallback |
| `listAvailableFYFromProjects($projectQuery, $useStaticFallback)` | FYs from `commencement_month_year`; optionally fallback to `listAvailableFY()` | Project-scoped dashboards/lists |

### 6.2 Recommended Source for Coordinator Dashboard

To align with project list and approved projects:

```
FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), true)
```

- **Query:** `Project::approved()` — coordinator sees all approved projects
- **Fallback:** `true` → use `listAvailableFY()` when no project dates exist
- **Result:** FY dropdown reflects only financial years that exist in approved projects

Alternative (no fallback when empty):

```
listAvailableFYFromProjects(Project::approved(), false)
```

Then handle empty with:

```php
$availableFY = FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false);
if (empty($availableFY)) {
    $availableFY = [FinancialYearHelper::currentFY()];
}
```

Matches the pattern used in project list and approved projects.

---

## 7. Required Implementation Plan

### 7.1 Scope

**File:** `app/Http/Controllers/CoordinatorController.php`  
**Method:** `buildCoordinatorDashboardPayload()`

### 7.2 Current Code (Line 194)

```php
$availableFY = FinancialYearHelper::listAvailableFY();
```

### 7.3 Required Change

Replace with:

```php
$availableFY = FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false);
if (empty($availableFY)) {
    $availableFY = [FinancialYearHelper::currentFY()];
}
```

Or, to use the built-in fallback:

```php
$availableFY = FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), true);
```

### 7.4 Blade / Dataset / Helper Changes

- **Blade:** No change — already uses `@foreach($availableFY ?? [] as $year)`
- **DatasetCacheService:** No change — does not need to expose FY options
- **FinancialYearHelper:** No change — `listAvailableFYFromProjects()` already exists

### 7.5 Summary of Required Fix

| Component | Action |
|-----------|--------|
| `CoordinatorController::buildCoordinatorDashboardPayload()` | Switch from `listAvailableFY()` to `listAvailableFYFromProjects(Project::approved(), true)` or equivalent |
| View (coordinator/widgets/system-budget-overview) | None |
| DatasetCacheService | None |
| FinancialYearHelper | None |

---

## 8. Audit Summary

| Question | Answer |
|----------|--------|
| Where do FY dropdown values originate? | `buildCoordinatorDashboardPayload()` → `FinancialYearHelper::listAvailableFY()` |
| Are they hardcoded in Blade? | No — uses `@foreach($availableFY ?? [] as $year)` |
| Are they hardcoded in JS? | No |
| Does the controller provide FY variables? | Yes — `$fy` and `$availableFY` |
| Is FinancialYearHelper used? | Yes — `listAvailableFY()` (static method) |
| Does DatasetCacheService expose FY options? | No |
| Do other coordinator pages use a different architecture? | Yes — project list and approved projects use `listAvailableFYFromProjects()` (dynamic) |
| Correct FY data source? | `FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), true)` (or with manual fallback) |

---

## 9. Conclusion

The Coordinator Dashboard FY dropdown is **not hardcoded** in Blade or JavaScript. It uses controller-passed `$availableFY` populated by `FinancialYearHelper::listAvailableFY()`, which returns a **static 10-year window** based on the current date.

The dashboard is **inconsistent** with `/coordinator/projects-list` and `/coordinator/approved-projects`, which use `listAvailableFYFromProjects()` for a project-derived FY list.

**Recommendation:** Update `buildCoordinatorDashboardPayload()` to use `listAvailableFYFromProjects(Project::approved(), true)` so the FY dropdown is aligned with the rest of the coordinator FY architecture.
