# COMMON PROJECTS — ATTACHMENT DELETE FEASIBILITY AUDIT

**Mode:** Audit only. No code changes.  
**Date:** 2026-02-17.  
**Scope:** Generic/common project attachments (non–IES/IIES/IAH/ILP). Feasibility of per-file delete, province isolation, canView/canEdit enforcement, and UI.

---

## 1. EXECUTIVE SUMMARY

The **common** (generic) project attachment system uses a **single-table, one-row-per-file** model (`ProjectAttachment`), controller `AttachmentController`, and partials `Edit/attachment.blade.php` and `Show/attachments.blade.php`. It applies to all project types that are **not** Individual (IES, IIES, IAH, ILP): Development Projects, CCI, RST, Edu-RUT, IGE, LDP, CIC, etc. **Download** is already route-based (`projects.attachments.download`) but has **no** project resolution, **no** province check, and **no** canView — so it is **IDOR-prone**. **View** uses a **direct public URL** (`asset('storage/' . $attachment->file_path)`). There is **no** per-file delete method, no delete route, and no delete button. Adding guarded read (view + download), per-file delete with province + isEditable + canEdit, and a delete button is **feasible** with the same pattern as IES/ILP. The main differences are: (1) no separate “file” table — each row is one attachment; (2) `ProjectAttachment` has **no** `deleting` event, so storage cleanup must be added to avoid orphans; (3) View link must be moved from public URL to a new view route. No architectural redesign is required; direct replication of the IES/ILP guard pattern is possible with small adaptations.

---

## 2. STRUCTURAL ANALYSIS

### 2.1 Controller

**File:** `app/Http/Controllers/Projects/AttachmentController.php` (not `ProjectAttachmentController`).

| # | Question | Answer |
|---|----------|--------|
| 1 | How are attachments stored? | **One row per file** in `project_attachments`. Each upload creates one `ProjectAttachment` record with `file_path`, `file_name`, `description`, `public_url`. |
| 2 | Multiple files per request? | **No.** Single file input `file` per store/update. Multiple attachments = multiple submissions; each adds one row. |
| 3 | File in separate file table / JSON / single column? | **Single table, single file_path per row.** No separate `ProjectAttachmentFile` table; no JSON column. |
| 4 | Does controller resolve project? | **store:** receives `Project $project` (injected). **update:** loads project by `$project_id`. **downloadAttachment($id):** does **not** resolve project — only `ProjectAttachment::findOrFail($id)`. So download has **no** project/province/canView. |
| 5 | Download/view methods? | **downloadAttachment($id)** exists; streams via `Storage::disk('public')->download(...)`. **No** viewFile (inline view); Blade “View” uses direct public URL. |
| 6 | ProjectPermissionHelper used? | **No.** Not used in any method. |
| 7 | Province isolation? | **No.** Not enforced. |
| 8 | ProjectStatus used? | **No.** |
| 9 | destroyFile()? | **No.** |
| 10 | destroy(projectId)? | **No.** No bulk delete of attachments in this controller. |

**Request lifecycle (current):**

- **Store:** ProjectController passes `Project`; optional `file`; validate → store on `public` disk under `project_attachments/{sanitized_project_type}/{project_id}` → create one `ProjectAttachment` row → redirect back.
- **Update:** Same as store (adds another attachment).
- **Download:** `findOrFail($id)` → check file exists on disk → `Storage::download()`. No project, no auth beyond middleware.

### 2.2 Model structure

**Model:** `App\Models\OldProjects\ProjectAttachment` (table `project_attachments`).

| Item | Status |
|------|--------|
| **Relationship to project** | ✅ `belongsTo(Project::class, 'project_id', 'project_id')` |
| **Separate file table** | ❌ None. One row = one file. |
| **Cascade delete** | ✅ Migration: `project_id` FK to `projects` with `onDelete('cascade')`. When project is deleted, attachment rows are removed. |
| **file_path** | ✅ Singular column per row. |
| **Storage path** | ✅ Uniform: `project_attachments/{sanitized_project_type}/{project_id}/{filename}` (public disk). |
| **deleting event** | ❌ **None.** Model has no `boot()` / `deleting` callback. Deleting a row does **not** remove the file from storage → **storage orphan risk** if per-file delete is added without cleanup. |

**Comparison with IES:** IES has parent `ProjectIESAttachments` (one per project) and child `ProjectIESAttachmentFile` (many per parent). Common has **no** parent record; `ProjectAttachment` rows are the only records and are already “per file.” So structure is **simpler** but **different**: no “parent attachment” to delete; deleting one `ProjectAttachment` is the per-file delete. Storage cleanup must be added (model `deleting` or controller).

### 2.3 Routes

**Command:** `php artisan route:list | grep attachment` (project-related):

| Method | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `projects/attachments/download/{id}` | `projects.attachments.download` | auth, role:executor,applicant,provincial,coordinator,general |

- **No** view route (inline).
- **No** delete route.
- Download goes through controller but controller does **not** enforce province or canView → **route-based but unguarded**.

### 2.4 Blade

**Edit:** `resources/views/projects/partials/Edit/attachment.blade.php`

| Item | Finding |
|------|---------|
| How file displayed | Loop over `$project->attachments`; each item shows file name, size, description, View + Download buttons. |
| Loop? | ✅ `@foreach($project->attachments as $index => $attachment)` |
| Multiple attachments? | ✅ Yes; multiple rows, each with own View/Download. |
| Delete button? | ❌ No. |
| View link | **Direct public URL:** `asset('storage/' . $attachment->file_path)` (same effect as `Storage::url()`). |
| Download link | **Route:** `route('projects.attachments.download', $attachment->id)`. |
| DOM id per row? | ❌ No (e.g. no `id="attachment-{{ $attachment->id }}"`). |
| fetch / form for delete? | N/A (no delete). |

**Show:** `resources/views/projects/partials/Show/attachments.blade.php` — same: View = `asset('storage/' . $attachment->file_path)`, Download = `route('projects.attachments.download', $attachment->id)`. No delete.

**Where used:** Edit partial is included in `Oldprojects/edit.blade.php` only for project types **not** in the individual list (i.e. when `!in_array($project->project_type, ['Individual - Ongoing...', 'Individual - Livelihood...', 'Individual - Access to Health', 'Individual - Initial...'])`). Show partial is included in `Oldprojects/show.blade.php` and PDF similarly for default/global section.

---

## 3. SECURITY FINDINGS

| # | Gap | Severity | Notes |
|---|-----|----------|--------|
| 1 | **IDOR on download** | **High** | Any authenticated user in the same role group can call `projects.attachments.download/{id}` with any attachment id; no project or province check. |
| 2 | **Direct URL exposure (View)** | **Critical** | View uses `asset('storage/' . file_path)`; file is served by web server without Laravel. Anyone with the URL can view. |
| 3 | **Missing province guard** | **Critical** | Download and (implicitly) View do not enforce province. Cross-province access possible. |
| 4 | **Missing canView** | **Critical** | No check that user is allowed to view the project. |
| 5 | **Missing canEdit** | **High** | store/update do not enforce canEdit; reliance on edit page access only. No delete to guard yet. |
| 6 | **Missing editable status** | **High** | Delete not implemented; when added, must block when project is approved (non-editable). |
| 7 | **Storage orphan risk** | **Medium** | Model has no `deleting` event; if per-file delete is added without cleanup, files remain on disk. |

---

## 4. APPROVAL & ROLE CONSTRAINTS

- **Common projects** (Development, CCI, RST, etc.) use the same **approval lifecycle** as others: status (e.g. draft, submitted, approved). `ProjectStatus::isEditable()` and `ProjectPermissionHelper::canEdit` are used elsewhere (e.g. budget lock, IES/ILP delete).
- **Edit page** is behind the same middleware (auth + roles: executor, applicant, provincial, coordinator, general). So attachment **store/update** are only reachable in context of project edit; no extra canEdit check in AttachmentController today.
- **Delete (when added)** should be **blocked** when project is approved (non-editable), same as IES/ILP, and require canEdit + province.
- **Province isolation** is the same as IES/IIES/IAH/ILP: `ProjectPermissionHelper::passesProvinceCheck($project, $user)`. Coordinator/general have broad access within the same rules (canView/canEdit as defined in the helper).

---

## 5. DATA INTEGRITY ASSESSMENT

- **Parent record:** There is no “parent attachment” record; the **project** is the parent. Each `ProjectAttachment` row is standalone. Per-file delete = delete one row; **project remains valid**. No “parent attachment” to keep in sync.
- **Attachments mandatory?** No. Controller treats attachment as optional (“If no file is uploaded, skip”). Zero attachments is valid.
- **Reporting / dependencies:** No direct reference to `project_attachments` in this audit; reporting may list or count attachments. Per-file delete only removes one row; no cascade to other tables. **Recommendation:** Confirm no report assumes a minimum number of attachments or specific attachment ids.
- **Foreign keys:** Only `project_id` → `projects.project_id` with `onDelete('cascade')`. Deleting a project removes attachment rows; no FK from elsewhere to `project_attachments`.
- **Cascade:** Adding per-file delete does not affect project cascade. Adding a **model `deleting` event** on `ProjectAttachment` to remove the file from storage would avoid orphaned files and keep behavior consistent with IES/ILP file models.

---

## 6. FEASIBILITY MATRIX

| Criterion | Status | Risk | Complexity |
|-----------|--------|------|------------|
| **Read hardening** | Feasible | Low | Low — add view route + guards to download; resolve project from `$attachment->project`; same pattern as ILP. |
| **Per-file delete** | Feasible | Low | Low — one table, one row per file; add `destroyAttachment($id)` with project → province → isEditable → canEdit; delete row; add storage cleanup (model or controller). |
| **Route addition** | Feasible | Low | Low — add GET view, keep GET download, add DELETE destroy; same middleware group as existing download. |
| **UI integration** | Feasible | Low | Low — add DOM id, Delete button, confirm + fetch to new delete route; same pattern as ILP. |
| **Data integrity** | Safe | Low | Parent is project; optional attachments; no mandatory count. Only add storage cleanup to avoid orphans. |
| **Migration risk** | Low | Low | Backward compatibility: keep public storage; new View link can use route. Old public URLs still work if already stored. |

---

## 7. RECOMMENDED IMPLEMENTATION STRATEGY (NO CODE)

### SECTION A — Read guard plan

- **viewFile($id):** Resolve `ProjectAttachment::findOrFail($id)` → `$attachment->project`; if no project, 404. Then `ProjectPermissionHelper::passesProvinceCheck($project, $user)` and `ProjectPermissionHelper::canView($project, $user)`; 403 on failure. Stream file with `response()->file($path)` for inline view.
- **downloadAttachment($id):** Same guard chain (resolve project from attachment, province, canView); then existing stream logic. Optionally rename to `downloadFile` for consistency with IES/ILP; route name can stay for backward compatibility.
- **Route:** Add GET `projects/attachments/view/{id}` (or `view/{id}`) for inline view. Keep existing download route but add guards inside controller.

### SECTION B — Delete guard plan

- **destroyAttachment($id)** (or destroyFile): Resolve `ProjectAttachment::findOrFail($id)` → `$attachment->project`. Province check, `ProjectStatus::isEditable($project->status)`, `ProjectPermissionHelper::canEdit($project, $user)`; 403 on failure. Then delete attachment row. **Storage cleanup:** Either (1) add `deleting` event on `ProjectAttachment` that deletes `Storage::disk('public')->delete($this->file_path)`, or (2) delete file in controller before/after `$attachment->delete()`. Prefer model event for consistency with IES/ILP.
- Return JSON `{ success: true, message: '...' }` for UI.

### SECTION C — Route plan

- Add in same middleware group as `projects.attachments.download`:
  - GET `projects/attachments/view/{id}` → viewFile (or inline view method) — name e.g. `projects.attachments.view`.
  - DELETE `projects/attachments/files/{id}` → destroyAttachment — name e.g. `projects.attachments.files.destroy`.
- Keep GET `projects/attachments/download/{id}`; add guards inside existing method.

### SECTION D — UI plan

- **Edit/attachment.blade.php:** Add `id="common-attachment-{{ $attachment->id }}"` (or similar) to the card/wrapper for each attachment. Replace View link from `asset('storage/' . ...)` to `route('projects.attachments.view', $attachment->id)`. Keep Download as `route('projects.attachments.download', $attachment->id)`. Add Delete button with `onclick="confirmRemoveCommonAttachment({{ $attachment->id }}, ...)"`. Add script: confirm dialog + fetch to DELETE route with CSRF; on success remove row from DOM.
- **Show/attachments.blade.php:** Replace View with `route('projects.attachments.view', $attachment->id)`. No Delete button.

### SECTION E — Documentation plan

- Document in same folder as this audit: implementation note (routes added, guards, model change for storage cleanup, Blade changes). Optional: short “Common attachments security migration” doc.

**Direct replication of IES pattern:** Yes, with two adaptations: (1) no separate file table — use `ProjectAttachment` as the deletable record and add storage cleanup (model `deleting`); (2) add a view route and switch View link from public URL to that route. No architectural redesign.

---

## 8. RISK ASSESSMENT

- **Current:** Download is IDOR-prone; View is direct public URL; no delete. Province and canView/canEdit not enforced for attachments.
- **After implementation:** Same as IES/ILP: province, canView on read; province, isEditable, canEdit on delete; View and Download both route-based and guarded. Legacy public URLs remain valid if already stored (backward compatible).
- **Risks:** (1) Forgetting storage cleanup on delete → orphaned files (mitigation: model `deleting` or controller cleanup). (2) Reports or external links assuming attachment count/ids (mitigation: confirm before rollout).

---

## 9. CONFIRMATION: NO CODE MODIFIED

This audit did **not** modify:

- Controllers  
- Routes  
- Blade  
- Models  
- Migrations  
- Any other code  

Only this documentation file was created.

---

## 10. DOCUMENTATION FILE PATH

`Documentations/V2/Attachments/Delete/Common_Project_Attachments_Feasibility.md`
