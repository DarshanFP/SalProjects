# Photo Rearrangement — Phase-Wise Implementation Plan

This plan covers **photo–activity mapping**, **photo optimization** (JPEG, resize, location preservation, metadata stripping), **activity-based file naming**, and **showing location below each photo** in view/export. It is based on the requirements in:

- [Photo-Activity-Mapping_Viability_Review.md](./Photo-Activity-Mapping_Viability_Review.md)
- [Photo_Optimization_Service_Proposal.md](./Photo_Optimization_Service_Proposal.md)
- [Current_Photo_Naming_And_Storage.md](./Current_Photo_Naming_And_Storage.md)

---

## Scope

| Area | In scope | Out of scope (later) |
|------|----------|----------------------|
| **Monthly reports** | ReportController, edit, show; ReportAll create; optimization; activity mapping; activity-based naming; `photo_location`; view/export | ReportCommonForm, monthly developmentProject reportform (align if in use) |
| **Photo optimization** | Resize, JPEG, **max 350 KB**, extract & store GPS, strip other EXIF, fallback to original | WebP, Spatie image-optimizer |
| **Location** | Store EXIF GPS in `photo_location`; show below image (font 1.5rem); blank if none | Reverse geocoding |
| **Quarterly reports** | — | activity_id, optimization, activity-based naming (Phase 9) |

---

## Phase 1: Database migrations and models

**Goal:** Add `activity_id` and `photo_location` to `DP_Photos` and set up Eloquent relations.

### 1.1 Migration: `add_activity_id_and_photo_location_to_dp_photos_table`

- Add `activity_id` (nullable, string, FK to `DP_Activities.activity_id`, `onDelete` → `set null`).
- Add `photo_location` (nullable, string or text) for EXIF GPS.
- Keep `description` (nullable).

### 1.2 Models

- **DPPhoto:** `$fillable` add `activity_id`, `photo_location`. `belongsTo(DPActivity::class, 'activity_id', 'activity_id')`.
- **DPActivity:** `hasMany(DPPhoto::class, 'activity_id', 'activity_id')`.

### 1.3 Deliverables

- [x] Migration file; run `php artisan migrate`.
- [x] DPPhoto, DPActivity updated and tested (e.g. `$activity->photos`, `$photo->activity`).

---

## Phase 2: Photo Optimization Service

**Goal:** `ReportPhotoOptimizationService` that resizes, re-encodes to JPEG, **extracts and returns GPS** before stripping, and strips other EXIF. On error, returns `null` so the caller can store the original.

### 2.1 Service class

- **Path:** `app/Services/ReportPhotoOptimizationService.php`
- **Method:** `optimize(UploadedFile|string $file): ?array{data: string, extension: string, location: ?string}`
  - **Location:** Before any Intervention step, `exif_read_data($path)` on the original; if GPS present, convert to a readable string (e.g. `"12.34° N, 56.78° E"` or `"12.34, 56.78"`) and set `$location`; else `$location = null`.
  - **Intervention:** `ImageManager` (Gd), `read($path)`, `scaleDown(maxDimension, maxDimension)`, then encode to JPEG. Use `stripProfile()` (or equivalent) to remove metadata **after** GPS has been read.
  - **350 KB cap:** After each `toJpeg`, if `strlen($data) > maxFileSizeKb * 1024` (default 350 KB), re-encode at lower quality (70, 60, 50); if still over, `scaleDown` to 1280 then 960 and retry the same qualities. Return the first result under the limit; if even 960px and quality 50 exceeds 350 KB, return that result (best-effort).
  - **Return:** `['data' => (string) $encoded, 'extension' => 'jpg', 'location' => $location]`.
  - **On `\Throwable`:** `Log::warning`, return `null` if `fallbackToOriginal` is true.

### 2.2 Config and registration

- **Config:** `config/report_photos.php` (create; optional publish) with `optimization.enabled`, `max_dimension` (1920), `jpeg_quality` (82), **`max_file_size_kb` (350)**, `strip_profile` (true), `fallback_to_original_on_error` (true).
- **AppServiceProvider:** Bind `ReportPhotoOptimizationService` (e.g. singleton) reading from config.

### 2.3 Dependencies

- **intervention/image** (already in `composer.json`). PHP `exif` for `exif_read_data` (enable if needed).

### 2.4 Deliverables

- [x] `ReportPhotoOptimizationService` with `optimize()` returning `{ data, extension, location }` or `null`.
- [x] `config/report_photos.php` and registration.
- [ ] Unit or feature test: optimize returns JPEG binary and location when EXIF GPS present; returns `null` on invalid file (or mocked error).

---

## Phase 3: Integrate optimization into current monthly photo flow

**Goal:** Use the optimization service in `handlePhotos()` and `updatePhotos()` (and, if applicable, `MonthlyDevelopmentProjectController`) **without** changing the form (still description, no activity selector). Store `photo_location`. Keep existing behaviour when optimization returns `null`.

### 3.1 handlePhotos() (create)

- Before `storeAs`, call `app(ReportPhotoOptimizationService::class)->optimize($file)`.
- **If result:**
  - Filename: `{photo_id}.jpg` (photo_id is already generated in the loop). Path: `$folderPath . '/' . $photo_id . '.jpg'`.
  - `Storage::disk('public')->put($path, $result['data'])`.
  - `DPPhoto::create([..., 'photo_path' => $path, 'photo_location' => $result['location']])`.
- **If null:**
  - `$path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public')`.
  - `DPPhoto::create([..., 'photo_path' => $path, 'photo_location' => null])`.

### 3.2 updatePhotos() (edit) — new photos only

- Same as 3.1 for each new file in the request: optimize; if result, `put` with `{photo_id}.jpg` and `photo_location`; if null, `storeAs` and `photo_location = null`.

### 3.3 MonthlyDevelopmentProjectController

- Apply the same pattern where it handles `photos` (if it has a separate store path for photos).

### 3.4 DPPhoto::create / update

- Ensure `photo_location` is in `$fillable` and is set as above. `activity_id` can be omitted (remains null).

### 3.5 Deliverables

- [x] ReportController: `handlePhotos`, `updatePhotos` updated; `MonthlyDevelopmentProjectController` if it stores photos.
- [x] New and updated photos use optimization when possible and have `photo_location` when EXIF GPS exists.
- [ ] Manual test: create/edit report with photos (with and without EXIF); confirm smaller JPEGs and `photo_location` in DB when GPS present.

---

## Phase 4: View and export — show photo_location below each photo

**Goal:** Where photos are shown, render `photo_location` below the image with `font-size: 1.5rem` (or equivalent). If empty, show nothing.

### 4.1 partials/view/photos.blade.php

- In the loop over `$photoGroup` / `$photo`, after the image (and “View Full Size”):  
  `@if(!empty($photo->photo_location))<div class="mt-1" style="font-size: 1.5rem;">{{ $photo->photo_location }}</div>@endif`

### 4.2 ExportReportController and PDF view

- **preparePhotosForPdfOptimized:** Add `'photo_location' => $photo->photo_location ?? ''` to each photo in the grouped array.
- **PDFReport/photos.blade.php:** After each `<img>` (or “Photo Not Found”), if `$photo['photo_location']`, add a line with a smaller font (e.g. 10pt or 1.5rem equivalent) for the location.

### 4.3 ExportReportController — DOC (addPhotosSection)

- When writing each photo, after “Photo / Description”, if `photo_location` is present, add a line with a smaller font (e.g. `size` 9) for the location.

### 4.4 Deliverables

- [x] Report view: location below each photo when present.
- [x] PDF and DOC export: location below each photo when present.
- [ ] Manual test with a photo that has EXIF GPS and one without.

---

## Phase 5: Activity mapping — Backend (handlePhotos, updatePhotos)

**Goal:** Accept activity linkage from the form, enforce ≤3 photos per activity, use **activity-based filename** when saving, and persist `activity_id`. Optimization and `photo_location` already integrated; this phase switches the **filename** to the new pattern and wires `activity_id`.

### 5.1 Form input (to be provided by Phase 6)

- `photo_activity_id[groupIndex]` (activity_id string), **or**
- `photo_objective_index[groupIndex]` and `photo_activity_index[groupIndex]` (indices to resolve to `activity_id` via `$report->objectives` and `$report->objectives->activities`).
- If missing or null → treat as **Unassigned** (`activity_id = null`, objective/activity `00_00` in filename).

### 5.2 handlePhotos() — changes

- For each group: resolve `activity_id` from `photo_activity_id` or from objective/activity indices. Validate that `activity_id` belongs to the report (or is null).
- **3‑per‑activity:** Before saving, for that `activity_id`, `existingCount = DPPhoto::where('activity_id', $activity_id)->count()` (or 0 if null). For the 1–3 files in the group, ensure `existingCount + group size ≤ 3`. If over, reject or truncate with a clear validation error.
- **Activity-based filename:** For each file in the group:
  - **ReportID:** `$report->report_id`
  - **MMYYYY:** `date('mY', strtotime($report->reporting_period_from))`
  - **ObjectiveNum, ActivityNum:** If `activity_id` present, from `DPActivity::find($activity_id)` and its `objective`; compute 1-based index of that objective in `$report->objectives` and 1-based index of the activity in `$objective->activities`; format as `sprintf('%02d', ...)`. If Unassigned: `00`, `00`.
  - **Incremental:** For that `activity_id` (or null for Unassigned), `1 +` (count of `DPPhoto` already stored in this request for that activity) or `1 +` (count of existing in DB for that activity) + (file index in group). Format `sprintf('%02d', ...)` (01–03 per activity; for Unassigned, 01, 02, … as needed).
  - **Filename:** `{ReportID}_{MMYYYY}_{ObjectiveNum}_{ActivityNum}_{Incremental}.jpg` when using optimization; when optimization returns `null`, use same base with `.{originalExt}`.
- **Storage:** Folder unchanged: `REPORTS/{project_id}/{report_id}/photos/{month_year}/`. If optimize result: `Storage::put($folderPath . '/' . $filename, $result['data'])`. If null: `$file->storeAs($folderPath, $filename, 'public')` (or build path and write contents).
- **DPPhoto::create:** `photo_id`, `report_id`, `photo_path`, `activity_id`, `description` = null (or omit), `photo_location` from `$result['location']` or null.

### 5.3 updatePhotos() — changes

- **Existing photos:** Accept `photo_activity_id` (or index pair) per `existing_photo_ids` (or a parallel array keyed by existing photo id). Update `DPPhoto::where('photo_id', $id)->update(['activity_id' => $resolved_activity_id])`. Do **not** rename files on disk for existing rows.
- **New photos:** Same logic as 5.2: resolve `activity_id`, 3‑per‑activity, activity-based filename, optimize, store, `activity_id` and `photo_location`.

### 5.4 Validation (StoreMonthlyReportRequest / UpdateMonthlyReportRequest)

- Add rules: `photo_activity_id` or `photo_objective_index` and `photo_activity_index` nullable; if present, ensure they resolve to an `activity_id` belonging to the report (or custom rule). Optional: custom rule to enforce ≤3 photos per `activity_id` across the request.

### 5.5 Helper (optional)

- `ReportPhotoNamingHelper` or private method: `buildActivityBasedFilename($report, $activity_id, $incremental, $extension)` to centralise the `{ReportID}_{MMYYYY}_{Obj}_{Act}_{Inc}.{ext}` logic and the `00_00` case for Unassigned.

### 5.6 Deliverables

- [x] handlePhotos and updatePhotos support `photo_activity_id` or index pair; 3‑per‑activity; activity-based filename; optimization and `photo_location` unchanged.
- [x] Validation updated.
- [x] Backend works when form sends `photo_activity_id`/indices; when they are null, Unassigned (`00_00`) is used.

---

## Phase 6: Activity mapping — Create and Edit UI

**Goal:** Replace the description textarea with a **“Link to Activity”** selector and ensure the activity list stays in sync with objectives/activities. Send `photo_activity_id` or `photo_objective_index` + `photo_activity_index` for each group.

### 6.1 partials/create/photos.blade.php

- Replace `photo_descriptions[groupIndex]` with a `<select name="photo_activity_id[{{ $groupIndex }}]">` (or two selects / hidden inputs for `photo_objective_index` and `photo_activity_index`).  
- **Options:** One option per `(objective_index, activity_index)` with label `"Objective {{ i }} – {{ activity label or 'New Activity' }}"`. Add an option for “— Unassigned —” with value `""` or `"__unassigned__"`.
- **Population:** The list must be built from the live DOM (objectives/activities) or a shared JS structure. Options: (a) a JS function `getReportActivities()` that walks `.objective`, `.activity` and returns `[{ objIndex, actIndex, label }]`; (b) on load and when `addActivity` / `removeActivity` / `reindexActivities` run, refresh the options in each photo group’s select. Ensure `addPhotoGroup()` and `reindexPhotoGroups()` add the same `photo_activity_id` (or index pair) for new/reindexed groups.
- **Default:** “— Unassigned —” or the first activity.

### 6.2 partials/edit/photos.blade.php

- **Existing groups:** For each group (from `$groupedPhotos` or equivalent, keyed by `activity_id` or `description` as fallback), set the select value to the correct `activity_id` or `(objective_index, activity_index)`. For `activity_id === null`, select “— Unassigned —”.
- **New groups:** Same markup and behaviour as create (options from objectives/activities; default Unassigned).
- **existing_photo_ids:** Keep; ensure the order/keys align with the `photo_activity_id` (or index pair) for existing groups so `updatePhotos()` can update `activity_id` for the right rows.

### 6.3 JS sync

- When `addActivity` or `removeActivity` or `reindexActivities` runs in the objectives partial, refresh the activity options in all photo-group selects (or in `window.reportActivities` and have the selects read from it). Ensure indices used in `photo_objective_index` / `photo_activity_index` match the backend’s expectations (1-based or 0-based; must be consistent with `storeObjectivesAndActivities` / `storeActivities`).

### 6.4 Client-side 3‑per‑activity (optional)

- Disable or warn when the user selects an activity that already has 3 photos in the current form (existing + new groups). This requires tracking how many groups point to each activity.

### 6.5 Deliverables

- [x] Create: activity selector instead of description; correct `photo_activity_id` or index pair sent.
- [x] Edit: activity selector for existing and new groups; `existing_photo_ids` and activity ids/indices aligned.
- [x] Activity options stay in sync with add/remove/reindex of activities.
- [ ] Manual test: create and edit reports, assign and unassign activities, hit 3‑photo limit.

---

## Phase 7: View — Photos under activities

**Goal:** Show each activity’s photos **under that activity** in the objectives section. Show `photo_location` below each image (font 1.5rem). Use a separate block only for **Unassigned** photos.

### 7.1 partials/view/objectives.blade.php

- In the loop over `$objective->activities`, for each `$activity`:
  - After the activity’s text (month, summary, etc.), output its photos:  
    `$photos = $activity->photos` or `$report->photos->where('activity_id', $activity->activity_id)`.
  - For each photo: image, “View Full Size”, and **below** the image:  
    `@if(!empty($photo->photo_location))<div class="mt-1" style="font-size: 1.5rem;">{{ $photo->photo_location }}</div>@endif`
- Reuse the same image/modal and styling as in the current `partials/view/photos` where possible.

### 7.2 partials/view/photos.blade.php

- Use **only for Unassigned** photos: `$report->photos->whereNull('activity_id')`. If the collection is empty, render nothing or a short “No unassigned photos.”
- For each Unassigned photo: same layout as 7.1 (image, View Full Size, `photo_location` below when present).

### 7.3 ReportController show()

- Eager-load: `objectives.activities.photos` (or ensure `DPActivity` has `photos` and it is loaded). Pass `objectives` to the view. `groupedPhotos` can be removed from the main view or passed only as “Unassigned” for `partials/view/photos`.

### 7.4 show.blade.php

- Include `partials/view/objectives` (which now contains in-activity photos and location).
- Include `partials/view/photos` only to show Unassigned; or inlay an “Unassigned photos” block inside the objectives partial at the end.

### 7.5 Deliverables

- [x] Photos appear under each activity in the objectives view, with location below when present.
- [x] Unassigned photos in a dedicated block; location below when present.
- [ ] No regression in image display, modals, or “View Full Size”.

---

## Phase 8: Export (PDF and DOC) — by activity and location

**Goal:** Group photos by `activity_id` (and “Unassigned”); include `photo_location`; structure export so it mirrors the web view (photos under each activity, then Unassigned), or at least group by activity and show location under each photo.

### 8.1 preparePhotosForPdfOptimized

- Group by `activity_id` (and a key like `"Unassigned"` for `null`). For each photo, include `'photo_location' => $photo->photo_location ?? ''`. For a label, use `$photo->activity` and `$photo->activity->objective` to build `"Objective X – Activity Y"` or `"Unassigned"`.
- **Optional:** Change the PDF structure so the “Photos” block is weaved into the objectives/activities section (each activity’s photos immediately after that activity’s text). That may require restructuring `PDFReport` and what `preparePhotosForPdfOptimized` returns (e.g. pass `objectives` with `activities` and each activity’s `photos`).

### 8.2 PDFReport/photos.blade.php

- Iterate over the new grouping (by activity or “Unassigned”). For each photo, render image (or “Photo Not Found”) and, if `photo_location` is present, a line with a smaller font (e.g. 10pt or 1.5rem equivalent).

### 8.3 preparePhotosForDoc and addPhotosSection

- Mirror the grouping and `photo_location` handling. In `addPhotosSection`, for each photo, add a line with the location in a smaller font when present.

### 8.4 Deliverables

- [x] PDF: photos grouped by activity (and Unassigned); location below each photo when present.
- [x] DOC: same.
- [ ] Manual test: export report with assigned and unassigned photos, with and without location.

---

## Phase 9: Quarterly reports (later)

**Goal:** Apply the same concepts to quarterly report types that have objectives/activities: `activity_id`, `photo_location`, optimization, activity-based naming (if desired), and view/export. Depends on the exact schema and forms for each type (RQDP, RQDL, RQIS, RQST, RQWD).

### 9.1 (Deferred) Tasks

- Add `activity_id` and `photo_location` to the relevant quarterly photo tables (and, if needed, `objective_id` or equivalent).
- In each quarterly controller’s photo store/update: call `ReportPhotoOptimizationService::optimize`, store `photo_location`; optionally adopt activity-based naming and `activity_id` once objectives/activities and form are aligned.
- Update quarterly create/edit forms with an activity selector and 3‑per‑activity if adopted.
- Update quarterly show and export to group by activity and show location.

---

## Pre-requisites and assumptions

- [x] **ReportAll** and **edit.blade.php** are the active create/edit paths for monthly reports. **ReportCommonForm** aligned: uses `partials/create/photos`, submits to `ReportController::store`. **monthly/developmentProject/reportform.blade.php** aligned: uses `partials/create/photos` with `objectivesIndexBase`, `MonthlyDevelopmentProjectController::store` handles `photos[groupIndex][]`, `photo_activity_id`, 3‑per‑activity, activity-based filename, `report_month_year`→`reporting_period_*`; routes `monthly.developmentProject.create` (→createForm) and `monthly.developmentProject.store` added.
- [ ] PHP `exif` is enabled if `exif_read_data` is used.
- [ ] `intervention/image` remains available; GD (or Imagick) is configured as in the project.

---

## Suggested order and dependencies

```
Phase 1 (DB)
    ↓
Phase 2 (Optimization service)
    ↓
Phase 3 (Optimization in current monthly flow; photo_location)
    ↓
Phase 4 (View/Export: show photo_location)  ← can parallelize with 5/6 if needed
    ↓
Phase 5 (Backend: activity_id, activity-based filename, 3‑per‑activity)
    ↓
Phase 6 (Create/Edit UI: activity selector)  ← can be done in parallel with 5 if backend supports null/Unassigned
    ↓
Phase 7 (View: photos under activities + Unassigned block)
    ↓
Phase 8 (Export: by activity + location)
    ↓
Phase 9 (Quarterly) — when required
```

---

## Testing checklist (high level)

- [ ] Create monthly report with several photo groups; assign to different activities; some Unassigned; some with EXIF GPS. Check: `activity_id`, `photo_location`, activity-based filenames, 3‑per‑activity.
- [ ] Edit: change activity, add new groups, delete photos; 3‑per‑activity validation.
- [ ] View: photos under correct activities; Unassigned block; location below images.
- [ ] PDF/DOC: grouping and location.
- [ ] Optimization: large image → smaller JPEG **≤ 350 KB** (or best-effort when image is very dense); corrupt/unsupported file → original stored, no crash.
- [ ] Backward compatibility: existing reports with `activity_id = null` and `description` still view and export.

---

*Document version: 1.0 — Photo Rearrangement*
