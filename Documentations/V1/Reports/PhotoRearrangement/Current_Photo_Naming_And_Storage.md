# Current Photo Naming and Storage

## 1. Monthly Reports (ReportController, MonthlyDevelopmentProjectController)

### 1.1 Storage folder

```
REPORTS/{project_id}/{report_id}/photos/{month_year}/
```

- **`project_id`** — e.g. `DP-0001`
- **`report_id`** — e.g. `DP-0001-202501-001` (from `generateReportId()`)
- **`month_year`** — `m_Y` from `reporting_period_from`, e.g. `01_2025`

**Example full path (relative to `storage/app/public`):**  
`REPORTS/DP-0001/DP-0001-202501-001/photos/01_2025/`

### 1.2 File naming

- **Method:** `$file->storeAs($folderPath, $file->getClientOriginalName(), 'public')`
- **Filename:** **Original client filename** from the upload (e.g. `IMG_20240115_123456.jpg`, `photo.png`, `DSC_0001.JPG`).
- **Implications:**
  - User-controlled; can include spaces, Unicode, or long names.
  - **Risk of overwrite** if two files in the same report have the same name (same folder).
  - Extension is whatever the user uploaded (`.jpg`, `.png`, `.heic`, etc.).

### 1.3 Database (DP_Photos)

| Column       | Set on create? | Source / notes                          |
|-------------|----------------|------------------------------------------|
| `photo_id`  | Yes            | `{report_id}-{4-digit}` e.g. `DP-0001-202501-001-0001` |
| `report_id` | Yes            | Report ID                                |
| `photo_path`| Yes            | Full path: `REPORTS/.../photos/01_2025/IMG_xxx.jpg`   |
| `photo_name`| **No**         | Not set → `null`; view/PDF use `$photo->photo_name ?? 'Photo'` |
| `description` | Yes          | From `photo_descriptions[groupIndex]`    |

### 1.4 Code references

- **ReportController**  
  - `handlePhotos()`: lines ~728–733 (create), ~842–847 (update)  
  - `updatePhotos()`: same folder and `storeAs(..., getClientOriginalName(), 'public')`
- **MonthlyDevelopmentProjectController**  
  - `store()`: `$folderPath = "REPORTS/{$request->project_id}/{$report->report_id}/photos/{$monthYear}";`  
  - `$path = $file->storeAs($folderPath, $file->getClientOriginalName(), 'public');`

---

## 2. Monthly Report Attachments (ReportAttachmentController)

Attachments are **not** report photos; they are PDF/DOC/XLS etc.

- **Folder:**  
  `REPORTS/{project_id}/{report_id}/attachments/{month_year}/`
- **Filename:** Sanitized from `file_name` + original extension via `sanitizeFilename()`.
- **Table:** `report_attachments` (`file_path`, `file_name`, etc.).

---

## 3. Quarterly Reports

### 3.1 Storage folder

All quarterly report types use the **same** base folder:

```
ReportImages/Quarterly/
```

- **Flat folder** — no `project_id`, `report_id`, or `month_year` in the path.
- **Example path:** `ReportImages/Quarterly/Ab3xYz...xyz.jpg`

### 3.2 File naming

- **Method:** `$file->store('ReportImages/Quarterly', 'public')`
- **Filename:** **Laravel-generated** — `Str::random(40)` plus the original file’s extension (e.g. `Ab3xYz...xyz.jpg`). No `getClientOriginalName()`.
- **Implications:**
  - Unique per file; no overwrite from same name.
  - No project/report in path; harder to organise or purge by project/report on disk alone.

### 3.3 Per-type models and path column

| Report type              | Model       | Path column in create/update |
|--------------------------|------------|------------------------------|
| Development              | RQDPPhoto  | `photo_path`                 |
| Development Livelihood   | RQDLPhoto  | `path`                       |
| Institutional Support    | RQISPhoto  | `photo_path`                 |
| Skill Training           | RQSTPhoto  | `photo_path` (store), `path` (update) |
| Women in Distress        | RQWDPhoto  | `photo_path`                 |

- **DevelopmentLivelihoodController** and **SkillTrainingController** (update) use **`path`**; the others use **`photo_path`**. The actual DB column name may differ by migration; controllers must match the model’s attribute.

### 3.4 Code references

- **DevelopmentProjectController:**  
  - store: `$file->store('ReportImages/Quarterly', 'public')` → `photo_path`  
  - update: same `store()`, `photo_path`
- **DevelopmentLivelihoodController:**  
  - store: `$file->store('ReportImages/Quarterly', 'public')` → `path`  
  - update: same `store()`, `path`
- **InstitutionalSupportController:**  
  - store: `$file->store('ReportImages/Quarterly', 'public')` → `photo_path`
- **SkillTrainingController:**  
  - store: `$file->store('ReportImages/Quarterly', 'public')` → `photo_path`  
  - update: `$file->store('ReportImages/Quarterly', 'public')` → `path`
- **WomenInDistressController:**  
  - store: `$file->store('ReportImages/Quarterly', 'public')` → `photo_path`

---

## 4. Summary

| Aspect        | Monthly (ReportController, MonthlyDev) | Quarterly (all 5 types)      |
|---------------|----------------------------------------|------------------------------|
| **Folder**    | `REPORTS/{project_id}/{report_id}/photos/{month_year}/` | `ReportImages/Quarterly/`     |
| **Filename**  | `getClientOriginalName()`              | Laravel `store()` random 40-char + ext |
| **Path col**  | `photo_path`                           | `photo_path` or `path`       |
| **Unique ID** | `photo_id` (before `storeAs`)          | Not used in path/filename    |

---

## 5. Notes for optimization / relocation

1. **Monthly**
   - `photo_id` is known **before** `storeAs`. When adding optimization, the file can be stored as e.g. `{photo_id}.jpg` to avoid overwrites and to keep a stable, predictable name.
   - `getClientOriginalName()` can be kept as fallback when optimization is skipped (e.g. non-image or optimization error); consider appending a short random suffix if uniqueness is required.
2. **Quarterly**
   - `store()` already gives unique names; optimization can keep that by writing with `Storage::put($folder . '/' . Str::random(40) . '.jpg', $data)` or by generating a unique name before calling the service.
3. **Report attachments** use a different path (`.../attachments/...`) and should not be mixed with report photos.

---

## 6. Proposed photo filename (with activity mapping)

When **photo–activity mapping** is in place, monthly report photos will be named by **ReportID + month-year + objective + activity + 2‑digit incremental**:

### 6.1 Pattern

```
{ReportID}_{MMYYYY}_{ObjectiveNum}_{ActivityNum}_{Incremental}.{ext}
```

| Part           | Meaning                                                       | Example   |
|----------------|---------------------------------------------------------------|-----------|
| **ReportID**   | `report_id`                                                   | `DP-0001-202501-001` |
| **MMYYYY**     | Month and year of report (`reporting_period_from`), no separator | `012025`  |
| **ObjectiveNum** | 2‑digit, 1‑based objective index in the report             | `01`, `02` |
| **ActivityNum**  | 2‑digit, 1‑based activity index within that objective      | `01`, `02` |
| **Incremental**  | 2‑digit, 1–3 for the up‑to‑3 photos per activity           | `01`, `02`, `03` |
| **ext**        | File extension (e.g. `jpg` when using the optimization service) | `jpg`     |

### 6.2 Examples

- `DP-0001-202501-001_012025_01_02_01.jpg` — Report `DP-0001-202501-001`, Jan 2025, Objective 1, Activity 2, 1st photo.
- `DP-0001-202501-001_012025_02_01_03.jpg` — Objective 2, Activity 1, 3rd photo.

### 6.3 Unassigned photos (`activity_id` null)

- Use **`00_00`** for ObjectiveNum and ActivityNum.
- Incremental: `01`, `02`, … as needed.
- Example: `DP-0001-202501-001_012025_00_00_01.jpg`

### 6.4 Folder

- Unchanged: `REPORTS/{project_id}/{report_id}/photos/{month_year}/`
- Full path example: `REPORTS/DP-0001/DP-0001-202501-001/photos/01_2025/DP-0001-202501-001_012025_01_02_01.jpg`

### 6.5 Deriving the parts in code

- **ReportID:** `$report->report_id`
- **MMYYYY:** `date('mY', strtotime($report->reporting_period_from))`
- **ObjectiveNum / ActivityNum:** from `$activity` → `$activity->objective`; 1-based index of that objective in `$report->objectives`, and 1-based index of `$activity` in `$objective->activities`. Alternatively from `activity_id` / `objective_id` (e.g. last `-NNN` segment, then `sprintf('%02d', (int)$nnn)`).
- **Incremental:** for the given `activity_id`, `sprintf('%02d', 1 + (count of existing `DPPhoto` with that `activity_id`))` or, when saving a group of 1–3 files, the 1-based file index in the group (`01`, `02`, `03`).

---

*Document version: 1.1 — Photo Rearrangement (activity-based naming added)*
