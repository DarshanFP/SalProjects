# Phase B — Controller Guard Alignment

**Rule: Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.**

---

**Phase:** B  
**Objective:** Ensure all project attachment controllers use the consistent guard chain: passesProvinceCheck → canView → canViewProject.  
**Scope:** Project attachments only. Reports OUT OF SCOPE.

---

## 1. Objective

Align every project attachment controller (AttachmentController, IESAttachmentsController, IIESAttachmentsController, IAHDocumentsController, ILPAttachedDocumentsController) so that both view and download methods enforce:

1. `ProjectPermissionHelper::passesProvinceCheck($project, $user)`
2. `ProjectPermissionHelper::canView($project, $user)` (which delegates to `ProjectAccessService::canViewProject`)

No role-based switch, no manual `user->role` checks, no status-based restrictions for read operations.

---

## 2. Scope — Exact Files Involved

| File | Methods to Audit/Align |
|------|------------------------|
| `app/Http/Controllers/Projects/AttachmentController.php` | viewAttachment, downloadAttachment |
| `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` | viewFile, downloadFile |
| `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` | viewFile, downloadFile |
| `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` | viewFile, downloadFile |
| `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` | viewFile, downloadFile |

**Destroy methods:** Ensure they use `canEdit` (not `canView`) and `ProjectStatus::isEditable` — out of scope for download fix but verify no accidental use of canView for delete.

---

## 3. What Will NOT Be Touched

- Routes
- ProjectPermissionHelper
- ProjectAccessService
- ReportAttachmentController
- Report logic
- Store/update logic in attachment controllers

---

## 4. Pre-Implementation Checklist

- [ ] Phase A complete (routes verified)
- [ ] Document current guard flow for each controller's view and download methods
- [ ] Identify any controller using manual role switch or status check for read
- [ ] Ensure project resolution from attachment/file is correct (project_id, relationships)

---

## 5. Step-by-Step Implementation Plan

### Step B1: AttachmentController

1. **viewAttachment($id):**
   - Resolve: `$attachment = ProjectAttachment::findOrFail($id);` → `$project = $attachment->project`
   - Add/verify: `ProjectPermissionHelper::passesProvinceCheck($project, Auth::user())` → abort(403) on fail
   - Add/verify: `ProjectPermissionHelper::canView($project, Auth::user())` → abort(403) on fail
   - Remove any manual `user->role` or status-based restriction for view

2. **downloadAttachment($id):**
   - Same guard chain as viewAttachment
   - Ensure both use identical logic

### Step B2: IESAttachmentsController

1. **viewFile($fileId):**
   - Resolve project: `$file->project ?? $file->iesAttachment?->project`
   - Add/verify: passesProvinceCheck, canView
   - Remove any role-based bypass or restriction

2. **downloadFile($fileId):**
   - Same as viewFile

### Step B3: IIESAttachmentsController

1. **viewFile($fileId):**
   - Resolve: `$file->project ?? $file->iiesAttachment?->project`
   - Add/verify: passesProvinceCheck, canView

2. **downloadFile($fileId):**
   - Same as viewFile

### Step B4: IAHDocumentsController

1. **viewFile($fileId):**
   - Resolve: `$file->project ?? $file->iahDocument?->project`
   - Add/verify: passesProvinceCheck, canView

2. **downloadFile($fileId):**
   - Same as viewFile

### Step B5: ILPAttachedDocumentsController

1. **viewFile($fileId):**
   - Resolve: `$file->project ?? $file->ilpDocument?->project`
   - Add/verify: passesProvinceCheck, canView

2. **downloadFile($fileId):**
   - Same as viewFile

### Step B6: Validate Project Resolution Consistency

**Critical:** Project resolution from attachment/file must be consistent across all attachment types. If one controller resolves project via `$file->project` and another via `$file->iesAttachment?->project`, ensure both paths yield the same Project instance. Audit and document:

- [ ] AttachmentController: `$attachment->project` (direct relationship)
- [ ] IESAttachmentsController: `$file->project ?? $file->iesAttachment?->project`
- [ ] IIESAttachmentsController: `$file->project ?? $file->iiesAttachment?->project`
- [ ] IAHDocumentsController: `$file->project ?? $file->iahDocument?->project`
- [ ] ILPAttachedDocumentsController: `$file->project ?? $file->ilpDocument?->project`

All must resolve to a valid Project with no null dereference. Add null check: `if (! $project) { abort(404); }` before guards.

### Step B7: Standard Guard Pattern

Use this pattern in every view/download method. **Order matters:** passesProvinceCheck MUST be called before canView (see Phase C).

```php
$user = Auth::user();
if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) {
    abort(403);
}
if (! ProjectPermissionHelper::canView($project, $user)) {
    abort(403);
}
```

### Step B8: Verify destroy Uses canEdit

- destroyAttachment / destroyFile: Must use `canEdit`, `isEditable`, and `passesProvinceCheck`
- Do NOT use `canView` for delete — coordinator/provincial may have view but not edit
- Document: Coordinator and provincial are typically not given destroy permission (route may allow; controller must enforce canEdit)

---

## 6. Security Impact Analysis

| Change | Risk | Mitigation |
|--------|------|------------|
| Add missing passesProvinceCheck | Blocks cross-province access | Correct behavior |
| Add missing canView | Blocks unauthorized access | Correct behavior |
| Remove role-based bypass | May break if coordinator was given special path | Ensure ProjectAccessService allows coordinator (Phase D) |

---

## 7. Performance Impact Analysis

- Two extra calls per request: passesProvinceCheck, canView (which calls canViewProject)
- getAccessibleUserIds is cached per request in ProjectAccessService
- Negligible impact

---

## 8. Rollback Strategy

- Revert controller files from version control
- Run tests to ensure executor flow unchanged

---

## 9. Deployment Checklist

- [ ] All 5 controllers updated and tested
- [ ] No syntax errors
- [ ] Executor download still works
- [ ] Provincial and coordinator download tested manually

---

## 10. Regression Checklist

- [ ] AttachmentController: view, download (DP)
- [ ] IESAttachmentsController: view, download
- [ ] IIESAttachmentsController: view, download
- [ ] IAHDocumentsController: view, download
- [ ] ILPAttachedDocumentsController: view, download
- [ ] Executor: all project types
- [ ] Provincial: owner project, in_charge project
- [ ] Coordinator: all project types

---

## 11. Sign-Off Criteria

- [ ] Every view and download method in all 5 controllers uses passesProvinceCheck + canView
- [ ] No manual role switch for read operations
- [ ] No status-based restriction for view/download
- [ ] destroy methods use canEdit (not canView)
- [ ] Phase_B_Implementation_Summary.md created and updated
