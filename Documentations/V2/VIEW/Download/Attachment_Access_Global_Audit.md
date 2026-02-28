# Attachment Access Global Audit — Provincial & Coordinator

**Date:** 2026-02-23  
**Purpose:** Determine why Provincial and Coordinator users cannot download project attachments, and what must be fixed to allow access across all project types.  
**Scope:** DP, IES, IIES, IAH, ILP, Monthly Reports.  
**Status:** Audit only — no implementation.

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

## 1. Executive Summary

The audit traces attachment download flow from route → middleware → controller → permission helpers for Provincial and Coordinator roles. Key findings:

- **Route-level:** Project attachment routes are in a shared middleware group (`role:executor,applicant,provincial,coordinator,general,admin`) and are **not** nested inside the executor-only group. Provincial and Coordinator should pass route middleware.
- **Controller-level:** All project attachment controllers (AttachmentController, IESAttachmentsController, IIESAttachmentsController, IAHDocumentsController, ILPAttachedDocumentsController) consistently use `passesProvinceCheck` + `ProjectPermissionHelper::canView` for view/download.
- **ReportAttachmentController** (monthly report attachments) has **no** project/report permission check — only route middleware gates access. Security gap; does not explain "cannot download."
- **ProjectAccessService** and **ProjectPermissionHelper** allow Coordinator (global oversight) and Provincial (owner OR in_charge in scope) for `canViewProject`.
- **ProvincialController::showProject** uses owner + in_charge parity; **ProvincialController::showMonthlyReport** uses owner-only (`report->user_id`), excluding in-charge reports.
- **View layer** uses shared route names (`projects.attachments.download`, etc.); no role-prefixed attachment routes.

**Likely root causes for "cannot download":**

1. **Provincial report access:** `showMonthlyReport` restricts to `report->user_id` only; in-charge reports are blocked, so provincial never sees report attachments.
2. **Province isolation:** If provincial `province_id` does not match `project->province_id`, `passesProvinceCheck` blocks access.
3. **Route cache:** Stale `php artisan route:cache` could reflect old route definitions (speculative; needs verification).

---

## 2. Current Behavior Map

```
Provincial/Coordinator → project show / report show
    → View renders attachment links (route: projects.attachments.download, etc.)
    → User clicks link → GET /projects/attachments/download/{id}
    → Middleware: auth, role:executor,applicant,provincial,coordinator,general,admin
    → AttachmentController::downloadAttachment
        → passesProvinceCheck(project, user)
        → canView(project, user) → ProjectAccessService::canViewProject
    → 200 + file download OR 403
```

---

## 3. Route Audit Table

| Route Name | URI | Middleware | Allowed Roles | Controller | Status |
|------------|-----|------------|---------------|------------|--------|
| projects.attachments.download | GET /projects/attachments/download/{id} | auth, role:executor,applicant,provincial,coordinator,general,admin | All 6 | AttachmentController@downloadAttachment | OK — shared |
| projects.attachments.view | GET /projects/attachments/view/{id} | Same | Same | AttachmentController@viewAttachment | OK |
| projects.attachments.files.destroy | DELETE /projects/attachments/files/{id} | Same | Same | AttachmentController@destroyAttachment | OK |
| projects.ies.attachments.download | GET /projects/ies/attachments/download/{fileId} | Same | Same | IESAttachmentsController@downloadFile | OK |
| projects.ies.attachments.view | GET /projects/ies/attachments/view/{fileId} | Same | Same | IESAttachmentsController@viewFile | OK |
| projects.ies.attachments.files.destroy | DELETE /projects/ies/attachments/files/{fileId} | Same | Same | IESAttachmentsController@destroyFile | OK |
| projects.iah.documents.view | GET /projects/iah/documents/view/{fileId} | Same | Same | IAHDocumentsController@viewFile | OK |
| projects.iah.documents.download | GET /projects/iah/documents/download/{fileId} | Same | Same | IAHDocumentsController@downloadFile | OK |
| projects.iah.documents.files.destroy | DELETE /projects/iah/documents/files/{fileId} | Same | Same | IAHDocumentsController@destroyFile | OK |
| projects.iies.attachments.download | GET /projects/iies/attachments/download/{fileId} | Same | Same | IIESAttachmentsController@downloadFile | OK |
| projects.iies.attachments.view | GET /projects/iies/attachments/view/{fileId} | Same | Same | IIESAttachmentsController@viewFile | OK |
| projects.iies.attachments.files.destroy | DELETE /projects/iies/attachments/files/{fileId} | Same | Same | IIESAttachmentsController@destroyFile | OK |
| projects.ilp.documents.view | GET /projects/ilp/documents/view/{fileId} | Same | Same | ILPAttachedDocumentsController@viewFile | OK |
| projects.ilp.documents.download | GET /projects/ilp/documents/download/{fileId} | Same | Same | ILPAttachedDocumentsController@downloadFile | OK |
| projects.ilp.documents.files.destroy | DELETE /projects/ilp/documents/files/{fileId} | Same | Same | ILPAttachedDocumentsController@destroyFile | OK |
| reports.attachments.download | GET reports/monthly/attachments/download/{id} | auth, role:executor,applicant,provincial,coordinator,general | No admin | ReportAttachmentController@downloadAttachment | OK — shared; admin excluded |
| reports.attachments.remove | DELETE reports/monthly/attachments/{id} | auth, role:executor,applicant | Executor only | ReportAttachmentController@remove | N/A — inside executor prefix |

**Notes:**

- Executor group closes at `routes/web.php:474`; shared project routes (476–516) are top-level. No nesting issue.
- `reports.attachments.remove` is inside executor prefix (366–471) and is executor-only by design.
- No duplicate route definitions for attachment download/view.
- Route order: no earlier route shadowing attachment routes.

---

## 4. Controller Guard Flow Diagram

```
AttachmentController / IES / IIES / IAH / ILP (view/download)
    │
    ├─► ProjectPermissionHelper::passesProvinceCheck(project, user)
    │       • admin, coordinator: return true
    │       • provincial: project->province_id === user->province_id
    │       • general (province_id null): return true
    │
    └─► ProjectPermissionHelper::canView(project, user)
            └─► ProjectAccessService::canViewProject(project, user)
                    • admin, coordinator, general: return true (after province)
                    • executor, applicant: owner OR in_charge
                    • provincial: owner OR in_charge in getAccessibleUserIds

ReportAttachmentController@downloadAttachment
    │
    └─► NO permission check
        • Only route middleware (auth + role)
        • No passesProvinceCheck, canView, or report-level authorization
```

---

## 5. Provincial Access Analysis

| Check | Location | Provincial Behavior |
|-------|----------|---------------------|
| passesProvinceCheck | ProjectPermissionHelper:24–31 | Returns true if `project->province_id === user->province_id` or `user->province_id === null` |
| canViewProject | ProjectAccessService:72–91 | Uses `getAccessibleUserIds`; allows if `user_id` or `in_charge` in scope |
| showProject | ProvincialController:641–660 | Checks owner OR in_charge in `accessibleUserIds` — consistent |
| showMonthlyReport | ProvincialController:692–719 | Checks **only** `report->user_id` in `accessibleUserIds` — **in-charge excluded** |

**Provincial blocking scenarios:**

1. **Report attachments:** Provincial cannot open a report where the in-charge is in scope but the creator (`user_id`) is not → 403 at report show → never sees attachment links.
2. **Province mismatch:** Project has different `province_id` than provincial → `passesProvinceCheck` fails → 403 on attachment download.

---

## 6. Coordinator Access Analysis

| Check | Location | Coordinator Behavior |
|-------|----------|----------------------|
| passesProvinceCheck | ProjectPermissionHelper:25–26 | Returns true (coordinator in bypass list) |
| canViewProject | ProjectAccessService:78–79 | Returns true (coordinator in bypass list) |
| showProject | CoordinatorController:663–675 | Uses ProjectAccessService::canViewProject; delegates to ProjectController::show |
| showMonthlyReport | CoordinatorController:677–696 | No additional auth; delegates to ReportController::show |

**Coordinator should not be blocked** by province or view logic. If coordinator cannot download:

- Route cache or environment issue (unverified).
- Some other role/middleware misconfiguration (not found in audit).

---

## 7. All Project Types Coverage Table

| Project Type | Attachment Controller | passesProvinceCheck | canView | Provincial | Coordinator |
|--------------|------------------------|---------------------|---------|------------|-------------|
| DP / Common | AttachmentController | Yes | Yes | Allowed if in scope + province | Allowed |
| IES | IESAttachmentsController | Yes | Yes | Same | Same |
| IIES | IIESAttachmentsController | Yes | Yes | Same | Same |
| IAH | IAHDocumentsController | Yes | Yes | Same | Same |
| ILP | ILPAttachedDocumentsController | Yes | Yes | Same | Same |
| Monthly Reports | ReportAttachmentController | **No** | **No** | Route only; report show may block | Route only |

---

## 8. Root Causes Identified

| # | Category | Description | File(s) | Line(s) |
|---|----------|-------------|---------|---------|
| 1 | C | Provincial report access: owner-only | ProvincialController | 713–715 |
| 2 | D | ReportAttachmentController: no permission check | ReportAttachmentController | 114–146 |
| 3 | E | Province isolation: provincial province_id vs project | ProjectPermissionHelper | 24–31 |
| 4 | A | reports.attachments.remove inside executor group | web.php | 366–471 |
| 5 | F | Admin excluded from reports.attachments.download | web.php | 521 |
| 6 | H | Route cache may be stale | N/A | Speculative |

**Categories:** A=Route, B=Controller (project), C=Controller (report), D=Service/permission, E=Province, F=Role middleware, G=Shadowing, H=Cache.

---

## 9. Minimal Fix Strategy (Ordered Steps)

1. **Report access for Provincial:** In `ProvincialController::showMonthlyReport`, include in-charge:  
   `in_array($report->user_id, $ids) || ($report->in_charge && in_array($report->in_charge, $ids))`.
2. **Report attachment authorization:** Add `passesProvinceCheck` + report view check (or `canView(project)`) in `ReportAttachmentController::downloadAttachment` to align with project attachments.
3. **Admin for report downloads:** Add `admin` to the reports group middleware at `web.php:521` if admin must download report attachments.
4. **Verify route cache:** After any route change, run `php artisan route:clear` and retest.

---

## 10. Safer Refactor Strategy (Optional)

- Introduce a shared `ReportAccessService` (similar to `ProjectAccessService`) for report view access.
- Have `ReportAttachmentController` call this service before streaming the file.
- Ensure `showMonthlyReport` and report attachment download use the same access rules.

---

## 11. Security Impact Analysis

| Item | Current Risk | After Minimal Fix |
|------|--------------|-------------------|
| Project attachments | Gated by province + canView | Unchanged |
| Report attachments | IDOR: anyone with attachment ID and route access can download | Add report/project view check |
| Provincial report scope | In-charge reports excluded | Include in-charge |

---

## 12. Performance Impact Analysis

- Minimal fix: No notable impact; same helpers used.
- Refactor with ReportAccessService: One additional service call per report attachment download.

---

## 13. Regression Checklist

- [ ] Executor: project attachment download (DP, IES, IIES, IAH, ILP)
- [ ] Executor: report attachment download
- [ ] Provincial: project attachment download (owner projects)
- [ ] Provincial: project attachment download (in-charge projects)
- [ ] Provincial: report attachment download (owner reports)
- [ ] Provincial: report attachment download (in-charge reports) — after fix
- [ ] Coordinator: project attachment download (all types)
- [ ] Coordinator: report attachment download
- [ ] General: project and report attachment download
- [ ] Admin: project attachment download (if applicable)

---

## 14. Final Recommendation

| Action | Recommendation |
|--------|----------------|
| Implement | Minimal fix steps 1–3 (Provincial report access, ReportAttachmentController check, Admin in reports group if needed). |
| Adjust | Align ProvincialController::showMonthlyReport with ProjectAccessService owner+in_charge logic. |
| Verify cache | Run `php artisan route:clear` and test; avoid assuming route cache without evidence. |

---

## Appendix: File References

| Component | Path |
|-----------|------|
| Routes | `routes/web.php` |
| Role middleware | `app/Http/Middleware/Role.php` |
| ProjectPermissionHelper | `app/Helpers/ProjectPermissionHelper.php` |
| ProjectAccessService | `app/Services/ProjectAccessService.php` |
| AttachmentController | `app/Http/Controllers/Projects/AttachmentController.php` |
| IESAttachmentsController | `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` |
| IIESAttachmentsController | `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` |
| IAHDocumentsController | `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` |
| ILPAttachedDocumentsController | `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` |
| ReportAttachmentController | `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php` |
| ProvincialController | `app/Http/Controllers/ProvincialController.php` |
| CoordinatorController | `app/Http/Controllers/CoordinatorController.php` |
| Project attachments partial | `resources/views/projects/partials/Show/attachments.blade.php` |
| Report attachments partial | `resources/views/reports/monthly/partials/view/attachments.blade.php` |
