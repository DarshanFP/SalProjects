# Project Type Update & Attachment Issue Analysis

**Date:** 2026-02-16  
**Scope:** IIES-0059, IOES-0043 — Project Type Update Failure & Attachment View/Download Failure  
**Status:** READ-ONLY INVESTIGATION — No code modifications performed

---

## 1. Affected Projects

| Project ID  | Project Type (Constant)                         | Description                         |
|-------------|--------------------------------------------------|-------------------------------------|
| **IIES-0059** | `Individual - Initial - Educational support`   | Individual Initial Educational Support |
| **IOES-0043** | `Individual - Ongoing Educational support`     | Individual Ongoing Educational Support (IES) |

**Note:** IIES-0059 and IOES-0043 are `project_id` values (e.g. `IIES-0059`, `IOES-0043`). The prefix denotes project type: IIES → Individual Initial, IOES → Individual Ongoing (IES).

---

## 2. Observed Behaviour

- **Update not working:** Users report that project updates fail or appear to have no effect.
- **Attachments cannot be viewed/downloaded:** View and Download buttons for IES/IIES attachments return 404 or fail.

---

## 3. Architecture Overview

### 3.1 Project Type Storage

| Aspect | Implementation |
|--------|----------------|
| **Column** | `projects.project_type` (string, max 255) |
| **Storage** | Plain string, no enum or FK to `project_types` |
| **Values** | Full labels from `App\Constants\ProjectType` (e.g. `'Individual - Ongoing Educational support'`) |
| **Validation** | `required\|string\|max:255` (or nullable when `save_as_draft`) |
| **Edit form** | Project type is **editable** in Edit general info (not readonly) |

**No validation restricts `project_type` to known constants** — any string passes. Changing project type during edit updates the DB and causes the switch in `ProjectController@update` to run the **new** type's controllers (e.g. changing IIES → IES runs IES controllers on an IIES project).

### 3.2 Attachment Storage

| Component | IES (IOES) | IIES |
|-----------|------------|------|
| **Parent table** | `project_IES_attachments` | `project_IIES_attachments` |
| **File table** | `project_IES_attachment_files` | `project_IIES_attachment_files` |
| **Storage path** | `project_attachments/IES/{project_id}/` | `project_attachments/IIES/{project_id}/` |
| **Disk** | `Storage::disk('public')` | Same |
| **View/Download** | Route-based: `/projects/ies/attachments/view/{fileId}`, `/projects/ies/attachments/download/{fileId}` | Route-based: `/projects/iies/attachments/view/{fileId}`, `/projects/iies/attachments/download/{fileId}` |

**Legacy IES fallback:** `ProjectIESAttachments::getFilesForField()` returns stdClass objects (no `id`) when `project_IES_attachment_files` is empty but legacy column has a path. IIES has no legacy fallback — always uses `project_IIES_attachment_files`.

---

## 4. Root Cause Findings

### 4.1 Project Update Failure — Potential Causes

| # | Cause | Mechanism | Risk |
|---|-------|-----------|------|
| 1 | **Society visibility validation** | `UpdateProjectRequest` validates `society_id` with `Rule::in($allowedSocietyIds)`. `SocietyVisibilityHelper::getAllowedSocietyIds()` returns societies in user's province (or global). If IIES-0059/IOES-0043 have `society_id` outside the user's province, validation fails and update is rejected. Edit form uses `$societies` from the same helper — project's society may not appear in the dropdown. | High |
| 2 | **Status not editable** | `ProjectPermissionHelper::canEdit()` returns false if `!ProjectStatus::isEditable($project->status)`. Approved projects are not editable. | Medium |
| 3 | **User not owner/in-charge** | `canEdit` requires `isOwnerOrInCharge`. If user is neither `user_id` nor `in_charge`, update is rejected (403). | Medium |
| 4 | **Project type change → wrong controller** | Edit form allows changing `project_type`. If user changes IIES → IES (or vice versa), `GeneralInfoController` updates `project_type`. The switch in `ProjectController@update` then runs IES controllers for an IIES project (or IIES for IES). Type-specific tables differ (IIES vs IES); this can cause data inconsistency or errors. | Medium |
| 5 | **province_id / society_id FKs** | Recent migrations add `province_id` and `society_id` to `projects`. Null or invalid FKs could cause DB errors. | Low (guarded by migrations) |

### 4.2 Attachment View/Download Failure — Potential Causes

| # | Cause | Mechanism | Risk |
|---|-------|-----------|------|
| 1 | **Storage symlink missing** | IES doc notes that direct `Storage::url()` fails without `public/storage` symlink. Route-based view/download was added to avoid this. | High (if symlink absent) |
| 2 | **Legacy IES files without `id`** | IES `getFilesForField` can return legacy objects with no `id`. Show view checks `isset($file->id)`; when false, it uses `Storage::url()`, which fails without symlink. | High |
| 3 | **File missing on disk** | Controllers check `Storage::disk('public')->exists($file->file_path)`. If DB record exists but file is missing, 404 JSON is returned. | Medium |
| 4 | **Wrong file path** | Paths must be `project_attachments/IES/{project_id}/...` or `project_attachments/IIES/{project_id}/...`. Mismatch (e.g. casing, typo) causes "file not found". | Low |
| 5 | **IIES view always uses route** | IIES Show view always uses `route('projects.iies.attachments.view', $file->id)`. IIES `getFilesForField` only returns `ProjectIIESAttachmentFile` models (with `id`), so this is safe unless an edge case returns objects without `id`. | Low |

### 4.3 Middleware & Guards

| Layer | Behaviour |
|-------|-----------|
| **Routes** | `executor/projects/{project_id}/update` (PUT) under `auth`, `role:executor,applicant`. Attachment routes under `auth`, `role:executor,applicant,provincial,coordinator,general`. |
| **Update authorization** | `UpdateProjectRequest::authorize()` uses `ProjectPermissionHelper::canEdit($project, $user)`. |
| **Attachment authorization** | View/download use `fileId` only. No policy or ownership check on attachment access — any authenticated user in the role group with a valid `fileId` can access. |

**No province_id or society_id guard on update** — access is via `canEdit` (status + owner/in-charge). Society restriction is through **validation** (`Rule::in($allowedSocietyIds)`), not authorization.

---

## 5. Risk Assessment

| Question | Answer |
|----------|--------|
| **Is this systemic?** | Partially. Society visibility and status/ownership apply to all project types. Attachment symlink/legacy issues affect IES/IIES. |
| **Affects all project types?** | Society visibility and status affect all. Attachment issues are specific to IES and IIES. |
| **Only certain types?** | IIES-0059 and IOES-0043 are individual types. Society visibility is more likely to matter if societies were assigned across provinces before Phase 5B1. |

---

## 6. Recommended Fix Strategy (NO CODE)

### Phase 1: Data Verification

1. **IIES-0059 & IOES-0043 records**
   - Confirm `project_type`, `province_id`, `society_id`, `status`, `user_id`, `in_charge` in `projects`.
   - Check if `society_id` is in `SocietyVisibilityHelper::getAllowedSocietyIds()` for the reporting user.
2. **Attachments**
   - Verify rows in `project_IES_attachment_files` (IOES-0043) and `project_IIES_attachment_files` (IIES-0059).
   - Confirm `file_path` and that files exist under `storage/app/public/`.
3. **Storage**
   - Confirm `public/storage` symlink points to `storage/app/public`.

### Phase 2: Update Flow

1. **Society visibility**
   - For projects whose society is outside the user's province: either relax validation for edit (keep existing society when unchanged) or fix society/province assignment.
2. **Project type**
   - Consider making `project_type` readonly in Edit form to avoid type switches and wrong controller branch.
3. **Validation feedback**
   - Ensure validation errors (e.g. society_id) are clearly shown in the UI.

### Phase 3: Attachment Flow

1. **Storage symlink**
   - Ensure `php artisan storage:link` is run in production.
2. **Legacy IES**
   - For legacy IES files (no `id`), prefer controller-based view/download (e.g. by `project_id` + `field` + `file_path`) instead of `Storage::url()`.
3. **Missing files**
   - Log and monitor when file records exist but files are missing on disk; optionally migrate or repair paths.

### Phase 4: Monitoring

1. Log failed updates (validation, authorization) with project_id and user_id.
2. Log attachment 404s (missing file, missing record) for investigation.

---

## 7. Production Safety Notes

- **Read-only analysis:** No code, migrations, or config changes were made.
- **No DB queries run:** Findings are from code and migration review.
- **Log review:** Production logs were inspected; no explicit IIES-0059 or IOES-0043 errors were found.
- **Existing fix:** `IES_Attachment_404_Fix_Implementation.md` documents route-based view/download; implementation exists in IES and IIES controllers. Verify deployment and symlink in production.

---

## Appendix: Key File References

| Purpose | Path |
|---------|------|
| Project model | `app/Models/OldProjects/Project.php` |
| Project type constants | `app/Constants/ProjectType.php` |
| Update request | `app/Http/Requests/Projects/UpdateProjectRequest.php` |
| General info update | `app/Http/Controllers/Projects/GeneralInfoController.php` |
| Project update flow | `app/Http/Controllers/Projects/ProjectController.php` (lines 1382–1553) |
| Society visibility | `app/Helpers/SocietyVisibilityHelper.php` |
| Edit permission | `app/Helpers/ProjectPermissionHelper.php` |
| IES attachments | `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` |
| IIES attachments | `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` |
| IES fix doc | `Documentations/V2/Attachments/IES_Attachment_404_Fix_Implementation.md` |
