# Project View Access — Current Implementation Summary

**Last updated:** 2026-02-23  
**Source:** `../Project_View_Access_Audit_And_Implementation_Plan.md`  
**Status:** Phases 1–8 completed (implementation + cache + ProjectAccessService + docs)

---

## Overview

This document records all implementations completed for the Project View Access Audit plan. Implementations follow the phased plan in the parent audit document.

---

## Phase 1: Provincial Owner + In-Charge Parity (High Priority) ✅

**Scope:** Include projects where either owner (`user_id`) OR in-charge (`in_charge`) is in the provincial's accessible user scope.

### Files Modified

| File | Changes |
|------|---------|
| `app/Models/OldProjects/Project.php` | Added `scopeAccessibleByUserIds($query, $userIds)` — filters projects where `user_id` or `in_charge` is in given IDs |
| `app/Models/Reports/Monthly/DPReport.php` | Added `scopeAccessibleByUserIds($query, $userIds)` — filters reports via `whereHas('project', ...)` using project's `accessibleByUserIds` |
| `app/Http/Controllers/ProvincialController.php` | Replaced all `Project::whereIn('user_id', $accessibleUserIds)` with `Project::accessibleByUserIds($accessibleUserIds)`; same for `DPReport`; updated `showProject()` to allow access when owner OR in-charge in scope; updated `user_id` filter in `projectList` to include projects where selected user is owner or in-charge |

### Key Code

**Project scope:**
```php
public function scopeAccessibleByUserIds($query, $userIds)
{
    $ids = $userIds instanceof \Illuminate\Support\Collection ? $userIds->toArray() : (array) $userIds;
    if (empty($ids)) return $query->whereRaw('1 = 0');
    return $query->where(function ($q) use ($ids) {
        $q->whereIn('user_id', $ids)->orWhereIn('in_charge', $ids);
    });
}
```

**showProject authorization:**
```php
$canAccess = in_array($project->user_id, $accessibleUserIds->toArray())
    || ($project->in_charge && in_array($project->in_charge, $accessibleUserIds->toArray()));
if (!$canAccess) abort(403, 'Unauthorized');
```

---

## Phase 2: Provincial Project List — Correct Project ID Route (High Priority) ✅

**Scope:** Fix project ID link in provincial project list to use provincial route instead of executor route.

### Files Modified

| File | Changes |
|------|---------|
| `resources/views/provincial/ProjectList.blade.php` | Line 277: Changed `route('projects.show', $project->project_id)` to `route('provincial.projects.show', $project->project_id)` |

### Rationale

`projects.show` resolves to `/executor/projects/{project_id}` (executor role middleware). Provincial users received 403 when clicking the project ID link. Using `provincial.projects.show` keeps them within provincial routes.

---

## Phase 3: ExportController — In-Charge + Null-Safety (High Priority) ✅

**Scope:** Include in-charge in provincial download checks; add null-safety for `$project->user`.

### Files Modified

| File | Changes |
|------|---------|
| `app/Models/OldProjects/Project.php` | Added `inChargeUser()` relationship |
| `app/Http/Controllers/Projects/ExportController.php` | Eager-load `inChargeUser` in `downloadPdf` and `downloadDoc`; provincial case now checks both `$project->user->parent_id === $user->id` and `$project->inChargeUser->parent_id === $user->id` with null guards |

### Key Code

```php
$ownerInScope = $project->user && $project->user->parent_id === $user->id;
$inChargeInScope = $project->inChargeUser && $project->inChargeUser->parent_id === $user->id;
if ($ownerInScope || $inChargeInScope) { $hasAccess = true; }
```

---

## Phase 4: ExportController — Align Download with View (Medium Priority) ✅

**Scope:** Remove status-based download restrictions for coordinator/provincial; ensure general has explicit access.

### Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/Projects/ExportController.php` | Provincial: removed status whitelist (access if owner or in-charge in scope); Coordinator: use `ProjectPermissionHelper::canView()` instead of status whitelist; General: added explicit case using `ProjectPermissionHelper::canView()`; included `general` in role switch |

### Rationale

Previously, provincial/coordinator could **view** a project but get 403 on **download** due to status filters. Download access now aligns with view access for read-only roles.

---

## Phase 5: ActivityHistoryHelper — Include General (Medium Priority) ✅

**Scope:** Allow general users to view project and report activity history.

### Files Modified

| File | Changes |
|------|---------|
| `app/Helpers/ActivityHistoryHelper.php` | In `canViewProjectActivity()` and `canViewReportActivity()`: added `general` to `['admin', 'coordinator', 'general']` check |

### Key Code

```php
if (in_array($user->role, ['admin', 'coordinator', 'general'])) {
    return true;
}
```

---

## Phase 6: Admin on Shared Download Routes (High Priority) ✅

**Scope:** Allow admin users to access shared project download, attachment, and activity-history routes.

### Files Modified

| File | Changes |
|------|---------|
| `routes/web.php` | Extended shared project group middleware from `role:executor,applicant,provincial,coordinator,general` to include `admin` |

### Rationale

Admins could view projects via admin routes but received 403 on download PDF/DOC links using shared `projects.downloadPdf` / `projects.downloadDoc` because admin was excluded from the middleware.

---

## Regression Checklist (Post-Implementation)

| # | Item | Status |
|---|------|--------|
| 1 | Provincial project list shows projects where in_charge is in team | ✅ |
| 2 | Provincial showProject allows access when in_charge in scope | ✅ |
| 3 | Project ID link in provincial ProjectList uses `provincial.projects.show` | ✅ |
| 4 | ExportController provincial includes in-charge check | ✅ |
| 5 | ExportController coordinator/provincial: download aligns with canView | ✅ |
| 6 | ActivityHistoryHelper includes general in admin/coordinator branch | ✅ |
| 7 | Null check for `$project->user` and inChargeUser in ExportController | ✅ |
| 8 | Admin included in shared download/attachments/activity-history routes | ✅ |

---

## Phase 6 (Plan): Cache getAccessibleUserIds (Low Priority) ✅

**Scope:** Reduce 24+ calls to `getAccessibleUserIds` per provincial request by caching the result per request.

### Implementation

Caching is implemented in `ProjectAccessService::getAccessibleUserIds()` (Phase 7). ProvincialController delegates to the service. Cache key: `provincial_id` + (for general) `md5` of session province filter state. Result cached per request.

---

## Phase D.4: Project Access Indexes ✅

**Scope:** Add index on `projects.status` for status-filtered queries (Phase D performance optimization).

### File Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_02_23_164600_add_project_access_indexes.php` | Adds `projects_status_index` on `projects.status`; safe idempotent check before add/drop |

---

## Phase 7: ProjectAccessService (Low Priority) ✅

**Scope:** Centralize project access logic in a single service to avoid drift and duplication.

### Files Created

| File | Purpose |
|------|---------|
| `app/Services/ProjectAccessService.php` | `getAccessibleUserIds(User)`, `canViewProject(Project, User)`, `getVisibleProjectsQuery(User)` — consolidates province, role, owner/in-charge logic with request-scoped cache |

### Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/ProvincialController.php` | Injects `ProjectAccessService`; delegates `getAccessibleUserIds()` to service (removed local implementation) |
| `app/Helpers/ActivityHistoryHelper.php` | `canViewProjectActivity()` and `canViewReportActivity()` now delegate to `ProjectAccessService::canViewProject()` |

### Key API

```php
// ProjectAccessService
$service->getAccessibleUserIds(User $provincial): Collection
$service->canViewProject(Project $project, User $user): bool
$service->getVisibleProjectsQuery(User $user): Builder
```

---

## Phase 8: Role Access Documentation ✅

**Scope:** Document role access model per plan Phase 5 (Documentation and Tests).

### Files Created

| File | Purpose |
|------|---------|
| `Documentations/V2/VIEW/Implemented/Role_Access_Model.md` | Role access model: provincial scope (owner+in-charge), download follows view, routes by role, ProjectAccessService usage |

---

## Remaining

| Phase | Scope |
|-------|-------|
| Tests | Provincial in-charge access, project ID link (no 403), general/admin download, ExportController across roles (see plan §15) |

---

*Implementation completed 2026-02-23.*
