# Photo Rearrangement — Status: Completed and Remaining

Overview of what has been **completed** and what is **yet to be completed** for the Photo Rearrangement work (activity mapping, optimization, location, view/export).

---

## 1. Completed

### 1.1 Phase 1: Database and models

| Item | Status |
|------|--------|
| Migration `add_activity_id_and_photo_location_to_dp_photos_table` | ✅ Done |
| `activity_id` (nullable, FK to `DP_Activities.activity_id`, `nullOnDelete`) | ✅ Done |
| `photo_location` (nullable, string 500) | ✅ Done |
| `DPPhoto`: `activity_id`, `photo_location` in `$fillable`; `belongsTo(DPActivity)` | ✅ Done |
| `DPActivity`: `hasMany(DPPhoto)` | ✅ Done |
| Migration run (`php artisan migrate`) | ✅ Done |

---

### 1.2 Phase 2: Photo optimization service

| Item | Status |
|------|--------|
| `ReportPhotoOptimizationService` with `optimize(UploadedFile\|string): ?array` | ✅ Done |
| GPS extraction from EXIF **before** Intervention; `photo_location` in return | ✅ Done |
| Resize (`scaleDown`), re-encode to JPEG, `removeProfile()` to strip other EXIF | ✅ Done |
| **350 KB cap:** iterative quality (82→70→60→50) and dimensions (1920→1280→960) | ✅ Done |
| Return `['data','extension','location']` or `null` on error; fallback to original when `null` | ✅ Done |
| `config/report_photos.php` (enabled, max_dimension, jpeg_quality, max_file_size_kb, strip_profile, etc.) | ✅ Done |
| `AppServiceProvider`: singleton binding for `ReportPhotoOptimizationService` | ✅ Done |

---

### 1.3 Phase 3: Optimization in monthly photo flow

| Item | Status |
|------|--------|
| `ReportController::handlePhotos()`: optimize, `put`/`storeAs`, `photo_location` | ✅ Done |
| `ReportController::updatePhotos()`: same for new photos | ✅ Done |
| `MonthlyDevelopmentProjectController::store`: optimize, `photo_location`, activity-based logic | ✅ Done |
| `DPPhoto::create` includes `photo_location` (and `activity_id` where applicable) | ✅ Done |

---

### 1.4 Phase 4: View and export — show `photo_location`

| Item | Status |
|------|--------|
| `partials/view/photos.blade.php`: `photo_location` below image (font-size 1.5rem) | ✅ Done |
| `ExportReportController::preparePhotosForPdfOptimized`: `'photo_location'` in photo data | ✅ Done |
| `PDFReport/photos.blade.php` (and related): `photo_location` with smaller font (e.g. 10pt) | ✅ Done |
| `ExportReportController` DOC / `addPhotosSection`: location in smaller font when present | ✅ Done |

---

### 1.5 Phase 5: Activity mapping — backend

| Item | Status |
|------|--------|
| `photo_activity_id[groupIndex]` supported: `"obj:act"`, `"__unassigned__"`, or `activity_id` | ✅ Done |
| **3‑per‑activity:** truncate to `3 - existingCount` when over; applied in create and update | ✅ Done |
| **Activity-based filename:** `{ReportID}_{MMYYYY}_{Obj}_{Act}_{Inc}.{ext}`; Unassigned `00_00` | ✅ Done |
| `HandlesReportPhotoActivity` trait: `resolveActivityId`, `resolveActivityIdFromIndices`, `buildActivityBasedFilename` | ✅ Done |
| `ReportController` uses trait; `handlePhotos` and `updatePhotos` use it | ✅ Done |
| `StoreMonthlyReportRequest` / `UpdateMonthlyReportRequest`: `photo_activity_id`, `photo_activity_id.*` | ✅ Done |
| `description` only for Unassigned (`activity_id === null`); else `null` | ✅ Done |

---

### 1.6 Phase 6: Activity mapping — create and edit UI

| Item | Status |
|------|--------|
| `partials/create/photos.blade.php`: “Link to Activity” `<select>` instead of description textarea | ✅ Done |
| Options: “— Unassigned —” and “Objective X – Activity Y” from DOM | ✅ Done |
| `getReportActivities()` and `window.refreshPhotoActivityOptions()`; 0-based and 1-based via `objectivesIndexBase` | ✅ Done |
| `reindexPhotoGroups()` and `addPhotoGroup()`: `photo_activity_id` select name/data-group-index | ✅ Done |
| `partials/create/objectives.blade.php`: `reindexActivities` calls `refreshPhotoActivityOptions` | ✅ Done |
| `partials/edit/photos.blade.php`: activity select for existing (server-rendered) and new groups (JS); `refreshPhotoActivityOptions` only in `#new-photos-container` | ✅ Done |
| `partials/edit/objectives.blade.php`: `reindexActivities` calls `refreshPhotoActivityOptions` | ✅ Done |

---

### 1.7 Phase 7: View — photos under activities

| Item | Status |
|------|--------|
| `partials/view/objectives.blade.php`: photos under each `$activity`; image, “View Full Size”, `photo_location` below | ✅ Done |
| `partials/view/photos.blade.php`: **Unassigned** only (`activity_id` null); legacy `$groupedPhotos` fallback | ✅ Done |
| `ReportController::show()`: eager-load `objectives.activities.photos`; pass `$unassignedPhotos` | ✅ Done |
| `show.blade.php`: `partials/view/objectives` and `partials/view/photos` with `unassignedPhotos` | ✅ Done |

---

### 1.8 Phase 8: Export — by activity and location

| Item | Status |
|------|--------|
| `preparePhotosForPdfOptimized($report)`: group by “Objective X – Activity Y” and “Unassigned”; `photo_location` | ✅ Done |
| `preparePhotosForDoc($report)`: same grouping and `photo_location` | ✅ Done |
| `downloadPdf` / `downloadDoc`: pass `$report`; eager-load `objectives.activities.photos` | ✅ Done |
| PDF/DOC templates: `photo_location` below each photo when present | ✅ Done |

---

### 1.9 Form and controller alignments (beyond core phases)

| Item | Status |
|------|--------|
| **ReportCommonForm:** Photos block replaced with `@include('reports.monthly.partials.create.photos')`; submits to `ReportController::store` | ✅ Done |
| **monthly/developmentProject/reportform.blade.php:** `@include(..., ['objectivesIndexBase' => true])`; removed old photo JS | ✅ Done |
| **MonthlyDevelopmentProjectController:** `HandlesReportPhotoActivity`; `createForm` returns `developmentProject.reportform`; `store` supports `photos[groupIndex][]`, `photo_activity_id`, 3‑per‑activity, activity-based filename, `report_month_year` → `reporting_period_*` | ✅ Done |
| Routes: `monthly.developmentProject.create` (→`createForm`), `monthly.developmentProject.store` | ✅ Done |
| `partials/create/photos`: `data-objectives-one-based` and `getReportActivities` 1-based support for reportform | ✅ Done |
| `project_id` in reportform: `$project->project_id` | ✅ Done |

---

### 1.10 Supporting files and fixes

| Item | Status |
|------|--------|
| `app/Traits/HandlesReportPhotoActivity.php` | ✅ Done |
| `ReportController::updatePhotos()`: `$photo_id` → `$photoId` in delete logging | ✅ Done |
| `ReportCommonForm` and developmentProject reportform: `refreshPhotoActivityOptions` on add/remove objective and activity | ✅ Done |

---

## 2. Yet to be completed

### 2.1 Phase 9: Quarterly reports (deferred)

| Task | Status |
|------|--------|
| Add `activity_id` and `photo_location` to quarterly photo tables (RQDPPhoto, RQDLPhoto, RQISPhoto, RQSTPhoto, RQWDPhoto, etc.) | ❌ Not started |
| Migrations for those columns; models updated | ❌ Not started |
| In each quarterly controller’s photo store/update: `ReportPhotoOptimizationService::optimize`, store `photo_location` | ❌ Not started |
| Optionally: activity-based naming and `activity_id` when objectives/activities exist | ❌ Not started |
| Quarterly create/edit: activity selector and 3‑per‑activity (if adopted) | ❌ Not started |
| Quarterly show and export: group by activity and show `photo_location` | ❌ Not started |

---

### 2.2 Automated and unit tests

| Task | Status |
|------|--------|
| Phase 2: Unit or feature test for `ReportPhotoOptimizationService::optimize` (JPEG + location when EXIF GPS; `null` on invalid/mock error) | ❌ Not done |

---

### 2.3 Manual tests (from phase deliverables)

| Task | Status |
|------|--------|
| Phase 2: Manual check that optimization behaves as expected (e.g. with EXIF) | ❌ Not done |
| Phase 3: Create/edit with photos with and without EXIF; confirm smaller JPEGs and `photo_location` in DB | ❌ Not done |
| Phase 4: View and export with photo that has EXIF GPS and one without | ❌ Not done |
| Phase 6: Create/edit, assign/unassign activities, hit 3‑photo limit | ❌ Not done |
| Phase 7: View regression: image display, modals, “View Full Size” | ❌ Not done |
| Phase 8: Export with assigned and unassigned photos, with and without location | ❌ Not done |

---

### 2.4 Pre-requisites and environment

| Task | Status |
|------|--------|
| Confirm PHP `exif` enabled for `exif_read_data` | ❌ Not confirmed |
| Confirm `intervention/image` and GD (or Imagick) as used in the project | ❌ Not confirmed |

---

### 2.5 Optional / nice-to-have

| Task | Status |
|------|--------|
| Phase 6: Client-side 3‑per‑activity (disable/warn when activity already has 3 photos in the form) | ❌ Not done |
| Phase 8: Weave PDF “Photos” into objectives/activities (photos after each activity’s text) | ❌ Not done; current grouping by activity and “Unassigned” is in place |

---

### 2.6 High-level testing checklist (from plan)

| Test | Status |
|------|--------|
| Create monthly report: several photo groups, different activities, some Unassigned, some with EXIF GPS; check `activity_id`, `photo_location`, filenames, 3‑per‑activity | ❌ Not done |
| Edit: change activity, add groups, delete photos; 3‑per‑activity | ❌ Not done |
| View: photos under correct activities; Unassigned block; location below images | ❌ Not done |
| PDF/DOC: grouping and location | ❌ Not done |
| Optimization: large image → ≤ 350 KB; corrupt/unsupported → original stored, no crash | ❌ Not done |
| Backward compatibility: `activity_id = null` and `description` reports still view and export | ❌ Not done |

---

### 2.7 Other form / store alignment (if any)

| Item | Status |
|------|--------|
| developmentProject reportform: `summary_activities[1][1][1]` vs store’s `summary_activities.$index.$activityIndex` (extra nesting) | ⚠️ Known mismatch; fix only if that form is used for full submit |

---

## 3. Summary

| Area | Completed | Remaining |
|------|-----------|-----------|
| **Phases 1–8 (monthly)** | All implementation | Manual tests, 1 unit/feature test |
| **Form alignments** | ReportCommonForm, developmentProject reportform | — |
| **Phase 9 (quarterly)** | — | Full phase (DB, controllers, forms, view, export) |
| **Tests** | — | 1 automated, 6+ manual, full high-level checklist |
| **Environment** | — | PHP `exif`, `intervention/image`/GD |
| **Optional** | — | Client-side 3‑per‑activity; PDF in-objectives layout |

---

*Last updated from Phase_Wise_Implementation_Plan and implementation work through developmentProject reportform alignment and Phase 8.*
