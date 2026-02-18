# Wave 1 — IES Download & View Hardening

## 1. Executive Summary

**Scope:** Security hardening of `downloadFile($fileId)` and `viewFile($fileId)` in `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` only.

**Objective:** Enforce Province Isolation, Editable Status, and `canEdit()` before returning any file response. No new features, no route changes, no changes to other controllers or to `destroy()`.

**Result:** Both methods now run a strict guard chain (project resolve → province check → status editable → canEdit). Failure at any step returns 403. File resolution and response format (download vs inline stream) are unchanged.

---

## 2. Pre-Hardening Behavior

| Aspect | downloadFile | viewFile |
|--------|--------------|----------|
| **File loading** | `ProjectIESAttachmentFile::findOrFail($fileId)` | Same |
| **Project resolution** | None | None |
| **ProjectPermissionHelper** | Not used | Not used |
| **ProjectStatus check** | None | None |
| **Province check** | None | None |
| **Response type** | `Storage::disk('public')->download($path, $name)` | `response($content, 200)` with `Content-Type` and `Content-Disposition: inline` |

**Security gap:** Any authenticated user could download or view any IES attachment by knowing or guessing a valid `$fileId`, including files from other provinces or from approved (non-editable) projects.

---

## 3. Identified Security Gaps

1. **No province isolation** — Users could access files belonging to projects in other provinces.
2. **No editable-status enforcement** — Approved (or other non-editable) projects’ attachments were still downloadable/viewable as if the project were editable.
3. **No permission check** — No use of `ProjectPermissionHelper::canEdit()` (or province/status); role and ownership were not enforced for download/view.
4. **IDOR risk** — Random or enumerated file IDs from other projects could be used to access files without any project-level authorization.

---

## 4. Guard Chain Implemented

Both methods now run the same guard sequence **immediately after** `findOrFail($fileId)` and **before** any storage check or file response:

1. **Resolve project**  
   `$project = $file->project ?? $file->iesAttachment?->project`  
   If no project can be resolved → 404 (JSON response, same as missing file record).

2. **Province**  
   `if (!ProjectPermissionHelper::passesProvinceCheck($project, $user)) abort(403)`

3. **Editable status**  
   `if (!ProjectStatus::isEditable($project->status)) abort(403)`

4. **Edit permission**  
   `if (!ProjectPermissionHelper::canEdit($project, $user)) abort(403)`

Only after all four pass do we proceed to the existing logic: storage existence check, then the same response as before (download or inline stream).

---

## 5. Exact Methods Modified

- **File:** `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`
- **Methods:** `downloadFile($fileId)`, `viewFile($fileId)`
- **Additions:**
  - Use statements: `App\Constants\ProjectStatus`, `App\Helpers\ProjectPermissionHelper`, `Illuminate\Support\Facades\Auth`
  - In both methods: project resolution, then province → status → canEdit guards; no change to storage disk, response format, or exception handling for missing file / disk errors.

**Not modified:** `destroy()`, routes, middleware, storage disk logic, any other controller (IIES, IAH, ILP, Generic, Reports).

---

## 6. Why Guard Order Matters

1. **Project first** — All subsequent checks need the project (and its `province_id`, `status`, `user_id`, `in_charge`). Resolving project once avoids repeated resolution and keeps a single source of truth.
2. **Province before status** — Province is the broadest isolation boundary; failing it early avoids leaking any project metadata.
3. **Status before canEdit** — `canEdit()` already implies editable status; checking status explicitly keeps policy clear and fails non-editable projects (e.g. approved) with 403 even for admin/general if we ever tighten behavior.
4. **canEdit last** — Encodes role and ownership (executor/applicant vs provincial/coordinator/admin/general) after boundary and status are satisfied.

---

## 7. Security Scenarios Validated

| Scenario | Expected | Implementation |
|----------|----------|----------------|
| Cross-province user requests file from project in another province | 403 | `passesProvinceCheck` fails → abort(403). |
| User requests file belonging to an approved project | 403 | `ProjectStatus::isEditable($project->status)` false → abort(403). |
| Random/other project’s file ID, same province but not owner/in-charge (executor) | 403 | `canEdit` false for executor → abort(403). |
| Owner of editable project in same province | Allowed | Province, status, and canEdit pass → existing download/view response. |
| In-charge of editable project in same province | Allowed | Same as owner. |
| Provincial/Coordinator for editable project in their province | Allowed | canEdit true → response returned. |

---

## 8. Confirmation: Scope Limited to IES Only

- **Touched controller:** `App\Http\Controllers\Projects\IES\IESAttachmentsController` only.
- **Touched methods:** `downloadFile`, `viewFile` only.
- **Not touched:** IIES, IAH, ILP, Generic, Reports controllers; routes; middleware; `destroy()`; any trait or shared abstraction.

---

## 9. Regression Check (No Functional Change to Downloads)

- **Response format:** Unchanged. Download still uses `Storage::disk('public')->download($file->file_path, $file->file_name)`. View still returns `response($fileContent, 200)` with `Content-Type` and `Content-Disposition: inline`.
- **Storage:** Same `Storage::disk('public')` and same path/name usage.
- **Errors:** 404 for missing file record or missing project; 404 for file not on disk; 500 on other exceptions. New behavior: 403 when province, status, or canEdit fails.
- **Eligible users** (same province, editable project, canEdit): Behavior unchanged; they still get the same download or inline response as before.

---

## 10. Policy Adjustment — Read Access (Wave 1.1)

### Why editable status was removed from read

Read operations (download and view) must not depend on project **editable** status. Attachments are project evidence and must remain viewable after approval (e.g. for coordinators, provincials, or executors viewing their own submitted/approved project). Blocking read when `ProjectStatus::isEditable()` is false was correct for **mutation** (store/update/destroy) but wrong for **read** — it prevented legitimate viewing of approved projects.

### Why mutation vs read must be separated

- **Mutation (store, update, destroy):** Must enforce editable status and `canEdit()` so only authorised users can change attachments on projects that are still in an editable state.
- **Read (download, view):** Must enforce only **access** (province + role-based view). Anyone who may legitimately view the project may view its attachments, regardless of approval. No `ProjectStatus::isEditable()` and no `canEdit()` for read.

### Final guard chain for read (downloadFile / viewFile)

1. `$file = ProjectIESAttachmentFile::findOrFail($fileId)`
2. Resolve `$project` (`$file->project ?? $file->iesAttachment?->project`); if none → 404
3. `$user = Auth::user()`
4. `if (!ProjectPermissionHelper::passesProvinceCheck($project, $user)) abort(403)`
5. `if (!ProjectPermissionHelper::canView($project, $user)) abort(403)`

**Not used for read:** `ProjectStatus::isEditable()`, `ProjectPermissionHelper::canEdit()`.

**Effect of canView:** Province must match (already enforced above); then admin/coordinator/provincial/general can view; executor/applicant only if owner or in_charge. ID guessing remains blocked (wrong project → no access).

### Confirm approved projects now readable

- **Approved project:** User in same province with view access (owner, in_charge, provincial, coordinator, admin, general) → download/view **allowed**.
- **Editable project:** Same rules → allowed.
- **Cross-province / wrong project / executor not owner-in-charge:** Still 403.

---

*Wave 1 — Production-safe, security-only hardening. No feature additions, no delete button, no new routes, no abstractions. Wave 1.1 — Read policy: view allowed for approved projects; province + canView only.*
