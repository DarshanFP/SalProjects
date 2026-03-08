# Phase 6.6 — FY & Scope UI Visibility Fix

**Date:** 2026-03-04  
**Status:** Complete

---

## 1. Problem

FY and Scope selectors were placed inside the collapsed "Filters" section within the Projects List card. Users had to expand the Filters panel to change FY or scope, making these controls non-obvious and reducing their effectiveness.

---

## 2. Root Cause

The FY and Scope dropdowns were located in the `#projectFilters` collapse (`data-bs-toggle="collapse"`), which is hidden by default. The primary dashboard widgets (Project Budgets Overview, Quick Stats, etc.) rely on FY and scope, so these controls needed to be immediately visible.

---

## 3. Solution

A dedicated filter row was added **above** the dashboard widgets (before the Project Budgets Overview card). The row contains:

- **Financial Year** dropdown — dynamic FY list from project data
- **Project Scope** dropdown — My Projects, Projects I'm In-Charge Of, All My Projects

Both selectors are in a form that submits on change (`onchange="this.form.submit()"`), reloading the dashboard with the selected values.

---

## 4. Controller Validation

| Variable | Source | Passed to view |
|----------|--------|----------------|
| `$fy` | `$request->input('fy', FinancialYearHelper::currentFY())` | ✓ |
| `$scope` | `$request->input('scope', 'owned')` (validated) | ✓ |
| `$availableFY` | Dynamic from `listAvailableFYFromProjects` | ✓ |

**Dynamic FY logic:**
```php
$queryForFY = ProjectQueryService::getProjectsForUserQuery($user);
$availableFY = FinancialYearHelper::listAvailableFYFromProjects($queryForFY);
if (empty($availableFY)) {
    $availableFY = FinancialYearHelper::listAvailableFY();
}
// Ensure selected FY remains in list
if ($fy && !in_array($fy, $availableFY, true)) {
    $availableFY = array_merge([$fy], $availableFY);
    $availableFY = array_values(array_unique($availableFY));
    rsort($availableFY);
}
```

---

## 5. UX Improvement (FY–Scope Sync)

- FY list is derived from `getProjectsForUserQuery($user)` (owner or in-charge).
- When the derived list is empty, it falls back to `listAvailableFY()`.
- The selected FY is always kept in the list when the user has chosen it explicitly.

---

## 6. Result

The Executor dashboard now shows:

- **Financial Year** dropdown — visible above the widgets
- **Project Scope** dropdown — visible above the widgets

Changing either control submits the form and reloads the dashboard with updated totals, charts, and stats. Hidden inputs preserve `show`, `search`, `project_type`, `sort_by`, `sort_order`, and `per_page` when present.

---

## 7. Risk Assessment

**LOW RISK** — UI-only change. Controller variables and logic were already correct; the fix adds a visible form and moves focus to FY and scope as primary filters.
