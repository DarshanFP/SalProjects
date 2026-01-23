# Image Upload Fields — Optimization Review

**Purpose:** Ensure all image upload fields across `resources/views` (partials and forms) store optimized images as per [Photo_Optimization_Service_Proposal.md](./Photo_Optimization_Service_Proposal.md) to reduce storage size.

**Reference:** Photo Optimization Service — WhatsApp-style minimal size: resize (max 1920px), re-encode to JPEG, **cap ≤ 350 KB**, strip EXIF (after GPS extraction), fallback to original on error.

---

## 1. Summary

| Category | Image Upload Fields | Using Optimization? | Action |
|----------|---------------------|---------------------|--------|
| **Report photos (monthly)** | Photos in create/edit/objectives | ✅ Yes | None — already use `ReportPhotoOptimizationService` |
| **Report photos (quarterly)** | Photos in 5 quarterly report types | ❌ No | Integrate `ReportPhotoOptimizationService` |
| **Project — Problem Tree** | `problem_tree_image` | ✅ Yes (different service) | Optional: align with 350 KB cap + EXIF strip |
| **Project — IES / IIES / IAH / ILP** | Document attachments (PDF + images) | ❌ No | Run optimization for image uploads only |
| **Report attachments (monthly)** | create/edit attachments | N/A | Do **not** accept images — PDF/DOC/XLS only |
| **Other project attachments** | NPD, scripts-edit, `attachments.blade` | N/A | Do **not** accept images |

---

## 2. Attachment / File Upload Fields — By Location

### 2.1 Fields That **Accept Images** (In Scope for Optimization)

#### A. Report Photos — Monthly

| Partial / View | Input Name(s) | Accept | Backend | Optimization |
|----------------|---------------|--------|---------|--------------|
| `reports/monthly/partials/create/photos.blade.php` | `photos[{{ $groupIndex }}][]` | `image/*` | `ReportController::handlePhotos()` | ✅ `ReportPhotoOptimizationService` |
| `reports/monthly/partials/edit/photos.blade.php` | `photos[{{ $groupIndex }}][]` | `image/*` | `ReportController::updatePhotos()` | ✅ `ReportPhotoOptimizationService` |
| `reports/monthly/ReportAll.blade.php` | `photos[]` | `image/*`, `image/jpeg, image/png` | `ReportController` | ✅ Via same flow |
| `reports/monthly/edit.blade.php` | `new_photos[]` | `image/jpeg, image/png` | `ReportController::update()` | ✅ Via same flow |
| `reports/monthly/developmentProject/reportform.blade.php` | (photos in form) | — | `MonthlyDevelopmentProjectController` | ✅ `ReportPhotoOptimizationService` |

**Verdict:** All monthly report photo uploads are already optimized. No change needed.

---

#### B. Report Photos — Quarterly (Not Optimized)

| View | Input Name | Accept | Controller | Optimization |
|------|------------|--------|------------|--------------|
| `reports/quarterly/developmentProject/reportform.blade.php` | `photos[]` | `image/*` | `DevelopmentProjectController` | ❌ `$file->store('ReportImages/Quarterly', 'public')` |
| `reports/quarterly/developmentLivelihood/reportform.blade.php` | `photos[]` | `image/*` | `DevelopmentLivelihoodController` | ❌ `$file->store('ReportImages/Quarterly', 'public')` |
| `reports/quarterly/institutionalSupport/reportform.blade.php` | `photos[]` | `image/*` | `InstitutionalSupportController` | ❌ `$file->store('ReportImages/Quarterly', 'public')` |
| `reports/quarterly/skillTraining/reportform.blade.php` | `photos[]` | `image/*` | `SkillTrainingController` | ❌ `$file->store('ReportImages/Quarterly', 'public')` |
| `reports/quarterly/womenInDistress/reportform.blade.php` | `photos[]` | `image/*` | `WomenInDistressController` | ❌ `$file->store('ReportImages/Quarterly', 'public')` |

**Actions:**
- In each controller, before `$file->store(...)`, call `ReportPhotoOptimizationService::optimize($file)`.
- If `optimize()` returns non-null: `Storage::disk('public')->put($path, $result['data'])` with a path under `ReportImages/Quarterly/` and a unique `.jpg` name.
- If `optimize()` returns null: keep current `$file->store('ReportImages/Quarterly', 'public')`.
- Reuse the same pattern as in `ReportController` / `MonthlyDevelopmentProjectController` (see `HandlesReportPhotoActivity` or in-place logic).

---

#### C. Project — Problem Tree Image

| Partial / View | Input Name | Accept | Controller | Optimization |
|----------------|------------|--------|------------|--------------|
| `projects/partials/key_information.blade.php` | `problem_tree_image` | `image/jpeg,image/jpg,image/png` | `KeyInformationController` | ✅ `ProblemTreeImageService` |
| `projects/partials/Edit/key_information.blade.php` | `problem_tree_image` | `image/jpeg,image/jpg,image/png` | `KeyInformationController` | ✅ `ProblemTreeImageService` |
| `projects/partials/Show/key_information.blade.php` | — (display only) | — | — | — |

**Verdict:** Already optimized. `ProblemTreeImageService` uses `config('attachments.problem_tree_optimization')`: `max_dimension`, `jpeg_quality` (no 350 KB cap, no EXIF strip).

**Optional alignment with Photo_Optimization_Service_Proposal:**
- Add 350 KB cap and iterative quality/dimension reduction.
- Add EXIF strip (and optional GPS extraction) if desired for consistency with report photos.

---

#### D. Project — IES / IIES / IAH / ILP (Documents That Allow Images)

These accept **PDF and images** (`.pdf,.jpg,.jpeg,.png`). Only the **image** part should be optimized; PDFs must be stored as-is.

| Partial | Input / Field Pattern | Accept | Backend (Model/Controller) | Optimization |
|---------|------------------------|--------|----------------------------|--------------|
| `projects/partials/IES/attachments.blade.php` | `{{ $field }}[]` (e.g. `aadhar_card[]`) | `.pdf,.jpg,.jpeg,.png` | `ProjectIESAttachments` (storeAs) | ❌ None |
| `projects/partials/Edit/IES/attachments.blade.php` | `{{ $field }}[]` | `.pdf,.jpg,.jpeg,.png` | `ProjectIESAttachments` | ❌ None |
| `projects/partials/IIES/attachments.blade.php` | `{{ $field }}[]` | `.pdf,.jpg,.jpeg,.png` | `ProjectIIESAttachments` (storeAs) | ❌ None |
| `projects/partials/Edit/IIES/attachments.blade.php` | `{{ $field }}[]` | `.pdf,.jpg,.jpeg,.png` | `ProjectIIESAttachments` | ❌ None |
| `projects/partials/IAH/documents.blade.php` | `{{ $field }}[]` | `.pdf,.jpg,.jpeg,.png` | `ProjectIAHDocuments` (storeAs) | ❌ None |
| `projects/partials/Edit/IAH/documents.blade.php` | `{{ $field }}[]` | `.pdf,.jpg,.jpeg,.png` | `ProjectIAHDocuments` | ❌ None |
| `projects/partials/ILP/attached_docs.blade.php` | `attachments[{{ $field }}][]` | `.pdf,.jpg,.jpeg,.png` | `ProjectILPAttachedDocuments` (storeAs) | ❌ None |
| `projects/partials/Edit/ILP/attached_docs.blade.php` | `attachments[{{ $field }}][]` | `.pdf,.jpg,.jpeg,.png` | `ProjectILPAttachedDocuments` | ❌ None |
| `projects/partials/OLdshow/IES/attachments.blade.php` | `aadhar_card`, `fee_quotation`, etc. | `.pdf,.jpg,.jpeg,.png` | Legacy | ❌ None |

**Actions:**
- In each **model** (`ProjectIESAttachments`, `ProjectIIESAttachments`, `ProjectIAHDocuments`, `ProjectILPAttachedDocuments`): before `storeAs`, detect if the file is an image (e.g. `image/jpeg`, `image/png`, or via `Str::lower($ext)` in `['jpg','jpeg','png','gif','webp','heic']`).
- If image: `$result = app(ReportPhotoOptimizationService::class)->optimize($file)`; if non-null, `Storage::disk('public')->put($path, $result['data'])` with `.jpg` and the same naming logic; else keep current `storeAs`.
- If PDF (or other non-image): keep current `storeAs` unchanged.
- **Note:** `ReportPhotoOptimizationService` is built for report photos; it can be reused for project document images. If project docs need different limits (e.g. higher 350 KB or different path layout), consider a thin wrapper or config override.

---

### 2.2 Fields That Do **Not** Accept Images (Out of Scope)

| Partial / View | Input Name(s) | Accept | Notes |
|----------------|---------------|--------|-------|
| `reports/monthly/partials/create/attachments.blade.php` | `attachment_files[]` | `.pdf, .doc, .docx, .xls, .xlsx` | No images — no optimization |
| `reports/monthly/partials/edit/attachments.blade.php` | `new_attachment_files[]` | `.pdf, .doc, .docx, .xls, .xlsx` | No images — no optimization |
| `reports/monthly/partials/view/attachments.blade.php` | — (view only) | — | — |
| `projects/partials/attachments.blade.php` | `file` | `.pdf, .doc, .docx` | No images |
| `projects/partials/NPD/attachments.blade.php` | `attachments[0][file]` etc. | `.pdf` | No images |
| `projects/partials/scripts-edit.blade.php` | `attachments[${index}][file]` | `.pdf,.doc,.docx,.xlsx` | No images |

**Verdict:** No image optimization required. If in the future these accept images (e.g. `report_attachments` in `config/attachments.php` includes jpg/png), then add the same optimize-before-store logic and document here.

---

## 3. Optimization Checklist (Per Photo_Optimization_Service_Proposal)

For each **image** storage path, the service should:

| Requirement | ReportPhotoOptimizationService | ProblemTreeImageService | Quarterly / IES–ILP |
|-------------|-------------------------------|-------------------------|----------------------|
| Resize (longest edge ≤ 1920px) | ✅ | ✅ (configurable) | 🔲 To add |
| Re-encode to JPEG | ✅ | ✅ | 🔲 To add |
| **Cap file size ≤ 350 KB** | ✅ | ❌ | 🔲 To add |
| Strip EXIF (after GPS extraction) | ✅ | ❌ | 🔲 To add (optional for IES–ILP) |
| Fallback to original on error | ✅ | ✅ | 🔲 To add |

---

## 4. Integration Pattern (From Proposal §8)

For any new integration (Quarterly, IES/IIES/IAH/ILP):

```php
$result = app(ReportPhotoOptimizationService::class)->optimize($file);

if ($result !== null) {
    $baseName = /* unique name */ . '.jpg';
    $path = $folderPath . '/' . $baseName;
    Storage::disk('public')->put($path, $result['data']);
} else {
    $path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');
}
// use $path for DB (e.g. photo_path, file_path)
```

- `optimize()` returns `['data' => binary, 'extension' => 'jpg', 'location' => ?string]` or `null`.
- Ensure `$path` has no leading `/` when using `Storage::disk('public')->put($path, $data)`.

---

## 5. Backend Storage Locations (For Reference)

| Area | Storage Path / Pattern |
|------|------------------------|
| Monthly report photos | `REPORTS/{project_id}/{report_id}/photos/{month_year}/` |
| Monthly development project photos | Same as above (via `MonthlyDevelopmentProjectController`) |
| Report attachments (PDF, etc.) | `REPORTS/{project_id}/{report_id}/attachments/{month_year}/` |
| Quarterly report photos | `ReportImages/Quarterly/` |
| Problem Tree | `{project attachment base path}/{project_id}_Problem_Tree.jpg` |
| IES / IIES / IAH / ILP | Project-specific dirs in `project_attachments` or similar |

---

## 6. Recommended Implementation Order

1. **Quarterly reports (high impact):** Integrate `ReportPhotoOptimizationService` in all 5 quarterly controllers (Development, DevelopmentLivelihood, InstitutionalSupport, SkillTraining, WomenInDistress) for `photos` arrays.
2. **Project IES / IIES / IAH / ILP (medium impact):** In the four models, add an “if image → optimize → put, else storeAs” branch before `storeAs`. Reuse `ReportPhotoOptimizationService`; only run for image MIME/types.
3. **Problem Tree (low priority):** Optionally align `ProblemTreeImageService` with the 350 KB cap and EXIF stripping from the proposal, or leave as-is if current sizing is acceptable.

---

## 7. Files Touched for This Review

**Partials/views with image uploads (in scope):**

- `resources/views/projects/partials/key_information.blade.php`
- `resources/views/projects/partials/Edit/key_information.blade.php`
- `resources/views/projects/partials/IES/attachments.blade.php`
- `resources/views/projects/partials/Edit/IES/attachments.blade.php`
- `resources/views/projects/partials/IIES/attachments.blade.php`
- `resources/views/projects/partials/Edit/IIES/attachments.blade.php`
- `resources/views/projects/partials/IAH/documents.blade.php`
- `resources/views/projects/partials/Edit/IAH/documents.blade.php`
- `resources/views/projects/partials/ILP/attached_docs.blade.php`
- `resources/views/projects/partials/Edit/ILP/attached_docs.blade.php`
- `resources/views/projects/partials/OLdshow/IES/attachments.blade.php`
- `resources/views/reports/monthly/partials/create/photos.blade.php`
- `resources/views/reports/monthly/partials/edit/photos.blade.php`
- `resources/views/reports/monthly/ReportAll.blade.php`
- `resources/views/reports/monthly/edit.blade.php`
- `resources/views/reports/monthly/developmentProject/reportform.blade.php`
- `resources/views/reports/quarterly/developmentProject/reportform.blade.php`
- `resources/views/reports/quarterly/developmentLivelihood/reportform.blade.php`
- `resources/views/reports/quarterly/institutionalSupport/reportform.blade.php`
- `resources/views/reports/quarterly/skillTraining/reportform.blade.php`
- `resources/views/reports/quarterly/womenInDistress/reportform.blade.php`

**Partials/views with file uploads that do NOT accept images (out of scope):**

- `resources/views/reports/monthly/partials/create/attachments.blade.php`
- `resources/views/reports/monthly/partials/edit/attachments.blade.php`
- `resources/views/projects/partials/attachments.blade.php`
- `resources/views/projects/partials/NPD/attachments.blade.php`
- `resources/views/projects/partials/scripts-edit.blade.php`

---

*Document version: 1.0 — Image Upload Fields Optimization Review (aligned with Photo_Optimization_Service_Proposal).*
