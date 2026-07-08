# Phase 7.2 — DP Dual Create Path Resolution

**Status:** ✅ Implemented (Option A)  
**Date:** 2026-06-13

## Problem

Two monthly create entry points existed:

| Route | View | Budget source |
|-------|------|---------------|
| `monthly.report.create` | `ReportAll` | `BudgetCalculationService::getBudgetsForReport()` (current phase) |
| `monthly.developmentProject.create` | `reportform` / `ReportCommonForm` | `max('phase')` on `ProjectBudget` |

All UI "Write Report" links already pointed to `monthly.report.create`. The alternate path caused phase mismatch vs coordinator dashboard.

## Decision: Option A (recommended in plan)

Deprecate alternate **create** path; redirect to canonical form.

## Changes

`MonthlyDevelopmentProjectController`:

- `create()` and `createForm()` → `redirectToCanonicalReportCreate()` → `monthly.report.create`
- Auth gate preserved before redirect
- Logs: `Legacy developmentProject create redirected to monthly.report.create (Phase 7)`

**Store route** (`monthly.developmentProject.store`):

- Kept for backward compatibility (old bookmarks / reportform POST)
- Logs `Deprecated route monthly.developmentProject.store used`
- Success redirect changed to `monthly.report.edit` (not legacy create)

**Routes comment** updated in `routes/web.php`.

## Canonical entry point

**Write Report** → `route('monthly.report.create', $project->project_id)` → `ReportAll.blade.php`

## Files

- `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`
- `routes/web.php`

## Manual test

1. Visit `/reports/monthly/development-project/create/{approved_dp_project_id}` → should redirect to ReportAll
2. Create report from approved projects list → same form, SOA uses current phase budgets
