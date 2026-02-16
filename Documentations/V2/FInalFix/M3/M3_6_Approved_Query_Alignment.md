# M3.6 — Approved Query Alignment

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Task:** M3.6 — Approved Query Alignment  
**Mode:** Controlled Refactor (No Financial Formula Changes)  
**Date:** 2025-02-15

---

## Objective

Align all DB queries that filter for approved projects with canonical `ProjectStatus::APPROVED_STATUSES`. Replace single-status `where('status', ProjectStatus::APPROVED_BY_COORDINATOR)` with either `->approved()` (scope) or `whereIn('status', ProjectStatus::APPROVED_STATUSES)`.

---

## Files Modified

| File | Replacements |
|------|--------------|
| `app/Http/Controllers/ProvincialController.php` | Project queries → `->approved()`; DPReport → `whereIn('status', ProjectStatus::APPROVED_STATUSES)`; Collection filter → `whereIn('status', ProjectStatus::APPROVED_STATUSES)`; withCount projects → `$query->approved()` |
| `app/Http/Controllers/CoordinatorController.php` | Project queries → `->approved()`; Collection filters → `whereIn('status', ProjectStatus::APPROVED_STATUSES)`; withCount projects → `$query->approved()` |
| `app/Http/Controllers/GeneralController.php` | Project queries → `->approved()`; Collection filters → `whereIn('status', ProjectStatus::APPROVED_STATUSES)`; withCount projects → `$query->approved()` |
| `app/Console/Commands/TestApplicantAccess.php` | Project queries → `->approved()` |

---

## Before/After

### Project query (scope where applicable)

| Before | After |
|--------|-------|
| `Project::whereIn('user_id', $ids)->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)` | `Project::whereIn('user_id', $ids)->approved()` |
| `Project::where('status', ProjectStatus::APPROVED_BY_COORDINATOR)->with('user')` | `Project::approved()->with('user')` |
| `$query->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)` (in whereHas) | `$query->approved()` |

### Collection filter

| Before | After |
|--------|-------|
| `$teamProjects->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)` | `$teamProjects->whereIn('status', ProjectStatus::APPROVED_STATUSES)` |

### DPReport query

| Before | After |
|--------|-------|
| `DPReport::whereIn(...)->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)` | `DPReport::whereIn(...)->whereIn('status', ProjectStatus::APPROVED_STATUSES)` |

---

## Risk Reduction

| Risk | Before | After |
|------|--------|-------|
| Status drift | Queries only matched `APPROVED_BY_COORDINATOR`; excluded General-as-approver statuses | All approved statuses included via `APPROVED_STATUSES` |
| Inconsistency | Dashboards/reports could miss projects approved by General | Canonical definition applied |
| Maintenance | Adding approval path required updating many queries | Single source: `ProjectStatus::APPROVED_STATUSES` / `Project::scopeApproved()` |

---

## Why This Completes Domain Alignment

1. **Single source of truth** — `ProjectStatus::APPROVED_STATUSES` and `Project::scopeApproved()` are the canonical approval definition.
2. **Full coverage** — Queries now include `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`.
3. **Consistency with model** — `$project->isApproved()` and `Project::scopeApproved()` use the same status list.
4. **No formula or workflow changes** — Only query filters updated; financial logic and approval flow unchanged.

---

## Not Modified

- **ProjectController** — `where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` (excludes approved for executors); workflow logic per M3.6 STEP 2
- **Draft / revert / workflow logic** — No changes
- **BudgetReconciliationController** — Already used `whereIn('status', [...])` with all three approved statuses
- **Project model** — `scopeApproved()` unchanged; uses `ProjectStatus::APPROVED_STATUSES`

---

## Diff Summary

- Project queries: `where('status', X)` → `->approved()` where applicable
- Collections: `->where('status', X)` → `->whereIn('status', ProjectStatus::APPROVED_STATUSES)`
- DPReport: `where('status', X)` → `whereIn('status', ProjectStatus::APPROVED_STATUSES)` when filtering for approved reports
- Minimal, auditable changes; no financial or workflow logic touched

---

**M3.6 Complete — Approved Query Alignment Enforced**
