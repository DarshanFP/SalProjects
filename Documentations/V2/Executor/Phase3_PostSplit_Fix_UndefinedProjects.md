# Phase 3 – Post Split Fix: Undefined $projects

## Root Cause

During the Phase 3 dashboard structural split, the executor dashboard was changed to pass two separate lists—`$ownedProjects` and `$inChargeProjects`—instead of a single merged `$projects` list. The view contract was updated accordingly: `$projects` and `$enhancedProjects` were removed from the data passed to the view.

Some **included widget views** (partials) were not updated at that time and still referenced the old variables:

- **`$projects`** — previously the paginated merged list; used for chart data and visibility checks.
- **`$enhancedProjects`** — previously the metadata array (budget, utilization, health, last report) keyed by project_id.

When the dashboard was rendered, those widgets received no value for `$projects` or `$enhancedProjects`, causing **undefined variable** usage and potential errors (e.g. calling `->total()` or `->items()` on null, or passing null/undefined to `@json`).

## Affected Files

- **`resources/views/executor/widgets/project-status-visualization.blade.php`**  
  - Line 53: `$projects` and `method_exists($projects, 'items')` used in `@json(...)` for chart data.  
  - Line 54: `$enhancedProjects` used in `@json($enhancedProjects ?? [])`.

- **`resources/views/executor/widgets/project-health.blade.php`**  
  - Line 32: Condition `isset($projects) && $projects->total() > 0 && isset($enhancedProjects)` to show health chart and breakdown.  
  - Lines 45 and 53: `collect($enhancedProjects)->filter(...)` for “Projects with Budget Issues” and “Projects Needing Reports” counts.

No other executor dashboard views or widgets referenced the removed `$projects` or `$enhancedProjects`. The variable `$projects` in `projects-requiring-attention.blade.php` is a **local** variable assigned from `$projectsRequiringAttention['projects']` (controller-passed array key); it is not the removed dashboard variable.

## Fix Implemented

- **Replaced `$projects` with `$ownedProjects`**  
  - All chart and visibility logic now uses the owned-scope paginator.  
  - In `project-status-visualization.blade.php`: chart data is set to `isset($ownedProjects) && method_exists($ownedProjects, 'items') ? $ownedProjects->items() : []`, so charts reflect **owned projects only**.

- **Replaced `$enhancedProjects` with `$enhancedOwnedProjects`**  
  - All metadata (utilization, last report date, etc.) now comes from the owned-scope enhanced array.  
  - In `project-health.blade.php`: the health breakdown counts (budget issues, needing reports) use `collect($enhancedOwnedProjects)->filter(...)`.

- **Charts and KPIs**  
  - Project Status Distribution and Project Type Distribution charts now use only owned project data.  
  - Project Health widget shows health distribution and factors only for owned projects.  
  - This keeps dashboard responsibility metrics aligned with the Phase 2 KPI rule: **owned scope only**.

## Architectural Rationale

- **Dashboard KPIs must use owned scope**  
  Phase 2 established that all responsibility metrics (counts, budgets, charts) use owned-scope data. The post-split fix continues that: widgets that were still using the old merged `$projects` / `$enhancedProjects` now use `$ownedProjects` / `$enhancedOwnedProjects`, so no merged scope is reintroduced for these widgets.

- **No reintroduction of merged scope**  
  The fix does not bring back `$projects` or any merged list for the dashboard. The main list remains split into “My Projects (Owned)” and “Assigned Projects (In-Charge)”; only the **owned** list and its enhanced metadata are used for status/health widgets and charts.

- **Authorization untouched**  
  No permission or authorization logic was changed. Only the **data source** for the affected widget views was switched from the removed variables to the existing owned-scope variables already passed by `executorDashboard()`.

## Verification

- **No undefined variable errors**  
  All references to `$projects` and `$enhancedProjects` in executor dashboard widgets have been removed or replaced. The only remaining `$projects` in executor views is the local variable in `projects-requiring-attention.blade.php` (from `$projectsRequiringAttention['projects']`), which is correct.

- **Charts render**  
  Project Status and Project Type charts receive `$ownedProjects->items()` when available; otherwise an empty array. Project Health chart and breakdown use `$ownedProjects` / `$enhancedOwnedProjects` for visibility and counts.

- **Owned-only KPI integrity maintained**  
  Widgets that display project status, type, or health now use only owned-scope data, consistent with Phase 2 and Phase 3 design.
