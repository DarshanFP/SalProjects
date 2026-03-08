# Phase 2.1 — FY Selector Data Integrity Fix

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard FY Selector  
**Goal:** Ensure the Financial Year dropdown reflects only real project data and excludes FY values derived from pending or unrelated projects.

---

## Summary

The Provincial Dashboard FY dropdown now derives its options exclusively from **approved projects** with valid commencement dates. Static fabrication of FY values has been removed. When a province has no approved projects (or none with commencement dates), the dropdown shows only the current FY.

---

## 1. Controller Changes

**File:** `app/Http/Controllers/ProvincialController.php`

### Before

```php
// Phase 2: Dynamic FY selector — derive from provincial's accessible projects; fallback to last 10 years
$fyList = FinancialYearHelper::listAvailableFYFromProjects(
    Project::accessibleByUserIds($accessibleUserIds)
);
if (empty($fyList)) {
    $fyList = FinancialYearHelper::listAvailableFY();
}
```

### After

```php
// Phase 2.1: FY Selector Data Integrity — derive FY only from approved projects; no fabricated FYs
$fyList = FinancialYearHelper::listAvailableFYFromProjects(
    Project::accessibleByUserIds($accessibleUserIds)->approved(),
    false
);
if (empty($fyList)) {
    $fyList = [FinancialYearHelper::currentFY()];
}
```

### Changes

| Change | Description |
|--------|-------------|
| Added `->approved()` | FY list is derived only from approved projects. Pending and other non-approved projects no longer influence the dropdown. |
| Second parameter `false` | Instructs `listAvailableFYFromProjects` to return `[]` when no project dates exist instead of falling back to `listAvailableFY()`. |
| Minimal fallback | When the province has no approved projects (or none with `commencement_month_year`), uses `[FinancialYearHelper::currentFY()]` instead of a static 10-year list. |

---

## 2. Supporting Helper Change

**File:** `app/Support/FinancialYearHelper.php`

### Modification

Added optional parameter `$useStaticFallback` to `listAvailableFYFromProjects()`:

```php
public static function listAvailableFYFromProjects(Builder $projectQuery, bool $useStaticFallback = true): array
```

- **Default `true`:** Preserves existing behavior for other callers (ProjectController, ExecutorController) — falls back to `listAvailableFY()` when project-derived list is empty.
- **`false`:** Returns `[]` when no project dates exist, allowing the controller to apply a minimal fallback (`[currentFY()]`).

**Backward compatibility:** All existing callers continue to behave as before; no breaking changes.

---

## 3. Query Modification

| Aspect | Implementation |
|--------|----------------|
| Base query | `Project::accessibleByUserIds($accessibleUserIds)` |
| Scope added | `->approved()` — limits to projects with status in `ProjectStatus::APPROVED_STATUSES` |
| FY source | `commencement_month_year` from approved projects only |
| Exclusion | Pending, draft, and non-approved projects are excluded from FY derivation |

---

## 4. Fallback Behavior

| Condition | Result |
|-----------|--------|
| Province has approved projects with commencement dates | FY list derived from those dates; sorted descending (newest first). |
| Province has approved projects but none with commencement dates | Empty list from helper → fallback to `[FinancialYearHelper::currentFY()]`. |
| Province has no approved projects | Empty list from helper → fallback to `[FinancialYearHelper::currentFY()]`. |

**Removed:** `FinancialYearHelper::listAvailableFY()` — no fabricated FY values from a static 10-year window.

---

## 5. FY Order Verification

`FinancialYearHelper::listAvailableFYFromProjects()` uses `rsort($fyList)` to sort FY labels descending. Example expected order:

- 2026-27
- 2025-26
- 2024-25

Most recent FY appears first in the dropdown. **No changes required** — existing implementation already meets this requirement.

---

## 6. View Integration Verification

**File:** `resources/views/provincial/index.blade.php`

The Blade selector uses `$fyList` as provided by the controller:

```blade
@foreach($fyList ?? [] as $year)
    <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
@endforeach
```

**No changes required.** The controller continues to pass `fyList` in the `compact()` array.

---

## 7. Verification Results

| Item | Status |
|------|--------|
| FY derived from approved projects only | ✓ |
| Static `listAvailableFY()` removed from Provincial flow | ✓ |
| Minimal fallback `[currentFY()]` when no approved project data | ✓ |
| FY list sorted descending (newest first) | ✓ |
| View uses `$fyList` | ✓ |
| Other dashboards unchanged | ✓ (use default `$useStaticFallback = true`) |

---

## 8. Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/ProvincialController.php` | Query scoped with `->approved()`, `listAvailableFYFromProjects(..., false)`, fallback `[currentFY()]` |
| `app/Support/FinancialYearHelper.php` | Added `$useStaticFallback` parameter (default `true`) to `listAvailableFYFromProjects()` |

---

## 9. Testing Recommendations

1. **Province with approved projects:** FY dropdown shows only FYs from those projects' commencement dates.
2. **Province with no approved projects:** FY dropdown shows only current FY.
3. **Province with approved projects but no commencement dates:** FY dropdown shows only current FY.
4. **Other dashboards (Executor, Coordinator, General):** Confirm FY dropdown behavior unchanged.
