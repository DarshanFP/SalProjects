# Phase 5 — Dashboard Exclusion Verification

## Summary

Verified that trashed projects are excluded from dashboard counts, pending index, financial summary, and export queries.

## Query Review

### Automatic Exclusion (SoftDeletes)

All Eloquent `Project::query()` and `Project::where()` calls automatically exclude trashed rows via the SoftDeletes global scope.

| Location | Query Type | Trashed Excluded |
|----------|------------|------------------|
| ProjectController::index | ProjectQueryService::getProjectsForUserQuery | Yes |
| ProjectController::approvedProjects | ProjectQueryService::getApprovedProjectsForUser | Yes |
| CoordinatorController (dashboard, project list, budget) | Project:: | Yes |
| ProvincialController (dashboard, project list) | Project:: | Yes |
| GeneralController (dashboard, projects, reports) | Project:: | Yes |
| AdminReadOnlyController | Project:: | Yes |
| BudgetExportController | Project (via passed project) | Yes |
| ExportController | Project (via route) | Yes |

### withTrashed / onlyTrashed Usage

| Location | Purpose |
|----------|---------|
| ProjectController::restore | Load trashed project for restore |
| ProjectController::forceDelete | Load trashed project for permanent delete |
| ProjectQueryService::getTrashedProjectsQuery | List trashed (onlyTrashed) |

### Raw SQL Fix

**SocietiesAuditCommand** — Uses `DB::table('projects')` which bypasses SoftDeletes. Fixed by adding `whereNull('deleted_at')` (or `whereNull('projects.deleted_at')` in joins) to all project queries.

## Confirmation: Trashed Excluded

- Dashboard project counts: Use Project model → excluded
- Pending index: ProjectQueryService → excluded
- Financial summary: Project model → excluded
- Export queries: Project model → excluded

## Risk Assessment

- **Low:** Eloquent default behavior correctly excludes trashed
- **Addressed:** SocietiesAuditCommand raw SQL updated to exclude trashed
