# Comprehensive Review: Provincial & Coordinator Attachment View/Download Access

**Date:** 2026-02-11  
**Scope:** All project types and report attachments — view and download access for provincial, coordinator, and general roles.  
**Status:** Full permission audit (zero-trust). Includes route audit, view flow audit, controller authorization audit, middleware audit, policy/gate audit, cache/deployment risks, security gaps, and recommended fix plan.

---

## 1. Executive Summary

- **Symptom:** Provincial and coordinator users are blocked from viewing/downloading **any** project or report attachments (all project types: general attachments, IIES, and monthly report attachments).
- **Root cause:** Attachment **view** and **download** links on project/report show pages use **generic** route names that resolve to URLs without role prefix (e.g. `/projects/iies/attachments/view/36`, `/projects/attachments/download/5`, `reports/monthly/attachments/download/{id}`). Those URLs are served by a **shared** route group. If that group in production allows only `executor` and `applicant` (e.g. due to older deployed code or stale route cache), provincial and coordinator are denied by the Role middleware.
- **Current codebase (this repo):** The shared group already includes `executor,applicant,provincial,coordinator,general` for all attachment view/download routes. So the fix is to align production with this repo and clear/refresh route cache; no route definition changes are required in code for access to work.

---

## 2. Route Inventory: Attachment & Download Routes

### 2.1 Middleware Groups in `routes/web.php`

| Group (line range) | Middleware (roles) | Purpose |
|--------------------|--------------------|---------|
| 139–215 | `auth`, `role:coordinator,general` | Coordinator + General routes |
| 217–262 | `auth`, `role:general` | General-only routes |
| 264–396 | `auth`, `role:provincial` | Provincial-only routes |
| 402–469 | `auth`, `role:executor,applicant` | Executor/Applicant-only routes |
| 472–488 | `auth`, `role:executor,applicant,provincial,coordinator,general` | **Shared** project list, project downloads, project attachments, IIES attachments, activity history |
| 494–510 | `auth`, `role:executor,applicant,provincial,coordinator,general` | **Shared** report attachment download, report show, report PDF/DOC download |

### 2.2 All Attachment / Download Routes (by location and allowed roles)

#### A. Role-prefixed routes (work for the named role)

| Route name | URL pattern | Middleware group | Allowed roles |
|------------|-------------|------------------|---------------|
| `coordinator.projects.downloadPdf` | `/coordinator/projects/{project_id}/download-pdf` | coordinator,general | coordinator, general |
| `coordinator.projects.downloadDoc` | `/coordinator/projects/{project_id}/download-doc` | coordinator,general | coordinator, general |
| `coordinator.monthly.report.downloadPdf` | `/coordinator/reports/monthly/downloadPdf/{report_id}` | coordinator,general | coordinator, general |
| `coordinator.monthly.report.downloadDoc` | `/coordinator/reports/monthly/downloadDoc/{report_id}` | coordinator,general | coordinator, general |
| `provincial.projects.downloadPdf` | `/provincial/projects/{project_id}/download-pdf` | provincial | provincial |
| `provincial.projects.downloadDoc` | `/provincial/projects/{project_id}/download-doc` | provincial | provincial |
| `provincial.monthly.report.downloadPdf` | `/provincial/reports/monthly/downloadPdf/{report_id}` | provincial | provincial |
| `provincial.monthly.report.downloadDoc` | `/provincial/reports/monthly/downloadDoc/{report_id}` | provincial | provincial |

#### B. Generic (non–role-prefixed) routes — used in shared partials

| Route name | URL pattern | Middleware group (current code) | Intended roles |
|------------|-------------|---------------------------------|----------------|
| `projects.downloadPdf` | `/projects/{project_id}/download-pdf` | Shared (472–488) | executor, applicant, provincial, coordinator, general |
| `projects.downloadDoc` | `/projects/{project_id}/download-doc` | Shared (472–488) | executor, applicant, provincial, coordinator, general |
| `projects.attachments.download` | `/projects/attachments/download/{id}` | Shared (472–488) | executor, applicant, provincial, coordinator, general |
| `projects.iies.attachments.download` | `/projects/iies/attachments/download/{fileId}` | Shared (472–488) | executor, applicant, provincial, coordinator, general |
| `projects.iies.attachments.view` | `/projects/iies/attachments/view/{fileId}` | Shared (472–488) | executor, applicant, provincial, coordinator, general |
| `reports.attachments.download` | `reports/monthly/attachments/download/{id}` | Shared (494–510) | executor, applicant, provincial, coordinator, general |
| `monthly.report.downloadPdf` | `reports/monthly/downloadPdf/{report_id}` | Shared (494–510) | executor, applicant, provincial, coordinator, general |
| `monthly.report.downloadDoc` | `reports/monthly/downloadDoc/{report_id}` | Shared (494–510) | executor, applicant, provincial, coordinator, general |

#### C. Executor/Applicant-only route (by design)

| Route name | URL pattern | Middleware group | Note |
|------------|-------------|------------------|------|
| `reports.attachments.remove` | `DELETE reports/monthly/attachments/{id}` | executor,applicant (403–468, inside prefix `reports/monthly`) | Only report owner (executor/applicant) should remove report attachments during edit. Provincial/coordinator do not get “remove” in the same flow. |

---

## 3. How Views Use These Routes

### 3.1 Single project show view for all roles

- Provincial, coordinator, and general all view a project via the **same** Blade view: `resources/views/projects/Oldprojects/show.blade.php`.
- That view includes shared partials that render attachment links using **generic** route names only (no role-based route name):
  - `projects/partials/Show/attachments.blade.php` → `route('projects.attachments.download', $attachment->id)` → `/projects/attachments/download/{id}`
  - `projects/partials/Show/IIES/attachments.blade.php` → `route('projects.iies.attachments.view', $file->id)` and `route('projects.iies.attachments.download', $file->id)` → `/projects/iies/attachments/view/{fileId}` and `/projects/iies/attachments/download/{fileId}`

So when a provincial or coordinator user clicks “View” or “Download” on a project attachment (any project type, including IIES), the browser requests these **generic** URLs. The request is handled by the **shared** route group. If that group in production allows only `executor,applicant`, the Role middleware denies access and redirects to the role dashboard.

### 3.2 PDF/DOC download buttons on project show

- `projects/Oldprojects/show.blade.php` uses a **role-based** switch for the main “Download PDF” link:
  - Provincial → `provincial.projects.downloadPdf`
  - Coordinator → `coordinator.projects.downloadPdf`
  - Default → `projects.downloadPdf`
- So the main PDF/DOC buttons can work for provincial/coordinator **if** they use the role-specific routes. But **attachment** view/download (files inside the project) always use the generic routes above, so they still fail when the shared group is executor-only.

### 3.3 Monthly report show and attachments

- Report monthly show view includes `reports/monthly/partials/view/attachments.blade.php`, which uses `route('reports.attachments.download', $attachment->id)` → `reports/monthly/attachments/download/{id}`.
- Report monthly show uses a role-based switch for PDF: `coordinator` → `coordinator.monthly.report.downloadPdf`, `provincial` → `provincial.monthly.report.downloadPdf`, default → `monthly.report.downloadPdf`.
- So again: PDF/DOC can be role-specific, but **report attachment download** uses the generic route and is subject to the shared group’s allowed roles.

### 3.4 Summary: why “all project types” and “all attachments” are affected

- **Project attachments (general):** `projects.attachments.download` — generic, shared group.  
- **Project attachments (IIES):** `projects.iies.attachments.view` and `projects.iies.attachments.download` — generic, shared group.  
- **Report attachments:** `reports.attachments.download` — generic, shared group.  

There are no other project-type–specific attachment view/download routes in this codebase. So a single cause (shared group in production allowing only executor/applicant) explains provincial and coordinator being unable to view or download **any** of these attachments.

---

## 4. Production vs Current Codebase

- **Production log** (from your report): Role middleware showed `allowed_roles: ["executor","applicant"]` for the request to `.../projects/iies/attachments/view/36`, and the user (role `provincial`) was denied and redirected to `/provincial/dashboard`.
- **This repo:** The same URL is registered only once, inside the shared group at lines 472–488 with `role:executor,applicant,provincial,coordinator,general`.

So production is applying a **stricter** set of roles than this repo. That implies either:

1. **Older `routes/web.php` on production** — at some point the shared group may have been `role:executor,applicant` only, and the IIES/project/report attachment routes were in that group, or  
2. **Stale route cache** — `php artisan route:cache` was run when the shared group did not include provincial/coordinator/general; the cached file is still in use.

There are no duplicate definitions in this repo for `/projects/attachments/download/{id}`, `/projects/iies/attachments/view/{fileId}`, or `reports/monthly/attachments/download/{id}`; they exist only in the shared groups above.

---

## 5. Controllers: No Extra Role Checks

- **AttachmentController::downloadAttachment** and **IIESAttachmentsController::viewFile / downloadFile** do not perform any project-level authorization. They resolve the file by ID and serve it. So once the route allows the user (e.g. provincial), the controller will serve the file. For security, it is still recommended to add a check that the current user is allowed to access the **project** (or report) that owns the attachment (e.g. by province/hierarchy), so one provincial cannot access another province’s project attachments by guessing IDs.
- **ReportAttachmentController::downloadAttachment** similarly serves by attachment ID without an explicit “user can access this report” check. Same recommendation: add report/project-level access check when time permits.

---

## 6. Findings Summary

| # | Finding | Severity |
|---|---------|----------|
| 1 | All project and report attachment **view/download** routes in this repo are in the **shared** middleware group with roles `executor,applicant,provincial,coordinator,general`. No code change is required in routes for provincial/coordinator to have access. | — |
| 2 | Production is applying only `executor,applicant` to at least one of these routes (e.g. IIES view), so provincial/coordinator are denied. This is a **deployment or route cache** issue, not a missing route or wrong group in the current codebase. | High |
| 3 | Shared partials (project show, report show) use **generic** route names for attachments. Therefore provincial/coordinator always hit the shared routes; if production’s shared group is executor-only, **all** attachment view/download for all project types fail for them. | High |
| 4 | Role-prefixed routes (`coordinator.*`, `provincial.*`) for PDF/DOC exist and are used where the view explicitly switches by role; attachment links do not use role-prefixed routes. | Informational |
| 5 | `reports.attachments.remove` is correctly executor/applicant-only; provincial/coordinator do not need to remove report attachments from the report-edit flow. | OK |
| 6 | Controllers do not enforce project/report-level access for attachment view/download; adding such checks is recommended for defense in depth. | Medium (recommendation) |

---

## 7. Recommendations

### 7.1 Immediate (restore access)

1. **Deploy** the current `routes/web.php` (or the version that has the shared group including `provincial,coordinator,general`) to production.
2. **Clear and optionally re-cache routes** on production:
   - `php artisan route:clear`
   - If you use route caching: `php artisan route:cache`
3. **Verify** that a provincial (and coordinator) user can open a project show page and successfully view/download:
   - A general project attachment (`projects.attachments.download`),
   - An IIES attachment view and download (`projects.iies.attachments.view`, `projects.iies.attachments.download`),
   - A monthly report attachment (`reports.attachments.download`).

### 7.2 Optional (security and consistency)

4. **Project/report-level authorization:** In `AttachmentController::downloadAttachment`, `IIESAttachmentsController::viewFile`/`downloadFile`, and `ReportAttachmentController::downloadAttachment`, add a check that the authenticated user is allowed to access the project (or report) that owns the attachment (e.g. via a shared helper or policy). This prevents cross-province or cross-hierarchy access by guessing IDs.
5. **Consistency:** Consider whether all attachment links on project/report show pages should use role-aware route names (e.g. pass current role and use `provincial.projects.downloadPdf`-style routes where applicable). The current design (single generic URL + shared middleware) is valid and simpler; the main fix is ensuring production uses the shared group that includes provincial, coordinator, and general.

---

## 8. Reference: Route Snippets (current codebase)

**Shared project routes (472–488):**
```php
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    // ...
    Route::get('/projects/attachments/download/{id}', [AttachmentController::class, 'downloadAttachment'])->name('projects.attachments.download');
    Route::get('/projects/iies/attachments/download/{fileId}', [IIESAttachmentsController::class, 'downloadFile'])->name('projects.iies.attachments.download');
    Route::get('/projects/iies/attachments/view/{fileId}', [IIESAttachmentsController::class, 'viewFile'])->name('projects.iies.attachments.view');
    // ...
});
```

**Shared report routes (494–510):**
```php
Route::middleware(['auth', 'role:executor,applicant,provincial,coordinator,general'])->group(function () {
    Route::get('reports/monthly/attachments/download/{id}', [ReportAttachmentController::class, 'downloadAttachment'])->name('reports.attachments.download');
    Route::get('reports/monthly/downloadPdf/{report_id}', [ExportReportController::class, 'downloadPdf'])->name('monthly.report.downloadPdf');
    Route::get('reports/monthly/downloadDoc/{report_id}', [ExportReportController::class, 'downloadDoc'])->name('monthly.report.downloadDoc');
    // ...
});
```

**Executor-only (463, inside 403–468):**
```php
Route::middleware(['auth', 'role:executor,applicant'])->group(function () {
    // ...
    Route::prefix('reports/monthly')->group(function () {
        // ...
        Route::delete('/reports/monthly/attachments/{id}', [ReportAttachmentController::class, 'remove'])->name('reports.attachments.remove');
    });
});
```

---

## 9. Full Permission Audit (Zero-Trust)

The following sections are from a comprehensive permission audit of project and report attachment view/download flows. **Do not assume current behavior is correct.**

---

### 9.1 Critical Permission Bugs

- **Attachment downloads are ID-only; no project/report or hierarchy check**
  - **`AttachmentController::downloadAttachment($id)`**
    - Fetches `ProjectAttachment::findOrFail($id)` and streams file.
    - **No check** that the current user can access the parent `Project` (no `project_id` join, no province/parent hierarchy check, no role-specific ownership).
    - **Impact:** Any authenticated user in the shared roles can download **any project attachment** if they can guess or enumerate `attachment.id` → **IDOR**.
  - **`IIESAttachmentsController::downloadFile($fileId)` / `viewFile($fileId)`**
    - Fetches `ProjectIIESAttachmentFile::findOrFail($fileId)` by ID and streams file.
    - **No check** that the file belongs to a project the user may access.
    - **Impact:** Same IDOR risk for all IIES attachments.
  - **`ReportAttachmentController::downloadAttachment($id)`**
    - Fetches `ReportAttachment::findOrFail($id)` and streams file.
    - **No check** that the user is owner of the report, or that the report’s user is under their hierarchy, or any province-based filter.
    - **Impact:** IDOR over report attachments across all projects/provinces for all shared roles.

- **Role middleware redirects instead of 403**
  - `App\Http\Middleware\Role`: if user’s role is not in the allowed list it **redirects** to dashboard instead of returning 403. For attachments/exports this masks authorization failures; for AJAX/API it returns 302/HTML instead of JSON 403.

---

### 9.2 Cross-Role Escalation Risks

- **Provincial and coordinator can access attachments beyond their hierarchy**
  - Shared routes allow executor, applicant, provincial, coordinator, general. Controllers **do not enforce** that the underlying Project belongs to their province or to executors under them, or that the DPReport comes from their subtree.
  - **Export endpoints** (project/report PDF/DOC) **do** enforce:
    - `ExportController::downloadPdf/Doc` uses `ProjectPermissionHelper` + explicit status/parent checks.
    - `ExportReportController::downloadPdf/Doc` checks executor/applicant = own report; provincial = report user’s parent_id; coordinator/general = global.
  - **Result:** Export endpoints are well-guarded; **attachment endpoints are not**, giving broader effective read access via attachments than via the main PDFs/DOCs.

---

### 9.3 Missing Authorization Checks

- **Controllers with no authorization on attachment flows**
  - **AttachmentController:** `downloadAttachment($id)` — no `Auth::user()`, no `ProjectPermissionHelper` or policy, no join to Project to scope by project + user/hierarchy.
  - **IIESAttachmentsController:** `downloadFile` / `viewFile` — no Auth/user or relationship back to Project used for checks.
  - **ReportAttachmentController:** `downloadAttachment($id)` — same: ID-only, no Auth, no DPReport/project join, no province/hierarchy checks.
  - **ReportAttachmentController::remove($id):** executor/applicant-only at route level; no explicit owner check in the method (acceptable but could be hardened).
  - **Export controllers:** `ExportController` (project PDF/DOC) and `ExportReportController` (report PDF/DOC) **do** contain proper authorization (Auth::user(), ProjectPermissionHelper or explicit role + parent/status checks). These are the patterns to mirror in attachment controllers.

- **No policies or Gates**
  - `AuthServiceProvider` has empty `$policies` and empty `boot()`. No `Gate::define(...)`. Attachment and export flows rely only on route middleware `role:*` and ad-hoc controller checks (only in export controllers, not in attachments).

---

### 9.4 Duplicate / Conflicting Routes

- **No duplicate definitions or name collisions** for:
  - `projects.attachments.download`, `projects.iies.attachments.view`, `projects.iies.attachments.download`, `reports.attachments.download`, `monthly.report.downloadPdf`, `monthly.report.downloadDoc`.
- Each is defined **once** in `web.php` (see section 2). No route name appears in multiple middleware groups. The only executor/applicant-only attachment-related route is `reports.attachments.remove` (DELETE), which is appropriate.

---

### 9.5 Inconsistent View Routing

- **Generic vs role-prefixed route names**
  - Attachment links use **generic** route names everywhere (shared partials); no view checks `Auth::user()->role` before rendering attachment buttons.
  - PDF/DOC: some views use role-specific names (`coordinator.projects.downloadPdf`, `provincial.monthly.report.downloadPdf`); others (e.g. `general/widgets/partials/pending-items-table.blade.php`) use generic `projects.downloadPdf` and `monthly.report.downloadPdf`.
  - **Assessment:** Not a security bug (export controllers enforce access) but inconsistent; consider standardizing or adding role-based conditionals for UX (hide buttons when user cannot download).

---

### 9.6 Middleware Audit

- **Role middleware (`App\Http\Middleware\Role`)**
  - Parses roles (comma-separated); comparison is **case-sensitive** (`in_array($userRole, $allowedRoles)`). On no match: **redirect** via `getDashboardUrl`, not 403.
  - **Assumption:** `$request->user()` is non-null — if a protected route were missing `auth` middleware, unauthenticated access would throw. No environment-based differences; no conditionally registered routes; no fallback routes overriding behavior.

---

### 9.7 Policy / Gate Audit

- **No policies:** `AuthServiceProvider::$policies` is empty. No model policies for Project, DPReport, ProjectAttachment, ProjectIIESAttachmentFile, or ReportAttachment.
- **No Gates:** No `Gate::define()` usage.
- **Recommendation:** Introduce `ProjectPolicy` and `ReportPolicy` (e.g. `view`, `viewAttachments`, `downloadExport`) and use `authorize()` in attachment and export controllers.

---

### 9.8 Cache & Deployment Risks

- **RouteServiceProvider** only registers `web.php` and `api.php`; no duplicate or conditional route groups for attachments.
- **Risks:** If `php artisan route:cache` was run when shared groups used only `role:executor,applicant`, cached routes would still enforce that. If production has not been redeployed with current `web.php`, it may still have older shared groups. No attachment routes defined in other files.

---

### 9.9 Security Gaps

- **IDOR / object-level authorization:** All three attachment controllers (project, IIES, report) are vulnerable: ID-based lookups without scoping to a project/report and validating that the current user may access that parent resource.
- **Missing province / hierarchy isolation:** For attachments, provincial and coordinator access is not constrained by province or parent-child at controller level; only export controllers enforce hierarchy. A provincial could potentially read attachments from projects/reports not under their centers if they discover IDs.
- **HandlesAuthorization / ProjectPermissionHelper not used in attachment controllers:** These exist and implement good patterns (`ProjectPermissionHelper::canView(Project, User)`); they are not used in AttachmentController, IIESAttachmentsController, or ReportAttachmentController.
- **Direct file path:** DB stores `file_path`; controllers serve via `Storage::disk('public')` with `exists()` check; filenames sanitized. No arbitrary path injection.
- **Missing explicit 403:** Role middleware uses redirects; attachment controllers (when checks are added) should return 403 for unauthorized access.

---

### 9.10 Recommended Fix Plan (Step-by-Step)

1. **Harden attachment controllers (close IDOR)**
   - **AttachmentController::downloadAttachment($id):** Resolve attachment → project; get `Auth::user()`; call `ProjectPermissionHelper::canView($project, $user)` or `requireProjectAccess($project)`; abort 403 if false.
   - **IIESAttachmentsController::downloadFile / viewFile:** Resolve file → IIES attachment → project; same project-level check as above.
   - **ReportAttachmentController::downloadAttachment($id):** Resolve attachment → report → project; apply rules analogous to `ExportReportController` (executor/applicant = own report; provincial = report user under them; coordinator/general = allow); abort 403 when unauthorized.

2. **Optionally introduce policies/Gates**
   - Add `ProjectPolicy` and `DPReportPolicy` (e.g. `view`, `viewAttachments`, `downloadExport`). Register in `AuthServiceProvider`. In Export* and attachment controllers, replace manual checks with `$this->authorize('viewAttachments', $project)` (or equivalent).

3. **Middleware behavior (optional)**
   - For web UI, redirect on role failure is acceptable. Consider returning 403 for AJAX/API (e.g. when `Accept` is JSON). Ensure every protected route has `auth` middleware.

4. **Align production with current routes and clear cache**
   - Deploy current `routes/web.php` (shared groups at 472–488 and 494–510 include provincial, coordinator, general). On production: `php artisan route:clear`; if using route cache, `php artisan route:cache`. Re-test with provincial/coordinator for project attachments, IIES view/download, report attachments, and PDF/DOC.

5. **View consistency (optional)**
   - Standardize use of generic vs role-prefixed route names for PDF/DOC, or add role-based conditionals in Blade to hide download buttons when the user cannot download (controllers remain the enforcement layer).

---

*End of review.*
