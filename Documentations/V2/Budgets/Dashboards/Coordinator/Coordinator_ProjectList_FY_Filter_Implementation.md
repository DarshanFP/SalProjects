# Coordinator Project List — Financial Year Filter Implementation

**Date:** 2026-03-07  
**Route:** `GET /coordinator/projects-list`  
**Controller:** `CoordinatorController::projectList()`  
**View:** `resources/views/coordinator/ProjectList.blade.php`

---

## 1. Implementation Overview

The Coordinator project list page now exposes a Financial Year (FY) filter in the UI, consistent with the Provincial project list architecture. The backend already supported FY filtering via `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)`; the implementation adds:

- FY dropdown in the filter form
- FY list generation from approved projects
- Active filter badge for selected FY
- Pagination continues to preserve query parameters (including `fy`)
- **Auto-filter:** All dropdowns submit the form on change (no Filter button); Clear button resets all filters

**Scope:** UI-only changes; no modifications to project query logic, `ProjectAccessService`, dataset caching, or dashboard architecture.

---

## 2. Controller Changes

### 2.1 FY List Generation

Added after `$fy = $request->input('fy', FinancialYearHelper::currentFY());`:

```php
// FY list for dropdown (from approved projects; coordinator sees all)
$fyList = FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false);
if (empty($fyList)) {
    $fyList = [FinancialYearHelper::currentFY()];
}
// When status=forwarded_to_coordinator, ensure next FY in dropdown (pending projects may not yet have commencement)
if ($request->input('status') === 'forwarded_to_coordinator') {
    $nextFy = FinancialYearHelper::nextFY();
    if (! in_array($nextFy, $fyList, true)) {
        $fyList = array_values(array_unique(array_merge([$nextFy], $fyList)));
        rsort($fyList);
    }
}
```

### 2.2 View Variables

Added `fy` and `fyList` to `compact()`:

```php
return view('coordinator.ProjectList', compact(
    'projects',
    'coordinator',
    'projectTypes',
    'users',
    'provinces',
    'centers',
    'provincials',
    'statuses',
    'filterPresets',
    'pagination',
    'fy',
    'fyList'
));
```

### 2.3 Unchanged Logic

- `$fy = $request->input('fy', FinancialYearHelper::currentFY());` — unchanged
- `$projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)` — unchanged
- All other filter logic — unchanged

---

## 3. Blade View Changes

### 3.1 FY Dropdown

Added as the first column in the basic filter row. Uses `auto-filter` class for instant submit on change:

```blade
<div class="col-md-2">
    <label for="fy" class="form-label">Financial Year</label>
    <select name="fy" id="fy" class="form-select auto-filter">
        @foreach($fyList ?? [] as $year)
            <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
        @endforeach
    </select>
</div>
```

### 3.2 Active Filter Badge

Updated `anyFilled` to include `'fy'`:

```blade
@if(request()->anyFilled(['fy', 'search', 'province', 'status', 'project_type', 'provincial_id', 'user_id', 'center', 'start_date', 'end_date']))
```

Added FY badge in Active Filters:

```blade
@if(request('fy'))
    <span class="badge bg-info me-2">FY: {{ request('fy') }}</span>
@endif
```

### 3.3 Layout

- FY dropdown: `col-md-2`
- Search: `col-md-2` (adjusted from `col-md-3`)
- Province, Status, Project Type: `col-md-2` each
- Clear button only: `col-md-2` (Filter button removed; dropdowns auto-submit)
- Total: 12 columns (Bootstrap grid)

### 3.4 Auto-Filter (Dropdowns Submit on Change)

All filter dropdowns use the `auto-filter` class and submit the form immediately on change:

- **Basic filters:** FY, Province, Status, Project Type
- **Advanced filters:** Provincial, Executor/Applicant, Center, Sort By, Order

Script (in `@push('scripts')`):

```javascript
var filterSubmitting = false;
document.querySelectorAll('.auto-filter').forEach(function(el) {
    el.addEventListener('change', function() {
        if (filterSubmitting) return;
        filterSubmitting = true;
        this.closest('form').submit();
    });
});
```

- **Search** and **date range** fields: No auto-filter; user presses Enter to submit.
- **Clear** button: Resets all filters (full URL without query params).

---

## 4. FY List Generation Logic

| Source | Method | Fallback |
|--------|--------|----------|
| Approved projects | `FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false)` | `[FinancialYearHelper::currentFY()]` |
| Status = forwarded_to_coordinator | Add `FinancialYearHelper::nextFY()` if missing | — |

- Coordinator sees all projects, so `Project::approved()` yields FYs from approved projects across the system.
- For pending projects (`forwarded_to_coordinator`), next FY is added so users can filter projects that may not yet have commencement dates.

---

## 5. Active Filter Badge Update

- `'fy'` added to `request()->anyFilled([...])` so the Active Filters section appears when FY is set.
- Badge shows `FY: {fy}` (e.g. `FY: 2025-26`) when `request('fy')` is present.

---

## 6. Pagination Compatibility

Pagination uses:

```blade
request()->fullUrlWithQuery(['page' => $i])
```

This keeps all current query parameters (including `fy`) when navigating pages. No changes were made to pagination logic.

---

## 7. Architecture Compatibility

| Component | Impact |
|-----------|--------|
| ProjectAccessService | No change — still uses `getVisibleProjectsQuery($coordinator, $fy)` |
| DatasetCacheService | No change — not used by project list |
| Coordinator dashboard cache | No change — separate page |
| Resolver batch processing | No change — not used by project list |

Project list is independent of the dashboard dataset pipeline.

---

## 8. Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/CoordinatorController.php` | Added `fyList` generation, added `fy` and `fyList` to `compact()` |
| `resources/views/coordinator/ProjectList.blade.php` | Added FY dropdown, added FY to active filters, adjusted column widths, added `auto-filter` class to all dropdowns, removed Filter button, added auto-filter submit script |

---

## 9. Example URLs with FY Filtering

| Scenario | Example URL |
|----------|-------------|
| Default (current FY) | `/coordinator/projects-list` |
| FY 2025-26 | `/coordinator/projects-list?fy=2025-26` |
| FY + status | `/coordinator/projects-list?fy=2024-25&status=forwarded_to_coordinator` |
| FY + province + page | `/coordinator/projects-list?fy=2025-26&province=Maharashtra&page=2` |

---

## 10. Future Enhancements (FY-Scoped Filter Options)

Optional follow-ups:

1. **FY-scoped project types:** Replace `coordinator_project_list_filters` cache with FY-scoped `projectTypes` (e.g. `Project::approved()->inFinancialYear($fy)->distinct()->pluck('project_type')`).
2. **FY-scoped centers/users:** Narrow centers and users to those with projects in the selected FY.
3. **Clear button with FY preserved:** Optionally use `route('coordinator.projects.list', ['fy' => $fy ?? FinancialYearHelper::currentFY()])` for Clear to keep the current FY.
4. ~~**Auto-filter on dropdown change:**~~ **Implemented.** All dropdowns use `auto-filter` class and submit on change; Filter button removed.
