# Financial Year Source Audit — Coordinator Dashboard

**Date:** 2026-03-06  
**Scope:** Coordinator dashboard FY dropdown source and architecture consistency  
**Status:** Audit complete — no hardcoding found; recommendations for optional enhancement

---

## 1. Executive Summary

The Coordinator dashboard FY dropdown is **not hardcoded**. It uses the dynamic source `FinancialYearHelper::listAvailableFY()` via controller-passed `$availableFY`. The observed values (e.g. 2025-26, 2024-25, … 2016-17) come from the helper’s static 10-year window, not from hardcoded HTML or JavaScript.

- **Controller:** `$availableFY = FinancialYearHelper::listAvailableFY()`  
- **Blade:** `@foreach($availableFY ?? [] as $year)`  
- **$availableFY:** Used as intended; not ignored

The “hardcoded” impression is likely due to:
- A fixed 10-year window that looks similar year to year  
- Predictable formatting (YYYY-YY)  
- No literal FY strings in Blade or JS

---

## 2. Current FY Dropdown Implementation

### 2.1 Location

| Item | Path | Lines |
|------|------|-------|
| FY selector | `resources/views/coordinator/widgets/system-budget-overview.blade.php` | 43–47 |

### 2.2 Blade Implementation

```blade
<label for="budget_filter_fy" class="form-label">Financial Year</label>
<select name="fy" id="budget_filter_fy" class="form-select form-select-sm" onchange="this.form.submit()">
    @foreach($availableFY ?? [] as $year)
        <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
    @endforeach
</select>
```

- **Type:** Blade loop over `$availableFY`  
- **Fallback:** `?? []` when `$availableFY` is null  
- **No hardcoded options**

### 2.3 Controller Data Source

| Controller | Method | FY List Logic | Variable Passed |
|------------|--------|---------------|-----------------|
| `CoordinatorController` | `coordinatorDashboard()` | `FinancialYearHelper::listAvailableFY()` | `$availableFY` |

```php
$availableFY = FinancialYearHelper::listAvailableFY();
// ...
return view('coordinator.index', compact(..., 'fy', 'availableFY'));
```

---

## 3. Locations of Hardcoded FY Lists

**Result:** None found in the audited scope.

| Search | Scope | Result |
|--------|-------|--------|
| `20[12][0-9]-[12][0-9]` | `resources/views`, `resources/js`, `public/js` | No matches |
| `<option.*>20XX-XX</option>` | `resources/views` | No matches |
| Literal FY strings (e.g. 2016-17, 2025-26) | `resources/` | No matches |

---

## 4. FinancialYearHelper Behavior

### 4.1 `listAvailableFY(int $yearsBack = 10)`

- **Source:** `app/Support/FinancialYearHelper.php` (lines 105–118)  
- **Logic:** Current FY (from `currentFY()`) and the previous 9 years  
- **Output:** Array of strings, newest first, e.g. `["2025-26", "2024-25", …, "2016-17"]`  
- **Dependencies:** `currentFY()` → `fromDate(Carbon::today())`  
- **Static:** No DB; uses system date only

### 4.2 `listAvailableFYFromProjects(Builder $projectQuery, bool $useStaticFallback = true)`

- **Source:** Same helper (lines 129–149)  
- **Logic:** FYs derived from project `commencement_month_year`  
- **Fallback:** If empty and `$useStaticFallback` is true, returns `listAvailableFY()`

---

## 5. Differences Between Dashboards

| Dashboard | FY Variable | FY Source | Blade Pattern |
|-----------|-------------|-----------|---------------|
| **Coordinator** | `$availableFY` | `listAvailableFY()` | `@foreach($availableFY ?? [] as $year)` |
| **Provincial** | `$fyList` | `listAvailableFYFromProjects(...)` with fallback `[currentFY()]` | `@foreach($fyList ?? [] as $year)` |
| **Executor** | `$availableFY` | `listAvailableFYFromProjects($queryForFY)` with fallback `listAvailableFY()` | `@foreach($availableFY ?? [] as $year)` |
| **General** | `$availableFY` | (controller not inspected; likely `listAvailableFY()`) | `@foreach($availableFY ?? [] as $year)` |

- **Coordinator:** Static 10-year window (no project data).  
- **Provincial & Executor:** Project-derived FY list, with static fallback only when needed.

---

## 6. JavaScript / Public JS

| Path | Search | Result |
|------|--------|--------|
| `resources/js/` | `fy`, `financial year`, `20XX` | No FY list generation |
| `public/js/` | Same | No FY list generation |

FY options are rendered server-side via Blade; no JS-driven FY list.

---

## 7. Recommended Fix / Enhancement Strategy

### 7.1 Current State (No Bug)

- FY list is dynamic via `FinancialYearHelper::listAvailableFY()`  
- No hardcoded FY values  
- Controller passes `$availableFY` and Blade uses it

### 7.2 Optional Enhancement (Architectural Consistency)

If the goal is to align with Provincial/Executor and show only FYs present in the Coordinator’s project scope:

1. Use `listAvailableFYFromProjects()` with a coordinator-scoped project query (e.g. approved projects).
2. Fall back to `listAvailableFY()` when the derived list is empty.
3. Keep existing Blade usage; only the controller logic changes.

**Example (conceptual):**

```php
$queryForFY = Project::approved(); // or coordinator-visible scope
$availableFY = FinancialYearHelper::listAvailableFYFromProjects($queryForFY);
if (empty($availableFY)) {
    $availableFY = FinancialYearHelper::listAvailableFY();
}
```

Coordinator may intentionally use a static list for system-wide reporting; in that case, the current implementation is correct.

---

## 8. Risk Assessment

| Change | Risk | Notes |
|--------|------|------|
| Keep current implementation | None | Current setup is valid |
| Switch to `listAvailableFYFromProjects()` | Low | Requires a proper coordinator project query and clear fallback behavior |
| Change `yearsBack` (e.g. 10 → 15) | Low | Config/parameter change only |
| Add hardcoded FYs | High | Avoid; breaks date-based behavior |

---

## 9. Conclusion

The Coordinator FY dropdown already uses the dynamic `FinancialYearHelper` source. `$availableFY` is passed from the controller and used in the Blade loop. The values look “hardcoded” because `listAvailableFY()` returns a fixed 10-year window based on the current date.

**Findings:**

1. FY options are generated via `@foreach($availableFY ?? [] as $year)` — not hardcoded HTML.
2. `$availableFY` is provided by the controller and used.
3. There are no hardcoded FY lists in Blade, HTML, or JS.
4. Coordinator uses `listAvailableFY()`; Provincial and Executor use `listAvailableFYFromProjects()` for scope-aware FY lists.

**Recommendation:** No change is required for correctness. Use `listAvailableFYFromProjects()` only if you want a project-scoped FY list consistent with Provincial and Executor.
