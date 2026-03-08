# Phase 6.5 — Dynamic FY & Scope UI Integration Fix

**Date:** 2026-03-04  
**Status:** Complete

---

## 1. Overview

Phase 6.5 wires the dynamic FY list into the Executor dashboard. The FY dropdown is now derived from project data (`commencement_month_year`) via `listAvailableFYFromProjects()`, with fallback to the static list when no project dates exist. The scope selector continues to work as implemented in Phase 4.

---

## 2. Controller Changes (ExecutorController)

### Dynamic FY list

**Before:** `$availableFY = FinancialYearHelper::listAvailableFY()` (static 10-year list)

**After:**
```php
$availableFY = FinancialYearHelper::listAvailableFYFromProjects(
    ProjectQueryService::getProjectsForUserQuery($user)
);
if (empty($availableFY)) {
    $availableFY = FinancialYearHelper::listAvailableFY();
}
// Ensure selected FY remains in the list when explicitly chosen
if ($fy && !in_array($fy, $availableFY, true)) {
    $availableFY = array_merge([$fy], $availableFY);
    $availableFY = array_values(array_unique($availableFY));
    rsort($availableFY);
}
```

- Base query: `getProjectsForUserQuery($user)` — projects where the user is owner or in-charge.
- `listAvailableFYFromProjects` derives FYs from `commencement_month_year`.
- Fallback: static `listAvailableFY()` when the derived list is empty.
- Selected FY is merged in when missing from the derived list.

### FY and scope defaults

- `$fy = $request->input('fy', FinancialYearHelper::currentFY())`
- `$scope = $request->input('scope', 'owned')` (validated)

### View variables

`compact()` passes: `fy`, `scope`, `availableFY` plus the other dashboard variables.

---

## 3. View Verification (executor/index.blade.php)

### FY dropdown

```blade
<select name="fy" id="fy" class="form-select" onchange="this.form.submit()">
    @foreach($availableFY ?? [] as $year)
        <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
    @endforeach
</select>
```

- Uses `$availableFY` (now dynamic).
- `$fy` drives the selected option.
- Form submits on change.

### Scope selector

```blade
<select name="scope" id="scope" class="form-select" onchange="this.form.submit()">
    <option value="owned" {{ ($scope ?? 'owned') == 'owned' ? 'selected' : '' }}>Owned</option>
    <option value="in_charge" {{ ($scope ?? 'owned') == 'in_charge' ? 'selected' : '' }}>In-Charge</option>
    <option value="owned_and_in_charge" {{ ($scope ?? 'owned') == 'owned_and_in_charge' ? 'selected' : '' }}>Owned + In-Charge</option>
</select>
```

- Options: `owned`, `in_charge`, `owned_and_in_charge`.
- Form submits on change.

---

## 4. Behaviour Summary

| Action | Behaviour |
|--------|-----------|
| FY dropdown | Filled from project dates (owner or in-charge); fallback to static list when empty |
| Scope dropdown | Owned, In-Charge, Owned + In-Charge |
| Change FY | Form submits; dashboard reloads with new FY |
| Change scope | Form submits; dashboard reloads with new scope |

---

## 5. Validation Checklist

- [x] Dynamic FY list from `listAvailableFYFromProjects(getProjectsForUserQuery($user))`
- [x] Fallback to static list when empty
- [x] Selected FY kept in list when missing
- [x] FY default: `FinancialYearHelper::currentFY()`
- [x] Scope default: `owned`
- [x] FY and scope passed to view
- [x] FY dropdown uses `$availableFY`
- [x] Scope selector options present
- [x] Both dropdowns submit form on change
