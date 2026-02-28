# Project Attachment Controllers — Guard-Chain Verification Audit

**Rule:** Every implementation step must generate or update a corresponding MD file in this same folder documenting changes made, files touched, and test results.

**Audit Date:** 2025-02-23  
**Scope:** PROJECT ATTACHMENT controllers only (view, download, destroy methods)  
**Type:** Verification only — no code changes.

---

## 1. Executive Summary

| Metric | Result |
|--------|--------|
| **Controllers audited** | 5 |
| **View/Download methods** | 10 (all OK) |
| **Destroy (per-file) methods** | 5 (all OK for province + canEdit) |
| **Invariant violations** | 0 |
| **Order violations** | 0 |
| **ProjectAccessService direct use** | None |
| **Role-based switch** | None in attachment controllers |
| **Security risk level** | **LOW** |

**Conclusion:** All project attachment controllers that handle **view**, **download**, and **per-file destroy** implement the correct guard chain. For read operations (view/download), `passesProvinceCheck` is always called before `canView`. For destroy operations, `passesProvinceCheck` is called before `isEditable` and `canEdit`. No violations found.

---

## 2. STEP 1 — Identified Controllers

| Controller | Full File Path |
|------------|----------------|
| **AttachmentController** | `app/Http/Controllers/Projects/AttachmentController.php` |
| **IESAttachmentsController** | `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` |
| **IIESAttachmentsController** | `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` |
| **IAHDocumentsController** | `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` |
| **ILPAttachedDocumentsController** | `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` (aliased in `routes/web.php` as `ILPAttachedDocumentsController`) |

---

## 3. STEP 2 — Controller-by-Controller Table

### 3.1 AttachmentController

| Method | Calls passesProvinceCheck | Calls canView | Order Correct | Notes |
|--------|---------------------------|---------------|---------------|-------|
| **viewAttachment** | Yes (L158) | Yes (L161) | ✅ Yes | `passesProvinceCheck` → `canView` |
| **downloadAttachment** | Yes (L192) | Yes (L195) | ✅ Yes | Same |
| **destroyAttachment** | Yes (L245) | N/A (uses canEdit L253) | ✅ N/A | destroy uses `passesProvinceCheck` → `isEditable` → `canEdit` |

### 3.2 IESAttachmentsController

| Method | Calls passesProvinceCheck | Calls canView | Order Correct | Notes |
|--------|---------------------------|---------------|---------------|-------|
| **viewFile** | Yes (L352) | Yes (L355) | ✅ Yes | `passesProvinceCheck` → `canView` |
| **downloadFile** | Yes (L294) | Yes (L297) | ✅ Yes | Same |
| **destroyFile** | Yes (L210) | N/A (uses canEdit L234) | ✅ N/A | `passesProvinceCheck` → `isEditable` → `canEdit` |

### 3.3 IIESAttachmentsController

| Method | Calls passesProvinceCheck | Calls canView | Order Correct | Notes |
|--------|---------------------------|---------------|---------------|-------|
| **viewFile** | Yes (L330) | Yes (L333) | ✅ Yes | `passesProvinceCheck` → `canView` |
| **downloadFile** | Yes (L272) | Yes (L275) | ✅ Yes | Same |
| **destroyFile** | Yes (L399) | N/A (uses canEdit L423) | ✅ N/A | `passesProvinceCheck` → `isEditable` → `canEdit` |

### 3.4 IAHDocumentsController

| Method | Calls passesProvinceCheck | Calls canView | Order Correct | Notes |
|--------|---------------------------|---------------|---------------|-------|
| **viewFile** | Yes (L357) | Yes (L360) | ✅ Yes | `passesProvinceCheck` → `canView` |
| **downloadFile** | Yes (L299) | Yes (L302) | ✅ Yes | Same |
| **destroyFile** | Yes (L422) | N/A (uses canEdit L446) | ✅ N/A | `passesProvinceCheck` → `isEditable` → `canEdit` |

### 3.5 ILPAttachedDocumentsController (AttachedDocumentsController)

| Method | Calls passesProvinceCheck | Calls canView | Order Correct | Notes |
|--------|---------------------------|---------------|---------------|-------|
| **viewFile** | Yes (L275) | Yes (L278) | ✅ Yes | `passesProvinceCheck` → `canView` |
| **downloadFile** | Yes (L304) | Yes (L307) | ✅ Yes | Same |
| **destroyFile** | Yes (L333) | N/A (uses canEdit L341) | ✅ N/A | `passesProvinceCheck` → `isEditable` → `canEdit` |

---

## 4. STEP 3 — Invariant Verification

**Invariant:** `passesProvinceCheck` MUST be called BEFORE `canView` for read operations (view/download).

| Check | Result |
|-------|--------|
| Only canView called (no passesProvinceCheck) | ❌ No violations |
| passesProvinceCheck called after canView | ❌ No order violations |
| Both called in correct order | ✅ All 10 view/download methods |
| Neither called | ❌ No critical issues |

For **destroy** methods, the correct chain is: `passesProvinceCheck` → `isEditable` → `canEdit`. `canView` is not required for delete; all five destroyFile/destroyAttachment methods follow this pattern.

---

## 5. STEP 4 — Project Resolution

### AttachmentController
- **Attachment** → Project: `$attachment->project` (L152, L186)
- Null check: Yes (L153–155, L187–189)
- Alternate bypass: None

### IESAttachmentsController
- **File** → Project: `$file->project ?? $file->iesAttachment?->project` (L286, L346)
- Null check: Yes (L287–291, L347–351)
- Alternate bypass: None

### IIESAttachmentsController
- **File** → Project: `$file->project ?? $file->iiesAttachment?->project` (L264, L324)
- Null check: Yes (L265–269, L325–329)
- Alternate bypass: None

### IAHDocumentsController
- **File** → Project: `$file->project ?? $file->iahDocument?->project` (L291, L351)
- Null check: Yes (L292–296, L352–356)
- Alternate bypass: None

### ILPAttachedDocumentsController
- **File** → Project: `$file->project ?? $file->ilpDocument?->project` (L270, L302, L330)
- Null check: Yes (L271–274, L303–306, L331–334)
- Alternate bypass: None

---

## 6. Guard Chain Diagram

```
READ (view / download)
─────────────────────────────────────────────────────────────
  [Resolve attachment/file] → [Resolve project]
       ↓
  [project === null?] → 404
       ↓
  passesProvinceCheck(project, user) → 403 if false
       ↓
  canView(project, user) → 403 if false
       ↓
  [Serve file]

DESTROY (per-file delete)
─────────────────────────────────────────────────────────────
  [Resolve file] → [Resolve project]
       ↓
  [project === null?] → 404
       ↓
  passesProvinceCheck(project, user) → 403 if false
       ↓
  ProjectStatus::isEditable(project->status) → 403 if false
       ↓
  canEdit(project, user) → 403 if false
       ↓
  [Delete file]
```

---

## 7. Violations Found

**None.** All view, download, and per-file destroy methods enforce the guard chain correctly.

---

## 8. Additional Findings

### 8.1 ProjectAccessService
- **Direct use in attachment controllers:** None.
- `ProjectPermissionHelper::canView` delegates to `ProjectAccessService::canViewProject` internally (see `ProjectPermissionHelper.php` L95–98).

### 8.2 Role-based switch
- No role-based branching in attachment controller logic.
- Role handling is encapsulated in `ProjectPermissionHelper` and `ProjectAccessService`.

### 8.3 Early return before guard chain
- Only early returns are for:
  - Project null → 404
  - File not found on disk → 404
- These occur before guard execution only when the resource is absent; no bypass path.

### 8.4 Parent destroy methods (out of scope)
The following bulk destroy methods (`destroy($projectId)`) exist and are **not** audited in this report:
- `IESAttachmentsController::destroy` (L172–186)
- `IIESAttachmentsController::destroy` (L226–253)
- `IAHDocumentsController::destroy` (L237–264)
- `ILPAttachedDocumentsController::destroy` (L229–262)

These are typically invoked from project edit flows that enforce access at the parent controller level. A separate audit may be warranted if these routes are directly exposed.

---

## 9. Security Risk Level

**LOW** — All audited attachment view/download/destroy methods enforce province isolation and permission checks correctly.

---

## 10. Recommendation

**No change required** for the audited methods. The guard chain is correctly implemented across all five attachment controllers.

---

## 11. Next Step Guidance

| Phase | Safe to proceed? | Notes |
|-------|------------------|-------|
| **Phase C** | ✅ Yes | Attachment controllers are correctly guarded. |
| **Phase D** | ✅ Yes | No guard-chain changes needed in attachment controllers. |

**Phase C + D:** Proceed with confidence for attachment-related flows. Ensure any new routes or controllers added in these phases follow the same pattern: `passesProvinceCheck` → `canView` (for read) or `passesProvinceCheck` → `isEditable` → `canEdit` (for destroy).
