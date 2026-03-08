# Phase-2 FY Selector Implementation Report

**Date:** 2026-03-05  
**Phase:** Dynamic Financial Year Selector  
**Goal:** Expose Financial Year selection in the Provincial dashboard UI using existing controller FY infrastructure.

---

## Summary

The Provincial dashboard now includes a dynamic FY selector that:

1. Derives available FY options from the provincial's accessible projects
2. Falls back to the last 10 financial years when no project dates exist
3. Auto-submits on FY change while preserving center, role, and project type filters
4. Integrates with the existing dashboard control bar

---

## Controller Changes

**File:** `app/Http/Controllers/ProvincialController.php`

### FY List Generation (after line 255)

```php
// Phase 2: Dynamic FY selector — derive from provincial's accessible projects; fallback to last 10 years
$fyList = FinancialYearHelper::listAvailableFYFromProjects(
    Project::accessibleByUserIds($accessibleUserIds)
);
if (empty($fyList)) {
    $fyList = FinancialYearHelper::listAvailableFY();
}
```

- `listAvailableFYFromProjects()` uses project `commencement_month_year` to derive FY strings from the provincial's accessible project scope
- `FinancialYearHelper::listAvailableFYFromProjects()` already falls back to `listAvailableFY()` when the derived list is empty; the explicit `if (empty($fyList))` provides an additional safeguard
- The base query uses `Project::accessibleByUserIds($accessibleUserIds)` so the FY list reflects the provincial's jurisdiction

### View Variables

Replaced `availableFY` with `fyList` in the `compact()` array:

```php
'fy',
'fyList'
```

---

## Blade Changes

### 1. `resources/views/provincial/index.blade.php`

**Filter Form:**

- Added `id="fyFilterForm"` to the form
- Renamed FY select `id` to `fySelector` (label `for` updated)
- Switched data source from `$availableFY` to `$fyList`
- Removed inline `onchange="this.form.submit()"` in favor of JS-based auto-submit

```blade
<form method="GET" action="{{ route('provincial.dashboard') }}" id="fyFilterForm" class="mb-4">
    <div class="row">
        <div class="col-md-3">
            <label for="fySelector" class="form-label">Financial Year</label>
            <select name="fy" id="fySelector" class="form-select">
                @foreach($fyList ?? [] as $year)
                    <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
                @endforeach
            </select>
        </div>
        {{-- center, role, project_type selects unchanged --}}
    </div>
</form>
```

### 2. `resources/views/provincial/dashboard.blade.php`

- Added `@stack('scripts')` before `</body>` so `@push('scripts')` content from child views (e.g. `index.blade.php`) is rendered

### Auto-Submit JavaScript

```javascript
// Phase 2: FY selector auto-submit (preserves center, role, project_type via form)
const fySelector = document.getElementById('fySelector');
const fyFilterForm = document.getElementById('fyFilterForm');
if (fySelector && fyFilterForm) {
    fySelector.addEventListener('change', function() {
        fyFilterForm.submit();
    });
}
```

---

## Filter Preservation

All filters are in the same GET form. When the FY selector (or center/role/project_type selects) changes and the form submits:

- `fy` — from FY select
- `center` — from Center select
- `role` — from Role select
- `project_type` — from Project Type select

are all sent in the query string, so changing FY does not clear other filters.

---

## Testing Results

| Test | Result |
|------|--------|
| PHP syntax check (`ProvincialController.php`) | Pass |
| Route `provincial.dashboard` exists | Pass |
| View variables `fy`, `fyList` passed to view | Confirmed |
| Form IDs `fyFilterForm`, `fySelector` present | Confirmed |
| `@stack('scripts')` in layout | Added to `provincial/dashboard.blade.php` |

**Manual verification recommended:**

1. Visit provincial dashboard → FY selector shows options from accessible projects or last 10 years
2. Change FY → page reloads with new FY; center, role, project_type remain applied
3. Change center/role/project_type → FY remains selected
4. "Clear Filters" → all filters (including FY) reset

---

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/ProvincialController.php` | Added FY list logic, pass `fyList` instead of `availableFY` |
| `resources/views/provincial/index.blade.php` | Form IDs, `fyList` variable, auto-submit JS |
| `resources/views/provincial/dashboard.blade.php` | Added `@stack('scripts')` |
