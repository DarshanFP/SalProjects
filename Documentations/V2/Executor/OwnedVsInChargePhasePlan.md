# Executor Dashboard – Owned vs In-Charge Separation Plan

**Date:** 2026-02-18  
**Business Decision:** In-Charge keeps full operational permissions. No permission removal. No schema change. Only dashboard aggregation and responsibility metrics separate. Authorization continues using merged scope. Metrics use owned scope.

---

## 1. Feasibility Summary

| Aspect | Result | Evidence |
|--------|--------|----------|
| **Query Layer Feasibility** | YES | ProjectQueryService is self-contained. All single-user project scoping flows through `getProjectsForUserQuery`. New methods `getOwnedProjectsQuery` and `getInChargeProjectsQuery` can be added alongside; no existing method needs modification. Existing callers use static methods; no shared mutable state. |
| **Dashboard Refactor Feasibility** | YES | ExecutorController methods depend exclusively on ProjectQueryService (or DPReport filtered by projectIds from it). No direct `Project::where` in controller. Each KPI method has a single entry point for project scope—swap call to owned variant. |
| **Authorization Risk** | NONE | Authorization uses `ProjectPermissionHelper::canView`, `canEdit`, `canSubmit` (project-level checks). Dashboard metrics are not used for authorization. Controllers that enforce permissions (ProjectController, ReportController, attachment controllers) use ProjectPermissionHelper or route-level middleware. Changing dashboard metrics does not touch authorization paths. |
| **KPI Risk** | LOW | KPIs are computed from project/report collections. Switching to owned scope changes input set only; calculation logic (resolver, aggregation) unchanged. Meaning of metrics changes (responsibility vs visibility) as intended. |
| **Overall Risk Level** | LOW | Additive changes (new methods); targeted substitutions (ExecutorController only for metrics); authorization untouched; no schema change. |

---

## 2. Impact Surface

| Category | Count | Details |
|----------|-------|---------|
| **Total files affected** | 4 | ProjectQueryService, ExecutorController, executor report-overview blade, (optional) ActivityHistoryService |
| **Total methods affected** | 13 | 4 new in ProjectQueryService, 9 modified in ExecutorController |
| **High-risk components** | 0 | None. Authorization and report/project CRUD unchanged. |
| **Low-risk components** | 4 | ProjectQueryService (additive), ExecutorController (parameter swap), report-overview (data source swap), ActivityHistoryService (optional) |

### Call Site Summary (from grep)

| Method | Call Sites (app only) | Uses Merged? |
|--------|------------------------|--------------|
| `getProjectsForUserQuery` | ExecutorController (4), ProjectController (3) | Yes |
| `getProjectIdsForUser` | ExecutorController (8), ReportController (5), ReportQueryService (2), DevelopmentProjectController (1) | Yes |
| `getApprovedProjectsForUser` | ExecutorController (5), ProjectController (1) | Yes |
| `getEditableProjectsForUser` | ExecutorController (1) | Yes |
| `getRevertedProjectsForUser` | ExecutorController (1) | Yes |

**Scope of change:** Only ExecutorController dashboard methods switch to owned scope. ProjectController, ReportController, ReportQueryService, DevelopmentProjectController remain on merged scope (authorization and listing).

---

## 3. Phase 1 – Infrastructure Layer

### Add to ProjectQueryService

| Method | Scope Logic | Returns |
|--------|-------------|---------|
| `getOwnedProjectsQuery(User $user)` | Province (if set) + `where('user_id', $user->id)` | Builder |
| `getInChargeProjectsQuery(User $user)` | Province (if set) + `where('in_charge', $user->id)->where('user_id', '!=', $user->id)` | Builder |
| `getOwnedProjectIds(User $user)` | `getOwnedProjectsQuery($user)->pluck('project_id')` | Collection |
| `getInChargeProjectIds(User $user)` | `getInChargeProjectsQuery($user)->pluck('project_id')` | Collection |

### Optional (for Phase 2 convenience)

| Method | Scope Logic |
|--------|-------------|
| `getApprovedOwnedProjectsForUser(User $user, array $with)` | `getOwnedProjectsQuery` + `whereIn('status', APPROVED_STATUSES)` + with |
| `getEditableOwnedProjectsForUser(User $user, array $with)` | `getOwnedProjectsQuery` + `whereIn('status', getEditableStatuses())` + with |
| `getRevertedOwnedProjectsForUser(User $user, array $with)` | `getOwnedProjectsQuery` + `whereIn('status', REVERTED_STATUSES)` + with |

### Behavior

- **No behavior change:** Existing methods unchanged. All current callers continue to use merged scope.
- **Backward compatibility:** `getProjectsForUserQuery`, `getProjectIdsForUser`, etc. remain as-is. No deprecation in Phase 1.

---

## 4. Phase 2 – KPI Separation

### Method-Level Changes (ExecutorController)

| Method | Current Source | New Source |
|--------|----------------|------------|
| `executorDashboard` | `getApprovedProjectsForUser` for budget summary | `getApprovedOwnedProjectsForUser` |
| `getQuickStats` | `getProjectsForUserQuery`, `getApprovedProjectsForUser`, `getProjectIdsForUser` | `getOwnedProjectsQuery`, `getApprovedOwnedProjectsForUser`, `getOwnedProjectIds` |
| `getActionItems` | `getProjectIdsForUser`, `getRevertedProjectsForUser`, `getApprovedProjectsForUser` | `getOwnedProjectIds`, `getRevertedOwnedProjectsForUser`, `getApprovedOwnedProjectsForUser` |
| `getReportStatusSummary` | `getProjectIdsForUser` | `getOwnedProjectIds` |
| `getChartData` | `getProjectIdsForUser`, `getApprovedProjectsForUser` | `getOwnedProjectIds`, `getApprovedOwnedProjectsForUser` |
| `getReportChartData` | `getProjectIdsForUser` | `getOwnedProjectIds` |
| `getUpcomingDeadlines` | `getApprovedProjectsForUser` | `getApprovedOwnedProjectsForUser` |
| `getProjectsRequiringAttention` | `getEditableProjectsForUser` | `getEditableOwnedProjectsForUser` |
| `getReportsRequiringAttention` | `getProjectIdsForUser` | `getOwnedProjectIds` |

### Main Projects List (executorDashboard)

- **Current:** `getProjectsForUserQuery` (merged) for main table.
- **Decision:** Keep merged for main "My Projects" list (visibility: owned + in-charge). Per business decision, only metrics separate; list can stay combined until Phase 3.
- **Phase 2:** No change to projects list query. Only metrics/KPIs switch to owned.

### Budget Summaries (executorDashboard)

- **Current:** `getApprovedProjectsForUser` → `calculateBudgetSummariesFromProjects`.
- **Phase 2:** Use `getApprovedOwnedProjectsForUser` for `$approvedProjectsForSummary`.

### Project Types Filter

- **Current:** `getProjectsForUserQuery` → distinct project_type.
- **Phase 2:** Option A: Keep merged (filter covers all visible projects). Option B: Use owned + in-charge union for filter values. Recommended: keep merged so filter applies to full visible set.

---

## 5. Phase 3 – Dashboard Structural Split

### Owned Section

- **Content:** All responsibility metrics (Quick Stats, Budget Overview, Action Items, Projects/Reports Requiring Attention, Upcoming Deadlines, Report Status Summary, Charts, Project Health).
- **Data:** Uses owned scope only (from Phase 2).
- **Project table:** Add tab/filter "My Projects (Owned)" — uses `getOwnedProjectsQuery` (or equivalent) with same filters (show, search, project_type, sort).

### In-Charge Section

- **Content:** Separate list "Assigned Projects (In-Charge)" — view-only, no KPIs.
- **Data:** `getInChargeProjectsQuery` with pagination. No budget/report/health metrics.
- **Controller:** Pass `$inChargeProjects` (paginated) to view.

### Data Contract Changes

| Variable | Phase 2 | Phase 3 |
|----------|---------|---------|
| `$projects` | Merged (current) | Owned only for "My Projects" default tab |
| `$inChargeProjects` | — | New: paginated in-charge list |
| `$budgetSummaries` | Owned | Owned |
| `$quickStats` | Owned | Owned |
| `$actionItems` | Owned | Owned |
| `$reportStatusSummary` | Owned | Owned |
| `$chartData` | Owned | Owned |
| `$reportChartData` | Owned | Owned |
| `$upcomingDeadlines` | Owned | Owned |
| `$projectsRequiringAttention` | Owned | Owned |
| `$reportsRequiringAttention` | Owned | Owned |
| `$projectHealthSummary` | Derived from `$projects` | Derived from owned `$projects` |

### Required Controller Adjustments

- `executorDashboard`: Add `$showSection` (owned | incharge) or default "owned". When incharge, pass `$inChargeProjects` instead of `$projects` for the table. Metrics always use owned.
- Optional: Add `ownedCount` and `inChargeCount` for badges.

---

## 6. Phase 4 – Cleanup & Hardening

### Replace Raw Queries in Blades

| File | Current | Action |
|------|---------|--------|
| `resources/views/executor/widgets/report-overview.blade.php` (lines 54–64) | Raw `Project::where(user_id)->orWhere(in_charge)` + `DPReport::whereIn` | Pass `$recentReports` from controller using `getOwnedProjectIds` for report-overview "Recent Reports" (metrics alignment). |

### Standardize Combined Scope Naming

- Document that `getProjectsForUserQuery` / `getProjectIdsForUser` represent **combined scope** (owner OR in-charge).
- No code rename required if callers outside ExecutorController continue to use them (ReportController, ProjectController, etc.).

### Optional: ActivityHistoryService

- **Current:** `getForExecutor` uses merged scope (direct `Project::where`).
- **Phase 4 (optional):** Keep combined for activity feed (operational visibility). Or add `getOwnedActivityIds` if activity feed should reflect owned-only for consistency. Per business decision (no permission change), leaving activity feed combined is acceptable.

### Deprecate Old Merged Methods

- **Not recommended.** ProjectController, ReportController, ReportQueryService, and others depend on merged scope for authorization and listing. Keep `getProjectsForUserQuery` and derivatives.

---

## 7. Risk Mitigation Strategy

### Order of Execution

1. **Phase 1:** Add infrastructure. Run tests. Deploy. Zero functional change.
2. **Phase 2:** Replace ProjectQueryService calls in ExecutorController with owned variants. Verify dashboard metrics. Compare before/after for users with in-charge projects (metrics should decrease).
3. **Phase 3:** Add In-Charge section UI. Ensure owned list and in-charge list are clearly separated.
4. **Phase 4:** Update report-overview blade to receive data from controller.

### Rollback Plan

- Phase 1: Remove new methods (no callers yet).
- Phase 2: Revert ExecutorController to use `getProjectIdsForUser`, `getApprovedProjectsForUser`, etc.
- Phase 3: Hide In-Charge section; revert projects list to merged.
- Phase 4: Revert blade to raw query.

### Verification Checkpoints

| Phase | Check |
|-------|-------|
| 1 | `getOwnedProjectIds($user)->count() <= getProjectIdsForUser($user)->count()` for any user |
| 2 | Quick Stats, Budget Overview, Action Items, Charts show lower/equal values than before for users with in-charge projects |
| 2 | Users with only owned projects see no change |
| 3 | In-Charge section shows only projects where `in_charge = user->id` and `user_id != user->id` |
| 3 | Authorization: in-charge user can still edit/view assigned projects (unchanged) |

### Test Cases Required

1. User with owned projects only: metrics unchanged before/after Phase 2.
2. User with owned + in-charge: Phase 2 metrics exclude in-charge projects.
3. In-charge user: can still access project/report routes for assigned projects (ProjectPermissionHelper).
4. Report list (executor.report.list): still shows reports for both owned and in-charge projects (ReportController uses merged scope).

---

## 8. Appendix: ExecutorController Method Classification

| Method | Depends on ProjectQueryService? | Mixes projectIds + collections? | Assumes merged for KPI? | Classification |
|--------|--------------------------------|----------------------------------|--------------------------|----------------|
| `getQuickStats` | Yes | Yes (projectIds for reports, collections for projects) | Yes | A) Safe to convert to Owned-only |
| `getActionItems` | Yes | Yes | Yes | A) Safe to convert |
| `getReportStatusSummary` | Yes (projectIds) | No | Yes | A) Safe to convert |
| `getChartData` | Yes | Yes | Yes | A) Safe to convert |
| `getReportChartData` | Yes (projectIds) | No | Yes | A) Safe to convert |
| `getUpcomingDeadlines` | Yes (getApprovedProjectsForUser) | No | Yes | A) Safe to convert |
| `getProjectsRequiringAttention` | Yes | No | Yes | A) Safe to convert |
| `getReportsRequiringAttention` | Yes (projectIds) | No | Yes | A) Safe to convert |
| `executorDashboard` | Yes | Yes | Yes (budget uses approved) | A) Safe to convert |

All methods: A) Safe to convert to Owned-only for metrics.

---

## 9. Appendix: KPI Integrity Validation

| Metric | Owned-only Changes Meaning? | In-Charge Mini Metrics? | Classification |
|--------|-----------------------------|--------------------------|----------------|
| Budget utilization | Yes (responsibility) | No | SAFE_TO_SPLIT |
| Approval rate | Yes (responsibility) | No | SAFE_TO_SPLIT |
| Overdue count | Yes | No | SAFE_TO_SPLIT |
| Health status | Yes | No | SAFE_TO_SPLIT |
| Deadlines | Yes | No | SAFE_TO_SPLIT |
| Report counts | Yes | No | SAFE_TO_SPLIT |
| Charts (budget, report) | Yes | No | SAFE_TO_SPLIT |

All metrics: SAFE_TO_SPLIT. No dual metric or restructure required.

---

## 10. Appendix: High-Risk Area Classification

| Location | Uses Merged? | Action |
|----------|--------------|--------|
| `resources/views/executor/widgets/report-overview.blade.php` | Raw Project query | **Must update** — pass from controller with owned scope for Recent Reports |
| `app/Services/ActivityHistoryService.php` | getForExecutor | **Leave as is** — operational visibility; no permission change |
| `app/Helpers/ActivityHistoryHelper.php` | getActivitiesQuery | **Leave as is** — used for activity history; combined scope for visibility |
| `app/Http/Controllers/Reports/Aggregated/*` | Project::where user_id/orWhere in_charge | **Leave as is** — report aggregation; not Executor dashboard |
