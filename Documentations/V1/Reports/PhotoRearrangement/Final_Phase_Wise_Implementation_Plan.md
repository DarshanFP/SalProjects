# Photo Rearrangement — Final Phase-Wise Implementation Plan

This plan **fixes** the remaining bugs and **completes** all outstanding work identified in:

- [Phase_Wise_Implementation_Plan.md](./Phase_Wise_Implementation_Plan.md)
- [Status_Completed_And_Remaining.md](./Status_Completed_And_Remaining.md)
- [Issues_And_Discrepancies.md](./Issues_And_Discrepancies.md)

It assumes **Phases 1–8 (monthly)** implementation is done as in Status; it does **not** re-do that work. It only covers **fixes**, **remaining deliverables**, **tests**, **environment**, and **documentation**.

---

## Scope of this plan

| In scope | Out of scope (later) |
|----------|----------------------|
| Fix edit flow: `existing_photo_ids`, `updatePhotos` keys, stable `_unassigned_` group key | Phase 9 (Quarterly) — when required |
| Environment: PHP `exif`, `intervention/image` / GD | `summary_activities` nesting (developmentProject reportform) — fix only if that form is used for full submit |
| Unit/feature test: `ReportPhotoOptimizationService` | |
| Manual tests for Phases 2–4, 6–8 and high-level checklist | |
| Documentation updates (stripProfile→removeProfile, Phase 3 filename, GPS, enabled, photos_to_delete, objectivesIndexBase, photo_activity_id, scope, path vs photo_path, config) | |
| Optional: client-side 3‑per‑activity; PDF weaved into objectives | |

---

## Phase A: Fix edit flow (existing photos’ `activity_id` and `description`)

**Goal:** Existing photos must get their `activity_id` and `description` updated when the user changes the “Link to Activity” or the optional description on edit and submits. Today the edit form does not send `existing_photo_ids`, and `updatePhotos` uses the wrong keys for `photo_descriptions` and `photo_activity_id`. Also, when `description` is `null` (Unassigned), the form key must be stable.

**Source:** Issues_And_Discrepancies §2.1 (High), §2.2 (High), §2.3 (Medium).

---

### A.1 Edit form: add `existing_photo_ids`

**File:** `resources/views/reports/monthly/partials/edit/photos.blade.php`

**Current:** The loop over existing groups does not emit `existing_photo_ids`. `ReportController::updatePhotos()` does `$existingPhotoIds = $request->input('existing_photo_ids', [])` and iterates; with `[]` the loop never runs.

**Change:** For each existing group, add a hidden input for **each photo** in that group. Inside the inner `@foreach ($photoGroup as $photo)`, at the start of each `image-preview-item` div, add:

```blade
<input type="hidden" name="existing_photo_ids[]" value="{{ $photo->photo_id }}">
```

- `existing_photo_ids[]` is repeated once per photo across all groups. The controller does not need grouping; it uses `$photo->description` to compute the group key when reading `photo_activity_id` and `photo_descriptions`. Do **not** change `data-description` or other existing attributes in this step.

**Deliverable:** [ ] Edit form submits `existing_photo_ids` with one `photo_id` per existing photo.

---

### A.2 Edit form: stable group key for `photo_activity_id` and `photo_descriptions`

**File:** `resources/views/reports/monthly/partials/edit/photos.blade.php`

**Current:** `name="photo_activity_id[{{ $description }}]"` and `name="photo_descriptions[{{ $description }}]"`. When `$description` is `null` or `''`, the key is empty or becomes `0` in PHP, and the controller cannot reliably find the Unassigned group’s values.

**Change:** Define `$groupKey` once per group and use it in the `name` attributes:

```blade
@foreach ($groupedPhotos as $description => $photoGroup)
    @php
        $groupKey = ($description === null || $description === '') ? '_unassigned_' : $description;
    @endphp
    ...
    <select name="photo_activity_id[{{ $groupKey }}]" ...>
    <textarea name="photo_descriptions[{{ $groupKey }}]" ...>
```

- Reuse the same `$groupKey` as in A.1 for consistency.

**Deliverable:** [ ] `photo_activity_id` and `photo_descriptions` use a stable `_unassigned_` when the group’s description is null or empty.

---

### A.3 `ReportController::updatePhotos()` — correct keys and group key

**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

**Current (conceptual):**

```php
foreach ($existingPhotoIds as $index => $photoId) {
    $photo = DPPhoto::where('photo_id', $photoId)->where('report_id', $report_id)->first();
    $description = $photoDescriptions[$index] ?? $photo->description ?? '';
    $val = $photoActivityIds[$photo->description] ?? $photoActivityIds[$photoId] ?? '__unassigned__';
    ...
}
```

- `$photoDescriptions[$index]` does not match the form: the form sends `photo_descriptions[groupKey]`.
- `$photoActivityIds[$photoId]` does not exist in the form.
- When `$photo->description` is null, `$photoActivityIds[null]` may not match the form’s `photo_activity_id[_unassigned_]`.

**Change:**

1. Compute `$groupKey` the same way as in the form:  
   `$groupKey = ($photo->description === null || $photo->description === '') ? '_unassigned_' : $photo->description;`

2. **Description:**  
   `$description = $photoDescriptions[$groupKey] ?? $photo->description ?? '';`

3. **Activity:**  
   `$val = $photoActivityIds[$groupKey] ?? '__unassigned__';`  
   (Remove the `$photoActivityIds[$photoId]` fallback; the form does not send it.)

4. **Resolve and update:**  
   `$updates['activity_id'] = $this->resolveActivityId($report, $val);`  
   - In `resolveActivityId`, `'__unassigned__'` (and empty) already yield `null`, so no change there.

5. **`description` for linked vs Unassigned:**  
   - When `activity_id` is set (linked): `description` can be `null` (as per current design).  
   - When `activity_id` is null (Unassigned): keep `$description` from the form.  
   So:  
   `$updates['description'] = $updates['activity_id'] === null ? $description : null;`  
   (or your existing rule; the important part is using `$groupKey` for the form inputs.)

**Deliverable:** [ ] `updatePhotos` uses `$groupKey` from `$photo->description` (or `_unassigned_`) to read `photo_descriptions` and `photo_activity_id`; existing photos’ `activity_id` and `description` are updated on submit.

---

### A.4 Deliverables summary (Phase A)

- [x] A.1: Edit form sends `existing_photo_ids[]` for every existing photo.
- [x] A.2: Edit form uses `$groupKey` (`_unassigned_` when description is null/empty) for `photo_activity_id` and `photo_descriptions`.
- [x] A.3: `updatePhotos` uses `$groupKey` and the correct array keys; `_unassigned_` is resolved to `activity_id = null`.
- [ ] **Sanity check:** Edit a report, change “Link to Activity” for an existing group, submit; confirm `activity_id` and `description` in DB.

---

## Phase B: Environment and pre-requisites

**Goal:** Confirm that the runtime and extensions required by the optimization service and the rest of the photo flow are available.

**Source:** Status §2.4; Phase_Wise_Implementation_Plan “Pre-requisites and assumptions”.

---

### B.1 PHP `exif`

- `ReportPhotoOptimizationService::extractGpsFromExif()` uses `exif_read_data()`.
- **Check:** `php -m | grep exif` or `function_exists('exif_read_data')` in a short artisan tinker or route.
- **If missing:** Enable `exif` in `php.ini` (e.g. `extension=exif` or via your stack’s config).

**Deliverable:** [x] PHP `exif` is enabled where the app runs (local, staging, prod as relevant). *(Confirmed: `php -m | grep exif`.)*

---

### B.2 `intervention/image` and GD

- **Check:** `composer show intervention/image`; in code, `ImageManager::gd()` or `new ImageManager(new \Intervention\Image\Drivers\Gd\Driver())` and `$manager->read($path)` on a test image.
- **If Imagick is preferred:** The service is written for GD; switching would be a separate change. This plan only requires that the current GD-based path works.

**Deliverable:** [x] `intervention/image` is installed and the Gd driver works for `read` / `scaleDown` / `toJpeg` (and `removeProfile` where available). *(Confirmed: intervention/image 3.x; tests pass.)*

---

## Phase C: Automated test — `ReportPhotoOptimizationService`

**Goal:** One unit or feature test that gives confidence the optimization service behaves as specified: returns JPEG + `location` when EXIF GPS exists; returns `null` on invalid or unreadable input (or when we simulate failure).

**Source:** Status §2.2; Phase_Wise_Implementation_Plan Phase 2.4.

---

### C.1 Test cases (minimum)

1. **Valid image with EXIF GPS:**  
   - Use a fixture (small JPEG with GPS) or mark a test as requiring such a file.  
   - `optimize($file)` returns non-null; `extension === 'jpg'`; `location` is a non-empty string (e.g. `"lat, lng"` decimal).

2. **Valid image without EXIF GPS:**  
   - `optimize($file)` returns non-null; `extension === 'jpg'`; `location === null`.

3. **Invalid or unreadable input:**  
   - `optimize('/nonexistent/path')` or `optimize($invalidUploadedFile)` (or mocked) such that the service catches and returns `null` when `fallbackToOriginal` is true.

4. **Optional:**  
   - Output size ≤ 350 KB for a large input (or document that this is environment-dependent and run manually).

**Location:** e.g. `tests/Unit/ReportPhotoOptimizationServiceTest.php` or `tests/Feature/ReportPhotoOptimizationServiceTest.php`.

**Deliverable:** [x] At least one test file with the above cases (or equivalents), green in `php artisan test`. *(`tests/Unit/Services/ReportPhotoOptimizationServiceTest.php`: 4 passed; 1 skipped when `tests/fixtures/sample_with_gps.jpg` is missing—optional.)*

---

## Phase D: Manual tests and high-level checklist

**Goal:** Run the manual tests that were listed as remaining in Status for Phases 2–4, 6–8, and the overall checklist.

**Source:** Status §2.3, §2.6; Phase_Wise_Implementation_Plan “Testing checklist”.

---

### D.1 Phase 2 — Optimization

- [ ] Photo with EXIF GPS: after upload, `photo_location` in DB and in view/export.
- [ ] Large image: stored file ≤ 350 KB (or best-effort when very dense).
- [ ] Corrupt or unsupported file: original stored, no crash.

---

### D.2 Phase 3 / Create–edit with photos

- [ ] Create report: photos with and without EXIF; smaller JPEGs on disk; `photo_location` in DB when GPS present.
- [ ] Edit: add new photos; same behaviour for optimization and `photo_location`.

---

### D.3 Phase 4 — View and export `photo_location`

- [ ] View: `photo_location` below image when present; nothing when empty.
- [ ] PDF and DOC: `photo_location` below each photo when present.

---

### D.4 Phase 6 — Activity mapping (create and edit)

- [ ] Create: assign groups to different activities; some Unassigned; 3‑per‑activity (try 4th photo for same activity: truncated or validation).
- [ ] Edit: change “Link to Activity” for **existing** groups, submit; confirm `activity_id` (and `description` for Unassigned) in DB (depends on **Phase A**).
- [ ] Edit: add new groups; assign and unassign; 3‑per‑activity.

---

### D.5 Phase 7 — View

- [ ] Photos under the correct activities in the objectives section.
- [ ] Unassigned block shows only `activity_id` null; `photo_location` below when present.
- [ ] No regression: image display, modals, “View Full Size”.

---

### D.6 Phase 8 — Export

- [ ] PDF/DOC: grouping by “Objective X – Activity Y” and “Unassigned”; `photo_location` under each photo when present.
- [ ] Export with mix of assigned and unassigned, with and without location.

---

### D.7 High-level checklist

- [ ] Create: several photo groups; different activities; some Unassigned; some with EXIF; check `activity_id`, `photo_location`, activity-based filenames, 3‑per‑activity.
- [ ] Edit: change activity, add groups, delete photos (via existing AJAX DELETE); 3‑per‑activity.
- [ ] View: photos under activities; Unassigned block; location below images.
- [ ] PDF/DOC: grouping and location.
- [ ] Optimization: large → ≤ 350 KB; corrupt/unsupported → original stored.
- [ ] Backward compatibility: reports with `activity_id = null` and `description` still view and export.

---

### D.8 Deliverables summary (Phase D)

- [ ] D.1–D.7 and D.7 checklist completed and signed off (or explicitly deferred with reason).

---

## Phase E: Documentation updates

**Goal:** Align the written docs with the implementation and with the design choices (single `photo_activity_id`, AJAX delete, `_unassigned_`, etc.). No code changes.

**Source:** Issues_And_Discrepancies §1.1–1.4, §2.3–2.4, §3.1–3.2, §4.1–4.3.

---

### E.1 `Phase_Wise_Implementation_Plan.md`

- **§2.1:** Replace `stripProfile()` with `removeProfile()` (or “`removeProfile()` in Intervention v3”).
- **§2.1:** State that `photo_location` is decimal `"lat, lng"` (e.g. `"12.34, 56.78"`); drop or soften “12.34° N, 56.78° E” if not used.
- **§3.1, §3.2:** Note that in the implemented flow, new photos use **activity-based filename** from the start (Phase 3 and 5 are combined); remove or supersede `{photo_id}.jpg` for new uploads.
- **§5.1:** Clarify that the implementation uses a **single** `photo_activity_id[groupIndex]` with values `"obj:act"`, `"__unassigned__"`, or `activity_id`; `photo_objective_index` / `photo_activity_index` are not used.
- **§5.3:** Specify that **existing** photos’ `activity_id` (and `description`) are updated from `existing_photo_ids` plus `photo_activity_id[groupKey]` and `photo_descriptions[groupKey]`, with `groupKey = $photo->description ?? '_unassigned_'` when description is null/empty. **Do not** rename existing files on disk.
- **§6.2:** State that the edit form sends `existing_photo_ids[]` (one per photo) and uses `$groupKey = ($description === null || $description === '') ? '_unassigned_' : $description` for `photo_activity_id` and `photo_descriptions`.
- **§6.3 (or new subsection):** Add `objectivesIndexBase`: ReportAll uses 0-based; developmentProject reportform uses 1-based; `getReportActivities` and `resolveActivityId` / `resolveActivityIdFromIndices` are consistent with each create path.
- **Photo deletion on edit:** Note that removal of existing photos is done via **AJAX DELETE** (`/reports/monthly/photos/{photoId}`), not via `photos_to_delete` on submit; `photos_to_delete` can be marked optional or legacy.
- **Scope:** Clarify that ReportCommonForm and developmentProject reportform are treated as aligned for Photo Rearrangement; “align if in use” applies only to any remaining non–photo-rearrangement behaviour.

**Deliverable:** [ ] Phase_Wise_Implementation_Plan.md updated as above.

---

### E.2 `Photo_Optimization_Service_Proposal.md`

- Replace `stripProfile()` with `removeProfile()` in the implementation sketch and “Don’t Break” table.
- In §5.3 (config) and §13 (registration): add `enabled` and show `fallback_to_original_on_error` at top level to match `config/report_photos.php` and `AppServiceProvider`.

**Deliverable:** [ ] Photo_Optimization_Service_Proposal.md updated.

---

### E.3 `Status_Completed_And_Remaining.md`

- **§1.3:** Adjust Phase 3 description: new photos use activity-based filename (Phase 3 and 5 merged in implementation); remove `{photo_id}.jpg` as the current behaviour.
- **§1.6 / Edit:** Note that `existing_photo_ids` and `$groupKey` (including `_unassigned_`) are **implemented in the Final plan (Phase A)** and should be marked done only after Phase A is complete.
- **§2.4:** After Phase B, mark “Confirm PHP exif” and “Confirm intervention/image and GD” as done (or document “confirmed” with env/version).
- **§2.2, §2.3, §2.6:** After Phases C and D, mark the relevant tests as done.

**Deliverable:** [ ] Status_Completed_And_Remaining.md updated to reflect Phase A–D and doc updates.

---

### E.4 `Issues_And_Discrepancies.md`

- Add a short “Resolution” or “Updates” section: Phase A addresses §2.1, §2.2, §2.3; Phase E addresses §1.1–1.4, §2.4, §3.1–3.2, §4.1, §4.3; §4.2 (path vs photo_path) to be covered when Phase 9 is implemented.
- Optional: add “Resolved” or “See Final_Phase_Wise_Implementation_Plan” in the summary table for those items.

**Deliverable:** [ ] Issues_And_Discrepancies.md updated with resolution notes.

---

### E.5 Phase 9 / Quarterly (when implemented)

- In the Phase 9 section of the main plan (and in Status when that work starts): remind that some quarterly types use `path`, others `photo_path`; migrations and controllers must use the correct attribute. Ref: `Current_Photo_Naming_And_Storage.md` §3.3.

**Deliverable:** [ ] (Only when Phase 9 is taken up) Phase 9 docs include path vs photo_path.

---

## Phase F: Optional / nice-to-have

**Source:** Status §2.5; Phase_Wise_Implementation_Plan §6.4, §8.1.

---

### F.1 Client-side 3‑per‑activity (Phase 6)

- In create and edit, when the user selects an activity that already has 3 photos (existing + new groups in the form), disable that option or show a warning.
- Requires JS to count how many groups point to each activity and to react to select changes.

**Deliverable:** [ ] Optional: client-side 3‑per‑activity implemented and tested.

---

### F.2 PDF “Photos” weaved into objectives/activities (Phase 8)

- Instead of (or in addition to) a single “Photos” block grouped by activity, place each activity’s photos **immediately after** that activity’s text in the PDF. This may require changes to `PDFReport` structure and `preparePhotosForPdfOptimized` (e.g. pass `objectives` with `activities` and each activity’s `photos`).

**Deliverable:** [ ] Optional: PDF weaved layout implemented and tested.

---

## Phase G: Phase 9 — Quarterly reports (when required)

**Goal:** Apply `activity_id`, `photo_location`, optimization, and optionally activity-based naming and view/export grouping to the relevant quarterly report types. **Deferred** until needed.

**Source:** Phase_Wise_Implementation_Plan Phase 9; Status §2.1; Issues §4.2.

When implementing:

- Add `activity_id` and `photo_location` to the relevant quarterly photo tables (RQDPPhoto, RQDLPhoto, RQISPhoto, RQSTPhoto, RQWDPhoto, etc.); respect **`path` vs `photo_path`** per type (see `Current_Photo_Naming_And_Storage.md` §3.3).
- In each controller’s photo store/update: call `ReportPhotoOptimizationService::optimize`, store `photo_location`; optionally add activity-based naming and `activity_id` when objectives/activities and forms are aligned.
- Update quarterly create/edit forms (activity selector, 3‑per‑activity if adopted), show, and export.

**Deliverable:** [ ] Phase 9 done when required (out of scope of this Final plan).

---

## Suggested order and dependencies

```
Phase A (Fix edit flow)     ← must do first for edit to work
    ↓
Phase B (Environment)       ← can run in parallel with A
    ↓
Phase C (Unit test)         ← after B if test needs exif/GD
    ↓
Phase D (Manual tests)      ← after A; can overlap with C
    ↓
Phase E (Documentation)     ← after A–D to reflect final state
    ↓
Phase F (Optional)          ← whenever capacity allows
    ↓
Phase G (Quarterly)         ← when required
```

---

## Summary: what “done” looks like

| Phase | Deliverables |
|-------|--------------|
| **A** | Edit form sends `existing_photo_ids`; uses `$groupKey` with `_unassigned_`; `updatePhotos` uses `$groupKey` for `photo_descriptions` and `photo_activity_id`; existing photos’ `activity_id` and `description` update on submit. |
| **B** | PHP `exif` and `intervention/image` (Gd) confirmed. ✓ |
| **C** | Unit/feature test for `ReportPhotoOptimizationService::optimize` (JPEG, location, `null` on invalid). ✓ |
| **D** | Manual tests for Phases 2–4, 6–8 and high-level checklist completed. |
| **E** | Phase_Wise_Implementation_Plan, Photo_Optimization_Service_Proposal, Status_Completed_And_Remaining, and Issues_And_Discrepancies updated. |
| **F** | (Optional) Client-side 3‑per‑activity; PDF weaved into objectives. |
| **G** | (When required) Quarterly: `activity_id`, `photo_location`, optimization, path vs photo_path, forms, view, export. |

---

*Document version: 1.0 — Final Phase-Wise Implementation Plan (Photo Rearrangement)*  
*Supersedes: fixes and completions only; Phases 1–8 implementation as in Status remain the base.*
