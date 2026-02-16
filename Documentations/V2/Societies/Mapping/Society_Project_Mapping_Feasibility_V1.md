# Society–Project Mapping Feasibility (V1)

**Objective:** Replace hardcoded society dropdowns with dynamic data from the `societies` table and replace `society_name` (string) with `society_id` (FK) on `projects` and on user creation flows (General and Provincial).

**Status:** Feasibility audit only. No implementation.

---

## 1. Current Architecture

### 1.1 Tables Using Society Data

| Table | Column | Type | Nullable | Indexed | Notes |
|-------|--------|------|----------|---------|------|
| **projects** | society_name | string | Yes | No | `2024_07_20_085634_create_projects_table.php` |
| **users** | society_name | string | Yes | No | `2014_10_12_000000_create_users_table.php`; used for executors, applicants, provincials |
| **societies** | id, province_id, name, address, is_active | — | — | province_id, name, unique(province_id,name) | address added in later migration; no society_id on projects/users |
| **quarterly_reports** | society_name | string | Yes | No | Denormalized from project |
| **annual_reports** | society_name | string | Yes | No | Denormalized |
| **half_yearly_reports** | society_name | string | Yes | No | Denormalized |
| **dp_reports** (monthly) | society_name | string | Yes | No | Denormalized |

- **projects:** Single canonical store for project’s society; string only.
- **users:** Executors, applicants, and provincials have `society_name`; no `society_id`.
- **reports:** Store `society_name` as copy; no direct FK to societies.

### 1.2 Models

- **App\Models\OldProjects\Project:** `$fillable` includes `society_name`; no `society()` relation.
- **App\Models\User:** `$guarded = []`; has `society_name`; has `province_id` (nullable, FK) and `province` relation; no `society_id` or `society()` relation.
- **App\Models\Society:** Has `province_id`, `name`, `address`, `is_active`; unique per `(province_id, name)`; no soft deletes.

### 1.3 Controllers and Validation

- **ProjectController:** Reads/writes `society_name` in general info payload and update (StoreProjectRequest, UpdateProjectRequest, UpdateGeneralInfoRequest, StoreGeneralInfoRequest all allow `society_name` nullable string max 255).
- **GeneralController:** Create/update executor and create/update provincial use `society_name` (required for executor, nullable for provincial); store/update pass string to User.
- **ProvincialController:** Create/update executor use `society_name` (required); create/update provincial use `society_name` (nullable).
- **ExportController (Projects):** Uses `$project->society_name` in DOC export (single line).
- **Reports (Monthly):** ReportController and monthly report views use `society_name` (nullable string); reports are denormalized from project.

### 1.4 Views Using Society

| View | Field name | Source | Notes |
|------|------------|--------|-------|
| projects/partials/general_info.blade.php | society_name | Hardcoded 9 options | Create project; selects by user’s society_name |
| projects/partials/Edit/general_info.blade.php | society_name | Hardcoded (multiple blocks) | Edit project; one block uses typo "ST. ANNS'S" |
| projects/partials/Show/general_info.blade.php | — | Displays project.society_name | Read-only |
| general/executors/create.blade.php | society_name | Dynamic from provinces + activeSocieties | Options populated by JS; value = society.name |
| general/executors/edit.blade.php | society_name | Hardcoded 9 options | Same list as general_info |
| general/provincials/create.blade.php | society_name | Dynamic (API/JS by province) | value = society.name |
| general/provincials/edit.blade.php | society_name | Dynamic by province | value = society.name |
| provincial/createExecutor.blade.php | society_name | Hardcoded (options not shown in snippet; same pattern as edit) | Required |
| provincial/editExecutor.blade.php | society_name | Hardcoded 9 options | Same list |
| provincial/provincials/create.blade.php | society_name | Dynamic from $societies (Society model) | value = $society->name |
| provincial/provincials/edit.blade.php | society_name | Dynamic from $societies | value = $society->name |
| reports/monthly/show.blade.php | — | report.society_name | Display |
| reports/monthly/edit.blade.php | society_name | Read-only from report | — |

---

## 2. Identified Risks

### 2.1 Data and Schema

- **Data loss on string → FK:** Projects or users with `society_name` that do not match any `societies.name` (or match multiple due to province) cannot be backfilled without a clear rule. Unmatched rows must be handled (e.g. leave society_id NULL and optionally keep society_name for reference, or fix data first).
- **Case and spelling:** `societies.name` is unique per province and case-sensitive. Hardcoded list has one **typo**: option value `"ST. ANNS'S SOCIETY, VISAKHAPATNAM"` in create general_info and in one Edit block; correct spelling is `"ST. ANN'S SOCIETY, VISAKHAPATNAM"`. Existing DB may contain the typo string; seeder only creates the correct spelling.
- **Province ambiguity:** Same society name can exist in different provinces. Projects and users have province (or province_id) context; backfill must resolve society_id within the correct province.
- **Historical records:** Old projects/users with free-text society_name that never matched the hardcoded list may have values not present in `societies` at all.

### 2.2 Reports and Exports

- **Reports:** quarterly, annual, half_yearly, and monthly (dp) store `society_name`. They are generated/copied from projects. After projects use society_id, reports can either keep storing society_name (derived from society_id) or be extended with society_id; either way, export and display must resolve name from FK where applicable.
- **ExportController:** Uses `$project->society_name`; after migration this should use `$project->society->name` (or equivalent) when society_id is set, with fallback for NULL/legacy.

### 2.3 Authorization and Scoping

- **Provincial:** Must only see/select societies from their province. Current provincial provincials create/edit already use `Society::where('province_id', $province->id)`. Same scoping must apply when switching to society_id.
- **General:** Sees all provinces; society list must be scoped by selected province (or show province in list) to avoid ambiguous names. General executor create already loads societies by province (activeSocieties); same pattern for society_id.

### 2.4 Soft Deletes and Integrity

- **Societies:** No soft deletes. If a society is deleted, projects/users with that society_id would need FK policy (e.g. SET NULL or RESTRICT). Recommendation: nullable society_id + onDelete('set null').

### 2.5 Validation

- No validation currently enforces that project or user society_name is one of the hardcoded values; only max length and nullable. After migration, validation should use `exists:societies,id` and, for provincial, ensure society belongs to user’s province.

---

## 3. Hardcoded Value Audit Results

### 3.1 Extracted Values (from general_info and executor/provincial views)

| # | Value as in UI / option value | Used as option value in create form? | Note |
|---|-------------------------------|--------------------------------------|------|
| 1 | ST. ANN'S EDUCATIONAL SOCIETY | Yes | |
| 2 | SARVAJANA SNEHA CHARITABLE TRUST | Yes | |
| 3 | WILHELM MEYERS DEVELOPMENTAL SOCIETY | Yes | |
| 4 | ST. ANNS'S SOCIETY, VISAKHAPATNAM | Yes (create general_info) | **Typo:** ANNS'S instead of ANN'S |
| 5 | ST. ANN'S SOCIETY, VISAKHAPATNAM | Yes (edit, other blocks) | Correct spelling; seeder uses this |
| 6 | ST.ANN'S SOCIETY, SOUTHERN REGION | Yes | |
| 7 | ST. ANNE'S SOCIETY | Yes | |
| 8 | BIARA SANTA ANNA, MAUSAMBI | Yes | |
| 9 | ST. ANN'S CONVENT, LURO | Yes | |
| 10 | MISSIONARY SISTERS OF ST. ANN | Yes | |

### 3.2 SocietySeeder Predefined Societies

Only **Vijayawada** and **Visakhapatnam** get predefined names:

- **Vijayawada:** SARVAJANA SNEHA CHARITABLE TRUST, ST. ANN'S EDUCATIONAL SOCIETY  
- **Visakhapatnam:** ST. ANN'S SOCIETY, VISAKHAPATNAM, WILHELM MEYERS DEVELOPMENTAL SOCIETY  

Other provinces get societies from existing users’ and projects’ `society_name` (distinct, trimmed). So:

- **Not in seeder by default:** ST.ANN'S SOCIETY, SOUTHERN REGION; ST. ANNE'S SOCIETY; BIARA SANTA ANNA, MAUSAMBI; ST. ANN'S CONVENT, LURO; MISSIONARY SISTERS OF ST. ANN. They will exist in `societies` only if (a) those provinces exist and (b) users/projects already contain those strings, or they are created manually / via society CRUD.
- **Typo:** "ST. ANNS'S SOCIETY, VISAKHAPATNAM" is **not** created by seeder. Seeder uses "ST. ANN'S SOCIETY, VISAKHAPATNAM". So any project or user storing the typo string has a value that **does not** exist in `societies` (unless added manually).

### 3.3 Mismatched or Missing Societies (Summary)

| Issue | Detail |
|-------|--------|
| **Spelling mismatch** | Create form (general_info) submits "ST. ANNS'S SOCIETY, VISAKHAPATNAM"; DB may contain this. Societies table has "ST. ANN'S SOCIETY, VISAKHAPATNAM". Backfill must map typo → correct society (e.g. in Visakhapatnam) or leave NULL and fix data. |
| **Missing in seeder** | ST.ANN'S SOCIETY, SOUTHERN REGION; ST. ANNE'S SOCIETY; BIARA SANTA ANNA, MAUSAMBI; ST. ANN'S CONVENT, LURO; MISSIONARY SISTERS OF ST. ANN are not in seeder. Ensure they exist in the right provinces (via data or manual/CRUD) before backfill, or backfill will leave society_id NULL for those names. |
| **Duplicate meaning** | Only one real duplicate: typo vs correct "ST. ANN'S SOCIETY, VISAKHAPATNAM". |

**Recommendation:** Before backfill: (1) Add missing society names to appropriate provinces (script or CRUD). (2) Normalize "ST. ANNS'S SOCIETY, VISAKHAPATNAM" to "ST. ANN'S SOCIETY, VISAKHAPATNAM" in projects and users (or map typo to correct society_id in backfill).

---

## 4. Migration Strategy (Design Only)

### 4.1 Projects Table

- **Step 1:** Add column  
  `society_id` unsignedBigInteger nullable, after `society_name`.  
  FK → `societies.id`, `onDelete('set null')`. Index on `society_id`.
- **Step 2:** Backfill (see Section 5). Set `projects.society_id` from `projects.society_name` + project’s province (via user or province_id if present), with handling for typo and unmatched names.
- **Step 3:** Validate backfill (no orphaned FK; counts of NULL vs non-NULL; spot-check).
- **Step 4:** Drop column `society_name` (only after code and views use society_id and display uses relation).

### 4.2 Users Table

- **Step 1:** Add column  
  `society_id` unsignedBigInteger nullable, after `society_name`.  
  FK → `societies.id`, `onDelete('set null')`. Index on `society_id`.
- **Step 2:** Backfill using `users.society_name` and `users.province_id` (or province name) to resolve society per province.
- **Step 3:** Validate; then drop `society_name` when all code uses society_id.

### 4.3 Reports Tables (Optional / Later)

- Keep storing `society_name` for now (denormalized display). When generating reports from projects, set `report.society_name = $project->society->name ?? $project->society_name` during transition, then from `$project->society->name` only after projects no longer have society_name.
- If desired, add `society_id` to report tables in a later phase and backfill from project.

### 4.4 Safe Migration Order

1. Ensure all hardcoded society names exist in `societies` (correct province) and fix typo in data or backfill logic.
2. Add `society_id` to `projects` (migration); backfill; validate.
3. Add `society_id` to `users` (migration); backfill; validate.
4. Deploy code and views to use `society_id` and show name via relation; keep reading `society_name` only for fallback/display during transition.
5. After validation period, drop `society_name` from `projects` and then from `users` (separate migrations).

---

## 5. Data Backfill Plan

### 5.1 Resolving Society from Name + Province

- **Projects:** Province comes from `projects.user_id` → `users.province` or `users.province_id`. Resolve society:  
  `Society::where('province_id', $provinceId)->where('name', $normalizedName)->first()`  
  Use normalized name: replace "ST. ANNS'S SOCIETY, VISAKHAPATNAM" with "ST. ANN'S SOCIETY, VISAKHAPATNAM" before lookup.
- **Users:** Province from `users.province_id` or `users.province` (name) → province_id. Then same Society lookup by province_id + normalized name.

### 5.2 Backfill SQL Strategy (Conceptual)

- Prefer **application-level backfill** (Artisan command or script) so you can use Eloquent and Province/Society resolution:
  - For each project: get user → province; normalize society_name; find society by province_id + name; set project.society_id.
  - For each user: get province_id; normalize society_name; find society; set user.society_id.
- **Unmatched:** Leave society_id NULL. Optionally keep society_name in a temporary column or log for manual fix.
- **Duplicate name in same province:** Should not occur (unique constraint); if data is wrong, fix before backfill.

### 5.3 Handling Unmatched Legacy Values

- Do not set society_id for unmatched names.
- Option A: Keep society_name column for one release and show “Name: X (unmatched)” in admin for NULL society_id with legacy name, then fix data and re-run backfill or manual update.
- Option B: Add a one-off “legacy_society_name” nullable column for backfill period, then drop after cleanup.

---

## 6. Rollback Plan

- **Before dropping society_name:** Keep society_name column. Code can write both society_id and society_name (from society->name) for a period so rollback is revert code only.
- **After dropping society_name:** Rollback requires migration that re-adds society_name and repopulates it from `societies.name` via society_id (no historical recovery for values that were never matched).

---

## 7. Controller Changes Required

- **ProjectController:** Accept `society_id` (nullable, exists:societies,id); validate province scope for provincial. Save society_id; for display/export use `$project->society->name` with fallback. When dropping society_name, remove any writes to it.
- **GeneralController (create/update executor, create/update provincial):** Accept `society_id` instead of or in addition to society_name; validate exists and, for provincial, society belongs to user’s province. Save society_id to User.
- **ProvincialController (create/update executor, create/update provincial):** Same; scope society list to provincial’s province and validate society_id belongs to that province.
- **ExportController:** Use `$project->society->name ?? $project->society_name` during transition; then `$project->society->name` only.
- **Report controllers:** When setting report from project, use project’s society name via relation or legacy society_name during transition.

---

## 8. Validation Changes Required

- **StoreProjectRequest / UpdateProjectRequest / StoreGeneralInfoRequest / UpdateGeneralInfoRequest:** Add `society_id` => `'nullable|exists:societies,id'`. Optionally add rule that society’s province matches project’s user province. Remove or keep society_name only for legacy fallback.
- **General executor create/update:** `society_id` => `'required|exists:societies,id'` (and scope by province for General).
- **Provincial executor create/update:** `society_id` => `'required|exists:societies,id'` with custom rule: society belongs to provincial’s province.
- **Provincial create/update:** `society_id` => `'nullable|exists:societies,id'` with same province scope.

---

## 9. View Changes Required

- **projects/partials/general_info.blade.php:** Replace `<select name="society_name">` with `<select name="society_id">`. Options from `$societies` (or similar) with `value="{{ $society->id }}"` and label `{{ $society->name }}`. Preselect `old('society_id', $project->society_id ?? $user->society_id)` in create; in edit use `$project->society_id`. Scope societies by province for provincial; for General, pass societies grouped by province or with province in label.
- **projects/partials/Edit/general_info.blade.php:** Same; all blocks that currently use society_name dropdown.
- **projects/partials/Show/general_info.blade.php:** Display `$project->society->name ?? $project->society_name` during transition; then `$project->society->name`.
- **general/executors/create.blade.php:** Already loads societies by province; change to submit society_id (option value = society.id) and keep dropdown populated from same source.
- **general/executors/edit.blade.php:** Replace hardcoded options with dynamic list (from controller); name="society_id", value=society->id.
- **general/provincials/create.blade.php and edit.blade.php:** Change to society_id (value = society->id); backend validates and saves society_id.
- **provincial/createExecutor.blade.php and editExecutor.blade.php:** Replace hardcoded list with dynamic `@foreach($societies as $society)` from controller; name="society_id", value="{{ $society->id }}".
- **provincial/provincials/create and edit:** Already use $societies; switch to society_id and value="{{ $society->id }}".

---

## 10. Authorization Implications

- **General:** May select any society (or societies scoped by selected province in executor flow). No change to role checks; only ensure society list includes all provinces or is clearly scoped by province.
- **Provincial:** Must only see and select societies where `society.province_id === auth user's province_id`. Controllers already scope society list; validation must enforce that submitted society_id belongs to that province (e.g. `Society::where('id', $id)->where('province_id', $provinceId)->exists()`).

---

## 11. Performance Considerations

- **N+1:** When listing projects or users with society name, eager load `society` relation (e.g. `with('society')`) to avoid N+1.
- **Indexes:** society_id on projects and users should be indexed (included in migration above).
- **Dropdowns:** General create project may need societies for all provinces or per-province; consider caching or single query with `Society::with('province')->active()->orderBy('province_id')->orderBy('name')` and group in Blade/JS.

---

## 12. Recommended Execution Order (Step-by-Step)

1. **Data prep:** Ensure all 9 distinct society names (with typo corrected) exist in `societies` in the correct provinces. Add missing ones via seeder update or CRUD. Normalize existing projects/users "ST. ANNS'S SOCIETY, VISAKHAPATNAM" to "ST. ANN'S SOCIETY, VISAKHAPATNAM" if desired before backfill.
2. **Migration: add society_id to projects** (nullable, FK, index); run migration.
3. **Backfill projects:** Artisan command: set projects.society_id from society_name + user province; normalize typo; leave NULL where no match.
4. **Verify projects:** Spot-check and counts; ensure export and show views can use relation.
5. **Migration: add society_id to users** (nullable, FK, index); run migration.
6. **Backfill users:** Artisan command: set users.society_id from society_name + province_id/province.
7. **Verify users:** Spot-check and counts.
8. **Code and views:** Switch project create/edit and general info to society_id (validation, controller, views); switch General/Provincial executor and provincial create/edit to society_id; update ExportController and report generation to use society relation with fallback.
9. **Deploy and test:** General and Provincial create/edit project and member; verify province scoping and display.
10. **Drop society_name:** Migration to drop projects.society_name; then migration to drop users.society_name. Remove any fallback code.
11. **Reports (optional):** Add society_id to report tables and backfill from project; then use relation for display/export.

---

## Summary of Findings

- **projects:** Use `society_name` (string, nullable); not indexed. Safe to add `society_id` and backfill; one typo and several names may be missing in `societies` unless data or seeder is extended.
- **users:** Use `society_name` (string, nullable) for executors, applicants, provincials. Same backfill and scoping as projects.
- **Reports and export:** Depend on society name; can be switched to derive from FK with fallback during transition.
- **Mismatched societies:** (1) Typo "ST. ANNS'S SOCIETY, VISAKHAPATNAM" vs "ST. ANN'S SOCIETY, VISAKHAPATNAM". (2) Five names not in seeder; must exist in DB (right provinces) for full backfill.
- **Migration is feasible** provided: data is prepared (societies exist, typo handled), backfill is province-aware and normalized, and views/validation are updated with province scoping for provincial users.

---

**Document path:** `Documentations/V2/Societies/Mapping/Society_Project_Mapping_Feasibility_V1.md`

---

## Confirmation: Is the Migration Safe?

**Yes, provided:**

1. **Data preparation:** All required society names exist in `societies` in the correct provinces (add the five not in seeder if needed). Normalize the typo "ST. ANNS'S SOCIETY, VISAKHAPATNAM" in existing data or in backfill logic so it maps to "ST. ANN'S SOCIETY, VISAKHAPATNAM".
2. **Backfill is non-destructive:** Add `society_id` as nullable; backfill; leave NULL for unmatched rows; drop `society_name` only after code and validation use `society_id` and after a validation period.
3. **Province scoping:** Validation and queries for provincial users restrict society_id to the user’s province.

Without data preparation, backfill will leave some rows with `society_id` NULL (and optionally keep `society_name` for reference until cleaned up).
