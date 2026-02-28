# Provincial User Attachment Download — Feasibility Audit

**Date:** 2026-02-23  
**Scope:** Feasibility of providing Provincial users access to view/download project attachments for projects of their Executors and In-charges  
**Reference:** Implemented view access (Documentations/V2/VIEW/Implemented/), laravel.log lines 1–95  
**Status:** Feasibility assessed — **HIGH**; implementation largely in place; operational fix required.

---

## 1. Executive Summary

| Aspect | Finding |
|--------|---------|
| **Feasibility** | **High** — Design and controller logic already support provincial attachment access for projects of their executors/in-charges |
| **Current Gap** | Log evidence indicates provincial is denied at **route middleware** (allowed_roles: `["executor","applicant"]`), not at controller level |
| **Root Cause** | Stale route cache or older deployment where shared attachment routes exclude provincial |
| **Action Required** | Clear route cache and/or redeploy; verify shared group includes provincial |

---

## 2. Audit of Implemented View Access

### 2.1 Implemented Phases (from `Documentations/V2/VIEW/Implemented/`)

| Phase | Scope | Provincial Impact |
|-------|-------|-------------------|
| Phase 1 | Owner + In-Charge parity | Provincial list/show include projects where owner OR in-charge is in scope |
| Phase 2 | Project ID link fix | Provincial project list uses `provincial.projects.show` (avoids executor 403) |
| Phase 3 | ExportController in-charge + null-safety | Provincial PDF/DOC download allows owner or in-charge in scope |
| Phase 4 | ExportController align download with view | Provincial download follows view access (no status whitelist) |
| Phase 5 | ActivityHistoryHelper includes general | N/A for provincial |
| Phase 6 | Admin on shared download routes | Shared group extended to `executor,applicant,provincial,coordinator,general,admin` |
| Phase 7 | ProjectAccessService | Centralized `canViewProject()` for provincial: owner or in-charge in scope |
| Phase 8 | Role Access Model documentation | Download follows view; provincial uses shared attachment routes |

### 2.2 Role Access Model (from `Role_Access_Model.md`)

- Provincial scope: projects where **owner** OR **in-charge** is in `getAccessibleUserIds`
- Download access follows view access for read-only roles
- Shared routes: `projects.attachments.view`, `projects.attachments.download`, `projects.iies.attachments.view`, `projects.iies.attachments.download`, etc. — intended to allow `role:executor,applicant,provincial,coordinator,general,admin`

---

## 3. Log Evidence Analysis (laravel.log 1–95)

### 3.1 Log Snapshot (Lines 1–2)

```
local.INFO: Role middleware - Checking access {
  "user_id":4,
  "user_role":"provincial",
  "allowed_roles":["executor","applicant"],
  "current_url":"http://localhost:8000/projects/iies/attachments/view/274",
  "has_access":false
}
local.WARNING: Role middleware - Access denied, redirecting {
  "user_id":4,
  "user_role":"provincial",
  "allowed_roles":["executor","applicant"],
  "redirect_url":"/provincial/dashboard"
}
```

### 3.2 Interpretation

| Field | Value | Implication |
|-------|-------|-------------|
| `user_role` | provincial | User is a provincial |
| `allowed_roles` | ["executor","applicant"] | **Route middleware permits only executor and applicant** |
| `current_url` | `/projects/iies/attachments/view/274` | IIES attachment view route |
| `has_access` | false | Provincial denied before reaching controller |

The Role middleware (`App\Http\Middleware\Role`) logs `allowed_roles` from the route definition. If the shared group were correct, `allowed_roles` would include `provincial`.

### 3.3 Root Cause

Per `Documentations/V2/ATTACHMENT_ACCESS_REVIEW.md`:

> Production is applying a **stricter** set of roles than this repo. That implies either:
> 1. **Older `routes/web.php` on production** — shared group may have been `role:executor,applicant` only, or  
> 2. **Stale route cache** — `php artisan route:cache` was run when provincial was not in the shared group.

**Conclusion:** The denial occurs at route middleware, not in the attachment controllers. Controller-level logic for provincial is not being reached.

---

## 4. Controller-Level Feasibility

### 4.1 IIESAttachmentsController (and similar)

| Method | Guard Chain | Provincial Behavior |
|--------|-------------|---------------------|
| `viewFile($fileId)` | 1. `passesProvinceCheck($project, $user)` | Provincial with `province_id` set: must match project province |
| | 2. `ProjectPermissionHelper::canView($project, $user)` | Delegates to `ProjectAccessService::canViewProject()` |
| `downloadFile($fileId)` | Same guards (per hardening pattern) | Same as view |

### 4.2 ProjectAccessService::canViewProject for Provincial

```php
if ($user->role === 'provincial') {
    $accessibleUserIds = $this->getAccessibleUserIds($user);  // direct children
    return in_array($project->user_id, $ids)
        || ($project->in_charge && in_array($project->in_charge, $ids));
}
```

**Result:** Provincial can view (and thus download) attachments for projects where the **owner** or **in-charge** is a direct child (executor/applicant under them). This aligns with the requested scope: "executors and incharges."

### 4.3 Other Attachment Types

| Project Type | Controller | View/Download Guard |
|--------------|------------|---------------------|
| Common (DP, CIC, etc.) | `AttachmentController` | `passesProvinceCheck` + `canView` |
| IES | `IESAttachmentsController` | Same pattern |
| IIES | `IIESAttachmentsController` | Same pattern |
| IAH | `IAHDocumentsController` | Same pattern |
| ILP | `ILPAttachedDocumentsController` | Same pattern |

All use `ProjectPermissionHelper::canView`, which delegates to `ProjectAccessService::canViewProject()`. Provincial scope (owner + in-charge) is therefore enforced consistently.

---

## 5. Route Structure (Current Codebase)

From `routes/web.php` (lines 477–511):

```php
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general,admin'])->group(function () {
    // ...
    Route::get('/projects/attachments/view/{id}', ...)->name('projects.attachments.view');
    Route::get('/projects/attachments/download/{id}', ...)->name('projects.attachments.download');
    Route::get('/projects/ies/attachments/view/{fileId}', ...)->name('projects.ies.attachments.view');
    Route::get('/projects/ies/attachments/download/{fileId}', ...)->name('projects.ies.attachments.download');
    Route::get('/projects/iies/attachments/view/{fileId}', ...)->name('projects.iies.attachments.view');
    Route::get('/projects/iies/attachments/download/{fileId}', ...)->name('projects.iies.attachments.download');
    // IAH, ILP, etc.
});
```

**Intended behavior:** Provincial is in the allowed roles for all attachment view/download routes.

---

## 6. View Flow: Where Links Are Rendered

| View | Route Used | URL Generated |
|------|------------|---------------|
| `projects/partials/Show/IIES/attachments.blade.php` | `route('projects.iies.attachments.view', $file->id)` | `/projects/iies/attachments/view/{id}` |
| `projects/partials/Show/IIES/attachments.blade.php` | `route('projects.iies.attachments.download', $file->id)` | `/projects/iies/attachments/download/{id}` |
| `projects/partials/Show/attachments.blade.php` | `route('projects.attachments.view', ...)` | `/projects/attachments/view/{id}` |

These are **generic** routes (no role prefix). Provincial users reach them when viewing a project via `provincial.projects.show` — the same Blade partials are used. If the shared route group includes provincial, these links will work.

---

## 7. Feasibility Conclusion

### 7.1 Summary

| Criterion | Status |
|-----------|--------|
| Controller-level authorization for provincial | ✅ Implemented via `canViewProject` (owner + in-charge in scope) |
| Province isolation | ✅ `passesProvinceCheck` enforced |
| Route middleware design | ✅ Shared group in current code includes provincial |
| View links | ✅ Use shared routes; no changes needed for provincial |
| Operational alignment | ⚠️ Log shows middleware still restricts to executor,applicant |

### 7.2 Feasibility: **HIGH**

- **Design:** Provincial attachment access for executors' and in-charges' projects is already designed and implemented.
- **Code:** Controllers and `ProjectAccessService` enforce the intended scope.
- **Gap:** The environment producing the log appears to use routes where the shared group does not include provincial (older deployment or stale route cache).

### 7.3 Recommendations

| Priority | Action | Owner |
|----------|--------|-------|
| 1 | Run `php artisan route:clear` and redeploy if needed | DevOps/Dev |
| 2 | Verify `routes/web.php` shared group (lines 477–511) includes `provincial` in production | Dev |
| 3 | Test: provincial user → provincial.projects.show → View/Download on IIES attachment | QA |
| 4 | (Optional) Add automated test: provincial can access attachment for project of their executor | Dev |
| 5 | Reduce Role middleware log verbosity in production (INFO on every request is noisy) | Dev |

---

## 8. Security Scope

| Check | Status |
|-------|--------|
| Province isolation | Provincial sees only projects in their province |
| Hierarchy scope | Provincial sees only projects where owner or in-charge is a direct child |
| No IDOR | Controllers resolve project from attachment, then run `canView` |
| Delete restricted | `destroyFile` uses `canEdit`; provincial typically cannot delete |

---

## 9. Related Documents

| Document | Purpose |
|----------|---------|
| `Documentations/V2/VIEW/Project_View_Access_Audit_And_Implementation_Plan.md` | Original audit and plan |
| `Documentations/V2/VIEW/Implemented/Implementation_Summary_Phases_1-6.md` | Phase implementation details |
| `Documentations/V2/VIEW/Implemented/Role_Access_Model.md` | Role access model |
| `Documentations/V2/ATTACHMENT_ACCESS_REVIEW.md` | Attachment access root cause analysis |

---

*Audit completed 2026-02-23. Feasibility: provincial attachment download for executors' and in-charges' projects is **feasible and implemented**; operational fix (route cache/deploy) is required for production.*
