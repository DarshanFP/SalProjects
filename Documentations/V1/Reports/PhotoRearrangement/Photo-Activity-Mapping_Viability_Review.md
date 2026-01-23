# Photo–Activity Mapping: Viability Review

## 1. Executive Summary

**Proposal:** Replace the free-text **description** on report photo groups with a **link to an activity** (from objectives or new activities in the report). Each activity may have up to **3 photos**. In the report **view**, photos are shown **with the activities** they belong to.

**Conclusion:** **Viable** for **monthly reports** across create, edit, and view. **Partially viable** for **quarterly reports** (only those with objectives/activities). Other report types and project-specific flows need to be checked case by case.

---

## 2. Current State

### 2.1 Photo Section Structure

| Aspect | Monthly Reports | Quarterly Reports |
|--------|-----------------|-------------------|
| **Create** | `partials/create/photos`: **Photo groups** with up to **3 photos** per group + **description** (textarea). “Add More” adds another group. `photos[groupIndex][]`, `photo_descriptions[groupIndex]`. | One **photo** + **description** per row. `photos[]`, `photo_descriptions[]`. “Add More” adds another row. |
| **Edit** | `partials/edit/photos`: Existing photos grouped by **description**; new groups use same 3-photo + description pattern. | Per-type edit forms; existing + new photos with descriptions. |
| **View** | `partials/view/photos`: Photos grouped by **description**; each group shows images + “Description: …”. | Per-type show views; photos listed with descriptions. |

### 2.2 Where Photos Appear (Monthly)

- **Create:** `ReportAll.blade.php` → `@include('reports.monthly.partials.create.photos')`  
  - Uses **objectives** from `partials/create/objectives` (project objectives + activities, plus “Add Other Activity”).
- **Edit:** `edit.blade.php` → `@include('reports.monthly.partials.edit.photos', …)`  
  - Controller passes `groupedPhotos` from `$report->photos->groupBy('description')`.
- **View:** `show.blade.php` → `@include('reports.monthly.partials.view.photos', ['groupedPhotos' => $groupedPhotos])`.

### 2.3 Database (Monthly: DP_Photos)

- **Table:** `DP_Photos`
- **Relevant columns:** `photo_id`, `report_id`, `photo_path`, `photo_name`, **`description`** (text, nullable).
- **No** `activity_id` or `objective_id`; grouping is only by `description`.

### 2.4 Objectives/Activities (Monthly)

- **DP_Objectives:** `objective_id`, `report_id`, `project_objective_id`, `objective`, …
- **DP_Activities:** `activity_id`, `objective_id`, `project_activity_id` (nullable for “Add Other Activity”), `activity`, `month`, `summary_activities`, …
- **IDs:**  
  - `objective_id` = `{report_id}-{index}`  
  - `activity_id` = `{objective_id}-{index}`
- **Save order in store:** Report → Objectives → Activities → **Photos** → project-specific, attachments.

So when `handlePhotos()` runs, **objectives and activities already exist**; we can resolve `activity_id` from the form.

### 2.5 Quarterly Reports

- **Development / Development Livelihood / Institutional Support:** Objectives + activities in the form (structure similar to monthly).
- **Skill Training / Women in Distress:** Also have objectives/activities in their forms.
- **Photos:** One photo + description per row; stored in types such as `RQDPPhoto` (Development) with `report_id`, `photo_path`, `description`. No `activity_id` in the current schema.

---

## 3. Proposed Change (High Level)

1. **Data:** Add `activity_id` (nullable FK to `DP_Activities` for monthly; equivalent for quarterly where applicable). Keep `description` **nullable** for backward compatibility and “Unassigned” or legacy.
2. **Create:** For each **photo group** (3 photos), replace the description textarea with a **“Link to Activity”** selector: Objective X – Activity Y (including “New Activity (index)” for ad‑hoc activities).  
   - Form: `photo_activity_id[groupIndex]` or a composite `objective_index` + `activity_index` that the backend maps to `activity_id` after activities are saved.
3. **Edit:** Same selector for existing groups (pre-filled from `activity_id`) and for new groups.
4. **View:**  
   - **Option A:** Group photos by **activity** (and by objective) instead of by description.  
   - **Option B:** Keep a “Photos” block but under each activity in the objectives section, show that activity’s photos (up to 3).  
   - **Option B** matches “photos shown with the activities” and is the main target.
5. **Rules:** Up to **3 photos per activity**; an activity can have 0, 1, 2, or 3. “Add More” in the photo section = another group bound to an (possibly same) activity, subject to the 3‑per‑activity limit.
6. **Photo file naming (activity-based):** Each stored photo is named after the linked objective and activity:  
   **`{ReportID}_{MMYYYY}_{ObjectiveNum}_{ActivityNum}_{Incremental}.{ext}`**  
   - **ReportID** — `report_id` (e.g. `DP-0001-202501-001`).  
   - **MMYYYY** — month and year of the report, from `reporting_period_from` (e.g. `012025` for Jan 2025).  
   - **ObjectiveNum** — 2‑digit objective index, 1‑based (e.g. `01`, `02`). From the objective’s position in the report.  
   - **ActivityNum** — 2‑digit activity index within that objective, 1‑based (e.g. `01`, `02`).  
   - **Incremental** — 2‑digit photo index for that activity, `01`–`03` for the up‑to‑3 photos per activity.  
   - **Unassigned** (`activity_id` null): use `00_00` for obj/act; incremental `01`, `02`, … as needed.  
   - **Example:** `DP-0001-202501-001_012025_01_02_01.jpg` = report `DP-0001-202501-001`, Jan 2025, objective 1, activity 2, 1st photo.

---

## 4. Feasibility by Flow and Project Type

### 4.1 Monthly Reports – Create

| Item | Assessment |
|------|------------|
| **Objectives/activities available** | Yes. `partials/create/objectives` is included before photos; project objectives and “Add Other Activity” are available. |
| **Stable activity identifiers at submit** | Save order: Report → Objectives → Activities → Photos. When `handlePhotos()` runs, `activity_id`s exist. Form can send `objective_index` + `activity_index`; backend maps to `activity_id` using the same indexing logic as `storeObjectivesAndActivities` / `storeActivities`. |
| **UI change** | Replace `photo_descriptions[groupIndex]` with a select (or equivalent) for “Link to Activity”. Options: for each `(objective_index, activity_index)`: “Objective {i} – {activity label or ‘New Activity’}”. `addActivity()` and `reindexActivities()` already manage indices; the photo partial needs a similar list (or a shared JS structure). |
| **Backend** | In `handlePhotos()`: read `photo_activity_id[groupIndex]` or `photo_objective_index[groupIndex]` + `photo_activity_index[groupIndex]`, resolve to `activity_id`, pass to `DPPhoto::create`; set `description` to `null` or a fallback. Validation: `activity_id` must belong to the report. |
| **3‑photos‑per‑group** | Already in `partials/create/photos`: `photos[groupIndex][]` and logic for up to 3. No structural change. |
| **3‑photos‑per‑activity** | New rule: when saving, enforce that each `activity_id` has ≤3 photos. If “Add More” can target the same activity, either prevent >3 in the UI or truncate/warn in the backend. |

**Viability: Yes.** Main work: UI for the activity selector (and its options), backend mapping and validation, and the 3‑per‑activity rule.

---

### 4.2 Monthly Reports – Edit

| Item | Assessment |
|------|------------|
| **Existing photos** | Currently keyed by `description`. Need to load `activity_id` and, for each group, show the correct “Link to Activity” (and allow changing it). |
| **Activity list** | `$report->objectives` and `$report->objectives->activities` are available in edit; can build the same “Objective X – Activity Y” list as in create. |
| **New photo groups** | Same as create: 3 photos + activity selector. |
| **updatePhotos()** | Today: `existing_photo_ids`, `photo_descriptions`, `photos` (new), `photos_to_delete`. Need to: (1) accept `photo_activity_id` (or index pair) per existing group and per new group; (2) update `DP_Photos.activity_id` and set `description` to null or a default when linked to an activity. |
| **Deleting / re‑linking** | If the user changes the activity for a group or deletes an activity, photos can become “Unassigned” (`activity_id = null`). View/export should handle `activity_id === null` (e.g. “Unassigned” or “Other”). |

**Viability: Yes.** Same mapping and 3‑per‑activity logic as create; edit needs to pre‑fill the selector and support `activity_id` updates and unassign.

---

### 4.3 Monthly Reports – View

| Item | Assessment |
|------|------------|
| **Grouping** | Today: `$groupedPhotos = $report->photos->groupBy('description')`. New: group by `activity_id` (and optionally by objective for ordering). `activity_id === null` → “Unassigned” or “Other”. |
| **Where to show** | **Option A:** Only in Objectives: under each activity, show its photos (up to 3). **Option B:** Separate Photos section, but each subgroup is “Objective X – Activity Y” + photos. **Option B (inline with activities)** best matches “photos shown with the activities”. |
| **Blade changes** | `partials/view/objectives`: for each `$objective->activities` and each `$activity`, render `$activity->photos` (new relationship) or filter `$report->photos->where('activity_id', $activity->activity_id)`. A dedicated `partials/view/photos` can be repurposed for an “Unassigned” only block, or removed if all photos are shown under activities. |
| **Controller** | `show()` already has `objectives.activities`. Add `photos` on the right model. For `DP_Photos` we add `activity_id` and a `DPPhoto` → `DPActivity` relation; then either `DPActivity` hasMany `DPPhoto` or we pass `$report->photos` and group in the view. |

**Viability: Yes.** View changes are mostly in `partials/view/objectives` and how `partials/view/photos` is used (or retired for the main flow).

---

### 4.4 Monthly – Project Types

- **ReportAll** (used for create) and **edit** both include:
  - `partials/create/objectives` / `partials/edit/objectives` (objectives and activities)
  - `partials/create/photos` / `partials/edit/photos`
- Project‑specific blocks (Livelihood, Institutional, Skill, Crisis, etc.) do **not** replace the objectives or the main photo partial; they are extra sections.
- Therefore, **all monthly project types** that use `ReportAll` + `edit.blade.php` can use the same photo–activity mapping. No project‑type‑specific photo logic in the current structure.

**Viability: Yes**, for all monthly types using the common create/edit.

---

### 4.5 Quarterly Reports – Create / Edit / View

| Item | Assessment |
|------|------------|
| **Objectives/activities** | developmentProject, developmentLivelihood, institutionalSupport, skillTraining, womenInDistress: all have objectives and activities in their forms. |
| **Photo schema** | Quarterly photo tables (e.g. RQDP) have `report_id`, `photo_path`, `description`. They do **not** have `activity_id`. |
| **Photo UI** | One photo + description per row (no 3‑per‑group). To align with the proposal, each row would need: 1) an activity selector, 2) optionally moving to a 3‑photos‑per‑activity pattern (more invasive). |
| **DB and models** | Would need a migration to add `activity_id` (or equivalent) to the relevant quarterly photo tables, and to define objectives/activities and their IDs for each quarterly type. |

**Viability: Partially.** The idea is feasible where objectives/activities exist, but it requires:

- Schema change for each quarterly photo table that should support activity mapping.
- Aligning the form with “one activity per photo (group)” and, if desired, “3 photos per activity.”
- Per‑type checks: exact table names, how objectives/activities are stored (e.g. in `quarterly_report_details` or type‑specific tables).

---

### 4.6 Other Report Types

- **ReportCommonForm:** Uses 1 photo + description per group; “Add More” adds groups. It is not the main create path (create uses `ReportAll`), but if it stays in use, it would need the same activity selector and backend changes as above.
- **monthly/developmentProject/reportform.blade.php:** Has its own 1‑photo + description block. If this form is still used, it would need to be brought in line with the activity‑mapping and 3‑photos‑per‑activity design.
- **Aggregated (quarterly/half‑yearly/annual), PDF/DOC export:** These consume `$report->photos` and today group by `description`. They would need to switch to grouping by `activity_id` (and an “Unassigned” bucket) and, if we adopt “photos under activities” in the main view, the export layout may need to mirror that (e.g. photos under each activity in the exported document).

---

## 5. Implementation Considerations

### 5.1 Database

- **DP_Photos (monthly):**
  - Add `activity_id` nullable, FK to `DP_Activities.activity_id`, `onDelete` → `set null` (so removing an activity does not delete photos).
  - Keep `description` for legacy and for “Unassigned” or optional extra context.
- **Quarterly (and any other) photo tables that will support the feature:**  
  - Add `activity_id` (or the correct FK for that report type) in the same way.

### 5.2 Backend (Monthly)

- **handlePhotos():**  
  - Input: `photo_activity_id[groupIndex]` or `photo_objective_index[groupIndex]` + `photo_activity_index[groupIndex]`.  
  - Resolve to `activity_id` (and validate it belongs to the report).  
  - For each of the up to 3 files in the group, create `DPPhoto` with `activity_id` and `description = null` (or a small placeholder).  
  - Enforce: for each `activity_id`, `existing count + new for that activity ≤ 3`.  
  - **Photo filename:** use the activity-based pattern `{ReportID}_{MMYYYY}_{Obj:02d}_{Act:02d}_{Inc:02d}.{ext}`.  
    - **ObjectiveNum / ActivityNum:** from the activity’s objective (1-based index in `$report->objectives`) and the activity’s 1-based index in `$objective->activities`, or by parsing `activity_id` / `objective_id`.  
    - **Incremental:** for that `activity_id`, 1 + (count of existing photos already linked) or the file index in the group (`01`, `02`, `03`).  
  - Store under the existing folder: `REPORTS/{project_id}/{report_id}/photos/{month_year}/`.  
  - **Unassigned** (`activity_id` null): use `00_00` for obj/act; incremental from existing unassigned count or from the group.
- **updatePhotos():**  
  - Same resolution and 3‑per‑activity rule for new groups.  
  - For `existing_photo_ids`, accept `photo_activity_id` (or indices) and update `activity_id` (and optionally `description`).  
  - When updating, re‑check the 3‑per‑activity limit (in case the user reassigns groups to the same activity).  
  - **New** photos: use the same activity-based filename. **Existing** photos: keep current `photo_path` unless a separate rename/migration is run.

### 5.3 Frontend (Monthly)

- **Create `partials/create/photos`:**
  - Replace `photo_descriptions[groupIndex]` with a `<select>` (or similar) populated from the current set of (objective_index, activity_index) with labels “Objective {i} – {activity text or ‘New Activity’}”.  
  - The list must stay in sync with `addActivity` / `removeActivity` / `reindexActivities` in the objectives partial. Options: (a) a JS function that builds the list from the DOM, (b) a shared `window.reportActivities = [...]` updated when objectives/activities change.  
  - Add `photo_activity_id` or `photo_objective_index` + `photo_activity_index` to `addPhotoGroup()` and `reindexPhotoGroups()` so new/ reindexed groups have the right names.
- **Edit `partials/edit/photos`:**
  - For each existing group (by `activity_id` or by `description` as fallback): set the selector to the correct activity.  
  - For new groups: same as create.  
  - Ensure `existing_photo_ids` and the new `photo_activity_id` (or index pair) stay aligned when groups are removed or reordered.

### 5.4 View and Export

- **partials/view/objectives:**  
  - For each activity, render its photos (relationship or `$report->photos->where('activity_id', $activity->activity_id)`).  
  - If `activity_id` is null, either show under an “Unassigned” subsection or in a small “Other photos” block.
- **partials/view/photos:**  
  - Use only for “Unassigned” photos, or remove if everything is under objectives.
- **ReportController `show()`:**  
  - `groupedPhotos` can be deprecated for the main view or restricted to “Unassigned.”  
  - Ensure `objectives.activities` are loaded and that `DPActivity` has a `photos` relation (or that `DPPhoto` has `activity` and we group in the view).
- **ExportReportController (PDF/DOC), PDFReport views:**  
  - Replace grouping by `description` with grouping by `activity_id` (and “Unassigned”).  
  - Optionally, structure the export so photos appear under each activity, similar to the web view.

### 5.5 Validation and Requests

- **StoreMonthlyReportRequest / UpdateMonthlyReportRequest:**  
  - Add rules for `photo_activity_id` or `photo_objective_index` and `photo_activity_index` (e.g. `nullable`, `exists` or in the report’s objective/activity index range).  
  - Optional: custom rule to enforce 3 photos per activity across the request.

### 5.6 Backward Compatibility and Migration

- **Existing rows:** `activity_id` nullable. Old photos keep `description` and `activity_id = null`. In view/export, treat `activity_id === null` as “Unassigned” (and optionally still use `description` for display).  
- **Edit:** For `activity_id === null`, the selector can show “Unassigned” or “—”; user can leave as is or assign to an activity.  
- **Data migration:** Optional: one‑off to try to infer `activity_id` from `description` (e.g. if description contained an activity label). Not required for viability.

### 5.7 Photo Optimization Service (Minimal Size, WhatsApp‑Style)

A separate **Photo Optimization Service** can reduce stored image size (resize, re‑encode to JPEG, strip EXIF) so photos **don’t break** reports while using minimal storage. This is **independent** of the activity‑mapping change and can be implemented before or after it.

- **Proposal:** [Photo_Optimization_Service_Proposal.md](./Photo_Optimization_Service_Proposal.md) in this folder.
- **Idea:** Before `storeAs` / `store` in `handlePhotos`, `updatePhotos`, and quarterly photo handlers: run the service; if it returns optimized JPEG bytes, `Storage::put` with `.jpg`; otherwise keep the original file. Uses **intervention/image** (already in the project).
- **Goal:** Resize (e.g. longest edge ≤ 1920px), JPEG quality ~80–85, strip metadata; on any error, **fallback to original** so the report is never broken.

---

## 6. Risks and Gaps

| Risk | Mitigation |
|------|------------|
| **New activities** (“Add Other Activity”) don’t have `activity_id` until after save. | Backend saves activities before photos. Form sends objective_index + activity_index; backend uses the same indexing as `storeActivities` to get `activity_id`. |
| **Reindexing (add/remove activity) and photo selectors** getting out of sync. | Build the activity list from the live DOM or a shared JS structure that is updated by the objectives partial; avoid hard‑coded indices in the photo partial. |
| **User assigns more than 3 photos to one activity** (e.g. via “Add More” and selecting the same activity). | Client‑side: disable or warn when an activity already has 3. Backend: validate and reject or truncate with a clear error. |
| **Quarterly and other types** use different tables and forms. | Implement first for monthly; then replicate the pattern per quarterly (and other) type, including migrations and form/controller/view changes. |
| **ReportCommonForm and developmentProject reportform** might still be in use. | Confirm which forms are active; if they are, add the same activity selector and 3‑per‑activity behavior for consistency. |

---

## 7. Recommended Order of Work

1. **DB (monthly):** Migration to add `activity_id` to `DP_Photos`, and `DPActivity` hasMany `DPPhoto` / `DPPhoto` belongsTo `DPActivity`.
2. **Backend (monthly):**  
   - `handlePhotos()` and `updatePhotos()`: activity resolution, `activity_id` persistence, 3‑per‑activity rule.  
   - `show()`: pass activities with photos (or equivalent).  
   - Validation in Store/Update requests.
3. **Create:** `partials/create/photos`: activity selector, `addPhotoGroup`/`reindexPhotoGroups`; JS to keep activity options in sync with objectives/activities.
4. **Edit:** `partials/edit/photos`: pre‑fill from `activity_id`, same selector and rules for new groups; `updatePhotos()` support.
5. **View:** `partials/view/objectives`: show photos per activity; `partials/view/photos` only for “Unassigned” or remove; controller/`groupedPhotos` adjustments.
6. **Export:** PDF/DOC to group by `activity_id` and, if desired, to place photos under activities.
7. **Quarterly (and others):** After monthly is stable, add `activity_id` and the same UX to the relevant quarterly (and other) photo tables and forms.

---

## 8. Summary Table

| Report Type    | Create       | Edit         | View         | Notes                                                                 |
|----------------|-------------|-------------|--------------|-----------------------------------------------------------------------|
| **Monthly (all project types using ReportAll + edit)** | ✅ Viable   | ✅ Viable   | ✅ Viable    | Objectives/activities and save order support it; main work is UI and backend mapping. |
| **Quarterly (objectives/activities types)**   | ⚠️ Partially | ⚠️ Partially | ⚠️ Partially | Feasible once schema (activity_id) and form/controller are aligned; need per‑type verification. |
| **ReportCommonForm, monthly developmentProject reportform** | ⚠️ To align | ⚠️ To align | —            | If still used, apply same pattern as ReportAll/edit.                  |
| **Aggregated, PDF/DOC**                       | —           | —           | ✅ Viable    | Change grouping from `description` to `activity_id` (+ “Unassigned”). |

---

*Document version: 1.0  
Last updated: 2025‑01*
