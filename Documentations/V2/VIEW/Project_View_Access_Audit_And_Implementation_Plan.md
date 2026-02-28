# Project View Access — Audit and Implementation Plan

**Date:** 2026-02-23  
**Purpose:** Identify why owner, in-charge, provincial, coordinator, general, and admin users are not able to access projects in view across all project statuses.  
**Scope:** Project view, attachment, download, and activity history access for **coordinator** and **provincial** roles across all project statuses.  
**Status:** Issues identified; implementation plan only (no fixes applied).

---

## Executive Summary (Revised — V2 Deep Scan)

Full access-control re-audit for **coordinator** and **provincial** roles across: (1) Project VIEW, (2) Project ATTACHMENT, (3) Download (PDF, DOC, attachments), (4) Activity history. Key findings:

- **Provincial:** Owner vs in-charge asymmetry in project list, show, ExportController; wrong route (`projects.show`) for project ID link causes 403; status-based download restrictions conflict with view; `ActivityHistoryHelper` correctly uses owner+in-charge (unlike ProvincialController).
- **Coordinator:** No pre-check before `ProjectController::show`; ExportController status whitelist restricts download for draft/submitted; coordinator sees all (no hierarchy check).
- **Cross-cutting:** Duplicated logic; `getAccessibleUserIds` called 24+ times per provincial request; ExportController lacks null-safety for `$project->user`; attachment routes use `canView` (consistent).

---

## 1. User Roles and Expected Access

| Role | Expected View Access | Current Implementation |
|------|---------------------|------------------------|
| **Owner** (executor/applicant) | Own projects, all statuses | ✅ `ProjectPermissionHelper::canView` allows owner via `user_id` |
| **In-Charge** (executor/applicant) | In-charge projects, all statuses | ✅ `ProjectPermissionHelper::canView` allows in-charge via `in_charge` |
| **Provincial** | Projects of executors/applicants under them (owner OR in-charge) | ⚠️ Only owner (`user_id`) considered; in-charge excluded |
| **Coordinator** | All projects in hierarchy | ✅ Coordinator delegates to `ProjectController::show`; `canView` allows |
| **General** | All projects (coordinator + direct team) | ✅ General delegates to `ProjectController::show`; `canView` allows |
| **Admin** | All projects (read-only) | ⚠️ Admin excluded from shared download routes; may hit 403 on download links |

---

## 2. Identified Issues

### Issue 1: Provincial — Owner vs In-Charge Asymmetry

**Location:**  
- `ProvincialController::projectList()` — base query  
- `ProvincialController::showProject()` — authorization check  
- `ProvincialController::getAccessibleUserIds()` — used for scope

**Problem:**  
Provincial project list and show logic use **only** `user_id` (owner):

```php
// projectList() — line 534
$baseQuery = Project::whereIn('user_id', $accessibleUserIds)  // excludes in_charge-only projects

// showProject() — line 683-685
if (!in_array($project->user_id, $accessibleUserIds->toArray())) {
    abort(403, 'Unauthorized');
}
```

Projects where the **in-charge** is under the provincial but the **owner** is not (e.g. owner from another province, in-charge assigned to team member) are:
- Not listed in provincial project list  
- Return 403 when accessed directly via show URL  

**Expected:**  
Include projects where either `user_id` OR `in_charge` is in `$accessibleUserIds`.

---

### Issue 2: Provincial Project List — Wrong View Route for Project ID Link

**Location:** `resources/views/provincial/ProjectList.blade.php` — line 277

**Problem:**  
Project ID link uses `route('projects.show', $project->project_id)`:

```blade
<a href="{{ route('projects.show', $project->project_id) }}">
```

`projects.show` resolves to `/executor/projects/{project_id}` (under `role:executor,applicant`). Provincial users clicking this link receive **403 Forbidden** because of role middleware.

**Expected:**  
Use `route('provincial.projects.show', $project->project_id)` for the project ID link so provincial users stay within provincial routes.

---

### Issue 3: ExportController — General User Not in Explicit Download Logic

**Location:** `app/Http/Controllers/Projects/ExportController.php` — `downloadPdf()` and `downloadDoc()`

**Problem:**  
The switch handles only `admin`, `coordinator`, and `provincial`:

```php
if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
    switch ($user->role) { ... }
} else {
    $hasAccess = ProjectPermissionHelper::canView($project, $user);
}
```

General falls into the `else` branch and uses `canView`, which **does** include general. So general can download. The real issue is **inconsistency**: coordinator and provincial have **status-based** restrictions in ExportController, while `canView` has no status restriction. General gets broader download access than coordinator in some statuses (e.g. draft).

---

### Issue 4: ExportController — Provincial Download Uses Owner-Only Check

**Location:** `ExportController::downloadPdf()` and `downloadDoc()` — provincial case

**Problem:**  
```php
case 'provincial':
    if ($project->user->parent_id === $user->id) {  // owner's parent only
        if (in_array($project->status, [...])) {
            $hasAccess = true;
        }
    }
```

Same owner vs in-charge asymmetry: projects where the provincial is the in-charge’s parent (but not the owner’s) cannot be downloaded.

---

### Issue 5: ExportController — Status Restrictions vs View Access

**Location:** `ExportController::downloadPdf()` and `downloadDoc()`

**Problem:**  
- Provincial: allowed statuses = `SUBMITTED_TO_PROVINCIAL`, `REVERTED_BY_COORDINATOR`, `APPROVED_BY_COORDINATOR`  
- Coordinator: allowed statuses = `FORWARDED_TO_COORDINATOR`, `APPROVED_BY_COORDINATOR`, `REVERTED_BY_COORDINATOR`  

`ProjectPermissionHelper::canView` has **no status filter**. A provincial can **view** a project in draft or reverted status (if in their scope) but receives **403** when attempting to download. Same for coordinator for projects in draft/submitted_to_provincial.

**Expected:**  
For read-only roles, download access should align with view access: if the user can view the project, they should be able to download it (unless there is an explicit business rule to restrict download by status).

---

### Issue 6: Admin Excluded from Shared Project Download Routes

**Location:** `routes/web.php` — lines 477–511

**Problem:**  
Shared project routes (download PDF/DOC, attachments, activity history) use:

```php
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])
```

**Admin is not included.** When an admin opens a project via `admin.projects.show` and clicks a “Download PDF” or “Download DOC” link that uses `route('projects.downloadPdf', ...)`, they receive **403** because of middleware.

**Expected:**  
Include `admin` in the shared group or define admin-specific download routes that delegate to the same controller with admin authorization.

---

### Issue 7: ProjectController::listProjects — Role Coverage

**Location:** `app/Http/Controllers/Projects/ProjectController.php` — `listProjects()`

**Problem:**  
`listProjects` (used by `projects.list`) applies province filter and provincial parent filter, but has no explicit logic for coordinator or general. Coordinators and generals typically use their own lists (`coordinator.projects.list`, `general.projects`). If `projects.list` is used by any role, coordinator/general would see all projects (subject to province). This may be intentional but should be documented. No critical bug identified here; verify intended usage.

---

### Issue 8: Province Check for General Acting as Provincial

**Location:** `ProjectPermissionHelper::passesProvinceCheck()`, `ProvincialController::getAccessibleUserIds()`

**Problem:**  
When a general user acts as provincial (visits provincial routes), `getAccessibleUserIds` returns users from managed provinces. General typically has `province_id = null`, so `passesProvinceCheck` returns `true`. Projects from any province in their scope pass. This is consistent with general’s broader scope. No bug identified; behavior is correct.

---

## 3. Expected Comprehensive Implementation Plan

### Phase 1: Owner/In-Charge Parity for Provincial (High Priority)

1. **ProvincialController::projectList()**
   - Change base query from:
     ```php
     Project::whereIn('user_id', $accessibleUserIds)
     ```
   - To:
     ```php
     Project::where(function ($q) use ($accessibleUserIds) {
         $q->whereIn('user_id', $accessibleUserIds)
           ->orWhereIn('in_charge', $accessibleUserIds);
     })
     ```
   - Ensure `$accessibleUserIds` includes only executors/applicants (no provincial IDs in `in_charge`).

2. **ProvincialController::showProject()**
   - Change check from:
     ```php
     if (!in_array($project->user_id, $accessibleUserIds->toArray()))
     ```
   - To:
     ```php
     $canAccess = in_array($project->user_id, $accessibleUserIds->toArray())
         || (optional($project->in_charge) && in_array($project->in_charge, $accessibleUserIds->toArray()));
     if (!$canAccess) abort(403, 'Unauthorized');
     ```
   - Consider `in_charge` being nullable.

3. **Other Provincial methods**
   - Search for all uses of `whereIn('user_id', $accessibleUserIds)` and apply the same `user_id OR in_charge` pattern where the list should include in-charge projects (e.g. reports, pending approvals, team overview).
   - Refer to grep results: `projectList`, report lists, `getPendingApprovalsForDashboard`, `showMonthlyReport`, etc.

---

### Phase 2: Provincial View Route Fix (High Priority)

1. **resources/views/provincial/ProjectList.blade.php**
   - Replace `route('projects.show', $project->project_id)` with `route('provincial.projects.show', $project->project_id)` for the project ID link (around line 277).
   - Ensure all other “View” links in that view also use `provincial.projects.show` (line 378 already does).

---

### Phase 3: ExportController Consistency (Medium Priority)

1. **Unify access logic**
   - Option A: Use `ProjectPermissionHelper::canView()` for all roles (admin, coordinator, provincial, general) and remove status-based restrictions for download, so download aligns with view.
   - Option B: Keep status-based rules for coordinator/provincial and explicitly add general to the switch with the same logic as coordinator (e.g. allow all statuses general can view).
   - Recommendation: **Option A** — treat download as part of view; if user can view, they can download.

2. **Include in-charge for provincial**
   - When checking provincial access, allow if either:
     - `$project->user->parent_id === $user->id`, or
     - `$project->inChargeUser && $project->inChargeUser->parent_id === $user->id`
   - Ensure `inChargeUser` (or equivalent) is eager-loaded.

3. **Include admin**
   - Add `admin` to the roles that get full access (same as current admin branch).
   - Ensure shared download routes (or equivalent admin routes) allow admin.

---

### Phase 4: Admin Access to Shared Download Routes (High Priority)

1. **routes/web.php**
   - Extend the shared project group:
     ```php
     Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general,admin'])
     ```
   - Or add a separate admin route group for downloads that points to the same controller actions.
   - Ensure admin project show view uses routes admin can access.

2. **ExportController**
   - Confirm admin is explicitly granted download access (already the case in the switch).
   - Ensure no other gates block admin before reaching the controller.

---

### Phase 5: Documentation and Tests

1. **Documentation**
   - Update role access model docs to state that provincial scope includes both owner and in-charge.
   - Document that download access follows view access for read-only roles.
   - Document which routes each role uses for project list and project show.

2. **Tests**
   - Add/update tests for:
     - Provincial viewing project where they are in-charge’s parent but not owner’s parent.
     - Provincial clicking project ID link from project list (no 403).
     - General downloading projects in various statuses.
     - Admin downloading PDF/DOC from project show.
     - ExportController access for all roles across statuses.

---

## 4. Implementation Checklist

| # | Task | Priority | Files |
|---|------|----------|-------|
| 1 | Provincial project list: include `in_charge` in scope | High | `ProvincialController.php` |
| 2 | Provincial showProject: allow access if owner OR in-charge in scope | High | `ProvincialController.php` |
| 3 | Provincial ProjectList: fix project ID link to `provincial.projects.show` | High | `provincial/ProjectList.blade.php` |
| 4 | Provincial report lists and related views: include in-charge where relevant | Medium | `ProvincialController.php`, views |
| 5 | ExportController: align download with canView (or add general explicitly) | Medium | `ExportController.php` |
| 6 | ExportController: provincial download includes in-charge check | Medium | `ExportController.php` |
| 7 | Shared routes: add admin to download/attachments/activity-history group | High | `routes/web.php` |
| 8 | Tests for provincial in-charge access, admin download, general download | Medium | `tests/` |
| 9 | Update role access documentation | Low | `Documentations/` |

---

## 5. Route + Middleware Mapping (Coordinator & Provincial)

| Role        | List Route                 | Show Route                 | Attachment View/Download      | Download PDF/DOC                 | Activity History              | Middleware (Show)      |
|-------------|----------------------------|----------------------------|-------------------------------|----------------------------------|-------------------------------|------------------------|
| Coordinator | coordinator.projects.list  | coordinator.projects.show  | projects.attachments.view/down| coordinator.projects.downloadPdf | projects.activity-history     | role:coordinator,general |
| Provincial  | provincial.projects.list   | provincial.projects.show   | projects.attachments.view/down| provincial.projects.downloadPdf  | projects.activity-history     | role:provincial        |

**Route duplication:** Coordinator and Provincial each have role-specific download routes (`coordinator.projects.downloadPdf`, `provincial.projects.downloadPdf`) AND can use shared `projects.downloadPdf` (same controller, shared group: executor, applicant, provincial, coordinator, general). Show blade uses role-specific routes to avoid middleware issues.

**projects.activity-history:** Shared group (executor, applicant, provincial, coordinator, general). No admin. ActivityHistoryController uses `ActivityHistoryHelper::canViewProjectActivity` for authorization.

---

## 6. Newly Discovered Issues (V2 Deep Scan)

### Issue 9: ActivityHistoryHelper — General User Returns False

**Location:** `app/Helpers/ActivityHistoryHelper.php` lines 40–68

**Problem:** `canViewProjectActivity` checks `admin`, `coordinator`, `provincial`, `executor`, `applicant`. **General is not included.** General falls through to `return false` at line 68. General users receive 403 when accessing `projects.activity-history` for any project.

**Expected:** Add `general` to admin/coordinator branch: `if (in_array($user->role, ['admin', 'coordinator', 'general'])) return true;`

---

### Issue 10: ActivityHistoryHelper vs ProvincialController — Logic Drift

**Location:** `ActivityHistoryHelper.php` lines 54–60 vs `ProvincialController::getAccessibleUserIds`

**Observation:** ActivityHistoryHelper for provincial uses `parent_id` direct children + **owner OR in_charge** (lines 58–60). ProvincialController uses `getAccessibleUserIds` which returns direct children + (for general) managed provinces — but **only** `user_id` in project queries. So:
- ActivityHistoryHelper: ✅ owner + in_charge
- ProvincialController: ❌ owner only

**Risk:** Duplicated logic can drift; provincial can view activity for in-charge projects but cannot list/show them.

---

### Issue 11: ExportController — Null-Safety for $project->user

**Location:** `ExportController.php` lines 337, 465

**Problem:** `$project->user->parent_id` — if `$project->user` is null (orphaned project), this throws. Project query uses `->with(['...', 'user'])` but does not enforce non-null. Add `$project->user &&` guard.

---

### Issue 12: Provincial teamActivities Excludes General

**Location:** `ActivityHistoryController.php` lines 40–51

**Problem:** `teamActivities` checks `$user->role !== 'provincial'` → abort 403. General cannot access team activities (uses `allActivities` instead). By design; document clearly.

---

### Issue 13: Attachment Routes — Coordinator/Provincial Use Shared Group

**Location:** `routes/web.php` lines 485–486; `AttachmentController.php` lines 158–161, 192–195

**Finding:** Attachment view/download use `ProjectPermissionHelper::passesProvinceCheck` and `canView`. Coordinator (province_id null) and provincial (province_id set) both pass. Attachments are **not** status-restricted; aligns with view. No owner vs in-charge gap — `canView` handles both at project level.

---

## 7. Reference: Current Access Flow

```
Role          List Route                   Show Route                     Download Route (role-specific)
------        -----------                  ----------                     -------------------------------
Provincial    provincial.projects.list     provincial.projects.show       provincial.projects.downloadPdf
Coordinator   coordinator.projects.list    coordinator.projects.show      coordinator.projects.downloadPdf
```

Shared: `projects.attachments.view`, `projects.attachments.download`, `projects.activity-history` — all in `role:executor,applicant,provincial,coordinator,general`.

---

## 8. Files Touched (Code References)

| File | Lines (approx) | Relevant Sections |
|------|----------------|-------------------|
| `app/Helpers/ProjectPermissionHelper.php` | 21–26, 89–103 | `passesProvinceCheck`, `canView` |
| `app/Helpers/ActivityHistoryHelper.php` | 40–68, 78–114 | `canViewProjectActivity`, `canViewReportActivity` |
| `app/Http/Controllers/ProvincialController.php` | 45–80, 534, 636, 673–689 | `getAccessibleUserIds`, `projectList`, `showProject` |
| `app/Http/Controllers/CoordinatorController.php` | 660–671 | `showProject` (no pre-check) |
| `app/Http/Controllers/Projects/ExportController.php` | 319–370, 440–496 | `downloadPdf`, `downloadDoc` |
| `app/Http/Controllers/Projects/AttachmentController.php` | 147–174, 176–228 | `viewAttachment`, `downloadAttachment` |
| `app/Http/Controllers/Projects/ProjectController.php` | 827–835 | `show` — `ProjectPermissionHelper::canView` |
| `app/Http/Controllers/ActivityHistoryController.php` | 80–94 | `projectHistory` — `ActivityHistoryHelper::canViewProjectActivity` |
| `resources/views/provincial/ProjectList.blade.php` | 277, 378 | Project ID link (wrong route), View button |
| `resources/views/projects/Oldprojects/show.blade.php` | 283–290 | Download PDF — role-specific routes |
| `resources/views/projects/partials/Show/attachments.blade.php` | 43, 49 | View/Download links — shared routes |
| `routes/web.php` | 160, 199–200, 361–365, 477–511 | Coordinator/Provincial routes, shared group |

---

## 9. Architectural Risks

| Risk | Severity | Description |
|------|----------|-------------|
| Logic drift | High | ProvincialController uses `user_id` only; ActivityHistoryHelper uses `user_id` OR `in_charge`. Same concept implemented differently. |
| Duplicated scope logic | Medium | `getAccessibleUserIds` (ProvincialController) vs `ActivityHistoryHelper` team scope — different implementations for “who can provincial see?” |
| Status inconsistency | High | View: no status filter. Download: status whitelist. User can view but not download. |
| Centralization gap | Medium | No single `ProjectAccessService` or policy; checks scattered across helpers, controllers, ExportController. |

**Recommendation:** Introduce a central access service (e.g. `ProjectAccessService::canView($project, $user)`) that consolidates province, role, owner/in-charge, and status rules. Controllers and ExportController call this instead of reimplementing logic.

---

## 10. Performance Risks

| Risk | Location | Mitigation |
|------|----------|------------|
| N+1 on `getAccessibleUserIds` | ProvincialController | Called 24+ times per request across projectList, reportList, showProject, pending approvals, etc. Cache result per request or pass as parameter. |
| Missing eager load | ExportController | `$project->user` loaded via `with(['user'])`. `inChargeUser` not loaded — add if in-charge check is added. |
| Large whereIn | ProvincialController | `whereIn('user_id', $accessibleUserIds)` — if IDs grow large, consider subquery or indexed scope. |
| Index gaps | projects table | Verify indexes on `user_id`, `in_charge`, `status`, `province_id`. Migration `2026_02_18_160000` adds `province_id` + `society_id`; confirm `user_id`, `in_charge` indexed. |

---

## 11. Security Risks

| Risk | Severity | Description |
|------|----------|-------------|
| Route-level trust | Low | Coordinator `showProject` has no pre-check; relies on `ProjectController::show` + `canView`. For coordinator, `canView` returns true (province null). Acceptable if coordinator is trusted to see all. |
| Attachment IDOR | Mitigated | `AttachmentController` resolves project from attachment, then runs `passesProvinceCheck` + `canView`. No IDOR if helper is correct. |
| Direct URL tampering | Mitigated | Provincial/Coordinator use role-specific show routes. ProvincialController::showProject checks `user_id` (asymmetry: in_charge not checked) before delegate. |
| ExportController bypass | Low | Same controller for role-specific and shared routes. Authorization inside controller; no bypass. |

---

## 12. Status Consistency Analysis

| Component | Status restriction? | Allowed statuses |
|-----------|---------------------|------------------|
| `ProjectPermissionHelper::canView` | No | All |
| `ProvincialController::showProject` | No | All (pre-check: user in scope) |
| `CoordinatorController::showProject` | No | All |
| `ExportController` provincial | Yes | SUBMITTED_TO_PROVINCIAL, REVERTED_BY_COORDINATOR, APPROVED_BY_COORDINATOR |
| `ExportController` coordinator | Yes | FORWARDED_TO_COORDINATOR, APPROVED_BY_COORDINATOR, REVERTED_BY_COORDINATOR |
| `AttachmentController` view/download | No | All (guarded by canView) |
| `ActivityHistoryHelper` | No | All |

**Conflict:** Provincial/Coordinator can VIEW draft/reverted projects but get 403 on DOWNLOAD for those statuses.

---

## 13. Owner vs In-Charge Parity Summary

| Location | Owner (user_id) | In-Charge |
|----------|-----------------|-----------|
| `ProjectPermissionHelper::canView` | ✅ | ✅ |
| `ProvincialController::projectList` | ✅ | ❌ |
| `ProvincialController::showProject` | ✅ | ❌ |
| `ProvincialController` report lists | ✅ | ❌ |
| `ExportController` provincial case | ✅ (via user->parent_id) | ❌ |
| `ActivityHistoryHelper::canViewProjectActivity` (provincial) | ✅ | ✅ |
| `AttachmentController` | ✅ (via canView) | ✅ |

---

## 14. Refactor Recommendations

1. **Centralize access:** Create `ProjectAccessService::canViewProject($project, $user)` and `::getVisibleProjectIdsForUser($user)` that encode province, role, owner/in-charge, and (if needed) status. Use in controllers, ExportController, ActivityHistoryHelper.
2. **Model scope:** Add `Project::scopeForProvincial($provincial)` returning query with `whereIn('user_id', ...)->orWhereIn('in_charge', ...)`.
3. **Policy:** Consider `ProjectPolicy::view()` delegating to helper for consistency and Laravel convention.
4. **Cache getAccessibleUserIds:** Store in request-level cache or pass as argument to avoid 24+ calls.

---

## 15. Suggested Test Cases

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Provincial views project where in_charge is in team, owner is not | 403 (current); 200 (after fix) |
| 2 | Provincial clicks project ID link from ProjectList | 403 (current — wrong route); 200 (after fix) |
| 3 | Coordinator views project in draft status | 200 |
| 4 | Coordinator downloads project PDF in draft status | 403 (ExportController status filter) |
| 5 | Provincial downloads project PDF in SUBMITTED_TO_PROVINCIAL | 200 |
| 6 | Provincial downloads project PDF for in-charge's project (owner not in scope) | 403 (owner-only check) |
| 7 | Attachment view for project — provincial, coordinator | 200 when canView |
| 8 | Activity history for project — provincial | 200 when owner or in_charge in team |
| 9 | Activity history for project — general | 403 (ActivityHistoryHelper excludes general) |
| 10 | `$project->user` null — ExportController | No throw (add null check) |

---

## 16. Regression Checklist

After implementing fixes:

- [ ] Provincial project list shows projects where in_charge is in team
- [ ] Provincial showProject allows access when in_charge in scope
- [ ] Project ID link in provincial ProjectList uses `provincial.projects.show`
- [ ] ExportController provincial includes in-charge check
- [ ] ExportController coordinator/provincial: align status with canView or document exception
- [ ] ActivityHistoryHelper includes general in admin/coordinator branch
- [ ] Null check for `$project->user` in ExportController
- [ ] All attachment routes still guarded by canView
- [ ] No new N+1 introduced
- [ ] Indexes on projects.user_id, in_charge, status, province_id

---

## 17. Final Implementation Plan (Phased)

| Phase | Priority | Scope |
|-------|----------|-------|
| 1 | High | Provincial: owner+in_charge in projectList, showProject, report lists |
| 2 | High | Provincial ProjectList: fix project ID route to `provincial.projects.show` |
| 3 | High | ExportController: add in-charge for provincial; add null-safety |
| 4 | Medium | ExportController: align download status with view (or document) |
| 5 | Medium | ActivityHistoryHelper: add general to canViewProjectActivity |
| 6 | Low | Cache or reduce getAccessibleUserIds calls |
| 7 | Low | Introduce ProjectAccessService (optional refactor) |

---

*End of audit and implementation plan.*

---

**Audit Version:** V2 — Coordinator & Provincial Deep Access Scan  
**Date:** 2026-02-23
